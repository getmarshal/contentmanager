<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Marshal\ContentManager\Event\SQLQueryEvent;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Marshal\Util\Database\DatabaseAwareInterface;
use Marshal\Util\Database\DatabaseAwareTrait;
use Marshal\Util\Database\QueryBuilder;
use Marshal\Util\Database\Schema\Property;
use Marshal\Util\Database\Schema\Type;
use loophp\collection\Collection;

final class ContentRepository implements DatabaseAwareInterface, EventDispatcherAwareInterface
{
    use DatabaseAwareTrait;
    use EventDispatcherAwareTrait;

    private const string OP_SELECT = "where";
    private const string OP_UPDATE = "update";

    public function __construct(private ContentManager $contentManager)
    {
    }

    public function create(Content $content): int|string|null
    {
        // prepare the query
        $connection = $this->getDatabaseConnection($content->getType()->getDatabase());
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->insert($content->getType()->getTable());

        foreach ($content->getProperties() as $property) {
            if ($property->isAutoIncrement()) {
                continue;
            }

            $queryBuilder->setValue(
                $property->getName(),
                $queryBuilder->createNamedParameter(
                    $property->getDatabaseValue($connection->getDatabasePlatform()),
                    $property->getDatabaseType()->getBindingType()
                )
            );
        }

        $result = $queryBuilder->executeStatement();
        if (! \is_numeric($result) || \intval($result) <= 0) {
            return null;
        }

        return $connection->lastInsertId();
    }

    public function delete(Content $content, array $args = []): QueryBuilder
    {
        // build the delete query
        $connection = $this->getDatabaseConnection($content->getType()->getDatabase());
        $queryBuilder = $connection->createQueryBuilder();
        // $queryBuilder->delete("{$content->getType()->getTable()} {$content->getType()->getTable()}");
        // $this->applyQueryArgs($queryBuilder, $content, $args);
        return $queryBuilder;
    }

    public function filter(ContentQuery $query): Collection
    {
        $content = $this->contentManager->get($query->getSchema());
        $connection = $this->getDatabaseConnection($content->getType()->getDatabase());

        $table = $content->getType()->getTable();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select("$table.*")->from($table, $table);

        $duplicates = [];
        foreach ($content->getProperties() as $property) {
            if (! $property->hasRelation()) {
                continue;
            }

            $this->applyRelations($queryBuilder, $property, $table, $duplicates);
        }

        // apply query arguments
        $this->applyQueryArgs($queryBuilder, $content, $query);

        foreach ($query->getGroupBy() as $expression) {
            $queryBuilder->addGroupBy($expression);
        }

        foreach ($query->getOrderBy() as $property => $direction) {
            $queryBuilder->addOrderBy($property, $direction);
        }

        $this->getEventDispatcher()->dispatch(new SQLQueryEvent(
            sql: $queryBuilder->getSQL(),
            params: $queryBuilder->getParameters(),
        ));

        $iterable = $queryBuilder->setFirstResult($query->getOffset())
            ->setMaxResults($query->getLimit())
            ->executeQuery()
            ->iterateAssociative();

        $platform = $connection->getDatabasePlatform();
        $toArray = $query->getToArray();

        return Collection::fromCallable(static function () use ($iterable, $toArray, $content, $platform): \Generator {
            foreach ($iterable as $row) {
                yield $toArray
                    ? $content->hydrate($row, $platform)->toArray()
                    : $content->hydrate($row, $platform);
            }
        });
    }

    public function get(ContentQuery $query): Content
    {
        $content = $this->contentManager->get($query->getSchema());

        // build the query
        $table = $content->getType()->getTable();
        $connection = $this->getDatabaseConnection($content->getType()->getDatabase());
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select("$table.*")
            ->from($table, $table)
            ->setMaxResults(1);

        $duplicates = [];
        foreach ($content->getProperties() as $property) {
            if (! $property->hasRelation()) {
                continue;
            }

            $this->applyRelations($queryBuilder, $property, $table, $duplicates);
        }

        // apply query arguments
        $this->applyQueryArgs($queryBuilder, $content, $query);

        $this->getEventDispatcher()->dispatch(new SQLQueryEvent(
            sql: $queryBuilder->getSQL(),
            params: $queryBuilder->getParameters(),
        ));

        $result = $queryBuilder->executeQuery()->fetchAssociative();
        if (! empty($result)) {
            $content->hydrate($result, $connection->getDatabasePlatform());
        }

        return $content;
    }

