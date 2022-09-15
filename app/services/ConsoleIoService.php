<?php

namespace app\services;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ConsoleIoService
{
    /** @var ConfigService */
    private $config;

    /** @var string */
    private $commandName;

    /** @var Command */
    private $command;

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /**
     * @param ConfigService $config
     */
    public function __construct(ConfigService $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $commandName
     * @return $this
     */
    public function setCommandName(string $commandName): self
    {
        $this->commandName = $commandName;
        return $this;
    }

    /**
     * @param Command $command
     * @return $this
     */
    public function setCommand(Command $command): self
    {
        $this->command = $command;
        return $this;
    }

    /**
     * @param InputInterface $input
     * @return $this
     */
    public function setInput(InputInterface $input): self
    {
        $this->input = $input;
        return $this;
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @param string $msg
     * @param array|null $params
     * @return void
     */
    public function outputInfoMessage(string $msg, ?array $params = null): void
    {
        $msg = $this->formatMessage($msg);
        $this->outputMessage($msg, $params, false);
    }

    /**
     * @param string $msg
     * @param array|null $params
     * @return void
     */
    public function outputErrorMessage(string $msg, ?array $params = null): void
    {
        $msg = $this->formatMessage($msg, true);
        $this->outputMessage($msg, $params, true);
    }

    /**
     * @param string $question
     * @return bool
     */
    public function outputQuestion(string $question): bool
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->command->getHelper('question');
        return $questionHelper->ask($this->input, $this->output, new ConfirmationQuestion($question));
    }

    /**
     * @param string $msg
     * @param bool $isError
     * @return string
     */
    private function formatMessage(string $msg, bool $isError = false): string
    {
        return strtr('{datetime} [{type}] {msg} / CPU {cpu}% / {memUsage} Mb of {memAvailable} Mb / Peak {memPeak} Mb', [
            '{datetime}' => date('Y-m-d H:i:s'),
            '{type}' => $isError ? 'ERR' : 'INF',
            '{msg}' => $msg,
            '{cpu}' => sys_getloadavg()[0],
            '{memUsage}' => HelpersService::formatMemoryBytes(memory_get_usage()),
            '{memAvailable}' => HelpersService::formatMemoryBytes(ini_get('memory_limit')),
            '{memPeak}' => HelpersService::formatMemoryBytes(memory_get_peak_usage()),
        ]);
    }

    /**
     * @param string $msg
     * @param array|null $params
     * @param bool|null $isError
     * @return void
     */
    private function outputMessage(string $msg, ?array $params = null, ?bool $isError = null): void
    {
        $msg = $params ? strtr($msg, $params) : $msg;
        $this->output->writeln(is_null($isError) ? $msg : ($isError ? "<error>$msg</error>" : $msg));
        $this->writeLog($msg);
    }

    /**
     * @param string $msg
     * @return void
     */
    private function writeLog(string $msg): void
    {
        $path = strtr($this->config->get('services.console_io.logs_path'), ['{cmd}' => $this->commandName]);
        file_put_contents($path, $msg . PHP_EOL, FILE_APPEND);
    }
}