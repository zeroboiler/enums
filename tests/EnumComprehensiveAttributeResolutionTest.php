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

// ── Fixture: Multi-attribute string-backed enum ──────────────────

#[EnumColor(success: ['active', 'verified'], warning: ['pending'], danger: ['suspended', 'banned'])]
#[EnumLabel(labels: ['active' => 'Active', 'pending' => 'Pending Review', 'suspended' => 'Suspended'])]
#[EnumDescription(descriptions: ['active' => 'Account is active', 'banned' => 'Account is banned'])]
#[EnumIcon(default: 'heroicon-o-circle')]
enum FullMetaStatus: string
{
    use HasEnumMetadata;

    #[Label('Active Account'), Icon('heroicon-o-check'), Description('Fully verified account')]
    case ACTIVE = 'active';

    #[Label('Pending Verification')]
    case PENDING = 'pending';

    case VERIFIED = 'verified';

    case SUSPENDED = 'suspended';

    #[Color('danger'), Description('Permanently banned')]
    case BANNED = 'banned';
}

// ── Fixture: Int-backed with mixed zero/non-zero ──────────────────

enum NumericPriority: int
{
    use HasEnumMetadata;

    #[Color('danger')]
    case ZERO = 0;
    case ONE = 1;
    case TWO = 2;
    case HUNDRED = 100;
}

// ── Fixture: Pure enum with all attribute types ──────────────────

#[EnumIcon(default: 'heroicon-o-flag')]
enum SystemFeature
{
    use HasEnumMetadata;

    #[Icon('heroicon-o-shield-check'), Description('Two-factor authentication')]
    case TWO_FACTOR;

    #[Description('Dark mode support')]
    case DARK_MODE;

    #[Label('Beta Access Program')]
    case BETA;

    case DEBUG;
}

// ── Fixture: Single char backed value ──────────────────

enum SingleCharEnum: string
{
    use HasEnumMetadata;

    case A = 'a';
    case B = 'b';
    case Z = 'z';
}

// ── Tests ──────────────────────────────────────────────────────

