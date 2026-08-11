<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;

describe('LabelMapEnum — class-level attribute maps', function () {
    it('applies class-level labels from EnumLabel map', function () {
        expect(LabelMapEnum::DRAFT->label())->toBe('Draft Article');
        expect(LabelMapEnum::PUBLISHED->label())->toBe('Published Article');
        expect(LabelMapEnum::ARCHIVED->label())->toBe('Archived Article');
    });

    it('falls back to auto-generated label for unmapped cases', function () {
        // TRASHED is not in the class-level label map
        // Auto-generated from 'TRASHED' → 'Trashed'
        expect(LabelMapEnum::TRASHED->label())->toBe('Trashed');
    });

    it('applies class-level descriptions from EnumDescription map', function () {
        expect(LabelMapEnum::DRAFT->description())->toBe('Article is in draft state');
        expect(LabelMapEnum::PUBLISHED->description())->toBe('Article is publicly visible');
    });

    it('returns null for unmapped case descriptions', function () {
        // ARCHIVED and TRASHED are not in the description map
        expect(LabelMapEnum::ARCHIVED->description())->toBeNull();
        expect(LabelMapEnum::TRASHED->description())->toBeNull();
    });

    it('applies default icon from EnumIcon to unmapped cases', function () {
        // DRAFT, ARCHIVED, TRASHED should get the default icon
        expect(LabelMapEnum::DRAFT->icon())->toBe('heroicon-o-document-text');
        expect(LabelMapEnum::ARCHIVED->icon())->toBe('heroicon-o-document-text');
        expect(LabelMapEnum::TRASHED->icon())->toBe('heroicon-o-document-text');
    });

    it('applies per-case icon from EnumIcon icons map', function () {
        // PUBLISHED has a specific icon in the map
        expect(LabelMapEnum::PUBLISHED->icon())->toBe('heroicon-o-globe');
    });

    it('returns correct number of forSelect options', function () {
        $options = LabelMapEnum::forSelect();
        expect($options)->toHaveCount(4);
    });

    it('forSelect values are unique', function () {
        $values = array_column(LabelMapEnum::forSelect(), 'value');
        expect($values)->toBe(['draft', 'published', 'archived', 'trashed']);
    });

    it('forApi contains all metadata fields per case', function () {
        $api = LabelMapEnum::forApi();
        expect($api)->toHaveCount(4);

        $draft = $api[0];
        expect($draft)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($draft['label'])->toBe('Draft Article');
        expect($draft['icon'])->toBe('heroicon-o-document-text');
    });

    it('colors default to secondary for unmapped cases', function () {
        expect(LabelMapEnum::DRAFT->color())->toBe('secondary');
        expect(LabelMapEnum::PUBLISHED->color())->toBe('secondary');
    });

    it('supports tryFromLabel with class-level labels', function () {
        $case = LabelMapEnum::tryFromLabel('Draft Article');
        expect($case)->toBe(LabelMapEnum::DRAFT);

        $case = LabelMapEnum::tryFromLabel('Published Article');
        expect($case)->toBe(LabelMapEnum::PUBLISHED);
    });

    it('tryFromLabel is case-insensitive', function () {
        $case = LabelMapEnum::tryFromLabel('draft article');
        expect($case)->toBe(LabelMapEnum::DRAFT);
    });

    it('supports tryFromName and fromName', function () {
        expect(LabelMapEnum::tryFromName('DRAFT'))->toBe(LabelMapEnum::DRAFT);
        expect(LabelMapEnum::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('values() returns all backed values', function () {
        expect(LabelMapEnum::values())->toBe(['draft', 'published', 'archived', 'trashed']);
    });

    it('labels() returns all labels in order', function () {
        expect(LabelMapEnum::labels())->toBe([
            'Draft Article',
            'Published Article',
            'Archived Article',
            'Trashed',
        ]);
    });
});
