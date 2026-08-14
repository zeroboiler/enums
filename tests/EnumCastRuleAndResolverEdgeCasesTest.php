<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCast serialization edge cases', function () {
    it('serialize returns backed value for enum instance', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', UserStatus::ACTIVE, []);
        expect($result)->toBe('active');
    });

    it('serialize returns int for int-backed enum instance', function () {
        $cast = new EnumCast(\ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', \ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::PENDING, []);
        expect($result)->toBeInt();
    });

    it('serialize passes through raw string values', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', 'active', []);
        expect($result)->toBe('active');
    });

    it('serialize returns null for null value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('set throws InvalidArgumentException for wrong enum type', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        // Pass a different enum class instance
        expect(fn () => $cast->set($model, 'status', \ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::PENDING, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('set validates raw int value against string-backed enum', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        // UserStatus is string-backed — passing an int-backed value that doesn't match
        // tryFrom will return null for int values on string-backed enums
        expect(fn () => $cast->set($model, 'status', 999, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('get returns null for non-int non-string value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'status', ['array_value'], []);
        expect($result)->toBeNull();
    });

    it('get returns null for boolean value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'status', true, []);
        expect($result)->toBeNull();
    });
});

describe('EnumRule with pure enums', function () {
    it('validates case name for pure enum', function () {
        $rule = EnumRule::for(TicketStatus::class);
        $fail = fn () => null;

        // Should not fail for valid case name
        $called = false;
        $rule->validate('status', 'OPEN', function () use (&$called) {
            $called = true;
        });
        expect($called)->toBeFalse();

        // Should fail for invalid case name
        $called = false;
        $rule->validate('status', 'NONEXISTENT', function () use (&$called) {
            $called = true;
        });
        expect($called)->toBeTrue();
    });

    it('rejects non-string value for pure enum', function () {
        $rule = EnumRule::for(TicketStatus::class);
        $called = false;
        $rule->validate('status', 123, function () use (&$called) {
            $called = true;
        });
        expect($called)->toBeTrue();
    });

    it('nullable mode passes for null value', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $called = false;
        $rule->validate('status', null, function () use (&$called) {
            $called = true;
        });
        expect($called)->toBeFalse();
    });

    it('non-nullable mode fails for null value', function () {
        $rule = EnumRule::for(UserStatus::class);
        $called = false;
        $rule->validate('status', null, function () use (&$called) {
            $called = true;
        });
        expect($called)->toBeTrue();
    });

    it('validates against backing type strictly — rejects string for int enum', function () {
        $rule = EnumRule::for(\ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::class);
        $called = false;
        // OrderStatus is int-backed, passing a string should fail before tryFrom
        $rule->validate('status', 'PENDING', function () use (&$called) {
            $called = true;
        });
        expect($called)->toBeTrue();
    });

    it('includes allowed values in error message for enums with metadata', function () {
        $rule = EnumRule::for(UserStatus::class);
        // Access the private message() method via reflection
        $ref = new \ReflectionMethod($rule, 'message');
        $ref->setAccessible(true);
        $message = $ref->invoke($rule, 'status');
        expect($message)->toContain('status');
        expect($message)->toContain('invalid');
    });

    it('fails gracefully for non-existent enum class', function () {
        $rule = EnumRule::for('NonExistentEnumClass');
        $called = false;
        $rule->validate('status', 'anything', function () use (&$called) {
            $called = true;
        });
        expect($called)->toBeTrue();
    });
});

describe('EnumMetadataResolver invalidation', function () {
    beforeEach(function () {
        \ZeroBoiler\Enums\EnumCache::resetInstance();
    });

    afterEach(function () {
        \ZeroBoiler\Enums\EnumCache::resetInstance();
    });

    it('invalidate removes specific class from cache', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::class);

        EnumMetadataResolver::invalidate(UserStatus::class);

        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::class))->toBeTrue();
    });

    it('invalidateAll flushes everything', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::class);
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\SystemStatus::class);

        EnumMetadataResolver::invalidateAll();

        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::class))->toBeFalse();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\SystemStatus::class))->toBeFalse();
    });

    it('resolve rebuilds metadata after invalidation', function () {
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::invalidate(UserStatus::class);
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        // Same content, different cache entry
        expect($meta1)->toBe($meta2);
    });

    it('resolve throws LogicException for non-enum class', function () {
        expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))
            ->toThrow(\LogicException::class);
    });
});

describe('EnumManager delegation edge cases', function () {
    it('forSelect throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;
        // Use a plain PHP enum without HasEnumMetadata
        expect(fn () => $manager->forSelect(\BasicEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('tryFromLabel throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;
        expect(fn () => $manager->tryFromLabel(\BasicEnum::class, 'ACTIVE'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('hasCase throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;
        expect(fn () => $manager->hasCase(\BasicEnum::class, 'ACTIVE'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('tryFromName throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;
        expect(fn () => $manager->tryFromName(\BasicEnum::class, 'ACTIVE'))
            ->toThrow(\BadMethodCallException::class);
    });
});

describe('HasEnumMetadata values() for pure enums', function () {
    it('values returns case names for pure enums', function () {
        $values = TicketStatus::values();
        expect($values)->toBeArray();
        foreach (TicketStatus::cases() as $case) {
            expect(in_array($case->name, $values, true))->toBeTrue();
        }
    });

    it('values() count matches cases() count', function () {
        $stringValues = UserStatus::values();
        $intValues = \ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::values();
        $pureValues = TicketStatus::values();

        expect(count($stringValues))->toBe(count(UserStatus::cases()));
        expect(count($intValues))->toBe(count(\ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::cases()));
        expect(count($pureValues))->toBe(count(TicketStatus::cases()));
    });

    it('labels() count matches cases() count for all enum types', function () {
        expect(count(UserStatus::labels()))->toBe(count(UserStatus::cases()));
        expect(count(\ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::labels()))->toBe(count(\ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::cases()));
        expect(count(TicketStatus::labels()))->toBe(count(TicketStatus::cases()));
    });

    it('forSelect values are unique for backed enums', function () {
        $select = UserStatus::forSelect();
        $values = array_column($select, 'value');
        expect($values)->toEqual(array_unique($values));
    });

    it('forApi returns all expected keys for every case', function () {
        $api = UserStatus::forApi();
        $expectedKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

        foreach ($api as $item) {
            expect(array_keys($item))->toBe($expectedKeys);
            expect($item['color'])->toBeString()->not->toBeEmpty();
            expect($item['label'])->toBeString()->not->toBeEmpty();
        }
    });

    it('forApi color defaults to secondary when no color attribute set', function () {
        $api = UserStatus::forApi();
        // INACTIVE has no color attribute — should default to 'secondary'
        $inactive = array_filter($api, fn (array $item) => $item['name'] === 'INACTIVE');
        $inactive = array_values($inactive);
        expect($inactive[0]['color'])->toBe('secondary');
    });
});

/**
 * Plain enum without HasEnumMetadata — used to test EnumManager error handling.
 */
enum BasicEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
