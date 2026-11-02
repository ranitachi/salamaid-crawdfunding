<?php

namespace App\Support;

/**
 * Neutralises spreadsheet formula injection (CSV injection) in exported cells.
 * Text that a spreadsheet would evaluate (= + - @, TAB, CR prefixes) gets a
 * leading apostrophe; numbers and numeric strings are left untouched.
 */
final class CsvSanitizer
{
    private const TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @param array<int|string, mixed> $cells
     * @return array<int|string, mixed>
     */
    public static function row(array $cells): array
    {
        return array_map([self::class, 'cell'], $cells);
    }

    public static function cell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        return in_array($value[0], self::TRIGGERS, true) ? "'".$value : $value;
    }
}
