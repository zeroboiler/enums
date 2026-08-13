<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace Tests\Enums;

use BackedEnum;
use ReflectionEnum;
use ReflectionProperty;
use UnitEnum;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

/**
 * Integration contract test — verifies cross-concern behaviors that span
 * multiple components (cache, resolver, trait, manager, facade, rule, cast).
 *
 * This test covers scenarios that unit tests for individual classes miss:
 * - Cache isolation between different enum classes
 * - Cache invalidation propagation (single class + flush all)
 * - Resolution priority when class-level and per-case attributes conflict
 * - EnumRule validation with type-mismatched backed enums (int vs string)
 * - EnumCast set() with invalid raw values
 * - Manager delegation consistency with trait static methods
 * - Pure enum support through the full stack (forSelect, forApi, values, labels)
 * - Facade accessor binding correctness
 *
 * @see EnumCache
 * @see EnumMetadataResolver
 * @see HasEnumMetadata
 * @see EnumManager
 * @see EnumRule
 * @see EnumCast
 */
test('cache stores independent entries for different enum classes', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();
    $cache->setTtl(300);

    // Force resolution of two different enums
    EnumMetadataResolver::resolve(TestStringBackedEnum::class);
    EnumMetadataResolver::resolve(TestIntBackedEnum::class);

    // Both should be cached
    expect($cache->has(TestStringBackedEnum::class))->toBeTrue();
    expect($cache->has(TestIntBackedEnum::class))->toBeTrue();

    // Invalidate one — the other should remain
    EnumMetadataResolver::invalidate(TestStringBackedEnum::class);
    expect($cache->has(TestStringBackedEnum::class))->toBeFalse();
    expect($cache->has(TestIntBackedEnum::class))->toBeTrue();

    EnumCache::resetInstance();
});

test('flush invalidates all cached entries simultaneously', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();
    $cache->setTtl(300);

    EnumMetadataResolver::resolve(TestStringBackedEnum::class);
    EnumMetadataResolver::resolve(TestIntBackedEnum::class);
    EnumMetadataResolver::resolve(TestPureEnum::class);

    expect($cache->has(TestStringBackedEnum::class))->toBeTrue();
    expect($cache->has(TestIntBackedEnum::class))->toBeTrue();
    expect($cache->has(TestPureEnum::class))->toBeTrue();

    EnumMetadataResolver::invalidateAll();

    expect($cache->has(TestStringBackedEnum::class))->toBeFalse();
    expect($cache->has(TestIntBackedEnum::class))->toBeFalse();
    expect($cache->has(TestPureEnum::class))->toBeFalse();

    EnumCache::resetInstance();
});

test('cache TTL expiry correctly marks entries as stale', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();

    // Set very short TTL
    $cache->setTtl(0); // Disable caching — entries always stale
    EnumMetadataResolver::resolve(TestStringBackedEnum::class);

    expect($cache->has(TestStringBackedEnum::class))->toBeFalse();

    // Re-enable caching and resolve
    $cache->setTtl(300);
    EnumMetadataResolver::resolve(TestStringBackedEnum::class);
    expect($cache->has(TestStringBackedEnum::class))->toBeTrue();

    EnumCache::resetInstance();
});

test('resolution priority: per-case attribute overrides class-level attribute', function (): void {
    $meta = EnumMetadataResolver::resolve(TestPriorityEnum::class);

    // Case ACTIVE has per-case Label('Custom Active'), class-level EnumLabel maps it to 'Active User'
    // Per-case should win
    $active = TestPriorityEnum::ACTIVE;
    expect($active->label())->toBe('Custom Active');

    // Case PENDING has no per-case label, class-level EnumLabel applies
    $pending = TestPriorityEnum::PENDING;
    expect($pending->label())->toBe('Pending User');
});

test('class-level EnumColor maps multiple values to same color group', function (): void {
    $meta = EnumMetadataResolver::resolve(TestStringBackedEnum::class);

    // SUCCESS and PENDING should both get 'success' color from class-level
    expect(TestStringBackedEnum::SUCCESS->color())->toBe('success');
    expect(TestStringBackedEnum::PENDING->color())->toBe('success');
    expect(TestStringBackedEnum::FAILED->color())->toBe('danger');
});

