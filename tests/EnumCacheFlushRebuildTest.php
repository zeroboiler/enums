<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCacheFlushAndRebuild', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('flushes and rebuilds metadata correctly', function () {
        // First resolve — builds and caches
        $meta1 = OrderStatus::ACTIVE->label();
        expect($meta1)->toBeString()->not->toBeEmpty();

        // Verify cache exists
        $cache = EnumCache::getInstance();
        expect($cache->has(OrderStatus::class))->toBeTrue();

        // Flush
        EnumCache::flush();

        // Cache should be empty
        expect($cache->has(OrderStatus::class))->toBeFalse();

        // Re-resolve — should rebuild identically
        $meta2 = OrderStatus::ACTIVE->label();
        expect($meta2)->toBe($meta1);
    });

    it('flushes a specific class without affecting others', function () {
        // Resolve both enums
        UserStatus::ACTIVE->label();
        Priority::HIGH->label();

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        // Flush only UserStatus
        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();

        // UserStatus should rebuild on next access
        $label = UserStatus::ACTIVE->label();
        expect($label)->toBeString()->not->toBeEmpty();
        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('setTtl(0) disables caching completely', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        // Resolve — with TTL=0, cache should not store
        OrderStatus::ACTIVE->label();
        expect($cache->has(OrderStatus::class))->toBeFalse();

        // Reset TTL for other tests
        $cache->setTtl(300);
    });

    it('TTL expiry causes cache miss', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL

        OrderStatus::ACTIVE->label();
        expect($cache->has(OrderStatus::class))->toBeTrue();

        // Wait for TTL to expire
        sleep(2);

        expect($cache->has(OrderStatus::class))->toBeFalse();

        // Reset TTL
        $cache->setTtl(300);
    });

    it('get() throws OutOfBoundsException for missing class', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('resetInstance destroys singleton state', function () {
        EnumCache::getInstance();
        expect(EnumCache::getInstance())->toBeInstanceOf(EnumCache::class);

        EnumCache::resetInstance();

        // New instance should be created
        $newInstance = EnumCache::getInstance();
        expect($newInstance)->toBeInstanceOf(EnumCache::class);
    });

    it('fromName throws InvalidEnumException for non-existent case', function () {
        expect(fn () => UserStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class, 'Case name [NON_EXISTENT] does not exist on enum');
    });

    it('InvalidEnumException::value creates proper message', function () {
        $exception = InvalidEnumException::value(UserStatus::class, 'invalid_value');
        expect($exception->getMessage())->toContain('invalid_value');
        expect($exception->getMessage())->toContain(UserStatus::class);
    });
});
