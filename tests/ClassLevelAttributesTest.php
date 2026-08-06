<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Class-Level Enum Attributes', function () {
    beforeEach(function () {
        EnumCache::flush();
    });

    it('resolves class-level EnumLabel for all cases', function () {
        expect(TicketStatus::OPEN->label())->toBe('Open')
            ->and(TicketStatus::IN_PROGRESS->label())->toBe('In Progress')
            ->and(TicketStatus::CLOSED->label())->toBe('Closed');
    });

    it('resolves class-level EnumDescription for defined cases', function () {
        expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response')
            ->and(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
    });

    it('returns null for cases without class-level EnumDescription', function () {
        expect(TicketStatus::IN_PROGRESS->description())->toBeNull();
    });

    it('applies class-level EnumIcon default to all cases', function () {
        expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket')
            ->and(TicketStatus::IN_PROGRESS->icon())->toBe('heroicon-o-ticket')
            ->and(TicketStatus::CLOSED->icon())->toBe('heroicon-o-ticket');
    });

    it('defaults color to secondary when no EnumColor is set', function () {
        expect(TicketStatus::OPEN->color())->toBe('secondary')
            ->and(TicketStatus::IN_PROGRESS->color())->toBe('secondary')
            ->and(TicketStatus::CLOSED->color())->toBe('secondary');
    });

    it('generates forSelect with class-level labels', function () {
        $select = TicketStatus::forSelect();

        expect($select)->toHaveCount(3);
        expect($select[0])->toBe([
            'value' => 'open',
            'label' => 'Open',
        ]);
    });

    it('generates forApi with class-level metadata', function () {
        $api = TicketStatus::forApi();

        expect($api)->toHaveCount(3);

        $open = $api[0];
        expect($open['value'])->toBe('open')
            ->and($open['name'])->toBe('OPEN')
            ->and($open['label'])->toBe('Open')
            ->and($open['icon'])->toBe('heroicon-o-ticket')
            ->and($open['color'])->toBe('secondary');
    });

    it('tryFromLabel resolves with class-level labels', function () {
        expect(TicketStatus::tryFromLabel('Open'))->toBe(TicketStatus::OPEN)
            ->and(TicketStatus::tryFromLabel('In Progress'))->toBe(TicketStatus::IN_PROGRESS)
            ->and(TicketStatus::tryFromLabel('Closed'))->toBe(TicketStatus::CLOSED);
    });

    it('tryFromLabel is case-insensitive for class-level labels', function () {
        expect(TicketStatus::tryFromLabel('open'))->toBe(TicketStatus::OPEN)
            ->and(TicketStatus::tryFromLabel('IN PROGRESS'))->toBe(TicketStatus::IN_PROGRESS);
    });

    it('tryFromLabel returns null for non-existent labels', function () {
        expect(TicketStatus::tryFromLabel('NonExistent'))->toBeNull();
    });

    it('values() returns backed values for class-level attribute enum', function () {
        expect(TicketStatus::values())->toBe([
            'open',
            'in_progress',
            'closed',
        ]);
    });

    it('labels() returns class-level labels in order', function () {
        expect(TicketStatus::labels())->toBe([
            'Open',
            'In Progress',
            'Closed',
        ]);
    });
});

describe('Class-Level Attribute Override by Per-Case', function () {
    beforeEach(function () {
        EnumCache::flush();
    });

    it('per-case Color overrides class-level EnumColor', function () {
        // UserStatus has class-level EnumColor with success: ['active']
        // and per-case Color('danger') on BANNED
        expect(UserStatus::ACTIVE->color())->toBe('success')
            ->and(UserStatus::BANNED->color())->toBe('danger');
    });

    it('per-case Label overrides class-level EnumLabel', function () {
        // UserStatus ACTIVE has per-case Label('Active User')
        expect(UserStatus::ACTIVE->label())->toBe('Active User');
    });

    it('per-case Icon overrides class-level EnumIcon', function () {
        // UserStatus ACTIVE has per-case Icon('heroicon-o-check-circle')
        expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
    });

    it('per-case Description overrides class-level EnumDescription', function () {
        // UserStatus ACTIVE has per-case Description
        expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
    });
});
