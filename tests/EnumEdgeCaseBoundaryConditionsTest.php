<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\EnumCache;

// ── Fixtures ──────────────────────────────────────────────────

#[\ZeroBoiler\Enums\Attributes\EnumLabel(labels: ['alpha' => 'Alpha', 'beta' => 'Beta'])]
#[\ZeroBoiler\Enums\Attributes\EnumColor(success: ['alpha'], danger: ['beta'])]
enum DuplicateLabelEnum: string
{
    use HasEnumMetadata;

    #[\ZeroBoiler\Enums\Attributes\Label('Same Label')]
    case ALPHA = 'alpha';

    #[\ZeroBoiler\Enums\Attributes\Label('Same Label')]
    case BETA = 'beta';

    case GAMMA = 'gamma';
}

enum EmptyAfterFilterEnum: string
{
    use HasEnumMetadata;

    case SINGLE = 'single';
}

enum CamelCaseEnum: string
{
    use HasEnumMetadata;

    case ActiveUser = 'active_user';
    case PendingReview = 'pending_review';
}

enum IntBackedStrictEnum: int
{
    use HasEnumMetadata;

    case FIRST = 1;
    case SECOND = 2;
    case THIRD = 3;
}

enum PureStateEnum
{
    use HasEnumMetadata;

    case IDLE;
    case RUNNING;
    case STOPPED;
}

