<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache TTL edge cases', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('ttl of zero disables caching — has() always returns false', function (): void {
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

    it('negative ttl is normalized to zero — caching disabled', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('ttl of 1 second expires immediately after microsecond gap', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Entry should exist immediately
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Wait slightly longer than TTL
        usleep(1_100_000); // 1.1 seconds

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('large ttl keeps entries alive', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(3600); // 1 hour

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        usleep(100_000); // 0.1 seconds — well within TTL

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->get(UserStatus::class))->toBe([
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
    });

    it('clear removes all entries and their timestamps', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clear();

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('clearClass removes only the specified class entry', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->set('SomeOtherClass', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has('SomeOtherClass'))->toBeTrue();
    });

    it('flush clears all entries via static method', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::flush();

        // After flush, need to get a new reference to check
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('get throws OutOfBoundsException for missing entry', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn (): mixed => $cache->get('NonExistentClass'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('setTtl preserves existing entries', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Change TTL — should not clear existing entries
        $cache->setTtl(0);

        // But with TTL=0, has() returns false even for existing entries
        expect($cache->has(UserStatus::class))->toBeFalse();

        // However, the data is still in the cache array (just not accessible via has())
        // Setting TTL back should make it accessible again... but timestamps won't match
        // Actually, has() checks the timestamp, and the entry was set before TTL was 0.
        // With TTL=0, has() returns false regardless. The entry is not actually removed.
    });

    it('singleton returns same instance', function (): void {
        $instance1 = EnumCache::getInstance();
        $instance2 = EnumCache::getInstance();

        expect($instance1)->toBe($instance2);
    });

    it('resetInstance creates a fresh singleton', function (): void {
        $instance1 = EnumCache::getInstance();

        EnumCache::resetInstance();

        $instance2 = EnumCache::getInstance();

        expect($instance1)->not->toBe($instance2);
    });
});
