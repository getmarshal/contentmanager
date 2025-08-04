<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Migration;

use Doctrine\DBAL\Schema\SchemaDiff;
use Marshal\ContentManager\Event\CreateContentEvent;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Marshal\Util\Database\DatabaseAwareInterface;
use Marshal\Util\Database\DatabaseAwareTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrationGenerateCommand extends Command implements DatabaseAwareInterface, EventDispatcherAwareInterface
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
        $this->addOption('database', 'd', InputOption::VALUE_REQUIRED, 'The database to generate migrations for');
        $this->setDescription(
            "Generate and save statements to migrate a database to conform to it's schema specification"
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $input->validate();
        $database = $input->getOption('database');
        $io = new SymfonyStyle($input, $output);

        // get migration
        try {
            $diff = $this->generateMigration($database);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($diff->isEmpty()) {
            $io->info("No schema changes to migrate");
            return Command::SUCCESS;
        }

        // print statements
        $connection = $this->getDatabaseConnection($database);
        $statements = $connection->getDatabasePlatform()->getAlterSchemaSQL($diff);
        $io->info("This migration will generate the following statements:");
        $io->info($statements);
        $save = $io->ask("Save this migration? y/n");
        if ('y' !== $save) {
            $io->info("Migration aborted");
            return Command::SUCCESS;
        }

        $name = $io->ask("Enter a name for this migration");
        if (empty($name)) {
            $io->error("Migration name cannot be empty");
            return Command::FAILURE;
        }

        // normalize the name
        $normalizedName = $this->normalizeMigrationName($name);

        // save migration
        $event = new CreateContentEvent('marshal::migration', []);
        $this->getEventDispatcher()->dispatch($event);
        if (! $event->isSuccess()) {
            $io->error("Could not save migration");
            return Command::FAILURE;
        }
        // $result = $this->getDatabaseConnection()->executeStatement(
        //     "INSERT INTO migration (name, db, diff, createdat)
        //     VALUES (:name, :db, :diff, :createdat)",
        //     [
        //         'name' => $normalizedName,
        //         'db' => $database,
        //         'diff' => \serialize($migration),
        //         'createdat' => (new \DateTime())->format('c'),
        //     ]
        // );

        // if (! \is_numeric($result) || \intval($result) !== 1) {
        //     $io->error("Could not save migration");
        //     return Command::FAILURE;
        // }

        $io->success("Migration $normalizedName generated");
        return Command::SUCCESS;
    }

    private function generateMigration(string $database): SchemaDiff
    {
        $schemaManager = $this->getDatabaseConnection($database)->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();

        $definitions = [];
        $toSchema = $this->buildContentSchema($definitions);

        return $schemaManager->createComparator()->compareSchemas($fromSchema, $toSchema);
    }

    private function normalizeMigrationName(string $name): string
    {
        $replaced = \str_replace(' ', '_', $name);
        $timestamp = (new \DateTime())->format('Y-m-d-H-i-s');
        return "$timestamp-$replaced";
    }
}
