<?php

namespace app\console\commands\GenTree;

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

        if(!in_array($mode, static::MODES)) {
            throw new LogicException("Mode \"$mode\" for file \"$this->path\" is not available");
        }

        switch ($mode) {
            case self::MODE_READ:
                clearstatcache(true, $path);

                if(!is_file($path)) {
                    throw new LogicException("File \"$path\" not found");
                }

                if(!is_readable($path)) {
                    throw new LogicException("File \"$path\" is not readable");
                }

                $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($path);
                if(!in_array($mimeType, static::MIME_TYPES)) {
                    throw new LogicException("File \"$path\" MIME-type is wrong");
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
     * @param Closure|null $progressRwCallback
     */
    public function setProgressRwCallback(?Closure $progressRwCallback): void
    {
        $this->progressRwCallback = $progressRwCallback;
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
    public function readLine()
    {
        $str = !feof($this->handler) ? fgets($this->handler) : false;

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
        if(fputs($this->handler, str_repeat(' ', $indent) . $data . PHP_EOL) === false) {
            throw new LogicException("Write file \"$this->path\" failed");
        }

        fflush($this->handler);

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