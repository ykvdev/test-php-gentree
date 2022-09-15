<?php

namespace app\console\commands;

use app\services\ConsoleIoService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenTree extends Command
{
    public const COMMAND_NAME = 'gentree',
        OPTION_INPUT = 'input',
        OPTION_OUTPUT = 'output';

    /** @var ConsoleIoService */
    private $io;

    public function __construct(ConsoleIoService $io)
    {
        parent::__construct();

        $this->io = $io;
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Generate tree by CSV file')
            ->addOption(self::OPTION_INPUT, 'i', InputOption::VALUE_REQUIRED, 'Source CSV file')
            ->addOption(self::OPTION_OUTPUT, 'o', InputOption::VALUE_REQUIRED, 'Destination JSON file');
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
            $inputFilePath = $this->io->getInput()->getOption(self::OPTION_INPUT);

            $outputFilePath = $this->io->getInput()->getOption(self::OPTION_INPUT);
            if(!is_dir(dirname($outputFilePath))) {
                throw new \LogicException('Output file not found');
            }



            //$availableSymbols = $this->config->get('services.rates_api.available_symbols');

            /*$this->io->info('Start');

            $rates = $this->getRates();
            $this->io->info('Received rates: ' . var_export($rates, true));

            $this->saveRates($rates);
            $this->io->info('Rates saved');

            $this->io->info('End');*/

            // invalid options or missing arguments
            //return Command::INVALID
        } catch (\LogicException $e) {
            $this->io->outputErrorMessage($e->getMessage());
            return Command::INVALID;
        } catch (\Throwable $e) {
            $this->io->outputErrorMessage(PHP_EOL . '(' . get_class($e) . ') ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function readCsvFile(string $csvFilePath): \Generator
    {
        $lineNumber = 0;
        $file = $this->fopen($csvFilePath, 'r');
        while (($row = fgetcsv($file, null, ';')) !== false) {
            if($lineNumber++ != 0) {
                [$uniqueItemName, $type, $parent, $relation] = $row;
                yield [
                    'itemName' => trim($uniqueItemName),
                    'type' => trim($type),
                    'parent' => trim($parent) ?? null,
                    'relation' => trim($relation) ?? null,
                ];
            }
        }

        fclose($file);
    }

    private function makeTree(\Generator $generator, ?string $parent = null, ?string $relation = null): \Generator
    {
        foreach ($generator as $row) {
            if($row['parent'] == $parent || ($relation && $row['parent'] == $relation)) {
                yield [
                    'itemName' => $row['itemName'],
                    'parent' => $row['parent'],
                    'children' => $this->makeTree($generator, $row['itemName'], $row['relation']),
                ];
            }
        }
    }

    private function writeJsonFile($file, \Generator $tree, int $indent = 0): void
    {
        if(is_string($file)) {
            $dirPath = dirname($file);
            if(!is_dir($dirPath)) {
                throw new \LogicException("Directory $dirPath not found");
            }

            $fileNeedToClose = true;
            $file = $this->fopen($file, 'a');
        }

        foreach ($tree as $key => $data) {
            $coverSymbols = is_numeric($key) ? ['[', ']'] : ['{', '}'];
            $this->fwriteln($file, $coverSymbols[0], $indent);
            if(is_scalar($data)) {
                $this->fwriteln($file, json_encode($key, JSON_UNESCAPED_UNICODE)
                    . ': ' . json_encode($data, JSON_UNESCAPED_UNICODE) . ',', $indent);
            } else {
                $this->writeJsonFile($file, $data, $indent + 4);
            }
            $this->fwriteln($file, $coverSymbols[1], $indent);
        }

        if(isset($fileNeedToClose)) {
            fclose($file);
        }
    }

    private function fopen(string $path, string $mode)
    {
        if($mode == 'r' && !file_exists($path)) {
            throw new \LogicException("File $path not found");
        }

        if (($file = fopen($path, $mode)) === false) {
            throw new \LogicException("File $path open failed");
        }

        return $file;
    }

    /**
     * @param resource $handler
     * @param string|int|float $data
     * @param int $indent
     * @return void
     */
    private function fwriteln($handler, $data, int $indent = 0): void
    {
        if(fwrite($handler, str_repeat(' ', $indent) . $data . PHP_EOL) === false) {
            throw new \LogicException('Write file ' . stream_get_meta_data($handler)['uri'] . ' failed');
        }
    }
}