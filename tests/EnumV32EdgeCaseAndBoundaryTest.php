<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * V32 — Edge case and boundary tests for enum metadata resolution.
 *
 * Targets real coverage gaps:
 * - forApi/forSelect structural integrity with all metadata types
 * - forSelect preserves case declaration order
 * - values()/labels() consistency with forApi()/forSelect()
 * - EnumCache TTL boundary behavior (exact expiry)
 * - Cache isolation between different enum classes
 * - fromName throws InvalidEnumException with correct message format
 * - tryFromLabel case-insensitive matching
 * - is()/isNot()/in()/notIn() with mixed instances and strings
 * - int-backed enum value handling
 * - pure enum value/label behavior
 * - EnumMetadataResolver::invalidate() per-class clearing
 * - EnumCache setTtl boundary conditions
 * - zero-backed int enum handling
 * - single case enum behavior
 */
test('forApi returns consistent structure for all enum types', function (): void {
    $api = UserStatus::forApi();
    $cases = UserStatus::cases();

    expect($api)->toBeArray();
    expect(count($api))->toBe(count($cases));

    foreach ($api as $item) {
        expect($item)->toBeArray();
        expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect(is_string($item['label']))->toBeTrue();
        expect(is_string($item['color']))->toBeTrue();
    }
});

test('forSelect preserves case declaration order', function (): void {
    $select = UserStatus::forSelect();
    $cases = UserStatus::cases();

    expect(count($select))->toBe(count($cases));

    for ($i = 0; $i < count($cases); $i++) {
        $expectedValue = $cases[$i] instanceof \BackedEnum ? $cases[$i]->value : $cases[$i]->name;
        expect($select[$i]['value'])->toBe($expectedValue);
    }
});

test('forSelect and forApi values match', function (): void {
    $select = UserStatus::forSelect();
    $api = UserStatus::forApi();

    expect(count($select))->toBe(count($api));

    for ($i = 0; $i < count($select); $i++) {
        expect($select[$i]['value'])->toBe($api[$i]['value']);
        expect($select[$i]['label'])->toBe($api[$i]['label']);
    }
});

test('values() returns same values as forSelect value keys', function (): void {
    $values = UserStatus::values();
    $select = UserStatus::forSelect();

    $selectValues = array_column($select, 'value');
    expect($values)->toBe($selectValues);
});

test('labels() returns same labels as forSelect label keys', function (): void {
    $labels = UserStatus::labels();
    $select = UserStatus::forSelect();

    $selectLabels = array_column($select, 'label');
    expect($labels)->toBe($selectLabels);
});

test('fromName throws InvalidEnumException with class and name in message', function (): void {
    $this->expectException(InvalidEnumException::class);
    $this->expectExceptionMessage('Case name [NONEXISTENT] does not exist on enum');

    UserStatus::fromName('NONEXISTENT');
});

test('tryFromName returns null for nonexistent case', function (): void {
    expect(UserStatus::tryFromName('NONEXISTENT'))->toBeNull();
});

