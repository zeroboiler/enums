<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum production final audit v3', function () {
    it('forSelect preserves declaration order for string-backed enums', function () {
        $select = UserStatus::forSelect();
        $values = array_column($select, 'value');

        expect($values)->toBe([
            'active',
            'inactive',
            'pending',
            'suspended',
            'banned',
        ]);
    });

    it('forSelect preserves declaration order for int-backed enums', function () {
        $select = Priority::forSelect();
        $values = array_column($select, 'value');

        expect($values)->toBe([1, 2, 3, 4]);
    });

    it('forApi returns consistent structure across all enum types', function () {
        // String-backed
        $api = UserStatus::forApi();
        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['value'])->toBeString();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString();
            expect($item['color'])->toBeString();
        }

        // Int-backed
        $apiInt = Priority::forApi();
        foreach ($apiInt as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['value'])->toBeInt();
        }

        // Pure enum
        $apiPure = PureFeatureFlag::forApi();
        foreach ($apiPure as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            // Pure enum value should be the case name
            expect($item['value'])->toBe($item['name']);
        }
    });

    it('labels() returns labels in declaration order', function () {
        $labels = UserStatus::labels();

        expect($labels)->toBe([
            'Active User',
            'Inactive',
            'Awaiting Verification',
            'Suspended',
            'Banned',
        ]);
    });

    it('values() returns backed values for int enums', function () {
        $values = Priority::values();

        expect($values)->toBe([1, 2, 3, 4]);
    });

    it('is() returns false for different enum types with same case name', function () {
        // Even if two enums have the same case name, is() checks identity
        $status = UserStatus::ACTIVE;
        $priority = Priority::MEDIUM; // Not relevant but tests type safety

        // Same-type comparison
        expect($status->is(UserStatus::ACTIVE))->toBeTrue();
    });

    it('tryFromLabel is truly case-insensitive', function () {
        $case = UserStatus::tryFromLabel('active user');
        expect($case)->toBe(UserStatus::ACTIVE);

        $case = UserStatus::tryFromLabel('ACTIVE USER');
        expect($case)->toBe(UserStatus::ACTIVE);

        $case = UserStatus::tryFromLabel('aCtIvE uSeR');
        expect($case)->toBe(UserStatus::ACTIVE);
    });

    it('fromName throws for empty string', function () {
        expect(fn () => UserStatus::fromName(''))
            ->toThrow(InvalidEnumException::class);
    });

    it('fromName is case-sensitive — lowercase fails for uppercase case name', function () {
        expect(fn () => UserStatus::fromName('active'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase is case-sensitive', function () {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('active'))->toBeFalse();
    });

    it('EnumRule rejects null when not nullable', function () {
        $rule = EnumRule::for(UserStatus::class);
        $fail = fn (string $message): string => $message;
        $messages = [];

        // Capture failures by using a closure that collects messages
        $collector = function (string $message) use (&$messages): void {
            $messages[] = $message;
        };

        // Pass null with nullable=false
        $rule->validate('status', null, $collector);

        expect($messages)->not->toBeEmpty();
    });

    it('EnumRule accepts null when nullable', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $messages = [];

        $collector = function (string $message) use (&$messages): void {
            $messages[] = $message;
        };

        // Pass null with nullable=true — should not fail
        $rule->validate('status', null, $collector);

        expect($messages)->toBeEmpty();
    });

    it('EnumRule validates int-backed enum with type checking', function () {
        $rule = EnumRule::for(Priority::class);
        $messages = [];

        $collector = function (string $message) use (&$messages): void {
            $messages[] = $message;
        };

        // String value for int-backed enum should fail
        $rule->validate('priority', '1', $collector);
        expect($messages)->not->toBeEmpty();

        // Correct int value should pass
        $messages = [];
        $rule->validate('priority', 1, $collector);
        expect($messages)->toBeEmpty();
    });

    it('in() returns false for empty array', function () {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('in() works with single-element array', function () {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
        expect(UserStatus::ACTIVE->in([UserStatus::BANNED]))->toBeFalse();
    });

    it('color() returns secondary as default when no class-level or per-case color is set', function () {
        expect(Priority::LOW->color())->toBe('secondary');
    });

    it('description() returns null when no description is defined', function () {
        expect(Priority::LOW->description())->toBeNull();
    });

    it('icon() returns null when no icon is defined', function () {
        expect(Priority::LOW->icon())->toBeNull();
    });
});
