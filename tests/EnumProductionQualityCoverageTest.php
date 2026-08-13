<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum Singleton Lifecycle and Cache Consistency', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('returns the same singleton instance across multiple calls', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('resets to a fresh instance after resetInstance()', function () {
        $a = EnumCache::getInstance();
        EnumCache::resetInstance();
        $b = EnumCache::getInstance();

        expect($a)->not->toBe($b);
    });

    it('isolates cache entries between different enum classes', function () {
        $cache = EnumCache::getInstance();
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ]);
        $cache->set(Priority::class, [
            'labels' => [1 => 'Low'],
            'descriptions' => [],
            'colors' => [1 => 'info'],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });

    it('setTtl normalizes negative values to zero', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('flush() clears all entries via static accessor', function () {
        $cache = EnumCache::getInstance();
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(Priority::class, [
            'labels' => [1 => 'Low'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::flush();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
    });
});

describe('Enum Metadata Consistency Across Types', function () {
    it('string-backed enum values() returns backed values', function () {
        $values = UserStatus::values();

        expect($values)->toContain('active');
        expect($values)->toContain('banned');
        expect($values)->not->toContain('ACTIVE');
    });

    it('int-backed enum values() returns backed values', function () {
        $values = Priority::values();

        expect($values)->toBe([1, 2, 3, 4]);
    });

    it('pure enum values() returns case names', function () {
        $values = PureFeatureFlag::values();

        expect($values)->toBe(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
    });

    it('zero-backed enum handles zero value correctly in forSelect()', function () {
        $select = ZeroPriority::forSelect();

        $zeroOption = array_filter($select, fn (array $opt): bool => $opt['value'] === 0);
        expect($zeroOption)->not->toBeEmpty();

        $first = array_values($zeroOption)[0];
        expect($first['label'])->toBeString();
        expect($first['label'])->not->toBeEmpty();
    });

    it('pure enum forApi() uses case names as values', function () {
        $api = PureFeatureFlag::forApi();

        expect($api)->toHaveCount(3);
        expect($api[0]['value'])->toBe('DARK_MODE');
        expect($api[0]['name'])->toBe('DARK_MODE');
    });
});

describe('Enum Comparison Edge Cases', function () {
    it('is() with string name is case-sensitive', function () {
        expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
        expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
    });

    it('is() with enum instance uses strict identity', function () {
        $a = UserStatus::ACTIVE;
        $b = UserStatus::ACTIVE;

        expect($a->is($b))->toBeTrue();
    });

    it('in() with mixed instance and string arguments', function () {
        $status = UserStatus::ACTIVE;

        expect($status->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        expect($status->in(['ACTIVE', 'PENDING']))->toBeTrue();
        expect($status->in(['BANNED', 'SUSPENDED']))->toBeFalse();
    });

    it('notIn() is the exact negation of in()', function () {
        $status = UserStatus::ACTIVE;

        expect($status->notIn(['BANNED', 'SUSPENDED']))->toBeTrue();
        expect($status->notIn(['ACTIVE', 'PENDING']))->toBeFalse();
    });
});

describe('Enum Lookup Error Handling', function () {
    it('fromName() throws InvalidEnumException for non-existent case', function () {
        expect(fn () => UserStatus::fromName('NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('tryFromName() returns null for non-existent case', function () {
        expect(UserStatus::tryFromName('NONEXISTENT'))->toBeNull();
    });

    it('fromName() works with existing case', function () {
        $result = UserStatus::fromName('ACTIVE');

        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel() is case-insensitive', function () {
        expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel() returns null for non-existent label', function () {
        expect(UserStatus::tryFromLabel('NonExistentLabel'))->toBeNull();
    });

    it('hasCase() returns true for existing and false for non-existing', function () {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
    });
});

describe('Enum Label Auto-Generation', function () {
    it('generates Title Case from SCREAMING_SNAKE_CASE', function () {
        // Priority has no per-case or class-level labels
        expect(Priority::LOW->label())->toBe('Low');
        expect(Priority::MEDIUM->label())->toBe('Medium');
        expect(Priority::HIGH->label())->toBe('High');
        expect(Priority::URGENT->label())->toBe('Urgent');
    });

    it('per-case label overrides auto-generation', function () {
        expect(UserStatus::ACTIVE->label())->toBe('Active User');
        expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
    });

    it('generates label for pure enum cases without attributes', function () {
        expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
    });
});

describe('Enum Cache TTL Expiry Behaviour', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('entry expires when TTL elapses', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // disable caching

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Cached Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('get() throws OutOfBoundsException for non-existent entry', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get(UserStatus::class))
            ->toThrow(\OutOfBoundsException::class);
    });
});
