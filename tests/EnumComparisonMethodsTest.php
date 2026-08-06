<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum is() method', function () {
    it('compares against another enum instance', function () {
        $active = UserStatus::ACTIVE;
        $alsoActive = UserStatus::ACTIVE;

        expect($active->is($alsoActive))->toBeTrue();
    });

    it('returns false for different instances', function () {
        $active = UserStatus::ACTIVE;
        $banned = UserStatus::BANNED;

        expect($active->is($banned))->toBeFalse();
    });

    it('compares against a string case name', function () {
        $active = UserStatus::ACTIVE;

        expect($active->is('ACTIVE'))->toBeTrue();
        expect($active->is('BANNED'))->toBeFalse();
    });

    it('is case-sensitive for string comparison', function () {
        $active = UserStatus::ACTIVE;

        expect($active->is('active'))->toBeFalse();
        expect($active->is('Active'))->toBeFalse();
    });

    it('works with int-backed enums', function () {
        $pending = OrderStatus::PENDING;

        expect($pending->is(OrderStatus::PENDING))->toBeTrue();
        expect($pending->is('PENDING'))->toBeTrue();
        expect($pending->is('SHIPPED'))->toBeFalse();
    });

    it('works with pure enums', function () {
        $state = \ZeroBoiler\Enums\Tests\Fixtures\TicketStatus::OPEN;

        expect($state->is('OPEN'))->toBeTrue();
        expect($state->is(\ZeroBoiler\Enums\Tests\Fixtures\TicketStatus::CLOSED))->toBeFalse();
    });
});

describe('Enum isNot() method', function () {
    it('returns true for different instances', function () {
        $active = UserStatus::ACTIVE;
        $banned = UserStatus::BANNED;

        expect($active->isNot($banned))->toBeTrue();
    });

    it('returns false for same instances', function () {
        $active = UserStatus::ACTIVE;

        expect($active->isNot(UserStatus::ACTIVE))->toBeFalse();
    });

    it('returns false for matching string case name', function () {
        $active = UserStatus::ACTIVE;

        expect($active->isNot('ACTIVE'))->toBeFalse();
    });

    it('returns true for non-matching string case name', function () {
        $active = UserStatus::ACTIVE;

        expect($active->isNot('BANNED'))->toBeTrue();
    });
});

describe('Enum in() method', function () {
    it('returns true when case is in the list', function () {
        $active = UserStatus::ACTIVE;

        expect($active->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
    });

    it('returns false when case is not in the list', function () {
        $active = UserStatus::ACTIVE;

        expect($active->in(['BANNED', 'SUSPENDED']))->toBeFalse();
    });

    it('works with string case names', function () {
        $active = UserStatus::ACTIVE;

        expect($active->in(['ACTIVE', 'PENDING']))->toBeTrue();
        expect($active->in(['BANNED', 'DELETED']))->toBeFalse();
    });

    it('works with mixed instance and string inputs', function () {
        $active = UserStatus::ACTIVE;

        expect($active->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
    });

    it('returns false for empty array', function () {
        $active = UserStatus::ACTIVE;

        expect($active->in([]))->toBeFalse();
    });

    it('works with int-backed enums', function () {
        $pending = OrderStatus::PENDING;

        expect($pending->in([OrderStatus::PENDING, OrderStatus::SHIPPED]))->toBeTrue();
        expect($pending->in([OrderStatus::DELIVERED, OrderStatus::CANCELLED]))->toBeFalse();
    });
});
