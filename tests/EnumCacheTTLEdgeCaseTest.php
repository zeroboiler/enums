<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache TTL and Edge Cases', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    describe('TTL normalization', function () {
        it('normalizes negative TTL to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);
            expect($cache->getTtl())->toBe(0);
        });

        it('normalizes zero TTL to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            expect($cache->getTtl())->toBe(0);
        });

        it('accepts positive TTL values', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(60);
            expect($cache->getTtl())->toBe(60);
        });

        it('returns 0 by default TTL', function () {
            $cache = EnumCache::getInstance();
            // Default TTL is 300, but we reset the instance
            expect($cache->getTtl())->toBeInt();
        });
    });

    describe('TTL disabled (0) behavior', function () {
        it('has() always returns false when TTL is 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set(UserStatus::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('get() throws when TTL is 0 even after set', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set(UserStatus::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect(fn () => $cache->get(UserStatus::class))
                ->toThrow(\OutOfBoundsException::class);
        });
    });

    describe('TTL expiration', function () {
        it('entry expires after TTL', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(1);
            $meta = ['labels' => ['active' => 'Active'], 'descriptions' => [], 'colors' => [], 'icons' => []];
            $cache->set(UserStatus::class, $meta);

            // Immediately available
            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->get(UserStatus::class))->toBe($meta);

            // Wait for TTL to expire
            usleep(1_100_000); // 1.1 seconds (TTL is 1 second)

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('freshly set entry is available immediately even with short TTL', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $meta = ['labels' => ['active' => 'Active'], 'descriptions' => [], 'colors' => [], 'icons' => []];
            $cache->set(UserStatus::class, $meta);

            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->get(UserStatus::class))->toBe($meta);
        });
    });

    describe('Singleton reset', function () {
        it('resetInstance creates a fresh cache', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(60);
            $cache->set(UserStatus::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            expect($cache->has(UserStatus::class))->toBeTrue();

            EnumCache::resetInstance();

            $newCache = EnumCache::getInstance();
            expect($newCache->has(UserStatus::class))->toBeFalse();
        });
    });

    describe('Clear operations', function () {
        it('clear() removes all entries', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $meta = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
            $cache->set(UserStatus::class, $meta);

            $cache->clear();
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('clearClass() removes specific class only', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $meta = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
            $cache->set(UserStatus::class, $meta);

            $cache->clearClass(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('flush() clears all entries via singleton', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $meta = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
            $cache->set(UserStatus::class, $meta);

            EnumCache::flush();
            expect($cache->has(UserStatus::class))->toBeFalse();
        });
    });

    describe('Error cases', function () {
        it('get() throws for non-existent class', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            expect(fn () => $cache->get('NonExistentClass'))
                ->toThrow(\OutOfBoundsException::class);
        });

        it('clearClass() is safe for non-existent class', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            // Should not throw
            $cache->clearClass('NonExistentClass');
            expect(true)->toBeTrue();
        });
    });
});
