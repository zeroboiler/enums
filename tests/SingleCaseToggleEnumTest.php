<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;

describe('SingleCaseToggle — single-case enum edge cases', function (): void {
    it('has exactly one case', function (): void {
        expect(SingleCaseToggle::cases())->toHaveCount(1);
    });

    it('resolves class-level label', function (): void {
        expect(SingleCaseToggle::ON->label())->toBe('Enabled');
    });

    it('resolves class-level color', function (): void {
        expect(SingleCaseToggle::ON->color())->toBe('success');
    });

    it('resolves class-level description', function (): void {
        expect(SingleCaseToggle::ON->description())->toBe('Feature is enabled');
    });

    it('resolves default icon from EnumIcon', function (): void {
        expect(SingleCaseToggle::ON->icon())->toBe('heroicon-o-check');
    });

    it('forSelect returns a single element', function (): void {
        $options = SingleCaseToggle::forSelect();

        expect($options)->toHaveCount(1);
        expect($options[0])->toBe(['value' => 'on', 'label' => 'Enabled']);
    });

    it('forApi returns a single element with full metadata', function (): void {
        $api = SingleCaseToggle::forApi();

        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($api[0]['value'])->toBe('on');
        expect($api[0]['name'])->toBe('ON');
    });

    it('values returns a single-element array', function (): void {
        expect(SingleCaseToggle::values())->toBe(['on']);
    });

    it('labels returns a single-element array', function (): void {
        expect(SingleCaseToggle::labels())->toBe(['Enabled']);
    });

    it('is() returns true when comparing to itself', function (): void {
        expect(SingleCaseToggle::ON->is(SingleCaseToggle::ON))->toBeTrue();
        expect(SingleCaseToggle::ON->is('ON'))->toBeTrue();
    });

    it('is() returns false for non-existent case name', function (): void {
        expect(SingleCaseToggle::ON->is('OFF'))->toBeFalse();
    });

    it('in() returns true when containing self', function (): void {
        expect(SingleCaseToggle::ON->in([SingleCaseToggle::ON]))->toBeTrue();
    });

    it('in() returns false for empty array', function (): void {
        expect(SingleCaseToggle::ON->in([]))->toBeFalse();
    });

    it('tryFromLabel finds the only label', function (): void {
        expect(SingleCaseToggle::tryFromLabel('Enabled'))->toBe(SingleCaseToggle::ON);
    });

    it('tryFromLabel is case-insensitive', function (): void {
        expect(SingleCaseToggle::tryFromLabel('enabled'))->toBe(SingleCaseToggle::ON);
        expect(SingleCaseToggle::tryFromLabel('ENABLED'))->toBe(SingleCaseToggle::ON);
    });

    it('tryFromLabel returns null for unknown label', function (): void {
        expect(SingleCaseToggle::tryFromLabel('Disabled'))->toBeNull();
    });

    it('tryFromName finds the only case', function (): void {
        expect(SingleCaseToggle::tryFromName('ON'))->toBe(SingleCaseToggle::ON);
    });

    it('fromName returns the only case', function (): void {
        expect(SingleCaseToggle::fromName('ON'))->toBe(SingleCaseToggle::ON);
    });

    it('hasCase returns true for the existing case', function (): void {
        expect(SingleCaseToggle::hasCase('ON'))->toBeTrue();
    });

    it('hasCase returns false for non-existent case', function (): void {
        expect(SingleCaseToggle::hasCase('OFF'))->toBeFalse();
    });
});
