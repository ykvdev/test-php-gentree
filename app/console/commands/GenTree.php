<?php

namespace app\console\commands;

use app\console\commands\GenTree\AbstractFileAdapter;
use app\services\ConsoleIoService;
use Generator;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class GenTree extends Command
{
    public const COMMAND_NAME = 'gentree',
        COMMAND_DESC = 'Tree Generator',
        OPTION_INPUT_FILE_PATH = 'input',
        OPTION_OUTPUT_FILE_PATH = 'output';

    /** @var ConsoleIoService */
    private $io;

    /** @var string */
    private $inputFilePath;

    /** @var string */
    private $outputFilePath;

    /**
     * @param ConsoleIoService $io
     */
    public function __construct(ConsoleIoService $io)
    {
        parent::__construct();
        $this->io = $io;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESC)
            ->addOption(self::OPTION_INPUT_FILE_PATH, 'i', InputOption::VALUE_REQUIRED, 'Source CSV file')
            ->addOption(self::OPTION_OUTPUT_FILE_PATH, 'o', InputOption::VALUE_REQUIRED, 'Destination JSON file');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io->setCommand($this)->setInput($input)->setOutput($output);
        $this->inputFilePath = $input->getOption(self::OPTION_INPUT_FILE_PATH);
        $this->outputFilePath = $input->getOption(self::OPTION_OUTPUT_FILE_PATH);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->io->outputInfoMessage(implode(' / ', [
                strtr('{cmd} started', ['{cmd}' => self::COMMAND_DESC]),
                strtr('Group {gid}:{group}', ['{gid}' => getmygid(), '{group}' => posix_getgrgid(posix_getgid())['name']]),
                strtr('User {uid}:{user}', ['{uid}' => getmyuid(), '{user}' => get_current_user()]),
                strtr('Input file {inputFilePath}', ['{inputFilePath}' => $this->inputFilePath ?? '(none)']),
                strtr('Output file {outputFilePath}', ['{outputFilePath}' => $this->outputFilePath ?? '(none)']),
            ]));

            if(!$this->inputFilePath || !$this->outputFilePath) {
                throw new LogicException('You must specify -i and -o params with file paths');
            }

            if(is_file($this->outputFilePath) && !$this->io->outputQuestion('Output file already exists, rewrite this (y/n)? ')) {
                throw new LogicException('Output file already exists, specify other file name');
            }

            $inputFile = AbstractFileAdapter::factory($this->inputFilePath,AbstractFileAdapter::MODE_READ);
            $outputFile = AbstractFileAdapter::factory($this->outputFilePath,AbstractFileAdapter::MODE_WRITE);

            $this->io->outputInfoMessage('Reading input file data and making tree');
            $tree = $this->makeTreeByCsvFile($inputFile);

            $this->io->outputInfoMessage('Writing tree into output file');
            $outputFile->writeFile($tree);

            $this->io->outputInfoMessage('The End');
            return 0;
        } catch (LogicException $e) {
            $this->io->outputErrorMessage($e->getMessage());
            return 2;
        } catch (Throwable $e) {
            $this->io->outputErrorMessage(
                '(' . get_class($e) . ') ' . $e->getMessage() . PHP_EOL
                . $e->getFile() . ':' . $e->getLine() . PHP_EOL
                . $e->getTraceAsString()
            );
            return 1;
        }
    }

    /**
     * @param AbstractFileAdapter $csvFileAdapter
     * @param string|null $parent
     * @param string|null $relation
     * @return Generator
     */
    private function makeTreeByCsvFile(AbstractFileAdapter $csvFileAdapter, ?string $parent = null, ?string $relation = null): Generator
    {
        foreach ($csvFileAdapter->readFile() as $row) {
            if($row['parent'] == $parent || ($relation && $row['parent'] == $relation)) {
                yield from [
                    'itemName' => $row['itemName'],
                    'parent' => $row['parent'],
                    'children' => $this->makeTreeByCsvFile($csvFileAdapter, $row['itemName'], $row['relation']),
                ];
            }
        }
    }
}