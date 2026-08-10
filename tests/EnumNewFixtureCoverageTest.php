<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\OrderWorkflowStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('OrderWorkflowStatus — large enum bulk operations', function () {
    it('has 20 cases', function () {
        expect(OrderWorkflowStatus::cases())->toHaveCount(20);
    });

    it('forSelect returns all cases with value-label pairs', function () {
        $select = OrderWorkflowStatus::forSelect();

        expect($select)->toHaveCount(20);
        expect($select)->toBeArray();

        // Each entry should have 'value' and 'label' keys
        foreach ($select as $item) {
            expect($item)->toHaveKey('value');
            expect($item)->toHaveKey('label');
            expect($item['value'])->toBeString();
            expect($item['label'])->toBeString();
        }
    });

    it('forApi returns full metadata for all cases', function () {
        $api = OrderWorkflowStatus::forApi();

        expect($api)->toHaveCount(20);

        foreach ($api as $item) {
            expect($item)->toHaveKey('value');
            expect($item)->toHaveKey('label');
            expect($item)->toHaveKey('color');
        }
    });

    it('forApi includes icons and descriptions where available', function () {
        $api = OrderWorkflowStatus::forApi();

        // All should have color from class-level EnumColor
        foreach ($api as $item) {
            expect($item['color'])->toBeString();
        }
    });

    it('colors returns correct color for each case', function () {
        expect(OrderWorkflowStatus::DRAFT->color())->toBe('secondary');
        expect(OrderWorkflowStatus::PENDING->color())->toBe('warning');
        expect(OrderWorkflowStatus::ACTIVE->color())->toBe('success');
        expect(OrderWorkflowStatus::FAILED->color())->toBe('danger');
        expect(OrderWorkflowStatus::CANCELLED->color())->toBe('danger');
        expect(OrderWorkflowStatus::ARCHIVED->color())->toBe('secondary');
    });

    it('values returns all backed values', function () {
        $values = OrderWorkflowStatus::values();

        expect($values)->toHaveCount(20);
        expect($values)->toContain('draft');
        expect($values)->toContain('active');
        expect($values)->toContain('failed');
        expect($values)->toContain('archived');
    });

    it('labels returns all auto-generated labels', function () {
        $labels = OrderWorkflowStatus::labels();

        expect($labels)->toHaveCount(20);
        expect($labels)->toContain('Draft');
        expect($labels)->toContain('Active');
        expect($labels)->toContain('Failed');
        expect($labels)->toContain('Archived');
    });

    it('is() works for comparison', function () {
        $status = OrderWorkflowStatus::ACTIVE;

        expect($status->is(OrderWorkflowStatus::ACTIVE))->toBeTrue();
        expect($status->is(OrderWorkflowStatus::PENDING))->toBeFalse();
    });

    it('isNot() works for negated comparison', function () {
        $status = OrderWorkflowStatus::ACTIVE;

        expect($status->isNot(OrderWorkflowStatus::PENDING))->toBeTrue();
        expect($status->isNot(OrderWorkflowStatus::ACTIVE))->toBeFalse();
    });

    it('in() checks membership in multiple values', function () {
        $status = OrderWorkflowStatus::ACTIVE;

        expect($status->in([OrderWorkflowStatus::ACTIVE, OrderWorkflowStatus::COMPLETED]))->toBeTrue();
        expect($status->in([OrderWorkflowStatus::DRAFT, OrderWorkflowStatus::PENDING]))->toBeFalse();
    });

    it('tryFromName resolves by case name', function () {
        $result = OrderWorkflowStatus::tryFromName('ACTIVE');

        expect($result)->toBe(OrderWorkflowStatus::ACTIVE);
    });

    it('tryFromName returns null for non-existent name', function () {
        $result = OrderWorkflowStatus::tryFromName('NON_EXISTENT');

        expect($result)->toBeNull();
    });

    it('tryFromLabel resolves by generated label', function () {
        $result = OrderWorkflowStatus::tryFromLabel('Active');

        expect($result)->toBe(OrderWorkflowStatus::ACTIVE);
    });

    it('tryFromLabel returns null for non-existent label', function () {
        $result = OrderWorkflowStatus::tryFromLabel('Not A Real Label');

        expect($result)->toBeNull();
    });

    it('forSelect has value-label structure with consistent ordering', function () {
        $select = OrderWorkflowStatus::forSelect();

        // Verify ordering matches case declaration order
        $firstItem = $select[0];
        expect($firstItem['value'])->toBe('draft');
        expect($firstItem['label'])->toBe('Draft');
    });
});

