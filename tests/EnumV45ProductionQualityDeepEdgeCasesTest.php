<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('V45 — Production Quality Deep Edge Cases', function () {
    // -----------------------------------------------------------------------
    // EnumCache: get() throws before has() contract
    // -----------------------------------------------------------------------
    describe('EnumCache get/has contract', function () {
        it('get() throws OutOfBoundsException when no entry exists', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->clearClass('NonExistentEnum');

            expect(fn (): mixed => $cache->get('NonExistentEnum'))
                ->toThrow(\OutOfBoundsException::class, 'No cached metadata');
        });

        it('get() returns full metadata shape after set()', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $metadata = [
                'labels' => ['active' => 'Active'],
                'descriptions' => ['active' => 'Active status'],
                'colors' => ['active' => 'success'],
                'icons' => ['active' => 'check'],
            ];

            $cache->set('TestEnumCacheV45', $metadata);
            $result = $cache->get('TestEnumCacheV45');

            expect($result)->toBe($metadata);
        });

        it('getTtl/setTtl normalize negative values to 0', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $originalTtl = $cache->getTtl();

            $cache->setTtl(-10);
            expect($cache->getTtl())->toBe(0);

            $cache->setTtl($originalTtl);
        });

        it('has() returns false when TTL is 0 (caching disabled)', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $originalTtl = $cache->getTtl();

            $cache->setTtl(0);
            $cache->set('TestEnumCacheTtl0', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnumCacheTtl0'))->toBeFalse();

            $cache->setTtl($originalTtl);
        });
    });

    // -----------------------------------------------------------------------
    // EnumCast: serialize with numeric string roundtrip
    // -----------------------------------------------------------------------
    describe('EnumCast serialize edge cases', function () {
        it('serialize passes through string values for string-backed enums', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(OrderStatus::class);

            $result = $cast->serialize(
                new class {
                    public function __get(string $name): mixed { return null; }
                },
                'status',
                OrderStatus::PENDING,
                []
            );

            expect($result)->toBe('pending');
        });

        it('serialize returns null for null values', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(OrderStatus::class);

            $result = $cast->serialize(
                new class {
                    public function __get(string $name): mixed { return null; }
                },
                'status',
                null,
                []
            );

            expect($result)->toBeNull();
        });

        it('set() validates int value against int-backed enum', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(IntBackedPriority::class);

            $result = $cast->set(
                new class {
                    public function __get(string $name): mixed { return null; }
                },
                'priority',
                1,
                []
            );

            expect($result)->toBe(1);
        });

        it('set() throws for invalid raw value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(IntBackedPriority::class);

            expect(fn () => $cast->set(
                new class {
                    public function __get(string $name): mixed { return null; }
                },
                'priority',
                9999,
                []
            ))->toThrow(\InvalidArgumentException::class);
        });
    });

    // -----------------------------------------------------------------------
    // EnumRule: nullable with type mismatch rejection
    // -----------------------------------------------------------------------
    describe('EnumRule nullable and type mismatch', function () {
        it('nullable instance allows null value', function () {
            $rule = EnumRule::for(OrderStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', null, function (string $attribute, string|null $message = null) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('nullable instance rejects non-null invalid value', function () {
            $rule = EnumRule::for(OrderStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', 'nonexistent', function (string $attribute, string|null $message = null) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('rejects type mismatch for int-backed enum with string value', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;

            $rule->validate('priority', 'not-an-int', function (string $attribute, string|null $message = null) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('non-nullable rejects null value', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $failed = false;

            $rule->validate('status', null, function (string $attribute, string|null $message = null) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // InvalidEnumException: factory methods consistency
    // -----------------------------------------------------------------------
    describe('InvalidEnumException factory methods', function () {
        it('value() handles null value display', function () {
            $exception = InvalidEnumException::value('App\\Enums\\Status', null);

            expect($exception->getMessage())->toContain('null');
            expect($exception->getMessage())->toContain('App\\Enums\\Status');
        });

        it('value() handles int value display', function () {
            $exception = InvalidEnumException::value('App\\Enums\\Status', 42);

            expect($exception->getMessage())->toContain('42');
        });

        it('forName() includes both class and name in message', function () {
            $exception = InvalidEnumException::forName('App\\Enums\\Status', 'INVALID');

            expect($exception->getMessage())->toContain('INVALID');
            expect($exception->getMessage())->toContain('App\\Enums\\Status');
        });

        it('__toString format includes class name and message', function () {
            $exception = InvalidEnumException::value('App\\Enums\\Status', 42);
            $string = (string) $exception;

            expect($string)->toContain('InvalidEnumException');
            expect($string)->toContain('42');
        });
    });

    // -----------------------------------------------------------------------
    // EnumMetadataResolver: invalidation and cache lifecycle
    // -----------------------------------------------------------------------
    describe('EnumMetadataResolver cache lifecycle', function () {
        it('invalidate removes cache entry for specific enum', function () {
            $resolver = \ZeroBoiler\Enums\Support\EnumMetadataResolver::class;

            // Force resolve to cache the entry
            $resolver::resolve(UserStatus::class);

            // Invalidate and verify cache miss
            $resolver::invalidate(UserStatus::class);
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('invalidateAll clears all cached metadata', function () {
            $resolver = \ZeroBoiler\Enums\Support\EnumMetadataResolver::class;

            $resolver::resolve(UserStatus::class);
            $resolver::resolve(OrderStatus::class);

            $resolver::invalidateAll();
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(OrderStatus::class))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // EnumManager: trait delegation consistency
    // -----------------------------------------------------------------------
    describe('EnumManager trait delegation', function () {
        it('values() returns consistent types with trait', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;

            $fromManager = $manager->values(OrderStatus::class);
            $fromTrait = OrderStatus::values();

            expect($fromManager)->toBe($fromTrait);
        });

        it('labels() returns consistent with trait', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;

            $fromManager = $manager->labels(OrderStatus::class);
            $fromTrait = OrderStatus::labels();

            expect($fromManager)->toBe($fromTrait);
        });

        it('forSelect() returns value/label pairs', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $select = $manager->forSelect(OrderStatus::class);

            expect($select)->toBeArray();
            expect($select)->not->toBeEmpty();
            expect($select[0])->toHaveKeys(['value', 'label']);
        });

        it('forApi() returns full metadata shape', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $api = $manager->forApi(OrderStatus::class);

            expect($api)->toBeArray();
            expect($api)->not->toBeEmpty();
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('hasCase() returns true for existing case', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;

            expect($manager->hasCase(OrderStatus::class, 'PENDING'))->toBeTrue();
            expect($manager->hasCase(OrderStatus::class, 'NONEXISTENT'))->toBeFalse();
        });

        it('tryFromName() returns null for non-existent name', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;

            expect($manager->tryFromName(OrderStatus::class, 'NONEXISTENT'))->toBeNull();
        });

        it('tryFromLabel() finds case by label (case-insensitive)', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $label = OrderStatus::PENDING->label();

            $result = $manager->tryFromLabel(OrderStatus::class, strtolower($label));

            expect($result)->not->toBeNull();
            expect($result->name)->toBe('PENDING');
        });

        it('throws BadMethodCallException for non-enum class', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;

            expect(fn () => $manager->forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class, 'does not use HasEnumMetadata');
        });
    });

    // -----------------------------------------------------------------------
    // EnumsServiceProvider: service provider registration
    // -----------------------------------------------------------------------
    describe('EnumsServiceProvider registration', function () {
        it('EnumCache singleton returns same instance', function () {
            $a = \ZeroBoiler\Enums\EnumCache::getInstance();
            $b = \ZeroBoiler\Enums\EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('EnumManager is final readonly', function () {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('EnumCache is final', function () {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumCache::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('EnumCache resetInstance creates fresh singleton', function () {
            \ZeroBoiler\Enums\EnumCache::flush();
            $a = \ZeroBoiler\Enums\EnumCache::getInstance();
            \ZeroBoiler\Enums\EnumCache::resetInstance();
            $b = \ZeroBoiler\Enums\EnumCache::getInstance();

            // New instance after reset — not the same object
            expect($a)->not->toBe($b);
        });
    });
});
