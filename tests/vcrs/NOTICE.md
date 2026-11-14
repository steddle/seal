# VCRS test vectors

The records under `vectors/` are the test vectors of [VCRS](https://vcrs.org), the Verified Claimant Register Standard, drafted by [Steddle](https://steddle.com) and licensed under [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/).

They are copied unchanged from [github.com/steddle/vcrs](https://github.com/steddle/vcrs) at commit `7535c6867d1384c588005b1e20ad40c34dd3c7cc`, the commit Seal's `vectors/canonical.json` names. Seal's tests read each record and its `expected.digest`: SHA-256 over the canonical form of the record without its own `digest` member.
