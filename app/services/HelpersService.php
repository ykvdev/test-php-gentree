<?php

namespace app\services;

class HelpersService
{
    /**
     * @param int $memory Bytes
     * @return string Examples: 125 B, 52.6 Kb, 12 Mb, etc
     */
    public static function formatMemoryBytes(int $memory): string
    {
        $memoryFormatted = $memory;
        foreach (['B', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb', 'Eb', 'Zb', 'Yb'] as $i => $unit) {
            $memory = $memory / ($i == 0 ? 1 : 1024);
            if($memory < 1024 || $unit == 'Yb') {
                $memoryFormatted = round($memory, 2) . ' ' . $unit;
            }
        }

        return $memoryFormatted;
    }
}