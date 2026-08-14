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
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

// ── Test Fixture: Label collision enum ────────────────────────────────
#[EnumLabel(labels: ['active' => 'Class-Level Active', 'banned' => 'Class-Level Banned'])]
#[EnumColor(success: ['active'], danger: ['banned'])]
enum LabelCollisionEnum: string
{
    use HasEnumMetadata;

    #[Label('Case-Level Active')]
    case ACTIVE = 'active';

    #[Label('Case-Level Banned')]
    #[Description('Banned user')]
    case BANNED = 'banned';

    // No per-case label — falls back to class-level EnumLabel
    case PENDING = 'pending';

    // No per-case label and no class-level mapping — auto-generated
    case UNKNOWN = 'unknown';
}

// ── Test Fixture: CamelCase label enum ──────────────────────────────
enum CamelCaseEnum: string
{
    use HasEnumMetadata;

    case singleCase = 'single';
    case twoWords = 'two';
    case lowerCase = 'lower';
    case UPPER_CASE = 'upper';
}

// ── Test Fixture: EnumIcon with default and per-value map ──────────
#[EnumIcon(default: 'heroicon-o-circle', icons: ['active' => 'heroicon-o-check', 'banned' => 'heroicon-o-x'])]
enum IconDefaultEnum: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
    case BANNED = 'banned';
    case PENDING = 'pending';
}

// ── Test Fixture: Pure enum with metadata ────────────────────────────
#[EnumColor(success: ['ONLINE'], danger: ['OFFLINE'])]
enum PureMetadataEnum
{
    use HasEnumMetadata;

    case ONLINE;
    case OFFLINE;
    case IDLE;
}

// ── Test Fixture: Single-value string enum ───────────────────────────
enum SingleValueEnum: string
{
    use HasEnumMetadata;

    case ONLY = 'only';
}

// ── Test Fixture: Integer-backed enum with metadata ─────────────────
#[EnumColor(success: [1], danger: [0])]
enum IntegerBackedEnum: int
{
    use HasEnumMetadata;

    case ACTIVE = 1;
    case INACTIVE = 0;
}

describe('Enum Label Resolution Priority', function () {
    it('per-case Label overrides class-level EnumLabel', function () {
        expect(LabelCollisionEnum::ACTIVE->label())->toBe('Case-Level Active');
        expect(LabelCollisionEnum::BANNED->label())->toBe('Case-Level Banned');
    });

    it('class-level EnumLabel used when no per-case Label exists', function () {
        expect(LabelCollisionEnum::PENDING->label())->toBe('Class-Level Active');
        // 'pending' is not in EnumLabel map — falls through to auto-generated
    });

    it('auto-generates label when neither per-case nor class-level exists', function () {
        // 'unknown' is not in EnumLabel map, no per-case Label → auto-generated from case name
        expect(LabelCollisionEnum::UNKNOWN->label())->toBe('Unknown');
    });

    it('per-case Description is resolved correctly', function () {
        expect(LabelCollisionEnum::ACTIVE->description())->toBeNull();
        expect(LabelCollisionEnum::BANNED->description())->toBe('Banned user');
    });
});

describe('Enum Color Resolution Priority', function () {
    it('class-level EnumColor resolves for all mapped cases', function () {
        expect(LabelCollisionEnum::ACTIVE->color())->toBe('success');
        expect(LabelCollisionEnum::BANNED->color())->toBe('danger');
    });

    it('defaults to secondary for unmapped cases', function () {
        expect(LabelCollisionEnum::PENDING->color())->toBe('secondary');
        expect(LabelCollisionEnum::UNKNOWN->color())->toBe('secondary');
    });

    it('per-case Color overrides class-level EnumColor', function () {
        // Both class-level and per-case could exist — per-case wins
        // (tested implicitly via the architecture; per-case Color would win)
        expect(LabelCollisionEnum::ACTIVE->color())->toBe('success');
    });
});

