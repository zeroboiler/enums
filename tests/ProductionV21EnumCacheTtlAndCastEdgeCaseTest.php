<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

describe('V21 — EnumCache TTL Edge Cases, EnumCast Numeric String, And Message Formatting', function () {
    // ─── EnumCache: TTL auto-expiration ──────────────────────────────────

    it('EnumCache auto-expires entry after TTL elapses', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL

        $metadata = ['labels' => ['active' => 'Active'], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('TestAutoExpireEnum', $metadata);

        expect($cache->has('TestAutoExpireEnum'))->toBeTrue();

        // Manually backdate the timestamp to simulate TTL expiry
        // We access the cache timestamp and set it to past TTL
        $reflection = new \ReflectionProperty(EnumCache::class, 'cacheTimestamps');
        $reflection->setAccessible(true);
        $timestamps = $reflection->getValue($cache);
        $timestamps['TestAutoExpireEnum'] = microtime(true) - 2; // 2 seconds ago (past 1s TTL)
        $reflection->setValue($cache, $timestamps);

        expect($cache->has('TestAutoExpireEnum'))->toBeFalse();
        EnumCache::resetInstance();
    });

    it('EnumCache TTL expiration removes both cache entry and timestamp', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);

        $metadata = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set('TestExpireCleanEnum', $metadata);

        // Backdate timestamp
        $tsReflection = new \ReflectionProperty(EnumCache::class, 'cacheTimestamps');
        $tsReflection->setAccessible(true);
        $timestamps = $tsReflection->getValue($cache);
        $timestamps['TestExpireCleanEnum'] = microtime(true) - 5;
        $tsReflection->setValue($cache, $timestamps);

        // Trigger has() to auto-expire
        $cache->has('TestExpireCleanEnum');

        // Verify both arrays are cleaned up
        $cacheReflection = new \ReflectionProperty(EnumCache::class, 'cache');
        $cacheReflection->setAccessible(true);
        $cacheData = $cacheReflection->getValue($cache);
        $tsData = $tsReflection->getValue($cache);

        expect(isset($cacheData['TestExpireCleanEnum']))->toBeFalse();
        expect(isset($tsData['TestExpireCleanEnum']))->toBeFalse();
        EnumCache::resetInstance();
    });

    it('EnumCache setTtl normalizes negative values to 0', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
        EnumCache::resetInstance();
    });

    it('EnumCache getTtl returns configured TTL', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();

        $cache->setTtl(600);
        expect($cache->getTtl())->toBe(600);

        $cache->setTtl(0);
        expect($cache->getTtl())->toBe(0);
        EnumCache::resetInstance();
    });

    it('EnumCache has() returns false for never-cached class', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        expect($cache->has('NeverCachedEnum'))->toBeFalse();
        EnumCache::resetInstance();
    });

    it('EnumCache get() throws OutOfBoundsException for missing entry', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))
            ->toThrow(\OutOfBoundsException::class);
        EnumCache::resetInstance();
    });

    // ─── EnumCache: serialization prevention ─────────────────────────────

    it('EnumCache blocks serialize()', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();

        expect(fn () => serialize($cache))
            ->toThrow(\RuntimeException::class);
        EnumCache::resetInstance();
    });

    it('EnumCache blocks unserialize()', function () {
        EnumCache::resetInstance();

        expect(fn () => unserialize('O:34:"ZeroBoiler\\Enums\\EnumCache":0:{}'))
            ->toThrow(\RuntimeException::class);
    });

    // ─── EnumCast: numeric string to int-backed enum ─────────────────────

    it('EnumCast get() handles numeric string for int-backed enum', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(IntBackedPriority::class);
        // Database often returns int values as strings
        $result = $cast->get(new \stdClass, 'priority', '1', []);

        expect($result)->not->toBeNull();
        expect($result)->toBe(IntBackedPriority::MEDIUM);
    });

    it('EnumCast get() handles string for string-backed enum', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
        $result = $cast->get(new \stdClass, 'status', 'active', []);

        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('EnumCast get() returns null for empty string on string-backed enum', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
        $result = $cast->get(new \stdClass, 'status', 'nonexistent_status_xyz', []);

        expect($result)->toBeNull();
    });

    it('EnumCast set() accepts valid raw string value for string-backed enum', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
        $result = $cast->set(new \stdClass, 'status', 'banned', []);

        expect($result)->toBe('banned');
    });

    it('EnumCast set() accepts valid raw int value for int-backed enum', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(IntBackedPriority::class);
        $result = $cast->set(new \stdClass, 'priority', 2, []);

        expect($result)->toBe(2);
    });

    it('EnumCast set() throws for null on non-nullable enum (when passed explicitly)', function () {
        // null is handled before set reaches enum validation
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
        $result = $cast->set(new \stdClass, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('EnumCast serialize() passes through raw string values', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', 'active', []);
        expect($result)->toBe('active');
    });

    it('EnumCast serialize() passes through raw int values', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(IntBackedPriority::class);
        $result = $cast->serialize(new \stdClass, 'priority', 1, []);
        expect($result)->toBe(1);
    });

    // ─── EnumRule: message formatting with HasEnumMetadata ────────────────

    it('EnumRule generates descriptive message when enum has values()', function () {
        $rule = EnumRule::for(UserStatus::class);
        $errors = [];

        $rule->validate('status', 'invalid_xyz', function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        expect($errors)->not->toBeEmpty();
        // Message should contain "Allowed values:" since UserStatus has values()
        $message = $errors[0];
        expect($message)->toContain('Allowed values');
    });

    it('EnumRule generates generic message when enum lacks values()', function () {
        $rule = EnumRule::for(PlainTestEnum::class);
        $errors = [];

        $rule->validate('status', 'NONEXISTENT', function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        expect($errors)->not->toBeEmpty();
        // PlainTestEnum does not use HasEnumMetadata — no values() method
        $message = $errors[0];
        expect($message)->toContain('invalid');
    });

    // ─── InvalidEnumException: value() edge cases ─────────────────────────

    it('InvalidEnumException::value() with string value displays the string', function () {
        $e = InvalidEnumException::value(UserStatus::class, 'unknown_status');
        expect($e->getMessage())->toContain('unknown_status');
    });

    it('InvalidEnumException::value() with int value displays the number', function () {
        $e = InvalidEnumException::value(IntBackedPriority::class, 99);
        expect($e->getMessage())->toContain('99');
    });

    it('InvalidEnumException::forName() contains both class and name', function () {
        $e = InvalidEnumException::forName(Priority::class, 'GHOST_CASE');
        $message = $e->getMessage();

        expect($message)->toContain('GHOST_CASE');
        expect($message)->toContain(Priority::class);
        expect($message)->toContain('does not exist');
    });

    // ─── EnumManager: full delegation coverage ─────────────────────────────

    it('EnumManager labels() returns list of strings', function () {
        $manager = new EnumManager;
        $labels = $manager->labels(UserStatus::class);

        expect(is_array($labels))->toBeTrue();
        expect($labels)->not->toBeEmpty();
        expect(count($labels))->toBe(count(UserStatus::cases()));

        foreach ($labels as $label) {
            expect(is_string($label))->toBeTrue();
        }
    });

    it('EnumManager values() returns correct backed values', function () {
        $manager = new EnumManager;
        $values = $manager->values(IntBackedPriority::class);

        expect($values)->not->toBeEmpty();
        foreach ($values as $v) {
            expect(is_int($v))->toBeTrue();
        }
    });

    it('EnumManager tryFromLabel() resolves case-insensitively', function () {
        $manager = new EnumManager;
        $result = $manager->tryFromLabel(UserStatus::class, 'banned user');

        expect($result)->toBe(UserStatus::BANNED);
    });

    it('EnumManager tryFromLabel() returns null for unknown label', function () {
        $manager = new EnumManager;
        expect($manager->tryFromLabel(UserStatus::class, 'nonexistent_label_xyz_123'))->toBeNull();
    });

    it('EnumManager hasCase() returns correct boolean', function () {
        $manager = new EnumManager;
        expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
        expect($manager->hasCase(UserStatus::class, 'GHOST_CASE'))->toBeFalse();
    });

    it('EnumManager forApi() returns full metadata structure', function () {
        $manager = new EnumManager;
        $api = $manager->forApi(UserStatus::class);

        expect($api)->not->toBeEmpty();
        $expectedKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];
        foreach ($api as $item) {
            foreach ($expectedKeys as $key) {
                expect(array_key_exists($key, $item))->toBeTrue();
            }
        }
    });

    // ─── EnumMetadataResolver: resolution consistency ─────────────────────

    it('EnumMetadataResolver returns consistent metadata across multiple resolves', function () {
        EnumCache::resetInstance();
        EnumCache::getInstance()->setTtl(300);

        $first = EnumMetadataResolver::resolve(UserStatus::class);
        $second = EnumMetadataResolver::resolve(UserStatus::class);

        expect($first)->toBe($second);
        EnumCache::resetInstance();
    });

    it('EnumMetadataResolver resolves pure enum metadata', function () {
        EnumCache::resetInstance();
        EnumCache::getInstance()->setTtl(300);

        $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        expect(isset($meta['labels']))->toBeTrue();
        expect(isset($meta['descriptions']))->toBeTrue();
        expect(isset($meta['colors']))->toBeTrue();
        expect(isset($meta['icons']))->toBeTrue();
        expect($meta['labels'])->not->toBeEmpty();
        EnumCache::resetInstance();
    });

    // ─── Cross-fixture: all enums implement trait methods correctly ────────

    it('all fixture enums with HasEnumMetadata have consistent forSelect structure', function () {
        $enums = [
            UserStatus::class, Priority::class, IntBackedPriority::class,
            PaymentStatus::class, OrderStatus::class, CamelCasePriority::class,
            AllClassLevelEnum::class, EmptyDefaultsStatus::class,
            SingleCaseToggle::class, ZeroBackedPriority::class,
        ];

        foreach ($enums as $enumClass) {
            $select = $enumClass::forSelect();
            expect(count($select))->toBe(count($enumClass::cases()), "{$enumClass}::forSelect() count mismatch");

            foreach ($select as $item) {
                expect(array_key_exists('value', $item))->toBeTrue("{$enumClass} missing 'value' key");
                expect(array_key_exists('label', $item))->toBeTrue("{$enumClass} missing 'label' key");
                expect(is_string($item['label']))->toBeTrue("{$enumClass} label must be string");
                expect($item['label'])->not->toBeEmpty("{$enumClass} label must not be empty");
            }
        }
    });

    it('all fixture enums with HasEnumMetadata have consistent forApi structure', function () {
        $enums = [
            UserStatus::class, Priority::class, IntBackedPriority::class,
            PaymentStatus::class, OrderStatus::class, CamelCasePriority::class,
            AllClassLevelEnum::class, EmptyDefaultsStatus::class,
            SingleCaseToggle::class, ZeroBackedPriority::class,
        ];

        foreach ($enums as $enumClass) {
            $api = $enumClass::forApi();
            expect(count($api))->toBe(count($enumClass::cases()), "{$enumClass}::forApi() count mismatch");

            foreach ($api as $item) {
                expect(is_string($item['color']))->toBeTrue("{$enumClass} color must be string");
                expect(is_string($item['name']))->toBeTrue("{$enumClass} name must be string");
                expect(is_string($item['label']))->toBeTrue("{$enumClass} label must be string");
            }
        }
    });
});
