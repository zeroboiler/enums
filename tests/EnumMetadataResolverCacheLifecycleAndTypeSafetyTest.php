<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;

// ──────────────────────────────────────────────────────────────
// 1. EnumMetadataResolver — invalidation lifecycle
// ──────────────────────────────────────────────────────────────

describe('EnumMetadataResolver cache invalidation', function () {
    it('invalidates a single class cache', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        // Resolve once to populate cache
        EnumMetadataResolver::resolve(IntBackedPriority::class);
        expect($cache->has(IntBackedPriority::class))->toBeTrue();

        // Invalidate the specific class
        EnumMetadataResolver::invalidate(IntBackedPriority::class);
        expect($cache->has(IntBackedPriority::class))->toBeFalse();

        // Other enums should still be cached (if resolved)
        EnumMetadataResolver::resolve(PureFeatureFlag::class);
        expect($cache->has(PureFeatureFlag::class))->toBeTrue();

        EnumMetadataResolver::invalidate(IntBackedPriority::class);
        // PureFeatureFlag cache should NOT be affected
        expect($cache->has(PureFeatureFlag::class))->toBeTrue();
    });

    it('invalidates all class caches', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        EnumMetadataResolver::resolve(IntBackedPriority::class);
        EnumMetadataResolver::resolve(PureFeatureFlag::class);

        expect($cache->has(IntBackedPriority::class))->toBeTrue();
        expect($cache->has(PureFeatureFlag::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect($cache->has(IntBackedPriority::class))->toBeFalse();
        expect($cache->has(PureFeatureFlag::class))->toBeFalse();
    });

    it('resolve() rebuilds metadata after invalidation', function () {
        EnumMetadataResolver::invalidate(IntBackedPriority::class);

        $meta = EnumMetadataResolver::resolve(IntBackedPriority::class);

        expect($meta)->toBeArray();
        expect($meta)->toHaveKeys(['labels', 'colors', 'icons', 'descriptions']);

        // Verify int-backed key resolution
        expect($meta['labels'])->toHaveKey(1);
        expect($meta['labels'][1])->toBe('Critical Priority');
    });
});

// ──────────────────────────────────────────────────────────────
// 2. Int-backed enum — values() returns int array
// ──────────────────────────────────────────────────────────────

describe('Int-backed enum type safety', function () {
    it('values() returns int values, not strings', function () {
        $values = IntBackedPriority::values();

        expect($values)->not->toBeEmpty();
        foreach ($values as $value) {
            expect($value)->toBeInt();
        }
    });

    it('forSelect() returns int values, not case names', function () {
        $select = IntBackedPriority::forSelect();

        expect($select)->not->toBeEmpty();
        foreach ($select as $option) {
            expect($option['value'])->toBeInt();
        }
    });

    it('forApi() returns int values', function () {
        $api = IntBackedPriority::forApi();

        expect($api)->not->toBeEmpty();
        foreach ($api as $item) {
            expect($item['value'])->toBeInt();
        }
    });

    it('is() comparison works with int-backed enum instances', function () {
        expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::CRITICAL))->toBeTrue();
        expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::HIGH))->toBeFalse();
        expect(IntBackedPriority::CRITICAL->is('CRITICAL'))->toBeTrue();
        expect(IntBackedPriority::CRITICAL->is('HIGH'))->toBeFalse();
    });
});

// ──────────────────────────────────────────────────────────────
// 3. Pure enum — values() returns case name strings
// ──────────────────────────────────────────────────────────────

describe('Pure enum type safety', function () {
    it('values() returns case name strings', function () {
        $values = PureFeatureFlag::values();

        expect($values)->not->toBeEmpty();
        foreach ($values as $value) {
            expect($value)->toBeString();
        }
    });

    it('forSelect() returns case names as values', function () {
        $select = PureFeatureFlag::forSelect();

        expect($select)->not->toBeEmpty();
        expect($select[0]['value'])->toBe('DARK_MODE');
    });

    it('tryFromLabel is case-insensitive', function () {
        $result = PureFeatureFlag::tryFromLabel('beta features');
        expect($result)->not->toBeNull();
        expect($result->name)->toBe('BETA_FEATURES');
    });
});

// ──────────────────────────────────────────────────────────────
// 4. EnumManager delegation
// ──────────────────────────────────────────────────────────────

describe('EnumManager delegation contract', function () {
    it('forSelect delegates to trait method', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $result = $manager->forSelect(IntBackedPriority::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        expect($result[0])->toHaveKeys(['value', 'label']);
    });

    it('tryFromName delegates correctly', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $result = $manager->tryFromName(IntBackedPriority::class, 'CRITICAL');

        expect($result)->not->toBeNull();
        expect($result->name)->toBe('CRITICAL');
    });

    it('tryFromName returns null for non-existent case', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $result = $manager->tryFromName(IntBackedPriority::class, 'NONEXISTENT');

        expect($result)->toBeNull();
    });

    it('hasCase returns correct boolean', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        expect($manager->hasCase(IntBackedPriority::class, 'LOW'))->toBeTrue();
        expect($manager->hasCase(IntBackedPriority::class, 'GHOST'))->toBeFalse();
    });
});

// ──────────────────────────────────────────────────────────────
// 5. EnumRule — type mismatch detection
// ──────────────────────────────────────────────────────────────

describe('EnumRule type mismatch detection', function () {
    it('rejects string value for int-backed enum', function () {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failed = false;

        $rule->validate('priority', 'not-an-int', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('accepts correct int value for int-backed enum', function () {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failed = false;

        $rule->validate('priority', 1, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });
});

// ──────────────────────────────────────────────────────────────
// 6. EnumCache TTL behaviour
// ──────────────────────────────────────────────────────────────

describe('EnumCache TTL behavior', function () {
    it('TTL of 0 means no caching', function () {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(0);

        $cache->set(IntBackedPriority::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(IntBackedPriority::class))->toBeFalse();
    });

    it('setTtl normalizes negative values to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('resetInstance clears singleton', function () {
        EnumCache::resetInstance();

        // After reset, getInstance() should create a fresh instance
        $cache = EnumCache::getInstance();
        expect($cache)->not->toBeNull();
    });
});

// ──────────────────────────────────────────────────────────────
// 7. InvalidEnumException factory methods
// ──────────────────────────────────────────────────────────────

describe('InvalidEnumException factory methods', function () {
    it('forName includes class and name in message', function () {
        $exception = InvalidEnumException::forName('App\\Enums\\Status', 'INVALID');

        expect($exception->getMessage())->toContain('INVALID');
        expect($exception->getMessage())->toContain('App\\Enums\\Status');
    });

    it('value includes display representation', function () {
        $exception = InvalidEnumException::value('App\\Enums\\Status', 42);

        expect($exception->getMessage())->toContain('42');
    });

    it('value handles null', function () {
        $exception = InvalidEnumException::value('App\\Enums\\Status', null);

        expect($exception->getMessage())->toContain('null');
    });

    it('__toString includes class name', function () {
        $exception = InvalidEnumException::forName('App\\Enums\\Status', 'X');

        $string = (string) $exception;
        expect($string)->toContain('InvalidEnumException');
    });
});