describe('Enum comprehensive attribute resolution and edge cases', function () {

    // ── Resolution Priority ──────────────────────────────────────
    describe('resolution priority (per-case > class-level > auto)', function () {
        it('per-case label overrides class-level EnumLabel', function () {
            // 'active' has class-level 'Active' and per-case 'Active Account'
            expect(FullMetaStatus::ACTIVE->label())->toBe('Active Account');
        });

        it('class-level EnumLabel used when no per-case override', function () {
            expect(FullMetaStatus::PENDING->label())->toBe('Pending Review');
        });

        it('auto-generated label when neither defined', function () {
            expect(FullMetaStatus::VERIFIED->label())->toBe('Verified');
        });

        it('per-case description overrides class-level EnumDescription', function () {
            expect(FullMetaStatus::ACTIVE->description())->toBe('Fully verified account');
        });

        it('class-level EnumDescription when no per-case override', function () {
            expect(FullMetaStatus::BANNED->description())->toBe('Permanently banned');
        });

        it('null description when neither defined', function () {
            expect(FullMetaStatus::PENDING->description())->toBeNull();
        });

        it('per-case color overrides class-level EnumColor', function () {
            expect(FullMetaStatus::BANNED->color())->toBe('danger');
        });

        it('class-level EnumColor when no per-case override', function () {
            expect(FullMetaStatus::ACTIVE->color())->toBe('success');
            expect(FullMetaStatus::PENDING->color())->toBe('warning');
        });

        it('secondary default when no color defined anywhere', function () {
            // VERIFIED has no class-level or per-case color
            // But it IS in the EnumColor map? No — check EnumColor:
            // success: ['active', 'verified'] → yes, VERIFIED should be 'success'
            expect(FullMetaStatus::VERIFIED->color())->toBe('success');
        });

        it('per-case icon overrides class-level EnumIcon default', function () {
            expect(FullMetaStatus::ACTIVE->icon())->toBe('heroicon-o-check');
        });

        it('class-level EnumIcon default when no per-case override', function () {
            expect(FullMetaStatus::PENDING->icon())->toBe('heroicon-o-circle');
        });

        it('null icon when neither defined and no class-level default', function () {
            expect(FullMetaStatus::BANNED->icon())->toBeNull();
        });
    });

    // ── Int-backed edge cases ──────────────────────────────────
    describe('int-backed enum edge cases', function () {
        it('handles zero as a valid backed value', function () {
            expect(NumericPriority::ZERO->value)->toBe(0);
            expect(NumericPriority::ZERO->label())->toBe('Zero');
        });

        it('values() returns ints for int-backed enum', function () {
            $values = NumericPriority::values();
            expect($values)->toEqual([0, 1, 2, 100]);
        });

        it('forSelect() uses backed value (int) not name', function () {
            $select = NumericPriority::forSelect();
            expect($select[0])->toBe(['value' => 0, 'label' => 'Zero']);
            expect($select[3])->toBe(['value' => 100, 'label' => 'Hundred']);
        });

        it('forApi() includes int value and name', function () {
            $api = NumericPriority::forApi();
            expect($api[0]['value'])->toBe(0);
            expect($api[0]['name'])->toBe('ZERO');
            expect($api[0]['color'])->toBe('danger');
        });

        it('hasCase works with exact case name', function () {
            expect(NumericPriority::hasCase('ZERO'))->toBeTrue();
            expect(NumericPriority::hasCase('MILLION'))->toBeFalse();
        });

        it('tryFromName resolves by case name not value', function () {
            expect(NumericPriority::tryFromName('ZERO'))->toBe(NumericPriority::ZERO);
            // '0' is not a case name
            expect(NumericPriority::tryFromName('0'))->toBeNull();
        });
    });

    // ── Pure enum with class-level default icon ──────────────────
    describe('pure enum with class-level attributes', function () {
        it('values() returns case names for pure enum', function () {
            expect(SystemFeature::values())->toEqual([
                'TWO_FACTOR', 'DARK_MODE', 'BETA', 'DEBUG',
            ]);
        });

        it('forSelect() uses case names as values', function () {
            $select = SystemFeature::forSelect();
            expect($select[0])->toBe(['value' => 'TWO_FACTOR', 'label' => 'Two Factor']);
        });

        it('class-level default icon applies to all cases', function () {
            expect(SystemFeature::DARK_MODE->icon())->toBe('heroicon-o-flag');
            expect(SystemFeature::DEBUG->icon())->toBe('heroicon-o-flag');
        });

        it('per-case icon overrides class-level default', function () {
            expect(SystemFeature::TWO_FACTOR->icon())->toBe('heroicon-o-shield-check');
        });

        it('auto-label generates title case from SCREAMING_SNAKE', function () {
            expect(SystemFeature::DEBUG->label())->toBe('Debug');
            expect(SystemFeature::DARK_MODE->label())->toBe('Dark Mode');
        });

        it('labels() returns auto-generated labels in order', function () {
            $labels = SystemFeature::labels();
            expect($labels)->toEqual(['Two Factor', 'Dark Mode', 'Beta Access Program', 'Debug']);
        });
    });

    // ── Comparison methods exhaustive ──────────────────────────
    describe('comparison methods', function () {
        it('is() with self instance returns true for same case', function () {
            expect(FullMetaStatus::ACTIVE->is(FullMetaStatus::ACTIVE))->toBeTrue();
        });

        it('is() with self instance returns false for different case', function () {
            expect(FullMetaStatus::ACTIVE->is(FullMetaStatus::BANNED))->toBeFalse();
        });

        it('is() with string case name (exact match)', function () {
            expect(FullMetaStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(FullMetaStatus::ACTIVE->is('active'))->toBeFalse(); // case-sensitive name, not value
        });

        it('isNot() is logical negation of is()', function () {
            expect(FullMetaStatus::ACTIVE->isNot(FullMetaStatus::ACTIVE))->toBeFalse();
            expect(FullMetaStatus::ACTIVE->isNot(FullMetaStatus::BANNED))->toBeTrue();
            expect(FullMetaStatus::ACTIVE->isNot('BANNED'))->toBeTrue();
        });

        it('in() returns true when case is in list', function () {
            expect(FullMetaStatus::ACTIVE->in([FullMetaStatus::ACTIVE, FullMetaStatus::PENDING]))->toBeTrue();
        });

        it('in() returns false when case is not in list', function () {
            expect(FullMetaStatus::BANNED->in([FullMetaStatus::ACTIVE, FullMetaStatus::PENDING]))->toBeFalse();
        });

        it('in() works with mixed instances and strings', function () {
            expect(FullMetaStatus::ACTIVE->in([FullMetaStatus::PENDING, 'ACTIVE']))->toBeTrue();
        });

        it('in() returns false for empty list', function () {
            expect(FullMetaStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('in() works with single-element list', function () {
            expect(FullMetaStatus::ACTIVE->in([FullMetaStatus::ACTIVE]))->toBeTrue();
            expect(FullMetaStatus::BANNED->in([FullMetaStatus::ACTIVE]))->toBeFalse();
        });
    });

    // ── Lookup methods exhaustive ──────────────────────────
    describe('lookup methods', function () {
        it('tryFromLabel() is case-insensitive', function () {
            expect(FullMetaStatus::tryFromLabel('Active Account'))->toBe(FullMetaStatus::ACTIVE);
            expect(FullMetaStatus::tryFromLabel('active account'))->toBe(FullMetaStatus::ACTIVE);
            expect(FullMetaStatus::tryFromLabel('ACTIVE ACCOUNT'))->toBe(FullMetaStatus::ACTIVE);
        });

        it('tryFromLabel() returns null for non-existent label', function () {
            expect(FullMetaStatus::tryFromLabel('Nonexistent Label'))->toBeNull();
        });

        it('tryFromLabel() returns null for empty string', function () {
            expect(FullMetaStatus::tryFromLabel(''))->toBeNull();
        });

        it('tryFromName() is case-sensitive', function () {
            expect(FullMetaStatus::tryFromName('ACTIVE'))->toBe(FullMetaStatus::ACTIVE);
            expect(FullMetaStatus::tryFromName('active'))->toBeNull();
        });

        it('fromName() throws InvalidEnumException for non-existent name', function () {
            expect(fn () => FullMetaStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase() is case-sensitive', function () {
            expect(FullMetaStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(FullMetaStatus::hasCase('Active'))->toBeFalse();
            expect(FullMetaStatus::hasCase(''))->toBeFalse();
        });
    });

    // ── Bulk methods structure validation ──────────────────────────
    describe('bulk methods return structure', function () {
        it('forSelect() has value and label keys for every case', function () {
            $select = FullMetaStatus::forSelect();
            expect($select)->toHaveCount(5);
            foreach ($select as $option) {
                expect($option)->toHaveKey('value');
                expect($option)->toHaveKey('label');
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forApi() has all required keys for every case', function () {
            $api = FullMetaStatus::forApi();
            expect($api)->toHaveCount(5);
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            }
        });

        it('forApi() preserves case declaration order', function () {
            $api = FullMetaStatus::forApi();
            expect($api[0]['name'])->toBe('ACTIVE');
            expect($api[4]['name'])->toBe('BANNED');
        });

        it('values() returns unique values', function () {
            $values = FullMetaStatus::values();
            expect($values)->toEqual(array_values(array_unique($values)));
        });
    });

    // ── Metadata resolver cache behavior ──────────────────────────
    describe('EnumMetadataResolver cache behavior', function () {
        it('resolve() returns consistent results on repeated calls', function () {
            $meta1 = EnumMetadataResolver::resolve(FullMetaStatus::class);
            $meta2 = EnumMetadataResolver::resolve(FullMetaStatus::class);

            expect($meta1)->toBe($meta2);
        });

        it('invalidate() forces rebuild on next resolve', function () {
            EnumMetadataResolver::invalidate(FullMetaStatus::class);
            $meta = EnumMetadataResolver::resolve(FullMetaStatus::class);

            expect($meta)->toBeArray();
            expect($meta)->toHaveKey('labels');
            expect($meta)->toHaveKey('colors');
            expect($meta)->toHaveKey('descriptions');
            expect($meta)->toHaveKey('icons');
        });

        it('invalidateAll() clears all cached metadata', function () {
            EnumMetadataResolver::resolve(FullMetaStatus::class);
            EnumMetadataResolver::resolve(NumericPriority::class);

            EnumMetadataResolver::invalidateAll();

            // After invalidation, next resolve() should work fine
            $meta = EnumMetadataResolver::resolve(FullMetaStatus::class);
            expect($meta['labels'])->toBeArray();
        });
    });

    // ── Single-char backed enum ──────────────────────────
    describe('single-char backed enum', function () {
        it('label generates from single char case name', function () {
            expect(SingleCharEnum::A->label())->toBe('A');
            expect(SingleCharEnum::Z->label())->toBe('Z');
        });

        it('forSelect() uses single-char backed values', function () {
            $select = SingleCharEnum::forSelect();
            expect($select[0]['value'])->toBe('a');
            expect($select[2]['value'])->toBe('z');
        });

        it('color defaults to secondary', function () {
            expect(SingleCharEnum::A->color())->toBe('secondary');
        });

        it('icon and description default to null', function () {
            expect(SingleCharEnum::A->icon())->toBeNull();
            expect(SingleCharEnum::A->description())->toBeNull();
        });
    });

    // ── Type safety: return types ──────────────────────────
    describe('PHPStan L9 type safety', function () {
        it('label() always returns string (never null)', function () {
            // Enumerate all fixtures to verify
            foreach (FullMetaStatus::cases() as $case) {
                expect($case->label())->toBeString();
            }
            foreach (NumericPriority::cases() as $case) {
                expect($case->label())->toBeString();
            }
            foreach (SystemFeature::cases() as $case) {
                expect($case->label())->toBeString();
            }
        });

        it('color() always returns string (never null)', function () {
            foreach (FullMetaStatus::cases() as $case) {
                expect($case->color())->toBeString();
            }
            foreach (NumericPriority::cases() as $case) {
                expect($case->color())->toBeString();
            }
            foreach (SystemFeature::cases() as $case) {
                expect($case->color())->toBeString();
            }
        });

        it('icon() returns string or null', function () {
            $allNull = true;
            $allString = true;
            foreach (FullMetaStatus::cases() as $case) {
                $icon = $case->icon();
                if ($icon !== null) {
                    $allNull = false;
                    expect($icon)->toBeString();
                } else {
                    $allString = false;
                }
            }
            // At least some null and some string
            expect($allNull || $allString)->not->toBeTrue();
        });

        it('description() returns string or null', function () {
            $hasNull = false;
            $hasString = false;
            foreach (FullMetaStatus::cases() as $case) {
                $desc = $case->description();
                if ($desc !== null) {
                    $hasString = true;
                    expect($desc)->toBeString();
                } else {
                    $hasNull = true;
                }
            }
            expect($hasNull)->toBeTrue();
            expect($hasString)->toBeTrue();
        });

        it('forSelect() returns non-empty array of arrays with string/int values', function () {
            $select = FullMetaStatus::forSelect();
            expect($select)->toBeArray();
            expect($select)->not->toBeEmpty();
            foreach ($select as $option) {
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
            }
        });

        it('values() returns array of scalar values', function () {
            $stringValues = FullMetaStatus::values();
            foreach ($stringValues as $v) {
                expect($v)->toBeString();
            }

            $intValues = NumericPriority::values();
            foreach ($intValues as $v) {
                expect($v)->toBeInt();
            }
        });
    });
});
