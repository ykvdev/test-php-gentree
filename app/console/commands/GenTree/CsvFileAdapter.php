<?php

namespace app\console\commands\GenTree;

use Generator;
use LogicException;

class CsvFileAdapter extends AbstractFileAdapter
{
    protected const EXTENSION = 'csv',
        MIME_TYPES = ['text/csv', 'text/plain'],
        MODES = [self::MODE_READ];

    /**
     * @inheritDoc
     */
    public function readFile(): ?Generator
    {
        $lineNumber = 0;
        while ($line = $this->readLine()) {
            if($lineNumber++ != 0) {
                [$itemName, $type, $parent, $relation] = str_getcsv($line, ';');
                yield [
                    'itemName' => trim($itemName),
                    'type' => trim($type),
                    'parent' => trim($parent) ?? null,
                    'relation' => trim($relation) ?? null,
                ];
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function writeFile(iterable $data, int $indent = 0): void
    {
        throw new LogicException('CSV file writing not supported');
    }
}