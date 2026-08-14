<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;

/**
 * Contract compliance test — verifies Enum facade and EnumManager method signatures
 * match the trait methods they delegate to, ensuring PHPStan Level 9 type safety.
 */
describe('Enum Facade and Manager — Method Signature Contract', function () {

    describe('EnumManager delegates correctly', function () {
        it('forSelect() returns same result as trait method', function () {
            $expected = OrderStatus::forSelect();
            $actual = app(EnumManager::class)->forSelect(OrderStatus::class);

            expect($actual)->toBe($expected);
        });

        it('forApi() returns same result as trait method', function () {
            $expected = OrderStatus::forApi();
            $actual = app(EnumManager::class)->forApi(OrderStatus::class);

            expect($actual)->toBe($expected);
        });

        it('tryFromLabel() returns same result as trait method', function () {
            $label = OrderStatus::COMPLETED->label();
            $expected = OrderStatus::tryFromLabel($label);
            $actual = app(EnumManager::class)->tryFromLabel(OrderStatus::class, $label);

            expect($actual)->toBe($expected);
            expect($actual)->toBeInstanceOf(OrderStatus::class);
        });

        it('tryFromName() returns same result as trait method', function () {
            $expected = OrderStatus::tryFromName('PENDING');
            $actual = app(EnumManager::class)->tryFromName(OrderStatus::class, 'PENDING');

            expect($actual)->toBe($expected);
            expect($actual->name)->toBe('PENDING');
        });

        it('fromName() returns same result as trait method', function () {
            $expected = OrderStatus::fromName('PENDING');
            $actual = app(EnumManager::class)->fromName(OrderStatus::class, 'PENDING');

            expect($actual)->toBe($expected);
        });

        it('hasCase() returns same result as trait method', function () {
            expect(app(EnumManager::class)->hasCase(OrderStatus::class, 'PENDING'))->toBeTrue();
            expect(app(EnumManager::class)->hasCase(OrderStatus::class, 'UNKNOWN'))->toBeFalse();
        });

        it('values() returns same result as trait method', function () {
            $expected = OrderStatus::values();
            $actual = app(EnumManager::class)->values(OrderStatus::class);

            expect($actual)->toBe($expected);
        });

        it('labels() returns same result as trait method', function () {
            $expected = OrderStatus::labels();
            $actual = app(EnumManager::class)->labels(OrderStatus::class);

            expect($actual)->toBe($expected);
        });
    });

    describe('EnumManager throws on non-enum classes', function () {
        it('throws BadMethodCallException for non-enum class in forSelect', function () {
            expect(fn () => app(EnumManager::class)->forSelect(\stdClass::class))
                ->throws(\BadMethodCallException::class);
        });

        it('throws BadMethodCallException for non-enum class in forApi', function () {
            expect(fn () => app(EnumManager::class)->forApi(\stdClass::class))
                ->throws(\BadMethodCallException::class);
        });
    });

    describe('EnumManager works with int-backed enums', function () {
        it('forSelect returns int values for int-backed enum', function () {
            $result = app(EnumManager::class)->forSelect(IntBackedPriority::class);

            expect($result)->toBeArray();
            expect($result[0])->toHaveKey('value');
            expect($result[0]['value'])->toBeInt();
        });

        it('values returns int values for int-backed enum', function () {
            $values = app(EnumManager::class)->values(IntBackedPriority::class);

            expect($values)->each->toBeInt();
        });
    });

    describe('EnumManager works with pure enums', function () {
        it('forSelect returns case names for pure enum', function () {
            $result = app(EnumManager::class)->forSelect(PureFeatureFlag::class);

            expect($result)->toBeArray();
            expect($result[0])->toHaveKey('value');
            expect($result[0]['value'])->toBeString();
            expect($result[0])->toHaveKey('label');
        });

        it('values returns case names for pure enum', function () {
            $values = app(EnumManager::class)->values(PureFeatureFlag::class);

            expect($values)->each->toBeString();
        });

        it('tryFromName works with pure enum', function () {
            $case = app(EnumManager::class)->tryFromName(PureFeatureFlag::class, 'ENABLED');

            expect($case)->not->toBeNull();
            expect($case->name)->toBe('ENABLED');
        });
    });

    describe('EnumRule — type safety contract', function () {
        it('rejects int value for string-backed enum', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $fail = fn (string $m, string $s = null) => throw new \InvalidArgumentException($m);

            // Int value for string-backed enum should fail
            expect(fn () => $rule->validate('status', 42, $fail))
                ->throws(\InvalidArgumentException::class);
        });

        it('rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $fail = fn (string $m, string $s = null) => throw new \InvalidArgumentException($m);

            // String value for int-backed enum should fail
            expect(fn () => $rule->validate('priority', 'high', $fail))
                ->throws(\InvalidArgumentException::class);
        });

        it('accepts null when nullable', function () {
            $rule = EnumRule::for(OrderStatus::class)->nullable();
            $fail = fn (string $m, string $s = null) => throw new \InvalidArgumentException($m);

            // Should NOT throw — null is allowed when nullable
            $rule->validate('status', null, $fail);
            expect(true)->toBeTrue();
        });

        it('rejects null when not nullable', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $fail = fn (string $m, string $s = null) => throw new \InvalidArgumentException($m);

            expect(fn () => $rule->validate('status', null, $fail))
                ->throws(\InvalidArgumentException::class);
        });

        it('accepts valid string value for string-backed enum', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $fail = fn (string $m, string $s = null) => throw new \InvalidArgumentException($m);

            $rule->validate('status', 'pending', $fail);
            expect(true)->toBeTrue();
        });

        it('accepts valid int value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $fail = fn (string $m, string $s = null) => throw new \InvalidArgumentException($m);

            $rule->validate('priority', 1, $fail);
            expect(true)->toBeTrue();
        });

        it('rejects invalid value', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $fail = fn (string $m, string $s = null) => throw new \InvalidArgumentException($m);

            expect(fn () => $rule->validate('status', 'invalid_value', $fail))
                ->throws(\InvalidArgumentException::class);
        });
    });

    describe('EnumCast — type safety contract', function () {
        it('get() returns null for null value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(OrderStatus::class);
            $model = new \stdClass();
            $result = $cast->get($model, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('get() returns enum instance for valid value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(OrderStatus::class);
            $model = new \stdClass();
            $result = $cast->get($model, 'status', 'pending', []);

            expect($result)->toBeInstanceOf(OrderStatus::class);
            expect($result->name)->toBe('PENDING');
        });

        it('get() returns null for invalid value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(OrderStatus::class);
            $model = new \stdClass();
            $result = $cast->get($model, 'status', 'nonexistent', []);

            expect($result)->toBeNull();
        });

        it('set() returns backed value for enum instance', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(OrderStatus::class);
            $model = new \stdClass();
            $result = $cast->set($model, 'status', OrderStatus::PENDING, []);

            expect($result)->toBe('pending');
        });

        it('set() validates raw value and returns it', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(OrderStatus::class);
            $model = new \stdClass();
            $result = $cast->set($model, 'status', 'pending', []);

            expect($result)->toBe('pending');
        });

        it('set() throws for wrong enum type', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(OrderStatus::class);
            $model = new \stdClass();

            // TicketStatus is a different enum — should throw
            expect(fn () => $cast->set($model, 'status', TicketStatus::OPEN, []))
                ->throws(\InvalidArgumentException::class);
        });

        it('serialize() returns backed value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(OrderStatus::class);
            $model = new \stdClass();
            $result = $cast->serialize($model, 'status', OrderStatus::PENDING, []);

            expect($result)->toBe('pending');
        });

        it('serialize() returns null for null', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(OrderStatus::class);
            $model = new \stdClass();
            $result = $cast->serialize($model, 'status', null, []);

            expect($result)->toBeNull();
        });
    });
});
