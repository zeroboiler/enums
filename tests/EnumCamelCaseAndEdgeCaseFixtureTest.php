<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;
use ZeroBoiler\Enums\Tests\Fixtures\MixedTicketType;
use ZeroBoiler\Enums\Tests\Fixtures\NumericStatusCode;

describe('CamelCase enum auto-label and attribute resolution', function () {
    it('generates title-case labels from camelCase names', function () {
        expect(CamelCasePriority::softDeleted->label())->toBe('Soft Deleted');
    });

    it('uses class-level label when set for camelCase backed value', function () {
        expect(CamelCasePriority::active->label())->toBe('Online');
    });

    it('prefers per-case Label over class-level EnumLabel', function () {
        expect(CamelCasePriority::pendingReview->label())->toBe('Awaiting Approval');
    });

    it('auto-generates label for cases without any label attribute', function () {
        expect(CamelCasePriority::archived->label())->toBe('Archived');
    });

    it('resolves per-case color override for camelCase case', function () {
        expect(CamelCasePriority::pendingReview->color())->toBe('warning');
    });

    it('resolves class-level color for camelCase backed value', function () {
        expect(CamelCasePriority::active->color())->toBe('success');
    });

    it('returns secondary default color when no color attribute is set', function () {
        expect(CamelCasePriority::archived->color())->toBe('secondary');
    });

    it('resolves class-level description for camelCase backed value', function () {
        expect(CamelCasePriority::active->description())->toBe('User is online');
    });

    it('resolves per-case description override', function () {
        expect(CamelCasePriority::softDeleted->description())->toBe('Soft-deleted account');
    });

    it('resolves class-level default icon', function () {
        expect(CamelCasePriority::archived->icon())->toBe('heroicon-o-circle');
    });

    it('resolves per-value icon from class-level EnumIcon map', function () {
        expect(CamelCasePriority::active->icon())->toBe('heroicon-o-check');
    });

    it('returns null description when no description attribute is set', function () {
        expect(CamelCasePriority::pendingReview->description())->toBeNull();
    });

    it('has correct case count', function () {
        expect(CamelCasePriority::cases())->toHaveCount(4);
    });

    it('forSelect returns value-label pairs with correct keys', function () {
        $select = CamelCasePriority::forSelect();

        expect($select)->toHaveCount(4);
        expect($select[0])->toHaveKeys(['value', 'label']);
    });

    it('forApi returns full metadata for all camelCase cases', function () {
        $api = CamelCasePriority::forApi();

        expect($api)->toHaveCount(4);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('values() returns backed values for all camelCase cases', function () {
        $values = CamelCasePriority::values();

        expect($values)->toContain('active');
        expect($values)->toContain('pendingReview');
        expect($values)->toContain('archived');
        expect($values)->toContain('softDeleted');
    });

    it('comparison works with camelCase case instances', function () {
        expect(CamelCasePriority::active->is(CamelCasePriority::active))->toBeTrue();
        expect(CamelCasePriority::active->isNot(CamelCasePriority::archived))->toBeTrue();
        expect(CamelCasePriority::active->in(['active', 'pendingReview']))->toBeTrue();
        expect(CamelCasePriority::active->notIn(['archived', 'softDeleted']))->toBeTrue();
    });

    it('tryFromName resolves camelCase case names', function () {
        expect(CamelCasePriority::tryFromName('pendingReview'))->toBeInstanceOf(CamelCasePriority::class);
        expect(CamelCasePriority::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('fromName throws InvalidEnumException for invalid camelCase name', function () {
        expect(fn () => CamelCasePriority::fromName('nonExistent'))->toThrow(InvalidEnumException::class);
    });

    it('hasCase works with camelCase case names', function () {
        expect(CamelCasePriority::hasCase('softDeleted'))->toBeTrue();
        expect(CamelCasePriority::hasCase('SoftDeleted'))->toBeFalse(); // case-sensitive
    });

    it('tryFromLabel resolves auto-generated camelCase labels', function () {
        expect(CamelCasePriority::tryFromLabel('Soft Deleted'))->toBe(CamelCasePriority::softDeleted());
    });

    it('tryFromLabel is case-insensitive', function () {
        expect(CamelCasePriority::tryFromLabel('soft deleted'))->toBe(CamelCasePriority::softDeleted());
    });
});

describe('Empty defaults enum — pure auto-generation path', function () {
    it('auto-generates labels from SCREAMING_SNAKE_CASE', function () {
        expect(EmptyDefaultsStatus::DRAFT->label())->toBe('Draft');
        expect(EmptyDefaultsStatus::PUBLISHED->label())->toBe('Published');
        expect(EmptyDefaultsStatus::ARCHIVED->label())->toBe('Archived');
    });

    it('defaults all colors to secondary', function () {
        foreach (EmptyDefaultsStatus::cases() as $case) {
            expect($case->color())->toBe('secondary');
        }
    });

    it('defaults all descriptions to null', function () {
        foreach (EmptyDefaultsStatus::cases() as $case) {
            expect($case->description())->toBeNull();
        }
    });

    it('defaults all icons to null', function () {
        foreach (EmptyDefaultsStatus::cases() as $case) {
            expect($case->icon())->toBeNull();
        }
    });

    it('forApi returns consistent structure', function () {
        $api = EmptyDefaultsStatus::forApi();

        expect($api)->toHaveCount(3);
        foreach ($api as $item) {
            expect($item['color'])->toBe('secondary');
            expect($item['icon'])->toBeNull();
            expect($item['label'])->toBeString()->not->toBeEmpty();
        }
    });
});

describe('Numeric string-backed enum — edge cases', function () {
    it('handles empty string as backed value', function () {
        expect(NumericStatusCode::EMPTY_VALUE->value)->toBe('');
        expect(NumericStatusCode::EMPTY_VALUE->label())->toBe('None');
    });

    it('handles numeric string zero as backed value', function () {
        expect(NumericStatusCode::ZERO->value)->toBe('0');
        expect(NumericStatusCode::ZERO->label())->toBe('Zero');
    });

    it('resolves per-case color for numeric string zero', function () {
        expect(NumericStatusCode::ZERO->color())->toBe('danger');
    });

    it('resolves class-level color for numeric string one', function () {
        expect(NumericStatusCode::ONE->color())->toBe('success');
    });

    it('resolves class-level icon default for numeric string cases', function () {
        expect(NumericStatusCode::EMPTY_VALUE->icon())->toBe('heroicon-o-number');
        expect(NumericStatusCode::ZERO->icon())->toBe('heroicon-o-number');
    });

    it('resolves per-case icon override', function () {
        expect(NumericStatusCode::TWO->icon())->toBe('heroicon-o-double');
    });

    it('resolves class-level description for numeric string zero', function () {
        expect(NumericStatusCode::ZERO->description())->toBe('Numeric zero value');
    });

    it('resolves per-case description override for custom label', function () {
        expect(NumericStatusCode::TWO->description())->toBe('Custom description for two');
    });

    it('tryFromLabel works with class-level label for empty string value', function () {
        expect(NumericStatusCode::tryFromLabel('None'))->toBe(NumericStatusCode::EMPTY_VALUE);
    });

    it('values() includes empty string', function () {
        $values = NumericStatusCode::values();
        expect($values)->toContain('');
        expect($values)->toContain('0');
        expect($values)->toContain('1');
        expect($values)->toContain('2');
    });
});

describe('Int-backed enum with mixed attributes', function () {
    it('per-case attributes override all class-level defaults', function () {
        expect(MixedTicketType::CRITICAL_BUG->label())->toBe('Critical Bug');
        expect(MixedTicketType::CRITICAL_BUG->description())->toBe('System-breaking bug — immediate fix required');
        expect(MixedTicketType::CRITICAL_BUG->icon())->toBe('heroicon-o-fire');
        expect(MixedTicketType::CRITICAL_BUG->color())->toBe('danger');
    });

    it('partial per-case override keeps class-level for unset metadata', function () {
        // FEATURE has only #[Color('success')], other metadata from class-level
        expect(MixedTicketType::FEATURE->label())->toBe('Feature Request'); // class-level EnumLabel
        expect(MixedTicketType::FEATURE->color())->toBe('success');        // per-case override
        expect(MixedTicketType::FEATURE->icon())->toBe('heroicon-o-sparkles'); // class-level EnumIcon map
        expect(MixedTicketType::FEATURE->description())->toBeNull();         // not set anywhere
    });

    it('class-level defaults used when no per-case override', function () {
        // SUPPORT: no per-case attributes
        expect(MixedTicketType::SUPPORT->label())->toBe('Support Ticket'); // class-level EnumLabel
        expect(MixedTicketType::SUPPORT->description())->toBe('Get help');  // class-level EnumDescription
        expect(MixedTicketType::SUPPORT->icon())->toBe('heroicon-o-question-mark-circle'); // class-level default
        expect(MixedTicketType::SUPPORT->color())->toBe('secondary');        // no color set anywhere
    });

    it('values() returns int backed values', function () {
        $values = MixedTicketType::values();

        expect($values)->toEqual([1, 2, 3, 4]);
    });

    it('forSelect uses int backed values', function () {
        $select = MixedTicketType::forSelect();

        expect($select[0]['value'])->toBe(1);
    });
});
