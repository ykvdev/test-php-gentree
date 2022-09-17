<?php

namespace app\console\commands\GenTree;

use LogicException;

class JsonFileAdapter extends AbstractFileAdapter
{
    protected const EXTENSION = 'json',
        MIME_TYPES = ['application/json'],
        MODES = [self::MODE_WRITE];

    private const INDENT_SPACES_NUMBER = 2;

    /**
     * @inheritDoc
     */
    public function readFile()
    {
        throw new LogicException('JSON file reading not supported');
    }

    /**
     * @param iterable $data
     * @param int $indent
     * @param bool $isNeedIndentBeginBrace
     * @param int $depth
     * @return void
     */
    public function writeFile(iterable $data, int $indent = 0, bool $isNeedIndentBeginBrace = true, int $depth = 0): void
    {
        $beginBraceWrote = false;
        $indentForElements = $indent + self::INDENT_SPACES_NUMBER;
        foreach ($data as $key => $val) {
            if(!$beginBraceWrote) {
                [$beginBrace, $endBrace] = is_numeric($key) ? ['[', ']'] : ['{', '}'];
                if($isNeedIndentBeginBrace) {
                    $this->writeLine($beginBrace, $indent);
                } else {
                    $this->seek(-1);
                    $this->writeLine($beginBrace);
                }
                $beginBraceWrote = true;
            }

            if($field = $this->makeJsonField($key, $val ?: null)) {
                $this->writeLine($field, $indentForElements);
            }

            if(is_iterable($val)) {
                $this->writeFile($val, $indentForElements, !$field, $depth + 1);
            }

            $this->seek(-1);
            $this->writeLine(',');
        }

        if($beginBraceWrote) {
            $this->seek(-2);
            $this->writeLine(); // Write EOL
            $this->writeLine($endBrace, $indentForElements - self::INDENT_SPACES_NUMBER);
        } else {
            $this->seek(-1);
            $this->writeLine('[]');
        }

        // Remove last EOL in the end of file
        if($depth == 0) {
            $this->truncate($this->getSize() - 1);
        }
    }

    /**
     * @param mixed $field
     * @param mixed $value
     * @return string
     */
    private function makeJsonField($field, $value): ?string
    {
        return (
            (is_string($field) ? $this->encodeJsonString($field) . ': ' : '')
            . (is_scalar($value) || is_null($value) ? $this->encodeJsonString($value) : '')
        ) ?: null;
    }

    /**
     * @param string|null $str
     * @return string
     */
    private function encodeJsonString(?string $str = null): string
    {
        return json_encode($str, JSON_UNESCAPED_UNICODE);
    }
}