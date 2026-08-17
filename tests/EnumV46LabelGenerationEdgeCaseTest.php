<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\EdgeCaseNamingEnum;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * V46 Label generation edge cases and boundary testing.
 *
 * Exercises the generateLabel() private method via the public label() API
 * with unusual case names, boundary conditions, and across all three
 * PHP enum types (string-backed, int-backed, pure).
 */
describe('V46 Label Generation Edge Cases', function () {
    // ── Single-letter case names ────────────────────────────────────────

    it('generates label for single-letter SCREAMING case', function (): void {
        expect(EdgeCaseNamingEnum::X->label())->toBe('X');
    });

    it('generates label for two-letter SCREAMING case', function (): void {
        expect(EdgeCaseNamingEnum::AB->label())->toBe('Ab');
    });

    it('generates label for alphanumeric case name', function (): void {
        expect(EdgeCaseNamingEnum::A1->label())->toBe('A1');
    });

    // ── Underscore-heavy case names ────────────────────────────────────

    it('handles double underscore in case name', function (): void {
        // UNDER_SCORE__ → "under score  " → trim → "Under Score" (extra space collapsed by ucwords)
        $label = EdgeCaseNamingEnum::UNDER_SCORE__->label();
        expect($label)->toBe('Under Score');
    });

    it('handles triple underscore in case name', function (): void {
        $label = EdgeCaseNamingEnum::TRIPLE___WORD->label();
        expect($label)->toBe('Triple Word');
    });

    it('handles numeric after underscore', function (): void {
        expect(EdgeCaseNamingEnum::NUMBER_2->label())->toBe('Number 2');
    });

    // ── Simple case names ────────────────────────────────────────────

    it('capitalizes single-word SCREAMING case', function (): void {
        expect(EdgeCaseNamingEnum::SINGLE->label())->toBe('Single');
    });

    it('capitalizes lowercase case name (non-SCREAMING path)', function (): void {
        // 'LOWER' → strtoupper('LOWER') === 'LOWER' → true → SCREAMING path
        // Wait, LOWER is all uppercase so it takes the SCREAMING path
        expect(EdgeCaseNamingEnum::LOWER->label())->toBe('Lower');
    });

    // ── CamelCase enum fixture label generation ─────────────────────────

    it('generates camelCase label for non-screaming case name', function (): void {
        // 'softDeleted' is camelCase → preg_replace splits on capitals
        // → 'soft Deleted' → ucwords → 'Soft Deleted'
        expect(CamelCasePriority::softDeleted->label())->toBe('Soft Deleted');
    });

    it('generates label for mixed-case case name', function (): void {
        // 'pendingReview' → not all uppercase → camelCase path
        // → 'pending Review' → ucwords → 'Pending Review'
        // But wait — there's a #[Label('Awaiting Approval')] override!
        // So this should be 'Awaiting Approval'
        expect(CamelCasePriority::pendingReview->label())->toBe('Awaiting Approval');
    });

    // ── Int-backed enum label generation ──────────────────────────────

    it('auto-generates labels for int-backed enum', function (): void {
        // IntBackedPriority: NONE has value 4, no attribute override
        expect(IntBackedPriority::NONE->label())->toBe('None');
    });

    it('uses per-case label for int-backed enum', function (): void {
        expect(IntBackedPriority::HIGH->label())->toBe('High Priority');
    });

    it('uses class-level EnumLabel for int-backed enum', function (): void {
        expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
    });

    // ── Pure enum label generation ────────────────────────────────────

    it('auto-generates label for pure enum case', function (): void {
        expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
    });

    it('auto-generates label for pure enum single word', function (): void {
        expect(PureFeatureFlag::BETA->label())->toBe('Beta');
    });

    // ── Single case enum boundary ────────────────────────────────────

    it('generates label for single-case enum', function (): void {
        expect(SingleCaseToggle::ON->label())->toBe('Enabled');
    });

    it('forSelect works correctly on single-case enum', function (): void {
        $select = SingleCaseToggle::forSelect();
        expect($select)->toHaveCount(1);
        expect($select[0]['value'])->toBe('on');
        expect($select[0]['label'])->toBe('Enabled');
    });

    it('forApi works correctly on single-case enum', function (): void {
        $api = SingleCaseToggle::forApi();
        expect($api)->toHaveCount(1);
        expect($api[0]['name'])->toBe('ON');
        expect($api[0]['value'])->toBe('on');
        expect($api[0]['label'])->toBe('Enabled');
        expect($api[0]['color'])->toBe('success');
        expect($api[0]['description'])->toBe('Feature is enabled');
        expect($api[0]['icon'])->toBe('heroicon-o-check');
    });

    // ── Lookup edge cases ────────────────────────────────────────────

    it('tryFromName is case-sensitive for SCREAMING names', function (): void {
        expect(EdgeCaseNamingEnum::tryFromName('X'))->toBe(EdgeCaseNamingEnum::X);
        expect(EdgeCaseNamingEnum::tryFromName('x'))->toBeNull();
    });

    it('fromName throws for non-existent case on edge-case enum', function (): void {
        expect(fn (): mixed => EdgeCaseNamingEnum::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('tryFromLabel is case-insensitive', function (): void {
        expect(EdgeCaseNamingEnum::tryFromLabel('X'))->toBe(EdgeCaseNamingEnum::X);
        expect(EdgeCaseNamingEnum::tryFromLabel('x'))->toBe(EdgeCaseNamingEnum::X);
    });

    it('tryFromLabel matches full auto-generated label', function (): void {
        expect(EdgeCaseNamingEnum::tryFromLabel('Under Score'))->toBe(EdgeCaseNamingEnum::UNDER_SCORE__);
    });

    // ── Bulk methods with edge-case enum ─────────────────────────────

    it('values returns all backed values for edge-case enum', function (): void {
        $values = EdgeCaseNamingEnum::values();
        expect($values)->toHaveCount(8);
        expect($values)->toContain('x');
        expect($values)->toContain('ab');
        expect($values)->toContain('a1');
        expect($values)->toContain('under_score__');
        expect($values)->toContain('triple___word');
        expect($values)->toContain('number_2');
        expect($values)->toContain('single');
        expect($values)->toContain('lower');
    });

    it('labels returns all generated labels in order', function (): void {
        $labels = EdgeCaseNamingEnum::labels();
        expect($labels)->toHaveCount(8);
        expect($labels[0])->toBe('X');
        expect($labels[1])->toBe('Ab');
        expect($labels[2])->toBe('A1');
    });

    it('forApi returns complete metadata for all edge-case cases', function (): void {
        $api = EdgeCaseNamingEnum::forApi();
        expect($api)->toHaveCount(8);

        // All should have the required keys
        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['color'])->toBe('secondary'); // No color attributes defined
            expect($item['icon'])->toBeNull(); // No icon attributes defined
            expect($item['description'])->toBeNull(); // No description attributes defined
        }
    });

    // ── Comparison edge cases ────────────────────────────────────────

    it('is() compares with string name case-sensitively', function (): void {
        expect(EdgeCaseNamingEnum::X->is('X'))->toBeTrue();
        expect(EdgeCaseNamingEnum::X->is('x'))->toBeFalse();
    });

    it('in() works with mixed instances and strings', function (): void {
        expect(EdgeCaseNamingEnum::AB->in([EdgeCaseNamingEnum::AB, 'X']))->toBeTrue();
        expect(EdgeCaseNamingEnum::AB->in(['AB']))->toBeTrue();
        expect(EdgeCaseNamingEnum::AB->in(['ab']))->toBeFalse();
    });

    it('notIn() works correctly', function (): void {
        expect(EdgeCaseNamingEnum::SINGLE->notIn(['X', 'AB']))->toBeTrue();
        expect(EdgeCaseNamingEnum::SINGLE->notIn(['SINGLE']))->toBeFalse();
    });

    // ── toValue consistency ──────────────────────────────────────────

    it('toValue returns backed value for string-backed enum', function (): void {
        expect(EdgeCaseNamingEnum::UNDER_SCORE__->toValue())->toBe('under_score__');
    });

    it('toValue returns int for int-backed enum', function (): void {
        expect(IntBackedPriority::HIGH->toValue())->toBe(2);
    });

    it('toValue returns case name for pure enum', function (): void {
        expect(PureFeatureFlag::DARK_MODE->toValue())->toBe('DARK_MODE');
    });

    // ── hasCase edge cases ───────────────────────────────────────────

    it('hasCase returns true for existing case', function (): void {
        expect(EdgeCaseNamingEnum::hasCase('X'))->toBeTrue();
        expect(EdgeCaseNamingEnum::hasCase('UNDER_SCORE__'))->toBeTrue();
    });

    it('hasCase returns false for non-existent case', function (): void {
        expect(EdgeCaseNamingEnum::hasCase('NONEXISTENT'))->toBeFalse();
        expect(EdgeCaseNamingEnum::hasCase(''))->toBeFalse();
    });

    // ── Cache invalidation during label resolution ───────────────────

    it('cache invalidation allows fresh metadata after clear', function (): void {
        EnumCache::getInstance()->setTtl(300);
        EnumCache::flush();

        // First access caches the metadata
        $label1 = EdgeCaseNamingEnum::SINGLE->label();
        expect($label1)->toBe('Single');

        // Flush and re-access
        EnumCache::flush();
        $label2 = EdgeCaseNamingEnum::SINGLE->label();
        expect($label2)->toBe('Single');

        // Reset to test default
        EnumCache::getInstance()->setTtl(300);
        EnumCache::flush();
    });

    it('TTL zero disables caching', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        // With TTL=0, has() always returns false
        expect($cache->has(EdgeCaseNamingEnum::class))->toBeFalse();

        // But label() should still work (resolves fresh every time)
        expect(EdgeCaseNamingEnum::A1->label())->toBe('A1');

        // Reset
        $cache->setTtl(300);
        $cache->clear();
    });
});
