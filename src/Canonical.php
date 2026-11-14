<?php

namespace Steddle\Seal;

use JsonException;
use stdClass;

/**
 * The canonical form of RFC 8785, as vcrs-php writes it: the same value always gives the same bytes, and so the same
 * fingerprint.
 */
final class Canonical
{
    /**
     * @throws Refused
     */
    public static function json(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => self::number($value),
            is_string($value) => self::string($value),
            is_array($value) && array_is_list($value) => '['.implode(',', array_map(self::json(...), $value)).']',
            is_array($value), $value instanceof stdClass => self::object((array) $value),
            default => throw new Refused('not_json', 'Only JSON values have a canonical form.'),
        };
    }

    private static function string(string $value): string
    {
        try {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new Refused('not_json', 'A string in JSON is UTF-8.');
        }
    }

    /**
     * @param  array<array-key, mixed>  $members
     */
    private static function object(array $members): string
    {
        uksort($members, fn (int|string $a, int|string $b) => strcmp(self::utf16((string) $a), self::utf16((string) $b)));

        $written = [];

        foreach ($members as $name => $value) {
            $written[] = self::string((string) $name).':'.self::json($value);
        }

        return '{'.implode(',', $written).'}';
    }

    /**
     * A number as RFC 8785, section 3.2.2.3, writes it: the shortest digits that read back as the same double, laid out
     * as ECMAScript's Number.prototype.toString does.
     */
    private static function number(int|float $number): string
    {
        if (is_int($number) && abs($number) <= 2 ** 53) {
            return (string) $number;
        }

        $number = (float) $number;

        if (! is_finite($number)) {
            throw new Refused('not_finite', 'RFC 8785 has no form for NaN or an infinity.');
        }

        if ($number === 0.0) {
            return '0';
        }

        $precision = ini_set('serialize_precision', '-1');
        $shortest = var_export(abs($number), true);
        ini_set('serialize_precision', (string) $precision);

        preg_match('/^(\d+)\.(\d+)(?:E([+-]\d+))?$/', $shortest, $parts);
        $digits = ltrim($parts[1].$parts[2], '0');
        $point = strlen($parts[1]) + (int) ($parts[3] ?? 0) - (strlen($parts[1].$parts[2]) - strlen($digits));
        $digits = rtrim($digits, '0');
        $length = strlen($digits);

        $written = match (true) {
            $length <= $point && $point <= 21 => $digits.str_repeat('0', $point - $length),
            $point > 0 && $point <= 21 => substr($digits, 0, $point).'.'.substr($digits, $point),
            $point > -6 && $point <= 0 => '0.'.str_repeat('0', -$point).$digits,
            default => ($length === 1 ? $digits : $digits[0].'.'.substr($digits, 1)).'e'.($point - 1 >= 0 ? '+' : '-').abs($point - 1),
        };

        return ($number < 0 ? '-' : '').$written;
    }

    /**
     * RFC 8785 sorts member names by their UTF-16 code units.
     */
    private static function utf16(string $name): string
    {
        return mb_convert_encoding($name, 'UTF-16BE', 'UTF-8');
    }
}
