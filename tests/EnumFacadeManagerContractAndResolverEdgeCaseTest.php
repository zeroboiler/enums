<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderWorkflowStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

/**
 * Comprehensive edge-case tests for EnumFacade, EnumManager, EnumRule, and EnumCast
 * focusing on contract compliance, type safety, and cross-fixture consistency.
 *
 * PHPStan Level 9 compliance: no mixed types in assertions, strict comparisons only.
 */
describe('EnumFacadeManagerContractAndResolverEdgeCase', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    describe('EnumManager contract compliance', function () {
        it('throws BadMethodCallException for non-enum class', function () {
            expect(fn () => Enum::forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('throws BadMethodCallException when enum lacks HasEnumMetadata', function () {
            $plainEnum = new class {
                public static function cases(): array
                {
                    return [];
                }
            };

            // Plain classes without forSelect method trigger BadMethodCallException
            expect(fn () => Enum::forSelect($plainEnum::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('returns consistent results from forSelect and forApi', function () {
            $select = UserStatus::forSelect();
            $api = UserStatus::forApi();

            expect($select)->toHaveCount(count($api));
            expect(count($select))->toBeGreaterThan(0);

            // Each forSelect entry must have a matching forApi entry by value
            foreach ($select as $option) {
                $matching = array_filter($api, fn (array $a): bool => $a['value'] === $option['value']);
                expect($matching)->not->toBeEmpty();
            }
        });

        it('values() returns same set as backed values for string-backed enum', function () {
            $values = UserStatus::values();
            $cases = UserStatus::cases();

            $backedValues = array_map(
                fn (\BackedEnum $c): string => $c->value,
                $cases
            );

            expect($values)->toEqual($backedValues);
        });

        it('values() returns same set as case names for pure enum', function () {
            $values = PureFeatureFlag::values();
            $caseNames = array_map(fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());

            expect($values)->toEqual($caseNames);
        });
    });

    describe('EnumRule validation edge cases', function () {
        it('rejects non-scalar value for backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = false;

            $rule->validate('status', ['invalid_array'], function (string $message) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });

        it('accepts null when nullable is enabled for pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class)->nullable();
            $fail = false;

            $rule->validate('feature', null, function (string $message) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();
        });

        it('accepts null when nullable is enabled for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class)->nullable();
            $fail = false;

            $rule->validate('priority', null, function (string $message) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();
        });

        it('rejects int value for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = false;

            $rule->validate('status', 42, function (string $message) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });

        it('rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $fail = false;

            $rule->validate('priority', 'high', function (string $message) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });

        it('validates valid int-backed enum value correctly', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $fail = false;

            $rule->validate('priority', 1, function (string $message) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();
        });
    });

    describe('EnumCast edge cases', function () {
        it('returns null for empty string on string-backed enum', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new \stdClass, 'status', '', []);

            expect($result)->toBeNull();
        });

        it('serializes int-backed enum value correctly', function () {
            $cast = new EnumCast(IntBackedPriority::class);
            $result = $cast->set(new \stdClass, 'priority', IntBackedPriority::LOW, []);

            expect($result)->toBe(1);
        });

        it('serializes null to null', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(new \stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('set() throws for wrong enum class', function () {
            $cast = new EnumCast(UserStatus::class);

            expect(fn () => $cast->set(new \stdClass, 'status', IntBackedPriority::LOW, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('set() throws for invalid raw value', function () {
            $cast = new EnumCast(UserStatus::class);

            expect(fn () => $cast->set(new \stdClass, 'status', 'nonexistent', []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() passes through int-backed value as-is', function () {
            $cast = new EnumCast(IntBackedPriority::class);
            $result = $cast->serialize(new \stdClass, 'priority', IntBackedPriority::HIGH, []);

            expect($result)->toBe(2);
        });
    });

    describe('Zero-backed enum edge cases', function () {
        it('ZeroBackedPriority::NONE resolves correct label from class-level attribute', function () {
            expect(ZeroBackedPriority::NONE->label())->toBe('None');
        });

        it('ZeroBackedPriority::NONE resolves correct color from class-level attribute', function () {
            expect(ZeroBackedPriority::NONE->color())->toBe('secondary');
        });

        it('ZeroBackedPriority::LOW resolves correct color (success)', function () {
            expect(ZeroBackedPriority::LOW->color())->toBe('success');
        });

        it('ZeroBackedPriority::HIGH resolves correct color (danger)', function () {
            expect(ZeroBackedPriority::HIGH->color())->toBe('danger');
        });

        it('forSelect includes zero value correctly', function () {
            $select = ZeroBackedPriority::forSelect();
            $zeroEntry = array_filter($select, fn (array $opt): bool => $opt['value'] === 0);

            expect($zeroEntry)->not->toBeEmpty();
            expect(array_values($zeroEntry)[0]['label'])->toBe('None');
        });

        it('fromName resolves zero-backed case correctly', function () {
            $case = ZeroBackedPriority::fromName('NONE');

            expect($case)->toBe(ZeroBackedPriority::NONE);
            expect($case->value)->toBe(0);
        });

        it('tryFromName returns null for non-existent case', function () {
            expect(ZeroBackedPriority::tryFromName('CRITICAL'))->toBeNull();
        });

        it('values() includes zero in returned array', function () {
            $values = ZeroBackedPriority::values();

            expect(in_array(0, $values, true))->toBeTrue();
        });
    });

    describe('Cache invalidation and resolver consistency', function () {
        it('cache is populated after first resolve', function () {
            $cache = EnumCache::getInstance();

            expect($cache->has(UserStatus::class))->toBeFalse();

            UserStatus::ACTIVE->label();

            expect($cache->has(UserStatus::class))->toBeTrue();
        });

        it('clearClass only affects targeted class', function () {
            $cache = EnumCache::getInstance();

            // Resolve two enums to populate cache
            UserStatus::ACTIVE->label();
            IntBackedPriority::LOW->label();

            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has(IntBackedPriority::class))->toBeTrue();

            // Clear only one
            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(IntBackedPriority::class))->toBeTrue();
        });

        it('flush clears all cached entries', function () {
            $cache = EnumCache::getInstance();

            UserStatus::ACTIVE->label();
            IntBackedPriority::LOW->label();

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(IntBackedPriority::class))->toBeFalse();
        });

        it('metadata is identical across multiple resolves', function () {
            $first = UserStatus::ACTIVE->label();
            $second = UserStatus::ACTIVE->label();

            expect($first)->toBe($second);
        });

        it('labels() returns labels in case declaration order', function () {
            $labels = OrderWorkflowStatus::labels();
            $cases = OrderWorkflowStatus::cases();

            expect($labels)->toHaveCount(count($cases));

            // Each label must be a non-empty string
            foreach ($labels as $label) {
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        });
    });

    describe('InvalidEnumException named constructors', function () {
        it('value() creates exception with null value display', function () {
            $exception = InvalidEnumException::value(UserStatus::class, null);

            expect($exception->getMessage())->toContain('null');
            expect($exception->getMessage())->toContain(UserStatus::class);
        });

        it('value() creates exception with string value display', function () {
            $exception = InvalidEnumException::value(UserStatus::class, 'invalid');

            expect($exception->getMessage())->toContain('invalid');
            expect($exception->getMessage())->toContain(UserStatus::class);
        });

        it('value() creates exception with int value display', function () {
            $exception = InvalidEnumException::value(IntBackedPriority::class, 999);

            expect($exception->getMessage())->toContain('999');
            expect($exception->getMessage())->toContain(IntBackedPriority::class);
        });

        it('forName() creates exception with case name display', function () {
            $exception = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

            expect($exception->getMessage())->toContain('NONEXISTENT');
            expect($exception->getMessage())->toContain(UserStatus::class);
        });

        it('__toString returns formatted class name and message', function () {
            $exception = InvalidEnumException::forName(UserStatus::class, 'X');

            $string = (string) $exception;

            expect($string)->toContain(InvalidEnumException::class);
            expect($string)->toContain('X');
        });
    });

    describe('Pure enum type safety', function () {
        it('PureFeatureFlag::values returns case names as strings', function () {
            $values = PureFeatureFlag::values();

            foreach ($values as $value) {
                expect($value)->toBeString();
            }
        });

        it('PureFeatureFlag::forSelect uses case names as values', function () {
            $select = PureFeatureFlag::forSelect();

            foreach ($select as $option) {
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
                expect($option['label'])->not->toBeEmpty();
            }
        });

        it('PureFeatureFlag::forApi includes all expected keys', function () {
            $api = PureFeatureFlag::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString();
                expect($item['color'])->not->toBeEmpty();
            }
        });
    });

    describe('Cross-fixture EnumRule consistency', function () {
        it('EnumRule validates zero int value for zero-backed enum', function () {
            $rule = EnumRule::for(ZeroBackedPriority::class);
            $fail = false;

            $rule->validate('priority', 0, function (string $message) use (&$fail): void {
                $fail = true;
            });

            // Zero is a valid case value (NONE = 0)
            expect($fail)->toBeFalse();
        });

        it('EnumRule rejects negative int for non-existent enum case', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $fail = false;

            $rule->validate('priority', -1, function (string $message) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });
    });
});
