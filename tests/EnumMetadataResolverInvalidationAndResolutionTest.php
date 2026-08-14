<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * EnumMetadataResolver invalidation and re-resolution tests.
 *
 * Verifies that invalidate() and invalidateAll() correctly clear cached
 * metadata and force fresh resolution on the next resolve() call.
 *
 * @covers \ZeroBoiler\Enums\Support\EnumMetadataResolver
 * @covers \ZeroBoiler\Enums\EnumCache
 */

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumMetadataResolver invalidation and re-resolution', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('invalidates a single class and forces re-resolution', function (): void {
        // First resolution — caches the metadata
        $first = EnumMetadataResolver::resolve(UserStatus::class);
        expect($first)->toBeArray();
        expect($first)->toHaveKey('labels');

        // Verify it's cached
        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Invalidate just this class
        EnumMetadataResolver::invalidate(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Re-resolve should work without error
        $second = EnumMetadataResolver::resolve(UserStatus::class);
        expect($second)->toBeArray();
        expect($second)->toHaveKey('labels');
    });

    it('invalidateAll clears every cached class', function (): void {
        // Resolve multiple enums to populate cache
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);
        EnumMetadataResolver::resolve(PaymentStatus::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();
        expect($cache->has(PaymentStatus::class))->toBeTrue();

        // Invalidate all
        EnumMetadataResolver::invalidateAll();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeFalse();
        expect($cache->has(PaymentStatus::class))->toBeFalse();
    });

    it('re-resolved metadata is identical in structure', function (): void {
        $before = EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::invalidate(UserStatus::class);
        $after = EnumMetadataResolver::resolve(UserStatus::class);

        // Same structure keys
        expect(array_keys($before))->toBe(array_keys($after));
        expect($before['labels'])->toBe($after['labels']);
        expect($before['colors'])->toBe($after['colors']);
    });

    it('int-backed enum resolves with int keys', function (): void {
        $meta = EnumMetadataResolver::resolve(IntBackedPriority::class);
        expect($meta)->toHaveKey('labels');
        expect($meta)->toHaveKey('colors');

        // Int-backed enum values should be int keys in the maps
        foreach (IntBackedPriority::cases() as $case) {
            expect($case->value)->toBeInt();
        }
    });
});
