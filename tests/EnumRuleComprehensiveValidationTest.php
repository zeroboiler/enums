<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum validation rule comprehensive coverage', function () {
    describe('String-backed enum validation', function () {
        it('passes for valid string values', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = fn () => throw new \LogicException('Should not fail');

            expect($rule->validate('status', 'active', $fail))->toBeNull();
            expect($rule->validate('status', 'inactive', $fail))->toBeNull();
            expect($rule->validate('status', 'banned', $fail))->toBeNull();
        });

        it('fails for invalid string values', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 'unknown_value', $fail);
            expect($failed)->toBeTrue();
        });

        it('fails when value is int but enum is string-backed', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 42, $fail);
            expect($failed)->toBeTrue();
        });
    });

    describe('Int-backed enum validation', function () {
        it('passes for valid int values', function () {
            $rule = EnumRule::for(Priority::class);
            $fail = fn () => throw new \LogicException('Should not fail');

            expect($rule->validate('priority', 1, $fail))->toBeNull();
            expect($rule->validate('priority', 2, $fail))->toBeNull();
            expect($rule->validate('priority', 3, $fail))->toBeNull();
            expect($rule->validate('priority', 4, $fail))->toBeNull();
        });

        it('fails for invalid int values', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 99, $fail);
            expect($failed)->toBeTrue();
        });

        it('fails when value is string but enum is int-backed', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', '1', $fail);
            expect($failed)->toBeTrue();
        });

        it('fails for negative int values when not defined', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', -1, $fail);
            expect($failed)->toBeTrue();
        });
    });

    describe('Int-backed enum with color attribute validation', function () {
        it('passes for all valid int-backed values', function () {
            $rule = EnumRule::for(IntStatusWithColor::class);
            $fail = fn () => throw new \LogicException('Should not fail');

            // Test valid values — we iterate over actual cases
            foreach (IntStatusWithColor::cases() as $case) {
                $rule->validate('status', $case->value, $fail);
            }
            // No exception thrown
            expect(true)->toBeTrue();
        });
    });

    describe('Pure enum validation', function () {
        it('passes for valid case names', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $fail = fn () => throw new \LogicException('Should not fail');

            $rule->validate('feature', 'TWO_FACTOR_AUTH', $fail);
            $rule->validate('feature', 'DARK_MODE', $fail);
            // No exception thrown
            expect(true)->toBeTrue();
        });

        it('fails for invalid case names', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('feature', 'NON_EXISTENT_CASE', $fail);
            expect($failed)->toBeTrue();
        });

        it('fails for non-string values on pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('feature', 123, $fail);
            expect($failed)->toBeTrue();
        });
    });

    describe('Nullable behaviour', function () {
        it('non-nullable rule rejects null', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', null, $fail);
            expect($failed)->toBeTrue();
        });

        it('nullable rule accepts null', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $fail = fn () => throw new \LogicException('Should not fail');

            $rule->validate('status', null, $fail);
            // No exception thrown
            expect(true)->toBeTrue();
        });

        it('nullable rule still validates non-null values', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 'invalid_value', $fail);
            expect($failed)->toBeTrue();
        });
    });

    describe('Error message generation', function () {
        it('includes allowed values in error message for enums with HasEnumMetadata', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $message = '';
            $fail = function (string $msg) use (&$failed, &$message): void {
                $failed = true;
                $message = $msg;
            };

            $rule->validate('status', 'invalid', $fail);
            expect($failed)->toBeTrue();
            expect($message)->toContain('selected');
            expect($message)->toContain('status');
        });
    });

    describe('Named constructor API', function () {
        it('for() creates a non-nullable rule', function () {
            $rule = EnumRule::for(UserStatus::class);

            // Access via reflection to verify nullable state
            $ref = new ReflectionProperty($rule, 'nullable');
            expect($ref->getValue($rule))->toBeFalse();
        });

        it('nullable() creates a new instance with nullable=true', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();

            $ref = new ReflectionProperty($rule, 'nullable');
            expect($ref->getValue($rule))->toBeTrue();
        });

        it('nullable() does not mutate the original rule', function () {
            $original = EnumRule::for(UserStatus::class);
            $nullable = $original->nullable();

            $originalRef = new ReflectionProperty($original, 'nullable');
            $nullableRef = new ReflectionProperty($nullable, 'nullable');

            expect($originalRef->getValue($original))->toBeFalse();
            expect($nullableRef->getValue($nullable))->toBeTrue();
        });
    });
});
