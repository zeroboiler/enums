<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;

describe('Int-backed enum metadata resolution', function () {
    it('resolves per-case labels for int-backed enum', function () {
        expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
        expect(IntBackedPriority::HIGH->label())->toBe('High Priority');
        expect(IntBackedPriority::LOW->label())->toBe('Low Priority');
        expect(IntBackedPriority::NONE->label())->toBe('None'); // auto-generated
    });

    it('resolves class-level labels for int-backed enum', function () {
        // CRITICAL = 1, should get class-level label
        expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
        // NONE = 4, no class-level or per-case label → auto-generated
        expect(IntBackedPriority::NONE->label())->toBe('None');
    });

    it('resolves per-case color overrides for int-backed enum', function () {
        expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
        expect(IntBackedPriority::HIGH->color())->toBe('warning');
        expect(IntBackedPriority::LOW->color())->toBe('success');
    });

    it('resolves class-level color for int-backed enum', function () {
        // NONE = 4, class-level EnumColor has success: [3, 4]
        expect(IntBackedPriority::NONE->color())->toBe('success');
    });

    it('resolves class-level description for int-backed enum', function () {
        expect(IntBackedPriority::CRITICAL->description())->toBe('Critical priority — immediate action required');
        expect(IntBackedPriority::LOW->description())->toBe('Low priority — handle when convenient');
    });

    it('returns null description when none defined', function () {
        expect(IntBackedPriority::HIGH->description())->toBeNull();
        expect(IntBackedPriority::NONE->description())->toBeNull();
    });

    it('resolves class-level default icon for int-backed enum', function () {
        expect(IntBackedPriority::CRITICAL->icon())->toBe('heroicon-o-flag');
        expect(IntBackedPriority::NONE->icon())->toBe('heroicon-o-flag');
    });

    it('returns int backed values from values()', function () {
        $values = IntBackedPriority::values();
        expect($values)->toBe([1, 2, 3, 4]);
        expect($values)->each->toBeInt();
    });

    it('returns correct forSelect structure with int values', function () {
        $select = IntBackedPriority::forSelect();
        expect($select)->toHaveCount(4);

        $first = $select[0];
        expect($first)->toHaveKey('value');
        expect($first)->toHaveKey('label');
        expect($first['value'])->toBeInt();
        expect($first['label'])->toBeString()->not->toBeEmpty();
    });

    it('returns correct forApi structure with int values', function () {
        $api = IntBackedPriority::forApi();
        expect($api)->toHaveCount(4);

        $critical = $api[0];
        expect($critical)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($critical['value'])->toBe(1);
        expect($critical['name'])->toBe('CRITICAL');
        expect($critical['color'])->toBe('danger');
    });

    it('compares via is() with enum instances', function () {
        expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::CRITICAL))->toBeTrue();
        expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::HIGH))->toBeFalse();
    });

    it('compares via is() with string names', function () {
        expect(IntBackedPriority::CRITICAL->is('CRITICAL'))->toBeTrue();
        expect(IntBackedPriority::CRITICAL->is('HIGH'))->toBeFalse();
    });

    it('compares via isNot()', function () {
        expect(IntBackedPriority::CRITICAL->isNot(IntBackedPriority::HIGH))->toBeTrue();
        expect(IntBackedPriority::CRITICAL->isNot(IntBackedPriority::CRITICAL))->toBeFalse();
    });

    it('compares via in() with instances', function () {
        expect(IntBackedPriority::CRITICAL->in([IntBackedPriority::CRITICAL, IntBackedPriority::HIGH]))->toBeTrue();
        expect(IntBackedPriority::NONE->in([IntBackedPriority::CRITICAL]))->toBeFalse();
    });

    it('compares via in() with mixed instances and strings', function () {
        expect(IntBackedPriority::CRITICAL->in([IntBackedPriority::HIGH, 'CRITICAL']))->toBeTrue();
    });

    it('lookups via tryFromName', function () {
        expect(IntBackedPriority::tryFromName('CRITICAL'))->toBeInstanceOf(IntBackedPriority::class);
        expect(IntBackedPriority::tryFromName('CRITICAL')->value)->toBe(1);
        expect(IntBackedPriority::tryFromName('UNKNOWN'))->toBeNull();
    });

    it('lookups via fromName throws for invalid name', function () {
        expect(fn () => IntBackedPriority::fromName('INVALID'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase returns correct bool', function () {
        expect(IntBackedPriority::hasCase('CRITICAL'))->toBeTrue();
        expect(IntBackedPriority::hasCase('NONEXISTENT'))->toBeFalse();
    });

    it('tryFromLabel reverse lookup works', function () {
        $case = IntBackedPriority::tryFromLabel('Critical Priority');
        expect($case)->toBeInstanceOf(IntBackedPriority::class);
        expect($case->value)->toBe(1);
    });

    it('tryFromLabel is case-insensitive', function () {
        $case = IntBackedPriority::tryFromLabel('critical priority');
        expect($case)->toBeInstanceOf(IntBackedPriority::class);
    });

    it('select option values are unique', function () {
        $values = array_column(IntBackedPriority::forSelect(), 'value');
        expect($values)->each->toBeUnique();
    });

    it('labels returns all labels in order', function () {
        $labels = IntBackedPriority::labels();
        expect($labels)->toHaveCount(4);
        expect($labels)->each->toBeString()->not->toBeEmpty();
    });
});

