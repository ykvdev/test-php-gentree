<?php

namespace app\console\commands\GenTreeCommand;

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
    public function readFile(?int $maxLinesNumber = null): ?Generator
    {
        $lineNumber = 1;
        while ($line = $this->readLine()) {
            if($maxLinesNumber && $lineNumber - 1 > $maxLinesNumber) {
                $this->isTooManyLines = true;
                break;
            }

            if($lineNumber != 1) {
                [$itemName, $type, $parent, $relation] = str_getcsv($line, ';');
                yield [
                    'itemName' => trim($itemName),
                    'type' => trim($type),
                    'parent' => trim($parent) ?? null,
                    'relation' => trim($relation) ?? null,
                ];
            }

            $lineNumber++;
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