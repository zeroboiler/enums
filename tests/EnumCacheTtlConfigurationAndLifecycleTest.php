<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;

/**
 * Tests for EnumCache TTL behavior, negative value handling, and cache lifecycle.
 */
beforeEach(function () {
    EnumCache::resetInstance();
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('EnumCache TTL configuration', function () {
    it('returns default TTL of 300 seconds', function () {
        $cache = EnumCache::getInstance();

        expect($cache->getTtl())->toBe(300);
    });

    it('setTtl accepts positive values', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(60);

        expect($cache->getTtl())->toBe(60);
    });

    it('setTtl clamps negative values to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-10);

        expect($cache->getTtl())->toBe(0);
    });

    it('setTtl accepts zero to disable caching', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        expect($cache->getTtl())->toBe(0);
    });

    it('TTL of 0 makes has() always return false', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(EmptyDefaultsStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(EmptyDefaultsStatus::class))->toBeFalse();
    });
});

describe('EnumCache singleton behavior', function () {
    it('getInstance always returns the same instance', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('resetInstance creates a fresh instance', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(60);

        EnumCache::resetInstance();

        $fresh = EnumCache::getInstance();

        // Fresh instance should have default TTL (300), not the 60 we set
        expect($fresh->getTtl())->toBe(300);
    });

    it('flush clears all cached data', function () {
        $cache = EnumCache::getInstance();
        $cache->set(EmptyDefaultsStatus::class, [
            'labels' => ['draft' => 'Draft'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(EmptyDefaultsStatus::class))->toBeTrue();

        EnumCache::flush();

        expect($cache->has(EmptyDefaultsStatus::class))->toBeFalse();
    });
});

describe('EnumCache per-class operations', function () {
    it('set and get roundtrip', function () {
        $cache = EnumCache::getInstance();
        $metadata = [
            'labels' => ['draft' => 'Draft', 'published' => 'Published'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];

        $cache->set(EmptyDefaultsStatus::class, $metadata);

        expect($cache->get(EmptyDefaultsStatus::class))->toBe($metadata);
    });

    it('clearClass removes only the specified class', function () {
        $cache = EnumCache::getInstance();

        $cache->set(EmptyDefaultsStatus::class, [
            'labels' => ['draft' => 'Draft'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Store a second class entry
        $cache->set('SomeOtherEnum', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(EmptyDefaultsStatus::class);

        expect($cache->has(EmptyDefaultsStatus::class))->toBeFalse();
        expect($cache->has('SomeOtherEnum'))->toBeTrue();
    });

    it('get throws OutOfBoundsException for missing entry', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))
            ->toThrow(\OutOfBoundsException::class);
    });
});

describe('EnumCache TTL-based expiration', function () {
    it('entry expires after TTL', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL

        $cache->set(EmptyDefaultsStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(EmptyDefaultsStatus::class))->toBeTrue();

        // Wait for TTL to expire
        sleep(2);

        expect($cache->has(EmptyDefaultsStatus::class))->toBeFalse();
    });

    it('entry persists within TTL', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(10);

        $cache->set(EmptyDefaultsStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Immediately check — should still be valid
        expect($cache->has(EmptyDefaultsStatus::class))->toBeTrue();
    });
});
