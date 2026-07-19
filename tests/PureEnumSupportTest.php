<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\PureStatus;

/**
 * Tests for Issue #9: HasEnumMetadata works correctly on non-backed (pure) enums.
 *
 * The trait uses $this->name as a fallback when the enum is not backed,
 * so all metadata methods should work on pure enums too.
 */
describe('Issue #9: HasEnumMetadata on non-backed (pure) enums', function (): void {
    it('generates labels for pure enum cases', function (): void {
        expect(PureStatus::PUBLISHED->label())->toBe('Published');
        expect(PureStatus::DRAFT->label())->toBe('Draft');
        expect(PureStatus::ARCHIVED->label())->toBe('Archived');
    });

    it('generates forSelect using case name as value', function (): void {
        $options = PureStatus::forSelect();

        expect($options)->toHaveCount(3);
        expect($options[0])->toBe(['value' => 'PUBLISHED', 'label' => 'Published']);
        expect($options[1])->toBe(['value' => 'DRAFT', 'label' => 'Draft']);
        expect($options[2])->toBe(['value' => 'ARCHIVED', 'label' => 'Archived']);
    });

    it('generates forApi using case name as value', function (): void {
        $api = PureStatus::forApi();

        expect($api)->toHaveCount(3);
        expect($api[0]['value'])->toBe('PUBLISHED');
        expect($api[0]['name'])->toBe('PUBLISHED');
        expect($api[0]['label'])->toBe('Published');
    });

    it('tryFromLabel works on pure enums', function (): void {
        expect(PureStatus::tryFromLabel('Published'))->toBe(PureStatus::PUBLISHED);
        expect(PureStatus::tryFromLabel('Draft'))->toBe(PureStatus::DRAFT);
        expect(PureStatus::tryFromLabel('Archived'))->toBe(PureStatus::ARCHIVED);
    });

    it('tryFromLabel case-insensitive fallback works on pure enums', function (): void {
        expect(PureStatus::tryFromLabel('PUBLISHED'))->toBe(PureStatus::PUBLISHED);
        expect(PureStatus::tryFromLabel('draft'))->toBe(PureStatus::DRAFT);
    });

    it('tryFromLabel strict mode works on pure enums', function (): void {
        expect(PureStatus::tryFromLabel('Published', strict: true))
            ->toBe(PureStatus::PUBLISHED);
        expect(PureStatus::tryFromLabel('PUBLISHED', strict: true))
            ->toBeNull();
    });

    it('fromLabel works on pure enums', function (): void {
        expect(PureStatus::fromLabel('Published'))->toBe(PureStatus::PUBLISHED);
    });

    it('fromLabel throws on unknown label for pure enums', function (): void {
        expect(fn (): PureStatus => PureStatus::fromLabel('Unknown'))
            ->toThrow(ValueError::class);
    });

    it('returns correct values (case names) for pure enums', function (): void {
        expect(PureStatus::values())->toBe(['PUBLISHED', 'DRAFT', 'ARCHIVED']);
    });

    it('returns correct labels for pure enums', function (): void {
        expect(PureStatus::labels())->toBe(['Published', 'Draft', 'Archived']);
    });

    it('color defaults to secondary for pure enums', function (): void {
        expect(PureStatus::PUBLISHED->color())->toBe('secondary');
    });

    it('icon defaults to null for pure enums', function (): void {
        expect(PureStatus::PUBLISHED->icon())->toBeNull();
    });

    it('description defaults to null for pure enums', function (): void {
        expect(PureStatus::PUBLISHED->description())->toBeNull();
    });
});
