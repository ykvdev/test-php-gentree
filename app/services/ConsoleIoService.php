<?php

namespace app\services;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ConsoleIoService
{
    /** @var ConfigService */
    private $config;

    /** @var string */
    private $commandName;

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    public function __construct(ConfigService $config)
    {
        $this->config = $config;
    }

    public function setCommandName(string $commandName): self
    {
        $this->commandName = $commandName;
        return $this;
    }

    public function setInput(InputInterface $input): self
    {
        $this->input = $input;
        return $this;
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;
        return $this;
    }

    public function outputInfoMessage(string $msg, ?array $params = null): void
    {
        $msg = $this->formatMessage($msg);
        $this->outputMessage($msg, $params, false);
    }

    public function outputErrorMessage(string $msg, ?array $params = null): void
    {
        $msg = $this->formatMessage($msg, true);
        $this->outputMessage($msg, $params, true);
    }

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

    private function outputMessage(string $msg, ?array $params = null, ?bool $isError = null): void
    {
        $msg = $params ? strtr($msg, $params) : $msg;
        $this->output->writeln(is_null($isError) ? $msg : ($isError ? "<error>$msg</error>" : $msg));
        $this->writeLog($msg);
    }

    private function writeLog(string $msg): void
    {
        $path = strtr($this->config->get('services.console_io.logs_path'), ['{cmd}' => $this->commandName]);
        file_put_contents($path, $msg . PHP_EOL, FILE_APPEND);
    }
}