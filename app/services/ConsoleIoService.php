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

    public function outputInfoMessage(string $msg): void
    {
        $msg = date('Y-m-d H:i:s') . ' [INFO] ' . $msg;
        $this->output->writeln($msg);
        $this->writeLog($msg);
    }

    public function outputErrorMessage(string $msg): void
    {
        $msg = date('Y-m-d H:i:s') . ' [ERROR] ' . $msg;
        $this->output->writeln("<error>{$msg}</error>");
        $this->writeLog($msg);
    }

    public function writeLog(string $msg): void
    {
        $path = strtr($this->config->get('services.console_io.logs_path'), ['{cmd}' => $this->commandName]);
        file_put_contents($path, $msg . PHP_EOL, FILE_APPEND);
    }
}