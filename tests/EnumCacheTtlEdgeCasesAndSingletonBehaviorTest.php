<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache TTL edge cases and singleton behavior', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    describe('Singleton lifecycle', function (): void {
        it('returns the same instance on repeated getInstance calls', function (): void {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('returns a new instance after resetInstance', function (): void {
            $a = EnumCache::getInstance();
            EnumCache::resetInstance();
            $b = EnumCache::getInstance();

            expect($a)->not->toBe($b);
        });

        it('prevents cloning with RuntimeException', function (): void {
            $cache = EnumCache::getInstance();
            clone $cache;
        })->throws(\RuntimeException::class, 'cannot be cloned');

        it('prevents unserialization with RuntimeException', function (): void {
            $cache = EnumCache::getInstance();
            unserialize(serialize($cache));
        })->throws(\RuntimeException::class, 'cannot be unserialized');
    });

    describe('TTL behavior', function (): void {
        it('disables caching when TTL is set to 0', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->getTtl())->toBe(0);
        });

        it('normalizes negative TTL to 0', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);

            expect($cache->getTtl())->toBe(0);
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('caches entry when TTL is positive', function (): void {
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

        it('expires entry after TTL elapses', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(1);

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();

            // Wait for TTL to expire
            sleep(2);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('throws OutOfBoundsException when getting non-existent entry', function (): void {
            $cache = EnumCache::getInstance();
            $cache->get('NonExistentEnum');
        })->throws(\OutOfBoundsException::class, 'No cached metadata');
    });

    describe('Cache clearing', function (): void {
        it('clears all entries via clear()', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();

            $cache->clear();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('clears a specific class via clearClass()', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->set('SomeOtherEnum', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has('SomeOtherEnum'))->toBeTrue();
        });

        it('flush() is a static convenience for clear()', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });
    });

    describe('EnumMetadataResolver integration with cache', function (): void {
        it('resolve() populates the cache', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            EnumMetadataResolver::resolve(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeTrue();
        });

        it('invalidate() clears a specific class from cache', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            EnumMetadataResolver::resolve(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeTrue();

            EnumMetadataResolver::invalidate(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('invalidateAll() clears all classes from cache', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            EnumMetadataResolver::resolve(UserStatus::class);
            $cache->set('ManualEntry', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has('ManualEntry'))->toBeTrue();

            EnumMetadataResolver::invalidateAll();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has('ManualEntry'))->toBeFalse();
        });
    });
});
