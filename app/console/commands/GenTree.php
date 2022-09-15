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
        OPTION_INPUT_FILE_PATH = 'input',
        OPTION_OUTPUT_FILE_PATH = 'output';

    /** @var ConsoleIoService */
    private $io;

    /** @var string */
    private $inputFilePath;

    /** @var string */
    private $outputFilePath;

    public function __construct(ConsoleIoService $io)
    {
        parent::__construct();
        $this->io = $io;
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Generate tree by CSV file')
            ->addOption(self::OPTION_INPUT_FILE_PATH, 'i', InputOption::VALUE_REQUIRED, 'Source CSV file')
            ->addOption(self::OPTION_OUTPUT_FILE_PATH, 'o', InputOption::VALUE_REQUIRED, 'Destination JSON file');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io->setCommandName($this->getName())->setInput($input)->setOutput($output);
        $this->inputFilePath = $input->getOption(self::OPTION_INPUT_FILE_PATH);
        $this->outputFilePath = $input->getOption(self::OPTION_INPUT_FILE_PATH);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->io->outputInfoMessage("Opening input file: \"$this->inputFilePath\"");
            $inputFile = AbstractFileAdapter::factory($this->inputFilePath,AbstractFileAdapter::MODE_READ);

            $this->io->outputInfoMessage("Opening output file: \"$this->outputFilePath\"");
            $outputFile = AbstractFileAdapter::factory($this->outputFilePath,AbstractFileAdapter::MODE_WRITE);

            $this->io->outputInfoMessage('Reading input file data');
            $inputFileData = $inputFile->readFile();

            $this->io->outputInfoMessage('Making tree by input data');
            $tree = $this->makeTree($inputFileData);

            $this->io->outputInfoMessage('Writing tree into output file');
            $outputFile->writeFile($tree);

            $this->io->outputInfoMessage('The End');
        } catch (LogicException $e) {
            $this->io->outputErrorMessage($e->getMessage());
            return Command::INVALID;
        } catch (Throwable $e) {
            $this->io->outputErrorMessage(PHP_EOL . '(' . get_class($e) . ') ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param Generator $generator
     * @param string|null $parent
     * @param string|null $relation
     * @return Generator
     */
    private function makeTree(Generator $generator, ?string $parent = null, ?string $relation = null): Generator
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
}