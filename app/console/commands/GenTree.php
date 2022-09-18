<?php

namespace app\console\commands;

use app\console\commands\GenTree\AbstractFileAdapter;
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

class GenTree extends Command
{
    public const COMMAND_NAME = 'gentree',
        COMMAND_DESC = 'Tree Generator',
        OPTION_INPUT_FILE_PATH = 'input',
        OPTION_OUTPUT_FILE_PATH = 'output';

    /** @var ConsoleIoService */
    private $io;

    /** @var AbstractFileAdapter */
    private $inputFile;

    /** @var AbstractFileAdapter */
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
            ->addOption(self::OPTION_INPUT_FILE_PATH, 'i', InputOption::VALUE_REQUIRED, 'Source file')
            ->addOption(self::OPTION_OUTPUT_FILE_PATH, 'o', InputOption::VALUE_REQUIRED, 'Destination file');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io->setCommand($this)->setInput($input)->setOutput($output);
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
            $inputFilePath = $input->getOption(self::OPTION_INPUT_FILE_PATH);
            $outputFilePath = $input->getOption(self::OPTION_OUTPUT_FILE_PATH);

            $this->io->outputInfoMessage(implode(' | ', [
                strtr('{cmd} started', ['{cmd}' => self::COMMAND_DESC]),
                strtr('Group {gid}:{group}', ['{gid}' => getmygid(), '{group}' => posix_getgrgid(posix_getgid())['name']]),
                strtr('User {uid}:{user}', ['{uid}' => getmyuid(), '{user}' => get_current_user()]),
                strtr('Input file {inputFilePath}', ['{inputFilePath}' => $inputFilePath ?? '(none)']),
                strtr('Output file {outputFilePath}', ['{outputFilePath}' => $outputFilePath ?? '(none)']),
            ]));

            if(!$inputFilePath || !$outputFilePath) {
                throw new LogicException('You must specify -i and -o params with file paths');
            }

            if(is_file($outputFilePath) && !$this->io->outputQuestion('Output file already exists, rewrite this (y/n)? ')) {
                throw new LogicException('Output file already exists, specify other file name');
            }

            $this->io->outputInfoMessage('Opening files');
            $this->inputFile = AbstractFileAdapter::factory($inputFilePath, AbstractFileAdapter::MODE_READ);
            $this->outputFile = AbstractFileAdapter::factory($outputFilePath, AbstractFileAdapter::MODE_WRITE);

            $this->inputFile->setProgressRwCallback($this->getFileRwProgressCallback());
            $tree = $this->makeTreeByInputFile();

            $this->outputFile->setProgressRwCallback($this->getFileRwProgressCallback());
            $this->outputFile->writeFile($tree);
            $this->io->outputEol();

            $this->io->outputInfoMessage('Finished');
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
     * @param string|null $parent
     * @param string|null $relation
     * @return Generator
     */
    private function makeTreeByInputFile(?string $parent = null, ?string $relation = null): Generator
    {
        $this->inputFile->setPosition(0, SEEK_SET);
        foreach ($this->inputFile->readFile() as $row) {
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
}