describe('Enum edge case boundary conditions', function () {
    // ── tryFromLabel with duplicate labels ───────────────────────
    describe('tryFromLabel with duplicate labels', function () {
        it('returns the first matching case when multiple cases share the same label', function () {
            $result = DuplicateLabelEnum::tryFromLabel('Same Label');

            expect($result)->not->toBeNull();
            expect($result)->toBe(DuplicateLabelEnum::ALPHA);
        });

        it('does not return the second case with the same label', function () {
            $result = DuplicateLabelEnum::tryFromLabel('Same Label');

            expect($result)->not->toBe(DuplicateLabelEnum::BETA);
        });

        it('still resolves unique labels correctly', function () {
            $result = DuplicateLabelEnum::tryFromLabel('Gamma');

            expect($result)->toBe(DuplicateLabelEnum::GAMMA);
        });
    });

    // ── in() with empty array ───────────────────────────────────
    describe('in() method edge cases', function () {
        it('returns false for empty cases array', function () {
            expect(IntBackedStrictEnum::FIRST->in([]))->toBeFalse();
        });

        it('returns true when the single element matches', function () {
            expect(IntBackedStrictEnum::FIRST->in([IntBackedStrictEnum::FIRST]))->toBeTrue();
        });

        it('returns false when the single element does not match', function () {
            expect(IntBackedStrictEnum::FIRST->in([IntBackedStrictEnum::SECOND]))->toBeFalse();
        });

        it('works with mixed instance and string arguments', function () {
            expect(IntBackedStrictEnum::FIRST->in([IntBackedStrictEnum::SECOND, 'FIRST']))->toBeTrue();
        });
    });

    // ── EnumCache TTL normalization ────────────────────────────
    describe('EnumCache TTL boundary conditions', function () {
        beforeEach(function () {
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::resetInstance();
        });

        it('normalizes negative TTL to zero', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);

            expect($cache->getTtl())->toBe(0);
        });

        it('accepts zero TTL as valid', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);

            expect($cache->getTtl())->toBe(0);
            expect($cache->has('AnyClass'))->toBeFalse();
        });

        it('preserves positive TTL values exactly', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(60);

            expect($cache->getTtl())->toBe(60);
        });
    });

    // ── fromName / tryFromName case sensitivity ───────────────
    describe('name lookup case sensitivity', function () {
        it('tryFromName is case-sensitive — lowercase does not match uppercase', function () {
            expect(IntBackedStrictEnum::tryFromName('first'))->toBeNull();
        });

        it('tryFromName returns correct case for exact match', function () {
            expect(IntBackedStrictEnum::tryFromName('FIRST'))->toBe(IntBackedStrictEnum::FIRST);
        });

        it('fromName throws for wrong case', function () {
            expect(fn () => IntBackedStrictEnum::fromName('first'))
                ->toThrow(InvalidEnumException::class);
        });

        it('fromName throws for non-existent name', function () {
            expect(fn () => IntBackedStrictEnum::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });
    });

    // ── CamelCase label generation ──────────────────────────────
    describe('auto-generated labels for camelCase', function () {
        it('converts camelCase case name to Title Case', function () {
            expect(CamelCaseEnum::ActiveUser->label())->toBe('Active User');
        });

        it('generates label for PendingReview', function () {
            expect(CamelCaseEnum::PendingReview->label())->toBe('Pending Review');
        });
    });

    // ── Pure enum specific behavior ────────────────────────────
    describe('pure enum behavior', function () {
        it('values() returns case names for pure enums', function () {
            $values = PureStateEnum::values();

            expect($values)->toEqual(['IDLE', 'RUNNING', 'STOPPED']);
        });

        it('forSelect() uses case names as values for pure enums', function () {
            $select = PureStateEnum::forSelect();

            expect($select)->toBeArray();
            expect($select[0])->toHaveKey('value');
            expect($select[0]['value'])->toBe('IDLE');
        });

        it('color() defaults to secondary for pure enum cases without color', function () {
            expect(PureStateEnum::IDLE->color())->toBe('secondary');
        });

        it('icon() defaults to null for pure enum cases without icon', function () {
            expect(PureStateEnum::IDLE->icon())->toBeNull();
        });

        it('description() defaults to null for pure enum cases without description', function () {
            expect(PureStateEnum::IDLE->description())->toBeNull();
        });

        it('is() works with string comparison on pure enums', function () {
            expect(PureStateEnum::IDLE->is('IDLE'))->toBeTrue();
            expect(PureStateEnum::IDLE->is('RUNNING'))->toBeFalse();
        });
    });

    // ── Class-level metadata resolution ────────────────────────
    describe('class-level attribute resolution with DuplicateLabelEnum', function () {
        it('class-level EnumLabel is overridden by per-case Label', function () {
            // ALPHA has per-case Label('Same Label'), not the class-level one
            expect(DuplicateLabelEnum::ALPHA->label())->toBe('Same Label');
        });

        it('GAMMA gets its label from class-level EnumLabel map', function () {
            // No per-case label for GAMMA, so class-level 'gamma' => 'Gamma' applies
            // But GAMMA doesn't exist in class-level map, so auto-generated from name
            expect(DuplicateLabelEnum::GAMMA->label())->toBe('Gamma');
        });

        it('ALPHA gets color from class-level EnumColor', function () {
            expect(DuplicateLabelEnum::ALPHA->color())->toBe('success');
        });

        it('BETA gets color from class-level EnumColor', function () {
            expect(DuplicateLabelEnum::BETA->color())->toBe('danger');
        });

        it('GAMMA defaults to secondary color (not in any color map)', function () {
            expect(DuplicateLabelEnum::GAMMA->color())->toBe('secondary');
        });
    });

    // ── Metadata cache invalidation ────────────────────────────
    describe('metadata cache invalidation cycle', function () {
        it('invalidate() forces re-resolution on next access', function () {
            // Resolve first to populate cache
            EnumMetadataResolver::resolve(IntBackedStrictEnum::class);

            // Invalidate
            EnumMetadataResolver::invalidate(IntBackedStrictEnum::class);

            // Re-resolve should still work
            $meta = EnumMetadataResolver::resolve(IntBackedStrictEnum::class);
            expect($meta)->toBeArray();
            expect($meta)->toHaveKey('labels');
        });

        it('invalidateAll() clears all cached metadata', function () {
            EnumMetadataResolver::resolve(IntBackedStrictEnum::class);
            EnumMetadataResolver::resolve(PureStateEnum::class);

            EnumMetadataResolver::invalidateAll();

            // Both should re-resolve without error
            $meta1 = EnumMetadataResolver::resolve(IntBackedStrictEnum::class);
            $meta2 = EnumMetadataResolver::resolve(PureStateEnum::class);

            expect($meta1)->toBeArray();
            expect($meta2)->toBeArray();
        });
    });

    // ── labels() and values() consistency ───────────────────────
    describe('bulk method consistency', function () {
        it('labels() and values() return arrays of the same count', function () {
            $labels = IntBackedStrictEnum::labels();
            $values = IntBackedStrictEnum::values();

            expect(count($labels))->toBe(count($values));
            expect(count($labels))->toBe(3);
        });

        it('forApi() returns array with all expected keys', function () {
            $api = IntBackedStrictEnum::forApi();

            expect($api)->toBeArray();
            expect(count($api))->toBe(3);

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            }
        });

        it('forApi() preserves declaration order', function () {
            $api = IntBackedStrictEnum::forApi();

            expect($api[0]['name'])->toBe('FIRST');
            expect($api[1]['name'])->toBe('SECOND');
            expect($api[2]['name'])->toBe('THIRD');
        });
    });

    // ── EnumRule with pure enum validation ─────────────────────
    describe('EnumRule with pure enums', function () {
        it('passes for valid pure enum case name', function () {
            $rule = EnumRule::for(PureStateEnum::class);
            $fail = false;

            $rule->validate('state', 'IDLE', function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();
        });

        it('fails for invalid pure enum case name', function () {
            $rule = EnumRule::for(PureStateEnum::class);
            $fail = false;

            $rule->validate('state', 'INVALID', function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });

        it('passes null when nullable for pure enum', function () {
            $rule = EnumRule::for(PureStateEnum::class)->nullable();
            $fail = false;

            $rule->validate('state', null, function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();
        });
    });

    // ── hasCase edge cases ────────────────────────────────────
    describe('hasCase edge cases', function () {
        it('returns true for valid case name', function () {
            expect(IntBackedStrictEnum::hasCase('FIRST'))->toBeTrue();
        });

        it('returns false for empty string', function () {
            expect(IntBackedStrictEnum::hasCase(''))->toBeFalse();
        });

        it('returns false for lowercase variant', function () {
            expect(IntBackedStrictEnum::hasCase('first'))->toBeFalse();
        });
    });
});