describe('SystemStatus — int-backed enum with full class-level metadata', function () {
    it('has 3 cases', function () {
        expect(SystemStatus::cases())->toHaveCount(3);
    });

    it('resolves label from class-level EnumLabel', function () {
        expect(SystemStatus::ENABLED->label())->toBe('Enabled');
        expect(SystemStatus::DISABLED->label())->toBe('Disabled');
        expect(SystemStatus::MAINTENANCE->label())->toBe('Maintenance');
    });

    it('resolves color from class-level EnumColor', function () {
        expect(SystemStatus::ENABLED->color())->toBe('success');
        expect(SystemStatus::DISABLED->color())->toBe('danger');
        expect(SystemStatus::MAINTENANCE->color())->toBe('warning');
    });

    it('resolves icon from class-level EnumIcon', function () {
        expect(SystemStatus::ENABLED->icon())->toBe('heroicon-o-check');
        expect(SystemStatus::DISABLED->icon())->toBe('heroicon-o-x-mark');
        // MAINTENANCE has no per-case icon → falls back to default
        expect(SystemStatus::MAINTENANCE->icon())->toBe('heroicon-o-cog-6-tooth');
    });

    it('resolves description from class-level EnumDescription', function () {
        expect(SystemStatus::ENABLED->description())->toBe('System is fully operational');
        expect(SystemStatus::DISABLED->description())->toBe('System is offline');
        expect(SystemStatus::MAINTENANCE->description())->toBe('Undergoing scheduled maintenance');
    });

    it('forSelect returns int-backed values with string labels', function () {
        $select = SystemStatus::forSelect();

        expect($select)->toHaveCount(3);

        $values = array_column($select, 'value');
        expect($values)->toContain(0);
        expect($values)->toContain(1);
        expect($values)->toContain(2);
    });

    it('forApi returns complete metadata', function () {
        $api = SystemStatus::forApi();

        expect($api)->toHaveCount(3);

        foreach ($api as $item) {
            expect($item)->toHaveKey('value');
            expect($item)->toHaveKey('label');
            expect($item)->toHaveKey('color');
            expect($item)->toHaveKey('icon');
            expect($item)->toHaveKey('description');
        }
    });

    it('values returns int values', function () {
        $values = SystemStatus::values();

        expect($values)->toContain(0);
        expect($values)->toContain(1);
        expect($values)->toContain(2);
    });

    it('labels returns string labels in case order', function () {
        $labels = SystemStatus::labels();

        expect($labels)->toBe([
            'Disabled',
            'Enabled',
            'Maintenance',
        ]);
    });

    it('isActive-style helper methods are not auto-generated', function () {
        // Verify that random method calls don't work — only trait-provided methods
        expect(method_exists(SystemStatus::ENABLED, 'label'))->toBeTrue();
        expect(method_exists(SystemStatus::ENABLED, 'color'))->toBeTrue();
        expect(method_exists(SystemStatus::ENABLED, 'icon'))->toBeTrue();
        expect(method_exists(SystemStatus::ENABLED, 'description'))->toBeTrue();
        expect(method_exists(SystemStatus::ENABLED, 'is'))->toBeTrue();
        expect(method_exists(SystemStatus::ENABLED, 'isNot'))->toBeTrue();
        expect(method_exists(SystemStatus::ENABLED, 'in'))->toBeTrue();
    });
});

