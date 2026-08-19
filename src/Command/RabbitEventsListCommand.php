<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Command;

use RabbitEvents\Bundle\Listener\Registry as ListenerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'rabbitevents:list',
    description: 'List all registered RabbitEvents listeners'
)]
class RabbitEventsListCommand extends Command
{
    public function __construct(private ListenerRegistry $listenerRegistry)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $events = $this->listenerRegistry->getEvents();

        if (empty($events)) {
            $output->writeln('<info>No RabbitEvents listeners registered.</info>');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Event', 'Listeners']);

        foreach ($events as $event) {
            $listeners = $this->listenerRegistry->getListeners($event);
            $listenerNames = array_map(function ($listener) {
                if (is_string($listener)) {
                    return $listener;
                }
                if (is_array($listener) && isset($listener[0])) {
                    return is_string($listener[0]) ? $listener[0] : get_class($listener[0]);
                }
                if ($listener instanceof \Closure) {
                    return 'Closure';
                }
                if (is_object($listener)) {
                    return get_class($listener);
                }
                return 'Unknown';
            }, $listeners);

            $table->addRow([$event, implode("\n", $listenerNames)]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
