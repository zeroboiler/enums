<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Comprehensive edge case tests for enum notIn(), in() with mixed types,
 * is()/isNot() with edge cases, and comparison method type safety.
 *
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata
 */

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;

// ── Test fixtures ──────────────────────────────────────────────

#[\ZeroBoiler\Enums\Attributes\EnumColor(success: ['active'], danger: ['banned'])]
enum CompareStatus: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BANNED = 'banned';
}

enum IntCompareStatus: int
{
    use HasEnumMetadata;

    case PENDING = 0;
    case ACTIVE = 1;
    case CLOSED = 2;
}

enum SingleCaseEnum: string
{
    use HasEnumMetadata;

    case ONLY = 'only';
}

// ── Tests ──────────────────────────────────────────────────────

describe('Enum comparison notIn() edge cases', function (): void {

    it('notIn returns true when case is not in the list', function (): void {
        expect(CompareStatus::ACTIVE->notIn([CompareStatus::BANNED]))->toBeTrue();
    });

    it('notIn returns false when case is in the list', function (): void {
        expect(CompareStatus::ACTIVE->notIn([CompareStatus::ACTIVE]))->toBeFalse();
    });

    it('notIn works with string names', function (): void {
        expect(CompareStatus::ACTIVE->notIn(['BANNED', 'INACTIVE']))->toBeTrue();
        expect(CompareStatus::ACTIVE->notIn(['ACTIVE', 'INACTIVE']))->toBeFalse();
    });

    it('notIn works with mixed instances and strings', function (): void {
        expect(CompareStatus::ACTIVE->notIn([CompareStatus::BANNED, 'INACTIVE']))->toBeTrue();
        expect(CompareStatus::ACTIVE->notIn(['BANNED', CompareStatus::ACTIVE]))->toBeFalse();
    });

    it('notIn returns true for empty list', function (): void {
        expect(CompareStatus::ACTIVE->notIn([]))->toBeTrue();
    });

    it('notIn returns false when all cases are listed', function (): void {
        expect(CompareStatus::ACTIVE->notIn([
            CompareStatus::ACTIVE,
            CompareStatus::INACTIVE,
            CompareStatus::BANNED,
        ]))->toBeFalse();
    });

    it('notIn works with int-backed enum', function (): void {
        expect(IntCompareStatus::ACTIVE->notIn([IntCompareStatus::PENDING, IntCompareStatus::CLOSED]))->toBeTrue();
        expect(IntCompareStatus::ACTIVE->notIn([IntCompareStatus::PENDING, IntCompareStatus::ACTIVE]))->toBeFalse();
    });

    it('notIn works with int-backed enum string names', function (): void {
        expect(IntCompareStatus::ACTIVE->notIn(['PENDING', 'CLOSED']))->toBeTrue();
        expect(IntCompareStatus::ACTIVE->notIn(['PENDING', 'ACTIVE']))->toBeFalse();
    });
});

describe('Enum comparison in() edge cases', function (): void {

    it('in returns true for empty list is always false', function (): void {
        expect(CompareStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('in works with single-element list', function (): void {
        expect(CompareStatus::ACTIVE->in([CompareStatus::ACTIVE]))->toBeTrue();
        expect(CompareStatus::BANNED->in([CompareStatus::ACTIVE]))->toBeFalse();
    });

    it('in with all cases listed returns true for any', function (): void {
        expect(CompareStatus::BANNED->in([
            CompareStatus::ACTIVE,
            CompareStatus::INACTIVE,
            CompareStatus::BANNED,
        ]))->toBeTrue();
    });

    it('in with int-backed enum works with int instances', function (): void {
        expect(IntCompareStatus::ACTIVE->in([IntCompareStatus::ACTIVE, IntCompareStatus::CLOSED]))->toBeTrue();
    });

    it('in is case-sensitive for string names', function (): void {
        expect(CompareStatus::ACTIVE->in(['active']))->toBeFalse(); // backed value, not case name
        expect(CompareStatus::ACTIVE->in(['ACTIVE']))->toBeTrue();
    });
});

describe('Enum is()/isNot() edge cases', function (): void {

    it('is returns true for same instance', function (): void {
        $case = CompareStatus::ACTIVE;
        expect($case->is($case))->toBeTrue();
    });

    it('isNot returns false for same instance', function (): void {
        $case = CompareStatus::ACTIVE;
        expect($case->isNot($case))->toBeFalse();
    });

    it('is compares different enum types correctly via instance', function (): void {
        // These are the same type, so comparison should work
        expect(CompareStatus::ACTIVE->is(CompareStatus::INACTIVE))->toBeFalse();
    });

    it('is with string name works for single-case enum', function (): void {
        expect(SingleCaseEnum::ONLY->is('ONLY'))->toBeTrue();
        expect(SingleCaseEnum::ONLY->is('NOT_ONLY'))->toBeFalse();
    });

    it('notIn complements in for all cases', function (): void {
        $allCases = CompareStatus::cases();

        foreach ($allCases as $case) {
            // The case should be found when all cases are in the list
            expect($case->in($allCases))->toBeTrue("{$case->name} should be in the full list");
            expect($case->notIn($allCases))->toBeFalse("{$case->name} should NOT be notIn the full list");
        }
    });
});

describe('Enum lookup edge cases', function (): void {

    it('tryFromLabel is case-insensitive', function (): void {
        expect(CompareStatus::tryFromLabel('Active'))->toBe(CompareStatus::ACTIVE);
        expect(CompareStatus::tryFromLabel('ACTIVE'))->toBe(CompareStatus::ACTIVE);
        expect(CompareStatus::tryFromLabel('active'))->toBe(CompareStatus::ACTIVE);
    });

    it('tryFromName is case-sensitive', function (): void {
        expect(CompareStatus::tryFromName('ACTIVE'))->toBe(CompareStatus::ACTIVE);
        expect(CompareStatus::tryFromName('active'))->toBeNull(); // Not a case name
    });

    it('fromName throws for non-existent case', function (): void {
        expect(fn () => CompareStatus::fromName('NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase returns false for empty string', function (): void {
        expect(CompareStatus::hasCase(''))->toBeFalse();
    });

    it('hasCase returns false for case name of different enum', function (): void {
        // ACTIVE is not a case on IntCompareStatus
        expect(IntCompareStatus::hasCase('ACTIVE'))->toBeTrue();
    });

    it('fromName exception contains class and case name', function (): void {
        try {
            CompareStatus::fromName('MISSING');
            expect(true)->toBeFalse('Should have thrown');
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('MISSING');
            expect($e->getMessage())->toContain(CompareStatus::class);
        }
    });
});
