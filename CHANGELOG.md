# Changelog

## [0.1.1] - 2026-11-14

**Added**
- `Statement` and `Reference`: what a seal stamps, by the rules of `SPEC.md`, with `vectors/statement.json` (three statements, sixteen refusals).
- `SPEC.md`: Steddle Seal 1.0, how a seal is made and how anyone checks one.
- `canonical.json` holds the short escapes: backspace, tab, form feed and carriage return.

**Changed**
- The copy of VCRS's 138 test records is gone from `tests/vcrs/`. They belong to VCRS's own suite.
- A Composer download leaves out `tests/`, `phpunit.xml` and the repository's own files.

## [0.1.0] - 2026-11-14

First public release.

**Added**
- `Fingerprint`: reads a SHA-256 written in 64 hex characters of either case, bare or after `sha256:`, and prints `sha256:` with lowercase hex.
- `Json::decode()` and `Canonical::json()`: RFC 8785 over I-JSON, to the same bytes as VCRS's validator on all 138 of its test records.
- `Refused`, with a fixed `why` per refusal and, where one member is at fault, its JSON Pointer in `pointer`.
- `vectors/fingerprint.json` and `vectors/canonical.json`, the contract every implementation is tested against, under CC BY 4.0.
