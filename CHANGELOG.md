# Changelog

## [Unreleased]

**Added**
- `Fingerprint`: reads a SHA-256 written in 64 hex characters of either case, bare or after `sha256:`, and prints `sha256:` with lowercase hex.
- `Json::decode()` and `Canonical::json()`: RFC 8785 over I-JSON, to the same bytes as VCRS's validator on all 138 of its test records.
- `Refused`, with a fixed `why` per refusal and, where one member is at fault, its JSON Pointer in `pointer`.
- `vectors/fingerprint.json` and `vectors/canonical.json`, the contract every implementation is tested against.
