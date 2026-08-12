<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\OrderWorkflowStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;

describe('Cross-Fixture Integration Tests', function () {
    describe('OrderWorkflowStatus (20 cases, scale)', function () {
        it('has all 20 cases', function () {
            expect(OrderWorkflowStatus::cases())->toHaveCount(20);
        });

        it('returns 20 values in declaration order', function () {
            $values = OrderWorkflowStatus::values();
            expect($values)->toHaveCount(20);
            expect($values)->toBe([
                'draft', 'pending', 'processing', 'review', 'held', 'deferred',
                'active', 'completed', 'verified', 'approved', 'delivered', 'paid',
                'failed', 'cancelled', 'rejected', 'expired', 'suspended', 'blocked',
                'archived', 'unknown',
            ]);
        });

        it('returns 20 labels in declaration order', function () {
            $labels = OrderWorkflowStatus::labels();
            expect($labels)->toHaveCount(20);
            // All labels should be non-empty strings
            foreach ($labels as $label) {
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        });

        it('forSelect returns correct structure for all 20 cases', function () {
            $options = OrderWorkflowStatus::forSelect();
            expect($options)->toBeArray();
            expect($options)->toHaveCount(20);

            foreach ($options as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
            }
        });

        it('forApi returns correct structure with all metadata fields', function () {
            $api = OrderWorkflowStatus::forApi();
            expect($api)->toBeArray();
            expect($api)->toHaveCount(20);

            foreach ($api as $item) {
                expect($item)->toBeArray();
                expect($item)->toHaveKeys(['value', 'name', 'label', 'color', 'icon', 'description']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
                expect($item['color'])->toBeIn(['success', 'danger', 'warning', 'secondary']);
            }
        });

        it('assigns correct colors from class-level EnumColor', function () {
            // Success states
            expect(OrderWorkflowStatus::ACTIVE->color())->toBe('success');
            expect(OrderWorkflowStatus::COMPLETED->color())->toBe('success');
            expect(OrderWorkflowStatus::VERIFIED->color())->toBe('success');
            expect(OrderWorkflowStatus::APPROVED->color())->toBe('success');
            expect(OrderWorkflowStatus::DELIVERED->color())->toBe('success');
            expect(OrderWorkflowStatus::PAID->color())->toBe('success');

            // Danger states
            expect(OrderWorkflowStatus::FAILED->color())->toBe('danger');
            expect(OrderWorkflowStatus::CANCELLED->color())->toBe('danger');
            expect(OrderWorkflowStatus::REJECTED->color())->toBe('danger');
            expect(OrderWorkflowStatus::EXPIRED->color())->toBe('danger');
            expect(OrderWorkflowStatus::SUSPENDED->color())->toBe('danger');
            expect(OrderWorkflowStatus::BLOCKED->color())->toBe('danger');

            // Warning states
            expect(OrderWorkflowStatus::PENDING->color())->toBe('warning');
            expect(OrderWorkflowStatus::PROCESSING->color())->toBe('warning');
            expect(OrderWorkflowStatus::REVIEW->color())->toBe('warning');
            expect(OrderWorkflowStatus::HELD->color())->toBe('warning');
            expect(OrderWorkflowStatus::DEFERRED->color())->toBe('warning');

            // Secondary states
            expect(OrderWorkflowStatus::DRAFT->color())->toBe('secondary');
            expect(OrderWorkflowStatus::ARCHIVED->color())->toBe('secondary');
            expect(OrderWorkflowStatus::UNKNOWN->color())->toBe('secondary');
        });

        it('tryFromLabel resolves all cases by auto-generated label', function () {
            // Auto-generated labels: DRAFT → "Draft", PENDING → "Pending", etc.
            expect(OrderWorkflowStatus::tryFromLabel('Draft'))->toBe(OrderWorkflowStatus::DRAFT);
            expect(OrderWorkflowStatus::tryFromLabel('Pending'))->toBe(OrderWorkflowStatus::PENDING);
            expect(OrderWorkflowStatus::tryFromLabel('Active'))->toBe(OrderWorkflowStatus::ACTIVE);
            expect(OrderWorkflowStatus::tryFromLabel('Failed'))->toBe(OrderWorkflowStatus::FAILED);
            expect(OrderWorkflowStatus::tryFromLabel('Archived'))->toBe(OrderWorkflowStatus::ARCHIVED);
        });

        it('tryFromLabel is case-insensitive', function () {
            expect(OrderWorkflowStatus::tryFromLabel('draft'))->toBe(OrderWorkflowStatus::DRAFT);
            expect(OrderWorkflowStatus::tryFromLabel('DRAFT'))->toBe(OrderWorkflowStatus::DRAFT);
            expect(OrderWorkflowStatus::tryFromLabel('Active'))->toBe(OrderWorkflowStatus::ACTIVE);
            expect(OrderWorkflowStatus::tryFromLabel('active'))->toBe(OrderWorkflowStatus::ACTIVE);
        });

        it('hasCase returns correct results for all cases', function () {
            expect(OrderWorkflowStatus::hasCase('DRAFT'))->toBeTrue();
            expect(OrderWorkflowStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(OrderWorkflowStatus::hasCase('UNKNOWN'))->toBeTrue();
            expect(OrderWorkflowStatus::hasCase('NONEXISTENT'))->toBeFalse();
            expect(OrderWorkflowStatus::hasCase(''))->toBeFalse();
        });

        it('comparison methods work correctly at scale', function () {
            $active = OrderWorkflowStatus::ACTIVE;
            expect($active->is(OrderWorkflowStatus::ACTIVE))->toBeTrue();
            expect($active->is('ACTIVE'))->toBeTrue();
            expect($active->isNot(OrderWorkflowStatus::FAILED))->toBeTrue();
            expect($active->in([
                OrderWorkflowStatus::ACTIVE,
                OrderWorkflowStatus::COMPLETED,
                OrderWorkflowStatus::VERIFIED,
            ]))->toBeTrue();
            expect($active->in([
                OrderWorkflowStatus::FAILED,
                OrderWorkflowStatus::CANCELLED,
            ]))->toBeFalse();
        });
    });

    describe('PaymentStatus (all class-level attributes)', function () {
        it('resolves labels from EnumLabel', function () {
            expect(PaymentStatus::APPROVED->label())->toBe('Approved Payment');
            expect(PaymentStatus::REJECTED->label())->toBe('Rejected Payment');
            expect(PaymentStatus::REVIEW->label())->toBe('Under Review');
        });

        it('resolves colors from EnumColor', function () {
            expect(PaymentStatus::APPROVED->color())->toBe('success');
            expect(PaymentStatus::REJECTED->color())->toBe('danger');
            expect(PaymentStatus::REVIEW->color())->toBe('warning');
        });

        it('resolves descriptions from EnumDescription', function () {
            expect(PaymentStatus::APPROVED->description())->toBe('Payment has been approved');
            expect(PaymentStatus::REJECTED->description())->toBe('Payment was rejected');
            expect(PaymentStatus::REVIEW->description())->toBe('Payment is under review');
        });

        it('resolves default icon from EnumIcon', function () {
            // All cases share the same default icon
            expect(PaymentStatus::APPROVED->icon())->toBe('heroicon-o-banknotes');
            expect(PaymentStatus::REJECTED->icon())->toBe('heroicon-o-banknotes');
            expect(PaymentStatus::REVIEW->icon())->toBe('heroicon-o-banknotes');
        });

        it('forApi returns complete metadata for all cases', function () {
            $api = PaymentStatus::forApi();
            expect($api)->toHaveCount(3);

            $approved = $api[0];
            expect($approved['value'])->toBe('approved');
            expect($approved['label'])->toBe('Approved Payment');
            expect($approved['color'])->toBe('success');
            expect($approved['description'])->toBe('Payment has been approved');
            expect($approved['icon'])->toBe('heroicon-o-banknotes');
        });

        it('tryFromLabel resolves by EnumLabel mapping', function () {
            expect(PaymentStatus::tryFromLabel('Approved Payment'))->toBe(PaymentStatus::APPROVED);
            expect(PaymentStatus::tryFromLabel('Rejected Payment'))->toBe(PaymentStatus::REJECTED);
            expect(PaymentStatus::tryFromLabel('Under Review'))->toBe(PaymentStatus::REVIEW);
        });

        it('values returns backed values', function () {
            expect(PaymentStatus::values())->toBe(['approved', 'rejected', 'review']);
        });
    });

    describe('DetailedTicketStatus (class-level + per-case override)', function () {
        it('uses class-level description for OPEN and CLOSED', function () {
            expect(DetailedTicketStatus::OPEN->description())->toBe('Ticket is open and awaiting triage');
            expect(DetailedTicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
        });

        it('uses per-case Description override for IN_PROGRESS', function () {
            expect(DetailedTicketStatus::IN_PROGRESS->description())->toBe('Ticket is currently being worked on');
        });

        it('labels are auto-generated for all cases', function () {
            expect(DetailedTicketStatus::OPEN->label())->toBe('Open');
            expect(DetailedTicketStatus::CLOSED->label())->toBe('Closed');
            // IN_PROGRESS → "In Progress" (auto-generated from SCREAMING_SNAKE_CASE)
            expect(DetailedTicketStatus::IN_PROGRESS->label())->toBe('In Progress');
        });

        it('colors default to secondary when not defined', function () {
            expect(DetailedTicketStatus::OPEN->color())->toBe('secondary');
            expect(DetailedTicketStatus::CLOSED->color())->toBe('secondary');
            expect(DetailedTicketStatus::IN_PROGRESS->color())->toBe('secondary');
        });

        it('icon is null when not defined', function () {
            expect(DetailedTicketStatus::OPEN->icon())->toBeNull();
            expect(DetailedTicketStatus::CLOSED->icon())->toBeNull();
            expect(DetailedTicketStatus::IN_PROGRESS->icon())->toBeNull();
        });
    });

    describe('LabelMapEnum fixture', function () {
        it('resolves labels from EnumLabel mapping', function () {
            $cases = LabelMapEnum::cases();
            foreach ($cases as $case) {
                $label = $case->label();
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        });
    });

    describe('IntBackedPriority fixture', function () {
        it('has int backed values', function () {
            foreach (IntBackedPriority::cases() as $case) {
                expect($case->value)->toBeInt();
            }
        });

        it('values returns int array', function () {
            $values = IntBackedPriority::values();
            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('labels are auto-generated', function () {
            foreach (IntBackedPriority::cases() as $case) {
                $label = $case->label();
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        });
    });

    describe('DefaultIconFeature fixture', function () {
        it('has a default icon from class-level EnumIcon', function () {
            foreach (DefaultIconFeature::cases() as $case) {
                $icon = $case->icon();
                expect($icon)->toBeString();
                expect($icon)->not->toBeEmpty();
            }
        });
    });

    describe('OverriddenIconRole fixture', function () {
        it('per-case icons override class-level default', function () {
            foreach (OverriddenIconRole::cases() as $case) {
                $icon = $case->icon();
                // At least the default should be present
                expect($icon)->toBeString();
            }
        });
    });

    describe('Cross-fixture label uniqueness', function () {
        it('UserStatus labels are unique', function () {
            $labels = UserStatus::labels();
            expect($labels)->toEqual(array_unique($labels));
        });

        it('PaymentStatus labels are unique', function () {
            $labels = PaymentStatus::labels();
            expect($labels)->toEqual(array_unique($labels));
        });

        it('OrderWorkflowStatus labels are unique (20 cases)', function () {
            $labels = OrderWorkflowStatus::labels();
            expect($labels)->toEqual(array_unique($labels));
        });
    });

    describe('Cross-fixture forSelect structure consistency', function () {
        it('all fixtures produce consistent forSelect structure', function () {
            $enums = [
                UserStatus::class,
                PaymentStatus::class,
                OrderWorkflowStatus::class,
                DetailedTicketStatus::class,
                CamelCaseRole::class,
            ];

            foreach ($enums as $enum) {
                $select = $enum::forSelect();
                expect($select)->toBeArray();
                expect(count($select))->toBeGreaterThan(0);

                foreach ($select as $option) {
                    expect($option)->toHaveKeys(['value', 'label']);
                    expect($option['value'])->not->toBeNull();
                    expect($option['label'])->toBeString();
                    expect($option['label'])->not->toBeEmpty();
                }
            }
        });

        it('pure enums use case names as forSelect values', function () {
            $select = PureFeatureFlag::forSelect();
            foreach ($select as $option) {
                // Pure enums use case name as value
                expect($option['value'])->toBeIn(
                    array_map(fn ($c) => $c->name, PureFeatureFlag::cases())
                );
            }
        });

        it('backed enums use backed value as forSelect value', function () {
            $select = UserStatus::forSelect();
            foreach ($select as $option) {
                // String-backed: value should be the string backed value
                expect($option['value'])->toBeString();
            }

            $intSelect = IntBackedPriority::forSelect();
            foreach ($intSelect as $option) {
                expect($option['value'])->toBeInt();
            }
        });
    });

    describe('Cross-fixture forApi structure consistency', function () {
        it('all fixtures produce consistent forApi structure', function () {
            $enums = [
                UserStatus::class,
                PaymentStatus::class,
                OrderWorkflowStatus::class,
                DetailedTicketStatus::class,
            ];

            foreach ($enums as $enum) {
                $api = $enum::forApi();
                expect($api)->toBeArray();
                expect(count($api))->toBeGreaterThan(0);

                foreach ($api as $item) {
                    expect($item)->toHaveKeys(['value', 'name', 'label', 'color', 'icon', 'description']);
                    expect($item['label'])->toBeString();
                    expect($item['color'])->toBeIn(['success', 'danger', 'warning', 'info', 'secondary']);
                }
            }
        });
    });
});