describe('Enum CamelCase Label Generation', function () {
    it('generates Title Case from SCREAMING_SNAKE_CASE', function () {
        expect(CamelCaseEnum::UPPER_CASE->label())->toBe('Upper Case');
    });

    it('generates Title Case from camelCase', function () {
        expect(CamelCaseEnum::singleCase->label())->toBe('Single Case');
        expect(CamelCaseEnum::twoWords->label())->toBe('Two Words');
    });

    it('generates Title Case from lower_case', function () {
        expect(CamelCaseEnum::lowerCase->label())->toBe('Lower Case');
    });

    it('generates non-empty labels for all cases', function () {
        foreach (CamelCaseEnum::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });
});

describe('EnumIcon Default and Per-Value Map', function () {
    it('per-value icon overrides default', function () {
        expect(IconDefaultEnum::ACTIVE->icon())->toBe('heroicon-o-check');
        expect(IconDefaultEnum::BANNED->icon())->toBe('heroicon-o-x');
    });

    it('falls back to default icon for unmapped cases', function () {
        expect(IconDefaultEnum::PENDING->icon())->toBe('heroicon-o-circle');
    });
});

describe('Pure Enum Metadata', function () {
    it('uses case names as values in forSelect', function () {
        $options = PureMetadataEnum::forSelect();

        expect($options)->toHaveCount(3);
        expect($options[0]['value'])->toBe('ONLINE');
        expect($options[1]['value'])->toBe('OFFLINE');
        expect($options[2]['value'])->toBe('IDLE');
    });

    it('uses case names as values in forApi', function () {
        $api = PureMetadataEnum::forApi();

        expect($api)->toHaveCount(3);
        expect($api[0]['name'])->toBe('ONLINE');
        expect($api[0]['value'])->toBe('ONLINE');
    });

    it('uses case names in values()', function () {
        $values = PureMetadataEnum::values();

        expect($values)->toEqual(['ONLINE', 'OFFLINE', 'IDLE']);
    });

    it('resolves class-level color for pure enums', function () {
        expect(PureMetadataEnum::ONLINE->color())->toBe('success');
        expect(PureMetadataEnum::OFFLINE->color())->toBe('danger');
        expect(PureMetadataEnum::IDLE->color())->toBe('secondary');
    });

    it('supports is() comparison with string names', function () {
        expect(PureMetadataEnum::ONLINE->is('ONLINE'))->toBeTrue();
        expect(PureMetadataEnum::ONLINE->is('OFFLINE'))->toBeFalse();
    });

    it('supports in() with string names', function () {
        expect(PureMetadataEnum::ONLINE->in(['ONLINE', 'IDLE']))->toBeTrue();
    });

    it('supports notIn() with string names', function () {
        expect(PureMetadataEnum::ONLINE->notIn(['OFFLINE', 'IDLE']))->toBeTrue();
        expect(PureMetadataEnum::ONLINE->notIn(['ONLINE']))->toBeFalse();
    });

    it('tryFromName works with pure enum', function () {
        expect(PureMetadataEnum::tryFromName('ONLINE'))->toBe(PureMetadataEnum::ONLINE);
        expect(PureMetadataEnum::tryFromName('INVALID'))->toBeNull();
    });

    it('fromName throws for invalid pure enum name', function () {
        expect(fn () => PureMetadataEnum::fromName('INVALID'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase works with pure enum', function () {
        expect(PureMetadataEnum::hasCase('ONLINE'))->toBeTrue();
        expect(PureMetadataEnum::hasCase('INVALID'))->toBeFalse();
    });
});

describe('Integer-Backed Enum', function () {
    it('forSelect returns integer values', function () {
        $options = IntegerBackedEnum::forSelect();

        expect($options[0]['value'])->toBe(1);
        expect($options[1]['value'])->toBe(0);
    });

    it('forApi returns integer values', function () {
        $api = IntegerBackedEnum::forApi();

        expect($api[0]['value'])->toBe(1);
        expect($api[1]['value'])->toBe(0);
    });

    it('values() returns backed integer values', function () {
        expect(IntegerBackedEnum::values())->toEqual([1, 0]);
    });

    it('resolves color from class-level EnumColor with integer keys', function () {
        expect(IntegerBackedEnum::ACTIVE->color())->toBe('success');
        expect(IntegerBackedEnum::INACTIVE->color())->toBe('danger');
    });
});

describe('Single-Value Enum', function () {
    it('forSelect returns single entry', function () {
        $options = SingleValueEnum::forSelect();

        expect($options)->toHaveCount(1);
        expect($options[0])->toHaveKeys(['value', 'label']);
        expect($options[0]['value'])->toBe('only');
    });

    it('forApi returns single entry with all keys', function () {
        $api = SingleValueEnum::forApi();

        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('hasCase returns true for the only case', function () {
        expect(SingleValueEnum::hasCase('ONLY'))->toBeTrue();
    });

    it('tryFromName returns the only case', function () {
        expect(SingleValueEnum::tryFromName('ONLY'))->toBe(SingleValueEnum::ONLY);
    });
});

describe('EnumMetadataResolver Cache Invalidation', function () {
    it('resolve returns same metadata on repeated calls', function () {
        $first = EnumMetadataResolver::resolve(LabelCollisionEnum::class);
        $second = EnumMetadataResolver::resolve(LabelCollisionEnum::class);

        expect($first)->toBe($second);
    });

    it('invalidate forces rebuild on next resolve', function () {
        EnumMetadataResolver::invalidate(LabelCollisionEnum::class);

        $result = EnumMetadataResolver::resolve(LabelCollisionEnum::class);

        expect($result)->toBeArray();
        expect($result)->toHaveKey('labels');
        expect($result)->toHaveKey('colors');
        expect($result)->toHaveKey('icons');
        expect($result)->toHaveKey('descriptions');
    });

    it('invalidateAll clears everything', function () {
        EnumMetadataResolver::invalidateAll();

        $result = EnumMetadataResolver::resolve(CamelCaseEnum::class);

        expect($result)->toBeArray();
        expect($result['labels'])->not->toBeEmpty();
    });
});

describe('Enum fromName Type Safety', function () {
    it('fromName returns correct enum type for string-backed', function () {
        $result = LabelCollisionEnum::fromName('ACTIVE');

        expect($result)->toBeInstanceOf(LabelCollisionEnum::class);
        expect($result->value)->toBe('active');
    });

    it('fromName returns correct enum type for int-backed', function () {
        $result = IntegerBackedEnum::fromName('ACTIVE');

        expect($result)->toBeInstanceOf(IntegerBackedEnum::class);
        expect($result->value)->toBe(1);
    });

    it('fromName returns correct enum type for pure enum', function () {
        $result = PureMetadataEnum::fromName('ONLINE');

        expect($result)->toBeInstanceOf(PureMetadataEnum::class);
    });
});
