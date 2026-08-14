<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager & Facade contract + cache behavior', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::getInstance()->setTtl(0); // Disable TTL for deterministic tests
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::getInstance()->setTtl(0);
    });

    describe('EnumManager: forSelect', function () {
        it('returns correct shape for string-backed enum', function () {
            $manager = new EnumManager;
            $result = $manager->forSelect(UserStatus::class);

            expect($result)->toBeArray();
            expect($result)->toHaveCount(5);

            foreach ($result as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('returns correct shape for int-backed enum', function () {
            $manager = new EnumManager;
            $result = $manager->forSelect(IntBackedPriority::class);

            expect($result)->toBeArray();
            expect($result)->toHaveCount(4);

            foreach ($result as $option) {
                expect($option)->toHaveKey('value');
                expect($option)->toHaveKey('label');
            }
        });

        it('returns correct shape for pure enum', function () {
            $manager = new EnumManager;
            $result = $manager->forSelect(PureFeatureFlag::class);

            expect($result)->toBeArray();
            expect($result)->toHaveCount(3);

            // Pure enums use case names as values
            $names = array_column($result, 'value');
            expect($names)->toContain('DARK_MODE');
            expect($names)->toContain('BETA_FEATURES');
            expect($names)->toContain('MAINTENANCE_MODE');
        });
    });

    describe('EnumManager: forApi', function () {
        it('returns full API metadata shape', function () {
            $manager = new EnumManager;
            $result = $manager->forApi(UserStatus::class);

            expect($result)->toBeArray();
            expect($result)->toHaveCount(5);

            foreach ($result as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString();
            }
        });

        it('ACTIVE case has correct metadata', function () {
            $manager = new EnumManager;
            $result = $manager->forApi(UserStatus::class);
            $active = array_values(array_filter($result, fn (array $item): bool => $item['name'] === 'ACTIVE'))[0];

            expect($active['value'])->toBe('active');
            expect($active['label'])->toBe('Active User');
            expect($active['description'])->toBe('User can fully access the system');
            expect($active['color'])->toBe('success');
            expect($active['icon'])->toBe('heroicon-o-check-circle');
        });
    });

    describe('EnumManager: tryFromLabel', function () {
        it('resolves case by exact label', function () {
            $manager = new EnumManager;
            $result = $manager->tryFromLabel(UserStatus::class, 'Active User');

            expect($result)->not->toBeNull();
            expect($result->name)->toBe('ACTIVE');
        });

        it('is case-insensitive', function () {
            $manager = new EnumManager;
            $result = $manager->tryFromLabel(UserStatus::class, 'active user');

            expect($result)->not->toBeNull();
            expect($result->name)->toBe('ACTIVE');
        });

        it('returns null for non-existent label', function () {
            $manager = new EnumManager;
            $result = $manager->tryFromLabel(UserStatus::class, 'NonExistentLabel');

            expect($result)->toBeNull();
        });
    });

    describe('EnumManager: tryFromName / fromName / hasCase', function () {
        it('tryFromName resolves existing case', function () {
            $manager = new EnumManager;
            $result = $manager->tryFromName(UserStatus::class, 'BANNED');

            expect($result)->not->toBeNull();
            expect($result->name)->toBe('BANNED');
        });

        it('tryFromName returns null for non-existent', function () {
            $manager = new EnumManager;
            $result = $manager->tryFromName(UserStatus::class, 'NON_EXISTENT');

            expect($result)->toBeNull();
        });

        it('fromName throws for non-existent', function () {
            $manager = new EnumManager;
            expect(fn () => $manager->fromName(UserStatus::class, 'NON_EXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns true for existing', function () {
            $manager = new EnumManager;
            expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
        });

        it('hasCase returns false for non-existing', function () {
            $manager = new EnumManager;
            expect($manager->hasCase(UserStatus::class, 'GHOST'))->toBeFalse();
        });
    });

    describe('EnumManager: values / labels', function () {
        it('values returns all backed values', function () {
            $manager = new EnumManager;
            $values = $manager->values(UserStatus::class);

            expect($values)->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);
        });

        it('labels returns all labels in order', function () {
            $manager = new EnumManager;
            $labels = $manager->labels(UserStatus::class);

            expect($labels)->toHaveCount(5);
            expect($labels[0])->toBe('Active User'); // ACTIVE
        });

        it('values returns int for int-backed enum', function () {
            $manager = new EnumManager;
            $values = $manager->values(IntBackedPriority::class);

            expect($values)->toBe([1, 2, 3, 4]);
        });
    });

    describe('EnumManager: throws BadMethodCallException for non-trait enum', function () {
        it('forSelect throws when enum does not use HasEnumMetadata', function () {
            $manager = new EnumManager;
            expect(fn () => $manager->forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });
    });

    describe('EnumCache: singleton behavior', function () {
        it('returns the same instance on repeated calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('resetInstance creates a new instance', function () {
            $a = EnumCache::getInstance();
            $a->set('test', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            expect($a->has('test'))->toBeTrue();

            EnumCache::resetInstance();
            $b = EnumCache::getInstance();
            expect($b->has('test'))->toBeFalse();
        });
    });

    describe('EnumCache: TTL expiration', function () {
        it('auto-expires entries after TTL', function () {
            EnumCache::getInstance()->setTtl(1);
            EnumCache::getInstance()->set('ttl_test', [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect(EnumCache::getInstance()->has('ttl_test'))->toBeTrue();

            // Wait for TTL to expire
            sleep(2);

            expect(EnumCache::getInstance()->has('ttl_test'))->toBeFalse();
        });

        it('TTL of 0 disables caching', function () {
            EnumCache::getInstance()->setTtl(0);
            EnumCache::getInstance()->set('disabled', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect(EnumCache::getInstance()->has('disabled'))->toBeFalse();
        });

        it('negative TTL is normalized to 0', function () {
            EnumCache::getInstance()->setTtl(-5);

            expect(EnumCache::getInstance()->getTtl())->toBe(0);
        });
    });

    describe('EnumCache: clear / clearClass', function () {
        it('clear removes all entries', function () {
            EnumCache::getInstance()->setTtl(0);
            EnumCache::getInstance()->set('a', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);
            EnumCache::getInstance()->set('b', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            EnumCache::getInstance()->clear();

            expect(EnumCache::getInstance()->has('a'))->toBeFalse();
            expect(EnumCache::getInstance()->has('b'))->toBeFalse();
        });

        it('clearClass removes only the specified class', function () {
            EnumCache::getInstance()->setTtl(0);
            EnumCache::getInstance()->set('keep', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);
            EnumCache::getInstance()->set('remove', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            EnumCache::getInstance()->clearClass('remove');

            expect(EnumCache::getInstance()->has('keep'))->toBeTrue();
            expect(EnumCache::getInstance()->has('remove'))->toBeFalse();
        });
    });

    describe('EnumCache: get throws for missing entry', function () {
        it('throws OutOfBoundsException', function () {
            EnumCache::getInstance()->get('nonexistent');
        })->throws(\OutOfBoundsException::class);
    });

    describe('Trait: is() / isNot() / in() / notIn() on int-backed enum', function () {
        it('is() works with int-backed enum instance', function () {
            expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::CRITICAL))->toBeTrue();
            expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::HIGH))->toBeFalse();
        });

        it('is() works with case name string', function () {
            expect(IntBackedPriority::CRITICAL->is('CRITICAL'))->toBeTrue();
            expect(IntBackedPriority::CRITICAL->is('HIGH'))->toBeFalse();
        });

        it('notIn() works correctly', function () {
            expect(IntBackedPriority::NONE->notIn([
                IntBackedPriority::CRITICAL,
                IntBackedPriority::HIGH,
            ]))->toBeTrue();

            expect(IntBackedPriority::NONE->notIn([
                IntBackedPriority::CRITICAL,
                IntBackedPriority::NONE,
            ]))->toBeFalse();
        });
    });
});
