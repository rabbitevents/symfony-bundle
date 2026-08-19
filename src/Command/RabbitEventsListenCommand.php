<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Command;

use RabbitEvents\Bundle\Context;
use RabbitEvents\Bundle\Listener\Registry as ListenerRegistry;
use RabbitEvents\Bundle\Listener\ListenerOptions;
use RabbitEvents\Bundle\Listener\Handler\Processor;
use RabbitEvents\Bundle\Listener\QueueName;
use RabbitEvents\Bundle\Listener\Worker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Command\SignalableCommandInterface;

#[AsCommand(
    name: 'rabbitevents:listen',
    description: 'Listen for RabbitEvents messages'
)]
class RabbitEventsListenCommand extends Command implements SignalableCommandInterface
{
    public function __construct(
        private Context $context,
        private Worker $worker,
        private Processor $processor
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('events', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Events to listen for')
            ->addOption('service', 's', InputOption::VALUE_REQUIRED, 'Service name for queue naming', '')
            ->addOption('memory', null, InputOption::VALUE_REQUIRED, 'Memory limit in MB', 128)
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Timeout in seconds', 60)
            ->addOption('tries', null, InputOption::VALUE_REQUIRED, 'Max number of retries', 0)
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Sleep time between retries (seconds)', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
        }

        $events = $input->getArgument('events');

        $options = new ListenerOptions(
            service: $input->getOption('service'),
            events: $events,
            memory: (int) $input->getOption('memory'),
            timeout: (int) $input->getOption('timeout'),
            maxTries: (int) $input->getOption('tries'),
            sleep: (int) $input->getOption('sleep'),
        );

        $output->writeln(sprintf(
            '<info>Listening for events:</info> %s',
            implode(', ', $events)
        ));

        $topic = $this->context->createTopic();
        $queueName = QueueName::resolve($options->service ?: 'app', $events);
        $queue = $this->context->createQueue($queueName, $events, $topic);
        $consumer = $this->context->createConsumer($queue);

        $status = $this->worker->work($this->processor, $consumer, $options);

        return $status->value;
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->worker->shouldQuit = true;

        return false;
    }
}