    public function update(Content $content, array $data): int|string
    {
        // build the delete query
        $connection = $this->getDatabaseConnection($content->getType()->getDatabase());
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->update($content->getType()->getTable());

        $query = new ContentQuery();
        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }

        $this->applyQueryArgs(
            queryBuilder: $queryBuilder,
            content: $content,
            query: $query,
            platform: $connection->getDatabasePlatform(),
            operation: self::OP_UPDATE
        );

        // set the where clause using the model primary key
        $queryBuilder->where($queryBuilder->expr()->eq(
            $content->getType()->getAutoIncrement()->getName(),
            $queryBuilder->createNamedParameter($content->getAutoId())
        ));

        return $queryBuilder->executeStatement();
    }

    private function applyQueryArgs(
        QueryBuilder $queryBuilder,
        Content $content,
        ContentQuery $query,
        ?AbstractPlatform $platform = NULL,
        string $operation = self::OP_SELECT
    ): void {
        foreach ($query->getWhere() as $name => $value) {
            // potentially modified Property/argument
            if (! $content->hasProperty($name)) {
                $this->buildModifiedProperty($queryBuilder, $content->getType(), $name, $value);
                continue;
            }

            $property = $content->getProperty($name);

            if ($value instanceof Content) {
                $normalizedValue = $value->getAutoId();
            } elseif (\is_array($value) && ! empty($value) && $operation === self::OP_SELECT) {
                $this->buildArrayValue($queryBuilder, $property, $value);
                continue;
            } else {
                $normalizedValue = $value;
            }

            switch ($operation) {
                case self::OP_SELECT:
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->eq(
                            $content->getTable() . '.' . $property->getName(),
                            $queryBuilder->createNamedParameter(
                                $normalizedValue,
                                $property->getDatabaseType()->getBindingType()
                            )
                        )
                    );
                    break;

                case self::OP_UPDATE:
                    $queryBuilder->set(
                        $property->getName(),
                        $queryBuilder->createNamedParameter(
                            $property->getDatabaseType()->convertToDatabaseValue($normalizedValue, $platform),
                            $property->getDatabaseType()->getBindingType()
                        )
                    );
                    break;
            }
        }
    }

    private function applyRelations(QueryBuilder $queryBuilder, Property $property, string $table, array &$duplicates): void
    {
        $duplicates[] = $property->getRelation()->getAlias();

        $subSelect = [];
        foreach ($property->getRelation()->getType()->getProperties() as $subProperty) {
            $subSelect[] = "'{$subProperty->getName()}', {$property->getRelation()->getAlias()}.{$subProperty->getName()}";
        }

        // @todo this is pgsql only!
        $queryBuilder
            ->addSelect(\sprintf("JSON_BUILD_OBJECT(%s) AS %s",
                \implode(', ', $subSelect),
                $property->getName()
            ))->leftJoin(
                fromAlias: $table,
                join: $property->getRelation()->getType()->getTable(),
                alias: $property->getRelation()->getAlias(),
                condition: $table . '.' . $property->getName() . '=' . $property->getName() . '.' . $property->getRelationProperty()->getName()
            );

        foreach($property->getRelation()->getType()->getProperties() as $innerProperty) {
            if (! $innerProperty->hasRelation() || \in_array($innerProperty->getRelation()->getAlias(), $duplicates, TRUE)) {
                continue;
            }

            $duplicates[] = $innerProperty->getRelation()->getAlias();
            $this->applyRelations($queryBuilder, $innerProperty, $property->getRelation()->getAlias(), $duplicates);
        }
    }

    private function buildArrayValue(QueryBuilder $queryBuilder, Property $property, array $value): void
    {
        foreach ($value as $key => $subValue) {
            if (\str_contains($key, '__')) {
                $this->buildModifiedProperty(
                    $queryBuilder,
                    $property->getRelation()->getType(),
                    $key,
                    $subValue
                );
                continue;
            }

            if ($subValue instanceof Content) {
                $this->buildScalarValue($queryBuilder, $property, $key, $subValue->getAutoId());
                continue;
            }

            if (\is_scalar($subValue)) {
                $this->buildScalarValue($queryBuilder, $property, $key, $subValue);
                continue;
            }

            if (\is_array($subValue)) {
                foreach ($property->getRelation()->getType()->getProperties() as $subProperty) {
                    if ($subProperty->getName() !== $key) {
                        continue;
                    }

                    $this->buildArrayValue($queryBuilder, $subProperty, $subValue);
                }
            }
        }
    }

    private function buildModifiedProperty(QueryBuilder $queryBuilder, Type $type, string $arg, mixed $value): void
    {
        $split = \explode('__', $arg);
        if (\count($split) !== 2) {
            // potentially raw query
            $this->buildRawWhereQuery($queryBuilder, $arg, $value);
            return;
        }

        $modifiers = ['gt', 'gte', 'in', 'isnull', 'lt', 'lte', 'notin'];
        if (! \in_array(\strtolower($split[1]), $modifiers, true)) {
            if ($type->hasProperty($split[0])) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq("$split[0].$split[1]", $queryBuilder->createNamedParameter((string) $value))
                );
                return;
            }
        }

        $table = $type->getTable();
        $column = "$table.$split[0]";
        switch (\strtolower($split[1])) {
            case 'gt':
                $queryBuilder->andWhere($queryBuilder->expr()->gt(
                    $column,
                    $queryBuilder->createNamedParameter($value)
                ));
                break;

            case 'gte':
                $queryBuilder->andWhere($queryBuilder->expr()->gte(
                    $column,
                    $queryBuilder->createNamedParameter($value)
                ));
                break;

            case 'in':
                $queryBuilder->andWhere($queryBuilder->expr()->in(
                    $column,
                    \array_map(static fn (string $property): string => "'$property'", $value))
                );
                break;

            case 'isnull':
                if (FALSE === $value) {
                    $queryBuilder->andWhere($queryBuilder->expr()->isNotNull($column));
                } elseif (TRUE === $value) {
                    $queryBuilder->andWhere($queryBuilder->expr()->isNull($column));
                }
                break;

            case 'lt':
                $queryBuilder->andWhere($queryBuilder->expr()->lt(
                    $column,
                    $queryBuilder->createNamedParameter($value)
                ));
                break;

            case 'lte':
                $queryBuilder->andWhere($queryBuilder->expr()->lte(
                    $column,
                    $queryBuilder->createNamedParameter($value)
                ));
                break;

            case 'notin':
                $queryBuilder->andWhere($queryBuilder->expr()->notIn(
                    $column,
                    \array_map(static fn (string $property): string => "'$property'", $value))
                );
                break;
        }
    }

    private function buildRawWhereQuery(QueryBuilder $queryBuilder, string $expression, mixed $arg): void
    {
        if (! \is_array($arg)) {
            return;
        }

        $queryBuilder->andWhere($expression);
            foreach ($arg as $key => $value) {
                if ($value instanceof Content) {
                    $queryBuilder->setParameter($key, $value->getType()->getAutoIncrement()->getValue());
                    continue;
                }

                if (\is_scalar($value)) {
                    $queryBuilder->setParameter($key, $value);
                }

                // @todo throw exception if value is not an instance of Content, and not a scalar
                // possibly check $content for presence of $key property first
            }
    }

    private function buildScalarValue(QueryBuilder $queryBuilder, Property $property, string $name, mixed $value): void
    {
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $property->getName() . '.' . $name,
                $queryBuilder->createNamedParameter(
                    $value,
                    $property->getDatabaseType()->getBindingType()
                )
            )
        );
    }
}
