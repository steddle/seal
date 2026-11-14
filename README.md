# steddle/seal

Seal is how every Steddle product stores proof alike. It lives in the foundry, laid out as a package of its own: its own namespace, `Steddle\Seal`, its own vectors, and no requirement beyond PHP. It imports nothing from Laravel and registers nothing, so a product that installs the foundry gets the classes and nothing runs.

## Fingerprint

A SHA-256 value. `Fingerprint::read()` takes 64 hex characters in either case, bare or after `sha256:`, and refuses anything else with a `Refused` whose `why` is `wrong_length`, `not_hex` or `unknown_prefix`. A short form or a trailing line break is refused, never trimmed. `Fingerprint::of()` hashes bytes. A fingerprint prints `sha256:` and 64 lowercase hex characters, and two are equal when their 32 bytes are.

```php
use Steddle\Seal\Fingerprint;

(string) Fingerprint::read('E3B0C442...B855'); // sha256:e3b0c442...b855
Fingerprint::of('')->equals(Fingerprint::read('sha256:e3b0c442...b855')); // true
```

## Vectors

`vectors/` is the contract, copied unchanged from Seal's specification. The tests in `tests/` read every case from it and run in the foundry's suite.
