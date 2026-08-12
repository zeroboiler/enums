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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

// ─── Enum Fixtures ───────────────────────────────────────────────

#[EnumColor(success: ['active'], danger: ['banned'])]
#[EnumLabel(labels: ['active' => 'Active', 'banned' => 'Banned'])]
#[EnumIcon(default: 'heroicon-o-flag', icons: [1 => 'heroicon-o-check', 0 => 'heroicon-o-x-mark'])]
#[EnumDescription(descriptions: ['active' => 'User is active', 'banned' => 'User is banned'])]
enum FullFeaturedStringEnum: string
{
    use HasEnumMetadata;

    #[Color('warning')]
    #[Label('Pending User')]
    #[Icon('heroicon-o-clock')]
    #[Description('Awaiting verification')]
    case PENDING = 'pending';

    #[Color('success')]
    #[Description('Override description at case level')]
    case ACTIVE = 'active';

    #[Color('danger')]
    case BANNED = 'banned';
}

#[EnumColor(success: [1], warning: [2])]
enum IntBackedWithMetadata: int
{
    use HasEnumMetadata;

    case LOW = 0;
    case MEDIUM = 1;
    case HIGH = 2;
}

enum PureEnumNoAttributes
{
    use HasEnumMetadata;

    case ALPHA;
    case BETA;
    case GAMMA;
}

#[EnumLabel(label: 'Case-Level Override Via EnumLabel')]
enum CaseLevelEnumLabel: string
{
    use HasEnumMetadata;

    #[EnumLabel(label: 'Custom Label')]
    case FOO = 'foo';

    case BAR = 'bar';
}

#[EnumDescription(description: 'Case-Level Description Override')]
enum CaseLevelEnumDescription: string
{
    use HasEnumMetadata;

    case FIRST = 'first';
    case SECOND = 'second';
}

// ─── Tests ──────────────────────────────────────────────────────

