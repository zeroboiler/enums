<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;

describe('EnumCache resetInstance behavior', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('creates a fresh instance after reset', function (): void {
        $first = EnumCache::getInstance();
        EnumCache::resetInstance();
        $second = EnumCache::getInstance();

        expect($first)->not->toBe($second);
    });

    it('fresh instance has empty cache after reset', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set('TestEnum', [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ]);

        expect($cache->has('TestEnum'))->toBeTrue();

        EnumCache::resetInstance();

        $fresh = EnumCache::getInstance();
        expect($fresh->has('TestEnum'))->toBeFalse();
    });

    it('fresh instance resets TTL to default (300)', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(60);
        expect($cache->getTtl())->toBe(60);

        EnumCache::resetInstance();

        $fresh = EnumCache::getInstance();
        expect($fresh->getTtl())->toBe(300);
    });

    it('flush delegates to singleton clear', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set('EnumA', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('EnumB', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('EnumA'))->toBeTrue();
        expect($cache->has('EnumB'))->toBeTrue();

        EnumCache::flush();

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeFalse();
    });

    it('clearClass removes only the targeted class', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set('KeepMe', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('RemoveMe', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass('RemoveMe');

        expect($cache->has('KeepMe'))->toBeTrue();
        expect($cache->has('RemoveMe'))->toBeFalse();
    });

    it('get throws OutOfBoundsException for missing entry', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn (): mixed => $cache->get('NonExistent'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('TTL of 0 disables caching', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set('TestEnum', [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestEnum'))->toBeFalse();
    });

    it('negative TTL is normalized to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-10);

        expect($cache->getTtl())->toBe(0);
    });

    it('expired entries are auto-evicted on has() check', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);
        $cache->set('ShortLived', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('ShortLived'))->toBeTrue();

        // Sleep briefly to let TTL expire
        sleep(2);

        expect($cache->has('ShortLived'))->toBeFalse();
    });
});
