<?php

namespace app\console\commands;

use app\services\ConfigService;
use app\services\ConsoleIoService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenTree extends Command
{
    /** @var ConfigService */
    private $config;

    /** @var ConsoleIoService */
    private $io;

    public function __construct(ConsoleIoService $io, ConfigService $config)
    {
        parent::__construct();

        $this->io = $io;
        $this->config = $config;
    }

    protected function configure(): void
    {
        $this->setName('gentree')->setDescription('Generate tree by CSV file');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io->setCommandName($this->getName())->setInput($input)->setOutput($output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            //$availableSymbols = $this->config->get('services.rates_api.available_symbols');

            /*$this->io->info('Start');

            $rates = $this->getRates();
            $this->io->info('Received rates: ' . var_export($rates, true));

            $this->saveRates($rates);
            $this->io->info('Rates saved');

            $this->io->info('End');*/
        } catch (\Throwable $e) {
            $this->io->outputErrorMessage(PHP_EOL . '(' . get_class($e) . ') '
                . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }

        return 0;
    }
}