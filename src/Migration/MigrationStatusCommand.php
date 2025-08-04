<?php

declare(strict_types= 1);

namespace Marshal\ContentManager\Migration;

use Marshal\ContentManager\Event\ReadCollectionEvent;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MigrationStatusCommand extends Command implements EventDispatcherAwareInterface
{
    use EventDispatcherAwareTrait;
    use MigrationCommandTrait;

    public function __construct(protected ContainerInterface $container, string $name)
    {
        parent::__construct($name);
    }

    public function configure(): void
    {
        $this->setDescription('View the status of database schema migrations');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // read migrations status
        $event = new ReadCollectionEvent('marshal::migration')
            ->orderBy('createdat', 'DESC')
            ->toArray();
        $this->getEventDispatcher()->dispatch($event);

        $result = [];
        foreach ($event->getCollection() as $row) {
            $row['status'] = $row['status'] == 1
                ? 'Done'
                : 'Pending';

            $result[] = [
                'migration' => $row['name'],
                'database' => $row['db'],
                'status' => $row['status'],
                'created' => $row['createdat']->format('c'),
                'executed' => $row['updatedat'],
            ];
        }

        // display status table
        $io->table(['Migration', 'Database', 'Status', 'Created', 'Executed'], $result);

        return Command::SUCCESS;
    }
}
