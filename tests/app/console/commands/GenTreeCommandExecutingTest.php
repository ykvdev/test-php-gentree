<?php

namespace tests\app\console\commands;

use app\console\commands\GenTreeCommand;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Tester\CommandTester;

final class GenTreeCommandExecutingTest extends TestCase
{
    private const APP_DATA_PATH = __DIR__ . '/../../../../data';

    /** @var CommandTester */
    private $commandTester;

    /**
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function setUp(): void
    {
        $cmd = (new Container)->get(GenTreeCommand::class);
        $cmd->setApplication(new Application);
        $this->commandTester = new CommandTester($cmd);
    }

    /**
     * @return void
     */
    public function testWithoutParams(): void
    {
        $this->assertEquals(GenTreeCommand::EXIT_CODE_INVALID, $this->commandTester->execute([]));
        $this->assertStringContainsString('You must specify -i and -o params', $this->commandTester->getDisplay());

        $this->expectException(InvalidOptionException::class);
        $this->assertEquals(GenTreeCommand::EXIT_CODE_INVALID, $this->commandTester->execute(['-i' => null, '-o' => null]));
    }

    /**
     * @return void
     */
    public function testInputFileNotFound(): void
    {
        $this->assertEquals(GenTreeCommand::EXIT_CODE_INVALID, $this->commandTester->execute([
            '-i' => self::APP_DATA_PATH . '/not-existing-file.csv',
            '-o' => self::APP_DATA_PATH . '/output.json'
        ]));

        $this->assertRegExp('/File .*csv.* not found/', $this->commandTester->getDisplay());
    }

    /**
     * @return void
     */
    public function testInputFileIsNotCsv(): void
    {
        $this->assertEquals(GenTreeCommand::EXIT_CODE_INVALID, $this->commandTester->execute([
            '-i' => self::APP_DATA_PATH . '/output.example.json',
            '-o' => self::APP_DATA_PATH . '/output.json'
        ]));

        $this->assertRegExp('/Mode "r" for file .*json.* is not available/', $this->commandTester->getDisplay());
    }

    /**
     * @return void
     */
    public function testOutputFileAlreadyExists(): void
    {
        $this->commandTester->setInputs(['n']);
        $this->assertEquals(GenTreeCommand::EXIT_CODE_INVALID, $this->commandTester->execute([
            '-i' => self::APP_DATA_PATH . '/input.example.csv',
            '-o' => self::APP_DATA_PATH . '/output.example.json'
        ]));
        $this->assertStringContainsString('Output file already exists', $this->commandTester->getDisplay());
    }

    /**
     * @return void
     */
    public function testOutputFileIsNotJson(): void
    {
        $this->assertEquals(GenTreeCommand::EXIT_CODE_INVALID, $this->commandTester->execute([
            '-i' => self::APP_DATA_PATH . '/input.example.csv',
            '-o' => self::APP_DATA_PATH . '/output.csv'
        ]));

        $this->assertRegExp('/Mode "w" for file .*csv.* is not available/', $this->commandTester->getDisplay());
    }

    /**
     * @return void
     */
    public function testInputFileHasTooManyLines(): void
    {
        $this->assertEquals(GenTreeCommand::EXIT_CODE_SUCCESS, $this->commandTester->execute([
            '-i' => self::APP_DATA_PATH . '/input.example.csv',
            '-l' => 100,
            '-o' => self::APP_DATA_PATH . '/output.json'
        ]));

        $this->assertStringContainsString('Input file has too many lines', $this->commandTester->getDisplay());
    }

    /**
     * @return void
     */
    public function testNormalExecuting(): void
    {
        $this->assertEquals(GenTreeCommand::EXIT_CODE_SUCCESS, $this->commandTester->execute([
            '-i' => self::APP_DATA_PATH . '/input.example.csv',
            '-o' => self::APP_DATA_PATH . '/output.json'
        ]));

        $this->assertRegExp('/Read .* of .* from input file and write tree .* to output file/', $this->commandTester->getDisplay());
        $this->assertFileExists(self::APP_DATA_PATH . '/output.json');
        $this->assertGreaterThan(400, filesize(self::APP_DATA_PATH . '/output.json'));
    }
}