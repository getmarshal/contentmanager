<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Migration;

use Marshal\ContentManager\Schema\Content;
use Marshal\ContentManager\Schema\Property;
use Marshal\ContentManager\Schema\PropertyConfig;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Marshal\Util\Database\DatabaseAwareInterface;
use Marshal\Util\Database\DatabaseAwareTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MigrationSetupCommand extends Command implements DatabaseAwareInterface, EventDispatcherAwareInterface
{
    use DatabaseAwareTrait;
    use EventDispatcherAwareTrait;
    use MigrationCommandTrait;

    public function __construct(protected ContainerInterface $container, string $name)
    {
        parent::__construct($name);
    }

    public function configure(): void
    {
        $this->setDescription("Setup migrations. Installs the migration table onto the main database");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info("Setting up migrations");

        $connection = $this->getDatabaseConnection();
        if ($connection->createSchemaManager()->tableExists('migration')) {
            $io->info("Migrations already setup");
            return Command::SUCCESS;
        }

        // create the migrations table
        $properties = [];
        foreach ($this->getMigrationSchema() as $column => $columnDefinition) {
            $properties[$column] = new Property($column, new PropertyConfig($columnDefinition));
        }
        $type = new Content(
            database: 'main',
            table: 'migration',
            config: [],
            properties: $properties
        );
        $schema = $this->buildContentSchema([$type]);
        foreach ($schema->toSql($connection->getDatabasePlatform()) as $createStmt) {
            $connection->executeStatement($createStmt);
        }

        $io->success("Migration table setup");

        return Command::SUCCESS;
    }

    private function getMigrationSchema(): array
    {
        return [
            'id' => [
                'type' => 'bigint',
                'notnull' => true,
                'autoincrement' => true,
            ],
            'name' => [
                'type' => 'string',
                'notnull' => true,
                'index' => true,
                'length' => 255,
            ],
            'db' => [
                'type' => 'string',
                'notnull' => true,
                'index' => true,
                'length' => 255,
            ],
            'diff' => [
                'type' => 'blob',
                'notnull' => true,
            ],
            'status' => [
                'type' => 'smallint',
                'notnull' => true,
                'default' => 0,
                'index' => true,
            ],
            'createdat' => [
                'type' => 'datetime',
                'notnull' => true,
            ],
            'updatedat' => [
                'type' => 'datetime',
            ],
        ];
    }
}
