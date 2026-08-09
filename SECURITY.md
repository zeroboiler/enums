# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability within ZeroBoiler Enums, please send an email to security@zeroboiler.com.

All security vulnerabilities will be promptly addressed. We request that you do not publicly disclose the vulnerability until we have issued a fix.

## Supported Versions

Only the latest version of `zeroboiler/enums` receives security patches.

| Version | Supported |
| ------- | ---------- |
| 1.x     | ✅        |

## Scope

This package provides attribute-based metadata resolution for PHP enums and validation rules. It does not:

- Handle user authentication or authorization
- Process or store user input directly (validation is delegated to Laravel's validator)
- Make network requests or interact with databases
- Execute shell commands or eval'd code

The primary security surfaces are:

1. **Enum validation** — The `EnumRule` validation rule delegates to Laravel's validator. It uses strict type checking (`is_int()`, `is_string()`) before passing values to `tryFrom()`, preventing TypeError exceptions.
2. **Eloquent casting** — The `EnumCast` validates enum type mismatches on `set()` and silently returns `null` for invalid values on `get()` (matching Eloquent conventions).
3. **Metadata resolution** — The `EnumMetadataResolver` uses PHP Reflection API to read attribute metadata. It does not execute any code from attribute constructor arguments.
