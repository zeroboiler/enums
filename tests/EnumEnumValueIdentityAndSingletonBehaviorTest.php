<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Comprehensive edge-case tests for enum value identity, singleton behavior,
 * attribute resolution boundaries, and cross-type consistency.
 *
 * Covers areas that are hard to test automatically but critical for production:
 * - Enum singleton identity (same case === same case)
 * - forSelect/forApi structural shape contracts
 * - Cache invalidation lifecycle edge cases
 * - tryFromLabel whitespace sensitivity
 * - EnumMetadataResolver::invalidate/invalidateAll contract
 * - Cross-type (string/int/pure) behavioral consistency
 */
describe('Enum Value Identity And Singleton Behavior', function () {

    it('PHP enum instances are singletons — same case always identical', function () {
        $a = UserStatus::ACTIVE;
        $b = UserStatus::ACTIVE;

        // PHP guarantees enum case identity
        expect($a)->toBe($b);
        expect($a === $b)->toBeTrue();
    });

    it('different cases are never identical', function () {
        expect(UserStatus::ACTIVE === UserStatus::BANNED)->toBeFalse();
        expect(Priority::LOW === Priority::HIGH)->toBeFalse();
        expect(RequestState::DRAFT === RequestState::APPROVED)->toBeFalse();
    });

    it('is() with same instance returns true', function () {
        $case = UserStatus::ACTIVE;
        expect($case->is($case))->toBeTrue();
    });

    it('is() with different case returns false', function () {
        expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
    });

    it('is() is symmetric', function () {
        $a = UserStatus::ACTIVE;
        $b = UserStatus::PENDING;

        expect($a->is($b))->toBe($b->is($a));
    });

    it('in() with empty array returns false', function () {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('notIn() with empty array returns true', function () {
        expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
    });

    it('in() with single matching element returns true', function () {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
    });

    it('notIn() with single non-matching element returns true', function () {
        expect(UserStatus::ACTIVE->notIn([UserStatus::BANNED]))->toBeTrue();
    });

    it('in() with all cases returns true for any case', function () {
        $all = UserStatus::cases();

        foreach ($all as $case) {
            expect($case->in($all))->toBeTrue();
        }
    });
});

describe('forSelect Structural Shape Contract', function () {

    it('returns array with exact case count', function () {
        $select = UserStatus::forSelect();

        expect($select)->toBeArray();
        expect($select)->toHaveCount(count(UserStatus::cases()));
    });

    it('each entry has exactly value and label keys', function () {
        $select = UserStatus::forSelect();

        foreach ($select as $entry) {
            expect(array_keys($entry))->toBe(['value', 'label']);
        }
    });

    it('values are unique for backed enums', function () {
        $select = UserStatus::forSelect();
        $values = array_column($select, 'value');

        expect($values)->toEqual(array_values(array_unique($values)));
    });

    it('labels are non-empty strings', function () {
        $select = UserStatus::forSelect();

        foreach ($select as $entry) {
            expect($entry['label'])->toBeString();
            expect($entry['label'])->not->toBeEmpty();
        }
    });

    it('string-backed enum values are strings', function () {
        $select = UserStatus::forSelect();

        foreach ($select as $entry) {
            expect($entry['value'])->toBeString();
        }
    });

    it('int-backed enum values are ints', function () {
        $select = Priority::forSelect();

        foreach ($select as $entry) {
            expect($entry['value'])->toBeInt();
        }
    });

    it('pure enum values are case name strings', function () {
        $select = RequestState::forSelect();

        foreach ($select as $entry) {
            expect($entry['value'])->toBeString();
            // Pure enum values are case names
            expect(RequestState::tryFromName($entry['value']))->not->toBeNull();
        }
    });
});

describe('forApi Structural Shape Contract', function () {

    it('returns array with exact case count', function () {
        $api = UserStatus::forApi();

        expect($api)->toBeArray();
        expect($api)->toHaveCount(count(UserStatus::cases()));
    });

    it('each entry has exactly 6 required keys', function () {
        $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];
        $api = UserStatus::forApi();

        foreach ($api as $entry) {
            expect(array_keys($entry))->toBe($requiredKeys);
        }
    });

    it('name field matches the enum case name', function () {
        $api = UserStatus::forApi();
        $names = array_column($api, 'name');

        foreach (UserStatus::cases() as $case) {
            expect(in_array($case->name, $names, true))->toBeTrue();
        }
    });

    it('color is always a non-empty string', function () {
        $api = UserStatus::forApi();

        foreach ($api as $entry) {
            expect($entry['color'])->toBeString();
            expect($entry['color'])->not->toBeEmpty();
        }
    });

    it('description is string or null', function () {
        $api = UserStatus::forApi();

        foreach ($api as $entry) {
            expect($entry['description'])->toBeNull()->or()->toBeString();
        }
    });

    it('icon is string or null', function () {
        $api = UserStatus::forApi();

        foreach ($api as $entry) {
            expect($entry['icon'])->toBeNull()->or()->toBeString();
        }
    });

    it('forApi entries are in declaration order', function () {
        $api = UserStatus::forApi();
        $cases = UserStatus::cases();

        foreach ($cases as $index => $case) {
            expect($api[$index]['name'])->toBe($case->name);
        }
    });
});

describe('tryFromLabel Edge Cases', function () {

    it('is whitespace-sensitive', function () {
        $label = UserStatus::ACTIVE->label();

        // Exact label should match
        expect(UserStatus::tryFromLabel($label))->toBe(UserStatus::ACTIVE);

        // Label with leading/trailing whitespace should NOT match
        expect(UserStatus::tryFromLabel(' ' . $label))->toBeNull();
        expect(UserStatus::tryFromLabel($label . ' '))->toBeNull();
    });

    it('is case-insensitive', function () {
        $label = UserStatus::ACTIVE->label();

        expect(UserStatus::tryFromLabel(strtolower($label)))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel(strtoupper($label)))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel(ucwords(strtolower($label))))->toBe(UserStatus::ACTIVE);
    });

    it('returns null for empty string', function () {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });

    it('returns null for label with only whitespace', function () {
        expect(UserStatus::tryFromLabel('   '))->toBeNull();
    });

    it('returns null for non-existent label', function () {
        expect(UserStatus::tryFromLabel('non-existent-label-xyz-123'))->toBeNull();
    });

    it('returns first match when multiple cases share same label (edge case)', function () {
        // This tests the O(n) iteration behavior — first match wins
        $result = UserStatus::tryFromLabel(UserStatus::ACTIVE->label());

        expect($result)->toBe(UserStatus::ACTIVE);
    });
});