test('fromName returns correct case for valid name', function (): void {
    expect(UserStatus::fromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
});

test('tryFromLabel returns null when no label matches', function (): void {
    expect(UserStatus::tryFromLabel('this-label-does-not-exist'))->toBeNull();
});

test('hasCase returns correct boolean', function (): void {
    expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
    expect(UserStatus::hasCase('active'))->toBeFalse(); // case name, not value
    expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
});

test('is() comparison works with both instance and string', function (): void {
    $status = UserStatus::ACTIVE;

    expect($status->is(UserStatus::ACTIVE))->toBeTrue();
    expect($status->is('ACTIVE'))->toBeTrue();
    expect($status->is(UserStatus::INACTIVE))->toBeFalse();
    expect($status->is('INACTIVE'))->toBeFalse();
});

test('isNot() is inverse of is()', function (): void {
    $status = UserStatus::ACTIVE;

    expect($status->isNot(UserStatus::ACTIVE))->toBeFalse();
    expect($status->isNot('ACTIVE'))->toBeFalse();
    expect($status->isNot(UserStatus::INACTIVE))->toBeTrue();
    expect($status->isNot('INACTIVE'))->toBeTrue();
});

test('in() matches any in the list', function (): void {
    $status = UserStatus::ACTIVE;

    expect($status->in([UserStatus::ACTIVE]))->toBeTrue();
    expect($status->in(['ACTIVE']))->toBeTrue();
    expect($status->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
    expect($status->in([UserStatus::INACTIVE, UserStatus::PENDING]))->toBeFalse();
    expect($status->in([]))->toBeFalse();
});

test('notIn() is inverse of in()', function (): void {
    $status = UserStatus::ACTIVE;

    expect($status->notIn([UserStatus::ACTIVE]))->toBeFalse();
    expect($status->notIn(['ACTIVE']))->toBeFalse();
    expect($status->notIn([UserStatus::INACTIVE, UserStatus::PENDING]))->toBeTrue();
    expect($status->notIn([]))->toBeTrue();
});

test('in() accepts mixed instances and strings', function (): void {
    $status = UserStatus::ACTIVE;

    expect($status->in([UserStatus::INACTIVE, 'ACTIVE']))->toBeTrue();
    expect($status->in(['INACTIVE', UserStatus::PENDING]))->toBeFalse();
});

test('int-backed enum forSelect uses int values', function (): void {
    $select = Priority::forSelect();

    foreach ($select as $item) {
        expect(is_int($item['value']))->toBeTrue();
        expect(is_string($item['label']))->toBeTrue();
    }

    $values = array_column($select, 'value');
    expect($values)->toBe([1, 2, 3, 4]); // LOW=1, MEDIUM=2, HIGH=3, URGENT=4
});

test('int-backed enum values() returns int array', function (): void {
    expect(Priority::values())->toBe([1, 2, 3, 4]);
});

test('int-backed enum fromName works correctly', function (): void {
    expect(Priority::fromName('LOW'))->toBe(Priority::LOW);
    expect(Priority::fromName('URGENT'))->toBe(Priority::URGENT);
});

test('int-backed enum tryFromName returns null for invalid', function (): void {
    expect(Priority::tryFromName('CRITICAL'))->toBeNull();
});

test('pure enum forSelect uses case names as values', function (): void {
    $select = PureFeatureFlag::forSelect();

    foreach ($select as $item) {
        expect(is_string($item['value']))->toBeTrue();
    }

    $values = array_column($select, 'value');
    $names = array_map(fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());
    expect($values)->toBe($names);
});

test('pure enum values() returns case names', function (): void {
    $values = PureFeatureFlag::values();
    $names = array_map(fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());

    expect($values)->toBe($names);
});

test('pure enum comparison methods work', function (): void {
    $flag = PureFeatureFlag::cases()[0];

    expect($flag->is($flag))->toBeTrue();
    expect($flag->is($flag->name))->toBeTrue();
    expect($flag->isNot($flag))->toBeFalse();
});

test('color defaults to secondary when no attribute or class-level', function (): void {
    foreach (Priority::cases() as $case) {
        expect($case->color())->toBe('secondary');
    }
});

test('icon defaults to null when no attribute set', function (): void {
    foreach (Priority::cases() as $case) {
        expect($case->icon())->toBeNull();
    }
});

test('description defaults to null when no attribute set', function (): void {
    foreach (Priority::cases() as $case) {
        expect($case->description())->toBeNull();
    }
});

test('EnumCache resetInstance clears all cached metadata', function (): void {
    EnumCache::resetInstance();

    $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
    expect($meta1)->toBeArray();

    $cache = EnumCache::getInstance();
    expect($cache->has(UserStatus::class))->toBeTrue();

    EnumCache::resetInstance();

    $cache = EnumCache::getInstance();
    expect($cache->has(UserStatus::class))->toBeFalse();
});

test('EnumMetadataResolver invalidate clears specific class', function (): void {
    EnumCache::resetInstance();

    EnumMetadataResolver::resolve(UserStatus::class);
    EnumMetadataResolver::resolve(Priority::class);

    $cache = EnumCache::getInstance();
    expect($cache->has(UserStatus::class))->toBeTrue();
    expect($cache->has(Priority::class))->toBeTrue();

    EnumMetadataResolver::invalidate(UserStatus::class);

    expect($cache->has(UserStatus::class))->toBeFalse();
    expect($cache->has(Priority::class))->toBeTrue(); // Still cached
});

test('EnumMetadataResolver invalidateAll clears everything', function (): void {
    EnumCache::resetInstance();

    EnumMetadataResolver::resolve(UserStatus::class);
    EnumMetadataResolver::resolve(Priority::class);

    EnumMetadataResolver::invalidateAll();

    $cache = EnumCache::getInstance();
    expect($cache->has(UserStatus::class))->toBeFalse();
    expect($cache->has(Priority::class))->toBeFalse();
});

test('EnumCache setTtl with zero disables caching', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();
    $cache->setTtl(0);

    expect($cache->has(UserStatus::class))->toBeFalse();
    expect($cache->getTtl())->toBe(0);

    // Even after setting, has() should return false with TTL=0
    $cache->set(UserStatus::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
    expect($cache->has(UserStatus::class))->toBeFalse();

    // Restore
    $cache->setTtl(300);
});

test('EnumCache setTtl clamps negative values to zero', function (): void {
    $cache = EnumCache::getInstance();
    $cache->setTtl(-100);

    expect($cache->getTtl())->toBe(0);

    $cache->setTtl(300);
});

test('EnumCache get throws OutOfBoundsException for missing entry', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();

    $this->expectException(\OutOfBoundsException::class);
    $cache->get('NonExistentEnum');
});

test('zero-backed int enum value handling', function (): void {
    $case = ZeroPriority::ZERO;

    expect($case->value)->toBe(0);
    expect($case->label())->toBeString();
    expect($case->color())->toBeString();

    $select = ZeroPriority::forSelect();
    expect($select)->toHaveCount(2);

    $zeroItem = array_filter($select, fn (array $item): bool => $item['value'] === 0);
    expect(count($zeroItem))->toBe(1);
});

test('single case enum works correctly', function (): void {
    $cases = SingleCaseToggle::cases();
    expect(count($cases))->toBe(1);

    $toggle = SingleCaseToggle::ON;
    expect($toggle->is('ON'))->toBeTrue();
    expect($toggle->in(['ON']))->toBeTrue();
    expect($toggle->notIn(['OFF']))->toBeTrue();

    $select = SingleCaseToggle::forSelect();
    expect(count($select))->toBe(1);

    $api = SingleCaseToggle::forApi();
    expect(count($api))->toBe(1);
    expect($api[0])->toHaveKey('value');
    expect($api[0])->toHaveKey('name');
    expect($api[0])->toHaveKey('label');
});

test('forApi includes description when set', function (): void {
    $api = UserStatus::forApi();

    $hasDescription = false;
    foreach ($api as $item) {
        if ($item['description'] !== null) {
            $hasDescription = true;
            break;
        }
    }
    expect($hasDescription)->toBeTrue();
});

test('tryFromLabel is case-insensitive', function (): void {
    expect(UserStatus::tryFromLabel('inactive'))->toBe(UserStatus::INACTIVE);
});

test('forApi and forSelect always return arrays with same count as cases', function (): void {
    $enums = [
        UserStatus::class,
        Priority::class,
        PureFeatureFlag::class,
        SingleCaseToggle::class,
    ];

    foreach ($enums as $enumClass) {
        $caseCount = count($enumClass::cases());
        expect(count($enumClass::forApi()))->toBe($caseCount);
        expect(count($enumClass::forSelect()))->toBe($caseCount);
    }
});

test('EnumCache clear removes specific class entry', function (): void {
    EnumCache::resetInstance();

    EnumMetadataResolver::resolve(UserStatus::class);
    EnumMetadataResolver::resolve(Priority::class);

    $cache = EnumCache::getInstance();
    $cache->clearClass(UserStatus::class);

    expect($cache->has(UserStatus::class))->toBeFalse();
    expect($cache->has(Priority::class))->toBeTrue();
});

test('EnumCache flush clears all entries via static method', function (): void {
    EnumCache::resetInstance();

    EnumMetadataResolver::resolve(UserStatus::class);
    EnumMetadataResolver::resolve(Priority::class);

    EnumCache::flush();

    $cache = EnumCache::getInstance();
    expect($cache->has(UserStatus::class))->toBeFalse();
    expect($cache->has(Priority::class))->toBeFalse();
});

test('InvalidEnumException value named constructor formats message', function (): void {
    $exception = InvalidEnumException::value('SomeEnum', 'invalid_value');
    expect($exception->getMessage())->toBe('Value [invalid_value] is not a valid case of [SomeEnum].');
});

test('InvalidEnumException value named constructor with null value', function (): void {
    $exception = InvalidEnumException::value('SomeEnum', null);
    expect($exception->getMessage())->toBe('Value [null] is not a valid case of [SomeEnum].');
});

test('InvalidEnumException value named constructor with int value', function (): void {
    $exception = InvalidEnumException::value('SomeEnum', 999);
    expect($exception->getMessage())->toBe('Value [999] is not a valid case of [SomeEnum].');
});

test('InvalidEnumException __toString includes class name', function (): void {
    $exception = InvalidEnumException::forName('TestEnum', 'BAD');
    $str = (string) $exception;

    expect($str)->toContain('InvalidEnumException');
    expect($str)->toContain('BAD');
    expect($str)->toContain('TestEnum');
});

test('multiple enum classes cache independently', function (): void {
    EnumCache::resetInstance();

    // Resolve multiple enums
    $userStatusMeta = EnumMetadataResolver::resolve(UserStatus::class);
    $priorityMeta = EnumMetadataResolver::resolve(Priority::class);
    $pureMeta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

    // All should be cached
    $cache = EnumCache::getInstance();
    expect($cache->has(UserStatus::class))->toBeTrue();
    expect($cache->has(Priority::class))->toBeTrue();
    expect($cache->has(PureFeatureFlag::class))->toBeTrue();

    // Re-resolve should return same data
    expect(EnumMetadataResolver::resolve(UserStatus::class))->toBe($userStatusMeta);
    expect(EnumMetadataResolver::resolve(Priority::class))->toBe($priorityMeta);
    expect(EnumMetadataResolver::resolve(PureFeatureFlag::class))->toBe($pureMeta);
});

test('int-backed enum hasCase works with case names not values', function (): void {
    expect(Priority::hasCase('LOW'))->toBeTrue();
    expect(Priority::hasCase('1'))->toBeFalse(); // value, not name
    expect(Priority::hasCase('HIGH'))->toBeTrue();
    expect(Priority::hasCase('MEDIUM'))->toBeTrue();
});

test('int-backed enum tryFromLabel works for auto-generated labels', function (): void {
    // Auto-generated from LOW -> "Low"
    expect(Priority::tryFromLabel('Low'))->toBe(Priority::LOW);
    expect(Priority::tryFromLabel('URGENT'))->toBeNull(); // Not a label, it's a name
});
