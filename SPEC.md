# Steddle Seal 1.0

A Steddle Seal shows that a file is identical to one that existed no later than the time it records. It doesn't show who made or sent the file, and it isn't an electronic seal, an electronic signature or a qualified time stamp under the EU eIDAS Regulation.

This document says how a seal is made and how anyone checks one. The test vectors in `vectors/` are part of it: where this text and a vector differ, the vector holds.

## 1. Fingerprint

A fingerprint is the SHA-256 of bytes, written `sha256:` followed by 64 lowercase hexadecimal characters. A reader accepts the 64 characters in either case, bare or after `sha256:`, and refuses anything else, never trimming it. Two fingerprints are equal when their 32 bytes are. `vectors/fingerprint.json`.

## 2. Canonical JSON

Data is turned into bytes by RFC 8785 over I-JSON (RFC 7493): members sorted by their UTF-16 code units, no insignificant white space, numbers as ECMAScript prints them. A member name that appears twice, NaN, an infinity and half a surrogate pair are refused. `vectors/canonical.json`.

## 3. Statement

What a seal stamps is a statement: one JSON object.

1. Its RFC 8785 bytes are what is fingerprinted and stamped, and they are kept as they are.
2. It has a member `form`, the name of its layout: `issuer.thing/version`, in lowercase, such as `sendnda.agreement/1`. The issuer gives the name and never reuses it.
3. Wherever it points at something, it uses a reference: an object with exactly `fingerprint` and `over`. `over` says what the fingerprint was taken over: `bytes` (a file as it is), `rfc8785` (data in its canonical form) or a form name. A fingerprint never stands as a plain string.
4. It holds no fraction: a decimal is written as a string. Whole numbers go up to 2^53.
5. `stated_at`, where present, is the issuer's own clock: UTC to the second, ending in `Z`. It is the issuer's word. The time a seal proves is the block's (section 5).
6. No member name starts with U+0000.
7. Everything else in it is the issuer's own.
8. It holds nothing a reader of the sealed files may not see.

`vectors/statement.json` gives three statements with their bytes, fingerprint and references, and sixteen that are refused, each with its reason.

## 4. Proof

The statement's fingerprint is stamped through OpenTimestamps, which commits it into a Bitcoin block. Only the fingerprint leaves the issuer. The proof is a file of its own beside the statement, never inside it: a statement never lists its own proof. A proof is upgraded once, when a block confirms it. After that it leads from the fingerprint to that block.

## 5. Checking a seal

With the statement's bytes, the proof and the files the statement points at, anyone checks a seal on public tools:

1. The SHA-256 of each file equals the fingerprint its reference holds.
2. The proof leads from the statement's fingerprint to a Bitcoin block: `ots --no-bitcoin verify statement.json.ots` names the block and the merkle root it must carry, which any block explorer shows.
3. That block's time is the latest the statement, and every file it fingerprints, can have existed. A block's time is good to a few hours.

## 6. Versions

This is Steddle Seal 1.0. A seal made before it keeps its own bytes and proof, and is issued again under 1.0: a 1.0 statement that references the earlier stamped bytes and their proof as files.
