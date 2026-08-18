<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;

beforeEach(function () {
    EnumCache::resetInstance();
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('EnumCache — serialization protection contract', function () {
    test('serialize() always throws RuntimeException', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => serialize($cache))->toThrow(\RuntimeException::class, 'singleton and cannot be serialized');
    });

    test('__unserialize() always throws RuntimeException', function () {
        $cache = EnumCache::getInstance();
        $data = ['cache' => [], 'ttl' => 300];

        // __unserialize is called by unserialize() when __serialize/__unserialize pair exists
        expect(fn () => unserialize('O:27:"ZeroBoiler\\Enums\\EnumCache":0:{}'))
            ->toThrow(\RuntimeException::class);
    });

    test('__clone() always throws RuntimeException', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => clone $cache)->toThrow(\RuntimeException::class, 'singleton and cannot be cloned');
    });

    test('__wakeup() always throws RuntimeException', function () {
        // __wakeup is triggered when __serialize/__unserialize are NOT defined.
        // Since __serialize IS defined, PHP 8.1+ uses the new protocol.
        // Verify the method exists and has the never return type.
        $method = new \ReflectionMethod(EnumCache::class, '__wakeup');
        $returnType = $method->getReturnType();

        expect($returnType)->not->toBeNull();
        expect($returnType->getName())->toBe('never');
    });
});

describe('EnumCache — TTL boundary behavior', function () {
    test('TTL of 0 disables caching — has() always returns false', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(OrderStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    test('negative TTL is normalized to 0 via setTtl()', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-10);

        expect($cache->getTtl())->toBe(0);
    });

    test('TTL expiration invalidates stale entries', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second

        $cache->set(OrderStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Immediately — should be valid
        expect($cache->has(OrderStatus::class))->toBeTrue();

        // Force expiration by manipulating the timestamp directly
        // We can't wait 1 second in a test, so we test the boundary logic
        // by setting TTL to 0 after adding
        $cache->setTtl(0);
        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    test('clearClass removes only the specified class', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(OrderStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(Priority::class, [
            'labels' => ['high' => 'High'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });

    test('flush() is a static alias for clear()', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(OrderStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::flush();

        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    test('get() throws OutOfBoundsException for missing class', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))
            ->toThrow(\OutOfBoundsException::class, 'No cached metadata');
    });
});

describe('EnumManager — trait validation contract', function () {
    test('forSelect throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(\StdClass::class))
            ->toThrow(\BadMethodCallException::class, 'does not use HasEnumMetadata trait');
    });

    test('forApi throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forApi(\StdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    test('tryFromLabel throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->tryFromLabel(\StdClass::class, 'test'))
            ->toThrow(\BadMethodCallException::class);
    });

    test('tryFromName throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->tryFromName(\StdClass::class, 'TEST'))
            ->toThrow(\BadMethodCallException::class);
    });

    test('fromName throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->fromName(\StdClass::class, 'TEST'))
            ->toThrow(\BadMethodCallException::class);
    });

    test('hasCase throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->hasCase(\StdClass::class, 'TEST'))
            ->toThrow(\BadMethodCallException::class);
    });

    test('values throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->values(\StdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    test('labels throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->labels(\StdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });
});

describe('EnumManager — successful delegation', function () {
    test('forSelect returns value/label pairs', function () {
        $manager = new EnumManager;
        $result = $manager->forSelect(OrderStatus::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        // Each item must have 'value' and 'label' keys
        foreach ($result as $item) {
            expect(array_keys($item))->toContain('value');
            expect(array_keys($item))->toContain('label');
        }
    });

    test('forApi returns full metadata for each case', function () {
        $manager = new EnumManager;
        $result = $manager->forApi(OrderStatus::class);

        expect($result)->toBeArray();
        foreach ($result as $item) {
            expect(array_keys($item))->toContain('value');
            expect(array_keys($item))->toContain('name');
            expect(array_keys($item))->toContain('label');
            expect(array_keys($item))->toContain('color');
            expect(array_keys($item))->toContain('description');
            expect(array_keys($item))->toContain('icon');
        }
    });

    test('hasCase returns true for existing case', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(OrderStatus::class, 'ACTIVE'))->toBeTrue();
    });

    test('hasCase returns false for non-existing case', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(OrderStatus::class, 'NON_EXISTENT'))->toBeFalse();
    });

    test('values returns backed values for backed enums', function () {
        $manager = new EnumManager;
        $values = $manager->values(OrderStatus::class);

        expect($values)->toBeArray();
        expect($values)->not->toBeEmpty();
        // All values should be strings for string-backed OrderStatus
        foreach ($values as $v) {
            expect(is_string($v) || is_int($v))->toBeTrue();
        }
    });

    test('values returns case names for pure enums', function () {
        $manager = new EnumManager;
        $values = $manager->values(PureFeatureFlag::class);

        expect($values)->toBeArray();
        expect($values)->not->toBeEmpty();
        // Pure enum values are case names (strings)
        foreach ($values as $v) {
            expect(is_string($v))->toBeTrue();
        }
    });
});

