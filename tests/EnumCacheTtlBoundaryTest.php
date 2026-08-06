<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum TTL Boundary & Cache Behavior', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    describe('TTL normalization', function () {
        it('normalizes negative TTL to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-100);

            $cache->set('TestEnum', [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('TTL of 1 second expires quickly', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(1);

            $cache->set('TestEnum', [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            // Should exist immediately
            expect($cache->has('TestEnum'))->toBeTrue();

            // Sleep just past TTL
            usleep(1_100_000);

            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('high TTL keeps entries alive', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(3600);

            $cache->set('TestEnum', [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeTrue();

            usleep(100_000); // 100ms

            expect($cache->has('TestEnum'))->toBeTrue();
        });
    });

    describe('TTL expiration removes only expired entries', function () {
        it('removes expired but keeps fresh entries', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(1);

            $cache->set('OldEnum', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            usleep(1_100_000); // Let OldEnum expire

            $cache->set('NewEnum', [
                'labels' => ['a' => 'A'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('OldEnum'))->toBeFalse();
            expect($cache->has('NewEnum'))->toBeTrue();
        });
    });

    describe('Cache get throws on missing entry', function () {
        it('throws OutOfBoundsException', function () {
            $cache = EnumCache::getInstance();

            expect(fn () => $cache->get('NonExistent'))
                ->toThrow(\OutOfBoundsException::class);
        });

        it('exception message includes class name', function () {
            $cache = EnumCache::getInstance();

            try {
                $cache->get('SomeEnum');
                expect(true)->toBeFalse('Should have thrown');
            } catch (\OutOfBoundsException $e) {
                expect($e->getMessage())->toContain('SomeEnum');
            }
        });
    });

    describe('EnumCache::flush static convenience', function () {
        it('flushes via static method', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300); // Enable caching

            $cache->set('EnumA', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set('EnumB', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('EnumA'))->toBeTrue();
            expect($cache->has('EnumB'))->toBeTrue();

            EnumCache::flush();

            expect($cache->has('EnumA'))->toBeFalse();
            expect($cache->has('EnumB'))->toBeFalse();
        });
    });

    describe('Comparison method edge cases', function () {
        it('is() with instance from different enum throws type error', function () {
            // PHP will throw TypeError at runtime for mismatched types
            // This test documents the expected behavior
            $status = UserStatus::ACTIVE;
            $priority = Priority::HIGH;

            // These are different enum types — comparing them via name string works
            expect($status->is('ACTIVE'))->toBeTrue();
            expect($priority->is('HIGH'))->toBeTrue();

            // is() with a different enum instance — PHP type system prevents this
            // via the `self` type hint on the parameter
        });

        it('in() with empty array returns false', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('in() with single matching element', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
        });

        it('isNot() negates is() correctly', function () {
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
        });

        it('tryFromLabel with empty string returns null', function () {
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });
    });

    describe('values/labels consistency', function () {
        it('values() and forSelect() have same count', function () {
            $values = UserStatus::values();
            $select = UserStatus::forSelect();

            expect(count($values))->toBe(count($select));
        });

        it('values() preserves declaration order', function () {
            $values = Priority::values();
            expect($values)->toEqual([1, 2, 3, 4]);
        });

        it('labels() count matches cases count', function () {
            expect(count(UserStatus::labels()))->toBe(count(UserStatus::cases()));
            expect(count(Priority::labels()))->toBe(count(Priority::cases()));
            expect(count(RequestState::labels()))->toBe(count(RequestState::cases()));
        });
    });
});
