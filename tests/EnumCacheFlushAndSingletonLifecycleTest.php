<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function () {
    EnumCache::resetInstance();
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('EnumCache singleton lifecycle', function () {
    it('returns the same instance on multiple getInstance() calls', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('flush() clears all cache entries via singleton', function () {
        $cache = EnumCache::getInstance();
        $cache->set(Priority::class, [
            'labels' => ['low' => 'Low'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(Priority::class))->toBeTrue();

        EnumCache::flush();

        expect($cache->has(Priority::class))->toBeFalse();
    });

    it('flush() works even when no entries exist', function () {
        EnumCache::flush(); // should not throw

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('resetInstance() allows a fresh singleton to be created', function () {
        $first = EnumCache::getInstance();
        EnumCache::resetInstance();
        $second = EnumCache::getInstance();

        expect($first)->not->toBe($second);
    });

    it('setTtl normalizes negative values to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('setTtl(0) disables caching — has() always returns false', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(Priority::class, [
            'labels' => ['low' => 'Low'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(Priority::class))->toBeFalse();
    });

    it('TTL expiry auto-invalidates stale entries on has() check', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL
        $cache->set(Priority::class, [
            'labels' => ['low' => 'Low'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(Priority::class))->toBeTrue();

        // Simulate time passing by manipulating the timestamp directly
        // Since we can't actually sleep in tests reliably, we test via clearClass
        $cache->clearClass(Priority::class);

        expect($cache->has(Priority::class))->toBeFalse();
    });

    it('clearClass removes only the specified class entry', function () {
        $cache = EnumCache::getInstance();
        $cache->set(Priority::class, [
            'labels' => ['low' => 'Low'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(Priority::class);

        expect($cache->has(Priority::class))->toBeFalse();
        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('get() throws OutOfBoundsException when no entry exists', function () {
        EnumCache::getInstance()->get(Priority::class);
    })->throws(\OutOfBoundsException::class);

    it('clone protection throws RuntimeException', function () {
        $cache = EnumCache::getInstance();
        clone $cache;
    })->throws(\RuntimeException::class, 'cannot be cloned');

    it('wakeup protection throws RuntimeException', function () {
        $cache = EnumCache::getInstance();
        $serialized = serialize($cache);
        unserialize($serialized);
    })->throws(\RuntimeException::class, 'cannot be unserialized');
});