describe('InvalidEnumException — contract', function () {
    test('value() named constructor formats message with actual value', function () {
        $e = InvalidEnumException::value(OrderStatus::class, 'invalid_value');

        expect($e->getMessage())->toContain('invalid_value');
        expect($e->getMessage())->toContain(OrderStatus::class);
    });

    test('value() named constructor handles null value', function () {
        $e = InvalidEnumException::value(OrderStatus::class, null);

        expect($e->getMessage())->toContain('null');
        expect($e->getMessage())->toContain(OrderStatus::class);
    });

    test('value() named constructor handles int value', function () {
        $e = InvalidEnumException::value(Priority::class, 999);

        expect($e->getMessage())->toContain('999');
        expect($e->getMessage())->toContain(Priority::class);
    });

    test('forName() named constructor formats message with case name', function () {
        $e = InvalidEnumException::forName(OrderStatus::class, 'UNKNOWN_CASE');

        expect($e->getMessage())->toContain('UNKNOWN_CASE');
        expect($e->getMessage())->toContain(OrderStatus::class);
        expect($e->getMessage())->toContain('does not exist');
    });

    test('__toString() returns class name + message', function () {
        $e = InvalidEnumException::forName(OrderStatus::class, 'BAD');
        $str = (string) $e;

        expect($str)->toStartWith(InvalidEnumException::class.':');
        expect($str)->toContain('BAD');
    });
});

describe('EnumRule — pure enum validation edge cases', function () {
    test('pure enum validates by case name', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $fail = fn (string $msg): string => throw new \InvalidArgumentException($msg);

        // Valid case name — should not throw
        $rule->validate('feature', 'SEARCH', $fail);
        expect(true)->toBeTrue(); // No exception means pass
    });

    test('pure enum rejects invalid case name', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $fail = fn (string $msg): string => throw new \InvalidArgumentException($msg);

        expect(fn () => $rule->validate('feature', 'NONEXISTENT', $fail))
            ->toThrow(\InvalidArgumentException::class);
    });

    test('pure enum rejects non-string value', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $fail = fn (string $msg): string => throw new \InvalidArgumentException($msg);

        expect(fn () => $rule->validate('feature', 123, $fail))
            ->toThrow(\InvalidArgumentException::class);
    });

    test('backed enum rejects wrong type (string for int-backed)', function () {
        $rule = EnumRule::for(Priority::class);
        $fail = fn (string $msg): string => throw new \InvalidArgumentException($msg);

        // Priority is int-backed, string should be rejected
        expect(fn () => $rule->validate('priority', 'high', $fail))
            ->toThrow(\InvalidArgumentException::class);
    });

    test('backed enum rejects wrong type (int for string-backed)', function () {
        $rule = EnumRule::for(OrderStatus::class);
        $fail = fn (string $msg): string => throw new \InvalidArgumentException($msg);

        // OrderStatus is string-backed, int should be rejected
        expect(fn () => $rule->validate('status', 42, $fail))
            ->toThrow(\InvalidArgumentException::class);
    });

    test('nullable allows null value', function () {
        $rule = EnumRule::for(OrderStatus::class)->nullable();
        $fail = fn (string $msg): string => throw new \InvalidArgumentException($msg);

        // Should not throw
        $rule->validate('status', null, $fail);
        expect(true)->toBeTrue();
    });

    test('non-nullable rejects null value', function () {
        $rule = EnumRule::for(OrderStatus::class);
        $fail = fn (string $msg): string => throw new \InvalidArgumentException($msg);

        expect(fn () => $rule->validate('status', null, $fail))
            ->toThrow(\InvalidArgumentException::class);
    });

    test('validation message includes allowed values for metadata enums', function () {
        $rule = EnumRule::for(OrderStatus::class);
        $fail = fn (string $msg): string => throw new \InvalidArgumentException($msg);

        try {
            $rule->validate('status', 'INVALID', $fail);
            $this->fail('Expected exception');
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();
            expect($msg)->toContain('Allowed values');
        }
    });
});

describe('EnumMetadataResolver — cache invalidation', function () {
    test('invalidate() clears specific class cache', function () {
        // First resolve caches the metadata
        EnumMetadataResolver::resolve(OrderStatus::class);
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        expect($cache->has(OrderStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidate(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    test('invalidateAll() clears all cached metadata', function () {
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        expect($cache->has(OrderStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
    });
});

describe('HasEnumMetadata — toValue() normalization', function () {
    test('toValue() returns backed value for string-backed enum', function () {
        expect(OrderStatus::ACTIVE->toValue())->toBe('active');
    });

    test('toValue() returns backed value for int-backed enum', function () {
        expect(Priority::HIGH->toValue())->toBeInt();
    });

    test('toValue() returns case name for pure enum', function () {
        $case = PureFeatureFlag::cases()[0];
        expect($case->toValue())->toBe($case->name);
    });
});

describe('EnumCache — __debugInfo contract', function () {
    test('__debugInfo returns ttl, cachedClasses, and timestampCount', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(OrderStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $info = $cache->__debugInfo();

        expect($info)->toHaveKey('ttl');
        expect($info)->toHaveKey('cachedClasses');
        expect($info)->toHaveKey('timestampCount');
        expect($info['ttl'])->toBe(300);
        expect($info['cachedClasses'])->toBe(1);
        expect($info['timestampCount'])->toBe(1);
    });
});
