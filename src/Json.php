<?php

namespace Steddle\Seal;

use JsonException;

/**
 * JSON as RFC 8785 reads it, I-JSON (RFC 7493): a member name that appears twice in one object is refused, since
 * parsers disagree on which value wins.
 */
final class Json
{
    /**
     * @throws Refused
     */
    public static function decode(string $json): mixed
    {
        try {
            $value = json_decode($json, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $invalid) {
            throw match (true) {
                $invalid->getCode() === JSON_ERROR_UTF16 => new Refused('lone_surrogate', 'A string holds half a surrogate pair.'),
                self::holdsNonFinite($json) => new Refused('not_finite', 'JSON has no NaN or infinity.'),
                default => new Refused('not_json', "The text is not JSON: {$invalid->getMessage()}."),
            };
        }

        $offset = 0;
        self::scan($json, $offset, '');

        return $value;
    }

    /**
     * NaN, Infinity and -Infinity are what JavaScript and Python write for a number JSON has no form for, so text
     * that holds one outside a string is refused as not finite rather than as not JSON.
     */
    private static function holdsNonFinite(string $json): bool
    {
        return preg_match('/\b(?:NaN|Infinity)\b/', preg_replace('/"(?:[^"\\\\]|\\\\.)*"/s', '""', $json)) === 1;
    }

    /**
     * Walks one value of JSON that json_decode already accepted, so the text is well formed.
     */
    private static function scan(string $json, int &$offset, string $pointer): void
    {
        self::space($json, $offset);

        if ($json[$offset] === '{') {
            $offset++;
            $names = [];
            self::space($json, $offset);

            while ($json[$offset] !== '}') {
                $name = self::string($json, $offset);
                $member = $pointer.'/'.str_replace(['~', '/'], ['~0', '~1'], $name);

                if (isset($names[$name])) {
                    throw new Refused('duplicate_name', "The member '{$name}' appears more than once in its object.", $member);
                }

                $names[$name] = true;
                self::space($json, $offset);
                $offset++;
                self::scan($json, $offset, $member);
                self::space($json, $offset);

                if ($json[$offset] === ',') {
                    $offset++;
                    self::space($json, $offset);
                }
            }

            $offset++;

            return;
        }

        if ($json[$offset] === '[') {
            $offset++;
            self::space($json, $offset);

            for ($index = 0; $json[$offset] !== ']'; $index++) {
                self::scan($json, $offset, "{$pointer}/{$index}");
                self::space($json, $offset);

                if ($json[$offset] === ',') {
                    $offset++;
                    self::space($json, $offset);
                }
            }

            $offset++;

            return;
        }

        if ($json[$offset] === '"') {
            self::string($json, $offset);

            return;
        }

        $offset += strspn($json, '0123456789+-.eEtrufalsn', $offset);
    }

    /**
     * Reads the string at the offset, which sits on its opening quote, and returns it decoded.
     */
    private static function string(string $json, int &$offset): string
    {
        $start = $offset++;

        while ($json[$offset] !== '"') {
            $offset += $json[$offset] === '\\' ? 2 : 1;
        }

        $offset++;

        return json_decode(substr($json, $start, $offset - $start), flags: JSON_THROW_ON_ERROR);
    }

    private static function space(string $json, int &$offset): void
    {
        $offset += strspn($json, " \t\n\r", $offset);
    }
}
