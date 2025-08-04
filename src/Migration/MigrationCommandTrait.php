<?php

declare(strict_types= 1);

namespace Marshal\ContentManager\Migration;

use Doctrine\DBAL\Schema\Schema;
use Marshal\ContentManager\Schema\Content;

trait MigrationCommandTrait
{
    private function buildContentSchema(array $definition): Schema
    {
        $schema = new Schema();
        foreach ($definition as $content) {
            if (! $content instanceof Content) {
                continue;
            }

            $table = $schema->createTable($content->getTable());
            foreach ($content->getProperties() as $property) {
                // prepare column options
                $columnOptions = [
                    'notnull' => $property->getNotNull(),
                    'default' => $property->getDefaultValue(),
                    'autoincrement' => $property->isAutoIncrement(),
                    'length' => $property->getLength(),
                    'fixed' => $property->getFixed(),
                    'precision' => $property->getPrecision(),
                    'scale' => $property->getScale(),
                    'platformOptions' => $property->getPlatformOptions(),
                    'unsigned' => $property->getUnsigned(),
                ];

                if ($property->hasComment()) {
                    $columnOptions['comment'] = $property->getComment();
                }

                // add column to table
                $table->addColumn(
                    name: $property->getIdentifier(),
                    typeName: $property->getDatabaseTypeName(),
                    options: $columnOptions
                );

                // autoincrementing properties are primary keys
                if ($property->isAutoIncrement()) {
                    $table->setPrimaryKey([$property->getIdentifier()]);
                }

                // configure column index
                if ($property->hasIndex()) {
                    $table->addIndex(
                        columnNames: [$property->getIdentifier()],
                        indexName: $property->getIndex()->getName() ?? \strtolower("idx_{$content->getTable()}_{$property->getIdentifier()}"),
                        flags: $property->getIndex()->getFlags(),
                        options: $property->getIndex()->getOptions()
                    );
                }

                if ($property->hasUniqueConstraint()) {
                    $constraint = $property->getUniqueConstraint();
                    $table->addUniqueIndex(
                        columnNames: [$property->getIdentifier()],
                        indexName: $constraint->getName() ?? \strtolower("uniq_{$content->getTable()}_{$property->getIdentifier()}"),
                        options: $constraint->getOptions(),
                    );
                }

                // configure column foreign key
                if ($property->hasRelation()) {
                    $relation = $property->getRelation();
                    $table->addForeignKeyConstraint(
                        foreignTableName: $relation->getSchema()->getTable(),
                        localColumnNames: [$property->getIdentifier()],
                        foreignColumnNames: [$relation->getProperty()->getIdentifier()],
                        options: [
                            'onUpdate' => $relation->getOnUpdate(),
                            'onDelete' => $relation->getOnDelete(),
                        ],
                        name: \strtolower("fk_{$content->getTable()}_{$property->getIdentifier()}")
                    );
                }
            }
        }

        return $schema;
    }
}
