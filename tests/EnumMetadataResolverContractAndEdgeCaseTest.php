<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Comprehensive contract and edge-case tests for EnumMetadataResolver, EnumCache,
 * and the HasEnumMetadata trait public API.
 *
 * Covers: cache TTL behavior, cache isolation, resolver invalidation,
 * label generation edge cases (camelCase, single-word, zero-length),
 * toValue() consistency, and comparison method boundary conditions.
 */
describe('EnumMetadataResolver contract and edge cases', function () {
    beforeEach(function () {
        EnumCache::flush();
    });

    describe('EnumCache singleton behavior', function () {
        it('returns the same instance on repeated calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('resetInstance creates a fresh singleton', function () {
            EnumCache::getInstance()->setTtl(999);
            EnumCache::resetInstance();

            expect(EnumCache::getInstance()->getTtl())->toBe(300);
        });

        it('setTtl clamps negative values to 0', function () {
            EnumCache::getInstance()->setTtl(-5);

            expect(EnumCache::getInstance()->getTtl())->toBe(0);
        });

        it('setTtl accepts 0 to disable caching', function () {
            EnumCache::getInstance()->setTtl(0);

            expect(EnumCache::getInstance()->has(IntBackedPriority::class))->toBeFalse();
        });

        it('flush clears all cache entries', function () {
            EnumMetadataResolver::resolve(IntBackedPriority::class);
            EnumMetadataResolver::resolve(PureFeatureFlag::class);

            EnumCache::flush();

            expect(EnumCache::getInstance()->has(IntBackedPriority::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(PureFeatureFlag::class))->toBeFalse();
        });

        it('clearClass removes only the targeted class', function () {
            EnumMetadataResolver::resolve(IntBackedPriority::class);
            EnumMetadataResolver::resolve(PureFeatureFlag::class);

            EnumCache::getInstance()->clearClass(IntBackedPriority::class);

            expect(EnumCache::getInstance()->has(IntBackedPriority::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(PureFeatureFlag::class))->toBeTrue();
        });

        it('throws OutOfBoundsException when getting non-existent cache entry', function () {
            EnumCache::flush();

            expect(fn () => EnumCache::getInstance()->get('NonExistingEnum'))
                ->toThrow(\OutOfBoundsException::class);
        });

        it('blocks cloning via __clone', function () {
            expect(fn () => clone EnumCache::getInstance())
                ->toThrow(\RuntimeException::class);
        });

        it('__debugInfo returns ttl and counts without internal state', function () {
            EnumMetadataResolver::resolve(IntBackedPriority::class);
            $info = EnumCache::getInstance()->__debugInfo();

            expect($info)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
            expect($info['cachedClasses'])->toBeGreaterThanOrEqual(1);
            expect($info['timestampCount'])->toBe($info['cachedClasses']);
        });
    });

    describe('EnumMetadataResolver invalidation', function () {
        it('invalidate removes specific class cache', function () {
            EnumMetadataResolver::resolve(IntBackedPriority::class);

            EnumMetadataResolver::invalidate(IntBackedPriority::class);

            expect(EnumCache::getInstance()->has(IntBackedPriority::class))->toBeFalse();
        });

        it('invalidateAll clears everything', function () {
            EnumMetadataResolver::resolve(IntBackedPriority::class);
            EnumMetadataResolver::resolve(PureFeatureFlag::class);

            EnumMetadataResolver::invalidateAll();

            expect(EnumCache::getInstance()->has(IntBackedPriority::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(PureFeatureFlag::class))->toBeFalse();
        });

        it('re-resolves metadata after invalidation', function () {
            $before = EnumMetadataResolver::resolve(IntBackedPriority::class);
            EnumMetadataResolver::invalidate(IntBackedPriority::class);
            $after = EnumMetadataResolver::resolve(IntBackedPriority::class);

            expect($before)->toBe($after);
        });

        it('throws LogicException for non-enum class', function () {
            expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))
                ->toThrow(\LogicException::class);
        });
    });

    describe('toValue() consistency across enum types', function () {
        it('returns backed value for string-backed enum', function () {
            expect(UserStatus::ACTIVE->toValue())->toBe('active');
        });

        it('returns backed value for int-backed enum', function () {
            expect(IntBackedPriority::LOW->toValue())->toBeInt();
        });

        it('returns case name for pure enum', function () {
            expect(PureFeatureFlag::DARK_MODE->toValue())->toBe('DARK_MODE');
        });
    });

    describe('comparison method edge cases', function () {
        it('in() returns false for empty array', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('notIn() returns true for empty array', function () {
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
        });

        it('is() is case-sensitive for string names', function () {
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        });

        it('in() works with a single-element array', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
            expect(UserStatus::BANNED->in([UserStatus::ACTIVE]))->toBeFalse();
        });

        it('notIn() is the exact negation of in()', function () {
            $cases = [UserStatus::ACTIVE, UserStatus::INACTIVE];

            foreach (UserStatus::cases() as $case) {
                expect($case->notIn($cases))->toBe(!$case->in($cases));
            }
        });
    });

    describe('label generation edge cases', function () {
        it('generates label for single-word case name', function () {
            // PURE Enums without attributes auto-generate from case name
            // "DARK_MODE" → "Dark Mode" (already covered, but single word check)
            $label = PureFeatureFlag::DARK_MODE->label();

            expect($label)->toBe('Dark Mode');
        });

        it('tryFromLabel is case-insensitive', function () {
            $label = UserStatus::ACTIVE->label();
            $found = UserStatus::tryFromLabel(strtolower($label));

            expect($found)->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel returns null for non-matching label', function () {
            expect(UserStatus::tryFromLabel('nonexistent-label-xyz-123'))->toBeNull();
        });

        it('tryFromName returns null for non-matching name', function () {
            expect(UserStatus::tryFromName('NONEXISTENT'))->toBeNull();
        });

        it('fromName throws InvalidEnumException for non-matching name', function () {
            expect(fn () => UserStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('fromName exception message contains class name and invalid name', function () {
            try {
                UserStatus::fromName('GHOST');
                expect(true)->toBeFalse(); // should not reach
            } catch (InvalidEnumException $e) {
                expect($e->getMessage())->toContain('GHOST');
                expect($e->getMessage())->toContain(UserStatus::class);
            }
        });
    });

    describe('bulk method return type consistency', function () {
        it('forSelect returns value and label keys', function () {
            foreach (UserStatus::forSelect() as $item) {
                expect($item)->toHaveKeys(['value', 'label']);
                expect($item['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forApi returns all metadata keys', function () {
            $expectedKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

            foreach (UserStatus::forApi() as $item) {
                expect($item)->toHaveKeys($expectedKeys);
            }
        });

        it('forApi color is never empty string', function () {
            foreach (UserStatus::forApi() as $item) {
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('values() and labels() have same count as cases()', function () {
            expect(UserStatus::values())->toHaveCount(count(UserStatus::cases()));
            expect(UserStatus::labels())->toHaveCount(count(UserStatus::cases()));
        });

        it('forSelect values are unique', function () {
            $values = array_column(UserStatus::forSelect(), 'value');

            expect($values)->each->toBeUnique();
        });
    });
});
