# Seal

[![Packagist](https://img.shields.io/packagist/v/steddle/seal)](https://packagist.org/packages/steddle/seal)
[![Tests](https://github.com/steddle/seal/actions/workflows/tests.yml/badge.svg)](https://github.com/steddle/seal/actions/workflows/tests.yml)
[![License](https://img.shields.io/github/license/steddle/seal)](LICENSE)

The fingerprint and canonical JSON that Steddle's products store proof with.

Two parts. A fingerprint is a SHA-256 value written one way, so two systems that store the same bytes agree on its text. Canonical JSON turns data into one exact byte sequence (RFC 8785 over I-JSON), so anyone who holds the data gets the same fingerprint. Plain PHP: no framework, nothing registered, no requirement beyond `ext-mbstring`.

## Installation

```bash
composer require steddle/seal
```

### Requires

- PHP 8.3 or later, with `ext-mbstring`

## Quick start

```php
use Steddle\Seal\Canonical;
use Steddle\Seal\Fingerprint;
use Steddle\Seal\Json;

$record = Json::decode('{ "b": [1.0, 1e0], "a": null }');

Canonical::json($record);                           // {"a":null,"b":[1,1]}
(string) Fingerprint::of(Canonical::json($record)); // sha256:7773f186...c753bb05
```

The same record, written with other spacing, member order or number forms, gives the same canonical bytes and so the same fingerprint.

## Fingerprint

- `Fingerprint::of($bytes)` hashes bytes.
- `Fingerprint::read($text)` takes 64 hex characters in either case, bare or after `sha256:`.
- A fingerprint prints `sha256:` and 64 lowercase hex characters. Two are equal when their 32 bytes are.
- A short form, white space, a trailing line break or another prefix is refused, never trimmed.

## Canonical JSON

- `Json::decode($text)` reads I-JSON and refuses a member name twice, NaN or an infinity, half a surrogate pair, and any text that isn't JSON.
- `Canonical::json($value)` writes members sorted by UTF-16 code units and numbers as ECMAScript prints them.
- Every refusal is a `Refused` exception. Its `why` is a fixed code (`duplicate_name`, `not_finite`, `lone_surrogate`, `not_json`, `wrong_length`, `not_hex`, `unknown_prefix`), so callers can branch on it. Where one member is at fault, `pointer` gives its RFC 6901 JSON Pointer, such as `/records/3/id`.

## Vectors

`vectors/` is the contract: `fingerprint.json` and `canonical.json` state every input and its expected output or refusal, RFC 8785's number table among them. Another implementation in any language is equal to this one when it gives the same answer on every case. `tests/vcrs/` holds the 138 test records of [VCRS](https://vcrs.org), the Verified Claimant Register Standard, and each gets its stated fingerprint.

```bash
composer install
vendor/bin/pest
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Credits

- [Steddle](https://steddle.com)
- [RFC 8785](https://www.rfc-editor.org/rfc/rfc8785), JSON Canonicalization Scheme, and [RFC 7493](https://www.rfc-editor.org/rfc/rfc7493), I-JSON

## License

The code is MIT. See [LICENSE](LICENSE). The test vectors in `vectors/` are CC BY 4.0, as their [notice](vectors/NOTICE.md) says, and so are the VCRS records in `tests/vcrs/` ([notice](tests/vcrs/NOTICE.md)).
