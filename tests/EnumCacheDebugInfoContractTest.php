<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function () {
    EnumCache::resetInstance();
    EnumCache::getInstance()->setTtl(300);
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('EnumCache __debugInfo contract', function () {
    it('returns an array with ttl, cachedClasses, and timestampCount keys', function () {
        $debug = EnumCache::getInstance()->__debugInfo();

        expect($debug)->toBeArray()
            ->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
    });

    it('reports ttl as the configured value', function () {
        EnumCache::getInstance()->setTtl(600);
        $debug = EnumCache::getInstance()->__debugInfo();

        expect($debug['ttl'])->toBe(600);
    });

    it('reports cachedClasses as 0 when empty', function () {
        $debug = EnumCache::getInstance()->__debugInfo();

        expect($debug['cachedClasses'])->toBe(0);
    });

    it('reports timestampCount as 0 when empty', function () {
        $debug = EnumCache::getInstance()->__debugInfo();

        expect($debug['timestampCount'])->toBe(0);
    });

    it('reports correct cachedClasses count after caching one enum', function () {
        EnumCache::getInstance()->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $debug = EnumCache::getInstance()->__debugInfo();

        expect($debug['cachedClasses'])->toBe(1);
        expect($debug['timestampCount'])->toBe(1);
    });

    it('reports correct counts after caching multiple enums', function () {
        EnumCache::getInstance()->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        EnumCache::getInstance()->set('App\\Enums\\FakeEnum', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $debug = EnumCache::getInstance()->__debugInfo();

        expect($debug['cachedClasses'])->toBe(2);
        expect($debug['timestampCount'])->toBe(2);
    });

    it('reports 0 cachedClasses after clear', function () {
        EnumCache::getInstance()->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::getInstance()->clear();

        $debug = EnumCache::getInstance()->__debugInfo();

        expect($debug['cachedClasses'])->toBe(0);
        expect($debug['timestampCount'])->toBe(0);
    });

    it('reports decremented counts after clearClass', function () {
        EnumCache::getInstance()->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        EnumCache::getInstance()->set('App\\Enums\\FakeEnum', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::getInstance()->clearClass(UserStatus::class);

        $debug = EnumCache::getInstance()->__debugInfo();

        expect($debug['cachedClasses'])->toBe(1);
        expect($debug['timestampCount'])->toBe(1);
    });

    it('all values are int type', function () {
        $debug = EnumCache::getInstance()->__debugInfo();

        expect($debug['ttl'])->toBeInt();
        expect($debug['cachedClasses'])->toBeInt();
        expect($debug['timestampCount'])->toBeInt();
    });
});

describe('EnumCache serialization blocking', function () {
    it('throws on __clone', function () {
        expect(fn () => clone EnumCache::getInstance())->toThrow(\RuntimeException::class);
    });

    it('throws on __wakeup', function () {
        $cache = EnumCache::getInstance();
        expect(fn () => $cache->__wakeup())->toThrow(\RuntimeException::class);
    });

    it('throws on __serialize', function () {
        expect(fn () => EnumCache::getInstance()->__serialize())->toThrow(\RuntimeException::class);
    });

    it('throws on __unserialize', function () {
        expect(fn () => EnumCache::getInstance()->__unserialize([]))->toThrow(\RuntimeException::class);
    });
});
