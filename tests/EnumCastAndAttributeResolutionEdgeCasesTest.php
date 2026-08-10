<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Comprehensive EnumCast and attribute resolution edge case tests.
 *
 * Covers:
 * - EnumCast get/set/serialize for string-backed and int-backed enums
 * - EnumCast type mismatch rejection
 * - EnumColor class-level attribute with int-backed values
 * - EnumLabel case-level overrides over class-level defaults
 * - EnumIcon class-level default applied to all cases
 * - EnumDescription class-level and case-level resolution
 * - Cache invalidation affects subsequent resolve() calls
 * - EnumRule for pure enums (validates against case names)
 *
 * @see \ZeroBoiler\Enums\Casts\EnumCast
 * @see \ZeroBoiler\Enums\Support\EnumMetadataResolver
 * @see \ZeroBoiler\Enums\Rules\EnumRule
 */

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCast edge cases', function (): void {

    describe('EnumCast get() — string-backed enum', function (): void {
        it('returns enum instance for valid string value', function (): void {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(
                new \stdClass,
                'status',
                'active',
                ['status' => 'active'],
            );

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('returns null for null value', function (): void {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(
                new \stdClass,
                'status',
                null,
                ['status' => null],
            );

            expect($result)->toBeNull();
        });

        it('returns null for non-matching string value', function (): void {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(
                new \stdClass,
                'status',
                'nonexistent',
                ['status' => 'nonexistent'],
            );

            expect($result)->toBeNull();
        });

        it('returns null for non-string, non-int value', function (): void {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(
                new \stdClass,
                'status',
                ['array_value'],
                ['status' => ['array_value']],
            );

            expect($result)->toBeNull();
        });
    });

    describe('EnumCast get() — int-backed enum', function (): void {
        it('returns enum instance for valid int value', function (): void {
            $cast = new EnumCast(IntBackedPriority::class);
            $result = $cast->get(
                new \stdClass,
                'priority',
                1,
                ['priority' => 1],
            );

            expect($result)->toBe(IntBackedPriority::CRITICAL);
        });

        it('returns null for non-matching int value', function (): void {
            $cast = new EnumCast(IntBackedPriority::class);
            $result = $cast->get(
                new \stdClass,
                'priority',
                999,
                ['priority' => 999],
            );

            expect($result)->toBeNull();
        });
    });

    describe('EnumCast set() — type validation', function (): void {
        it('stores enum value for valid instance', function (): void {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(
                new \stdClass,
                'status',
                UserStatus::ACTIVE,
                ['status' => 'active'],
            );

            expect($result)->toBe('active');
        });

        it('rejects enum instance of wrong class', function (): void {
            $cast = new EnumCast(UserStatus::class);

            expect(fn () => $cast->set(
                new \stdClass,
                'status',
                PaymentStatus::APPROVED,
                ['status' => 'approved'],
            ))->toThrow(\InvalidArgumentException::class);
        });

        it('rejects invalid raw string value', function (): void {
            $cast = new EnumCast(UserStatus::class);

            expect(fn () => $cast->set(
                new \stdClass,
                'status',
                'nonexistent',
                ['status' => 'nonexistent'],
            ))->toThrow(\InvalidArgumentException::class);
        });

        it('returns null for null value', function (): void {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(
                new \stdClass,
                'status',
                null,
                ['status' => null],
            );

            expect($result)->toBeNull();
        });
    });

    describe('EnumCast serialize()', function (): void {
        it('serializes enum instance to backed value', function (): void {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(
                new \stdClass,
                'status',
                UserStatus::BANNED,
                ['status' => 'banned'],
            );

            expect($result)->toBe('banned');
        });

        it('passes through string values', function (): void {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(
                new \stdClass,
                'status',
                'active',
                ['status' => 'active'],
            );

            expect($result)->toBe('active');
        });

        it('passes through int values', function (): void {
            $cast = new EnumCast(IntBackedPriority::class);
            $result = $cast->serialize(
                new \stdClass,
                'priority',
                2,
                ['priority' => 2],
            );

            expect($result)->toBe(2);
        });

        it('returns null for non-scalar values', function (): void {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(
                new \stdClass,
                'status',
                ['array'],
                ['status' => ['array']],
            );

            expect($result)->toBeNull();
        });
    });
});

