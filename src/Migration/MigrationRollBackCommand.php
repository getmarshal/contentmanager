<?php

declare(strict_types= 1);

namespace Marshal\ContentManager\Migration;

use Doctrine\DBAL\Schema\SchemaDiff;
use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MigrationRollBackCommand extends Command implements EventDispatcherAwareInterface
{
    use EventDispatcherAwareTrait;
    use MigrationCommandTrait;

    public function __construct(protected ContainerInterface $container, string $name)
    {
        parent::__construct($name);
    }

    public function configure(): void
    {
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the migration to rollback');
        $this->setDescription('Reverse one or more database migrations');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // validate the input
        $input->validate();

        // get details
        $name = $input->getOption('name');
        $event = new ReadContentEvent('marshal::migration', ['name' => $name]);
        $this->getEventDispatcher()->dispatch($event);
        if (! $event->hasContent()) {
            $io->error("Migration $name not found");
            return Command::FAILURE;
        }

        $migration = $event->getContent();

        $diff = $migration->get('diff');
        $database = $migration->get('db');
        \assert($diff instanceof SchemaDiff);

        // created tables
        foreach ($diff->getCreatedTables() as $table) {
            // @todo delete the table
        }

        foreach ($diff->getAlteredTables() as $tableDiff) {}

        foreach ($diff->getDroppedTables() as $table) {
            // @todo recreate the table
        }

        $io->success(\sprintf(
            "Migration %s successfully rolled back",
            $name
        ));

        return Command::SUCCESS;
    }
}
