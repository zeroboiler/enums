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
});
