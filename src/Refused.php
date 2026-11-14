<?php

namespace Steddle\Seal;

use InvalidArgumentException;

/**
 * A refusal whose `why` is the code the vector files name, so a caller branches on the code and never on the wording.
 * `pointer` is the RFC 6901 JSON Pointer of the member at fault, where the refusal is about one member.
 */
final class Refused extends InvalidArgumentException
{
    public function __construct(public readonly string $why, string $message, public readonly ?string $pointer = null)
    {
        parent::__construct($message);
    }
}
