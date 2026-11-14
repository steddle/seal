<?php

namespace Steddle\Seal;

use stdClass;

/**
 * What a seal stamps: one JSON object that names its form and points at other things with references. Its RFC 8785
 * bytes are fingerprinted and kept.
 */
final class Statement
{
    private function __construct(private readonly array|stdClass $value, private readonly string $bytes) {}

    /**
     * @throws Refused
     */
    public static function read(string $json): self
    {
        return self::of(Json::decode($json));
    }

    /**
     * @throws Refused
     */
    public static function of(mixed $value): self
    {
        if (($problems = self::problems($value)) !== []) {
            throw new Refused($problems[0], 'The statement breaks the statement rules: '.implode(', ', $problems).'.');
        }

        return new self($value, Canonical::json($value));
    }

    /**
     * @return list<string> every rule the value breaks, sorted
     */
    public static function problems(mixed $value): array
    {
        if (! self::isObject($value)) {
            return ['not_an_object'];
        }

        $members = (array) $value;
        $why = [];

        if (! array_key_exists('form', $members)) {
            $why[] = 'missing_form';
        } elseif (! is_string($members['form']) || preg_match(Reference::FORM, $members['form']) !== 1) {
            $why[] = 'form_pattern';
        }

        if (array_key_exists('stated_at', $members) && (! is_string($members['stated_at']) || preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ\z/', $members['stated_at']) !== 1)) {
            $why[] = 'time_not_utc';
        }

        self::walk($value, $why);

        $why = array_values(array_unique($why));
        sort($why, SORT_STRING);

        return $why;
    }

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function fingerprint(): Fingerprint
    {
        return Fingerprint::of($this->bytes);
    }

    public function form(): string
    {
        return ((array) $this->value)['form'];
    }

    /**
     * @return array<string, Reference> every reference, by its RFC 6901 JSON Pointer, in the order the statement holds them
     */
    public function references(): array
    {
        $references = [];
        self::collect($this->value, '', $references);

        return $references;
    }

    /**
     * @param  list<string>  $why
     */
    private static function walk(mixed $value, array &$why): void
    {
        if (self::isObject($value)) {
            $members = (array) $value;

            if (array_key_exists('fingerprint', $members)) {
                self::reference($members, $why);

                return;
            }

            foreach ($members as $name => $member) {
                if (str_starts_with((string) $name, "\0")) {
                    $why[] = 'name_starts_with_nul';
                }

                self::walk($member, $why);
            }

            return;
        }

        match (true) {
            is_array($value) => array_walk($value, fn (mixed $item) => self::walk($item, $why)),
            is_float($value) => $why[] = 'fraction_in_statement',
            is_int($value) && abs($value) > 2 ** 53 => $why[] = 'number_too_large',
            is_string($value) && preg_match('/^(sha256:)?[0-9a-fA-F]{64}\z/', $value) === 1 => $why[] = 'fingerprint_outside_reference',
            default => null,
        };
    }

    /**
     * @param  array<array-key, mixed>  $members
     * @param  list<string>  $why
     */
    private static function reference(array $members, array &$why): void
    {
        if (! self::isReference($members)) {
            $why[] = 'reference_shape';

            return;
        }

        if (! is_string($members['fingerprint']) || preg_match('/^sha256:[0-9a-f]{64}\z/', $members['fingerprint']) !== 1) {
            $why[] = 'fingerprint_spelling';
        }

        if (! is_string($members['over']) || ! Reference::names($members['over'])) {
            $why[] = 'over_pattern';
        }
    }

    /**
     * @param  array<string, Reference>  $references
     */
    private static function collect(mixed $value, string $pointer, array &$references): void
    {
        if (! is_array($value) && ! $value instanceof stdClass) {
            return;
        }

        $members = (array) $value;

        if (self::isObject($value) && self::isReference($members)) {
            $references[$pointer] = Reference::to(Fingerprint::read($members['fingerprint']), $members['over']);

            return;
        }

        foreach ($members as $name => $member) {
            self::collect($member, $pointer.'/'.str_replace(['~', '/'], ['~0', '~1'], (string) $name), $references);
        }
    }

    /**
     * @param  array<array-key, mixed>  $members
     */
    private static function isReference(array $members): bool
    {
        $names = array_map(strval(...), array_keys($members));
        sort($names, SORT_STRING);

        return $names === ['fingerprint', 'over'];
    }

    private static function isObject(mixed $value): bool
    {
        return $value instanceof stdClass || (is_array($value) && $value !== [] && ! array_is_list($value));
    }
}