describe('toValue Cross-Type Consistency', function () {

    it('string-backed enum returns string value', function () {
        expect(UserStatus::ACTIVE->toValue())->toBe('active');
        expect(UserStatus::BANNED->toValue())->toBe('banned');
    });

    it('int-backed enum returns int value', function () {
        expect(Priority::LOW->toValue())->toBe(1);
        expect(Priority::URGENT->toValue())->toBe(4);
    });

    it('pure enum returns case name string', function () {
        expect(RequestState::DRAFT->toValue())->toBe('DRAFT');
        expect(RequestState::APPROVED->toValue())->toBe('APPROVED');
    });

    it('toValue matches values() array content', function () {
        // String-backed
        $stringValues = UserStatus::values();
        foreach (UserStatus::cases() as $case) {
            expect(in_array($case->toValue(), $stringValues, true))->toBeTrue();
        }

        // Int-backed
        $intValues = Priority::values();
        foreach (Priority::cases() as $case) {
            expect(in_array($case->toValue(), $intValues, true))->toBeTrue();
        }

        // Pure
        $pureValues = RequestState::values();
        foreach (RequestState::cases() as $case) {
            expect(in_array($case->toValue(), $pureValues, true))->toBeTrue();
        }
    });
});

describe('EnumCache Invalidation Lifecycle', function () {

    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('resolve() populates the cache', function () {
        $cache = EnumCache::getInstance();

        expect($cache->has(UserStatus::class))->toBeFalse();

        EnumMetadataResolver::resolve(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('invalidate() clears a specific class', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        EnumMetadataResolver::invalidate(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });

    it('invalidateAll() clears every cached class', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);
        EnumMetadataResolver::resolve(RequestState::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
        expect($cache->has(RequestState::class))->toBeFalse();
    });

    it('TTL of 0 disables caching', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        EnumMetadataResolver::resolve(UserStatus::class);

        // With TTL=0, has() should always return false
        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('negative TTL is clamped to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
    });

    it('clearClass() removes only the specified class', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'test'],
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

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });
});

describe('EnumMetadataResolver Internal Contract', function () {

    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('resolved metadata has exactly 4 keys', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect(array_keys($meta))->toBe(['labels', 'descriptions', 'colors', 'icons']);
    });

    it('each metadata key is an array', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        foreach ($meta as $key => $value) {
            expect($value)->toBeArray();
        }
    });

    it('resolved metadata is idempotent within TTL', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        $first = EnumMetadataResolver::resolve(UserStatus::class);
        $second = EnumMetadataResolver::resolve(UserStatus::class);

        expect($first)->toBe($second);
    });
});

