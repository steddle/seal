<?php

namespace Steddle\Seal;

/**
 * How a statement points at something: its fingerprint and what that fingerprint was taken over, `bytes`, `rfc8785`
 * or a form name.
 */
final class Reference
{
    public const string FORM = '/^[a-z][a-z0-9-]*\.[a-z][a-z0-9-]*\/[1-9][0-9]*$/';

    private function __construct(public readonly Fingerprint $fingerprint, public readonly string $over) {}

    /**
     * @throws Refused
     */
    public static function to(Fingerprint $fingerprint, string $over): self
    {
        if (! self::names($over)) {
            throw new Refused('over_pattern', "A reference is over 'bytes', 'rfc8785' or a form name, never '{$over}'.");
        }

        return new self($fingerprint, $over);
    }

    public static function names(string $over): bool
    {
        return in_array($over, ['bytes', 'rfc8785'], true) || preg_match(self::FORM, $over) === 1;
    }

    /**
     * @return array{fingerprint: string, over: string}
     */
    public function toArray(): array
    {
        return ['fingerprint' => (string) $this->fingerprint, 'over' => $this->over];
    }
}
