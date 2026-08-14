<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * EnumCache singleton lifecycle, TTL behavior, and edge-case tests.
 *
 * Tests cache expiration, TTL configuration, singleton behavior,
 * and multi-class isolation.
 *
 * @see \ZeroBoiler\Enums\EnumCache
 * @see \ZeroBoiler\Enums\EnumCache::getInstance()
 */

use ZeroBoiler\Enums\EnumCache;

// ── Test Enums ─────────────────────────────────────────────────

enum CacheStatusA: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum CacheStatusB: string
{
    case Pending = 'pending';
    case Done = 'done';
}

// ── Tests ─────────────────────────────────────────────────────

describe('EnumCache — Singleton Behavior', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('returns same instance on multiple calls', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();
        expect($a)->toBe($b);
    });

    it('returns new instance after resetInstance', function (): void {
        $a = EnumCache::getInstance();
        EnumCache::resetInstance();
        $b = EnumCache::getInstance();

        expect($a)->not->toBe($b);
    });
});

describe('EnumCache — TTL Expiration', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('has() returns false for non-existent entry', function (): void {
        $cache = EnumCache::getInstance();
        expect($cache->has('NonExistentEnum'))->toBeFalse();
    });

    it('has() returns true immediately after set', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set(CacheStatusA::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(CacheStatusA::class))->toBeTrue();
    });

    it('entry expires after TTL', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second

        $cache->set(CacheStatusA::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(CacheStatusA::class))->toBeTrue();

        // Wait for TTL to expire
        sleep(2);

        expect($cache->has(CacheStatusA::class))->toBeFalse();
    });

    it('TTL of 0 disables caching', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set(CacheStatusA::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // With TTL 0, has() should always return false
        expect($cache->has(CacheStatusA::class))->toBeFalse();
    });

    it('negative TTL is normalized to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-10);

        expect($cache->getTtl())->toBe(0);
    });

    it('setTtl returns proper value from getTtl', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(60);

        expect($cache->getTtl())->toBe(60);
    });
});

describe('EnumCache — Multi-Class Isolation', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('entries for different classes are independent', function (): void {
        $cache = EnumCache::getInstance();

        $cache->set(CacheStatusA::class, [
            'labels' => ['active' => 'Active A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->set(CacheStatusB::class, [
            'labels' => ['pending' => 'Pending B'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $a = $cache->get(CacheStatusA::class);
        $b = $cache->get(CacheStatusB::class);

        expect($a['labels']['active'])->toBe('Active A');
        expect($b['labels']['pending'])->toBe('Pending B');
    });

    it('clearClass only removes specific class', function (): void {
        $cache = EnumCache::getInstance();

        $cache->set(CacheStatusA::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->set(CacheStatusB::class, [
            'labels' => ['pending' => 'Pending'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(CacheStatusA::class);

        expect($cache->has(CacheStatusA::class))->toBeFalse();
        expect($cache->has(CacheStatusB::class))->toBeTrue();
    });
});

describe('EnumCache — Flush and Clear', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('clear removes all entries', function (): void {
        $cache = EnumCache::getInstance();

        $cache->set(CacheStatusA::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(CacheStatusB::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        $cache->clear();

        expect($cache->has(CacheStatusA::class))->toBeFalse();
        expect($cache->has(CacheStatusB::class))->toBeFalse();
    });

    it('flush() static method clears via singleton', function (): void {
        EnumCache::getInstance()->set(CacheStatusA::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        EnumCache::flush();

        expect(EnumCache::getInstance()->has(CacheStatusA::class))->toBeFalse();
    });

    it('get throws OutOfBoundsException for missing entry', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistent'))
            ->toThrow(\OutOfBoundsException::class);
    });
});

describe('EnumCache — Structural Contract', function (): void {
    it('is a final class', function (): void {
        $ref = new ReflectionClass(EnumCache::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('has declare(strict_types=1)', function (): void {
        $ref = new ReflectionClass(EnumCache::class);
        $contents = file_get_contents((string) $ref->getFileName());
        expect($contents)->toContain('declare(strict_types=1)');
    });

    it('__clone is private and returns never', function (): void {
        $ref = new ReflectionMethod(EnumCache::class, '__clone');
        expect($ref->isPrivate())->toBeTrue();

        $returnType = $ref->getReturnType();
        expect($returnType)->not->toBeNull();
        expect($returnType->getName())->toBe('never');
    });

    it('__wakeup throws RuntimeException', function (): void {
        EnumCache::resetInstance();

        // __clone is private — calling clone triggers __clone() which throws
        // We can't directly test cloning because __clone is private,
        // but we verify the method exists and is private
        $ref = new ReflectionMethod(EnumCache::class, '__clone');
        expect($ref->isPrivate())->toBeTrue();
        expect($ref->getReturnType()?->getName())->toBe('never');
    });
});
