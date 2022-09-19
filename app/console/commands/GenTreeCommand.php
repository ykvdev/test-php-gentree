<?php

namespace app\console\commands;

use app\console\commands\GenTreeCommand\AbstractFileAdapter;
use app\services\ConsoleIoService;
use app\services\HelpersService;
use Closure;
use Exception;
use Generator;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class GenTreeCommand extends Command
{
    public const COMMAND_NAME = 'gentree',
        COMMAND_DESC = 'Tree Generator',
        OPTION_INPUT_FILE_PATH = 'input',
        OPTION_INPUT_FILE_LINES_LIMIT = 'input-lines',
        OPTION_OUTPUT_FILE_PATH = 'output',
        EXIT_CODE_SUCCESS = 0,
        EXIT_CODE_FAILURE = 1,
        EXIT_CODE_INVALID = 2;

    /** @var ConsoleIoService */
    private $io;

    /** @var AbstractFileAdapter|null */
    private $inputFile;

    /** @var int */
    private $inputFileLinesLimit;

    /** @var AbstractFileAdapter|null */
    private $outputFile;

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
            ->addOption(self::OPTION_INPUT_FILE_PATH, 'i',
                InputOption::VALUE_REQUIRED, 'Source file')
            ->addOption(self::OPTION_INPUT_FILE_LINES_LIMIT, 'l',
                InputOption::VALUE_REQUIRED, 'Source file lines limit', 20000)
            ->addOption(self::OPTION_OUTPUT_FILE_PATH, 'o',
                InputOption::VALUE_REQUIRED, 'Destination file');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io->setCommand($this)->setInput($input)->setOutput($output);

        if($inputFilePath = $input->getOption(self::OPTION_INPUT_FILE_PATH)) {
            $this->inputFile = AbstractFileAdapter::factory($inputFilePath, AbstractFileAdapter::MODE_READ);
        }

        $this->inputFileLinesLimit = $input->getOption(self::OPTION_INPUT_FILE_LINES_LIMIT);

        if($outputFilePath = $input->getOption(self::OPTION_OUTPUT_FILE_PATH)) {
            $this->outputFile = AbstractFileAdapter::factory($outputFilePath, AbstractFileAdapter::MODE_WRITE);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->io->outputInfoMessage(implode(' | ', [
                strtr('{cmd} started', ['{cmd}' => self::COMMAND_DESC]),
                strtr('Group {gid}:{group}', ['{gid}' => getmygid(), '{group}' => posix_getgrgid(posix_getgid())['name']]),
                strtr('User {uid}:{user}', ['{uid}' => getmyuid(), '{user}' => get_current_user()]),
                strtr('Input file {inputFilePath}', ['{inputFilePath}' => $this->inputFile ? $this->inputFile->getPath() : '(none)']),
                strtr('Output file {outputFilePath}', ['{outputFilePath}' => $this->outputFile ? $this->outputFile->getPath() : '(none)']),
            ]));

            if(!$this->inputFile || !$this->outputFile) {
                throw new LogicException('You must specify -i and -o params with file paths');
            }

            if(is_file($this->outputFile->getPath()) && !$this->io->outputQuestion('Output file already exists, rewrite this (y/n)? ')) {
                throw new LogicException('Output file already exists, specify other file name');
            }

            $this->io->outputInfoMessage('Opening files');
            $this->inputFile->open();
            $this->outputFile->open();

            $this->inputFile->setProgressRwCallback($this->getFileRwProgressCallback());
            $tree = $this->makeTreeByInputFile();

            $this->outputFile->setProgressRwCallback($this->getFileRwProgressCallback());
            $this->outputFile->writeFile($tree);
            $this->io->outputEol();

            if($this->inputFile->isTooManyLines()) {
                $this->io->outputWarningMessage("Input file has too many lines, read only first $this->inputFileLinesLimit lines");
            }

            $this->io->outputInfoMessage('Finished');
            return self::EXIT_CODE_SUCCESS;
        } catch (LogicException $e) {
            $this->io->outputErrorMessage($e->getMessage());
            return self::EXIT_CODE_INVALID;
        } catch (Throwable $e) {
            $this->io->outputErrorMessage(
                '(' . get_class($e) . ') ' . $e->getMessage() . PHP_EOL
                . $e->getFile() . ':' . $e->getLine() . PHP_EOL
                . $e->getTraceAsString()
            );
            return self::EXIT_CODE_FAILURE;
        }
    }

    /**
     * @return Closure
     */
    private function getFileRwProgressCallback(): Closure
    {
        return function() {
            $this->io->outputInfoMessage(strtr('Read {readSize} of {fileSize} from input file and write tree {writeSize} to output file', [
                '{readSize}' => HelpersService::formatMemoryBytes($this->inputFile->getRwSize()),
                '{fileSize}' => HelpersService::formatMemoryBytes($this->inputFile->getSize()),
                '{writeSize}' => HelpersService::formatMemoryBytes($this->outputFile->getRwSize()),
            ]), true, true);
        };
    }

    /**
     * @param string|null $parent
     * @param string|null $relation
     * @return Generator
     */
    private function makeTreeByInputFile(?string $parent = null, ?string $relation = null): Generator
    {
        $this->inputFile->setPosition(0, SEEK_SET);
        foreach ($this->inputFile->readFile($this->inputFileLinesLimit) as $row) {
            if($row['parent'] == $parent || ($relation && $row['parent'] == $relation)) {
                $position = $this->inputFile->getPosition();
                yield [
                    'itemName' => $row['itemName'],
                    'parent' => $row['parent'],
                    'children' => $this->makeTreeByInputFile($row['itemName'], $row['relation']),
                ];
                $this->inputFile->setPosition($position, SEEK_SET);
            }
        }
    }
}