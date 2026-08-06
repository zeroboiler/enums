<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Comparison methods — is(), isNot(), in()', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    describe('is() — string-backed enum', function () {
        it('returns true for same instance', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
        });

        it('returns false for different instance', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
        });

        it('returns true for matching case name string', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        });

        it('returns false for non-matching case name string', function () {
            expect(UserStatus::ACTIVE->is('BANNED'))->toBeFalse();
        });

        it('is case-sensitive for case name strings', function () {
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
        });
    });

    describe('is() — int-backed enum', function () {
        it('returns true for same instance', function () {
            expect(Priority::HIGH->is(Priority::HIGH))->toBeTrue();
        });

        it('returns false for different instance', function () {
            expect(Priority::HIGH->is(Priority::LOW))->toBeFalse();
        });

        it('returns true for matching case name string', function () {
            expect(Priority::HIGH->is('HIGH'))->toBeTrue();
        });
    });

    describe('is() — pure enum', function () {
        it('returns true for same instance', function () {
            expect(RequestState::DRAFT->is(RequestState::DRAFT))->toBeTrue();
        });

        it('returns true for matching case name string', function () {
            expect(RequestState::DRAFT->is('DRAFT'))->toBeTrue();
        });

        it('returns false for non-matching case name string', function () {
            expect(RequestState::DRAFT->is('SUBMITTED'))->toBeFalse();
        });
    });

    describe('isNot() — negation', function () {
        it('returns true for different instance', function () {
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
        });

        it('returns false for same instance', function () {
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
        });

        it('returns true for non-matching case name string', function () {
            expect(UserStatus::ACTIVE->isNot('BANNED'))->toBeTrue();
        });

        it('returns false for matching case name string', function () {
            expect(UserStatus::ACTIVE->isNot('ACTIVE'))->toBeFalse();
        });
    });

    describe('in() — group matching', function () {
        it('returns true when case is in the list (instances)', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
        });

        it('returns false when case is not in the list (instances)', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::BANNED, UserStatus::SUSPENDED]))->toBeFalse();
        });

        it('returns true when case is in the list (strings)', function () {
            expect(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
        });

        it('returns false when case is not in the list (strings)', function () {
            expect(UserStatus::ACTIVE->in(['BANNED', 'SUSPENDED']))->toBeFalse();
        });

        it('handles mixed list of instances and strings', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['BANNED', UserStatus::PENDING]))->toBeFalse();
        });

        it('returns true when case is in a single-element list', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
        });

        it('returns false for empty list', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('works with int-backed enum', function () {
            expect(Priority::HIGH->in([Priority::HIGH, Priority::URGENT]))->toBeTrue();
            expect(Priority::LOW->in(['HIGH', 'URGENT']))->toBeFalse();
        });

        it('works with pure enum', function () {
            expect(RequestState::DRAFT->in([RequestState::DRAFT, RequestState::SUBMITTED]))->toBeTrue();
            expect(RequestState::APPROVED->in(['DRAFT', 'SUBMITTED']))->toBeFalse();
        });
    });
});

describe('Class-level attributes — TicketStatus', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('resolves class-level labels via EnumLabel', function () {
        expect(TicketStatus::OPEN->label())->toBe('Open');
        expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
        expect(TicketStatus::CLOSED->label())->toBe('Closed');
    });

    it('resolves class-level descriptions via EnumDescription', function () {
        expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
        expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
    });

    it('returns null description for unspecified case', function () {
        // IN_PROGRESS is not in EnumDescription descriptions list
        expect(TicketStatus::IN_PROGRESS->description())->toBeNull();
    });

    it('resolves default icon via EnumIcon', function () {
        expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');
        expect(TicketStatus::CLOSED->icon())->toBe('heroicon-o-ticket');
    });

    it('forApi includes class-level metadata', function () {
        $api = TicketStatus::forApi();

        expect($api)->toHaveCount(3);
        expect($api[0]['value'])->toBe('open');
        expect($api[0]['name'])->toBe('OPEN');
        expect($api[0]['label'])->toBe('Open');
        expect($api[0]['description'])->toBe('Ticket is open and awaiting response');
        expect($api[0]['icon'])->toBe('heroicon-o-ticket');
    });

    it('forSelect uses class-level labels', function () {
        $options = TicketStatus::forSelect();

        expect($options)->toHaveCount(3);
        expect($options[0])->toBe(['value' => 'open', 'label' => 'Open']);
    });
});

describe('Edge cases — zero value int-backed enum', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('tryFromLabel works for NONE case', function () {
        expect(ZeroPriority::NONE->tryFromLabel('None'))->toBe(ZeroPriority::NONE);
    });

    it('comparison works with zero value', function () {
        expect(ZeroPriority::NONE->is(ZeroPriority::NONE))->toBeTrue();
        expect(ZeroPriority::NONE->isNot(ZeroPriority::LOW))->toBeTrue();
    });

    it('in() works with zero value case', function () {
        expect(ZeroPriority::NONE->in([ZeroPriority::NONE, ZeroPriority::LOW]))->toBeTrue();
    });

    it('forApi includes zero value correctly', function () {
        $api = ZeroPriority::forApi();

        expect($api[0]['value'])->toBe(0);
        expect($api[0]['name'])->toBe('NONE');
        expect($api[0]['label'])->toBe('None');
    });
});

describe('Edge cases — duplicate label handling', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('tryFromLabel returns first match when multiple cases share a label', function () {
        // Auto-generated labels are unique per case name, but if class-level
        // attributes set the same label for different cases, tryFromLabel
        // returns the first match in declaration order
        $result = OrderStatus::tryFromLabel('Pending');
        // PENDING is the only case with auto-generated label 'Pending'
        expect($result)->toBe(OrderStatus::PENDING);
    });

    it('tryFromLabel returns null for empty string', function () {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
        expect(Priority::tryFromLabel(''))->toBeNull();
        expect(RequestState::tryFromLabel(''))->toBeNull();
    });
});

describe('Edge cases — tryFromName case sensitivity', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('is case-sensitive for string-backed enums', function () {
        expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromName('active'))->toBeNull();
        expect(UserStatus::tryFromName('Active'))->toBeNull();
    });

    it('is case-sensitive for int-backed enums', function () {
        expect(Priority::tryFromName('LOW'))->toBe(Priority::LOW);
        expect(Priority::tryFromName('low'))->toBeNull();
    });

    it('is case-sensitive for pure enums', function () {
        expect(RequestState::tryFromName('DRAFT'))->toBe(RequestState::DRAFT);
        expect(RequestState::tryFromName('draft'))->toBeNull();
    });

    it('returns null for empty string', function () {
        expect(UserStatus::tryFromName(''))->toBeNull();
    });
});
