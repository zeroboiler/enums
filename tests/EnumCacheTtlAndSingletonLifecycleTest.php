<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache TTL and singleton lifecycle', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    describe('singleton behavior', function () {
        it('returns the same instance on multiple calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('returns a new instance after reset', function () {
            $a = EnumCache::getInstance();
            EnumCache::resetInstance();
            $b = EnumCache::getInstance();

            expect($a)->not->toBe($b);
        });
    });

    describe('TTL behavior', function () {
        it('setTtl normalizes negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);

            // After setting negative TTL, has() should return false
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('zero TTL disables caching', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('entries are valid immediately after set with positive TTL', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();
        });
    });

    describe('clear operations', function () {
        it('clear() removes all entries', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            $cache->clear();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('clearClass() removes only the specified class', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set('App\\Enums\\FirstEnum', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);
            $cache->set('App\\Enums\\SecondEnum', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            $cache->clearClass('App\\Enums\\FirstEnum');

            expect($cache->has('App\\Enums\\FirstEnum'))->toBeFalse();
            expect($cache->has('App\\Enums\\SecondEnum'))->toBeTrue();
        });

        it('get() throws OutOfBoundsException when entry missing', function () {
            $cache = EnumCache::getInstance();

            expect(fn () => $cache->get('NonExistentEnum'))
                ->toThrow(OutOfBoundsException::class);
        });

        it('flush() delegates to singleton clear()', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });
    });
});
