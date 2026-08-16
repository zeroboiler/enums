<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

describe('EnumCache V35 Production Readiness', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('prevents cloning via public __clone magic method', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn (): mixed => clone $cache)->toThrow(\RuntimeException::class);
    });

    it('clone prevention is enforced via RuntimeException', function (): void {
        $cache = EnumCache::getInstance();

        try {
            clone $cache;
            expect(true)->toBeFalse(); // Should never reach here
        } catch (\RuntimeException $e) {
            expect($e->getMessage())->toBe('EnumCache is a singleton and cannot be cloned.');
        }
    });

    it('clone() is callable (public visibility) but always throws', function (): void {
        $cache = EnumCache::getInstance();

        // Verify the method exists and is publicly callable
        expect(method_exists($cache, '__clone'))->toBeTrue();

        $ref = new \ReflectionMethod($cache, '__clone');
        expect($ref->isPublic())->toBeTrue();
        expect($ref->getReturnType()->getName())->toBe('never');
    });

    it('__debugInfo returns structured cache summary', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set('TestEnumA', [
            'labels' => ['a' => 'Label A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('TestEnumB', [
            'labels' => ['b' => 'Label B'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $debug = $cache->__debugInfo();

        expect($debug)->toBeArray();
        expect($debug)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
        expect($debug['ttl'])->toBeInt();
        expect($debug['cachedClasses'])->toBe(2);
        expect($debug['timestampCount'])->toBe(2);
    });

    it('__debugInfo hides internal cache data structures', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set('SomeEnum', [
            'labels' => ['x' => 'Secret Label'],
            'descriptions' => ['x' => 'Secret Description'],
            'colors' => ['x' => 'red'],
            'icons' => ['x' => 'heroicon-o-star'],
        ]);

        $debug = $cache->__debugInfo();

        // Debug info should NOT contain raw cache data
        expect($debug)->not->toHaveKey('cache');
        expect($debug)->not->toHaveKey('cacheTimestamps');
        expect($debug)->not->toHaveKey('instance');

        // Should only show summary
        expect($debug['cachedClasses'])->toBe(1);
    });

    it('__debugInfo returns zeros for empty cache', function (): void {
        $cache = EnumCache::getInstance();

        $debug = $cache->__debugInfo();

        expect($debug['cachedClasses'])->toBe(0);
        expect($debug['timestampCount'])->toBe(0);
        expect($debug['ttl'])->toBe(300); // default TTL
    });

    it('__debugInfo reflects TTL changes', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(60);

        $debug = $cache->__debugInfo();

        expect($debug['ttl'])->toBe(60);
    });

    it('__debugInfo reflects TTL = 0 (disabled)', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $debug = $cache->__debugInfo();

        expect($debug['ttl'])->toBe(0);
    });

    it('__debugInfo reflects negative TTL clamped to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        $debug = $cache->__debugInfo();

        expect($debug['ttl'])->toBe(0);
    });
});
