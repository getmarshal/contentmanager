<?php

declare(strict_types= 1);

namespace Marshal\ContentManager\Migration;

use Doctrine\DBAL\Schema\SchemaDiff;
use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\ContentManager\Event\UpdateContentEvent;
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

final class MigrationRunCommand extends Command implements DatabaseAwareInterface, EventDispatcherAwareInterface
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
        $this->addOption(
            name: 'name',
            shortcut: null,
            mode: InputOption::VALUE_REQUIRED,
            description: 'The name of the migration i.e it\'s config key'
        );
        $this->setDescription('Execute one or more pending migrations');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // validate the input
        $input->validate();

        // get details
        $name = $input->getOption('name');

        // get the migration
        $event = new ReadContentEvent('marshal::migration', ['name' => $name]);
        $this->getEventDispatcher()->dispatch($event);
        if (! $event->hasContent()) {
            $io->error("Migration $name not found");
            return Command::FAILURE;
        }

        $migration = $event->getContent();
        $diff = \unserialize($migration->get('diff'));
        \assert($diff instanceof SchemaDiff);

        try {
            $dbConnection = $this->getDatabaseConnection($migration->get('db'));
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $migrationStatements = $dbConnection->getDatabasePlatform()->getAlterSchemaSQL($diff);
        $io->info($migrationStatements);
        $proceed = $io->ask("Proceed with this migration? y/n");
        if ($proceed !== 'y') {
            $io->info("Migration aborted");
            return Command::SUCCESS;
        }

        // update migration table
        $event = new UpdateContentEvent($migration, [
            'status' => 1,
            'updatedat' => (new \DateTime())->format('c'),
        ]);
        $this->getEventDispatcher()->dispatch($event);
        if (! $event->isSuccess()) {
            $io->error("An error occurred. Migration aborted");
            return Command::FAILURE;
        }

        // run the migration
        $failedStatements = [];
        foreach ($migrationStatements as $statement) {
            try {
                $dbConnection->executeStatement($statement);
            } catch (\Throwable $e) {
                $failedStatements[] = $statement;
                continue;
            }
        }

        if (! empty($failedStatements)) {
            $io->error("The following statements failed to execute");
            $io->error($failedStatements);
        }

        $io->success(\sprintf(
            "Migration %s on database %s successfully run",
            $name, $migration->get('db')
        ));

        return Command::SUCCESS;
    }
}
