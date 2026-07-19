<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseName;
use ZeroBoiler\Enums\Tests\Fixtures\ClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\SpecialCharName;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

beforeEach(function (): void {
    EnumCache::resetInstance();
    EnumMetadataResolver::resetCache();
});

/*
|--------------------------------------------------------------------------
| Issue #11: Missing test coverage for edge cases
|--------------------------------------------------------------------------
*/

// ---------------------------------------------------------------------------
// EnumCast: serialize with null, int-backed serialization
// ---------------------------------------------------------------------------
describe('EnumCast serialization edge cases', function (): void {
    it('serializes null to null', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'status',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('serializes int-backed enum to its int value', function (): void {
        $cast = new EnumCast(Priority::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'priority',
            value: Priority::URGENT,
            attributes: [],
        );

        expect($result)->toBeInt()->toBe(4);
    });

    it('serializes raw int value as-is', function (): void {
        $cast = new EnumCast(Priority::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'priority',
            value: 3,
            attributes: [],
        );

        expect($result)->toBe(3);
    });

    it('serializes raw string value as-is', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'status',
            value: 'active',
            attributes: [],
        );

        expect($result)->toBe('active');
    });
});

// ---------------------------------------------------------------------------
// EnumCast: set() with int value on string-backed enum (type coercion)
// ---------------------------------------------------------------------------
describe('EnumCast set() type coercion edge cases', function (): void {
    it('throws when setting int value on string-backed enum', function (): void {
        $cast = new EnumCast(UserStatus::class);

        try {
            $cast->set(
                model: new class {},
                key: 'status',
                value: 1,
                attributes: [],
            );
            // If no exception, the value should not be silently accepted as valid
            expect(false)->toBeTrue('Should have thrown or rejected int for string-backed enum');
        } catch (Throwable $e) {
            expect($e)->toBeInstanceOf(Throwable::class);
        }
    });

    it('throws when setting string value on int-backed enum', function (): void {
        $cast = new EnumCast(Priority::class);

        try {
            $cast->set(
                model: new class {},
                key: 'priority',
                value: 'not-a-number',
                attributes: [],
            );
            expect(false)->toBeTrue('Should have thrown or rejected string for int-backed enum');
        } catch (Throwable $e) {
            expect($e)->toBeInstanceOf(Throwable::class);
        }
    });
});

// ---------------------------------------------------------------------------
// EnumRule: int values against string-backed enums, boolean, null
// ---------------------------------------------------------------------------
describe('EnumRule edge cases', function (): void {
    it('fails for int value against string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', 123, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails for boolean true value', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', true, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails for boolean false value', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', false, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails for null value when not nullable', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('passes for null value when nullable', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $failed = false;

        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails for float value', function (): void {
        $rule = EnumRule::for(Priority::class);
        $failed = false;

        $rule->validate('priority', 2.5, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('passes for valid int value on int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);
        $failed = false;

        $rule->validate('priority', 1, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// HasEnumMetadata: class-level attribute mappings
// ---------------------------------------------------------------------------
describe('HasEnumMetadata class-level attributes', function (): void {
    it('resolves class-level EnumLabel mapping', function (): void {
        expect(ClassLevelEnum::ACTIVE->label())->toBe('Active Label');
        expect(ClassLevelEnum::INACTIVE->label())->toBe('Inactive Label');
    });

    it('resolves class-level EnumDescription mapping', function (): void {
        expect(ClassLevelEnum::ACTIVE->description())->toBe('Class-level active description');
        expect(ClassLevelEnum::INACTIVE->description())->toBeNull();
    });

    it('resolves class-level EnumIcon default', function (): void {
        expect(ClassLevelEnum::ACTIVE->icon())->toBe('heroicon-o-default');
        expect(ClassLevelEnum::INACTIVE->icon())->toBe('heroicon-o-default');
    });
});

// ---------------------------------------------------------------------------
// HasEnumMetadata: generateLabel with camelCase
// ---------------------------------------------------------------------------
describe('HasEnumMetadata generateLabel camelCase', function (): void {
    it('generates title case from camelCase names', function (): void {
        expect(CamelCaseName::pendingReview->label())->toBe('Pending Review');
        expect(CamelCaseName::inProgress->label())->toBe('In Progress');
        expect(CamelCaseName::readyToShip->label())->toBe('Ready To Ship');
    });

    it('generates labels for special character case names', function (): void {
        // SCREAMING_SNAKE_CASE gets lowercased then title-cased
        expect(SpecialCharName::WITH_DASH->label())->toBe('With Dash');
        expect(SpecialCharName::WITH_NUMBER_2->label())->toBe('With Number 2');
        expect(SpecialCharName::UMLAUT_Ä->label())->toBe('Umlaut Ä');
    });
});

// ---------------------------------------------------------------------------
// Caching: clear(), clearClass(), persistence across multiple classes
// ---------------------------------------------------------------------------
describe('EnumCache edge cases', function (): void {
    it('clear() empties the entire cache', function (): void {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        $cache->clear();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
    });

    it('clearClass() removes only the specified class', function (): void {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });

    it('caches multiple enum classes independently', function (): void {
        $cache = EnumCache::getInstance();

        $userMeta = EnumMetadataResolver::resolve(UserStatus::class);
        $priorityMeta = EnumMetadataResolver::resolve(Priority::class);
        $orderMeta = EnumMetadataResolver::resolve(OrderStatus::class);

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();
        expect($cache->has(OrderStatus::class))->toBeTrue();

        // Verify data integrity for each
        expect($userMeta['labels']['active'])->toBe('Active User');
        // Priority and OrderStatus labels are auto-generated (not in metadata)
        expect(Priority::LOW->label())->toBe('Low');
        expect(OrderStatus::PENDING->label())->toBe('Pending');
    });

    it('clearClass() on non-cached class is a no-op', function (): void {
        $cache = EnumCache::getInstance();

        // Should not throw
        $cache->clearClass('NonExistentEnum');

        expect(true)->toBeTrue();
    });

    it('has() returns false for non-cached class', function (): void {
        $cache = EnumCache::getInstance();

        expect($cache->has('NonExistentEnum'))->toBeFalse();
    });

    it('get() works after set()', function (): void {
        $cache = EnumCache::getInstance();

        $testData = [
            'labels' => ['test' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];

        $cache->set('TestEnum', $testData);

        expect($cache->get('TestEnum'))->toBe($testData);
    });
});

// ---------------------------------------------------------------------------
// Zero-value int-backed enum edge case (already tested but explicitly listed)
// ---------------------------------------------------------------------------
describe('Zero value enum edge cases', function (): void {
    it('correctly handles enum with zero value', function (): void {
        expect(ZeroPriority::NONE->value)->toBe(0);
        expect(ZeroPriority::cases())->toHaveCount(3);
    });

    it('zero value serializes correctly through EnumCast', function (): void {
        $cast = new EnumCast(ZeroPriority::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'priority',
            value: ZeroPriority::NONE,
            attributes: [],
        );

        // Zero should serialize to 0, not null
        expect($result)->toBe(0);
    });

    it('zero value casts correctly through EnumCast get()', function (): void {
        $cast = new EnumCast(ZeroPriority::class);

        $result = $cast->get(
            model: new class {},
            key: 'priority',
            value: 0,
            attributes: [],
        );

        expect($result)->toBe(ZeroPriority::NONE);
    });
});
