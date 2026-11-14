<?php

namespace Steddle\Seal;

use InvalidArgumentException;

/**
 * A refusal whose `why` is the code the vector files name, so a caller branches on the code and never on the wording.
 */
final class Refused extends InvalidArgumentException
{
    public function __construct(public readonly string $why, string $message)
    {
        parent::__construct($message);
    }
}
