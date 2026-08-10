<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule and Enum Method Interaction', function () {
    describe('EnumRule with int-backed enums', function () {
        it('accepts valid int value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $fail = fn (): string => throw new \LogicException('Should not fail');

            // Should not throw
            $rule->validate('priority', 1, $fail);
            $rule->validate('priority', 3, $fail);
        })->throwsNoExceptions();

        it('rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', '1', $fail);
            expect($failed)->toBeTrue();
        });

        it('rejects invalid int value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 999, $fail);
            expect($failed)->toBeTrue();
        });

        it('rejects float value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 1.5, $fail);
            expect($failed)->toBeTrue();
        });
    });

    describe('EnumRule with pure enums', function () {
        it('accepts valid case name for pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $fail = fn (): string => throw new \LogicException('Should not fail');

            $rule->validate('flag', 'TWO_FACTOR_AUTH', $fail);
            $rule->validate('flag', 'DARK_MODE', $fail);
        })->throwsNoExceptions();

        it('rejects invalid case name for pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('flag', 'NON_EXISTENT', $fail);
            expect($failed)->toBeTrue();
        });

        it('rejects non-string value for pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('flag', 123, $fail);
            expect($failed)->toBeTrue();
        });
    });

    describe('EnumRule nullable', function () {
        it('allows null when nullable is true', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $fail = fn (): string => throw new \LogicException('Should not fail');

            $rule->validate('status', null, $fail);
        })->throwsNoExceptions();

        it('rejects null when nullable is false', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', null, $fail);
            expect($failed)->toBeTrue();
        });
    });

    describe('EnumRule error message includes allowed values', function () {
        it('includes backed values in error message for string enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $message = '';
            $fail = function (string $msg) use (&$message): void {
                $message = $msg;
            };

            $rule->validate('status', 'nonexistent', $fail);

            expect($message)->toContain('active');
            expect($message)->toContain('banned');
            expect($message)->toContain('inactive');
            expect($message)->toContain('selected');
        });

        it('includes backed values in error message for int enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $message = '';
            $fail = function (string $msg) use (&$message): void {
                $message = $msg;
            };

            $rule->validate('priority', 999, $fail);

            expect($message)->toContain('Allowed values');
        });
    });

    describe('fromName() throws for invalid names', function () {
        it('throws InvalidEnumException with class name in message', function () {
            expect(fn () => UserStatus::fromName('NON_EXISTENT'))
                ->toThrow(InvalidEnumException::class, function (InvalidEnumException $e) {
                    return str_contains($e->getMessage(), 'UserStatus')
                        && str_contains($e->getMessage(), 'NON_EXISTENT');
                });
        });

        it('InvalidEnumException::value() handles null value', function () {
            $e = InvalidEnumException::value(UserStatus::class, null);
            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain('UserStatus');
        });

        it('InvalidEnumException::value() handles string value', function () {
            $e = InvalidEnumException::value(UserStatus::class, 'invalid');
            expect($e->getMessage())->toContain('invalid');
            expect($e->getMessage())->toContain('UserStatus');
        });

        it('InvalidEnumException::value() handles int value', function () {
            $e = InvalidEnumException::value(IntBackedPriority::class, 999);
            expect($e->getMessage())->toContain('999');
            expect($e->getMessage())->toContain('IntBackedPriority');
        });
    });

    describe('EnumRule with non-existent class', function () {
        it('fails validation with generic message', function () {
            $rule = EnumRule::for('NonExistentClass');
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
                expect($message)->toContain('valid enum');
            };

            $rule->validate('field', 'value', $fail);
            expect($failed)->toBeTrue();
        });
    });
});
