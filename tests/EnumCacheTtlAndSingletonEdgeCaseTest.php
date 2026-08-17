<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;

/**
 * EnumCache TTL and singleton edge case tests.
 *
 * Covers TTL boundary behavior, singleton lifecycle, clone/serialize prevention,
 * and cache class-level clearing.
 */
describe('EnumCache TTL and Singleton Edge Cases', function (): void {
    beforeEach(function (): void {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('returns the same singleton instance on repeated calls', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('creates fresh singleton after resetInstance()', function (): void {
        $first = EnumCache::getInstance();
        $first->set('TestEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        EnumCache::resetInstance();

        $second = EnumCache::getInstance();

        // New instance should have empty cache
        expect($second->has('TestEnum'))->toBeFalse();
    });

    it('throws on clone attempt', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn () => clone $cache)->toThrow(\RuntimeException::class);
    });

    it('throws on serialize attempt', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn () => serialize($cache))->toThrow(\RuntimeException::class);
    });

    it('throws on unserialize attempt', function (): void {
        $cache = EnumCache::getInstance();
        $data = 'O:29:"ZeroBoiler\Enums\EnumCache":0:{}';

        expect(fn () => unserialize($data))->toThrow(\RuntimeException::class);
    });

    it('throws on wakeup attempt', function (): void {
        // __wakeup is also blocked, tested via reflection
        $cache = EnumCache::getInstance();
        $method = new \ReflectionMethod($cache, '__wakeup');

        expect($method->getReturnType()?->getName())->toBe('never');
    });

    it('setTtl clamps negative values to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
    });

    it('setTtl accepts zero (disabled caching)', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        expect($cache->getTtl())->toBe(0);
    });

    it('clearClass removes only the specified class entry', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300); // Enable caching

        $meta = ['labels' => ['a' => 'A'], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('EnumA', $meta);
        $cache->set('EnumB', $meta);

        expect($cache->has('EnumA'))->toBeTrue();
        expect($cache->has('EnumB'))->toBeTrue();

        $cache->clearClass('EnumA');

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeTrue();
    });

    it('clear removes all entries', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $meta = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('EnumX', $meta);
        $cache->set('EnumY', $meta);

        $cache->clear();

        expect($cache->has('EnumX'))->toBeFalse();
        expect($cache->has('EnumY'))->toBeFalse();
    });

    it('flush static method delegates to singleton clear', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $meta = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('EnumZ', $meta);

        EnumCache::flush();

        expect($cache->has('EnumZ'))->toBeFalse();
    });

    it('throws OutOfBoundsException when getting non-existent cache entry', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))->toThrow(\OutOfBoundsException::class);
    });

    it('debugInfo exposes ttl and class count', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(42);

        $meta = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('ClassA', $meta);
        $cache->set('ClassB', $meta);

        $debug = $cache->__debugInfo();

        expect($debug)->toHaveKey('ttl');
        expect($debug)->toHaveKey('cachedClasses');
        expect($debug)->toHaveKey('timestampCount');
        expect($debug['ttl'])->toBe(42);
        expect($debug['cachedClasses'])->toBe(2);
        expect($debug['timestampCount'])->toBe(2);
    });
});
