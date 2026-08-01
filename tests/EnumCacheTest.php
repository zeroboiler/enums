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

    it('stores and retrieves metadata', function (): void {
        $cache = EnumCache::getInstance();
        $data = ['labels' => ['x' => 'X'], 'descriptions' => [], 'colors' => [], 'icons' => []];

        $cache->set('TestEnum', $data);

        expect($cache->has('TestEnum'))->toBeTrue();
        expect($cache->get('TestEnum'))->toBe($data);
    });

    it('reports missing entries correctly', function (): void {
        $cache = EnumCache::getInstance();

        expect($cache->has('NonExistent'))->toBeFalse();
    });

    it('clears a specific class entry', function (): void {
        $cache = EnumCache::getInstance();
        $data = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];

        $cache->set('EnumA', $data);
        $cache->set('EnumB', $data);

        $cache->clearClass('EnumA');

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeTrue();
    });

    it('clears all entries via clear()', function (): void {
        $cache = EnumCache::getInstance();
        $data = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];

        $cache->set('EnumA', $data);
        $cache->set('EnumB', $data);

        $cache->clear();

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeFalse();
    });

    it('flushes all entries via static flush()', function (): void {
        $cache = EnumCache::getInstance();
        $data = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];

        $cache->set('EnumC', $data);

        EnumCache::flush();

        expect($cache->has('EnumC'))->toBeFalse();
    });

    it('expires entries after TTL', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $data = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('ExpiringEnum', $data);

        // TTL=0 means entries are immediately stale
        expect($cache->has('ExpiringEnum'))->toBeFalse();
    });

    it('resetInstance creates a fresh singleton', function (): void {
        $first = EnumCache::getInstance();
        $first->set('BeforeReset', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        EnumCache::resetInstance();
        $second = EnumCache::getInstance();

        expect($second)->not->toBe($first);
        expect($second->has('BeforeReset'))->toBeFalse();
    });

    it('setTtl changes the TTL value', function (): void {
        $cache = EnumCache::getInstance();

        $cache->setTtl(600);

        $data = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('LongLived', $data);

        expect($cache->has('LongLived'))->toBeTrue();
    });
});
