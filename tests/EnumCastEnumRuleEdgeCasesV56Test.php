<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\NumericStatusCode;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Rules\EnumRule;

/*
 * Edge-case tests for EnumCast and EnumRule — real-world production scenarios.
 *
 * Covers: zero values, numeric strings, null handling, wrong enum type rejection,
 * nullable rule behavior, and boundary conditions that arise in database
 * round-trips and form validation.
 */

describe('EnumCast edge cases — production scenarios', function (): void {
    it('returns null for null database value on string-backed enum', function (): void {
        $cast = EnumCast::of(UserStatus::class);
        $result = $cast->get(new stdClass, 'status', null, []);

        expect($result)->toBeNull();
    });

    it('returns null for null database value on int-backed enum', function (): void {
        $cast = EnumCast::of(NumericStatusCode::class);
        $result = $cast->get(new stdClass, 'code', null, []);

        expect($result)->toBeNull();
    });

    it('casts string-backed value correctly', function (): void {
        $cast = EnumCast::of(OrderStatus::class);
        $result = $cast->get(new stdClass, 'status', 'shipped', []);

        expect($result)->toBeInstanceOf(OrderStatus::class);
        expect($result->value)->toBe('shipped');
    });

    it('casts int-backed value correctly', function (): void {
        $cast = EnumCast::of(NumericStatusCode::class);
        $result = $cast->get(new stdClass, 'code', 200, []);

        expect($result)->toBeInstanceOf(NumericStatusCode::class);
        expect($result->value)->toBe(200);
    });

    it('returns null for invalid string-backed value (silent null)', function (): void {
        $cast = EnumCast::of(OrderStatus::class);
        $result = $cast->get(new stdClass, 'status', 'nonexistent', []);

        expect($result)->toBeNull();
    });

    it('returns null for invalid int-backed value (silent null)', function (): void {
        $cast = EnumCast::of(NumericStatusCode::class);
        $result = $cast->get(new stdClass, 'code', 9999, []);

        expect($result)->toBeNull();
    });

    it('handles zero value correctly on int-backed enum', function (): void {
        // IntBackedPriority has LOW = 0
        $cast = EnumCast::of(IntBackedPriority::class);
        $result = $cast->get(new stdClass, 'priority', 0, []);

        expect($result)->toBeInstanceOf(IntBackedPriority::class);
        expect($result->value)->toBe(0);
        expect($result->name)->toBe('LOW');
    });

    it('serializes enum instance to backed value', function (): void {
        $cast = EnumCast::of(OrderStatus::class);
        $result = $cast->serialize(new stdClass, 'status', OrderStatus::SHIPPED, []);

        expect($result)->toBe('shipped');
    });

    it('serializes int-backed enum to int value', function (): void {
        $cast = EnumCast::of(NumericStatusCode::class);
        $result = $cast->serialize(new stdClass, 'code', NumericStatusCode::CREATED, []);

        expect($result)->toBe(201);
    });

    it('serializes raw string value as passthrough', function (): void {
        $cast = EnumCast::of(OrderStatus::class);
        $result = $cast->serialize(new stdClass, 'status', 'shipped', []);

        expect($result)->toBe('shipped');
    });

    it('serializes raw int value as passthrough', function (): void {
        $cast = EnumCast::of(NumericStatusCode::class);
        $result = $cast->serialize(new stdClass, 'code', 200, []);

        expect($result)->toBe(200);
    });

    it('serializes null to null', function (): void {
        $cast = EnumCast::of(OrderStatus::class);
        $result = $cast->serialize(new stdClass, 'status', null, []);

        expect($result)->toBeNull();
    });

    it('set() throws on wrong enum type', function (): void {
        $cast = EnumCast::of(OrderStatus::class);

        expect(fn () => $cast->set(new stdClass, 'status', UserStatus::ACTIVE, []))
            ->toThrow(InvalidArgumentException::class);
    });

    it('set() throws on invalid raw string value', function (): void {
        $cast = EnumCast::of(OrderStatus::class);

        expect(fn () => $cast->set(new stdClass, 'status', 'invalid_status', []))
            ->toThrow(InvalidArgumentException::class);
    });

    it('set() throws on invalid raw int value', function (): void {
        $cast = EnumCast::of(NumericStatusCode::class);

        expect(fn () => $cast->set(new stdClass, 'code', 9999, []))
            ->toThrow(InvalidArgumentException::class);
    });

    it('set() returns null for null value', function (): void {
        $cast = EnumCast::of(OrderStatus::class);
        $result = $cast->set(new stdClass, 'status', null, []);

        expect($result)->toBeNull();
    });

    it('set() returns backed value for valid enum instance', function (): void {
        $cast = EnumCast::of(OrderStatus::class);
        $result = $cast->set(new stdClass, 'status', OrderStatus::PENDING, []);

        expect($result)->toBe('pending');
    });

    it('set() returns raw value for valid string value', function (): void {
        $cast = EnumCast::of(OrderStatus::class);
        $result = $cast->set(new stdClass, 'status', 'shipped', []);

        expect($result)->toBe('shipped');
    });

    it('set() returns raw value for valid int value', function (): void {
        $cast = EnumCast::of(NumericStatusCode::class);
        $result = $cast->set(new stdClass, 'code', 200, []);

        expect($result)->toBe(200);
    });

    it('set() handles zero value correctly', function (): void {
        $cast = EnumCast::of(IntBackedPriority::class);
        $result = $cast->set(new stdClass, 'priority', 0, []);

        expect($result)->toBe(0);
    });

    it('of() creates cast instances independently', function (): void {
        $cast1 = EnumCast::of(OrderStatus::class);
        $cast2 = EnumCast::of(NumericStatusCode::class);

        expect($cast1)->not->toBe($cast2);
    });
});

