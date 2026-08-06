<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache singleton behaviour', function (): void {
    it('returns same instance on multiple calls', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('setTtl normalizes negative values to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        // No assertion error means it didn't throw; TTL is internally ≥ 0.
        // We cannot read TTL directly (private), so verify via side-effect:
        // With TTL 0, has() always returns false.
        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('clearClass removes only the targeted class', function (): void {
        $cache = EnumCache::getInstance();
        $cache->clear();

        // Resolve both enums to populate cache
        UserStatus::ACTIVE->label();
        Priority::LOW->label();

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        // Clear only UserStatus
        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();

        // Cleanup
        $cache->clear();
    });

    it('flush clears everything via static accessor', function (): void {
        EnumCache::flush();

        UserStatus::ACTIVE->label();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        EnumCache::flush();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('resetInstance allows fresh singleton', function (): void {
        EnumCache::resetInstance();

        $newInstance = EnumCache::getInstance();

        // The new instance should be empty (no previous cache)
        expect($newInstance->has(OrderStatus::class))->toBeFalse();

        // Cleanup for other tests
        EnumCache::resetInstance();
    });

    it('get throws OutOfBoundsException for missing class', function (): void {
        EnumCache::getInstance()->clear();

        expect(fn () => EnumCache::getInstance()->get('NonexistentEnum'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('set and roundtrip metadata', function (): void {
        $cache = EnumCache::getInstance();
        $cache->clear();

        $metadata = [
            'labels' => ['test' => 'Test Label'],
            'descriptions' => [],
            'colors' => ['test' => 'success'],
            'icons' => [],
        ];

        $cache->set('TestClass', $metadata);

        expect($cache->has('TestClass'))->toBeTrue();
        expect($cache->get('TestClass'))->toBe($metadata);

        // Cleanup
        $cache->clear();
    });
});
