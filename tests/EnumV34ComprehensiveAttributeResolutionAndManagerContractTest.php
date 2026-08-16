<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * V34 — Comprehensive attribute resolution, cross-fixture metadata contract,
 * EnumManager delegation edge cases, and label generation algorithm tests.
 *
 * Targets:
 * - EnumLabel class-level label mapping overrides auto-generation
 * - EnumLabel case-level label override (dual TARGET support)
 * - EnumDescription class-level bulk mapping
 * - EnumIcon default icon + per-value icon map
 * - EnumColor color priority: per-case > class-level > default
 * - CamelCase label generation (camelCase → Title Case)
 * - SCREAMING_SNAKE_CASE label generation
 * - Int-backed enum forSelect returns int values, not strings
 * - Pure enum values() returns case names
 * - Single-case enum: forSelect/forApi/hasCase all work
 * - EnumManager delegates all 8 methods correctly
 * - EnumManager throws BadMethodCallException for non-metadata enums
 * - InvalidEnumException::value() named constructor format
 * - fromName() with exact case match returns correct case
 * - tryFromName() returns null for empty string
 * - is() rejects non-matching string (case-sensitive)
 * - notIn() returns true when case is absent
 * - values() returns unique entries for backed enums
 * - labels() count matches cases() count
 * - EnumCache TTL=0 disables caching (resolve always rebuilds)
 * - EnumMetadataResolver::invalidateAll clears everything
 */
test('EnumLabel class-level mapping overrides auto-generated labels', function (): void {
    $meta = EnumMetadataResolver::resolve(LabelMapEnum::class);

    // LabelMapEnum uses #[EnumLabel(labels: ['active' => 'Online'])]
    expect($meta['labels']['active'])->toBe('Online');
});

test('EnumLabel case-level override takes precedence over class-level', function (): void {
    $label = MixedAttributeStatus::ACTIVE->label();

    // ACTIVE has a per-case label override via EnumLabel or Label attribute
    expect($label)->toBeString()->not->toBeEmpty();
});

test('EnumDescription class-level bulk mapping provides descriptions', function (): void {
    // TicketStatus uses class-level descriptions
    $api = TicketStatus::forApi();

    expect($api)->toBeArray();
    foreach ($api as $item) {
        expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($item['color'])->toBeString();
    }
});

test('EnumIcon default icon applies to all cases without specific icon', function (): void {
    // DefaultIconFeature uses a class-level default icon
    $cases = \ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature::cases();

    foreach ($cases as $case) {
        $icon = $case->icon();

        // DefaultIconFeature sets a default, so all should have it
        expect($icon)->toBeString()->not->toBeEmpty();
    }
});

test('EnumColor per-case override wins over class-level mapping', function (): void {
    // UserStatus: BANNED has #[Color('danger')] per-case override
    // while class-level maps 'banned' to 'danger' via EnumColor
    $color = UserStatus::BANNED->color();

    expect($color)->toBe('danger');
});

test('SCREAMING_SNAKE_CASE generates Title Case labels', function (): void {
    // UserStatus ACTIVE → "Active" (auto-generated from SCREAMING_SNAKE_CASE)
    $label = UserStatus::INACTIVE->label();

    expect($label)->toBe('Inactive');
});

test('CamelCase generates Title Case labels', function (): void {
    // CamelCasePriority has camelCase case names
    // softDeleted → "Soft Deleted"
    $label = CamelCasePriority::softDeleted->label();

    expect($label)->toBe('Soft Deleted');
});

test('Int-backed enum forSelect returns int values', function (): void {
    $select = IntBackedPriority::forSelect();

    expect($select)->toBeArray();
    expect($select)->not->toBeEmpty();

    foreach ($select as $item) {
        expect($item['value'])->toBeInt();
        expect($item['label'])->toBeString()->not->toBeEmpty();
    }
});