describe('Attribute resolution edge cases', function (): void {

    it('class-level EnumColor resolves for int-backed enum by int value', function (): void {
        // IntStatusWithColor uses EnumColor with int values: success=[1,4], warning=[2], danger=[3]
        expect(IntStatusWithColor::ACTIVE->color())->toBe('success');
        expect(IntStatusWithColor::ACTIVE->value)->toBe(1);
        expect(IntStatusWithColor::PENDING->color())->toBe('warning');
        expect(IntStatusWithColor::PENDING->value)->toBe(2);
        expect(IntStatusWithColor::BANNED->color())->toBe('danger');
        expect(IntStatusWithColor::BANNED->value)->toBe(3);
        expect(IntStatusWithColor::DRAFT->color())->toBe('success');
        expect(IntStatusWithColor::DRAFT->value)->toBe(4);
    });

    it('per-case Color overrides class-level EnumColor for int-backed', function (): void {
        // IntBackedPriority: CRITICAL has per-case #[Color('danger')], class-level also maps 1→danger
        // HIGH has per-case #[Color('warning')], class-level maps 2→warning
        // NONE has no per-case color, class-level maps 4→success
        expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
        expect(IntBackedPriority::HIGH->color())->toBe('warning');
        expect(IntBackedPriority::NONE->color())->toBe('success');
    });

    it('class-level EnumColor resolves correctly for string-backed enum', function (): void {
        // PaymentStatus: success=['approved'], danger=['rejected'], warning=['review']
        expect(PaymentStatus::APPROVED->color())->toBe('success');
        expect(PaymentStatus::REJECTED->color())->toBe('danger');
        expect(PaymentStatus::REVIEW->color())->toBe('warning');
    });

    it('EnumIcon class-level default applies to all cases', function (): void {
        // IntBackedPriority has EnumIcon(default: 'heroicon-o-flag')
        // All cases without per-case Icon should get the default
        expect(IntBackedPriority::CRITICAL->icon())->toBe('heroicon-o-flag');
        expect(IntBackedPriority::HIGH->icon())->toBe('heroicon-o-flag');
        expect(IntBackedPriority::LOW->icon())->toBe('heroicon-o-flag');
        expect(IntBackedPriority::NONE->icon())->toBe('heroicon-o-flag');
    });

    it('EnumLabel class-level overrides auto-generated labels', function (): void {
        // IntBackedPriority: class-level maps 1→'Critical Priority', 3→'Low Priority'
        expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
        expect(IntBackedPriority::LOW->label())->toBe('Low Priority');
    });

    it('per-case EnumLabel overrides class-level EnumLabel', function (): void {
        // IntBackedPriority: HIGH has per-case #[Label('High Priority')]
        // Class-level doesn't map 2, so per-case should take effect
        expect(IntBackedPriority::HIGH->label())->toBe('High Priority');
    });

    it('auto-generated label for int-backed enum without class-level or per-case', function (): void {
        // IntBackedPriority::NONE has no per-case or class-level label
        expect(IntBackedPriority::NONE->label())->toBe('None');
    });

    it('EnumDescription class-level resolves from int key', function (): void {
        // IntBackedPriority: class-level maps 1→description, 3→description
        expect(IntBackedPriority::CRITICAL->description())->toBe('Critical priority — immediate action required');
        expect(IntBackedPriority::LOW->description())->toBe('Low priority — handle when convenient');
    });

    it('EnumDescription case-level overrides class-level for string-backed', function (): void {
        // DetailedTicketStatus: IN_PROGRESS has per-case Description
        expect(DetailedTicketStatus::IN_PROGRESS->description())->toBe('Ticket is currently being worked on');
    });

    it('EnumDescription class-level provides default for string-backed', function (): void {
        // DetailedTicketStatus: OPEN gets class-level description
        expect(DetailedTicketStatus::OPEN->description())->toBe('Ticket is open and awaiting triage');
    });

    it('auto-generated label from SCREAMING_SNAKE_CASE', function (): void {
        // UserStatus::INACTIVE has no Label attribute
        expect(UserStatus::INACTIVE->label())->toBe('Inactive');
    });

    it('auto-generated label from camelCase', function (): void {
        // DetailedTicketStatus::IN_PROGRESS has no Label attribute, camelCase name
        expect(DetailedTicketStatus::IN_PROGRESS->label())->toBe('In Progress');
    });

    it('PaymentStatus class-level EnumLabel overrides auto-generated', function (): void {
        expect(PaymentStatus::APPROVED->label())->toBe('Approved Payment');
        expect(PaymentStatus::REJECTED->label())->toBe('Rejected Payment');
        expect(PaymentStatus::REVIEW->label())->toBe('Under Review');
    });

    it('PaymentStatus class-level EnumIcon default applies to all cases', function (): void {
        expect(PaymentStatus::APPROVED->icon())->toBe('heroicon-o-banknotes');
        expect(PaymentStatus::REJECTED->icon())->toBe('heroicon-o-banknotes');
    });

    it('cache invalidation triggers fresh resolve', function (): void {
        EnumMetadataResolver::invalidate(UserStatus::class);

        // After invalidation, resolve should rebuild from scratch
        $meta = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        expect($meta['labels'])->toBeArray();
    });

    it('invalidateAll clears all cached metadata', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        EnumMetadataResolver::invalidateAll();

        // Next resolve should work fine (rebuild from scratch)
        $meta = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta['labels'])->toBeArray();
    });
});

describe('EnumRule for pure enums', function (): void {
    it('validates against case names for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $passed = true;

        $rule->validate('flag', 'DARK_MODE', function () use (&$passed): void {
            $passed = false;
        });

        expect($passed)->toBeTrue();
    });

    it('rejects invalid case name for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;

        $rule->validate('flag', 'NONEXISTENT_FLAG', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('rejects non-string value for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;

        $rule->validate('flag', 123, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('nullable pure enum passes null', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class)->nullable();
        $passed = true;

        $rule->validate('flag', null, function () use (&$passed): void {
            $passed = false;
        });

        expect($passed)->toBeTrue();
    });

    it('non-nullable pure enum rejects null', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;

        $rule->validate('flag', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('InvalidEnumException message quality', function (): void {
    it('value() factory includes the actual value in message', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, 'active');

        expect($e->getMessage())->toContain('active');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('value() factory handles null display correctly', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, null);

        expect($e->getMessage())->toContain('null');
    });

    it('forName() factory includes the case name and class', function (): void {
        $e = InvalidEnumException::forName(PaymentStatus::class, 'MISPELLED');

        expect($e->getMessage())->toContain('MISPELLED');
        expect($e->getMessage())->toContain(PaymentStatus::class);
    });
});
