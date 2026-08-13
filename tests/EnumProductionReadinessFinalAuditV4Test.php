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
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enums Production Readiness Audit', function () {
    // ── Attribute Resolution Priority ──────────────────────────────────────

    it('resolves per-case label over class-level label', function () {
        // IntBackedPriority: case-level #[Label('High Priority')] overrides
        // class-level #[EnumLabel(labels: [2 => ...])] which is not set for HIGH
        expect(IntBackedPriority::HIGH->label())->toBe('High Priority');
    });

    it('resolves class-level label when per-case is absent', function () {
        expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
    });

    it('auto-generates label when no attribute is set', function () {
        // NONE has no Label and no class-level label mapping for value 4
        expect(IntBackedPriority::NONE->label())->toBe('None');
    });

    it('resolves per-case color over class-level color', function () {
        // UserStatus ACTIVE: class-level says 'success', no per-case override
        expect(UserStatus::ACTIVE->color())->toBe('success');

        // UserStatus BANNED: per-case #[Color('danger')] overrides class-level
        expect(UserStatus::BANNED->color())->toBe('danger');
    });

    it('falls back to secondary color when nothing is defined', function () {
        // UserStatus INACTIVE has no per-case or class-level color
        expect(UserStatus::INACTIVE->color())->toBe('secondary');
    });

    // ── Class-Level Icon Defaults ──────────────────────────────────────────

    it('applies default icon from class-level EnumIcon to all cases', function () {
        // IntBackedPriority: #[EnumIcon(default: 'heroicon-o-flag')]
        // CRITICAL has no per-case icon, so it gets the default
        expect(IntBackedPriority::CRITICAL->icon())->toBe('heroicon-o-flag');
    });

    it('per-case icon overrides class-level default icon', function () {
        // PureFeatureFlag DARK_MODE has per-case icon 'heroicon-o-moon'
        expect(PureFeatureFlag::DARK_MODE->icon())->toBe('heroicon-o-moon');
    });

    // ── Class-Level Description Mapping ───────────────────────────────────

    it('resolves class-level description for int-backed enum', function () {
        expect(IntBackedPriority::CRITICAL->description())
            ->toBe('Critical priority — immediate action required');
    });

    it('returns null when no description is defined anywhere', function () {
        expect(UserStatus::INACTIVE->description())->toBeNull();
    });

    // ── Bulk Methods ───────────────────────────────────────────────────────

    it('forSelect returns backed values not case names', function () {
        $options = UserStatus::forSelect();

        expect($options)->toHaveCount(5);
        // Should contain backed values, not case names
        expect($options[0])->toHaveKey('value');
        expect($options[0]['value'])->toBe('active');
        expect($options[0])->toHaveKey('label');
    });

    it('forSelect returns int values for int-backed enums', function () {
        $options = IntBackedPriority::forSelect();

        expect($options)->toHaveCount(4);

        $values = array_column($options, 'value');
        expect($values)->each->toBeInt();
        expect($values)->toContain(1);
        expect($values)->toContain(2);
        expect($values)->toContain(3);
        expect($values)->toContain(4);
    });

    it('forSelect returns case names for pure enums', function () {
        $options = PureFeatureFlag::forSelect();

        expect($options)->toHaveCount(3);

        $values = array_column($options, 'value');
        expect($values)->toContain('DARK_MODE');
        expect($values)->toContain('BETA_FEATURES');
        expect($values)->toContain('MAINTENANCE_MODE');
    });

    it('forApi includes all metadata fields for each case', function () {
        $api = UserStatus::forApi();

        expect($api)->toHaveCount(5);

        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['color'])->toBeString()->not->toBeEmpty();
        }
    });

    it('values() returns backed values for string-backed enum', function () {
        $values = UserStatus::values();

        expect($values)->toContain('active');
        expect($values)->toContain('inactive');
        expect($values)->toContain('banned');
    });

    it('values() returns backed values for int-backed enum', function () {
        $values = IntBackedPriority::values();

        expect($values)->toEqual([1, 2, 3, 4]);
    });

    it('labels() returns human-readable labels for all cases', function () {
        $labels = PureFeatureFlag::labels();

        expect($labels)->toHaveCount(3);
        expect($labels)->toContain('Dark Mode');
        expect($labels)->toContain('Beta Features');
        expect($labels)->toContain('Maintenance Mode');
    });

    // ── Comparison Methods ────────────────────────────────────────────────

    it('is() uses strict identity with enum instances', function () {
        expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::CRITICAL))->toBeTrue();
        expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::HIGH))->toBeFalse();
    });

    it('is() works with string case names', function () {
        expect(IntBackedPriority::CRITICAL->is('CRITICAL'))->toBeTrue();
        expect(IntBackedPriority::CRITICAL->is('HIGH'))->toBeFalse();
    });

    it('is() is case-sensitive for string names', function () {
        expect(IntBackedPriority::CRITICAL->is('critical'))->toBeFalse();
    });

    it('isNot() negates is()', function () {
        expect(IntBackedPriority::LOW->isNot(IntBackedPriority::CRITICAL))->toBeTrue();
        expect(IntBackedPriority::LOW->isNot(IntBackedPriority::LOW))->toBeFalse();
    });

    it('in() matches against list of instances', function () {
        $result = IntBackedPriority::LOW->in([IntBackedPriority::LOW, IntBackedPriority::NONE]);
        expect($result)->toBeTrue();

        $result = IntBackedPriority::LOW->in([IntBackedPriority::CRITICAL]);
        expect($result)->toBeFalse();
    });

    it('in() matches against list of strings', function () {
        $result = IntBackedPriority::LOW->in(['LOW', 'NONE']);
        expect($result)->toBeTrue();
    });

    it('in() matches against mixed instances and strings', function () {
        $result = IntBackedPriority::LOW->in([IntBackedPriority::CRITICAL, 'LOW']);
        expect($result)->toBeTrue();
    });

    it('notIn() negates in()', function () {
        $result = IntBackedPriority::LOW->notIn([IntBackedPriority::CRITICAL]);
        expect($result)->toBeTrue();

        $result = IntBackedPriority::LOW->notIn([IntBackedPriority::LOW, IntBackedPriority::NONE]);
        expect($result)->toBeFalse();
    });

    // ── Lookup Methods ───────────────────────────────────────────────────

    it('tryFromLabel is case-insensitive', function () {
        $case = UserStatus::tryFromLabel('active user');
        expect($case)->toBe(UserStatus::ACTIVE);

        $case = UserStatus::tryFromLabel('ACTIVE USER');
        expect($case)->toBe(UserStatus::ACTIVE);

        $case = UserStatus::tryFromLabel('Active User');
        expect($case)->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel returns null for non-existent labels', function () {
        expect(UserStatus::tryFromLabel('nonexistent'))->toBeNull();
    });

    it('tryFromName resolves by case name', function () {
        expect(IntBackedPriority::tryFromName('CRITICAL'))->toBe(IntBackedPriority::CRITICAL);
        expect(IntBackedPriority::tryFromName('NONE'))->toBe(IntBackedPriority::NONE);
    });

    it('tryFromName returns null for non-existent case', function () {
        expect(IntBackedPriority::tryFromName('URGENT'))->toBeNull();
    });

    it('fromName throws InvalidEnumException for non-existent case', function () {
        expect(fn () => IntBackedPriority::fromName('URGENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase checks existence', function () {
        expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::hasCase('NONEXISTENT'))->toBeFalse();
    });

    // ── Cache Behavior ─────────────────────────────────────────────────────

    it('metadata is consistent across repeated calls', function () {
        $label1 = UserStatus::ACTIVE->label();
        $label2 = UserStatus::ACTIVE->label();

        expect($label1)->toBe($label2);
    });

    it('different enum classes maintain independent metadata', function () {
        $activeLabel = UserStatus::ACTIVE->label();
        $darkLabel = PureFeatureFlag::DARK_MODE->label();

        expect($activeLabel)->not->toBe($darkLabel);
        expect($activeLabel)->toBe('Active User');
        expect($darkLabel)->toBe('Dark Mode');
    });

    // ── Type System Consistency ───────────────────────────────────────────

    it('string-backed enum forSelect values are strings', function () {
        foreach (UserStatus::forSelect() as $option) {
            expect($option['value'])->toBeString();
            expect($option['label'])->toBeString();
        }
    });

    it('int-backed enum forSelect values are ints', function () {
        foreach (IntBackedPriority::forSelect() as $option) {
            expect($option['value'])->toBeInt();
            expect($option['label'])->toBeString();
        }
    });

    it('pure enum forSelect values are strings (case names)', function () {
        foreach (PureFeatureFlag::forSelect() as $option) {
            expect($option['value'])->toBeString();
            expect($option['label'])->toBeString();
        }
    });

    it('forApi color is always a non-empty string', function () {
        foreach (IntBackedPriority::forApi() as $item) {
            expect($item['color'])->toBeString()->not->toBeEmpty();
        }
    });

    it('forApi icon can be null', function () {
        $api = UserStatus::forApi();

        // ACTIVE has an icon
        expect($api[0]['icon'])->toBe('heroicon-o-check-circle');

        // INACTIVE has no icon — should be null
        expect($api[1]['icon'])->toBeNull();
    });

    // ── Select Option Uniqueness ───────────────────────────────────────────

    it('forSelect values are unique for each enum type', function () {
        $stringValues = array_column(UserStatus::forSelect(), 'value');
        expect($stringValues)->toEqual(array_unique($stringValues));

        $intValues = array_column(IntBackedPriority::forSelect(), 'value');
        expect($intValues)->toEqual(array_unique($intValues));

        $pureValues = array_column(PureFeatureFlag::forSelect(), 'value');
        expect($pureValues)->toEqual(array_unique($pureValues));
    });
});
