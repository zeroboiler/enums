<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * EnumCache edge-case tests — TTL boundary, negative TTL, concurrent invalidation.
 *
 * @covers \ZeroBoiler\Enums\EnumCache
 * @covers \ZeroBoiler\Enums\Support\EnumMetadataResolver
 */

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;

describe('EnumCache TTL edge cases', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('disables caching when TTL is set to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        // Set a value
        $cache->set(PaymentStatus::class, [
            'labels' => ['approved' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // TTL=0 means has() always returns false
        expect($cache->has(PaymentStatus::class))->toBeFalse();

        // get() throws because has() was false
        expect(fn (): mixed => $cache->get(PaymentStatus::class))
            ->throws(\OutOfBoundsException::class);
    });

    it('normalizes negative TTL to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
        expect($cache->has(PaymentStatus::class))->toBeFalse();
    });

    it('expires entries after TTL elapses', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);

        $metadata = [
            'labels' => ['approved' => 'Test Label'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];
        $cache->set(PaymentStatus::class, $metadata);

        // Immediately: should be valid
        expect($cache->has(PaymentStatus::class))->toBeTrue();
        expect($cache->get(PaymentStatus::class))->toBe($metadata);

        // Wait for TTL to expire
        sleep(2);

        expect($cache->has(PaymentStatus::class))->toBeFalse();
    });

    it('clearClass invalidates only the targeted class', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(PaymentStatus::class, [
            'labels' => ['approved' => 'Payment'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(\ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority::class, [
            'labels' => [1 => 'Critical'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Both exist
        expect($cache->has(PaymentStatus::class))->toBeTrue();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority::class))->toBeTrue();

        // Clear only one
        $cache->clearClass(PaymentStatus::class);

        expect($cache->has(PaymentStatus::class))->toBeFalse();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority::class))->toBeTrue();
    });

    it('flush() clears all entries via static accessor', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(PaymentStatus::class, [
            'labels' => ['approved' => 'Payment'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::flush();

        expect($cache->has(PaymentStatus::class))->toBeFalse();
    });

    it('singleton returns the same instance', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('resetInstance allows creating a fresh singleton', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(999);

        EnumCache::resetInstance();

        $fresh = EnumCache::getInstance();
        // Fresh instance should have default TTL (300)
        expect($fresh->getTtl())->toBe(300);
        // Not the same instance
        expect($fresh)->not->toBe($cache);
    });
});
