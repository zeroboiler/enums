<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\MixedTicketType;
use ZeroBoiler\Enums\Tests\Fixtures\NumericStatusCode;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\SingletonMode;

describe('Enum comprehensive edge case coverage', function (): void {

    // ------------------------------------------------------------------
    // Pure Enum Fixture Tests
    // ------------------------------------------------------------------

    describe('PureSystemState (pure enum without backing)', function (): void {
        it('has the correct number of cases', function (): void {
            expect(PureSystemState::cases())->toHaveCount(4);
        });

        it('forSelect uses case name as value (no backed value)', function (): void {
            $select = PureSystemState::forSelect();
            expect($select[0])->toHaveKey('value');
            expect($select[0]['value'])->toBe('INITIALIZING');
        });

        it('values() returns case names for pure enum', function (): void {
            $values = PureSystemState::values();
            expect($values)->toBe(['INITIALIZING', 'READY', 'RUNNING', 'FAILED']);
        });

        it('labels() returns non-empty strings', function (): void {
            $labels = PureSystemState::labels();
            expect($labels)->toHaveCount(4);
            expect($labels)->each->toBeString()->not->toBeEmpty();
        });

        it('READY has per-case label override', function (): void {
            expect(PureSystemState::READY->label())->toBe('Ready to Serve');
        });

        it('INITIALIZING uses class-level EnumLabel', function (): void {
            // INITIALIZING doesn't have a per-case label or EnumLabel entry
            // so it falls back to auto-generated label
            expect(PureSystemState::INITIALIZING->label())->toBe('Initializing');
        });

        it('FAILED uses class-level EnumColor', function (): void {
            expect(PureSystemState::FAILED->color())->toBe('danger');
        });

        it('RUNNING has default color secondary', function (): void {
            expect(PureSystemState::RUNNING->color())->toBe('secondary');
        });

        it('READY has per-case icon', function (): void {
            expect(PureSystemState::READY->icon())->toBe('heroicon-o-check-circle');
        });

        it('RUNNING has default icon from class-level', function (): void {
            expect(PureSystemState::RUNNING->icon())->toBe('heroicon-o-cog');
        });

        it('READY has per-case description', function (): void {
            expect(PureSystemState::READY->description())->toBe('All services started and accepting traffic');
        });

        it('INITIALIZING description falls back to class-level', function (): void {
            expect(PureSystemState::INITIALIZING->description())->toBe('All systems operational');
        });

        it('RUNNING description is null (no class-level or per-case)', function (): void {
            expect(PureSystemState::RUNNING->description())->toBeNull();
        });

        it('forApi returns correct structure with all metadata', function (): void {
            $api = PureSystemState::forApi();
            expect($api)->toHaveCount(4);
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('INITIALIZING has class-level icon', function (): void {
            expect(PureSystemState::INITIALIZING->icon())->toBe('heroicon-o-arrow-path');
        });

        it('tryFromName works for all cases', function (): void {
            expect(PureSystemState::tryFromName('READY'))->toBeInstanceOf(PureSystemState::class);
            expect(PureSystemState::tryFromName('NONEXISTENT'))->toBeNull();
        });

        it('hasCase returns correct boolean', function (): void {
            expect(PureSystemState::hasCase('FAILED'))->toBeTrue();
            expect(PureSystemState::hasCase('NONEXISTENT'))->toBeFalse();
        });

        it('fromName throws InvalidEnumException for invalid name', function (): void {
            expect(fn (): mixed => PureSystemState::fromName('INVALID'))
                ->toThrow(InvalidEnumException::class);
        });
    });

    // ------------------------------------------------------------------
    // Int-Backed Enum Without Attributes
    // ------------------------------------------------------------------

    describe('IntPriority (int-backed, no attributes)', function (): void {
        it('has 4 cases', function (): void {
            expect(IntPriority::cases())->toHaveCount(4);
        });

        it('values() returns int backed values', function (): void {
            $values = IntPriority::values();
            expect($values)->toBe([1, 5, 10, 99]);
            expect($values)->each->toBeInt();
        });

        it('auto-generates labels from SCREAMING_SNAKE_CASE', function (): void {
            expect(IntPriority::LOW->label())->toBe('Low');
            expect(IntPriority::MEDIUM->label())->toBe('Medium');
            expect(IntPriority::HIGH->label())->toBe('High');
            expect(IntPriority::CRITICAL->label())->toBe('Critical');
        });

        it('all colors default to secondary', function (): void {
            foreach (IntPriority::cases() as $case) {
                expect($case->color())->toBe('secondary');
            }
        });

        it('all icons default to null', function (): void {
            foreach (IntPriority::cases() as $case) {
                expect($case->icon())->toBeNull();
            }
        });

        it('all descriptions default to null', function (): void {
            foreach (IntPriority::cases() as $case) {
                expect($case->description())->toBeNull();
            }
        });

        it('forSelect uses int values', function (): void {
            $select = IntPriority::forSelect();
            expect($select)->toHaveCount(4);
            expect($select[0]['value'])->toBe(1);
            expect($select[3]['value'])->toBe(99);
        });

        it('forApi returns int values in value field', function (): void {
            $api = IntPriority::forApi();
            expect($api[0]['value'])->toBe(1);
            expect($api[1]['value'])->toBe(5);
        });

        it('tryFromLabel finds cases by auto-generated label', function (): void {
            expect(IntPriority::tryFromLabel('Low'))->toBe(IntPriority::LOW);
            expect(IntPriority::tryFromLabel('Critical'))->toBe(IntPriority::CRITICAL);
            expect(IntPriority::tryFromLabel('non-existent'))->toBeNull();
        });

        it('tryFromLabel is case-insensitive', function (): void {
            expect(IntPriority::tryFromLabel('low'))->toBe(IntPriority::LOW);
            expect(IntPriority::tryFromLabel('HIGH'))->toBe(IntPriority::HIGH);
        });
    });

    // ------------------------------------------------------------------
    // Singleton Enum Edge Cases
    // ------------------------------------------------------------------

    describe('SingletonMode (single-case enum)', function (): void {
        it('has exactly one case', function (): void {
            expect(SingletonMode::cases())->toHaveCount(1);
        });

        it('forSelect returns single-element array', function (): void {
            $select = SingletonMode::forSelect();
            expect($select)->toHaveCount(1);
            expect($select[0])->toHaveKeys(['value', 'label']);
        });

        it('forApi returns single-element array with all keys', function (): void {
            $api = SingletonMode::forApi();
            expect($api)->toHaveCount(1);
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('in() works with single-element array', function (): void {
            expect(SingletonMode::INSTANCE->in([SingletonMode::INSTANCE]))->toBeTrue();
            expect(SingletonMode::INSTANCE->in([]))->toBeFalse();
        });

        it('notIn() works with empty array', function (): void {
            expect(SingletonMode::INSTANCE->notIn([]))->toBeTrue();
            expect(SingletonMode::INSTANCE->notIn([SingletonMode::INSTANCE]))->toBeFalse();
        });

        it('values() returns single-element array', function (): void {
            expect(SingletonMode::values())->toBe(['INSTANCE']);
        });

        it('labels() returns single-element array', function (): void {
            expect(SingletonMode::labels())->toBe(['Instance']);
        });

        it('tryFromLabel returns the single case', function (): void {
            expect(SingletonMode::tryFromLabel('Instance'))->toBe(SingletonMode::INSTANCE);
        });

        it('color defaults to secondary', function (): void {
            expect(SingletonMode::INSTANCE->color())->toBe('secondary');
        });

        it('icon defaults to null', function (): void {
            expect(SingletonMode::INSTANCE->icon())->toBeNull();
        });

        it('description defaults to null', function (): void {
            expect(SingletonMode::INSTANCE->description())->toBeNull();
        });
    });

    // ------------------------------------------------------------------
    // Numeric String Backed Enum Edge Cases
    // ------------------------------------------------------------------

    describe('NumericStatusCode (string-backed with numeric values)', function (): void {
        it('handles empty string as backed value', function (): void {
            expect(NumericStatusCode::EMPTY_VALUE->value)->toBe('');
            expect(NumericStatusCode::EMPTY_VALUE->label())->toBe('None');
        });

        it('handles zero string as backed value', function (): void {
            expect(NumericStatusCode::ZERO->value)->toBe('0');
            expect(NumericStatusCode::ZERO->label())->toBe('Zero');
        });

        it('per-case attributes override class-level for TWO', function (): void {
            expect(NumericStatusCode::TWO->label())->toBe('Custom Two Label');
            expect(NumericStatusCode::TWO->description())->toBe('Custom description for two');
            expect(NumericStatusCode::TWO->icon())->toBe('heroicon-o-double');
        });

        it('ZERO has per-case color danger overriding class-level warning', function (): void {
            expect(NumericStatusCode::ZERO->color())->toBe('danger');
        });

        it('ONE uses class-level color success', function (): void {
            expect(NumericStatusCode::ONE->color())->toBe('success');
        });

        it('all cases have the default icon', function (): void {
            // TWO has a per-case icon override, others get default
            expect(NumericStatusCode::EMPTY_VALUE->icon())->toBe('heroicon-o-number');
            expect(NumericStatusCode::ZERO->icon())->toBe('heroicon-o-number');
            expect(NumericStatusCode::ONE->icon())->toBe('heroicon-o-number');
            expect(NumericStatusCode::TWO->icon())->toBe('heroicon-o-double');
        });

        it('forSelect returns string values (not int)', function (): void {
            $select = NumericStatusCode::forSelect();
            expect($select)->toHaveCount(4);
            expect($select[0]['value'])->toBe('');
            expect($select[1]['value'])->toBe('0');
            expect($select[2]['value'])->toBe('1');
            expect($select[3]['value'])->toBe('2');
        });

        it('values() returns string values', function (): void {
            $values = NumericStatusCode::values();
            expect($values)->each->toBeString();
        });
    });

    // ------------------------------------------------------------------
    // Mixed Ticket Type (Int-Backed with Mixed Attributes)
    // ------------------------------------------------------------------

    describe('MixedTicketType (int-backed with class+case attributes)', function (): void {
        it('CRITICAL_BUG has all per-case overrides', function (): void {
            expect(MixedTicketType::CRITICAL_BUG->label())->toBe('Critical Bug');
            expect(MixedTicketType::CRITICAL_BUG->description())->toBe('System-breaking bug — immediate fix required');
            expect(MixedTicketType::CRITICAL_BUG->icon())->toBe('heroicon-o-fire');
            expect(MixedTicketType::CRITICAL_BUG->color())->toBe('danger');
        });

        it('FEATURE uses class-level label but per-case color', function (): void {
            expect(MixedTicketType::FEATURE->label())->toBe('Feature Request');
            expect(MixedTicketType::FEATURE->color())->toBe('success');
        });

        it('SUPPORT uses only class-level attributes', function (): void {
            expect(MixedTicketType::SUPPORT->label())->toBe('Support Ticket');
            expect(MixedTicketType::SUPPORT->description())->toBe('Get help');
            expect(MixedTicketType::SUPPORT->icon())->toBe('heroicon-o-question-mark-circle');
            expect(MixedTicketType::SUPPORT->color())->toBe('secondary');
        });

        it('DOCS has per-case description but class-level label', function (): void {
            expect(MixedTicketType::DOCS->label())->toBe('Documentation Issue');
            expect(MixedTicketType::DOCS->description())->toBe('Needs documentation update');
        });

        it('values() returns int values', function (): void {
            expect(MixedTicketType::values())->toBe([1, 2, 3, 4]);
            expect(MixedTicketType::values())->each->toBeInt();
        });

        it('forSelect uses int values', function (): void {
            $select = MixedTicketType::forSelect();
            expect($select[0]['value'])->toBe(1);
            expect($select[3]['value'])->toBe(4);
        });

        it('tryFromLabel finds by label string', function (): void {
            expect(MixedTicketType::tryFromLabel('Critical Bug'))->toBe(MixedTicketType::CRITICAL_BUG);
            expect(MixedTicketType::tryFromLabel('Support Ticket'))->toBe(MixedTicketType::SUPPORT);
        });

        it('forApi returns int values', function (): void {
            $api = MixedTicketType::forApi();
            expect($api)->toHaveCount(4);
            expect($api[0]['value'])->toBe(1);
        });
    });

    // ------------------------------------------------------------------
    // CamelCase Priority
    // ------------------------------------------------------------------

    describe('CamelCasePriority (camelCase naming)', function (): void {
        it('auto-generates label for archived (lowercase case name)', function (): void {
            expect(CamelCasePriority::archived->label())->toBe('Archived');
        });

        it('active uses class-level EnumLabel', function (): void {
            expect(CamelCasePriority::active->label())->toBe('Online');
        });

        it('pendingReview uses per-case Label', function (): void {
            expect(CamelCasePriority::pendingReview->label())->toBe('Awaiting Approval');
        });

        it('softDeleted uses class-level EnumDescription for archived, per-case for softDeleted', function (): void {
            expect(CamelCasePriority::softDeleted->description())->toBe('Soft-deleted account');
            expect(CamelCasePriority::archived->description())->toBe('Account archived');
        });

        it('values() returns string backed values', function (): void {
            expect(CamelCasePriority::values())->toBe(['active', 'pendingReview', 'archived', 'softDeleted']);
            expect(CamelCasePriority::values())->each->toBeString();
        });
    });

    // ------------------------------------------------------------------
    // Empty Defaults Status
    // ------------------------------------------------------------------

    describe('EmptyDefaultsStatus (no attributes at all)', function (): void {
        it('auto-generates all labels from SCREAMING_SNAKE_CASE', function (): void {
            expect(EmptyDefaultsStatus::DRAFT->label())->toBe('Draft');
            expect(EmptyDefaultsStatus::PUBLISHED->label())->toBe('Published');
            expect(EmptyDefaultsStatus::ARCHIVED->label())->toBe('Archived');
        });

        it('all colors default to secondary', function (): void {
            foreach (EmptyDefaultsStatus::cases() as $case) {
                expect($case->color())->toBe('secondary');
            }
        });

        it('all descriptions default to null', function (): void {
            foreach (EmptyDefaultsStatus::cases() as $case) {
                expect($case->description())->toBeNull();
            }
        });

        it('all icons default to null', function (): void {
            foreach (EmptyDefaultsStatus::cases() as $case) {
                expect($case->icon())->toBeNull();
            }
        });

        it('labels count matches cases count', function (): void {
            expect(EmptyDefaultsStatus::labels())->toHaveCount(3);
        });
    });

    // ------------------------------------------------------------------
    // Cross-Fixture: Comparison Methods
    // ------------------------------------------------------------------

    describe('comparison methods across different enum types', function (): void {
        it('is() works with pure enum instances', function (): void {
            expect(PureSystemState::READY->is(PureSystemState::READY))->toBeTrue();
            expect(PureSystemState::READY->is(PureSystemState::FAILED))->toBeFalse();
        });

        it('is() works with int-backed enum string names', function (): void {
            expect(IntPriority::LOW->is('LOW'))->toBeTrue();
            expect(IntPriority::LOW->is('HIGH'))->toBeFalse();
        });

        it('is() is case-sensitive for string names', function (): void {
            expect(IntPriority::LOW->is('low'))->toBeFalse();
            expect(IntPriority::LOW->is('LOW'))->toBeTrue();
        });

        it('in() works with mixed instance and string arguments', function (): void {
            expect(IntPriority::MEDIUM->in([IntPriority::LOW, 'MEDIUM']))->toBeTrue();
            expect(IntPriority::MEDIUM->in(['LOW', IntPriority::HIGH]))->toBeFalse();
        });

        it('notIn() correctly negates in()', function (): void {
            expect(PureSystemState::READY->notIn(['FAILED']))->toBeTrue();
            expect(PureSystemState::READY->notIn([PureSystemState::READY]))->toBeFalse();
        });

        it('isNot() correctly negates is()', function (): void {
            expect(CamelCasePriority::active->isNot(CamelCasePriority::archived))->toBeTrue();
            expect(CamelCasePriority::active->isNot('active'))->toBeFalse();
        });
    });
});