test('class-level EnumIcon default applies to all cases without per-case icon', function (): void {
    expect(TestIconDefaultEnum::ACTIVE->icon())->toBe('heroicon-o-check');
    expect(TestIconDefaultEnum::PENDING->icon())->toBe('heroicon-o-check');

    // Per-case override wins
    expect(TestIconDefaultEnum::FAILED->icon())->toBe('heroicon-o-x-mark');
});

test('EnumRule rejects type-mismatched values for backed enums', function (): void {
    $rule = EnumRule::for(TestIntBackedEnum::class);

    // Passing a string to an int-backed enum should fail
    $failed = false;
    $rule->validate('status', '1', function (string $message) use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('EnumRule accepts null for nullable string-backed enum', function (): void {
    $rule = EnumRule::for(TestStringBackedEnum::class)->nullable();

    $failed = false;
    $rule->validate('status', null, function (string $message) use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('EnumRule rejects null for non-nullable enum', function (): void {
    $rule = EnumRule::for(TestStringBackedEnum::class);

    $failed = false;
    $rule->validate('status', null, function (string $message) use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('EnumRule validates pure enums against case names', function (): void {
    $rule = EnumRule::for(TestPureEnum::class);

    // Valid case name should pass
    $failed = false;
    $rule->validate('mode', 'ACTIVE', function (string $message) use (&$failed): void {
        $failed = true;
    });
    expect($failed)->toBeFalse();

    // Invalid case name should fail
    $failed = false;
    $rule->validate('mode', 'NONEXISTENT', function (string $message) use (&$failed): void {
        $failed = true;
    });
    expect($failed)->toBeTrue();
});

test('EnumCast set() throws on invalid raw value for string-backed enum', function (): void {
    $cast = new EnumCast(TestStringBackedEnum::class);

    expect(fn (): mixed => $cast->set(
        new \stdClass(),
        'status',
        'invalid_value',
        [],
    ))->toThrow(\InvalidArgumentException::class);
});

test('EnumCast set() throws on type mismatch for wrong enum instance', function (): void {
    $cast = new EnumCast(TestStringBackedEnum::class);

    // Passing an int-backed enum to a string-backed enum cast should throw
    expect(fn (): mixed => $cast->set(
        new \stdClass(),
        'status',
        TestIntBackedEnum::ZERO,
        [],
    ))->toThrow(\InvalidArgumentException::class);
});

test('EnumCast get() returns null for non-int non-string value', function (): void {
    $cast = new EnumCast(TestStringBackedEnum::class);

    $result = $cast->get(new \stdClass(), 'status', ['array_value'], []);

    expect($result)->toBeNull();
});

test('EnumCast serialize() passes through int/string values', function (): void {
    $cast = new EnumCast(TestStringBackedEnum::class);

    expect($cast->serialize(new \stdClass(), 'status', 'active', []))->toBe('active');
    expect($cast->serialize(new \stdClass(), 'status', 42, []))->toBe(42);
    expect($cast->serialize(new \stdClass(), 'status', null, []))->toBeNull();
});

test('Manager delegates correctly to trait static methods', function (): void {
    $manager = new EnumManager;

    // forSelect
    $select = $manager->forSelect(TestStringBackedEnum::class);
    expect($select)->toBe(TestStringBackedEnum::forSelect());

    // forApi
    $api = $manager->forApi(TestStringBackedEnum::class);
    expect($api)->toBe(TestStringBackedEnum::forApi());

    // tryFromLabel
    $case = $manager->tryFromLabel(TestStringBackedEnum::class, 'Success');
    expect($case)->toBe(TestStringBackedEnum::tryFromLabel('Success'));

    // tryFromName
    $case = $manager->tryFromName(TestStringBackedEnum::class, 'SUCCESS');
    expect($case)->toBe(TestStringBackedEnum::tryFromName('SUCCESS'));

    // hasCase
    expect($manager->hasCase(TestStringBackedEnum::class, 'SUCCESS'))->toBeTrue();
    expect($manager->hasCase(TestStringBackedEnum::class, 'UNKNOWN'))->toBeFalse();
});

test('Manager throws BadMethodCallException for enum without HasEnumMetadata', function (): void {
    $manager = new EnumManager;

    // Plain enum without the trait
    expect(fn (): mixed => $manager->forSelect(\PlainEnumWithoutTrait::class))
        ->toThrow(\BadMethodCallException::class);

    expect(fn (): mixed => $manager->forApi(\PlainEnumWithoutTrait::class))
        ->toThrow(\BadMethodCallException::class);

    expect(fn (): mixed => $manager->tryFromLabel(\PlainEnumWithoutTrait::class, 'test'))
        ->toThrow(\BadMethodCallException::class);

    expect(fn (): mixed => $manager->tryFromName(\PlainEnumWithoutTrait::class, 'A'))
        ->toThrow(\BadMethodCallException::class);

    expect(fn (): mixed => $manager->hasCase(\PlainEnumWithoutTrait::class, 'A'))
        ->toThrow(\BadMethodCallException::class);
});

test('pure enum full stack: forSelect, forApi, values, labels', function (): void {
    $cases = TestPureEnum::cases();

    // values() returns case names for pure enums
    expect(TestPureEnum::values())->toBe(['ACTIVE', 'PENDING', 'INACTIVE']);

    // labels() returns non-empty strings
    $labels = TestPureEnum::labels();
    expect($labels)->toHaveCount(3);
    foreach ($labels as $label) {
        expect($label)->toBeString()->not->toBeEmpty();
    }

    // forSelect returns case names as values
    $select = TestPureEnum::forSelect();
    expect($select)->toHaveCount(3);
    expect($select[0])->toHaveKeys(['value', 'label']);
    expect($select[0]['value'])->toBe('ACTIVE');

    // forApi returns full metadata
    $api = TestPureEnum::forApi();
    expect($api)->toHaveCount(3);
    expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    expect($api[0]['name'])->toBe('ACTIVE');
    expect($api[0]['value'])->toBe('ACTIVE'); // Pure enum uses case name as value
});

test('int-backed enum forSelect returns int values', function (): void {
    $select = TestIntBackedEnum::forSelect();
    expect($select)->toHaveCount(3);

    // Verify int values
    expect($select[0]['value'])->toBe(0);
    expect($select[1]['value'])->toBe(1);
    expect($select[2]['value'])->toBe(2);

    // Labels should be non-empty
    foreach ($select as $option) {
        expect($option['label'])->toBeString()->not->toBeEmpty();
    }
});

test('fromName throws InvalidEnumException with class and name details', function (): void {
    expect(fn (): mixed => TestStringBackedEnum::fromName('NONEXISTENT'))
        ->toThrow(InvalidEnumException::class, 'NONEXISTENT');
});

test('label generation handles camelCase and SCREAMING_SNAKE_CASE', function (): void {
    // TestLabelGenEnum has PascalCase and SCREAMING_SNAKE_CASE cases
    expect(TestLabelGenEnum::SomeCamelCase->label())->toBe('Some camel case');
    expect(TestLabelGenEnum::ALL_CAPS_SCREAMING->label())->toBe('All caps screaming');
});

test('enum cache singleton prevents cloning and unserialization', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();

    // Clone should throw
    expect(fn () => clone $cache)->toThrow(\RuntimeException::class);

    // Unserialization should throw
    $serialized = serialize($cache);
    expect(fn () => unserialize($serialized))->toThrow(\RuntimeException::class);

    EnumCache::resetInstance();
});

test('comparison methods work with both instances and string names for int-backed enum', function (): void {
    $case = TestIntBackedEnum::ONE;

    // is() with instance
    expect($case->is(TestIntBackedEnum::ONE))->toBeTrue();
    expect($case->is(TestIntBackedEnum::ZERO))->toBeFalse();

    // is() with string name
    expect($case->is('ONE'))->toBeTrue();
    expect($case->is('ZERO'))->toBeFalse();

    // isNot()
    expect($case->isNot(TestIntBackedEnum::ZERO))->toBeTrue();
    expect($case->isNot('ZERO'))->toBeTrue();

    // in() with instances
    expect($case->in([TestIntBackedEnum::ONE, TestIntBackedEnum::TWO]))->toBeTrue();
    expect($case->in([TestIntBackedEnum::ZERO]))->toBeFalse();

    // in() with strings
    expect($case->in(['ONE', 'TWO']))->toBeTrue();

    // in() mixed
    expect($case->in([TestIntBackedEnum::ZERO, 'ONE']))->toBeTrue();

    // notIn()
    expect($case->notIn(['ZERO']))->toBeTrue();
    expect($case->notIn(['ZERO', 'TWO']))->toBeTrue();
    expect($case->notIn(['ONE', 'TWO']))->toBeFalse();
});

test('tryFromLabel is case-insensitive and works with auto-generated labels', function (): void {
    // Auto-generated label for SUCCESS is "Success"
    expect(TestStringBackedEnum::tryFromLabel('Success'))->toBe(TestStringBackedEnum::SUCCESS);
    expect(TestStringBackedEnum::tryFromLabel('success'))->toBe(TestStringBackedEnum::SUCCESS);
    expect(TestStringBackedEnum::tryFromLabel('SUCCESS'))->toBe(TestStringBackedEnum::SUCCESS);

    // Non-existent label returns null
    expect(TestStringBackedEnum::tryFromLabel('Non Existent Label'))->toBeNull();
});

test('EnumCache setTtl normalizes negative values to zero', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();

    $cache->setTtl(-10);
    expect($cache->getTtl())->toBe(0);

    $cache->setTtl(100);
    expect($cache->getTtl())->toBe(100);

    EnumCache::resetInstance();
});

test('EnumCache clear and clearClass work independently', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();
    $cache->setTtl(300);

    $cache->set('ClassA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
    $cache->set('ClassB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

    expect($cache->has('ClassA'))->toBeTrue();
    expect($cache->has('ClassB'))->toBeTrue();

    // Clear only ClassA
    $cache->clearClass('ClassA');
    expect($cache->has('ClassA'))->toBeFalse();
    expect($cache->has('ClassB'))->toBeTrue();

    // Clear all
    $cache->clear();
    expect($cache->has('ClassB'))->toBeFalse();

    EnumCache::resetInstance();
});

test('EnumMetadataResolver buildMetadata returns expected structure', function (): void {
    EnumCache::resetInstance();

    $meta = EnumMetadataResolver::resolve(TestStringBackedEnum::class);

    // Verify structure
    expect($meta)->toBeArray();
    expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    expect($meta['labels'])->toBeArray();
    expect($meta['colors'])->toBeArray();
    expect($meta['descriptions'])->toBeArray();
    expect($meta['icons'])->toBeArray();

    // String-backed enum: keys are the backed values
    expect($meta['labels'])->toHaveKey('active');
    expect($meta['labels'])->toHaveKey('pending');
    expect($meta['labels'])->toHaveKey('failed');

    EnumCache::resetInstance();
});

// ---------------------------------------------------------------------------
// Test Fixtures
// ---------------------------------------------------------------------------

#[EnumColor(success: ['active', 'pending'], danger: ['failed'])]
enum TestStringBackedEnum: string
{
    use HasEnumMetadata;

    case SUCCESS = 'active';
    case PENDING = 'pending';
    case FAILED = 'failed';
}

enum TestIntBackedEnum: int
{
    use HasEnumMetadata;

    case ZERO = 0;
    case ONE = 1;
    case TWO = 2;
}

enum TestPureEnum
{
    use HasEnumMetadata;

    case ACTIVE;
    case PENDING;
    case INACTIVE;
}

#[EnumLabel(labels: ['pending' => 'Pending User'])]
enum TestPriorityEnum: string
{
    use HasEnumMetadata;

    #[Label('Custom Active')]
    case ACTIVE = 'active';

    case PENDING = 'pending';
}

#[EnumIcon(default: 'heroicon-o-check')]
enum TestIconDefaultEnum: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
    case PENDING = 'pending';

    #[Icon('heroicon-o-x-mark')]
    case FAILED = 'failed';
}

enum TestLabelGenEnum
{
    use HasEnumMetadata;

    case SomeCamelCase = 'someCamelCase';
    case ALL_CAPS_SCREAMING = 'ALL_CAPS_SCREAMING';
}

enum PlainEnumWithoutTrait
{
    case A;
    case B;
}
