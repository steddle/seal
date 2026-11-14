# steddle/seal

Seal is how every Steddle product stores proof alike. It lives in the foundry, laid out as a package of its own: its own namespace, `Steddle\Seal`, its own vectors, and no requirement beyond PHP. It imports nothing from Laravel and registers nothing, so a product that installs the foundry gets the classes and nothing runs.

## Fingerprint

A SHA-256 value. `Fingerprint::read()` takes 64 hex characters in either case, bare or after `sha256:`, and refuses anything else with a `Refused` whose `why` is `wrong_length`, `not_hex` or `unknown_prefix`. A short form or a trailing line break is refused, never trimmed. `Fingerprint::of()` hashes bytes. A fingerprint prints `sha256:` and 64 lowercase hex characters, and two are equal when their 32 bytes are.

```php
use Steddle\Seal\Fingerprint;

(string) Fingerprint::read('E3B0C442...B855'); // sha256:e3b0c442...b855
Fingerprint::of('')->equals(Fingerprint::read('sha256:e3b0c442...b855')); // true
```

## Canonical JSON

RFC 8785 over I-JSON, ported from vcrs-php's `Canonical::json` and held to the same bytes. `Json::decode()` reads text and refuses a member name twice (`duplicate_name`), NaN or an infinity (`not_finite`), half a surrogate pair (`lone_surrogate`) and anything else that is not JSON (`not_json`). `Canonical::json()` writes a decoded value with its members sorted by UTF-16 code units and its numbers as ECMAScript prints them, and refuses a number past the largest double.

```php
use Steddle\Seal\Canonical;
use Steddle\Seal\Fingerprint;
use Steddle\Seal\Json;

Canonical::json(Json::decode('{ "b": [1.0, 1e0], "a": null }')); // {"a":null,"b":[1,1]}
Fingerprint::of(Canonical::json($record)); // the same record always gives the same fingerprint
```

## Vectors

`vectors/` is the contract, copied unchanged from Seal's specification. The tests in `tests/` read every case from it and run in the foundry's suite. `tests/vcrs/` holds the 138 records of the VCRS standard, pinned at one commit under CC BY 4.0 (its `NOTICE.md`), and each must get its stated digest.
