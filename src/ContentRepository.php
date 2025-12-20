<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Marshal\Utils\Database\DatabaseManager;
use Marshal\Utils\Database\QueryBuilder;
use Marshal\ContentManager\Schema\Property;
use loophp\collection\Collection;

final class ContentRepository
{
    private const string OP_SELECT = "where";
    private const string OP_UPDATE = "update";
    private const string OP_DELETE = "delete";

    /**
     *
     * @param Content $content
     * @return int|string|null
     */
    public static function create(Content $content): int|string|null
    {
        // prepare the query
        $connection = DatabaseManager::getConnection($content->getDatabase());
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->insert($content->getTable());

        foreach ($content->getProperties() as $property) {
            if ($property->isAutoIncrement()) {
                continue;
            }

            if (true === $property->getNotNull() && null === $property->getValue()) {
                if (\is_callable($property->getDefaultValue())) {
                    $property->setValue(\call_user_func($property->getDefaultValue()));
                } else {
                    $property->setValue($property->getDefaultValue());
                }
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

    /**
     *
     * @param Content $content
     * @param array $args
     * @return int|string
     */
    public static function delete(Content $content, array $args = []): int|string
    {
        // build the delete query
        $connection = DatabaseManager::getConnection($content->getDatabase());
        $table = $content->getTable();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->delete("$table $table");

        // prepare query
        $query = new ContentQuery($content->getTypeIdentifier());
        if (empty($args)) {
            $query->where($content->getAutoIncrement()->getName(), $content->getAutoIncrement()->getValue());
        } else {
            foreach ($args as $arg => $value) {
                $query->where($arg, $value);
            }
        }

        self::applyQueryArgs(
            queryBuilder: $queryBuilder,
            content: $content,
            query: $query,
            platform: $connection->getDatabasePlatform(),
            operation: self::OP_DELETE,
        );

        return $queryBuilder->executeStatement();
    }

    /**
     *
     * @param ContentQuery $query
     * @return Collection
     */
    public static function filter(ContentQuery $query): Collection
    {
        $content = ContentManager::get($query->getSchema());
        $connection = DatabaseManager::getConnection($content->getDatabase());

        $table = $content->getTable();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->from($table, $table);

        // apply relations
        self::applyRelations($content, $queryBuilder, $table);

        // apply query arguments
        self::applyQueryArgs($queryBuilder, $content, $query);

        foreach ($query->getGroupBy() as $expression) {
            $queryBuilder->addGroupBy($expression);
        }

        foreach ($query->getOrderBy() as $property => $direction) {
            $queryBuilder->addOrderBy($property, $direction);
        }

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

    /**
     *
     * @param ContentQuery $query
     * @return Content
     */
    public static function get(ContentQuery $query): Content
    {
        $content = ContentManager::get($query->getSchema());

        // build the query
        $table = $content->getTable();
        $connection = DatabaseManager::getConnection($content->getDatabase());
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->from($table, $table)->setMaxResults(1);

        // apply relations
        self::applyRelations($content, $queryBuilder, $table);

        // apply query arguments
        self::applyQueryArgs($queryBuilder, $content, $query);

        $result = $queryBuilder->executeQuery()->fetchAssociative();
        if (! empty($result)) {
            $content->hydrate($result, $connection->getDatabasePlatform());
        }

        return $content;
    }

    /**
     *
     * @param Content $content
     * @param array $data
     * @return int|string
     */
    public static function update(Content $content, array $data): int|string
    {
        // build the delete query
        $connection = DatabaseManager::getConnection($content->getDatabase());
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->update($content->getTable());

        $query = new ContentQuery();
        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }

        self::applyQueryArgs(
            queryBuilder: $queryBuilder,
            content: $content,
            query: $query,
            platform: $connection->getDatabasePlatform(),
            operation: self::OP_UPDATE
        );

        // set the where clause using the model primary key
        $queryBuilder->where($queryBuilder->expr()->eq(
            $content->getAutoIncrement()->getName(),
            $queryBuilder->createNamedParameter($content->getAutoIncrement()->getValue())
        ));

        return $queryBuilder->executeStatement();
    }

    /**
     *
     * @param QueryBuilder $queryBuilder
     * @param Content $content
     * @param ContentQuery $query
     * @param AbstractPlatform $platform
     * @param string $operation
     */
    private static function applyQueryArgs(
        QueryBuilder $queryBuilder,
        Content $content,
        ContentQuery $query,
        ?AbstractPlatform $platform = NULL,
        string $operation = self::OP_SELECT
    ): void {
        foreach ($query->getWhere() as $name => $value) {
            // potentially modified Property/argument
            if (! $content->hasProperty($name)) {
                self::buildModifiedProperty($queryBuilder, $content, $name, $value);
                continue;
            }

            $property = $content->getProperty($name);

            if ($value instanceof Content) {
                $normalizedValue = $value->getAutoId();
            } elseif (\is_array($value) && ! empty($value) && $operation === self::OP_SELECT) {
                self::buildArrayValue($queryBuilder, $content, $property, $value);
                continue;
            } else {
                $normalizedValue = $value;
            }

            switch ($operation) {
                case self::OP_SELECT:
                case self::OP_DELETE:
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

    /**
     *
     * @param Content $content
     * @param QueryBuilder $queryBuilder
     * @param string $table
     * @param array $duplicates
     * @param array $relations
     * @param string $tableAlias
     */
    private static function applyRelations(
        Content $content,
        QueryBuilder $queryBuilder,
        string $table,
        array &$duplicates = [],
        array &$relations = [],
        ?string $tableAlias = null
    ): void {
        foreach ($content->getProperties() as $property) {
            if ($property->hasRelation()) {
                if (! \in_array($property->getIdentifier(), $relations, true)) {
                    $queryBuilder->leftJoin(
                        fromAlias: $table,
                        join: $property->getRelation()->getTable(),
                        alias: $property->getRelation()->getAlias(),
                        condition: $content->getTable() . '.' . $property->getName() . '=' . $property->getName() . '.' . $property->getRelationProperty()->getName()
                    );
                    $relations[] = $property->getIdentifier();
                }

                foreach($property->getRelation()->getRelationProperties() as $innerProperty) {
                    if (\in_array($property->getRelation()->getAlias() . '__' . $innerProperty->getName(), $duplicates, true)) {
                        continue;
                    }

                    $queryBuilder->addSelect(\sprintf(
                        "%s AS %s",
                        $property->getRelation()->getAlias() . '.' . $innerProperty->getName(),
                        $property->getRelation()->getAlias() . '__' . $innerProperty->getName()
                    ));
                    $duplicates[] = $property->getRelation()->getAlias() . '__' . $innerProperty->getName();

                    if ($innerProperty->hasRelation()) {
                        $innerContent = $innerProperty->getRelation()->getRelationContent();
                        $innerRelationAlias = $innerProperty->getRelation()->getAlias();
                        $duplicates[] = $innerRelationAlias . '__' . $innerProperty->getName();

                        if  (! \in_array($innerProperty->getIdentifier(), $relations, true)) {
                            $queryBuilder->leftJoin(
                                fromAlias: $table,
                                join: $innerProperty->getRelation()->getTable(),
                                alias: $innerRelationAlias,
                                condition: $property->getRelation()->getAlias() . '.' . $innerProperty->getName() . '=' . $innerProperty->getName() . '.' . $innerProperty->getRelationProperty()->getName()
                            );
                            $relations[] = $innerProperty->getIdentifier();
                        }

                        self::applyRelations($innerContent, $queryBuilder, $table, $duplicates, $relations, $innerRelationAlias);
                    }
                }
            } else {
                $alias = isset($tableAlias) ? $tableAlias : $content->getTable();
                if (\in_array($alias . '__' . $property->getName(), $duplicates, true)) {
                    continue;
                }

                $queryBuilder->addSelect(\sprintf(
                    "%s AS %s",
                    $alias . '.' . $property->getName(),
                    $alias . '__' . $property->getName()
                ));
                $duplicates[] = $alias . '__' . $property->getName();
            }
        }
    }

    /**
     *
     * @param QueryBuilder $queryBuilder
     * @param Content $content
     * @param Property $property
     * @param array $value
     */
    private static function buildArrayValue(QueryBuilder $queryBuilder, Content $content, Property $property, array $value): void
    {
        foreach ($value as $key => $subValue) {
            if (\str_contains($key, '__')) {
                self::buildModifiedProperty($queryBuilder, $content, $key, $subValue, $property);
                continue;
            }

            if ($subValue instanceof Content) {
                self::buildScalarValue($queryBuilder, $property, $key, $subValue->getAutoId());
                continue;
            }

            if (\is_scalar($subValue)) {
                self::buildScalarValue($queryBuilder, $property, $key, $subValue);
                continue;
            }

            if (\is_array($subValue)) {
                foreach ($property->getRelation()->getRelationProperties() as $subProperty) {
                    if ($subProperty->getName() !== $key) {
                        continue;
                    }

                    self::buildArrayValue($queryBuilder, $content, $subProperty, $subValue);
                }
            }
        }
    }

    /**
     *
     * @param QueryBuilder $queryBuilder
     * @param Content $content
     * @param string $arg
     * @param mixed $value
     */
    private static function buildModifiedProperty(
        QueryBuilder $queryBuilder,
        Content $content,
        string $arg,
        mixed $value,
        ?Property $property = null
    ): void {
        $split = \explode('__', $arg);
        // @todo instead of count use property name
        if (\count($split) !== 2) {
            // potentially raw query
            self::buildRawWhereQuery($queryBuilder, $arg, $value);
            return;
        }

        $modifiers = ['gt', 'gte', 'in', 'isnull', 'lt', 'lte', 'notin'];
        if (! \in_array(\strtolower($split[1]), $modifiers, true)) {
            if ($content->hasProperty($split[0])) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq("$split[0].$split[1]", $queryBuilder->createNamedParameter((string) $value))
                );
                return;
            }
        }

        $name = $split[0];
        if (! $content->hasProperty($name)) {
            if (null === $property) {
                return;
            }

            $tableName = $property->hasRelation()
                ? $property->getRelation()->getAlias()
                : $property->getName();
        } else {
            $property = $content->getProperty($name);
            $tableName = $property->hasRelation()
                ? $property->getName()
                : $content->getTable();
        }

        $column = "$tableName.$name";
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

    /**
     *
     * @param QueryBuilder $queryBuilder
     * @param string $expression
     * @param mixed $arg
     */
    private static function buildRawWhereQuery(QueryBuilder $queryBuilder, string $expression, mixed $arg): void
    {
        if (! \is_array($arg)) {
            return;
        }

        $queryBuilder->andWhere($expression);
        foreach ($arg as $key => $value) {
            if ($value instanceof Content) {
                $queryBuilder->setParameter($key, $value->getAutoId());
                continue;
            }

            if (\is_scalar($value)) {
                $queryBuilder->setParameter($key, $value);
            }

            // @todo throw exception if value is not an instance of Content, and not a scalar
            // possibly check $content for presence of $key property first
        }
    }

    /**
     *
     * @param QueryBuilder $queryBuilder
     * @param Property $property
     * @param string $name
     * @param mixed $value
     */
    private static function buildScalarValue(QueryBuilder $queryBuilder, Property $property, string $name, mixed $value): void
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
