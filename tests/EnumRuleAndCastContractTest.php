<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Contracts\Validation\ValidationRule;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule contract compliance', function () {
    it('implements ValidationRule interface', function () {
        expect(EnumRule::for(UserStatus::class))->toBeInstanceOf(ValidationRule::class);
    });

    it('is a readonly class', function () {
        $ref = new \ReflectionClass(EnumRule::class);
        expect($ref->isReadOnly())->toBeTrue();
        expect($ref->isFinal())->toBeTrue();
    });

    it('creates a new instance with nullable()', function () {
        $rule = EnumRule::for(UserStatus::class);
        $nullableRule = $rule->nullable();

        expect($nullableRule)->toBeInstanceOf(EnumRule::class);
        // Should be a different instance (immutable)
        expect(spl_object_id($nullableRule))->not->toBe(spl_object_id($rule));
    });

    it('rejects invalid string value for string-backed enum', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', 'invalid_value', function (string $message): void {
            expect($message)->toBeString()->toContain('invalid');
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('accepts valid string value for string-backed enum', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', 'active', function (): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects string value for int-backed enum (type mismatch)', function () {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failed = false;

        $rule->validate('priority', '1', function (string $message): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('accepts valid int value for int-backed enum', function () {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failed = false;

        $rule->validate('priority', 1, function (): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('passes null when nullable is enabled', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $failed = false;

        $rule->validate('status', null, function (): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects null when nullable is not enabled', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', null, function (): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('validates pure enum by case name', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);

        // Valid case name should pass
        $failed = false;
        $rule->validate('feature', 'DARK_MODE', function (): void {
            $failed = true;
        });
        expect($failed)->toBeFalse();

        // Invalid case name should fail
        $failed = false;
        $rule->validate('feature', 'INVALID', function (): void {
            $failed = true;
        });
        expect($failed)->toBeTrue();

        // Non-string value should fail for pure enum
        $failed = false;
        $rule->validate('feature', 123, function (): void {
            $failed = true;
        });
        expect($failed)->toBeTrue();
    });

    it('rejects int value for string-backed enum (type mismatch)', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', 42, function (string $message): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('EnumCast contract compliance', function () {
    it('is a final class', function () {
        $ref = new \ReflectionClass(EnumCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('constructs with enum class name', function () {
        $cast = new EnumCast(UserStatus::class);
        expect($cast)->toBeInstanceOf(EnumCast::class);
    });

    it('returns null for null value in get()', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('returns enum instance for valid value in get()', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'status', 'active', []);
        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('returns null for invalid value in get() (tryFrom fallback)', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'status', 'nonexistent', []);
        expect($result)->toBeNull();
    });

    it('returns null for null value in set()', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->set($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('returns backed value for enum instance in set()', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->set($model, 'status', UserStatus::BANNED, []);
        expect($result)->toBe('banned');
    });

    it('serializes enum to backed value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', UserStatus::ACTIVE, []);
        expect($result)->toBe('active');
    });

    it('serialize passes through int/string values', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        expect($cast->serialize($model, 'status', 'active', []))->toBe('active');
    });

    it('serialize returns null for non-enum non-scalar values', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        expect($cast->serialize($model, 'status', [], []))->toBeNull();
    });
});

describe('InvalidEnumException contract', function () {
    it('has value() and forName() named constructors', function () {
        $e = InvalidEnumException::value(UserStatus::class, 'invalid');
        expect($e)->toBeInstanceOf(InvalidEnumException::class);
        expect($e->getMessage())->toContain('invalid');
        expect($e->getMessage())->toContain(UserStatus::class);

        $e2 = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN');
        expect($e2)->toBeInstanceOf(InvalidEnumException::class);
        expect($e2->getMessage())->toContain('UNKNOWN');
    });

    it('__toString returns class name and message', function () {
        $e = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN');
        $str = (string) $e;
        expect($str)->toContain(InvalidEnumException::class);
        expect($str)->toContain('UNKNOWN');
    });

    it('value() displays null correctly', function () {
        $e = InvalidEnumException::value(UserStatus::class, null);
        expect($e->getMessage())->toContain('null');
    });

    it('value() displays int value correctly', function () {
        $e = InvalidEnumException::value(IntBackedPriority::class, 99);
        expect($e->getMessage())->toContain('99');
    });

    it('is a final class', function () {
        $ref = new \ReflectionClass(InvalidEnumException::class);
        expect($ref->isFinal())->toBeTrue();
    });
});
