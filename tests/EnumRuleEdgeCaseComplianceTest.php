<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;

describe('EnumRule edge cases and PHPStan compliance', function () {
    describe('backed enum type safety', function () {
        it('rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(Priority::class);
            $validator = new Validator(
                new Translator('en'),
                ['priority' => 'high'],
                ['priority' => $rule],
            );

            expect($validator->passes())->toBeFalse();
        });

        it('accepts int value for int-backed enum', function () {
            $rule = EnumRule::for(Priority::class);
            $validator = new Validator(
                new Translator('en'),
                ['priority' => 1],
                ['priority' => $rule],
            );

            expect($validator->passes())->toBeTrue();
        });

        it('rejects int value for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $validator = new Validator(
                new Translator('en'),
                ['status' => 123],
                ['status' => $rule],
            );

            expect($validator->passes())->toBeFalse();
        });

        it('accepts string value for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $validator = new Validator(
                new Translator('en'),
                ['status' => 'active'],
                ['status' => $rule],
            );

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('pure enum handling', function () {
        it('accepts valid case name for pure enum', function () {
            $rule = EnumRule::for(TicketStatus::class);
            $validator = new Validator(
                new Translator('en'),
                ['status' => 'OPEN'],
                ['status' => $rule],
            );

            expect($validator->passes())->toBeTrue();
        });

        it('rejects invalid case name for pure enum', function () {
            $rule = EnumRule::for(TicketStatus::class);
            $validator = new Validator(
                new Translator('en'),
                ['status' => 'NONEXISTENT'],
                ['status' => $rule],
            );

            expect($validator->passes())->toBeFalse();
        });

        it('rejects non-string value for pure enum', function () {
            $rule = EnumRule::for(TicketStatus::class);
            $validator = new Validator(
                new Translator('en'),
                ['status' => 123],
                ['status' => $rule],
            );

            expect($validator->passes())->toBeFalse();
        });
    });

    describe('nullable behavior', function () {
        it('rejects null when nullable is false', function () {
            $rule = EnumRule::for(UserStatus::class);
            $validator = new Validator(
                new Translator('en'),
                ['status' => null],
                ['status' => $rule],
            );

            expect($validator->passes())->toBeFalse();
        });

        it('accepts null when nullable is true', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $validator = new Validator(
                new Translator('en'),
                ['status' => null],
                ['status' => $rule],
            );

            expect($validator->passes())->toBeTrue();
        });

        it('validates non-null value even when nullable', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $validator = new Validator(
                new Translator('en'),
                ['status' => 'invalid_value'],
                ['status' => $rule],
            );

            expect($validator->passes())->toBeFalse();
        });
    });

    describe('error messages', function () {
        it('includes allowed values in error message for backed enums', function () {
            $rule = EnumRule::for(UserStatus::class);
            $validator = new Validator(
                new Translator('en'),
                ['status' => 'invalid'],
                ['status' => $rule],
            );

            $validator->passes();
            $errors = $validator->errors()->get('status');

            expect($errors)->not->toBeEmpty();
            expect(implode(' ', $errors))->toContain('Allowed values');
        });

        it('provides generic message for non-HasEnumMetadata enums', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $validator = new Validator(
                new Translator('en'),
                ['status' => 'invalid'],
                ['status' => $rule],
            );

            $validator->passes();
            $errors = $validator->errors()->get('status');

            expect($errors)->not->toBeEmpty();
        });
    });

    describe('named constructor pattern', function () {
        it('creates a non-nullable rule via for()', function () {
            $rule = EnumRule::for(UserStatus::class);

            // Nullable behavior defaults to false
            $validator = new Validator(
                new Translator('en'),
                ['status' => null],
                ['status' => $rule],
            );

            expect($validator->passes())->toBeFalse();
        });

        it('nullable() creates a new instance', function () {
            $original = EnumRule::for(UserStatus::class);
            $nullable = $original->nullable();

            expect($nullable)->not->toBe($original);
        });
    });
});
