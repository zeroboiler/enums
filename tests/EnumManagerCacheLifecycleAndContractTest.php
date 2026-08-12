<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum Cache Reset and Singleton Lifecycle', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('resetInstance creates a fresh singleton with default TTL', function () {
        $cache1 = EnumCache::getInstance();
        $cache1->setTtl(999);

        EnumCache::resetInstance();

        $cache2 = EnumCache::getInstance();
        // New instance should have default TTL (300)
        expect($cache2->getTtl())->toBe(300);
        expect($cache2)->not->toBe($cache1);
    });

    it('resetInstance clears all cached metadata', function () {
        $cache = EnumCache::getInstance();
        $cache->set('SomeEnum::class', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        expect($cache->has('SomeEnum::class'))->toBeTrue();

        EnumCache::resetInstance();

        $newCache = EnumCache::getInstance();
        expect($newCache->has('SomeEnum::class'))->toBeFalse();
    });

    it('flush() delegates to singleton clear()', function () {
        $cache = EnumCache::getInstance();
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        expect($cache->has(UserStatus::class))->toBeTrue();

        EnumCache::flush();

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('setTtl normalizes negative values to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('TTL of 0 causes has() to always return false', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('get() throws OutOfBoundsException when no cache exists', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum::class'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('clearClass only clears the specified class', function () {
        $cache = EnumCache::getInstance();
        $meta = [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];
        $cache->set(UserStatus::class, $meta);
        $cache->set(IntBackedPriority::class, $meta);

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();
    });
});

describe('EnumManager delegation contract', function () {
    it('forSelect delegates to the enum trait method', function () {
        $manager = new EnumManager;
        $result = $manager->forSelect(UserStatus::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        expect($result[0])->toHaveKeys(['value', 'label']);
    });

    it('forApi delegates to the enum trait method', function () {
        $manager = new EnumManager;
        $result = $manager->forApi(UserStatus::class);

        expect($result)->toBeArray();
        expect($result[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('tryFromLabel delegates to the enum trait method', function () {
        $manager = new EnumManager;
        $label = UserStatus::ACTIVE->label();
        $result = $manager->tryFromLabel(UserStatus::class, $label);

        expect($result)->toBeInstanceOf(UserStatus::class);
        expect($result->name)->toBe('ACTIVE');
    });

    it('tryFromLabel returns null for non-existent label', function () {
        $manager = new EnumManager;

        expect($manager->tryFromLabel(UserStatus::class, 'non-existent-label-xyz'))->toBeNull();
    });

    it('forSelect throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        // PureFeatureFlag uses HasEnumMetadata so it should work;
        // but if we pass a non-enum or an enum without the trait, it should throw
        expect(fn () => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('forApi throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forApi(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('tryFromLabel throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->tryFromLabel(\stdClass::class, 'test'))
            ->toThrow(\BadMethodCallException::class);
    });
});

describe('EnumFacade static method resolution', function () {
    it('Enum facade returns correct accessor', function () {
        // Test that the facade is correctly wired
        expect(Enum::getFacadeAccessor())->toBe('zeroboiler.enum');
    });

    it('Enum facade is final', function () {
        $ref = new \ReflectionClass(Enum::class);

        expect($ref->isFinal())->toBeTrue();
    });
});

describe('EnumManager is readonly final', function () {
    it('EnumManager is final and readonly', function () {
        $ref = new \ReflectionClass(EnumManager::class);

        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });
});

describe('InvalidEnumException named constructors', function () {
    it('forName creates exception with correct message', function () {
        $ex = InvalidEnumException::forName(UserStatus::class, 'NON_EXISTENT');

        expect($ex->getMessage())->toContain('NON_EXISTENT');
        expect($ex->getMessage())->toContain(UserStatus::class);
    });

    it('value creates exception with null display', function () {
        $ex = InvalidEnumException::value(UserStatus::class, null);

        expect($ex->getMessage())->toContain('null');
    });

    it('value creates exception with string display', function () {
        $ex = InvalidEnumException::value(UserStatus::class, 'invalid_value');

        expect($ex->getMessage())->toContain('invalid_value');
    });

    it('value creates exception with int display', function () {
        $ex = InvalidEnumException::value(IntBackedPriority::class, 999);

        expect($ex->getMessage())->toContain('999');
    });

    it('__toString returns class name and message', function () {
        $ex = InvalidEnumException::forName(UserStatus::class, 'BAD');

        $str = (string) $ex;
        expect($str)->toContain(InvalidEnumException::class);
        expect($str)->toContain('BAD');
    });
});

describe('Int-backed enum full contract', function () {
    it('values() returns int values', function () {
        $values = IntBackedPriority::values();

        expect($values)->each->toBeInt();
    });

    it('forSelect uses int values', function () {
        $select = IntBackedPriority::forSelect();

        foreach ($select as $option) {
            expect($option['value'])->toBeInt();
            expect($option['label'])->toBeString();
        }
    });

    it('forApi includes int values', function () {
        $api = IntBackedPriority::forApi();

        foreach ($api as $item) {
            expect($item['value'])->toBeInt();
            expect($item['name'])->toBeString();
        }
    });

    it('is() works with int-backed values', function () {
        expect(IntBackedPriority::LOW->is(IntBackedPriority::LOW))->toBeTrue();
        expect(IntBackedPriority::LOW->is('LOW'))->toBeTrue();
        expect(IntBackedPriority::LOW->is('HIGH'))->toBeFalse();
    });

    it('fromName throws for invalid case on int-backed enum', function () {
        expect(fn () => IntBackedPriority::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });
});

describe('Pure enum full contract', function () {
    it('values() returns case names for pure enum', function () {
        $values = PureFeatureFlag::values();
        $expectedNames = array_map(
            fn (\UnitEnum $c): string => $c->name,
            PureFeatureFlag::cases(),
        );

        expect($values)->toBe($expectedNames);
    });

    it('forSelect uses case names as values', function () {
        $select = PureFeatureFlag::forSelect();

        foreach ($select as $option) {
            expect($option['value'])->toBeString();
            expect($option['label'])->toBeString();
        }
    });

    it('hasCase works for pure enum', function () {
        expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::hasCase('NON_EXISTENT'))->toBeFalse();
    });

    it('is() works with pure enum case names', function () {
        expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->is(PureFeatureFlag::DARK_MODE))->toBeTrue();
    });

    it('in() works with pure enum', function () {
        expect(PureFeatureFlag::DARK_MODE->in(['DARK_MODE', 'BETA_FEATURES']))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->in(['BETA_FEATURES']))->toBeFalse();
    });
});

describe('EnumsServiceProvider structure', function () {
    it('is final', function () {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('extends Illuminate ServiceProvider', function () {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

        expect($ref->isSubclassOf(\Illuminate\Support\ServiceProvider::class))->toBeTrue();
    });
});
