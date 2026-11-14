<?php

namespace Steddle\Seal;

use Stringable;

/**
 * A SHA-256 value. It reads 64 hex characters in either case, bare or after `sha256:`, and prints `sha256:` and 64
 * lowercase hex characters. A short form, white space or another algorithm is refused, never trimmed or guessed.
 */
final class Fingerprint implements Stringable
{
    private function __construct(private readonly string $bytes) {}

    public static function of(string $bytes): self
    {
        return new self(hash('sha256', $bytes, true));
    }

    /**
     * @throws Refused
     */
    public static function read(string $written): self
    {
        $hex = $written;

        if (str_contains($written, ':')) {
            [$prefix, $hex] = explode(':', $written, 2);

            if ($prefix !== 'sha256') {
                throw new Refused('unknown_prefix', "A fingerprint is written after 'sha256:' or bare, never after '{$prefix}:'.");
            }
        }

        if (strlen($hex) !== 64) {
            throw new Refused('wrong_length', 'A fingerprint is 64 hex characters, '.strlen($hex).' given.');
        }

        if (strspn($hex, '0123456789abcdefABCDEF') !== 64) {
            throw new Refused('not_hex', 'A fingerprint is written in hex characters alone.');
        }

        return new self(hex2bin($hex));
    }

    public function equals(self $other): bool
    {
        return $this->bytes === $other->bytes;
    }

    public function __toString(): string
    {
        return 'sha256:'.bin2hex($this->bytes);
    }
}