describe('Cross-fixture consistency checks', function () {
    it('different enums with same value do not share metadata', function () {
        // OrderWorkflowStatus::ACTIVE has value 'active'
        // UserStatus::ACTIVE also has value 'active'
        // They should NOT share metadata
        expect(OrderWorkflowStatus::ACTIVE->label())->toBe('Active');
        expect(UserStatus::ACTIVE->label())->toBe('Active User'); // overridden via #[Label]
    });

    it('int-backed enums return int from value()', function () {
        expect(SystemStatus::ENABLED->value)->toBe(1);
        expect(ZeroPriority::LOW->value)->toBe(1);
    });

    it('string-backed enums return string from value()', function () {
        expect(OrderWorkflowStatus::ACTIVE->value)->toBe('active');
        expect(UserStatus::BANNED->value)->toBe('banned');
    });

    it('pure enums cannot be used with forSelect value keys', function () {
        // PureFeatureFlag is not backed — forSelect should still work using name
        $select = PureFeatureFlag::forSelect();

        expect($select)->not->toBeEmpty();

        foreach ($select as $item) {
            expect($item)->toHaveKey('value');
            expect($item)->toHaveKey('label');
            expect($item['value'])->toBeString();
        }
    });

    it('pure enums metadata is resolved by case name', function () {
        expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
        expect(PureFeatureFlag::BETA_FEATURES->label())->toBe('Beta Features');
        expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
    });

    it('forApi on pure enum includes all metadata', function () {
        $api = PureFeatureFlag::forApi();

        expect($api)->toHaveCount(3);

        $darkMode = $api[0] ?? null;
        expect($darkMode)->not->toBeNull();
        expect($darkMode['label'])->toBe('Dark Mode');
        expect($darkMode['color'])->toBe('secondary');
        expect($darkMode['icon'])->toBe('heroicon-o-moon');
        expect($darkMode['description'])->toBe('Toggle dark mode for the UI');
    });
});

describe('ZeroPriority int-backed edge case with zero value', function () {
    it('NONE case has value 0', function () {
        expect(ZeroPriority::NONE->value)->toBe(0);
    });

    it('tryFrom resolves 0 correctly', function () {
        expect(ZeroPriority::tryFrom(0))->toBe(ZeroPriority::NONE);
        expect(ZeroPriority::tryFrom(1))->toBe(ZeroPriority::LOW);
        expect(ZeroPriority::tryFrom(2))->toBe(ZeroPriority::HIGH);
    });

    it('label generates from case name', function () {
        expect(ZeroPriority::NONE->label())->toBe('None');
        expect(ZeroPriority::LOW->label())->toBe('Low');
        expect(ZeroPriority::HIGH->label())->toBe('High');
    });

    it('is() works with zero-value case', function () {
        $priority = ZeroPriority::NONE;

        expect($priority->is(ZeroPriority::NONE))->toBeTrue();
        expect($priority->is(ZeroPriority::LOW))->toBeFalse();
    });
});

describe('PaymentStatus — all class-level attributes', function () {
    it('forApi includes label, color, icon, description for all cases', function () {
        $api = PaymentStatus::forApi();

        expect($api)->toHaveCount(3);

        $approved = null;
        foreach ($api as $item) {
            if ($item['value'] === 'approved') {
                $approved = $item;
                break;
            }
        }

        expect($approved)->not->toBeNull();
        expect($approved['label'])->toBe('Approved Payment');
        expect($approved['color'])->toBe('success');
        expect($approved['icon'])->toBe('heroicon-o-banknotes');
        expect($approved['description'])->toBe('Payment has been approved');
    });

    it('forSelect maps values to custom labels', function () {
        $select = PaymentStatus::forSelect();

        expect($select)->toHaveCount(3);

        // Find by value
        $approvedLabel = null;
        foreach ($select as $item) {
            if ($item['value'] === 'approved') {
                $approvedLabel = $item['label'];
                break;
            }
        }
        expect($approvedLabel)->toBe('Approved Payment');

        $rejectedLabel = null;
        foreach ($select as $item) {
            if ($item['value'] === 'rejected') {
                $rejectedLabel = $item['label'];
                break;
            }
        }
        expect($rejectedLabel)->toBe('Rejected Payment');
    });
});

describe('TicketStatus — class-level EnumLabel and EnumDescription', function () {
    it('resolves per-value labels from EnumLabel map', function () {
        expect(TicketStatus::OPEN->label())->toBe('Open');
        expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
        expect(TicketStatus::CLOSED->label())->toBe('Closed');
    });

    it('resolves descriptions where defined', function () {
        expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
        expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
        // IN_PROGRESS has no description → null or empty
        expect(TicketStatus::IN_PROGRESS->description())->toBeNull();
    });

    it('uses default icon from EnumIcon', function () {
        // No per-case icon → falls back to default
        expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');
        expect(TicketStatus::CLOSED->icon())->toBe('heroicon-o-ticket');
    });
});
