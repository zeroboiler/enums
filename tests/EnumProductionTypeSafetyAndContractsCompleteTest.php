<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\{IntBackedPriority, PureFeatureFlag, UserStatus};

test('all enum fixtures use HasEnumMetadata trait', function (): void {
    $usesMeta = fn (string $class): bool => in_array(
        HasEnumMetadata::class,
        array_keys((new \ReflectionClass($class))->getTraits()),
        true,
    );

    expect($usesMeta(UserStatus::class))->toBeTrue()
        ->and($usesMeta(IntBackedPriority::class))->toBeTrue()
        ->and($usesMeta(PureFeatureFlag::class))->toBeTrue();
});

test('string-backed enum forSelect returns correct structure', function (): void {
    $select = UserStatus::forSelect();

    expect($select)->toBeArray()
        ->and(count($select))->toBeGreaterThan(0)
        ->and($select[0])->toHaveKeys(['value', 'label'])
        ->and($select[0]['value'])->toBeString()
        ->and($select[0]['label'])->toBeString();
});

test('string-backed enum forApi returns full metadata structure', function (): void {
    $api = UserStatus::forApi();

    expect($api)->toBeArray()
        ->and(count($api))->toBeGreaterThan(0)
        ->and($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon'])
        ->and($api[0]['value'])->toBeString()
        ->and($api[0]['name'])->toBeString()
        ->and($api[0]['label'])->toBeString()
        ->and($api[0]['color'])->toBeString();
});

test('int-backed enum forSelect returns int values', function (): void {
    $select = IntBackedPriority::forSelect();

    expect($select)->toBeArray()
        ->and(count($select))->toBeGreaterThan(0)
        ->and($select[0])->toHaveKeys(['value', 'label'])
        ->and($select[0]['value'])->toBeInt();
});

test('int-backed enum forApi returns int values', function (): void {
    $api = IntBackedPriority::forApi();

    expect($api)->toBeArray()
        ->and(count($api))->toBeGreaterThan(0)
        ->and($api[0]['value'])->toBeInt()
        ->and($api[0]['name'])->toBeString();
});

test('pure enum forSelect returns case names as values', function (): void {
    $select = PureFeatureFlag::forSelect();

    expect($select)->toBeArray()
        ->and(count($select))->toBeGreaterThan(0)
        ->and($select[0])->toHaveKeys(['value', 'label'])
        ->and($select[0]['value'])->toBeString(); // case name
});

test('pure enum forApi returns case names as values', function (): void {
    $api = PureFeatureFlag::forApi();

    expect($api)->toBeArray()
        ->and(count($api))->toBeGreaterThan(0)
        ->and($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon'])
        ->and($api[0]['value'])->toBe($api[0]['name']); // pure enum: value === name
});

test('values() returns scalar list for backed enums', function (): void {
    $values = UserStatus::values();

    expect($values)->toBeArray()
        ->and(count($values))->toBeGreaterThan(0);

    foreach ($values as $v) {
        expect($v)->toBeString();
    }
});

test('values() returns case names for pure enums', function (): void {
    $values = PureFeatureFlag::values();

    expect($values)->toBeArray()
        ->and(count($values))->toBeGreaterThan(0);

    foreach ($values as $v) {
        expect($v)->toBeString();
    }
});

test('labels() returns string list for all enum types', function (): void {
    $labels = UserStatus::labels();

    expect($labels)->toBeArray()
        ->and(count($labels))->toBeGreaterThan(0);

    foreach ($labels as $label) {
        expect($label)->toBeString();
    }
});

test('comparison methods use strict identity', function (): void {
    $active = UserStatus::ACTIVE;

    expect($active->is(UserStatus::ACTIVE))->toBeTrue()
        ->and($active->is('ACTIVE'))->toBeTrue()
        ->and($active->is('active'))->toBeFalse() // case name, not backed value
        ->and($active->isNot(UserStatus::BANNED))->toBeTrue()
        ->and($active->is(UserStatus::BANNED))->toBeFalse();
});

test('in() and notIn() accept mixed instances and strings', function (): void {
    $active = UserStatus::ACTIVE;

    expect($active->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue()
        ->and($active->in(['ACTIVE', 'PENDING']))->toBeTrue()
        ->and($active->in([UserStatus::BANNED]))->toBeFalse()
        ->and($active->notIn(['BANNED', 'SUSPENDED']))->toBeTrue()
        ->and($active->notIn(['ACTIVE']))->toBeFalse();
});

test('tryFromName resolves by case name', function (): void {
    expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE)
        ->and(UserStatus::tryFromName('active'))->toBeNull() // case-sensitive
        ->and(UserStatus::tryFromName('UNKNOWN'))->toBeNull();
});

test('fromName throws InvalidEnumException for invalid names', function (): void {
    UserStatus::fromName('NON_EXISTENT');
})->throws(InvalidEnumException::class);

test('hasCase checks existence', function (): void {
    expect(UserStatus::hasCase('ACTIVE'))->toBeTrue()
        ->and(UserStatus::hasCase('NON_EXISTENT'))->toBeFalse();
});

test('tryFromLabel is case-insensitive', function (): void {
    $case = UserStatus::tryFromLabel('Active User');

    expect($case)->not->toBeNull()
        ->and($case)->toBe(UserStatus::ACTIVE);
});

test('label auto-generates from SCREAMING_SNAKE_CASE', function (): void {
    $label = UserStatus::INACTIVE->label();

    expect($label)->toBe('Inactive');
});

test('color defaults to secondary when not defined', function (): void {
    $color = UserStatus::INACTIVE->color();

    expect($color)->toBe('secondary');
});

test('description returns null when not defined', function (): void {
    $desc = UserStatus::INACTIVE->description();

    expect($desc)->toBeNull();
});

test('icon returns null when not defined', function (): void {
    $icon = UserStatus::INACTIVE->icon();

    expect($icon)->toBeNull();
});

test('EnumRule validates string-backed enum values', function (): void {
    $rule = EnumRule::for(UserStatus::class);
    $fail = fn (): mixed => null;

    // Valid value should not fail
    $error = null;
    $rule->validate('status', 'active', fn (string $m): mixed => ($error = $m) ?: 'fail');

    expect($error)->toBeNull();
});

test('EnumRule rejects invalid values', function (): void {
    $rule = EnumRule::for(UserStatus::class);
    $error = null;

    $rule->validate('status', 'nonexistent', fn (string $m): mixed => ($error = $m) ?: 'fail');

    expect($error)->not->toBeNull()
        ->and($error)->toBeString();
});

test('EnumRule nullable allows null', function (): void {
    $rule = EnumRule::for(UserStatus::class)->nullable();
    $error = null;

    $rule->validate('status', null, fn (string $m): mixed => ($error = $m) ?: 'fail');

    expect($error)->toBeNull();
});

test('EnumRule rejects null when not nullable', function (): void {
    $rule = EnumRule::for(UserStatus::class);
    $error = null;

    $rule->validate('status', null, fn (string $m): mixed => ($error = $m) ?: 'fail');

    expect($error)->not->toBeNull();
});

test('EnumCast get returns null for null values', function (): void {
    $cast = new EnumCast(UserStatus::class);
    $result = $cast->get(new stdClass, 'status', null, []);

    expect($result)->toBeNull();
});

test('EnumCast get returns enum instance for valid value', function (): void {
    $cast = new EnumCast(UserStatus::class);
    $result = $cast->get(new stdClass, 'status', 'active', []);

    expect($result)->toBe(UserStatus::ACTIVE);
});

test('EnumCast get returns null for invalid value', function (): void {
    $cast = new EnumCast(UserStatus::class);
    $result = $cast->get(new stdClass, 'status', 'nonexistent', []);

    expect($result)->toBeNull();
});

test('EnumCast set returns backed value for enum instance', function (): void {
    $cast = new EnumCast(UserStatus::class);
    $result = $cast->set(new stdClass, 'status', UserStatus::ACTIVE, []);

    expect($result)->toBe('active');
});

test('EnumCast set returns raw value for valid int/string', function (): void {
    $cast = new EnumCast(UserStatus::class);
    $result = $cast->set(new stdClass, 'status', 'active', []);

    expect($result)->toBe('active');
});

test('EnumCast set throws for invalid raw value', function (): void {
    $cast = new EnumCast(UserStatus::class);

    $cast->set(new stdClass, 'status', 'nonexistent', []);
})->throws(InvalidArgumentException::class);

test('EnumCast set returns null for null', function (): void {
    $cast = new EnumCast(UserStatus::class);
    $result = $cast->set(new stdClass, 'status', null, []);

    expect($result)->toBeNull();
});

test('EnumCast serialize returns backed value', function (): void {
    $cast = new EnumCast(UserStatus::class);
    $result = $cast->serialize(new stdClass, 'status', UserStatus::ACTIVE, []);

    expect($result)->toBe('active');
});

test('EnumCast serialize passes through int/string', function (): void {
    $cast = new EnumCast(UserStatus::class);
    $result = $cast->serialize(new stdClass, 'status', 'active', []);

    expect($result)->toBe('active');
});

test('EnumCast serialize returns null for null', function (): void {
    $cast = new EnumCast(UserStatus::class);
    $result = $cast->serialize(new stdClass, 'status', null, []);

    expect($result)->toBeNull();
});

test('InvalidEnumException factory methods produce correct messages', function (): void {
    $e1 = InvalidEnumException::value(UserStatus::class, 'invalid');
    expect($e1->getMessage())->toContain('invalid')
        ->and($e1->getMessage())->toContain(UserStatus::class);

    $e2 = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');
    expect($e2->getMessage())->toContain('NONEXISTENT')
        ->and($e2->getMessage())->toContain('Case name');

    // __toString format
    expect((string) $e1)->toContain(InvalidEnumException::class);
});

test('EnumCache singleton behavior', function (): void {
    $cache = \ZeroBoiler\Enums\EnumCache::getInstance();

    expect($cache)->toBeInstanceOf(\ZeroBoiler\Enums\EnumCache::class);

    // Same instance returned
    $cache2 = \ZeroBoiler\Enums\EnumCache::getInstance();
    expect($cache)->toBe($cache2);
});

test('EnumCache set/get/has/clear cycle', function (): void {
    $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
    $cache->clear(); // start fresh

    expect($cache->has('TestClass'))->toBeFalse();

    $metadata = [
        'labels' => ['active' => 'Active'],
        'descriptions' => [],
        'colors' => [],
        'icons' => [],
    ];
    $cache->set('TestClass', $metadata);

    expect($cache->has('TestClass'))->toBeTrue()
        ->and($cache->get('TestClass'))->toBe($metadata);

    $cache->clear();
    expect($cache->has('TestClass'))->toBeFalse();
});

test('EnumCache clearClass only clears specific class', function (): void {
    $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
    $cache->clear();

    $cache->set('ClassA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
    $cache->set('ClassB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

    $cache->clearClass('ClassA');

    expect($cache->has('ClassA'))->toBeFalse()
        ->and($cache->has('ClassB'))->toBeTrue();

    $cache->clear();
});

test('EnumCache get throws OutOfBoundsException for missing key', function (): void {
    $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
    $cache->clear();

    $cache->get('Nonexistent');
})->throws(OutOfBoundsException::class);

test('EnumCache setTtl and getTtl', function (): void {
    $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
    $originalTtl = $cache->getTtl();

    $cache->setTtl(60);
    expect($cache->getTtl())->toBe(60);

    // Negative values clamped to 0
    $cache->setTtl(-10);
    expect($cache->getTtl())->toBe(0);

    // Restore
    $cache->setTtl($originalTtl);
});

test('EnumCache TTL=0 disables caching', function (): void {
    $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
    $cache->clear();
    $cache->setTtl(0);

    $cache->set('TestClass', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

    expect($cache->has('TestClass'))->toBeFalse(); // TTL=0 means always stale

    $cache->setTtl(300);
});