describe('Enums — Production Quality Assurance', function () {
    // ── InvalidEnumException::__toString ─────────────────────────

    describe('InvalidEnumException __toString()', function () {
        it('formats value exception as string', function () {
            $e = InvalidEnumException::value(FullFeaturedStringEnum::class, 'invalid');
            $str = (string) $e;

            expect($str)->toBeString()
                ->toContain('InvalidEnumException')
                ->toContain('invalid')
                ->toContain('FullFeaturedStringEnum');
        });

        it('formats null value exception as string', function () {
            $e = InvalidEnumException::value(FullFeaturedStringEnum::class, null);
            $str = (string) $e;

            expect($str)->toBeString()
                ->toContain('null');
        });

        it('formats name exception as string', function () {
            $e = InvalidEnumException::forName(FullFeaturedStringEnum::class, 'NONEXISTENT');
            $str = (string) $e;

            expect($str)->toBeString()
                ->toContain('NONEXISTENT')
                ->toContain('FullFeaturedStringEnum');
        });
    });

    // ── Class-level EnumLabel with per-case overrides ────────────

    describe('Class-level EnumLabel per-case resolution', function () {
        it('returns class-level label when no per-case override exists', function () {
            expect(FullFeaturedStringEnum::ACTIVE->label())->toBe('Active');
        });

        it('returns per-case #[Label] override over class-level', function () {
            expect(FullFeaturedStringEnum::PENDING->label())->toBe('Pending User');
        });

        it('returns auto-generated label when no attribute exists', function () {
            // BANNED has no #[Label] override, and class-level EnumLabel has 'Banned'
            expect(FullFeaturedStringEnum::BANNED->label())->toBe('Banned');
        });
    });

    // ── Case-level EnumLabel attribute ────────────────────────────

    describe('Case-level EnumLabel attribute', function () {
        it('uses EnumLabel::label at case level for override', function () {
            expect(CaseLevelEnumLabel::FOO->label())->toBe('Custom Label');
        });

        it('falls back to class-level EnumLabel::label for cases without override', function () {
            expect(CaseLevelEnumLabel::BAR->label())->toBe('Case-Level Override Via EnumLabel');
        });
    });

    // ── Case-level EnumDescription attribute ─────────────────────

    describe('Case-level EnumDescription attribute', function () {
        it('applies class-level EnumDescription::description to all cases', function () {
            expect(CaseLevelEnumDescription::FIRST->description())->toBe('Case-Level Description Override');
            expect(CaseLevelEnumDescription::SECOND->description())->toBe('Case-Level Description Override');
        });
    });

    // ── Class-level EnumIcon per-case icon map ──────────────────

    describe('Class-level EnumIcon per-value icon map', function () {
        it('returns icon from per-value icon map for matching cases', function () {
            expect(IntBackedWithMetadata::MEDIUM->icon())->toBe('heroicon-o-check');
            expect(IntBackedWithMetadata::LOW->icon())->toBe('heroicon-o-x-mark');
        });

        it('returns default icon for cases not in the icon map', function () {
            expect(IntBackedWithMetadata::HIGH->icon())->toBe('heroicon-o-flag');
        });

        it('returns per-case #[Icon] override over class-level map', function () {
            expect(FullFeaturedStringEnum::PENDING->icon())->toBe('heroicon-o-clock');
        });

        it('returns class-level icon for cases without per-case override', function () {
            expect(FullFeaturedStringEnum::ACTIVE->icon())->toBe('heroicon-o-flag');
        });
    });

    // ── Pure enum metadata ──────────────────────────────────────

    describe('Pure enum without attributes', function () {
        it('auto-generates labels from case names', function () {
            expect(PureEnumNoAttributes::ALPHA->label())->toBe('Alpha');
            expect(PureEnumNoAttributes::BETA->label())->toBe('Beta');
            expect(PureEnumNoAttributes::GAMMA->label())->toBe('Gamma');
        });

        it('returns secondary as default color', function () {
            expect(PureEnumNoAttributes::ALPHA->color())->toBe('secondary');
        });

        it('returns null for icon and description', function () {
            expect(PureEnumNoAttributes::ALPHA->icon())->toBeNull();
            expect(PureEnumNoAttributes::ALPHA->description())->toBeNull();
        });

        it('values() returns case names for pure enums', function () {
            expect(PureEnumNoAttributes::values())->toBe([
                'ALPHA',
                'BETA',
                'GAMMA',
            ]);
        });

        it('forSelect() uses case names as values', function () {
            $select = PureEnumNoAttributes::forSelect();
            expect($select[0]['value'])->toBe('ALPHA');
            expect($select[0]['label'])->toBe('Alpha');
        });
    });

    // ── Int-backed enum metadata ─────────────────────────────────

    describe('Int-backed enum metadata', function () {
        it('values() returns backed int values', function () {
            $values = IntBackedWithMetadata::values();
            expect($values)->each->toBeInt();
            expect($values)->toHaveCount(3);
        });

        it('forSelect() uses int values', function () {
            $select = IntBackedWithMetadata::forSelect();
            expect($select[0])->toHaveKeys(['value', 'label']);
            expect($select[0]['value'])->toBeInt();
        });

        it('tryFromLabel is case-insensitive', function () {
            expect(IntBackedWithMetadata::tryFromLabel('Medium'))->toBe(IntBackedWithMetadata::MEDIUM);
            expect(IntBackedWithMetadata::tryFromLabel('MEDIUM'))->toBe(IntBackedWithMetadata::MEDIUM);
            expect(IntBackedWithMetadata::tryFromLabel('medium'))->toBe(IntBackedWithMetadata::MEDIUM);
        });
    });

    // ── Cache invalidation ───────────────────────────────────────

    describe('Cache invalidation via EnumMetadataResolver', function () {
        it('invalidates cache for a specific class', function () {
            EnumCache::getInstance()->setTtl(0);
            EnumCache::getInstance()->clear();

            // Resolve once (populates cache)
            $meta1 = EnumMetadataResolver::resolve(FullFeaturedStringEnum::class);

            // Invalidate
            EnumMetadataResolver::invalidate(FullFeaturedStringEnum::class);

            // Verify cache was cleared
            expect(EnumCache::getInstance()->has(FullFeaturedStringEnum::class))->toBeFalse();

            // Re-resolve should work fine
            $meta2 = EnumMetadataResolver::resolve(FullFeaturedStringEnum::class);
            expect($meta2)->toBe($meta1);
        });

        it('invalidates all cached metadata', function () {
            EnumCache::getInstance()->setTtl(0);
            EnumCache::getInstance()->clear();

            EnumMetadataResolver::resolve(FullFeaturedStringEnum::class);
            EnumMetadataResolver::resolve(IntBackedWithMetadata::class);

            EnumMetadataResolver::invalidateAll();

            expect(EnumCache::getInstance()->has(FullFeaturedStringEnum::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(IntBackedWithMetadata::class))->toBeFalse();
        });
    });

    // ── Comparison methods edge cases ───────────────────────────

    describe('Comparison method edge cases', function () {
        it('is() rejects case-insensitive string names', function () {
            $case = FullFeaturedStringEnum::ACTIVE;

            // is() uses strict === on case names
            expect($case->is('active'))->toBeFalse(); // 'active' is the VALUE, not the name
            expect($case->is('ACTIVE'))->toBeTrue();  // 'ACTIVE' is the case NAME
        });

        it('in() works with empty array', function () {
            expect(FullFeaturedStringEnum::ACTIVE->in([]))->toBeFalse();
        });

        it('in() accepts mixed instances and strings', function () {
            $result = FullFeaturedStringEnum::ACTIVE->in([
                FullFeaturedStringEnum::PENDING,
                'ACTIVE', // string name
            ]);
            expect($result)->toBeTrue();
        });
    });

    // ── Bulk method consistency ──────────────────────────────────

    describe('Bulk method consistency', function () {
        it('forSelect() and forApi() return same case count', function () {
            $select = FullFeaturedStringEnum::forSelect();
            $api = FullFeaturedStringEnum::forApi();

            expect($select)->toHaveCount($api->count());
        });

        it('forApi() entries have all required keys', function () {
            $api = FullFeaturedStringEnum::forApi();
            foreach ($api as $entry) {
                expect($entry)->toHaveKeys([
                    'value',
                    'name',
                    'label',
                    'description',
                    'color',
                    'icon',
                ]);
            }
        });

        it('values() and labels() have same count as cases', function () {
            $count = count(FullFeaturedStringEnum::cases());

            expect(FullFeaturedStringEnum::values())->toHaveCount($count);
            expect(FullFeaturedStringEnum::labels())->toHaveCount($count);
        });

        it('labels() returns non-empty strings', function () {
            $labels = FullFeaturedStringEnum::labels();
            foreach ($labels as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }
        });
    });
});
