<?php

namespace app\console\commands\GenTree;

use LogicException;

class JsonFileAdapter extends AbstractFileAdapter
{
    /**
     * @inheritDoc
     */
    public function readFile()
    {
        throw new LogicException('JSON file reading not supported');
    }

    /**
     * @inheritDoc
     */
    public function writeFile(iterable $data, int $indent = 0): void
    {
        foreach ($data as $key => $val) {
            [$beginSymbol, $endSymbol] = is_numeric($key) ? ['[', ']'] : ['{', '}'];
            $this->writeLine($beginSymbol, $indent);

            if(is_scalar($val)) {
                $this->writeLine($this->makeJsonLine($key, $val), $this->incrementIndent($indent));
            } else {
                $this->writeFile($val, $this->incrementIndent($indent));
            }

            $this->writeLine($endSymbol, $indent);
        }
    }

    /**
     * @param string $field
     * @param string $value
     * @return string
     */
    private function makeJsonLine(string $field, string $value): string
    {
        return json_encode($field, JSON_UNESCAPED_UNICODE)
            . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE) . ',';
    }
}