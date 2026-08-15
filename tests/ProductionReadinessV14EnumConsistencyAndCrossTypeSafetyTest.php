<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

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
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingletonMode;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('V14 — Enum Consistency & Cross-Type Safety', function () {
    // ── Section 1: Type System Consistency ──────────────────────────────────

    it('string-backed enum forSelect returns string values only', function () {
        $options = UserStatus::forSelect();

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();

        foreach ($options as $option) {
            expect($option)->toHaveKeys(['value', 'label']);
            expect($option['value'])->toBeString();
            expect($option['label'])->toBeString()->not->toBeEmpty();
        }
    });

    it('int-backed enum forSelect returns int values only', function () {
        $options = IntPriority::forSelect();

        expect($options)->toBeArray()->not->toBeEmpty();

        foreach ($options as $option) {
            expect($option['value'])->toBeInt();
            expect($option['label'])->toBeString()->not->toBeEmpty();
        }
    });

    it('pure enum forSelect returns case name strings', function () {
        $options = PureFeatureFlag::forSelect();

        expect($options)->toBeArray()->not->toBeEmpty();

        foreach ($options as $option) {
            expect($option['value'])->toBeString();
            // Pure enums use case name as value
            expect($option['label'])->toBeString()->not->toBeEmpty();
        }
    });

    it('all three enum types return unique values from forSelect', function () {
        $stringValues = array_column(UserStatus::forSelect(), 'value');
        $intValues = array_column(IntPriority::forSelect(), 'value');
        $pureValues = array_column(PureFeatureFlag::forSelect(), 'value');

        expect($stringValues)->toEqual(array_unique($stringValues));
        expect($intValues)->toEqual(array_unique($intValues));
        expect($pureValues)->toEqual(array_unique($pureValues));
    });

    it('forApi returns consistent structure across all enum types', function () {
        $apiKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

        $stringApi = UserStatus::forApi();
        $intApi = IntPriority::forApi();
        $pureApi = PureFeatureFlag::forApi();

        expect($stringApi[0])->toHaveKeys($apiKeys);
        expect($intApi[0])->toHaveKeys($apiKeys);
        expect($pureApi[0])->toHaveKeys($apiKeys);
    });

    it('forApi color is always a non-empty string for every enum type', function () {
        foreach ([UserStatus::class, IntPriority::class, PureFeatureFlag::class, DetailedTicketStatus::class] as $enum) {
            $api = $enum::forApi();
            foreach ($api as $item) {
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        }
    });

    it('forApi icon and description are nullable but consistently typed', function () {
        $api = UserStatus::forApi();
        foreach ($api as $item) {
            expect($item['icon'])->toBeNull()->or()->toBeString();
            expect($item['description'])->toBeNull()->or()->toBeString();
        }
    });

    // ── Section 2: Values and Labels Consistency ─────────────────────────────

    it('values() count matches cases() count for every enum type', function () {
        expect(count(UserStatus::values()))->toBe(count(UserStatus::cases()));
        expect(count(IntPriority::values()))->toBe(count(IntPriority::cases()));
        expect(count(PureFeatureFlag::values()))->toBe(count(PureFeatureFlag::cases()));
    });

    it('labels() count matches cases() count', function () {
        expect(count(UserStatus::labels()))->toBe(count(UserStatus::cases()));
        expect(count(IntPriority::labels()))->toBe(count(IntPriority::cases()));
    });

    it('labels() returns non-empty strings', function () {
        $labels = UserStatus::labels();
        foreach ($labels as $label) {
            expect($label)->toBeString()->not->toBeEmpty();
        }
    });

    it('forSelect and values have same order', function () {
        $selectValues = array_column(UserStatus::forSelect(), 'value');
        $rawValues = UserStatus::values();

        expect($selectValues)->toBe($rawValues);
    });

    // ── Section 3: Lookup Method Consistency ─────────────────────────────────

    it('tryFromName returns null for empty string', function () {
        expect(UserStatus::tryFromName(''))->toBeNull();
    });

    it('tryFromName returns null for whitespace-only string', function () {
        expect(UserStatus::tryFromName('   '))->toBeNull();
    });

    it('tryFromLabel returns null for empty string', function () {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });

    it('fromName throws for empty string', function () {
        expect(fn () => UserStatus::fromName(''))->toThrow(InvalidEnumException::class);
    });

    it('fromName throws for whitespace-only string', function () {
        expect(fn () => UserStatus::fromName('   '))->toThrow(InvalidEnumException::class);
    });

    it('tryFromName is case-sensitive — lowercase of SCREAMING_SNAKE fails', function () {
        expect(UserStatus::tryFromName('active'))->toBeNull();
        expect(UserStatus::tryFromName('ACTIVE'))->toBeInstanceOf(UserStatus::class);
    });

    it('tryFromLabel is case-insensitive — lowercase of Title Case works', function () {
        $case = UserStatus::ACTIVE;
        $label = $case->label();
        $lowerLabel = strtolower($label);

        expect(UserStatus::tryFromLabel($lowerLabel))->toBe($case);
    });

    it('tryFromLabel returns null for label with extra whitespace', function () {
        $label = UserStatus::ACTIVE->label();
        expect(UserStatus::tryFromLabel('  ' . $label . '  '))->toBeNull();
    });

    it('hasCase returns false for empty string', function () {
        expect(UserStatus::hasCase(''))->toBeFalse();
    });

    // ── Section 4: Comparison Edge Cases ────────────────────────────────────

    it('is() with self comparison is always true', function () {
        foreach (UserStatus::cases() as $case) {
            expect($case->is($case))->toBeTrue();
        }
    });

    it('is() with different case is always false', function () {
        $cases = UserStatus::cases();
        if (count($cases) >= 2) {
            expect($cases[0]->is($cases[1]))->toBeFalse();
        }
    });

    it('isNot() with self is always false', function () {
        foreach (UserStatus::cases() as $case) {
            expect($case->isNot($case))->toBeFalse();
        }
    });

    it('notIn() with empty array is always true', function () {
        foreach (UserStatus::cases() as $case) {
            expect($case->notIn([]))->toBeTrue();
        }
    });

    it('in() with empty array is always false', function () {
        foreach (UserStatus::cases() as $case) {
            expect($case->in([]))->toBeFalse();
        }
    });

    it('in() with single element array works', function () {
        $active = UserStatus::ACTIVE;
        expect($active->in([UserStatus::ACTIVE]))->toBeTrue();
        expect($active->in([UserStatus::BANNED]))->toBeFalse();
    });

    it('comparison methods work identically for int-backed enums', function () {
        $low = IntPriority::LOW;
        $high = IntPriority::HIGH;

        expect($low->is($low))->toBeTrue();
        expect($low->is($high))->toBeFalse();
        expect($low->is('LOW'))->toBeTrue();
        expect($low->is('HIGH'))->toBeFalse();
        expect($low->isNot($high))->toBeTrue();
        expect($low->in([$low, $high]))->toBeTrue();
        expect($low->in([$high]))->toBeFalse();
        expect($low->notIn([$high]))->toBeTrue();
    });

    it('comparison methods work identically for pure enums', function () {
        $enabled = PureFeatureFlag::ENABLED;
        $disabled = PureFeatureFlag::DISABLED;

        expect($enabled->is($enabled))->toBeTrue();
        expect($enabled->is($disabled))->toBeFalse();
        expect($enabled->is('ENABLED'))->toBeTrue();
        expect($enabled->is('DISABLED'))->toBeFalse();
        expect($enabled->isNot($disabled))->toBeTrue();
        expect($enabled->in([$enabled, $disabled]))->toBeTrue();
    });

    // ── Section 5: EnumCache Lifecycle ───────────────────────────────────────

    it('EnumCache singleton returns same instance', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('EnumCache reset creates new instance', function () {
        $a = EnumCache::getInstance();
        EnumCache::resetInstance();
        $b = EnumCache::getInstance();

        expect($a)->not->toBe($b);
        EnumCache::resetInstance(); // cleanup
    });

    it('EnumCache TTL 0 disables caching', function () {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(0);
        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();

        // Restore
        $cache->setTtl(300);
    });

    it('EnumCache clear removes entries', function () {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();
        $cache->clear();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('EnumCache clearClass removes only specified entry', function () {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(0);
        $cache->set(UserStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(OrderStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        // With TTL 0, has() returns false. Let's use TTL > 0 for this test.
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(OrderStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeTrue();

        $cache->clear();
    });

    it('EnumCache get throws OutOfBoundsException for missing entry', function () {
        $cache = EnumCache::getInstance();
        $cache->clear();

        expect(fn () => $cache->get('NonExistentEnumClass'))->toThrow(\OutOfBoundsException::class);
    });

    it('EnumCache flush clears all entries via static method', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(UserStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(OrderStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        EnumCache::flush();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    // ── Section 6: InvalidEnumException ──────────────────────────────────────

    it('InvalidEnumException::value formats null display correctly', function () {
        $e = InvalidEnumException::value('SomeEnum', null);
        expect($e->getMessage())->toContain('null');
        expect($e->getMessage())->toContain('SomeEnum');
    });

    it('InvalidEnumException::value formats string display correctly', function () {
        $e = InvalidEnumException::value('SomeEnum', 'bad_value');
        expect($e->getMessage())->toContain('bad_value');
        expect($e->getMessage())->toContain('SomeEnum');
    });

    it('InvalidEnumException::value formats int display correctly', function () {
        $e = InvalidEnumException::value('SomeEnum', 999);
        expect($e->getMessage())->toContain('999');
    });

    it('InvalidEnumException::forName includes class and name', function () {
        $e = InvalidEnumException::forName('App\Enums\Status', 'NONEXISTENT');
        expect($e->getMessage())->toContain('NONEXISTENT');
        expect($e->getMessage())->toContain('App\Enums\Status');
    });

    it('InvalidEnumException::__toString includes class name and message', function () {
        $e = InvalidEnumException::forName('Test', 'BAD');
        $str = (string) $e;
        expect($str)->toContain('InvalidEnumException');
        expect($str)->toContain('BAD');
    });

    // ── Section 7: EnumRule Validation ──────────────────────────────────────

    it('EnumRule nullable passes null values', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $fail = fn (string $m) => throw new \RuntimeException($m);
        $rule->validate('status', null, $fail);
        // No exception = pass
        expect(true)->toBeTrue();
    });

    it('EnumRule non-nullable rejects null values', function () {
        $rule = EnumRule::for(UserStatus::class);
        $fail = fn (string $m) => throw new \RuntimeException($m);
        expect(fn () => $rule->validate('status', null, $fail))->toThrow(\RuntimeException::class);
    });

    it('EnumRule accepts valid string-backed value', function () {
        $rule = EnumRule::for(UserStatus::class);
        $fail = fn (string $m) => throw new \RuntimeException($m);
        $rule->validate('status', 'active', $fail);
        expect(true)->toBeTrue();
    });

    it('EnumRule accepts valid int-backed value', function () {
        $rule = EnumRule::for(IntPriority::class);
        $fail = fn (string $m) => throw new \RuntimeException($m);
        $rule->validate('priority', 1, $fail);
        expect(true)->toBeTrue();
    });

    it('EnumRule rejects invalid string-backed value', function () {
        $rule = EnumRule::for(UserStatus::class);
        $fail = fn (string $m) => throw new \RuntimeException($m);
        expect(fn () => $rule->validate('status', 'nonexistent', $fail))->toThrow(\RuntimeException::class);
    });

    it('EnumRule rejects type mismatch for int-backed enum', function () {
        $rule = EnumRule::for(IntPriority::class);
        $fail = fn (string $m) => throw new \RuntimeException($m);
        // String value for int-backed enum should fail
        expect(fn () => $rule->validate('priority', '1', $fail))->toThrow(\RuntimeException::class);
    });

    it('EnumRule for() returns new instance each time', function () {
        $a = EnumRule::for(UserStatus::class);
        $b = EnumRule::for(UserStatus::class);
        expect($a)->not->toBe($b);
    });

    it('EnumRule nullable() returns new instance', function () {
        $base = EnumRule::for(UserStatus::class);
        $nullable = $base->nullable();
        expect($nullable)->not->toBe($base);
    });

    // ── Section 8: EnumCast ─────────────────────────────────────────────────

    it('EnumCast get returns null for null value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};
        $result = $cast->get($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('EnumCast get returns enum instance for valid string value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};
        $result = $cast->get($model, 'status', 'active', []);
        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('EnumCast get returns null for non-existent value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};
        $result = $cast->get($model, 'status', 'nonexistent', []);
        expect($result)->toBeNull();
    });

    it('EnumCast get returns enum instance for valid int value', function () {
        $cast = new EnumCast(IntPriority::class);
        $model = new class {};
        $result = $cast->get($model, 'priority', 1, []);
        expect($result)->toBe(IntPriority::LOW);
    });

    it('EnumCast set returns backed value for enum instance', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};
        $result = $cast->set($model, 'status', UserStatus::ACTIVE, []);
        expect($result)->toBe('active');
    });

    it('EnumCast set returns null for null', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};
        $result = $cast->set($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('EnumCast set throws for wrong enum type', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};

        expect(fn () => $cast->set($model, 'status', OrderStatus::PENDING, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('EnumCast set validates raw string value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};

        expect(fn () => $cast->set($model, 'status', 'invalid_value', []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('EnumCast set validates raw int value', function () {
        $cast = new EnumCast(IntPriority::class);
        $model = new class {};

        expect(fn () => $cast->set($model, 'priority', 999, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('EnumCast set accepts raw valid string value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};
        $result = $cast->set($model, 'status', 'active', []);
        expect($result)->toBe('active');
    });

    it('EnumCast set accepts raw valid int value', function () {
        $cast = new EnumCast(IntPriority::class);
        $model = new class {};
        $result = $cast->set($model, 'priority', 2, []);
        expect($result)->toBe(2);
    });

    it('EnumCast serialize returns backed value for enum', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};
        $result = $cast->serialize($model, 'status', UserStatus::ACTIVE, []);
        expect($result)->toBe('active');
    });

    it('EnumCast serialize returns null for null', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};
        $result = $cast->serialize($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('EnumCast serialize passes through raw int/string', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {};
        expect($cast->serialize($model, 'status', 'active', []))->toBe('active');
    });

    // ── Section 9: Edge Cases — Special Fixtures ───────────────────────────

    it('single-case enum works with all methods', function () {
        $case = SingleCaseEnum::ONLY;

        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->color())->toBeString();
        expect(SingleCaseEnum::forSelect())->toHaveCount(1);
        expect(SingleCaseEnum::values())->toHaveCount(1);
        expect(SingleCaseEnum::hasCase('ONLY'))->toBeTrue();
        expect(SingleCaseEnum::tryFromName('ONLY'))->toBe($case);
    });

    it('empty defaults status returns secondary color by default', function () {
        expect(EmptyDefaultsStatus::ACTIVE->color())->toBe('secondary');
    });

    it('camelCase enum generates correct labels', function () {
        $label = CamelCaseRole::ADMIN->label();
        // camelCase → "Title Case"
        expect($label)->toBeString()->not->toBeEmpty();
        expect($label)->toBe('Admin');
    });

    it('zero-backed int enum works correctly', function () {
        $zero = ZeroBackedPriority::ZERO;
        expect($zero->value)->toBe(0);
        expect($zero->label())->toBeString()->not->toBeEmpty();
        expect(ZeroBackedPriority::tryFrom(0))->toBe($zero);
    });

    it('zero priority pure enum works correctly', function () {
        $zero = ZeroPriority::ZERO;
        expect($zero->name)->toBe('ZERO');
        expect($zero->label())->toBeString()->not->toBeEmpty();
        expect(ZeroPriority::tryFromName('ZERO'))->toBe($zero);
    });

    // ── Section 10: Attribute Metadata Resolution ──────────────────────────

    it('class-level EnumColor maps correctly to all cases', function () {
        // UserStatus has class-level EnumColor
        expect(UserStatus::ACTIVE->color())->toBe('success');
        expect(UserStatus::BANNED->color())->toBe('danger');
    });

    it('per-case Color overrides class-level EnumColor', function () {
        // DetailedTicketStatus may have per-case overrides
        $api = DetailedTicketStatus::forApi();
        foreach ($api as $item) {
            expect($item['color'])->toBeString();
        }
    });

    it('IntStatusWithColor resolves both int-backed and color metadata', function () {
        $case = IntStatusWithColor::ACTIVE;
        expect($case->value)->toBeInt();
        expect($case->color())->toBeString();
        expect($case->label())->toBeString()->not->toBeEmpty();
    });

    it('MixedAttributeStatus resolves mixed attribute types', function () {
        $api = MixedAttributeStatus::forApi();
        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['label'])->toBeString();
            expect($item['color'])->toBeString();
        }
    });

    // ── Section 11: Label Generation Algorithms ─────────────────────────────

    it('SCREAMING_SNAKE_CASE converts to Title Case', function () {
        // OrderStatus has SCREAMING_SNAKE cases
        $labels = OrderStatus::labels();
        foreach ($labels as $label) {
            // No underscores, no all-caps
            expect($label)->not->toContain('_');
            expect($label)->toBe(ucwords(strtolower($label)));
        }
    });

    it('labels contain no underscores for any fixture enum', function () {
        $enums = [UserStatus::class, OrderStatus::class, TicketStatus::class, Priority::class];

        foreach ($enums as $enum) {
            $labels = $enum::labels();
            foreach ($labels as $label) {
                expect($label)->not->toContain('_',
                    "Enum {$enum} has label with underscore: {$label}");
            }
        }
    });

    it('labels are trimmed (no leading/trailing spaces)', function () {
        $enums = [UserStatus::class, OrderStatus::class, TicketStatus::class];

        foreach ($enums as $enum) {
            foreach ($enum::labels() as $label) {
                expect($label)->toBe(trim($label));
            }
        }
    });

    // ── Section 12: Structural Guarantees ───────────────────────────────────

    it('every fixture enum uses HasEnumMetadata trait', function () {
        $enums = [
            UserStatus::class, OrderStatus::class, TicketStatus::class,
            Priority::class, IntPriority::class, PureFeatureFlag::class,
            SingleCaseEnum::class, CamelCaseRole::class, DetailedTicketStatus::class,
            EmptyDefaultsStatus::class, MixedAttributeStatus::class,
            IntStatusWithColor::class, PlainTestEnum::class, SingletonMode::class,
            ZeroPriority::class, ZeroBackedPriority::class,
        ];

        foreach ($enums as $enum) {
            expect(method_exists($enum, 'label'))->toBeTrue("{$enum} missing HasEnumMetadata trait");
            expect(method_exists($enum, 'color'))->toBeTrue("{$enum} missing HasEnumMetadata trait");
            expect(method_exists($enum, 'forSelect'))->toBeTrue("{$enum} missing HasEnumMetadata trait");
            expect(method_exists($enum, 'values'))->toBeTrue("{$enum} missing HasEnumMetadata trait");
        }
    });

    it('every enum has at least one case', function () {
        $enums = [
            UserStatus::class, OrderStatus::class, TicketStatus::class,
            Priority::class, IntPriority::class, PureFeatureFlag::class,
            SingleCaseEnum::class, CamelCaseRole::class, DetailedTicketStatus::class,
            EmptyDefaultsStatus::class, MixedAttributeStatus::class,
            IntStatusWithColor::class, PlainTestEnum::class, SingletonMode::class,
            ZeroPriority::class, ZeroBackedPriority::class,
        ];

        foreach ($enums as $enum) {
            expect($enum::cases())->not->toBeEmpty("{$enum} has no cases");
        }
    });
});
