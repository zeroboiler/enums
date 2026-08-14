<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Edge case tests for EnumCast — type mismatches, cross-enum casts,
 * and serialization behavior under PHPStan Level 9 constraints.
 *
 * @see \ZeroBoiler\Enums\Casts\EnumCast
 */

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

// ── Fixtures ──────────────────────────────────────────────────

#[\ZeroBoiler\Enums\Attributes\EnumColor(success: ['active'], danger: ['banned'])]
enum CastUserStatus: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
    case BANNED = 'banned';
}

enum CastPriority: int
{
    use HasEnumMetadata;

    case LOW = 0;
    case HIGH = 1;
}

enum CastEmptyStringEnum: string
{
    use HasEnumMetadata;

    case NONE = '';
}

enum CastNumericEnum: int
{
    use HasEnumMetadata;

    case ZERO = 0;
    case NEGATIVE = -1;
}

// ── Tests ─────────────────────────────────────────────────────

describe('EnumCast edge cases', function () {
    describe('get() — type handling', function () {
        it('returns null for null value', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            $result = $cast->get($model, 'status', null, []);
            expect($result)->toBeNull();
        });

        it('returns null for empty string value on non-empty-string enum', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            // 'not-a-status' doesn't match any case
            $result = $cast->get($model, 'status', 'not-a-status', []);
            expect($result)->toBeNull();
        });

        it('returns enum instance for valid string value', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            $result = $cast->get($model, 'status', 'active', []);
            expect($result)->toBe(CastUserStatus::ACTIVE);
            expect($result->value)->toBe('active');
        });

        it('returns enum instance for valid int value', function () {
            $cast = new EnumCast(CastPriority::class);
            $model = new \stdClass;
            $result = $cast->get($model, 'priority', 1, []);
            expect($result)->toBe(CastPriority::HIGH);
            expect($result->value)->toBe(1);
        });

        it('returns enum instance for zero-backed int value', function () {
            $cast = new EnumCast(CastNumericEnum::class);
            $model = new \stdClass;
            $result = $cast->get($model, 'value', 0, []);
            expect($result)->toBe(CastNumericEnum::ZERO);
        });

        it('returns enum instance for empty-string backed value', function () {
            $cast = new EnumCast(CastEmptyStringEnum::class);
            $model = new \stdClass;
            $result = $cast->get($model, 'status', '', []);
            expect($result)->toBe(CastEmptyStringEnum::NONE);
        });

        it('returns null for invalid int value on string-backed enum', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            // CastUserStatus is string-backed; tryFrom('not-a-value') returns null
            $result = $cast->get($model, 'status', 'nonexistent', []);
            expect($result)->toBeNull();
        });
    });

    describe('set() — validation and casting', function () {
        it('returns backed value for valid enum instance', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            $result = $cast->set($model, 'status', CastUserStatus::ACTIVE, []);
            expect($result)->toBe('active');
        });

        it('returns int backed value for valid enum instance', function () {
            $cast = new EnumCast(CastPriority::class);
            $model = new \stdClass;
            $result = $cast->set($model, 'priority', CastPriority::HIGH, []);
            expect($result)->toBe(1);
        });

        it('returns null for null value', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            $result = $cast->set($model, 'status', null, []);
            expect($result)->toBeNull();
        });

        it('throws InvalidArgumentException for wrong enum class', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            expect(fn () => $cast->set($model, 'status', CastPriority::HIGH, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('throws InvalidArgumentException for invalid raw string value', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            expect(fn () => $cast->set($model, 'status', 'invalid_status', []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('throws InvalidArgumentException for invalid raw int value', function () {
            $cast = new EnumCast(CastPriority::class);
            $model = new \stdClass;
            expect(fn () => $cast->set($model, 'priority', 999, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('passes through valid raw string value', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            $result = $cast->set($model, 'status', 'banned', []);
            expect($result)->toBe('banned');
        });

        it('passes through valid raw int value', function () {
            $cast = new EnumCast(CastPriority::class);
            $model = new \stdClass;
            $result = $cast->set($model, 'priority', 0, []);
            expect($result)->toBe(0);
        });

        it('throws InvalidArgumentException for boolean value', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            expect(fn () => $cast->set($model, 'status', true, []))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('serialize() — JSON serialization', function () {
        it('returns backed value for enum instance', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            $result = $cast->serialize($model, 'status', CastUserStatus::BANNED, []);
            expect($result)->toBe('banned');
        });

        it('returns backed value for int enum instance', function () {
            $cast = new EnumCast(CastPriority::class);
            $model = new \stdClass;
            $result = $cast->serialize($model, 'priority', CastPriority::LOW, []);
            expect($result)->toBe(0);
        });

        it('returns raw string value when passed directly', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            $result = $cast->serialize($model, 'status', 'active', []);
            expect($result)->toBe('active');
        });

        it('returns raw int value when passed directly', function () {
            $cast = new EnumCast(CastPriority::class);
            $model = new \stdClass;
            $result = $cast->serialize($model, 'priority', 1, []);
            expect($result)->toBe(1);
        });

        it('returns null for null value', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            $result = $cast->serialize($model, 'status', null, []);
            expect($result)->toBeNull();
        });

        it('returns null for unsupported type', function () {
            $cast = new EnumCast(CastUserStatus::class);
            $model = new \stdClass;
            $result = $cast->serialize($model, 'status', 42.5, []);
            expect($result)->toBeNull();
        });
    });
});
