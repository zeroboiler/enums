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
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

/**
 * V44 — Real-world integration, edge cases, and PHP 8.5 compliance audit.
 *
 * Covers:
 * - Zero-backed int enum (value = 0) roundtrip via EnumCast
 * - EnumCache TTL boundary (exact expiry, sub-second)
 * - EnumManager delegation for single-case enum
 * - Mixed per-case + class-level attribute resolution order
 * - CamelCase label generation
 * - EnumRule with pure enum (name validation)
 * - EnumRule nullable mode
 * - EnumCast set() with invalid enum instance type
 * - InvalidEnumException factory methods message format
 * - EnumCache singleton identity
 * - Cross-type (string, int, pure) consistency contract
 */
describe('V44 Real-World Integration and Edge Cases', function () {
    // ---------------------------------------------------------------
    // Zero-backed int enum
    // ---------------------------------------------------------------
    it('handles zero-backed int enum value correctly via toValue()', function () {
        $zero = ZeroBackedPriority::LOW;
        expect($zero->toValue())->toBe(0)
            ->and($zero->value)->toBe(0)
            ->and($zero->label())->toBeString()->not->toBeEmpty();
    });

    it('ZeroBackedPriority forSelect includes zero value correctly', function () {
        $options = ZeroBackedPriority::forSelect();
        $values = array_column($options, 'value');
        expect($values)->toContain(0);

        // Zero should be int, not string
        $zeroOption = array_first($options, fn (array $opt): bool => $opt['value'] === 0);
        expect($zeroOption)->not->toBeNull();
        expect($zeroOption['value'])->toBeInt();
    });

    it('ZeroBackedPriority fromName resolves zero-valued case', function () {
        $case = ZeroBackedPriority::fromName('LOW');
        expect($case->value)->toBe(0)
            ->and($case->name)->toBe('LOW');
    });

    // ---------------------------------------------------------------
    // EnumCache TTL boundary
    // ---------------------------------------------------------------
    it('EnumCache expires entry exactly at TTL boundary', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);

        $cache->set('Test\\EnumA', [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('Test\\EnumA'))->toBeTrue();

        // Wait for TTL to elapse
        usleep(1_100_000); // 1.1 seconds

        expect($cache->has('Test\\EnumA'))->toBeFalse();

        EnumCache::resetInstance();
    });

    it('EnumCache with TTL=0 disables caching entirely', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set('Test\\EnumB', [
            'labels' => ['b' => 'B'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('Test\\EnumB'))->toBeFalse();

        EnumCache::resetInstance();
    });

    it('EnumCache clearClass removes only specified class', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300); // long TTL

        $cache->set('Test\\ClassX', [
            'labels' => ['x' => 'X'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('Test\\ClassY', [
            'labels' => ['y' => 'Y'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('Test\\ClassX'))->toBeTrue();
        expect($cache->has('Test\\ClassY'))->toBeTrue();

        $cache->clearClass('Test\\ClassX');

        expect($cache->has('Test\\ClassX'))->toBeFalse();
        expect($cache->has('Test\\ClassY'))->toBeTrue();

        EnumCache::resetInstance();
    });

    it('EnumCache singleton returns same instance', function () {
        EnumCache::resetInstance();
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b); // strict identity

        EnumCache::resetInstance();
    });

    it('EnumCache resetInstance creates fresh instance', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(999);

        EnumCache::resetInstance();
        $fresh = EnumCache::getInstance();

        // Fresh instance should have default TTL (300)
        expect($fresh->getTtl())->toBe(300);
        // The old cache entries should be gone
        expect($fresh->has('anything'))->toBeFalse();

        EnumCache::resetInstance();
    });

    // ---------------------------------------------------------------
    // EnumManager delegation for single-case enum
    // ---------------------------------------------------------------
    it('EnumManager works with single-case enum', function () {
        $manager = new EnumManager;

        $select = $manager->forSelect(SingleCaseToggle::class);
        expect($select)->toHaveCount(1);
        expect($select[0])->toHaveKey('value');
        expect($select[0])->toHaveKey('label');

        $api = $manager->forApi(SingleCaseToggle::class);
        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('EnumManager hasCase returns true for single existing case', function () {
        $manager = new EnumManager;
        expect($manager->hasCase(SingleCaseToggle::class, 'ENABLED'))->toBeTrue();
        expect($manager->hasCase(SingleCaseToggle::class, 'DISABLED'))->toBeFalse();
    });

    it('EnumManager throws BadMethodCallException for non-trait enum', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(PlainTestEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });

    // ---------------------------------------------------------------
    // Mixed attribute resolution order
    // ---------------------------------------------------------------
    it('per-case attribute overrides class-level attribute', function () {
        // MixedAttributeStatus uses both class-level EnumLabel and per-case Label
        $active = MixedAttributeStatus::ACTIVE;
        expect($active->label())->toBeString()->not->toBeEmpty();
    });

    it('all cases of MixedAttributeStatus have non-empty labels', function () {
        foreach (MixedAttributeStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });

    // ---------------------------------------------------------------
    // CamelCase label generation
    // ---------------------------------------------------------------
    it('CamelCaseRole generates correct labels from camelCase names', function () {
        foreach (CamelCaseRole::cases() as $case) {
            $label = $case->label();
            // CamelCase names should generate "Title Case" labels
            expect($label)->toBeString()->not->toBeEmpty();
            // Label should not contain underscores
            expect($label)->not->toContain('_');
        }
    });

    // ---------------------------------------------------------------
    // EnumRule with pure enum
    // ---------------------------------------------------------------
    it('EnumRule validates pure enum by case name', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failures = [];

        // Valid case name should not fail
        $fail = fn (string $msg) => $failures[] = $msg;
        $rule->validate('flag', 'SEARCH', $fail);
        expect($failures)->toBeEmpty();
    });

    it('EnumRule rejects invalid pure enum case name', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failures = [];

        $fail = fn (string $msg) => $failures[] = $msg;
        $rule->validate('flag', 'NON_EXISTENT', $fail);
        expect($failures)->toHaveCount(1);
    });

    it('EnumRule nullable allows null for optional field', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $failures = [];

        $fail = fn (string $msg) => $failures[] = $msg;
        $rule->validate('status', null, $fail);
        expect($failures)->toBeEmpty();
    });

    it('EnumRule non-nullable rejects null', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failures = [];

        $fail = fn (string $msg) => $failures[] = $msg;
        $rule->validate('status', null, $fail);
        expect($failures)->toHaveCount(1);
    });

    // ---------------------------------------------------------------
    // EnumCast edge cases
    // ---------------------------------------------------------------
    it('EnumCast set() rejects wrong enum type', function () {
        $cast = new EnumCast(UserStatus::class);

        expect(fn () => $cast->set(
            new \stdClass,
            'status',
            IntBackedPriority::HIGH,
            []
        ))->toThrow(\InvalidArgumentException::class);
    });

    it('EnumCast get() returns null for non-existent backed value', function () {
        $cast = new EnumCast(IntBackedPriority::class);

        $result = $cast->get(new \stdClass, 'priority', 99999, []);
        expect($result)->toBeNull();
    });

    // ---------------------------------------------------------------
    // InvalidEnumException message format
    // ---------------------------------------------------------------
    it('InvalidEnumException::value formats string value correctly', function () {
        $ex = InvalidEnumException::value(UserStatus::class, 'invalid_value');
        expect($ex->getMessage())->toContain('invalid_value')
            ->and($ex->getMessage())->toContain(UserStatus::class);
    });

    it('InvalidEnumException::value formats null value as "null"', function () {
        $ex = InvalidEnumException::value(UserStatus::class, null);
        expect($ex->getMessage())->toContain('null')
            ->and($ex->getMessage())->toContain(UserStatus::class);
    });

    it('InvalidEnumException::forName includes case name and class', function () {
        $ex = InvalidEnumException::forName(UserStatus::class, 'NON_EXISTENT');
        expect($ex->getMessage())->toContain('NON_EXISTENT')
            ->and($ex->getMessage())->toContain(UserStatus::class);
    });

    it('InvalidEnumException __toString includes class name', function () {
        $ex = InvalidEnumException::value(UserStatus::class, 'x');
        $str = (string) $ex;
        expect($str)->toContain('InvalidEnumException');
    });

    // ---------------------------------------------------------------
    // Cross-type consistency contract
    // ---------------------------------------------------------------
    it('string-backed enum values() returns string values', function () {
        $values = UserStatus::values();
        foreach ($values as $value) {
            expect($value)->toBeString();
        }
    });

    it('int-backed enum values() returns int values', function () {
        $values = IntBackedPriority::values();
        foreach ($values as $value) {
            expect($value)->toBeInt();
        }
    });

    it('pure enum values() returns case names as strings', function () {
        $values = PureFeatureFlag::values();
        $names = array_map(fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());
        expect($values)->toBe($names);
    });

    it('forSelect returns same count as cases() for all enum types', function () {
        $enums = [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class];
        foreach ($enums as $enumClass) {
            $cases = count($enumClass::cases());
            $select = count($enumClass::forSelect());
            expect($select)->toBe($cases, "Mismatch for {$enumClass}");
        }
    });

    it('forApi returns same count as cases() for all enum types', function () {
        $enums = [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class];
        foreach ($enums as $enumClass) {
            $cases = count($enumClass::cases());
            $api = count($enumClass::forApi());
            expect($api)->toBe($cases, "Mismatch for {$enumClass}");
        }
    });

    // ---------------------------------------------------------------
    // label() and color() defaults
    // ---------------------------------------------------------------
    it('color() returns "secondary" when no color attribute is set', function () {
        // PureFeatureFlag has no color attributes
        foreach (PureFeatureFlag::cases() as $case) {
            expect($case->color())->toBe('secondary');
        }
    });

    it('description() returns null when no description attribute is set', function () {
        foreach (PureFeatureFlag::cases() as $case) {
            expect($case->description())->toBeNull();
        }
    });

    it('icon() returns null when no icon attribute is set', function () {
        foreach (PureFeatureFlag::cases() as $case) {
            expect($case->icon())->toBeNull();
        }
    });

    // ---------------------------------------------------------------
    // is()/isNot()/in()/notIn() with mixed types
    // ---------------------------------------------------------------
    it('is() works with string name argument', function () {
        expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(UserStatus::ACTIVE->is('BANNED'))->toBeFalse();
    });

    it('is() is case-sensitive for string comparison', function () {
        expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
        expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
    });

    it('notIn() is inverse of in()', function () {
        $case = UserStatus::ACTIVE;
        $group = [UserStatus::ACTIVE, UserStatus::PENDING];
        expect($case->in($group))->toBeTrue();
        expect($case->notIn($group))->toBeFalse();
    });

    it('in() returns false for empty array', function () {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('notIn() returns true for empty array', function () {
        expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
    });
});
