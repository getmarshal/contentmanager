<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use loophp\collection\Collection;
use Marshal\ContentManager\Event\ReadCollectionEvent;
use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\ContentManager\Schema\Content;
use Marshal\ContentManager\Schema\Property;
use Marshal\Util\Database\DatabaseAwareInterface;
use Marshal\Util\Database\DatabaseAwareTrait;
use Marshal\Util\Database\QueryBuilder;

final class ContentRepository implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    public function __construct(private ContentManager $contentManager)
    {
    }

    public function create(Content $content): int|string|null
    {
        // prepare the query
        $connection = $this->getDatabaseConnection($content->getDatabase());
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->insert($content->getTable());

        foreach ($content->getProperties() as $property) {
            if ($property->isAutoIncrement()) {
                continue;
            }

            $queryBuilder->setValue(
                $property->getIdentifier(),
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
        $connection = $this->getDatabaseConnection($content->getDatabase());
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->delete("{$content->getTable()} {$content->getTable()}");
        $this->applyArguments($queryBuilder, $content, $args);
        return $queryBuilder;
    }

    public function filter(ReadCollectionEvent $event): Collection
    {
        $content = $this->contentManager->get($event->getContentIdentifier());
        $connection = $this->getDatabaseConnection($content->getDatabase());
        // $prop = $content->getProperty('diff');
        // var_dump($prop->getDatabaseType());

        $table = $content->getTable();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select("$table.*")->from($table, $table);

        $duplicates = [];
        foreach ($content->getProperties() as $property) {
            if (! $property->hasRelation()) {
                continue;
            }

            $this->attachRelationSelect($queryBuilder, $property, $table, $duplicates);
        }

        // apply query arguments
        $this->applyArguments($queryBuilder, $content, $event->getParams());

        foreach ($event->getWhere() as $expression => $parameters) {
            $queryBuilder->andWhere($expression);
            foreach ($parameters as $key => $value) {
                $queryBuilder->setParameter($key, $value);
            }
        }

        foreach ($event->getGroupBy() as $expression) {
            $queryBuilder->addGroupBy($expression);
        }

        foreach ($event->getOrderBy() as $column => $direction) {
            $queryBuilder->addOrderBy($column, $direction);
        }

        $iterable = $queryBuilder->setFirstResult($event->getOffset())
            ->setMaxResults($event->getLimit())
            ->executeQuery()
            ->iterateAssociative();

        $platform = $connection->getDatabasePlatform();
        return Collection::fromCallable(static function () use ($iterable, $event, $content, $platform): \Generator {
            foreach ($iterable as $row) {
                yield $event->getToArray()
                    ? $content->hydrate($row, $platform)->toArray()
                    : $content->hydrate($row, $platform);
            }
        });
    }

    public function get(ReadContentEvent $event): void
    {
        $content = $this->contentManager->get($event->getContentIdentifier());

        // build the query
        $table = $content->getTable();
        $connection = $this->getDatabaseConnection($content->getDatabase());
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select("$table.*")->from($table, $table);

        $duplicates = [];
        foreach ($content->getProperties() as $property) {
            \assert($property instanceof Property);
            if (! $property->hasRelation()) {
                continue;
            }

            $this->attachRelationSelect($queryBuilder, $property, $table, $duplicates);
        }

        // apply query arguments
        $this->applyArguments($queryBuilder, $content, $event->getParams());

        foreach ($event->getWhere() as $expression => $parameters) {
            $queryBuilder->andWhere($expression);
            foreach ($parameters as $key => $value) {
                $queryBuilder->setParameter($key, $value);
            }
        }

        $result = $queryBuilder->setMaxResults(1)->executeQuery()->fetchAssociative();
        if (! empty($result)) {
            $event->setRawResult($result);
            $event->setContent($content->hydrate($result, $connection->getDatabasePlatform()));
        }
    }

    public function update(Content $content, array $data): int|string
    {
        // build the delete query
        $connection = $this->getDatabaseConnection($content->getDatabase());
        $table = $content->getTable();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->update($table);

        $this->applyArguments(
            $queryBuilder,
            $content,
            $data,
            platform: $connection->getDatabasePlatform(),
            operation: 'set'
        );

        // set the where clause using the model primary key
        $queryBuilder->where($queryBuilder->expr()->eq(
            $content->getAutoIncrement()->getIdentifier(),
            $queryBuilder->createNamedParameter($content->getAutoIncrement()->getValue())
        ));

        return $queryBuilder->executeStatement();
    }

    private function applyArguments(
        QueryBuilder $queryBuilder,
        Content $content,
        array $args,
        ?AbstractPlatform $platform = null,
        string $operation = 'where'
    ): void {
        foreach ($args as $identifier => $value) {
            // potentially modified column/argument
            if (! $content->hasProperty($identifier)) {
                $this->processColumnModifier($queryBuilder, $content, $identifier, $value);
                continue;
            }

            $property = $content->getProperty($identifier);

            if ($value instanceof Content) {
                $normalizedValue = $value->getAutoIncrement()->getValue();
            }elseif (\is_array($value) && ! empty($value) && $operation === 'where') {
                $this->processSubValue($queryBuilder, $property, $value);
                continue;
            } else {
                $normalizedValue = $value;
            }

            switch ($operation) {
                case 'where':
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->eq(
                            $content->getTable() . '.' . $property->getIdentifier(),
                            $queryBuilder->createNamedParameter(
                                $normalizedValue,
                                $property->getDatabaseType()->getBindingType()
                            )
                        )
                    );
                    break;

                case 'set':
                    $queryBuilder->set(
                        $property->getIdentifier(),
                        $queryBuilder->createNamedParameter(
                            $property->getDatabaseType()->convertToDatabaseValue($normalizedValue, $platform),
                            $property->getDatabaseType()->getBindingType()
                        )
                    );
                    break;
            }
        }
    }

    private function attachRelationSelect(QueryBuilder $queryBuilder, Property $property, string $table, array &$duplicates): void
    {
        $duplicates[] = $property->getRelation()->getAlias();

        $subSelect = [];
        foreach ($property->getRelation()->getSchema()->getProperties() as $subProperty) {
            $subSelect[] = "'{$subProperty->getIdentifier()}', {$property->getRelation()->getAlias()}.{$subProperty->getIdentifier()}";
        }

        // @todo this is pgsql only!
        $queryBuilder
            ->addSelect(\sprintf("JSON_BUILD_OBJECT(%s) AS %s",
                \implode(', ', $subSelect),
                $property->getIdentifier()
            ))->leftJoin(
                $table,
                $property->getRelation()->getSchema()->getTable(),
                $property->getRelation()->getAlias(),
                $table . '.' . $property->getIdentifier() . '=' . $property->getIdentifier() . '.' . $property->getRelationColumn()
            );

        foreach($property->getRelation()->getSchema()->getProperties() as $innerProperty) {
            if (! $innerProperty->hasRelation() || \in_array($innerProperty->getRelation()->getAlias(), $duplicates, TRUE)) {
                continue;
            }

            $duplicates[] = $innerProperty->getRelation()->getAlias();
            $this->attachRelationSelect($queryBuilder, $innerProperty, $property->getRelation()->getAlias(), $duplicates);
        }
    }

    private function processColumnModifier(QueryBuilder $queryBuilder, Content $content, string $arg, mixed $value): void
    {
        $table = $content->getTable();
        $modifiers = ['gt', 'gte', 'in', 'isnull', 'lt', 'lte', 'notIn'];
        $split = \explode('__', $arg);
        if (\count($split) === 2) {
            if (! \in_array($split[1], $modifiers, true)) {
                if ($content->hasProperty($split[0])) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->eq("$split[0].$split[1]", $queryBuilder->createNamedParameter((string) $value))
                    );
                    return;
                }
            }

            switch ($split[1]) {
                case 'gt':
                    $queryBuilder->andWhere($queryBuilder->expr()->gt(
                        "$table.$split[0]",
                        $queryBuilder->createNamedParameter($value)
                    ));
                    break;

                case 'gte':
                    $queryBuilder->andWhere($queryBuilder->expr()->gte(
                        "$table.$split[0]",
                        $queryBuilder->createNamedParameter($value)
                    ));
                    break;

                case 'in':
                    $queryBuilder->andWhere($queryBuilder->expr()->in(
                        "$table.$split[0]",
                        \array_map(static fn (string $column): string => "'$column'", $value))
                    );
                    break;

                case 'isnull':
                    if (FALSE === $value) {
                        $queryBuilder->andWhere($queryBuilder->expr()->isNotNull("$table.$split[0]"));
                    } elseif (TRUE === $value) {
                        $queryBuilder->andWhere($queryBuilder->expr()->isNull("$table.$split[0]"));
                    }
                    break;

                case 'lt':
                    $queryBuilder->andWhere($queryBuilder->expr()->lt(
                        "$table.$split[0]",
                        $queryBuilder->createNamedParameter($value)
                    ));
                    break;

                case 'lte':
                    $queryBuilder->andWhere($queryBuilder->expr()->lte(
                        "$table.$split[0]",
                        $queryBuilder->createNamedParameter($value)
                    ));
                    break;

                case 'notIn':
                    $queryBuilder->andWhere($queryBuilder->expr()->notIn(
                        "$table.$split[0]",
                        \array_map(static fn (string $column): string => "'$column'", $value))
                    );
                    break;
            }
        }
    }

    private function processSubValue(QueryBuilder $queryBuilder, Property $property, array $value): void
    {
        foreach ($value as $column => $subValue) {
            if (\str_contains($column, '__')) {
                $this->processColumnModifier(
                    $queryBuilder,
                    $property->getRelation()->getSchema(),
                    $column,
                    $subValue
                );
                continue;
            }

            if ($subValue instanceof Content) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        $property->getIdentifier() . '.' . $column,
                        $queryBuilder->createNamedParameter(
                            $subValue->getAutoIncrement()->getValue(),
                            $property->getDatabaseType()->getBindingType()
                        )
                    )
                );
                continue;
            }

            if (\is_scalar($subValue)) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        $property->getIdentifier() . '.' . $column,
                        $queryBuilder->createNamedParameter(
                            $subValue,
                            $property->getDatabaseType()->getBindingType()
                        )
                    )
                );
                continue;
            }

            if (\is_array($subValue)) {
                foreach ($property->getRelation()->getSchema()->getProperties() as $subProperty) {
                    if ($subProperty->getIdentifier() !== $column) {
                        continue;
                    }

                    $this->processSubValue($queryBuilder, $subProperty, $subValue);
                }
            }
        }
    }
}
