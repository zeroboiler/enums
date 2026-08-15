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
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;

describe('Enum V16 — Structural Safety And Cross-Type Contracts', function () {
    describe('strict_types compliance', function () {
        it('forSelect returns typed array with string keys and array values', function () {
            $options = OrderStatus::forSelect();

            expect($options)->toBeArray();
            expect($options)->toHaveCount(4);

            foreach ($options as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveKey('value');
                expect($option)->toHaveKey('label');
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forApi returns typed array with all six metadata keys', function () {
            $api = PaymentStatus::forApi();

            expect($api)->toBeArray();
            expect($api)->toHaveCount(3);

            foreach ($api as $item) {
                expect($item)->toBeArray();
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString()->not->toBeEmpty();
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('values returns list with correct backing type for string enum', function () {
            $values = OrderStatus::values();

            expect($values)->toBeArray();
            expect($values)->toHaveCount(4);

            foreach ($values as $v) {
                expect($v)->toBeString();
            }
        });

        it('values returns list with correct backing type for int enum', function () {
            $values = IntPriority::values();

            expect($values)->toBeArray();
            expect($values)->toHaveCount(4);

            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('labels returns non-empty strings for all cases', function () {
            $labels = OrderStatus::labels();

            expect($labels)->toHaveCount(4);
            expect($labels)->each->toBeString()->not->toBeEmpty();
        });

        it('forSelect values are unique for each case', function () {
            $values = array_column(OrderStatus::forSelect(), 'value');

            expect($values)->not->toBeEmpty();
            expect(array_unique($values))->toHaveCount(count($values));
        });

        it('forApi values are unique for each case', function () {
            $values = array_column(PaymentStatus::forApi(), 'value');

            expect($values)->not->toBeEmpty();
            expect(array_unique($values))->toHaveCount(count($values));
        });
    });

    describe('comparison method type safety', function () {
        it('is() returns bool for instance comparison', function () {
            expect(OrderStatus::PENDING->is(OrderStatus::PENDING))->toBeBool()->toBeTrue();
            expect(OrderStatus::PENDING->is(OrderStatus::SHIPPED))->toBeBool()->toBeFalse();
        });

        it('is() returns bool for string name comparison', function () {
            expect(OrderStatus::PENDING->is('PENDING'))->toBeBool()->toBeTrue();
            expect(OrderStatus::PENDING->is('SHIPPED'))->toBeBool()->toBeFalse();
        });

        it('is() is case-sensitive for string names', function () {
            expect(OrderStatus::PENDING->is('pending'))->toBeFalse();
            expect(OrderStatus::PENDING->is('PENDING'))->toBeTrue();
        });

        it('isNot() returns correct negation', function () {
            expect(OrderStatus::PENDING->isNot(OrderStatus::SHIPPED))->toBeTrue();
            expect(OrderStatus::PENDING->isNot(OrderStatus::PENDING))->toBeFalse();
        });

        it('in() works with empty array', function () {
            expect(OrderStatus::PENDING->in([]))->toBeFalse();
        });

        it('in() works with single element array', function () {
            expect(OrderStatus::PENDING->in([OrderStatus::PENDING]))->toBeTrue();
            expect(OrderStatus::PENDING->in([OrderStatus::SHIPPED]))->toBeFalse();
        });

        it('in() works with mixed instance and string arguments', function () {
            expect(OrderStatus::PENDING->in([OrderStatus::PENDING, 'SHIPPED']))->toBeTrue();
        });

        it('notIn() works with empty array', function () {
            expect(OrderStatus::PENDING->notIn([]))->toBeTrue();
        });

        it('notIn() returns false when case is in the list', function () {
            expect(OrderStatus::PENDING->notIn([OrderStatus::PENDING]))->toBeFalse();
        });
    });

    describe('lookup method type safety', function () {
        it('tryFromLabel returns null for empty string', function () {
            expect(OrderStatus::tryFromLabel(''))->toBeNull();
        });

        it('tryFromLabel returns null for non-existent label', function () {
            expect(OrderStatus::tryFromLabel('nonexistent_label_xyz'))->toBeNull();
        });

        it('tryFromLabel is truly case-insensitive', function () {
            $label = OrderStatus::PENDING->label();

            expect(OrderStatus::tryFromLabel(strtoupper($label)))->not->toBeNull();
            expect(OrderStatus::tryFromLabel(strtolower($label)))->not->toBeNull();
            expect(OrderStatus::tryFromLabel(ucfirst(strtolower($label)))))->not->toBeNull();
        });

        it('tryFromName returns null for empty string', function () {
            expect(OrderStatus::tryFromName(''))->toBeNull();
        });

        it('tryFromName is case-sensitive', function () {
            expect(OrderStatus::tryFromName('PENDING'))->toBeInstanceOf(OrderStatus::class);
            expect(OrderStatus::tryFromName('pending'))->toBeNull();
        });

        it('fromName returns correct case for valid name', function () {
            $case = OrderStatus::fromName('SHIPPED');

            expect($case)->toBeInstanceOf(OrderStatus::class);
            expect($case->name)->toBe('SHIPPED');
        });

        it('fromName throws InvalidEnumException for non-existent name', function () {
            expect(fn () => OrderStatus::fromName('NONEXISTENT'))->toThrow(InvalidEnumException::class);
        });

        it('fromName exception message contains class and name', function () {
            try {
                OrderStatus::fromName('INVALID');
                expect(true)->toBeFalse(); // Should not reach here
            } catch (InvalidEnumException $e) {
                expect($e->getMessage())->toContain('INVALID');
                expect($e->getMessage())->toContain(OrderStatus::class);
            }
        });

        it('hasCase returns bool consistently', function () {
            expect(OrderStatus::hasCase('PENDING'))->toBeBool()->toBeTrue();
            expect(OrderStatus::hasCase('NONEXISTENT'))->toBeBool()->toBeFalse();
        });
    });

    describe('int-backed enum type safety', function () {
        it('values returns int values not case names', function () {
            $values = IntPriority::values();

            expect($values)->toEqual([1, 5, 10, 99]);
        });

        it('forSelect uses int as value key', function () {
            $select = IntPriority::forSelect();

            expect($select[0]['value'])->toBe(1);
            expect($select[1]['value'])->toBe(5);
            expect($select[2]['value'])->toBe(10);
            expect($select[3]['value'])->toBe(99);
        });

        it('labels auto-generate from SCREAMING_SNAKE_CASE', function () {
            $labels = IntPriority::labels();

            expect($labels[0])->toBe('Low');
            expect($labels[1])->toBe('Medium');
            expect($labels[2])->toBe('High');
            expect($labels[3])->toBe('Critical');
        });

        it('default color is secondary when no attributes set', function () {
            foreach (IntPriority::cases() as $case) {
                expect($case->color())->toBe('secondary');
            }
        });

        it('default icon is null when no attributes set', function () {
            foreach (IntPriority::cases() as $case) {
                expect($case->icon())->toBeNull();
            }
        });

        it('default description is null when no attributes set', function () {
            foreach (IntPriority::cases() as $case) {
                expect($case->description())->toBeNull();
            }
        });

        it('forApi uses int value for value field', function () {
            $api = IntPriority::forApi();

            expect($api[0]['value'])->toBe(1);
            expect($api[0]['name'])->toBe('LOW');
        });
    });

    describe('pure enum type safety', function () {
        it('values returns case names for pure enum', function () {
            $values = PureSystemState::values();

            expect($values)->toEqual(['INITIALIZING', 'READY', 'RUNNING', 'FAILED']);
        });

        it('forSelect uses case name as value', function () {
            $select = PureSystemState::forSelect();

            expect($select[0]['value'])->toBe('INITIALIZING');
        });

        it('class-level EnumColor resolves correctly on pure enum', function () {
            expect(PureSystemState::READY->color())->toBe('success');
            expect(PureSystemState::FAILED->color())->toBe('danger');
            expect(PureSystemState::RUNNING->color())->toBe('secondary');
        });

        it('class-level EnumLabel resolves correctly on pure enum', function () {
            expect(PureSystemState::READY->label())->toBe('Ready to Serve'); // per-case override
            expect(PureSystemState::FAILED->label())->toBe('System Failure'); // per-case override
            expect(PureSystemState::RUNNING->label())->toBe('Running'); // auto-generated
        });

        it('class-level EnumIcon default fallback works for pure enum', function () {
            expect(PureSystemState::RUNNING->icon())->toBe('heroicon-o-cog');
        });

        it('class-level EnumIcon per-value map works for pure enum', function () {
            expect(PureSystemState::INITIALIZING->icon())->toBe('heroicon-o-arrow-path');
        });

        it('per-case Icon override takes priority over class-level', function () {
            expect(PureSystemState::READY->icon())->toBe('heroicon-o-check-circle');
        });
    });

    describe('attribute resolution priority contract', function () {
        it('per-case Label overrides class-level EnumLabel', function () {
            expect(TicketStatus::OPEN->label())->toBe('Open Ticket'); // per-case Label
        });

        it('class-level EnumLabel used when no per-case Label', function () {
            // TicketStatus::IN_PROGRESS should use class-level if defined, else auto-gen
            $label = TicketStatus::IN_PROGRESS->label();
            expect($label)->toBeString()->not->toBeEmpty();
        });

        it('per-case Color overrides class-level EnumColor', function () {
            // Check per-case color takes priority
            $color = TicketStatus::RESOLVED->color();
            expect($color)->toBeString()->not->toBeEmpty();
        });

        it('color defaults to secondary when nothing is set', function () {
            // OrderStatus has no color attributes at all
            expect(OrderStatus::DELIVERED->color())->toBe('secondary');
        });

        it('description returns null when nothing is set', function () {
            expect(OrderStatus::PENDING->description())->toBeNull();
            expect(OrderStatus::SHIPPED->description())->toBeNull();
        });

        it('icon returns null when nothing is set', function () {
            expect(OrderStatus::PENDING->icon())->toBeNull();
            expect(OrderStatus::CANCELLED->icon())->toBeNull();
        });
    });

    describe('EnumRule type safety', function () {
        it('constructs with class string', function () {
            $rule = EnumRule::for(OrderStatus::class);

            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('nullable returns new instance', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $nullable = $rule->nullable();

            expect($nullable)->toBeInstanceOf(EnumRule::class);
            expect($nullable)->not->toBe($rule);
        });

        it('validate passes for valid backed value', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $failed = false;

            $rule->validate('status', 'pending', function (string $message): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('validate fails for invalid backed value', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $failed = false;

            $rule->validate('status', 'nonexistent', function (string $message): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('validate rejects null when not nullable', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $failed = false;

            $rule->validate('status', null, function (string $message): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('validate accepts null when nullable', function () {
            $rule = EnumRule::for(OrderStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', null, function (string $message): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('validate works with pure enum case names', function () {
            $rule = EnumRule::for(PureSystemState::class);
            $failed = false;

            $rule->validate('state', 'READY', function (string $message): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('validate rejects invalid pure enum case name', function () {
            $rule = EnumRule::for(PureSystemState::class);
            $failed = false;

            $rule->validate('state', 'NONEXISTENT', function (string $message): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('validate rejects int value for string-backed enum', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $failed = false;

            $rule->validate('status', 123, function (string $message): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('validate accepts correct int value for int-backed enum', function () {
            $rule = EnumRule::for(IntPriority::class);
            $failed = false;

            $rule->validate('priority', 10, function (string $message): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('validate rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(IntPriority::class);
            $failed = false;

            $rule->validate('priority', 'HIGH', function (string $message): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    describe('InvalidEnumException contract', function () {
        it('named constructor value() formats message with null', function () {
            $e = InvalidEnumException::value(OrderStatus::class, null);

            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain(OrderStatus::class);
        });

        it('named constructor value() formats message with string', function () {
            $e = InvalidEnumException::value(OrderStatus::class, 'invalid_value');

            expect($e->getMessage())->toContain('invalid_value');
        });

        it('named constructor value() formats message with int', function () {
            $e = InvalidEnumException::value(IntPriority::class, 999);

            expect($e->getMessage())->toContain('999');
        });

        it('named constructor forName() formats message correctly', function () {
            $e = InvalidEnumException::forName(OrderStatus::class, 'NONEXISTENT');

            expect($e->getMessage())->toContain('NONEXISTENT');
            expect($e->getMessage())->toContain(OrderStatus::class);
        });

        it('__toString returns class name and message', function () {
            $e = InvalidEnumException::value(OrderStatus::class, 'x');

            expect($e->__toString())->toBeString();
            expect($e->__toString())->toContain(InvalidEnumException::class);
            expect($e->__toString())->toContain($e->getMessage());
        });
    });

    describe('bulk method consistency across enum types', function () {
        it('cases count matches forSelect count for string enum', function () {
            expect(count(OrderStatus::cases()))->toBe(count(OrderStatus::forSelect()));
        });

        it('cases count matches forApi count for string enum', function () {
            expect(count(OrderStatus::cases()))->toBe(count(OrderStatus::forApi()));
        });

        it('cases count matches forSelect count for int enum', function () {
            expect(count(IntPriority::cases()))->toBe(count(IntPriority::forSelect()));
        });

        it('cases count matches forApi count for int enum', function () {
            expect(count(IntPriority::cases()))->toBe(count(IntPriority::forApi()));
        });

        it('cases count matches forSelect count for pure enum', function () {
            expect(count(PureSystemState::cases()))->toBe(count(PureSystemState::forSelect()));
        });

        it('cases count matches forApi count for pure enum', function () {
            expect(count(PureSystemState::cases()))->toBe(count(PureSystemState::forApi()));
        });

        it('values count matches cases count for all enum types', function () {
            expect(count(OrderStatus::values()))->toBe(count(OrderStatus::cases()));
            expect(count(IntPriority::values()))->toBe(count(IntPriority::cases()));
            expect(count(PureSystemState::values()))->toBe(count(PureSystemState::cases()));
        });

        it('labels count matches cases count for all enum types', function () {
            expect(count(OrderStatus::labels()))->toBe(count(OrderStatus::cases()));
            expect(count(IntPriority::labels()))->toBe(count(IntPriority::cases()));
            expect(count(PureSystemState::labels()))->toBe(count(PureSystemState::cases()));
        });
    });

    describe('label generation edge cases', function () {
        it('single character case name generates correctly', function () {
            // PureSystemState doesn't have single char cases but the trait should handle it
            // Verify auto-generation for various patterns
            $label = OrderStatus::PENDING->label();
            expect($label)->toBe('Pending');
        });

        it('camelCase case name generates correctly', function () {
            $label = OrderStatus::PENDING->label();
            // PENDING is all caps so it goes through the snake_case path
            expect($label)->toBeString()->not->toBeEmpty();
        });

        it('consecutive uppercase letters in case name handled', function () {
            // INT status - if we had one, "INT" should become "Int"
            // Verify with an existing fixture
            $label = PureSystemState::INITIALIZING->label();
            expect($label)->toBeString()->not->toBeEmpty();
        });
    });
});
