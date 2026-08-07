# Security Policy

## Supported Versions

| Version | Status | PHP Version | Laravel Version |
|---------|--------|-------------|-----------------|
| 1.0.x   | ✅ Supported | >= 8.5 | >= 13.0 |
| < 1.0   | ❌ End of Life | — | — |

## Reporting a Vulnerability

If you discover a security vulnerability in ZeroBoiler Enums, please report it
responsibly by contacting the maintainers directly.

**Do NOT** open a public GitHub issue for security vulnerabilities.

### How to Report

1. Send an email to the ZeroBoiler security team with the subject line
   `[SECURITY] ZeroBoiler Enums Vulnerability Report`.
2. Include a detailed description of the vulnerability, affected versions,
   and steps to reproduce.
3. A maintainer will acknowledge receipt within 48 hours (business days).

### What to Expect

- We will investigate the report and confirm the vulnerability.
- A patch will be developed and released as a new version.
- Credit will be given to the reporter (unless anonymity is requested).
- Public disclosure will occur after the patch is available.

## Security Considerations

### Enum Metadata

The `HasEnumMetadata` trait reads PHP attributes via reflection. Attributes are
part of the codebase, not user input, so there is no injection risk. Metadata is
cached in-memory (per-process) and never persisted to disk or external storage.

### Eloquent Cast

`EnumCast` uses `tryFrom()` for deserialization — unknown values are silently
converted to `null`. This is by design (matches Eloquent conventions). If you
need strict validation on stored values, use model-level rules or accessors.

### Validation Rule

`EnumRule` validates input against valid enum cases. It does not expose internal
enum metadata or implementation details in error messages — only allowed values
are listed.

### CLI Commands

The `zeroboiler:enum-inspect` and `zeroboiler:enum-test` commands read enum
metadata via reflection. They only introspect classes that exist in the
application codebase and do not accept external input as class names in
production contexts.

## Dependencies

This package depends on:

- `illuminate/contracts` ^13.0
- `illuminate/support` ^13.0
- `illuminate/validation` ^13.0

Review the [Laravel Security Policy](https://github.com/laravel/framework/security)
for dependency vulnerability disclosures.
