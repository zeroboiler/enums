<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;

describe('Pure enum with per-case attributes', function () {
    it('generates labels from case names for pure enum', function () {
        expect(PureFeatureFlag::TWO_FACTOR_AUTH->label())->toBe('Two Factor Auth');
        expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
        expect(PureFeatureFlag::BETA_ACCESS->label())->toBe('Beta Access');
    });

    it('returns default secondary color for cases without color', function () {
        expect(PureFeatureFlag::DARK_MODE->color())->toBe('secondary');
        expect(PureFeatureFlag::BETA_ACCESS->color())->toBe('secondary');
    });

    it('returns icon for case with Icon attribute', function () {
        expect(PureFeatureFlag::TWO_FACTOR_AUTH->icon())->toBe('heroicon-o-shield-check');
    });

    it('returns null icon for cases without Icon attribute', function () {
        expect(PureFeatureFlag::DARK_MODE->icon())->toBeNull();
        expect(PureFeatureFlag::BETA_ACCESS->icon())->toBeNull();
    });

    it('returns null description for all cases', function () {
        expect(PureFeatureFlag::TWO_FACTOR_AUTH->description())->toBeNull();
        expect(PureFeatureFlag::DARK_MODE->description())->toBeNull();
    });

    it('values() returns case names for pure enum', function () {
        $values = PureFeatureFlag::values();

        expect($values)->toBe([
            'TWO_FACTOR_AUTH',
            'DARK_MODE',
            'BETA_ACCESS',
        ]);
    });

    it('forSelect() uses case names as values', function () {
        $select = PureFeatureFlag::forSelect();

        expect($select)->toBeArray();
        expect($select[0])->toHaveKeys(['value', 'label']);
        expect($select[0]['value'])->toBe('TWO_FACTOR_AUTH');
        expect($select[0]['label'])->toBe('Two Factor Auth');
    });

    it('forApi() returns full metadata with case names as values', function () {
        $api = PureFeatureFlag::forApi();

        expect($api)->toBeArray();
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($api[0]['value'])->toBe('TWO_FACTOR_AUTH');
        expect($api[0]['name'])->toBe('TWO_FACTOR_AUTH');
    });

    it('tryFromName resolves correctly', function () {
        expect(PureFeatureFlag::tryFromName('TWO_FACTOR_AUTH'))->toBe(PureFeatureFlag::TWO_FACTOR_AUTH);
        expect(PureFeatureFlag::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('fromName throws on invalid case', function () {
        $this->expectException(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class);

        PureFeatureFlag::fromName('NON_EXISTENT');
    });

    it('hasCase checks existence', function () {
        expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::hasCase('UNKNOWN'))->toBeFalse();
    });

    it('tryFromLabel resolves by auto-generated label', function () {
        expect(PureFeatureFlag::tryFromLabel('Dark Mode'))->toBe(PureFeatureFlag::DARK_MODE);
        expect(PureFeatureFlag::tryFromLabel('nonexistent'))->toBeNull();
    });

    it('is() works with instances and names', function () {
        expect(PureFeatureFlag::DARK_MODE->is(PureFeatureFlag::DARK_MODE))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->is(PureFeatureFlag::BETA_ACCESS))->toBeFalse();
    });

    it('isNot() negates is()', function () {
        expect(PureFeatureFlag::DARK_MODE->isNot(PureFeatureFlag::BETA_ACCESS))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->isNot(PureFeatureFlag::DARK_MODE))->toBeFalse();
    });

    it('in() checks group membership', function () {
        expect(PureFeatureFlag::DARK_MODE->in([PureFeatureFlag::DARK_MODE, PureFeatureFlag::BETA_ACCESS]))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->in(['DARK_MODE']))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->in([PureFeatureFlag::BETA_ACCESS]))->toBeFalse();
    });

    it('labels() returns all labels in order', function () {
        $labels = PureFeatureFlag::labels();

        expect($labels)->toHaveCount(3);
        expect($labels[0])->toBe('Two Factor Auth');
    });
});

describe('Int-backed enum with class-level EnumColor', function () {
    it('resolves class-level color from int backing values', function () {
        expect(IntStatusWithColor::ACTIVE->color())->toBe('success');
        expect(IntStatusWithColor::PENDING->color())->toBe('warning');
        expect(IntStatusWithColor::DRAFT->color())->toBe('success');
    });

    it('per-case Color overrides class-level EnumColor', function () {
        expect(IntStatusWithColor::BANNED->color())->toBe('danger');
    });

    it('generates labels from case names', function () {
        expect(IntStatusWithColor::ACTIVE->label())->toBe('Active');
        expect(IntStatusWithColor::PENDING->label())->toBe('Pending');
        expect(IntStatusWithColor::BANNED->label())->toBe('Banned');
        expect(IntStatusWithColor::DRAFT->label())->toBe('Draft');
    });

    it('values() returns int backed values', function () {
        $values = IntStatusWithColor::values();

        expect($values)->toEqual([1, 2, 3, 4]);
    });

    it('forSelect() uses int values', function () {
        $select = IntStatusWithColor::forSelect();

        expect($select[0]['value'])->toBe(1);
        expect($select[1]['value'])->toBe(2);
        expect($select[2]['value'])->toBe(3);
        expect($select[3]['value'])->toBe(4);
    });

    it('forApi() returns int values in full metadata', function () {
        $api = IntStatusWithColor::forApi();

        expect($api[0]['value'])->toBe(1);
        expect($api[0]['color'])->toBe('success');
        expect($api[2]['value'])->toBe(3);
        expect($api[2]['color'])->toBe('danger');
    });

    it('tryFromName works with case names', function () {
        expect(IntStatusWithColor::tryFromName('ACTIVE'))->toBe(IntStatusWithColor::ACTIVE);
        expect(IntStatusWithColor::tryFromName('BANNED'))->toBe(IntStatusWithColor::BANNED);
    });

    it('comparison methods work with int-backed enum', function () {
        expect(IntStatusWithColor::ACTIVE->is(IntStatusWithColor::ACTIVE))->toBeTrue();
        expect(IntStatusWithColor::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(IntStatusWithColor::ACTIVE->is(IntStatusWithColor::BANNED))->toBeFalse();

        expect(IntStatusWithColor::ACTIVE->in([IntStatusWithColor::ACTIVE, IntStatusWithColor::DRAFT]))->toBeTrue();
        expect(IntStatusWithColor::ACTIVE->in([IntStatusWithColor::BANNED, IntStatusWithColor::PENDING]))->toBeFalse();
    });
});
