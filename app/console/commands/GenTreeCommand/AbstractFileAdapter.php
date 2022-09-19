<?php

namespace app\console\commands\GenTreeCommand;

use Closure;
use finfo;
use Generator;
use LogicException;

abstract class AbstractFileAdapter
{
    public const MODE_READ = 'r';
    public const MODE_WRITE = 'w';

    private const ADAPTERS = [
        CsvFileAdapter::EXTENSION => CsvFileAdapter::class,
        JsonFileAdapter::EXTENSION => JsonFileAdapter::class,
    ];

    /** @var string */
    private $path;

    /** @var string */
    private $mode;

    /** @var resource|null */
    private $handler;

    /** @var Closure|null */
    private $progressRwCallback;

    /** @var int */
    private $progressRwCallbackLastCalledTime;

    /** @var int */
    private $rwBytes = 0;

    /** @var bool */
    protected $isTooManyLines = false;

    /**
     * @param string $path
     * @param string $mode
     * @return self
     */
    public static function factory(string $path, string $mode): self
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if(!($adapter = self::ADAPTERS[$ext] ?? null)) {
            throw new LogicException("No adapter found for file \"$path\"");
        }

        return new $adapter($path, $mode);
    }

    /**
     * @param string $path
     * @param string $mode
     */
    public function __construct(string $path, string $mode)
    {
        $this->path = $path;
        $this->mode = $mode;
    }

    /**
     * @return void
     */
    public function open(): void
    {
        if(!in_array($this->mode, static::MODES)) {
            throw new LogicException("Mode \"$this->mode\" for file \"$this->path\" is not available");
        }

        switch ($this->mode) {
            case self::MODE_READ:
                clearstatcache(true, $this->path);

                if(!is_file($this->path)) {
                    throw new LogicException("File \"$this->path\" not found");
                }

                if(!is_readable($this->path)) {
                    throw new LogicException("File \"$this->path\" is not readable");
                }

                $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($this->path);
                if(!in_array($mimeType, static::MIME_TYPES)) {
                    throw new LogicException("File \"$this->path\" MIME-type is wrong");
                }
                break;

            case self::MODE_WRITE:
                $dirPath = dirname($this->path);
                clearstatcache(true, $dirPath);

                if(!is_dir($dirPath)) {
                    throw new LogicException("Directory \"$dirPath\" not found");
                }

                if(!is_writable($dirPath)) {
                    throw new LogicException("Directory \"$dirPath\" is not writable");
                }
                break;

            default:
                throw new LogicException("File mode \"$this->mode\" is not available");
        }

        if (($this->handler = fopen($this->path, $this->mode)) === false) {
            throw new LogicException("File \"$this->path\" open failed");
        }
    }

    /**
     * @param Closure|null $progressRwCallback
     */
    public function setProgressRwCallback(?Closure $progressRwCallback): void
    {
        $this->progressRwCallback = $progressRwCallback;
    }

    /**
     * @param int|null $maxLinesNumber
     * @return Generator|null|void
     */
    abstract public function readFile(?int $maxLinesNumber = null);

    /**
     * @param iterable $data
     * @param int $indent
     * @return void
     */
    abstract public function writeFile(iterable $data, int $indent = 0): void;

    /**
     * @return string|false
     */
    public function readLine()
    {
        $str = !feof($this->handler) ? fgets($this->handler) : false;

        if(($position = $this->getPosition()) > $this->rwBytes) {
            $this->rwBytes = $position;
        }

        $this->callRwCallback();

        return $str;
    }

    /**
     * @param string|int|float|null $data
     * @param int $indent
     * @return void
     */
    public function writeLine($data = null, int $indent = 0): void
    {
        $data = str_repeat(' ', $indent) . $data . PHP_EOL;
        if(fputs($this->handler, $data) === false) {
            throw new LogicException("Write file \"$this->path\" failed");
        }
        fflush($this->handler);

        $this->rwBytes += $data ? strlen($data) : 0;

        $this->callRwCallback();
    }

    /**
     * @param int $size
     * @return void
     */
    public function truncate(int $size): void
    {
        if(!ftruncate($this->handler, $size)) {
            throw new LogicException("Truncate file \"$this->path\" to size $size failed");
        }

        $this->rwBytes = $size;

        $this->callRwCallback();
    }

    /**
     * @return false|int
     */
    public function getPosition()
    {
        if(($position = ftell($this->handler)) === false) {
            throw new LogicException("Get file \"$this->path\" position failed");
        }

        return $position;
    }

    /**
     * @param int $offset
     * @param int $whence
     * @return void
     */
    public function setPosition(int $offset, int $whence = SEEK_CUR): void
    {
        if(fseek($this->handler, $offset, $whence) === -1) {
            throw new LogicException("Set file \"$this->path\" position to $offset failed");
        }
    }

    /**
     * @return false|int
     */
    public function getSize()
    {
        return filesize($this->path);
    }

    /**
     * @return int
     */
    public function getRwSize(): int
    {
        return $this->rwBytes;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @return bool
     */
    public function isTooManyLines(): bool
    {
        return $this->isTooManyLines;
    }

    /**
     * @return void
     */
    private function callRwCallback(): void
    {
        $time = time();
        if($this->progressRwCallback && $time > $this->progressRwCallbackLastCalledTime) {
            $this->progressRwCallbackLastCalledTime = $time;
            ($this->progressRwCallback)();
        }
    }

    public function __destruct()
    {
        if(is_resource($this->handler)) {
            fclose($this->handler);
        }
    }
}