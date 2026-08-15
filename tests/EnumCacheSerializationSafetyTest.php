<?php

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;

describe('EnumCache serialization safety', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('blocks __clone() on singleton', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => clone $cache)->toThrow(\RuntimeException::class);
    });

    it('blocks __wakeup() on singleton', function () {
        // Create a serialized string containing an EnumCache instance
        // We can't actually create one normally, so we test the method directly
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->__wakeup())->toThrow(\RuntimeException::class);
    });

    it('blocks __serialize() on singleton', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->__serialize())->toThrow(\RuntimeException::class);
    });

    it('blocks __unserialize() on singleton', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->__unserialize([]))->toThrow(\RuntimeException::class);
    });

    it('serialize() throws via __serialize()', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => serialize($cache))->toThrow(\RuntimeException::class);
    });

    it('resetInstance destroys singleton for fresh creation', function () {
        $cache1 = EnumCache::getInstance();
        $cache1->setTtl(60);

        EnumCache::resetInstance();

        $cache2 = EnumCache::getInstance();
        // Fresh instance should have default TTL (300)
        expect($cache2->getTtl())->toBe(300);
        // Different instance
        expect($cache2)->not->toBe($cache1);
    });

    it('setTtl clamps negative values to 0', function () {
        $cache = EnumCache::getInstance();

        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
    });

    it('flush clears all entries via static accessor', function () {
        $cache = EnumCache::getInstance();
        $cache->set('TestEnum', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestEnum'))->toBeTrue();

        EnumCache::flush();

        expect($cache->has('TestEnum'))->toBeFalse();
    });

    it('clearClass removes specific entry without affecting others', function () {
        $cache = EnumCache::getInstance();
        $metadata = [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];
        $cache->set('EnumA', $metadata);
        $cache->set('EnumB', $metadata);

        $cache->clearClass('EnumA');

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeTrue();
    });
});
