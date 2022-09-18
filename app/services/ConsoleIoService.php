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

    /** @var Command */
    private $command;

    /** @var InputInterface */
    private $input;

    /** @var QuestionHelper */
    private $questionHelper;

    /** @var OutputInterface */
    private $output;

    /** @var string */
    private $prevMsg;

    /**
     * @param ConfigService $config
     */
    public function __construct(ConfigService $config)
    {
        $this->config = $config;
    }

    /**
     * @param Command $command
     * @return $this
     */
    public function setCommand(Command $command): self
    {
        $this->command = $command;
        $this->questionHelper = $command->getHelper('question');
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
     * @param bool $isUpdatable
     * @param bool $withPerformance
     * @return void
     */
    public function outputInfoMessage(string $msg, bool $isUpdatable = false, bool $withPerformance = false): void
    {
        $msg = $this->formatMessage($msg, 'INF', $withPerformance);

        if($isUpdatable) {
            $placeholderRepeatTimes = mb_strlen($this->prevMsg) - mb_strlen($msg);
            $this->output->write("\r$msg" . ($placeholderRepeatTimes > 0 ? str_repeat(' ', $placeholderRepeatTimes) : ''));
        } else {
            $this->output->writeln($msg);
        }

        $this->writeLog($msg);

        $this->prevMsg = $msg;
    }

    /**
     * @param string $msg
     * @return void
     */
    public function outputErrorMessage(string $msg): void
    {
        $msg = $this->formatMessage($msg, 'ERR');
        $this->output->writeln("<error>$msg</error>");
        $this->writeLog($msg);
    }

    /**
     * @param string $question
     * @return bool
     */
    public function outputQuestion(string $question): bool
    {
        $question = $this->formatMessage($question, 'QST');
        $isAccept = $this->questionHelper->ask($this->input, $this->output, new ConfirmationQuestion($question));
        $this->writeLog($question . ($isAccept ? 'y' : 'n'));

        return $isAccept;
    }

    /**
     * @return void
     */
    public function outputEol(): void
    {
        $this->output->write(PHP_EOL);
    }

    /**
     * @param string $msg
     * @param string $type
     * @param bool $withPerformance
     * @return string
     */
    private function formatMessage(string $msg, string $type, bool $withPerformance = false): string
    {
        $msg = date('Y-m-d H:i:s') . ' [' . $type . '] ' . $msg;
        if($withPerformance) {
            $msg .= strtr(' | CPU {cpu}% | Memory usage {memUsage} | Memory peak {memPeak}', [
                '{cpu}' => sys_getloadavg()[0],
                '{memUsage}' => HelpersService::formatMemoryBytes(memory_get_usage()),
                '{memPeak}' => HelpersService::formatMemoryBytes(memory_get_peak_usage()),
            ]);
        }

        return $msg;
    }

    /**
     * @param string $msg
     * @return void
     */
    private function writeLog(string $msg): void
    {
        $path = strtr($this->config->get('services.console_io.logs_path'), ['{cmd}' => $this->command->getName()]);
        file_put_contents($path, $msg . PHP_EOL, FILE_APPEND);
    }
}