<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;

describe('EnumCache', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    it('returns same singleton instance', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('returns false for uncached enum class', function (): void {
        $cache = EnumCache::getInstance();

        expect($cache->has('NonExistentEnum'))->toBeFalse();
    });

    it('stores and retrieves metadata', function (): void {
        $cache = EnumCache::getInstance();

        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => ['active' => 'Active user'],
            'colors' => ['active' => 'success'],
            'icons' => ['active' => 'check'],
        ];

        $cache->set('TestEnum', $metadata);

        expect($cache->has('TestEnum'))->toBeTrue();
        expect($cache->get('TestEnum'))->toBe($metadata);
    });

    it('clears all cache', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clear();

        expect($cache->has('EnumA'))->toBeFalse();
    });

    it('clears a specific class', function (): void {
        $cache = EnumCache::getInstance();
        $empty = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('EnumA', $empty);
        $cache->set('EnumB', $empty);

        $cache->clearClass('EnumA');

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeTrue();
    });

    it('flushes via static method', function (): void {
        $cache = EnumCache::getInstance();
        $empty = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('FlushEnum', $empty);

        EnumCache::flush();

        expect($cache->has('FlushEnum'))->toBeFalse();
    });

    it('expires entries after TTL', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL

        $empty = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('TtlEnum', $empty);

        // Wait for TTL to expire
        usleep(1100000); // 1.1 seconds

        expect($cache->has('TtlEnum'))->toBeFalse();
    });

    it('respects custom TTL', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(3600);

        $empty = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('LongTtl', $empty);

        expect($cache->has('LongTtl'))->toBeTrue();
    });

    it('creates fresh instance after reset', function (): void {
        $first = EnumCache::getInstance();
        EnumCache::resetInstance();
        $second = EnumCache::getInstance();

        expect($first)->not->toBe($second);
    });
});
