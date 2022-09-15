<?php

namespace app\console\commands\GenTree;

use finfo;
use Generator;
use LogicException;

abstract class AbstractFileAdapter
{
    public const MODE_READ = 'r';
    public const MODE_WRITE = 'a';

    private const INDENT_SPACES_NUMBER = 4;

    private const FILE_ADAPTERS = [
        'csv' => ['class' => CsvFileAdapter::class, 'mimeType' => 'text/csv'],
        'json' => ['class' => JsonFileAdapter::class, 'mimeType' => 'application/json'],
    ];

    /** @var string */
    private $path;

    /** @var string */
    private $mode;

    /** @var resource */
    private $handler;

    /**
     * @param string $path
     * @param string $mode
     * @return self
     */
    public static function factory(string $path, string $mode): self
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if(!($adapter = self::FILE_ADAPTERS[$ext] ?? null)) {
            throw new LogicException("No adapter for file \"$path\"");
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if($mimeType != $adapter['mimeType']) {
            throw new LogicException("File \"$path\" MIME-type is wrong");
        }

        return new $adapter['class']($path, $mode);
    }

    /**
     * @param string $path
     * @param string $mode
     */
    public function __construct(string $path, string $mode)
    {
        $this->path = $path;
        $this->mode = $mode;

        switch ($mode) {
            case self::MODE_READ:
                clearstatcache(true, $path);

                if(!is_file($path)) {
                    throw new LogicException("File \"$path\" not found");
                }

                if(!is_readable($path)) {
                    throw new LogicException("File \"$path\" is not readable");
                }
                break;

            case self::MODE_WRITE:
                $dirPath = dirname($path);
                clearstatcache(true, $dirPath);

                if(!is_dir($dirPath)) {
                    throw new LogicException("Directory \"$dirPath\" not found");
                }

                if(!is_writable($dirPath)) {
                    throw new LogicException("Directory \"$dirPath\" is not writable");
                }
                break;

            default:
                throw new LogicException("File mode \"$mode\" is not available");
        }

        if (($this->handler = fopen($path, $mode)) === false) {
            throw new LogicException("File \"$path\" open failed");
        }
    }

    /**
     * @return Generator|null|void
     */
    abstract public function readFile();

    /**
     * @param iterable $data
     * @param int $indent
     * @return void
     */
    abstract public function writeFile(iterable $data, int $indent = 0): void;

    /**
     * @return string|false
     */
    protected function readLine()
    {
        return !feof($this->handler) ? fgets($this->handler) : false;
    }

    /**
     * @param string|int|float $data
     * @param int $indent
     * @return void
     */
    protected function writeLine($data, int $indent = 0): void
    {
        if(fputs($this->handler, str_repeat(' ', $indent) . $data . PHP_EOL) === false) {
            throw new LogicException("Write file \"$this->path\" failed");
        }

        fflush($this->handler);
    }

    /**
     * @param int $indent
     * @return int
     */
    protected function incrementIndent(int $indent = 0): int
    {
        return $indent + self::INDENT_SPACES_NUMBER;
    }

    public function __destruct()
    {
        fclose($this->handler);
    }
}