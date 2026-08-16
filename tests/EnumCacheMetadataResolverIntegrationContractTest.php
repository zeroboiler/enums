<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache + EnumMetadataResolver integration contract', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('resolves metadata for string-backed enum on first call', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta)->toBeArray();
        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        expect($meta['labels']['active'])->toBe('Active User');
        expect($meta['colors']['active'])->toBe('success');
    });

    it('resolves metadata for int-backed enum on first call', function (): void {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        expect($meta)->toBeArray();
        expect($meta['labels'])->toHaveKey(1);
        expect($meta['labels'][1])->toBe('Low');
    });

    it('resolves metadata for pure enum on first call', function (): void {
        $meta = EnumMetadataResolver::resolve(PureSystemState::class);

        expect($meta)->toBeArray();
        expect($meta['labels'])->toHaveKey('IDLE');
    });

    it('caches metadata and returns same instance on second call', function (): void {
        $cache = EnumCache::getInstance();

        $first = EnumMetadataResolver::resolve(OrderStatus::class);
        expect($cache->has(OrderStatus::class))->toBeTrue();

        $second = EnumMetadataResolver::resolve(OrderStatus::class);
        expect($first)->toBe($second);
    });

    it('caches each enum class independently', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(OrderStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();
    });

    it('invalidates single class cache without affecting others', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(OrderStatus::class);

        EnumMetadataResolver::invalidate(UserStatus::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeTrue();
    });

    it('invalidates all class caches with invalidateAll', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
    });

    it('re-resolves metadata after invalidation', function (): void {
        $first = EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::invalidate(UserStatus::class);
        $second = EnumMetadataResolver::resolve(UserStatus::class);

        // Same structure but fresh resolution
        expect($first)->toEqual($second);
    });

    it('flush resets cache and TTL', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(60);
        EnumMetadataResolver::resolve(UserStatus::class);

        EnumCache::flush();

        $fresh = EnumCache::getInstance();
        // TTL is reset to default after resetInstance
        // flush() clears entries but preserves instance
        expect($fresh->has(UserStatus::class))->toBeFalse();
    });

    it('respect TTL expiration', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Disable caching

        EnumMetadataResolver::resolve(OrderStatus::class);
        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('zero-value int-backed enum resolves correctly', function (): void {
        $meta = EnumMetadataResolver::resolve(IntPriority::class);

        expect($meta['labels'])->toHaveKey(0);
        expect($meta['labels'][0])->toBeString();
    });

    it('single case enum resolves with valid structure', function (): void {
        $meta = EnumMetadataResolver::resolve(SingleCaseEnum::class);

        expect($meta)->toBeArray();
        expect($meta['labels'])->toHaveCount(1);
        expect($meta['colors'])->toHaveCount(1);
    });
});

describe('EnumCache singleton lifecycle', function (): void {
    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('returns same singleton instance across multiple getInstance calls', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('clear removes all entries but preserves instance', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set('App\\Enums\\Foo', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clear();
        expect($cache->has('App\\Enums\\Foo'))->toBeFalse();
    });

    it('clearClass removes only targeted entry', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set('App\\Enums\\A', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('App\\Enums\\B', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clearClass('App\\Enums\\A');
        expect($cache->has('App\\Enums\\A'))->toBeFalse();
        expect($cache->has('App\\Enums\\B'))->toBeTrue();
    });

    it('get throws OutOfBoundsException for missing entry', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistent'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('setTtl clamps negative values to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-10);

        expect($cache->getTtl())->toBe(0);
    });

    it('setTtl stores positive values exactly', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(600);

        expect($cache->getTtl())->toBe(600);
    });

    it('resetInstance allows fresh start', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(999);
        $cache->set('Test', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        EnumCache::resetInstance();
        $fresh = EnumCache::getInstance();

        expect($fresh)->not->toBe($cache);
        expect($fresh->getTtl())->toBe(300); // Default TTL
        expect($fresh->has('Test'))->toBeFalse();
    });
});
