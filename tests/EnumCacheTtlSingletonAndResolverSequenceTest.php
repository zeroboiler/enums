<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache TTL boundary and singleton lifecycle', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
        EnumMetadataResolver::invalidateAll();
    });

    afterEach(function () {
        EnumCache::resetInstance();
        EnumMetadataResolver::invalidateAll();
    });

    it('creates a fresh singleton on first getInstance() call', function () {
        $first = EnumCache::getInstance();
        $second = EnumCache::getInstance();

        expect($first)->toBe($second);
        expect($first)->toBeInstanceOf(EnumCache::class);
    });

    it('resets singleton to null on resetInstance()', function () {
        EnumCache::getInstance();
        EnumCache::resetInstance();

        // After reset, getInstance creates a new instance
        $new = EnumCache::getInstance();
        expect($new)->toBeInstanceOf(EnumCache::class);
    });

    it('returns false from has() when TTL is 0 (disabled)', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('normalizes negative TTL to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('auto-expires entries when TTL is exceeded', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Disable caching for this test
        $cache->setTtl(1);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();

        // Wait for TTL to expire
        sleep(2);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('throws OutOfBoundsException when get() is called without has() check', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('clears specific class without affecting others', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active User'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->set(Priority::class, [
            'labels' => ['1' => 'Low'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });

    it('clear() removes all entries and clear() is idempotent', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clear();
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Idempotent — second clear should not throw
        $cache->clear();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });
});

describe('EnumMetadataResolver cache invalidation sequence', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
        EnumMetadataResolver::invalidateAll();
    });

    afterEach(function () {
        EnumCache::resetInstance();
        EnumMetadataResolver::invalidateAll();
    });

    it('rebuilds metadata after invalidate() is called', function () {
        // First resolution — builds and caches
        $first = EnumMetadataResolver::resolve(UserStatus::class);
        expect($first['labels'])->toHaveKey('active');

        // Invalidate — removes from cache
        EnumMetadataResolver::invalidate(UserStatus::class);

        // Second resolution — should rebuild (not return stale data)
        $second = EnumMetadataResolver::resolve(UserStatus::class);
        expect($second)->toBe($first);
        expect($second['labels']['active'])->toBe('Active User');
    });

    it('invalidateAll() clears all enum caches', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
    });

    it('resolve() is idempotent — multiple calls return same result', function () {
        $a = EnumMetadataResolver::resolve(UserStatus::class);
        $b = EnumMetadataResolver::resolve(UserStatus::class);

        expect($a)->toBe($b);
    });
});

describe('Int-backed enum type safety edge cases', function () {
    it('Priority::values() returns int values, not strings', function () {
        $values = Priority::values();

        expect($values)->toBe([1, 2, 3, 4]);
        expect($values[0])->toBeInt();
    });

    it('Priority::forSelect() uses int values as keys', function () {
        $select = Priority::forSelect();

        expect($select[0]['value'])->toBe(1);
        expect($select[0]['value'])->toBeInt();
    });

    it('fromName() throws InvalidEnumException for non-existent case', function () {
        expect(fn () => Priority::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('tryFromName() returns null for wrong case', function () {
        expect(Priority::tryFromName('low'))->toBeNull(); // case-sensitive
        expect(Priority::tryFromName('LOW'))->toBeInstanceOf(Priority::class);
    });

    it('is() comparison works with both instance and string', function () {
        $p = Priority::HIGH;

        expect($p->is(Priority::HIGH))->toBeTrue();
        expect($p->is('HIGH'))->toBeTrue();
        expect($p->is('high'))->toBeFalse(); // case-sensitive
        expect($p->is(Priority::LOW))->toBeFalse();
    });

    it('in() works with mixed instances and strings', function () {
        $p = Priority::HIGH;

        expect($p->in([Priority::HIGH, Priority::URGENT]))->toBeTrue();
        expect($p->in(['HIGH', 'URGENT']))->toBeTrue();
        expect($p->in([Priority::HIGH, 'URGENT']))->toBeTrue();
        expect($p->in([Priority::LOW, 'MEDIUM']))->toBeFalse();
    });
});

describe('Pure enum edge cases', function () {
    it('PureFeatureFlag::values() returns case names', function () {
        $values = PureFeatureFlag::values();

        expect($values)->toBe(['TWO_FACTOR_AUTH', 'DARK_MODE', 'BETA_ACCESS']);
        expect($values)->each->toBeString();
    });

    it('PureFeatureFlag::forSelect() uses case names as values', function () {
        $select = PureFeatureFlag::forSelect();

        expect($select[0]['value'])->toBe('TWO_FACTOR_AUTH');
        expect($select[0]['value'])->toBeString();
    });

    it('PureFeatureFlag::forApi() includes name field identical to value', function () {
        $api = PureFeatureFlag::forApi();

        foreach ($api as $item) {
            expect($item['value'])->toBe($item['name']);
        }
    });

    it('tryFromLabel() works for pure enums with auto-generated labels', function () {
        $case = PureFeatureFlag::tryFromLabel('Dark Mode');

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('DARK_MODE');
    });

    it('icon() returns per-case attribute for pure enum', function () {
        expect(PureFeatureFlag::TWO_FACTOR_AUTH->icon())->toBe('heroicon-o-shield-check');
        expect(PureFeatureFlag::DARK_MODE->icon())->toBeNull();
    });
});