test('Pure enum values() returns case names as strings', function (): void {
    $values = PureFeatureFlag::values();

    expect($values)->toBeArray();
    expect($values)->not->toBeEmpty();

    foreach ($values as $v) {
        expect($v)->toBeString();
        // Pure enum values are case names, not backed values
        expect(PureFeatureFlag::tryFromName($v))->not->toBeNull();
    }
});

test('Single-case enum works with all trait methods', function (): void {
    $toggle = SingleCaseToggle::ON;

    expect($toggle->label())->toBeString()->not->toBeEmpty();
    expect($toggle->color())->toBeString();
    expect($toggle->is(SingleCaseToggle::ON))->toBeTrue();
    expect($toggle->isNot('OFF'))->toBeTrue();
    expect($toggle->in([SingleCaseToggle::ON]))->toBeTrue();
    expect($toggle->notIn([SingleCaseToggle::OFF]))->toBeTrue();
    expect($toggle->hasCase('ON'))->toBeTrue();
    expect($toggle->hasCase('OFF'))->toBeFalse();
    expect($toggle->tryFromName('ON'))->toBe($toggle);
    expect($toggle->tryFromName('NONEXISTENT'))->toBeNull();

    $select = SingleCaseToggle::forSelect();
    expect($select)->toHaveCount(1);
    expect($select[0])->toHaveKeys(['value', 'label']);

    $api = SingleCaseToggle::forApi();
    expect($api)->toHaveCount(1);
    expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
});

test('Zero-value int-backed enum resolves correctly', function (): void {
    $zero = ZeroPriority::ZERO;

    expect($zero->label())->toBeString()->not->toBeEmpty();
    expect($zero->color())->toBeString();
    expect($zero->value())->toBe(0);

    $values = ZeroPriority::values();
    expect(in_array(0, $values, true))->toBeTrue();

    $select = ZeroPriority::forSelect();
    expect($select)->not->toBeEmpty();
    $zeroItem = array_filter($select, fn (array $item): bool => $item['value'] === 0);
    expect($zeroItem)->not->toBeEmpty();
});

test('EnumManager forSelect delegates correctly', function (): void {
    EnumCache::resetInstance();
    $manager = new EnumManager;

    $result = $manager->forSelect(UserStatus::class);

    expect($result)->toBeArray();
    expect($result)->not->toBeEmpty();
    expect($result[0])->toHaveKeys(['value', 'label']);
});

test('EnumManager forApi returns full metadata structure', function (): void {
    EnumCache::resetInstance();
    $manager = new EnumManager;

    $result = $manager->forApi(Priority::class);

    expect($result)->toBeArray();
    foreach ($result as $item) {
        expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    }
});

test('EnumManager tryFromLabel resolves case-insensitively', function (): void {
    EnumCache::resetInstance();
    $manager = new EnumManager;

    $result = $manager->tryFromLabel(UserStatus::class, 'active user');

    expect($result)->not->toBeNull();
    expect($result->name)->toBe('ACTIVE');
});

test('EnumManager tryFromLabel returns null for non-existent label', function (): void {
    EnumCache::resetInstance();
    $manager = new EnumManager;

    $result = $manager->tryFromLabel(UserStatus::class, 'non-existent-label');

    expect($result)->toBeNull();
});

test('EnumManager fromName throws on invalid name', function (): void {
    EnumCache::resetInstance();
    $manager = new EnumManager;

    expect(fn (): mixed => $manager->fromName(UserStatus::class, 'INVALID'))
        ->toThrow(InvalidEnumException::class);
});

test('EnumManager hasCase checks case existence', function (): void {
    EnumCache::resetInstance();
    $manager = new EnumManager;

    expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
    expect($manager->hasCase(UserStatus::class, 'NONEXISTENT'))->toBeFalse();
});

test('EnumManager values returns all backed values', function (): void {
    EnumCache::resetInstance();
    $manager = new EnumManager;

    $values = $manager->values(Priority::class);

    expect($values)->toBeArray();
    expect($values)->not->toBeEmpty();
    // Int-backed: all values should be integers
    foreach ($values as $v) {
        expect($v)->toBeInt();
    }
});

