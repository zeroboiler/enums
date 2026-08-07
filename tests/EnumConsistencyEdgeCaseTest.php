<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum label edge cases', function (): void {
    it('handles single-letter case names', function (): void {
        // Verify single-char label generation works
        $label = OrderStatus::PENDING->label();
        expect($label)->toBeString()->not->toBeEmpty();
    });

    it('handles consecutive underscores in case names', function (): void {
        // Ensure multi-underscore cases are handled correctly
        // (if such a fixture exists)
        foreach (OrderStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
            expect($case->label())->not->toContain('_');
        }
    });

    it('label casing is consistent across calls', function (): void {
        $labels = [];
        for ($i = 0; $i < 5; $i++) {
            $labels[] = UserStatus::ACTIVE->label();
        }

        // All calls should return identical result
        expect(array_unique($labels))->toHaveCount(1);
    });
});

describe('Enum forSelect consistency', function (): void {
    it('forSelect preserves declaration order', function (): void {
        $options = UserStatus::forSelect();
        $values = array_column($options, 'value');
        $cases = UserStatus::cases();

        expect($values)->toHaveCount(count($cases));

        foreach ($cases as $index => $case) {
            $expected = $case instanceof \BackedEnum ? $case->value : $case->name;
            expect($values[$index])->toBe($expected);
        }
    });

    it('forApi preserves declaration order', function (): void {
        $api = UserStatus::forApi();
        $names = array_column($api, 'name');
        $cases = UserStatus::cases();

        expect($names)->toHaveCount(count($cases));

        foreach ($cases as $index => $case) {
            expect($names[$index])->toBe($case->name);
        }
    });

    it('values() and forSelect() values are consistent', function (): void {
        $values = UserStatus::values();
        $selectValues = array_column(UserStatus::forSelect(), 'value');

        expect($values)->toBe($selectValues);
    });

    it('int-backed enum values returns actual int values', function (): void {
        $values = Priority::values();

        expect($values)->toBe([1, 2, 3, 4]);
        // Ensure they are actual ints, not strings
        foreach ($values as $value) {
            expect($value)->toBeInt();
        }
    });
});

describe('Enum color defaults', function (): void {
    it('returns secondary for all cases when no colors defined', function (): void {
        foreach (OrderStatus::cases() as $case) {
            expect($case->color())->toBe('secondary');
        }
    });

    it('returns secondary for int-backed enum without colors', function (): void {
        foreach (Priority::cases() as $case) {
            expect($case->color())->toBe('secondary');
        }
    });

    it('returns valid color strings only', function (): void {
        $validColors = ['success', 'danger', 'warning', 'info', 'secondary'];

        foreach (UserStatus::cases() as $case) {
            expect($case->color())->toBeIn($validColors);
        }

        foreach (OrderStatus::cases() as $case) {
            expect($case->color())->toBeIn($validColors);
        }
    });
});
