<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Edge case tests for enum comparison methods (is, isNot, in).
 */
describe('Enum comparison edge cases', function () {
    it('is() works with enum instances (strict identity)', function () {
        expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
        expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
        expect(UserStatus::ACTIVE->is(UserStatus::INACTIVE))->toBeFalse();
    });

    it('is() works with case name strings (case-sensitive)', function () {
        expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
        expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
    });

    it('isNot() is correct negation of is()', function () {
        $status = UserStatus::ACTIVE;

        // Instance
        expect($status->isNot(UserStatus::ACTIVE))->toBeFalse();
        expect($status->isNot(UserStatus::BANNED))->toBeTrue();

        // String
        expect($status->isNot('ACTIVE'))->toBeFalse();
        expect($status->isNot('BANNED'))->toBeTrue();
    });

    it('in() with empty array returns false', function () {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('in() with single element', function () {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
        expect(UserStatus::ACTIVE->in([UserStatus::BANNED]))->toBeFalse();
    });

    it('in() with all instances', function () {
        $status = UserStatus::ACTIVE;
        $allCases = UserStatus::cases();
        expect($status->in($allCases))->toBeTrue();
    });

    it('in() with mixed instances and strings', function () {
        $status = UserStatus::ACTIVE;
        expect($status->in([UserStatus::BANNED, 'ACTIVE']))->toBeTrue();
        expect($status->in(['BANNED', UserStatus::PENDING]))->toBeFalse();
    });

    it('in() with all strings', function () {
        $status = UserStatus::ACTIVE;
        expect($status->in(['BANNED', 'PENDING']))->toBeFalse();
        expect($status->in(['BANNED', 'ACTIVE']))->toBeTrue();
    });

    it('is() returns false for different enum types', function () {
        // UserStatus::ACTIVE is not the same as TicketStatus::OPEN
        // Even though the string 'OPEN' isn't a UserStatus case name,
        // this still returns false correctly
        expect(UserStatus::ACTIVE->is('OPEN'))->toBeFalse();
    });

    it('comparison works with int-backed enums', function () {
        expect(Priority::HIGH->is(Priority::HIGH))->toBeTrue();
        expect(Priority::HIGH->is('HIGH'))->toBeTrue();
        expect(Priority::HIGH->is('high'))->toBeFalse(); // case-sensitive
        expect(Priority::HIGH->isNot(Priority::LOW))->toBeTrue();
        expect(Priority::HIGH->in([Priority::HIGH, Priority::URGENT]))->toBeTrue();
    });

    it('is() is reflexive for all cases', function () {
        foreach (UserStatus::cases() as $case) {
            expect($case->is($case))->toBeTrue();
            expect($case->is($case->name))->toBeTrue();
        }
    });

    it('isNot() is irreflexive for all cases', function () {
        foreach (UserStatus::cases() as $case) {
            expect($case->isNot($case))->toBeFalse();
            expect($case->isNot($case->name))->toBeFalse();
        }
    });
});
