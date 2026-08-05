<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function (): void {
    EnumCache::flush();
});

describe('HasEnumMetadata::tryFromName', function (): void {
    it('resolves a case by its name on a string-backed enum', function (): void {
        expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromName('PENDING'))->toBe(UserStatus::PENDING);
        expect(UserStatus::tryFromName('BANNED'))->toBe(UserStatus::BANNED);
    });

    it('resolves a case by its name on an int-backed enum', function (): void {
        expect(Priority::tryFromName('LOW'))->toBe(Priority::LOW);
        expect(Priority::tryFromName('URGENT'))->toBe(Priority::URGENT);
    });

    it('resolves a case by its name on a pure enum', function (): void {
        expect(RequestState::tryFromName('DRAFT'))->toBe(RequestState::DRAFT);
        expect(RequestState::tryFromName('APPROVED'))->toBe(RequestState::APPROVED);
    });

    it('returns null for a non-existent case name', function (): void {
        expect(UserStatus::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('returns null for an empty string', function (): void {
        expect(UserStatus::tryFromName(''))->toBeNull();
    });

    it('is case-sensitive (exact name match required)', function (): void {
        // PHP enum case names are uppercase by convention; lowercase should not match
        expect(UserStatus::tryFromName('active'))->toBeNull();
        expect(OrderStatus::tryFromName('pending'))->toBeNull();
    });
});

describe('HasEnumMetadata::fromName', function (): void {
    it('resolves a case by its name', function (): void {
        expect(OrderStatus::fromName('PENDING'))->toBe(OrderStatus::PENDING);
        expect(OrderStatus::fromName('DELIVERED'))->toBe(OrderStatus::DELIVERED);
    });

    it('resolves on int-backed enums', function (): void {
        expect(Priority::fromName('HIGH'))->toBe(Priority::HIGH);
    });

    it('resolves on pure enums', function (): void {
        expect(RequestState::fromName('SUBMITTED'))->toBe(RequestState::SUBMITTED);
    });

    it('throws InvalidEnumException for a non-existent case name', function (): void {
        expect(fn (): UserStatus => UserStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('throws InvalidEnumException for empty string', function (): void {
        expect(fn (): OrderStatus => OrderStatus::fromName(''))
            ->toThrow(InvalidEnumException::class);
    });

    it('exception message contains the case name and enum class', function (): void {
        try {
            UserStatus::fromName('WHATEVER');
            expect(false)->toBeTrue('Should have thrown');
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())
                ->toContain('WHATEVER')
                ->toContain(UserStatus::class);
        }
    });

    it('is case-sensitive', function (): void {
        expect(fn (): OrderStatus => OrderStatus::fromName('pending'))
            ->toThrow(InvalidEnumException::class);
    });
});

describe('HasEnumMetadata::hasCase', function (): void {
    it('returns true for existing case names', function (): void {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('PENDING'))->toBeTrue();
        expect(UserStatus::hasCase('BANNED'))->toBeTrue();
    });

    it('returns false for non-existent case names', function (): void {
        expect(UserStatus::hasCase('NON_EXISTENT'))->toBeFalse();
    });

    it('returns false for empty string', function (): void {
        expect(UserStatus::hasCase(''))->toBeFalse();
    });

    it('works with int-backed enums', function (): void {
        expect(Priority::hasCase('LOW'))->toBeTrue();
        expect(Priority::hasCase('MEDIUM'))->toBeTrue();
        expect(Priority::hasCase('HIGH'))->toBeTrue();
        expect(Priority::hasCase('URGENT'))->toBeTrue();
        expect(Priority::hasCase('CRITICAL'))->toBeFalse();
    });

    it('works with pure enums', function (): void {
        expect(RequestState::hasCase('DRAFT'))->toBeTrue();
        expect(RequestState::hasCase('REJECTED'))->toBeTrue();
        expect(RequestState::hasCase('CANCELLED'))->toBeFalse();
    });

    it('is case-sensitive', function (): void {
        expect(UserStatus::hasCase('active'))->toBeFalse();
        expect(OrderStatus::hasCase('Pending'))->toBeFalse();
    });
});

describe('fromName vs tryFrom integration', function (): void {
    it('fromName returns the same instance as tryFromName on success', function (): void {
        $a = UserStatus::tryFromName('ACTIVE');
        $b = UserStatus::fromName('ACTIVE');

        expect($a)->toBe($b);
        expect($a)->toBe(UserStatus::ACTIVE);
    });

    it('all cases can be resolved by name round-trip', function (): void {
        foreach (OrderStatus::cases() as $case) {
            $resolved = OrderStatus::tryFromName($case->name);
            expect($resolved)->toBe($case);
        }

        foreach (Priority::cases() as $case) {
            $resolved = Priority::tryFromName($case->name);
            expect($resolved)->toBe($case);
        }
    });
});