describe('Pure enum metadata resolution', function () {
    it('resolves per-case labels for pure enum', function () {
        expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
        expect(PureFeatureFlag::BETA_FEATURES->label())->toBe('Beta Features');
        expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode'); // auto-generated
    });

    it('resolves per-case colors for pure enum', function () {
        expect(PureFeatureFlag::DARK_MODE->color())->toBe('secondary');
        expect(PureFeatureFlag::BETA_FEATURES->color())->toBe('warning');
        expect(PureFeatureFlag::MAINTENANCE_MODE->color())->toBe('secondary'); // default
    });

    it('resolves per-case icons for pure enum', function () {
        expect(PureFeatureFlag::DARK_MODE->icon())->toBe('heroicon-o-moon');
        expect(PureFeatureFlag::BETA_FEATURES->icon())->toBe('heroicon-o-beaker');
        expect(PureFeatureFlag::MAINTENANCE_MODE->icon())->toBeNull();
    });

    it('resolves per-case descriptions for pure enum', function () {
        expect(PureFeatureFlag::DARK_MODE->description())->toBe('Toggle dark mode for the UI');
        expect(PureFeatureFlag::BETA_FEATURES->description())->toBe('Enable experimental beta features');
        expect(PureFeatureFlag::MAINTENANCE_MODE->description())->toBeNull();
    });

    it('returns case names from values()', function () {
        $values = PureFeatureFlag::values();
        expect($values)->toBe(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
    });

    it('returns correct forSelect with case names', function () {
        $select = PureFeatureFlag::forSelect();
        expect($select)->toHaveCount(3);
        expect($select[0]['value'])->toBe('DARK_MODE');
        expect($select[0]['label'])->toBe('Dark Mode');
    });

    it('returns correct forApi with case names', function () {
        $api = PureFeatureFlag::forApi();
        expect($api)->toHaveCount(3);
        expect($api[0]['value'])->toBe('DARK_MODE');
        expect($api[0]['name'])->toBe('DARK_MODE');
        expect($api[0]['icon'])->toBe('heroicon-o-moon');
    });

    it('comparison methods work with pure enum', function () {
        expect(PureFeatureFlag::DARK_MODE->is(PureFeatureFlag::DARK_MODE))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->is('BETA_FEATURES'))->toBeFalse();
        expect(PureFeatureFlag::DARK_MODE->in([PureFeatureFlag::BETA_FEATURES, 'DARK_MODE']))->toBeTrue();
    });

    it('lookup methods work with pure enum', function () {
        expect(PureFeatureFlag::tryFromName('DARK_MODE'))->toBeInstanceOf(PureFeatureFlag::class);
        expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::hasCase('UNKNOWN'))->toBeFalse();
    });

    it('tryFromLabel works with pure enum', function () {
        expect(PureFeatureFlag::tryFromLabel('Dark Mode'))->toBe(PureFeatureFlag::DARK_MODE);
        expect(PureFeatureFlag::tryFromLabel('dark mode'))->toBe(PureFeatureFlag::DARK_MODE); // case-insensitive
    });
});
