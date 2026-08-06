<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;

describe('EnumCache octane/laravel flush behavior', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
        EnumCache::getInstance()->setTtl(300);
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('persists entries between calls within the same TTL window', function (): void {
        $cache = EnumCache::getInstance();

        $cache->set('TestEnum', [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestEnum'))->toBeTrue();
        expect($cache->get('TestEnum'))->toBe([
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
    });

    it('clears all entries on flush() — simulating octane.terminate', function (): void {
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

        // This is what the ServiceProvider's octane.terminate handler calls
        EnumCache::flush();

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeFalse();
    });

    it('resetInstance() creates a brand new singleton — simulating laravel.flush', function (): void {
        $first = EnumCache::getInstance();
        $first->set('TestEnum', [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($first->has('TestEnum'))->toBeTrue();

        // Reset and verify clean state
        EnumCache::resetInstance();
        $second = EnumCache::getInstance();

        expect($second->has('TestEnum'))->toBeFalse();
    });

    it('clearClass() removes only the specified class entry', function (): void {
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

        $cache->clearClass('EnumA');

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeTrue();
    });

    it('TTL of 0 disables caching — has() always returns false', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set('TestEnum', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestEnum'))->toBeFalse();
    });

    it('expired TTL entries are auto-invalidated on has() check', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);

        $cache->set('TestEnum', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestEnum'))->toBeTrue();

        // Wait for TTL to expire
        usleep(1_100_000); // 1.1 seconds

        expect($cache->has('TestEnum'))->toBeFalse();
    });

    it('negative TTL is normalized to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        $cache->set('TestEnum', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // TTL 0 means caching disabled
        expect($cache->has('TestEnum'))->toBeFalse();
    });
});
