<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Command;

use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FetchContentCommand extends Command implements EventDispatcherAwareInterface
{
    use EventDispatcherAwareTrait;

    public const string NAME = 'content:fetch';

    public function configure(): void
    {
        $this->addOption('database', 'd', InputOption::VALUE_REQUIRED, 'The database to query', 'main');
        $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The ID of the content');
        $this->setDescription("Fetch content");
        $this->setName(self::NAME);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $input->validate();

        $io = new SymfonyStyle($input, $output);

        $event = new ReadContentEvent('football::fixture', [
            'tag' => 'R2MNL46F'
        ]);
        $this->getEventDispatcher()->dispatch($event);

        if (! $event->hasResult()) {
            $io->error("Content not found");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
