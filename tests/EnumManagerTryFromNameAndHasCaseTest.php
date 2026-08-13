<?php

declare(strict_types=1);

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;

describe('EnumManager tryFromName and hasCase', function () {
    it('tryFromName resolves string-backed enum case by name', function () {
        $manager = new EnumManager;
        $case = $manager->tryFromName(OrderStatus::class, 'PENDING');

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('PENDING');
        expect($case->value)->toBe('pending');
    });

    it('tryFromName resolves int-backed enum case by name', function () {
        $manager = new EnumManager;
        $case = $manager->tryFromName(IntStatusWithColor::class, 'ACTIVE');

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('ACTIVE');
    });

    it('tryFromName returns null for non-existent case name', function () {
        $manager = new EnumManager;
        $case = $manager->tryFromName(OrderStatus::class, 'NON_EXISTENT');

        expect($case)->toBeNull();
    });

    it('tryFromName returns null for empty string', function () {
        $manager = new EnumManager;
        $case = $manager->tryFromName(OrderStatus::class, '');

        expect($case)->toBeNull();
    });

    it('tryFromName works with pure enums', function () {
        $manager = new EnumManager;
        $case = $manager->tryFromName(PureFeatureFlag::class, 'NEW_DASHBOARD');

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('NEW_DASHBOARD');
    });

    it('tryFromName is case-sensitive', function () {
        $manager = new EnumManager;

        expect($manager->tryFromName(OrderStatus::class, 'PENDING'))->not->toBeNull();
        expect($manager->tryFromName(OrderStatus::class, 'pending'))->toBeNull();
        expect($manager->tryFromName(OrderStatus::class, 'Pending'))->toBeNull();
    });

    it('tryFromName returns all known cases by name', function () {
        $manager = new EnumManager;

        foreach (OrderStatus::cases() as $case) {
            $resolved = $manager->tryFromName(OrderStatus::class, $case->name);
            expect($resolved)->not->toBeNull();
            expect($resolved->name)->toBe($case->name);
        }
    });

    it('tryFromName throws BadMethodCallException for non-enum class', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->tryFromName(\stdClass::class, 'foo'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('hasCase returns true for existing case name', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(OrderStatus::class, 'PENDING'))->toBeTrue();
        expect($manager->hasCase(OrderStatus::class, 'SHIPPED'))->toBeTrue();
        expect($manager->hasCase(OrderStatus::class, 'DELIVERED'))->toBeTrue();
    });

    it('hasCase returns false for non-existent case name', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(OrderStatus::class, 'NON_EXISTENT'))->toBeFalse();
        expect($manager->hasCase(OrderStatus::class, ''))->toBeFalse();
    });

    it('hasCase is case-sensitive', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(OrderStatus::class, 'PENDING'))->toBeTrue();
        expect($manager->hasCase(OrderStatus::class, 'pending'))->toBeFalse();
        expect($manager->hasCase(OrderStatus::class, 'Pending'))->toBeFalse();
    });

    it('hasCase works with int-backed enums', function () {
        $manager = new EnumManager;

        foreach (IntStatusWithColor::cases() as $case) {
            expect($manager->hasCase(IntStatusWithColor::class, $case->name))->toBeTrue();
        }
    });

    it('hasCase works with pure enums', function () {
        $manager = new EnumManager;

        foreach (PureFeatureFlag::cases() as $case) {
            expect($manager->hasCase(PureFeatureFlag::class, $case->name))->toBeTrue();
        }
    });

    it('hasCase returns false for all non-case strings', function () {
        $manager = new EnumManager;
        $allNames = array_map(fn (\UnitEnum $c): string => $c->name, OrderStatus::cases());

        $nonNames = ['ORDERED', 'PROCESSING', 'CANCELLED', 'REFUNDED', 'ARCHIVED', 'random'];
        foreach ($nonNames as $name) {
            if (! in_array($name, $allNames, true)) {
                expect($manager->hasCase(OrderStatus::class, $name))->toBeFalse();
            }
        }
    });

    it('hasCase throws BadMethodCallException for non-enum class', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->hasCase(\stdClass::class, 'foo'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('tryFromName and hasCase are consistent for all cases', function () {
        $manager = new EnumManager;

        foreach (OrderStatus::cases() as $case) {
            $name = $case->name;
            expect($manager->hasCase(OrderStatus::class, $name))->toBeTrue();
            $resolved = $manager->tryFromName(OrderStatus::class, $name);
            expect($resolved)->not->toBeNull();
            expect($resolved->name)->toBe($name);
        }
    });

    it('tryFromName and hasCase handle TicketStatus (detailed fixture)', function () {
        $manager = new EnumManager;

        $resolved = $manager->tryFromName(TicketStatus::class, 'OPEN');
        expect($resolved)->not->toBeNull();
        expect($resolved->value)->toBe('open');

        expect($manager->hasCase(TicketStatus::class, 'OPEN'))->toBeTrue();
        expect($manager->hasCase(TicketStatus::class, 'CLOSED'))->toBeTrue();
        expect($manager->hasCase(TicketStatus::class, 'IN_PROGRESS'))->toBeTrue();
        expect($manager->hasCase(TicketStatus::class, 'UNKNOWN'))->toBeFalse();
    });
});
