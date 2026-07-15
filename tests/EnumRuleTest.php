<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule', function (): void {
    it('passes for valid enum value', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', 'active', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails for invalid enum value', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', 'unknown', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails for non-string/non-int value', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', ['array'], function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('includes allowed values in error message', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $message = null;

        $rule->validate('status', 'unknown', function ($msg) use (&$message): void {
            $message = $msg;
        });

        expect($message)->toContain('active');
        expect($message)->toContain('banned');
    });

    it('throws InvalidArgumentException for non-existent class', function (): void {
        expect(fn (): EnumRule => new EnumRule('App\\NonExistent\\FakeEnum'))
            ->toThrow(InvalidArgumentException::class, 'does not exist');
    });

    it('throws InvalidArgumentException for non-enum class', function (): void {
        expect(fn (): EnumRule => new EnumRule(stdClass::class))
            ->toThrow(InvalidArgumentException::class, 'does not exist or is not an enum');
    });

    it('throws via for() factory for invalid class', function (): void {
        expect(fn (): EnumRule => EnumRule::for('App\\Fake'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('accepts BackedEnum classes in constructor', function (): void {
        $rule = new EnumRule(UserStatus::class);

        expect($rule)->toBeInstanceOf(EnumRule::class);
    });
});
