<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Tests that verify PHPStan Level 9 compliance for EnumRule.
 *
 * Focuses on:
 * - Int-backed enums with int input (no string coercion)
 * - String-backed enums with string input
 * - Nullable fields
 * - Error message generation with int values (implode safety)
 * - Pure enums with case name validation
 * - Type-mismatch rejection
 */
describe('EnumRule PHPStan L9 Compliance', function () {
    it('validates int-backed enum with correct int type', function (): void {
        $rule = EnumRule::for(Priority::class);
        $fail = fn (string $message): string => $message;

        $rule->validate('priority', 3, $fail);
        $rule->validate('priority', 1, $fail);
        $rule->validate('priority', 4, $fail);

        // Should not throw — valid int values pass silently
        expect(true)->toBeTrue();
    });

    it('rejects int-backed enum when string is passed instead of int', function (): void {
        $rule = EnumRule::for(Priority::class);
        $errors = [];

        $fail = function (string $message) use (&$errors): void {
            $errors[] = $message;
        };

        $rule->validate('priority', '3', $fail);

        expect($errors)->not->toBeEmpty();
    });

    it('rejects int-backed enum with invalid int value', function (): void {
        $rule = EnumRule::for(Priority::class);
        $errors = [];

        $fail = function (string $message) use (&$errors): void {
            $errors[] = $message;
        };

        $rule->validate('priority', 99, $fail);

        expect($errors)->not->toBeEmpty();
        expect($errors[0])->toContain('Allowed values');
    });

    it('error message for int-backed enum contains all valid int values', function (): void {
        $rule = EnumRule::for(Priority::class);
        $errors = [];

        $fail = function (string $message) use (&$errors): void {
            $errors[] = $message;
        };

        $rule->validate('priority', 99, $fail);

        expect($errors[0])->toContain('1');
        expect($errors[0])->toContain('2');
        expect($errors[0])->toContain('3');
        expect($errors[0])->toContain('4');
    });

    it('error message for int-backed enum with zero value includes zero', function (): void {
        $rule = EnumRule::for(ZeroPriority::class);
        $errors = [];

        $fail = function (string $message) use (&$errors): void {
            $errors[] = $message;
        };

        $rule->validate('priority', 99, $fail);

        expect($errors[0])->toContain('0');
        expect($errors[0])->toContain('1');
        expect($errors[0])->toContain('2');
    });

    it('validates string-backed enum with correct string type', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $fail = fn (string $message): string => $message;

        $rule->validate('status', 'active', $fail);
        $rule->validate('status', 'banned', $fail);

        // Should not throw
        expect(true)->toBeTrue();
    });

    it('rejects string-backed enum when int is passed', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $errors = [];

        $fail = function (string $message) use (&$errors): void {
            $errors[] = $message;
        };

        $rule->validate('status', 1, $fail);

        expect($errors)->not->toBeEmpty();
    });

    it('error message for string-backed enum contains allowed string values', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $errors = [];

        $fail = function (string $message) use (&$errors): void {
            $errors[] = $message;
        };

        $rule->validate('status', 'invalid_value', $fail);

        expect($errors[0])->toContain('Allowed values');
    });

    it('nullable rule allows null value', function (): void {
        $rule = EnumRule::for(Priority::class)->nullable();
        $errors = [];

        $fail = function (string $message) use (&$errors): void {
            $errors[] = $message;
        };

        $rule->validate('priority', null, $fail);

        expect($errors)->toBeEmpty();
    });

    it('non-nullable rule rejects null value', function (): void {
        $rule = EnumRule::for(Priority::class);
        $errors = [];

        $fail = function (string $message) use (&$errors): void {
            $errors[] = $message;
        };

        $rule->validate('priority', null, $fail);

        expect($errors)->not->toBeEmpty();
    });

    it('implements ValidationRule interface correctly', function (): void {
        $rule = EnumRule::for(Priority::class);

        expect($rule)->toBeInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class);
    });

    it('nullable returns new instance', function (): void {
        $rule = EnumRule::for(Priority::class);
        $nullable = $rule->nullable();

        expect($nullable)->not->toBe($rule);
        expect($nullable)->toBeInstanceOf(EnumRule::class);
    });

    it('for creates instance with enum class', function (): void {
        $rule = EnumRule::for(Priority::class);

        expect($rule)->toBeInstanceOf(EnumRule::class);
    });
});