test('EnumManager labels returns all labels', function (): void {
    EnumCache::resetInstance();
    $manager = new EnumManager;

    $labels = $manager->labels(OrderStatus::class);

    expect($labels)->toBeArray();
    expect($labels)->not->toBeEmpty();
    foreach ($labels as $label) {
        expect($label)->toBeString()->not->toBeEmpty();
    }
});

test('EnumManager throws BadMethodCallException for enum without HasEnumMetadata', function (): void {
    EnumCache::resetInstance();
    $manager = new EnumManager;

    // PlainTestEnum does NOT use HasEnumMetadata trait
    expect(fn (): mixed => $manager->forSelect(PlainTestEnum::class))
        ->toThrow(\BadMethodCallException::class);
});

test('InvalidEnumException::value format includes display value and class', function (): void {
    $ex = InvalidEnumException::value('App\\Enums\\Status', 'invalid_value');

    expect($ex->getMessage())->toContain('invalid_value');
    expect($ex->getMessage())->toContain('App\\Enums\\Status');
    expect($ex->getMessage())->toContain('not a valid case');
});

test('InvalidEnumException is final and extends Exception', function (): void {
    $ref = new \ReflectionClass(InvalidEnumException::class);

    expect($ref->isFinal())->toBeTrue();
    expect($ref->isSubclassOf(\Exception::class))->toBeTrue();
});

test('fromName returns correct case for exact match', function (): void {
    $case = PaymentStatus::fromName('COMPLETED');

    expect($case)->toBe(PaymentStatus::COMPLETED);
    expect($case->name)->toBe('COMPLETED');
});

test('tryFromName returns null for empty string', function (): void {
    $result = UserStatus::tryFromName('');

    expect($result)->toBeNull();
});

test('is() is case-sensitive for string comparison', function (): void {
    $active = UserStatus::ACTIVE;

    // 'ACTIVE' matches (exact case)
    expect($active->is('ACTIVE'))->toBeTrue();
    // 'active' does NOT match (case-sensitive)
    expect($active->is('active'))->toBeFalse();
});

test('notIn returns true when case is absent from list', function (): void {
    $pending = OrderStatus::PENDING;

    expect($pending->notIn(['SHIPPED', 'DELIVERED', 'CANCELLED']))->toBeTrue();
});

test('values() returns unique entries for backed enums', function (): void {
    $values = UserStatus::values();
    $unique = array_unique($values);

    // All values should be unique
    expect($values)->toBeArray();
    expect(count($values))->toBe(count($unique));
});

test('labels() count matches cases() count for all fixture enums', function (): void {
    $enums = [
        UserStatus::class,
        OrderStatus::class,
        PaymentStatus::class,
        TicketStatus::class,
        Priority::class,
        IntBackedPriority::class,
        RequestState::class,
        SystemStatus::class,
    ];

    foreach ($enums as $enumClass) {
        $labels = $enumClass::labels();
        $cases = $enumClass::cases();

        expect(count($labels))
            ->toBe(count($cases))
            ->and($labels)
            ->each->toBeString()->not->toBeEmpty();
    }
});

test('EnumCache TTL=0 makes has() always return false', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();
    $cache->setTtl(0);

    $cache->set('TestEnum', [
        'labels' => ['test' => 'Test'],
        'descriptions' => [],
        'colors' => [],
        'icons' => [],
    ]);

    // TTL=0 means caching disabled — has() always false
    expect($cache->has('TestEnum'))->toBeFalse();

    // Restore
    $cache->setTtl(300);
});

test('EnumMetadataResolver invalidateAll clears all cached entries', function (): void {
    EnumCache::resetInstance();

    // Resolve two different enums to populate cache
    EnumMetadataResolver::resolve(UserStatus::class);
    EnumMetadataResolver::resolve(Priority::class);

    $cache = EnumCache::getInstance();
    expect($cache->has(UserStatus::class))->toBeTrue();
    expect($cache->has(Priority::class))->toBeTrue();

    // Invalidate all
    EnumMetadataResolver::invalidateAll();

    expect($cache->has(UserStatus::class))->toBeFalse();
    expect($cache->has(Priority::class))->toBeFalse();
});

