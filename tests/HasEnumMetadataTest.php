<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('HasEnumMetadata trait — additional coverage', function (): void {
    describe('values()', function (): void {
        it('returns all values for string-backed enum', function (): void {
            expect(UserStatus::values())->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);
        });

        it('returns all values for int-backed enum', function (): void {
            expect(Priority::values())->toBe([1, 2, 3, 4]);
        });

        it('includes zero value for zero-backed enum', function (): void {
            expect(ZeroPriority::values())->toBe([0, 1, 2]);
        });
    });

    describe('labels()', function (): void {
        it('returns all labels in order', function (): void {
            $labels = UserStatus::labels();

            expect($labels)->toHaveCount(5)
                ->and($labels[0])->toBe('Active User')
                ->and($labels[1])->toBe('Inactive');
        });

        it('returns auto-generated labels for minimal enum', function (): void {
            $labels = OrderStatus::labels();

            expect($labels)->toBe(['Pending', 'Shipped', 'Delivered', 'Cancelled']);
        });
    });

    describe('tryFromLabel()', function (): void {
        it('finds enum by exact label', function (): void {
            expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
        });

        it('finds enum by auto-generated label', function (): void {
            expect(OrderStatus::tryFromLabel('Pending'))->toBe(OrderStatus::PENDING);
            expect(OrderStatus::tryFromLabel('Delivered'))->toBe(OrderStatus::DELIVERED);
        });

        it('returns null for non-existent label', function (): void {
            expect(UserStatus::tryFromLabel('Unknown Status'))->toBeNull();
        });

        it('is case-insensitive', function (): void {
            expect(UserStatus::tryFromLabel('INACTIVE'))->toBe(UserStatus::INACTIVE);
            expect(OrderStatus::tryFromLabel('shipped'))->toBe(OrderStatus::SHIPPED);
        });
    });

    describe('forSelect()', function (): void {
        it('returns correct structure for int-backed enums', function (): void {
            $options = Priority::forSelect();

            expect($options)->toHaveCount(4);
            expect($options[0])->toBe(['value' => 1, 'label' => 'Low']);
            expect($options[3])->toBe(['value' => 4, 'label' => 'Urgent']);
        });

        it('returns correct structure for zero-value enums', function (): void {
            $options = ZeroPriority::forSelect();

            expect($options[0]['value'])->toBe(0);
        });
    });

    describe('forApi()', function (): void {
        it('returns complete metadata for each case', function (): void {
            $api = OrderStatus::forApi();

            expect($api)->toHaveCount(4);
            expect($api[0])->toEqual([
                'value' => 'pending',
                'name' => 'PENDING',
                'label' => 'Pending',
                'description' => null,
                'color' => 'secondary',
                'icon' => null,
            ]);
        });

        it('returns int values for int-backed enum', function (): void {
            $api = Priority::forApi();

            expect($api[0]['value'])->toBe(1)
                ->and($api[0]['name'])->toBe('LOW');
        });
    });

    describe('generateLabel() — label auto-generation', function (): void {
        it('converts SCREAMING_SNAKE_CASE to Title Case', function (): void {
            expect(OrderStatus::PENDING->label())->toBe('Pending');
            expect(OrderStatus::CANCELLED->label())->toBe('Cancelled');
        });

        it('handles single word case names', function (): void {
            expect(Priority::LOW->label())->toBe('Low');
            expect(Priority::URGENT->label())->toBe('Urgent');
        });
    });

    describe('color() fallback', function (): void {
        it('defaults to secondary when no color attribute', function (): void {
            expect(OrderStatus::PENDING->color())->toBe('secondary');
            expect(Priority::LOW->color())->toBe('secondary');
        });
    });

    describe('icon() fallback', function (): void {
        it('returns null when no icon attribute', function (): void {
            expect(OrderStatus::DELIVERED->icon())->toBeNull();
            expect(Priority::HIGH->icon())->toBeNull();
        });
    });

    describe('description() fallback', function (): void {
        it('returns null when no description attribute', function (): void {
            expect(OrderStatus::SHIPPED->description())->toBeNull();
            expect(Priority::LOW->description())->toBeNull();
        });
    });
});
