<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * EnumCache and EnumCast edge case coverage tests.
 *
 * Tests specific edge cases around cache TTL boundaries, class-level
 * cache clearing, and EnumCast serialization behavior.
 */

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache edge cases', function (): void {

    afterEach(function (): void {
        EnumCache::flush();
        EnumMetadataResolver::invalidateAll();
    });

    it('clearClass() only removes the targeted class entry', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        $cache = EnumCache::getInstance();

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });

    it('clearClass() is a no-op for non-existent cache entries', function (): void {
        $cache = EnumCache::getInstance();

        // Should not throw
        $cache->clearClass('NonExistentEnumClass');

        expect($cache->has('NonExistentEnumClass'))->toBeFalse();
    });

    it('flush() is a static alias that clears all entries', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);
        EnumMetadataResolver::resolve(IntStatusWithColor::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(IntStatusWithColor::class))->toBeTrue();

        EnumCache::flush();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(IntStatusWithColor::class))->toBeFalse();
    });

    it('setTtl with 0 disables caching — entries are always stale', function (): void {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(0);

        $cache->set('TestEnum', [
            'labels' => ['test' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // TTL is 0 — should always report stale
        expect($cache->has('TestEnum'))->toBeFalse();

        $cache->setTtl(300); // restore
    });

    it('TTL expiration triggers correctly after time passes', function (): void {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(1); // 1 second TTL

        $cache->set('TimeTestEnum', [
            'labels' => ['x' => 'X'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Immediately available
        expect($cache->has('TimeTestEnum'))->toBeTrue();

        // Manually backdate the timestamp to simulate expiration
        $reflection = new ReflectionProperty($cache, 'cacheTimestamps');
        $timestamps = $reflection->getValue($cache);
        $timestamps['TimeTestEnum'] = microtime(true) - 2; // 2 seconds ago
        $reflection->setValue($cache, $timestamps);

        // Now it should be expired
        expect($cache->has('TimeTestEnum'))->toBeFalse();

        $cache->setTtl(300); // restore
    });

    it('set() overwrites existing cache entry', function (): void {
        $cache = EnumCache::getInstance();
        $cache->clear();

        $cache->set('OverwriteEnum', [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->set('OverwriteEnum', [
            'labels' => ['b' => 'B'],
            'descriptions' => ['b' => 'B desc'],
            'colors' => [],
            'icons' => [],
        ]);

        $entry = $cache->get('OverwriteEnum');
        expect($entry['labels'])->toBe(['b' => 'B']);
        expect($entry['descriptions'])->toBe(['b' => 'B desc']);
    });

    it('get() throws OutOfBoundsException for non-existent entry', function (): void {
        $cache = EnumCache::getInstance();
        $cache->clear();

        expect(fn () => $cache->get('NeverCachedEnum'))
            ->toThrow(OutOfBoundsException::class);
    });
});

describe('EnumCast edge cases', function (): void {

    it('serialize() returns null for null value', function (): void {
        $cast = new EnumCast(OrderStatus::class);

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

    it('serialize() returns backed value for enum instance', function (): void {
        $cast = new EnumCast(OrderStatus::class);

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

    it('serialize() passes through int/string raw values', function (): void {
        $cast = new EnumCast(Priority::class);

        $intResult = $cast->serialize(
            new class {
                public function __get(string $name): mixed { return null; }
            },
            'priority',
            3,
            []
        );
        expect($intResult)->toBe(3);

        $cast2 = new EnumCast(OrderStatus::class);
        $strResult = $cast2->serialize(
            new class {
                public function __get(string $name): mixed { return null; }
            },
            'status',
            'shipped',
            []
        );
        expect($strResult)->toBe('shipped');
    });

    it('get() returns null for non-matching values', function (): void {
        $cast = new EnumCast(OrderStatus::class);

        $result = $cast->get(
            new class {
                public function __get(string $name): mixed { return null; }
            },
            'status',
            'non_existent_value',
            []
        );

        expect($result)->toBeNull();
    });

    it('set() throws InvalidArgumentException for wrong enum class', function (): void {
        $cast = new EnumCast(OrderStatus::class);

        expect(fn () => $cast->set(
            new class {
                public function __get(string $name): mixed { return null; }
            },
            'status',
            Priority::LOW,
            []
        ))->toThrow(InvalidArgumentException::class);
    });

    it('set() throws InvalidArgumentException for invalid raw value', function (): void {
        $cast = new EnumCast(Priority::class);

        expect(fn () => $cast->set(
            new class {
                public function __get(string $name): mixed { return null; }
            },
            'priority',
            999, // Not a valid Priority value
            []
        ))->toThrow(InvalidArgumentException::class);
    });
});

describe('EnumRule edge cases', function (): void {

    it('nullable() does not modify the original rule instance', function (): void {
        $original = EnumRule::for(UserStatus::class);
        $nullable = $original->nullable();

        // They should be different instances
        assert($original !== $nullable);

        // The original should work as non-nullable
        $originalFailed = false;
        $original->validate('status', null, function () use (&$originalFailed): void {
            $originalFailed = true;
        });
        expect($originalFailed)->toBeTrue();

        // The nullable should pass null
        $nullableFailed = false;
        $nullable->validate('status', null, function () use (&$nullableFailed): void {
            $nullableFailed = true;
        });
        expect($nullableFailed)->toBeFalse();
    });

    it('validate() rejects non-existent values for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', 'non_existent_status', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('validate() rejects non-existent values for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);
        $failed = false;

        $rule->validate('priority', 999, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('message() includes allowed values for HasEnumMetadata enums', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $lastMessage = '';

        $rule->validate('status', 'bad_value', function (string $message) use (&$lastMessage): void {
            $lastMessage = $message;
        });

        expect($lastMessage)->toContain('Allowed values');
        expect($lastMessage)->toContain('active');
    });

    it('validate() works with pure enum by case name', function (): void {
        // Use a class that exists as a pure enum
        $rule = EnumRule::for(\ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag::class);
        $failed = false;

        $rule->validate('flag', 'DARK_MODE', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });
});

describe('EnumMetadataResolver invalidation', function (): void {

    afterEach(function (): void {
        EnumCache::flush();
        EnumMetadataResolver::invalidateAll();
    });

    it('invalidate() only clears one class', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();

        EnumMetadataResolver::invalidate(UserStatus::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();
    });

    it('invalidateAll() clears every cached class', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);
        EnumMetadataResolver::resolve(OrderStatus::class);

        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeFalse();
    });

    it('resolve() rebuilds after invalidation', function (): void {
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);

        EnumMetadataResolver::invalidate(UserStatus::class);

        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta1)->toBe($meta2); // Same structure, freshly resolved
    });
});
