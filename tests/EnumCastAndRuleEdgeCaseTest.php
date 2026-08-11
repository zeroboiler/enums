<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * EnumCast and EnumRule edge-case tests.
 *
 * @covers \ZeroBoiler\Enums\Casts\EnumCast
 * @covers \ZeroBoiler\Enums\Rules\EnumRule
 */

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;

describe('EnumCast edge cases', function (): void {
    it('get() returns null for null value', function (): void {
        $cast = new EnumCast(PaymentStatus::class);
        $model = new class {};
        $result = $cast->get($model, 'status', null, []);

        expect($result)->toBeNull();
    });

    it('get() returns null for non-existent backed value', function (): void {
        $cast = new EnumCast(PaymentStatus::class);
        $model = new class {};
        $result = $cast->get($model, 'status', 'non_existent', []);

        expect($result)->toBeNull();
    });

    it('get() returns enum instance for valid value', function (): void {
        $cast = new EnumCast(PaymentStatus::class);
        $model = new class {};
        $result = $cast->get($model, 'status', 'approved', []);

        expect($result)->toBe(PaymentStatus::APPROVED);
    });

    it('get() works with int-backed enums', function (): void {
        $cast = new EnumCast(IntBackedPriority::class);
        $model = new class {};
        $result = $cast->get($model, 'priority', 1, []);

        expect($result)->toBe(IntBackedPriority::CRITICAL);
    });

    it('get() returns null for string value on int-backed enum', function (): void {
        $cast = new EnumCast(IntBackedPriority::class);
        $model = new class {};
        $result = $cast->get($model, 'priority', '1', []);

        // String '1' is not int, so null
        expect($result)->toBeNull();
    });

    it('set() returns null for null', function (): void {
        $cast = new EnumCast(PaymentStatus::class);
        $model = new class {};
        $result = $cast->set($model, 'status', null, []);

        expect($result)->toBeNull();
    });

    it('set() returns backed value for enum instance', function (): void {
        $cast = new EnumCast(PaymentStatus::class);
        $model = new class {};
        $result = $cast->set($model, 'status', PaymentStatus::APPROVED, []);

        expect($result)->toBe('approved');
    });

    it('set() throws for wrong enum type', function (): void {
        $cast = new EnumCast(PaymentStatus::class);
        $model = new class {};

        expect(fn (): mixed => $cast->set($model, 'status', IntBackedPriority::CRITICAL, []))
            ->throws(\InvalidArgumentException::class);
    });

    it('set() throws for invalid raw value', function (): void {
        $cast = new EnumCast(PaymentStatus::class);
        $model = new class {};

        expect(fn (): mixed => $cast->set($model, 'status', 'invalid_value', []))
            ->throws(\InvalidArgumentException::class);
    });

    it('serialize() returns backed value for enum instance', function (): void {
        $cast = new EnumCast(PaymentStatus::class);
        $model = new class {};
        $result = $cast->serialize($model, 'status', PaymentStatus::REJECTED, []);

        expect($result)->toBe('rejected');
    });

    it('serialize() passes through int/string values', function (): void {
        $cast = new EnumCast(PaymentStatus::class);
        $model = new class {};
        $result = $cast->serialize($model, 'status', 'review', []);

        expect($result)->toBe('review');
    });

    it('serialize() returns null for null', function (): void {
        $cast = new EnumCast(PaymentStatus::class);
        $model = new class {};
        $result = $cast->serialize($model, 'status', null, []);

        expect($result)->toBeNull();
    });
});

describe('EnumRule edge cases', function (): void {
    it('passes for valid string-backed value', function (): void {
        $rule = EnumRule::for(PaymentStatus::class);
        $fail = fn (): never => throw new \LogicException('Should not fail');

        // Should not throw
        $rule->validate('status', 'approved', $fail);
        expect(true)->toBeTrue();
    });

    it('fails for invalid string-backed value', function (): void {
        $rule = EnumRule::for(PaymentStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', 'non_existent', $fail);

        expect($failed)->toBeTrue();
    });

    it('rejects null when not nullable', function (): void {
        $rule = EnumRule::for(PaymentStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', null, $fail);

        expect($failed)->toBeTrue();
    });

    it('allows null when nullable', function (): void {
        $rule = EnumRule::for(PaymentStatus::class)->nullable();
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', null, $fail);

        expect($failed)->toBeFalse();
    });

    it('validates pure enums by case name', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('flag', 'DARK_MODE', $fail);

        expect($failed)->toBeFalse();
    });

    it('rejects invalid pure enum case name', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('flag', 'NON_EXISTENT_FLAG', $fail);

        expect($failed)->toBeTrue();
    });

    it('validates int-backed enums with int type', function (): void {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('priority', 1, $fail);

        expect($failed)->toBeFalse();
    });

    it('rejects int-backed enums with string type', function (): void {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('priority', '1', $fail);

        expect($failed)->toBeTrue();
    });

    it('includes allowed values in error message', function (): void {
        $rule = EnumRule::for(PaymentStatus::class);
        $message = '';
        $fail = function (string $msg) use (&$message): void {
            $message = $msg;
        };

        $rule->validate('status', 'invalid', $fail);

        expect($message)->toContain('approved');
        expect($message)->toContain('rejected');
        expect($message)->toContain('review');
    });
});