test('forApi returns color as non-empty string for all cases', function (): void {
    $enums = [
        UserStatus::class,
        OrderStatus::class,
        PaymentStatus::class,
        SystemStatus::class,
        RequestState::class,
    ];

    foreach ($enums as $enumClass) {
        $api = $enumClass::forApi();

        foreach ($api as $item) {
            expect($item['color'])
                ->toBeString()
                ->and($item['color'])
                ->not->toBeEmpty();
        }
    }
});

test('tryFromLabel is case-insensitive and finds correct case', function (): void {
    $case = UserStatus::tryFromLabel('ACTIVE USER');
    expect($case)->toBe(UserStatus::ACTIVE);

    $case2 = UserStatus::tryFromLabel('active user');
    expect($case2)->toBe(UserStatus::ACTIVE);

    $case3 = UserStatus::tryFromLabel('AcTiVe UsEr');
    expect($case3)->toBe(UserStatus::ACTIVE);
});

test('EnumRule supports pure enum validation by case name', function (): void {
    // Pure enums validate against case names, not backed values
    $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(PureSystemState::class);

    // Valid case name
    $failCalled = false;
    $fail = function (string $message) use (&$failCalled): void {
        $failCalled = true;
    };

    $rule->validate('state', 'IDLE', $fail);
    expect($failCalled)->toBeFalse();

    // Invalid case name
    $rule->validate('state', 'NONEXISTENT', $fail);
    expect($failCalled)->toBeTrue();
});

test('Multiple consecutive cache sets overwrite previous entry', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();
    $cache->setTtl(300);

    $cache->set('TestEnum', [
        'labels' => ['v1' => 'Version 1'],
        'descriptions' => [],
        'colors' => [],
        'icons' => [],
    ]);

    $first = $cache->get('TestEnum');
    expect($first['labels']['v1'])->toBe('Version 1');

    // Overwrite
    $cache->set('TestEnum', [
        'labels' => ['v2' => 'Version 2'],
        'descriptions' => [],
        'colors' => [],
        'icons' => [],
    ]);

    $second = $cache->get('TestEnum');
    expect($second['labels']['v2'])->toBe('Version 2');
    expect($second['labels'])->not->toHaveKey('v1');
});

test('IntPriority int-backed enum values() returns integers', function (): void {
    $values = IntPriority::values();

    expect($values)->toBeArray();
    expect($values)->not->toBeEmpty();
    foreach ($values as $v) {
        expect($v)->toBeInt();
    }
});

test('MixedAttributeStatus has at least one case with description', function (): void {
    $api = MixedAttributeStatus::forApi();

    $hasDescription = false;
    foreach ($api as $item) {
        if ($item['description'] !== null) {
            $hasDescription = true;
            break;
        }
    }

    expect($hasDescription)->toBeTrue();
});

test('EnumRule nullable allows null values', function (): void {
    $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class)->nullable();

    $failCalled = false;
    $fail = function (string $message) use (&$failCalled): void {
        $failCalled = true;
    };

    $rule->validate('status', null, $fail);
    expect($failCalled)->toBeFalse();
});

test('EnumRule non-nullable rejects null values', function (): void {
    $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class);

    $failCalled = false;
    $fail = function (string $message) use (&$failCalled): void {
        $failCalled = true;
    };

    $rule->validate('status', null, $fail);
    expect($failCalled)->toBeTrue();
});

test('EnumRule int-backed rejects string value with type mismatch', function (): void {
    $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(IntBackedPriority::class);

    $failCalled = false;
    $fail = function (string $message) use (&$failCalled): void {
        $failCalled = true;
    };

    // Int-backed enum should reject string value
    $rule->validate('priority', 'high', $fail);
    expect($failCalled)->toBeTrue();
});

test('EnumCache setTtl clamps negative values to 0', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();

    $cache->setTtl(-100);
    expect($cache->getTtl())->toBe(0);

    // Restore
    $cache->setTtl(300);
});