describe('EnumRule edge cases — production scenarios', function (): void {
    it('passes valid string-backed value', function (): void {
        $rule = EnumRule::for(OrderStatus::class);
        $fail = fn (string $msg): string => throw new \RuntimeException($msg);

        // Should not throw
        $rule->validate('status', 'shipped', $fail);
        expect(true)->toBeTrue(); // reached = no exception
    });

    it('fails invalid string-backed value', function (): void {
        $rule = EnumRule::for(OrderStatus::class);
        $fail = fn (string $msg): string => throw new \RuntimeException($msg);

        expect(fn () => $rule->validate('status', 'nonexistent', $fail))
            ->throw(\RuntimeException::class);
    });

    it('passes valid int-backed value', function (): void {
        $rule = EnumRule::for(NumericStatusCode::class);
        $fail = fn (string $msg): string => throw new \RuntimeException($msg);

        $rule->validate('code', 200, $fail);
        expect(true)->toBeTrue();
    });

    it('fails type mismatch (string for int-backed enum)', function (): void {
        $rule = EnumRule::for(NumericStatusCode::class);
        $fail = fn (string $msg): string => throw new \RuntimeException($msg);

        expect(fn () => $rule->validate('code', '200', $fail))
            ->throw(\RuntimeException::class);
    });

    it('fails type mismatch (int for string-backed enum)', function (): void {
        $rule = EnumRule::for(OrderStatus::class);
        $fail = fn (string $msg): string => throw new \RuntimeException($msg);

        expect(fn () => $rule->validate('status', 0, $fail))
            ->throw(\RuntimeException::class);
    });

    it('passes null for nullable rule', function (): void {
        $rule = EnumRule::for(OrderStatus::class)->nullable();
        $fail = fn (string $msg): string => throw new \RuntimeException($msg);

        $rule->validate('status', null, $fail);
        expect(true)->toBeTrue();
    });

    it('fails null for non-nullable rule', function (): void {
        $rule = EnumRule::for(OrderStatus::class);
        $fail = fn (string $msg): string => throw new \RuntimeException($msg);

        expect(fn () => $rule->validate('status', null, $fail))
            ->throw(\RuntimeException::class);
    });

    it('passes zero value for int-backed enum', function (): void {
        $rule = EnumRule::for(IntBackedPriority::class);
        $fail = fn (string $msg): string => throw new \RuntimeException($msg);

        $rule->validate('priority', 0, $fail);
        expect(true)->toBeTrue();
    });

    it('nullable() returns a new instance', function (): void {
        $rule = EnumRule::for(OrderStatus::class);
        $nullableRule = $rule->nullable();

        expect($nullableRule)->not->toBe($rule);
    });

    it('for() is a named constructor', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        expect($rule)->toBeInstanceOf(EnumRule::class);
    });
});