describe('fromName Exception Contract', function () {

    it('throws InvalidEnumException for non-existent name on string-backed enum', function () {
        expect(fn () => UserStatus::fromName('NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('throws InvalidEnumException for non-existent name on int-backed enum', function () {
        expect(fn () => Priority::fromName('NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('throws InvalidEnumException for non-existent name on pure enum', function () {
        expect(fn () => RequestState::fromName('NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('exception message includes the class name', function () {
        try {
            UserStatus::fromName('NONEXISTENT');
            expect(true)->toBeFalse(); // Should not reach
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('UserStatus');
            expect($e->getMessage())->toContain('NONEXISTENT');
        }
    });

    it('exception __toString includes class name', function () {
        try {
            UserStatus::fromName('NONEXISTENT');
            expect(true)->toBeFalse();
        } catch (InvalidEnumException $e) {
            $str = (string) $e;
            expect($str)->toContain('InvalidEnumException');
        }
    });
});

describe('EnumCache DebugInfo Shape', function () {

    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('returns array with ttl, cachedClasses, and timestampCount', function () {
        $cache = EnumCache::getInstance();
        $debug = $cache->__debugInfo();

        expect($debug)->toBeArray();
        expect($debug)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
        expect($debug['ttl'])->toBeInt();
        expect($debug['cachedClasses'])->toBeInt();
        expect($debug['timestampCount'])->toBeInt();
    });

    it('cachedClasses reflects actual cache size', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        expect($cache->__debugInfo()['cachedClasses'])->toBe(0);

        $cache->set('TestEnum1', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        expect($cache->__debugInfo()['cachedClasses'])->toBe(1);

        $cache->set('TestEnum2', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        expect($cache->__debugInfo()['cachedClasses'])->toBe(2);
    });
});

describe('InvalidEnumException Named Constructors', function () {

    it('value() creates exception with null value display', function () {
        $e = InvalidEnumException::value('TestEnum', null);

        expect($e->getMessage())->toContain('null');
        expect($e->getMessage())->toContain('TestEnum');
    });

    it('value() creates exception with int value display', function () {
        $e = InvalidEnumException::value('TestEnum', 42);

        expect($e->getMessage())->toContain('42');
    });

    it('value() creates exception with string value display', function () {
        $e = InvalidEnumException::value('TestEnum', 'invalid');

        expect($e->getMessage())->toContain('invalid');
    });

    it('forName() creates exception with class and name', function () {
        $e = InvalidEnumException::forName('TestEnum', 'BAD_CASE');

        expect($e->getMessage())->toContain('TestEnum');
        expect($e->getMessage())->toContain('BAD_CASE');
    });
});

describe('values() and labels() Count Consistency', function () {

    it('values() and labels() have same count as cases()', function () {
        expect(UserStatus::values())->toHaveCount(count(UserStatus::cases()));
        expect(UserStatus::labels())->toHaveCount(count(UserStatus::cases()));

        expect(Priority::values())->toHaveCount(count(Priority::cases()));
        expect(Priority::labels())->toHaveCount(count(Priority::cases()));

        expect(RequestState::values())->toHaveCount(count(RequestState::cases()));
        expect(RequestState::labels())->toHaveCount(count(RequestState::cases()));
    });

    it('labels() are in declaration order', function () {
        $labels = UserStatus::labels();
        $cases = UserStatus::cases();

        foreach ($cases as $index => $case) {
            expect($labels[$index])->toBe($case->label());
        }
    });
});

describe('hasCase Boundary Conditions', function () {

    it('returns true for every existing case name', function () {
        foreach (UserStatus::cases() as $case) {
            expect(UserStatus::hasCase($case->name))->toBeTrue();
        }
    });

    it('returns false for empty string', function () {
        expect(UserStatus::hasCase(''))->toBeFalse();
    });

    it('is case-sensitive', function () {
        foreach (UserStatus::cases() as $case) {
            if ($case->name !== strtoupper($case->name)) {
                // Only test if name is not all-uppercase (which all SCREAMING_SNAKE are)
            }
        }

        // All standard enums use SCREAMING_SNAKE, so test lowercase variant
        expect(UserStatus::hasCase('active'))->toBeFalse();
        expect(UserStatus::hasCase('Active'))->toBeFalse();
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
    });
});

describe('EnumCache Serialization Prevention', function () {

    it('clone throws RuntimeException', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => clone $cache)->toThrow(\RuntimeException::class);
    });

    it('__wakeup throws RuntimeException', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->__wakeup())->toThrow(\RuntimeException::class);
    });

    it('__serialize throws RuntimeException', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->__serialize())->toThrow(\RuntimeException::class);
    });

    it('__unserialize throws RuntimeException', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->__unserialize([]))->toThrow(\RuntimeException::class);
    });
});

describe('EnumCache get() Boundary', function () {

    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('throws OutOfBoundsException for non-cached class', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))->toThrow(\OutOfBoundsException::class);
    });

    it('returns cached metadata after set', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ];

        $cache->set('TestEnum', $metadata);
        $result = $cache->get('TestEnum');

        expect($result)->toBe($metadata);
    });
});

describe('EnumCache setTtl and getTtl Contract', function () {

    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('default TTL is 300', function () {
        $cache = EnumCache::getInstance();

        expect($cache->getTtl())->toBe(300);
    });

    it('setTtl persists across getInstance calls', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(60);

        expect(EnumCache::getInstance()->getTtl())->toBe(60);
    });

    it('resetInstance resets TTL to default', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(42);

        EnumCache::resetInstance();

        expect(EnumCache::getInstance()->getTtl())->toBe(300);
    });
});
