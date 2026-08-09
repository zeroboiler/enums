<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Class-level EnumIcon: default icon', function (): void {
    it('all cases inherit the class-level default icon', function (): void {
        expect(DefaultIconFeature::SEARCH->icon())->toBe('heroicon-o-circle-question-mark');
        expect(DefaultIconFeature::FILTER->icon())->toBe('heroicon-o-circle-question-mark');
    });

    it('forApi includes the default icon for every case', function (): void {
        $api = DefaultIconFeature::forApi();

        foreach ($api as $case) {
            expect($case['icon'])->toBe('heroicon-o-circle-question-mark');
        }
    });
});

describe('Class-level EnumIcon with per-case override', function (): void {
    it('per-case Icon overrides the class-level default', function (): void {
        expect(OverriddenIconRole::ADMIN->icon())->toBe('heroicon-o-user');
    });

    it('cases without per-case Icon inherit the default', function (): void {
        expect(OverriddenIconRole::VIEWER->icon())->toBe('heroicon-o-circle-question-mark');
    });
});

describe('Class-level EnumDescription: description map', function (): void {
    it('resolves class-level descriptions from map', function (): void {
        expect(DetailedTicketStatus::OPEN->description())->toBe('Ticket is open and awaiting triage');
        expect(DetailedTicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
    });

    it('per-case Description overrides class-level map', function (): void {
        expect(DetailedTicketStatus::IN_PROGRESS->description())->toBe('Ticket is currently being worked on');
    });

    it('forApi includes descriptions for all cases', function (): void {
        $api = DetailedTicketStatus::forApi();

        expect($api)->toHaveCount(3);

        $descriptions = array_column($api, 'description');
        expect($descriptions)->not->toContainNull();
    });
});

describe('Comprehensive class-level attributes (PaymentStatus)', function (): void {
    it('all class-level attributes resolve correctly', function (): void {
        // Label from EnumLabel
        expect(PaymentStatus::APPROVED->label())->toBe('Approved Payment');
        expect(PaymentStatus::REJECTED->label())->toBe('Rejected Payment');
        expect(PaymentStatus::REVIEW->label())->toBe('Under Review');

        // Description from EnumDescription
        expect(PaymentStatus::APPROVED->description())->toBe('Payment has been approved');
        expect(PaymentStatus::REJECTED->description())->toBe('Payment was rejected');
        expect(PaymentStatus::REVIEW->description())->toBe('Payment is under review');

        // Color from EnumColor
        expect(PaymentStatus::APPROVED->color())->toBe('success');
        expect(PaymentStatus::REJECTED->color())->toBe('danger');
        expect(PaymentStatus::REVIEW->color())->toBe('warning');

        // Icon from EnumIcon default
        expect(PaymentStatus::APPROVED->icon())->toBe('heroicon-o-banknotes');
        expect(PaymentStatus::REJECTED->icon())->toBe('heroicon-o-banknotes');
        expect(PaymentStatus::REVIEW->icon())->toBe('heroicon-o-banknotes');
    });

    it('forApi returns complete metadata for every case', function (): void {
        $api = PaymentStatus::forApi();

        expect($api)->toHaveCount(3);

        foreach ($api as $entry) {
            expect($entry)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($entry['label'])->toBeString()->not->toBeEmpty();
            expect($entry['description'])->toBeString()->not->toBeEmpty();
            expect($entry['color'])->toBeString()->not->toBeEmpty();
            expect($entry['icon'])->toBeString()->not->toBeEmpty();
        }
    });

    it('forSelect uses backed values', function (): void {
        $select = PaymentStatus::forSelect();

        $values = array_column($select, 'value');
        expect($values)->toContain('approved');
        expect($values)->toContain('rejected');
        expect($values)->toContain('review');
    });

    it('values() returns backed values', function (): void {
        $values = PaymentStatus::values();

        expect($values)->toContain('approved');
        expect($values)->toContain('rejected');
        expect($values)->toContain('review');
    });

    it('labels() returns class-level labels', function (): void {
        $labels = PaymentStatus::labels();

        expect($labels)->toBe([
            'Approved Payment',
            'Rejected Payment',
            'Under Review',
        ]);
    });
});

describe('Attribute priority: per-case > class-level > auto-generated', function (): void {
    it('per-case label overrides class-level label', function (): void {
        // UserStatus ACTIVE has per-case #[Label('Active User')]
        expect(UserStatus::ACTIVE->label())->toBe('Active User');
    });

    it('class-level label is used when no per-case override exists', function (): void {
        // PaymentStatus uses only class-level labels
        expect(PaymentStatus::APPROVED->label())->toBe('Approved Payment');
    });

    it('auto-generated label is used when no attributes exist', function (): void {
        // UserStatus INACTIVE has no Label or EnumLabel
        expect(UserStatus::INACTIVE->label())->toBeString()->not->toBeEmpty();
    });
});
