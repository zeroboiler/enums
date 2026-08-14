<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

/**
 * Fixture: Enum with dual-class-level attributes (bulk map + single override).
 *
 * Tests that:
 * - Class-level EnumLabel with both `labels` map and `label` single property
 * - Class-level EnumDescription with both `descriptions` map and `description` single property
 * - Per-case overrides always win over class-level (both map and single)
 */
#[EnumLabel(labels: ['a' => 'Class A', 'b' => 'Class B'], label: 'Fallback Label')]
#[EnumDescription(descriptions: ['a' => 'Class Desc A'], description: 'Fallback Description')]
#[EnumColor(success: ['a'], danger: ['b'], warning: ['c'])]
#[EnumIcon(default: 'heroicon-o-flag', icons: ['a' => 'heroicon-o-star'])]
enum DualOverrideTest: string
{
    use HasEnumMetadata;

    #[Label('Case A Override')]
    #[Description('Case A Desc Override')]
    #[Icon('heroicon-o-cog')]
    case A = 'a';

    #[Color('info')]
    case B = 'b';

    case C = 'c';
}

beforeEach(function () {
    EnumCache::resetInstance();
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('Dual class-level and case-level override resolution', function () {
    it('per-case Label overrides class-level EnumLabel map', function () {
        expect(DualOverrideTest::A->label())->toBe('Case A Override');
    });

    it('class-level EnumLabel map provides label for case without per-case Label', function () {
        expect(DualOverrideTest::B->label())->toBe('Class B');
    });

    it('auto-generates label when no class-level or per-case label exists', function () {
        // 'C' has no per-case Label, and no entry in the class-level `labels` map
        expect(DualOverrideTest::C->label())->toBe('C');
    });

    it('per-case Description overrides class-level EnumDescription map', function () {
        expect(DualOverrideTest::A->description())->toBe('Case A Desc Override');
    });

    it('class-level EnumDescription map provides description for unoverridden case', function () {
        expect(DualOverrideTest::B->description())->toBeNull(); // 'b' not in descriptions map
    });

    it('per-case Icon overrides class-level EnumIcon icon map', function () {
        expect(DualOverrideTest::A->icon())->toBe('heroicon-o-cog');
    });

    it('class-level EnumIcon icon map provides icon for unoverridden case', function () {
        expect(DualOverrideTest::B->icon())->toBeNull(); // 'b' not in icons map, falls through default
    });

    it('class-level EnumIcon default provides icon for cases without specific icon', function () {
        // 'C' has no per-case Icon and no entry in the class-level icons map
        // so it should get the default icon
        expect(DualOverrideTest::C->icon())->toBe('heroicon-o-flag');
    });

    it('per-case Color overrides class-level EnumColor', function () {
        expect(DualOverrideTest::B->color())->toBe('info');
    });

    it('class-level EnumColor provides color for unoverridden case', function () {
        expect(DualOverrideTest::A->color())->toBe('success');
        expect(DualOverrideTest::C->color())->toBe('warning');
    });
});

describe('DualOverrideTest bulk methods', function () {
    it('forSelect returns correct structure with overridden labels', function () {
        $select = DualOverrideTest::forSelect();

        expect($select)->toHaveCount(3);
        expect($select[0])->toBe(['value' => 'a', 'label' => 'Case A Override']);
        expect($select[1])->toBe(['value' => 'b', 'label' => 'Class B']);
        expect($select[2])->toBe(['value' => 'c', 'label' => 'C']);
    });

    it('forApi returns correct full metadata', function () {
        $api = DualOverrideTest::forApi();

        expect($api)->toHaveCount(3);

        // Case A: fully overridden
        expect($api[0]['value'])->toBe('a');
        expect($api[0]['name'])->toBe('A');
        expect($api[0]['label'])->toBe('Case A Override');
        expect($api[0]['description'])->toBe('Case A Desc Override');
        expect($api[0]['color'])->toBe('success');
        expect($api[0]['icon'])->toBe('heroicon-o-cog');

        // Case B: class-level label, per-case color
        expect($api[1]['value'])->toBe('b');
        expect($api[1]['label'])->toBe('Class B');
        expect($api[1]['color'])->toBe('info');

        // Case C: auto-generated label, default icon
        expect($api[2]['value'])->toBe('c');
        expect($api[2]['label'])->toBe('C');
        expect($api[2]['color'])->toBe('warning');
        expect($api[2]['icon'])->toBe('heroicon-o-flag');
    });
});
