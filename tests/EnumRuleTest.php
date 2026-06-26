<?php

declare(strict_types=1);

use NovaForge\Enums\Rules\EnumRule;
use NovaForge\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule', function () {
    it('passes for valid enum value', function () {
        $rule    = EnumRule::for(UserStatus::class);
        $failed  = false;

        $rule->validate('status', 'active', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails for invalid enum value', function () {
        $rule    = EnumRule::for(UserStatus::class);
        $failed  = false;

        $rule->validate('status', 'unknown', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails for non-string/non-int value', function () {
        $rule    = EnumRule::for(UserStatus::class);
        $failed  = false;

        $rule->validate('status', ['array'], function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('includes allowed values in error message', function () {
        $rule    = EnumRule::for(UserStatus::class);
        $message = null;

        $rule->validate('status', 'unknown', function ($msg) use (&$message) {
            $message = $msg;
        });

        expect($message)->toContain('active');
        expect($message)->toContain('banned');
    });
});
