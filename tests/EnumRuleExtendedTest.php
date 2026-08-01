<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule extended coverage', function (): void {
    it('passes null when nullable is enabled', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $failed = false;

        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails on null when nullable is disabled', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('nullable() creates a new instance without mutating original', function (): void {
        $base = EnumRule::for(UserStatus::class);
        $nullable = $base->nullable();

        expect($base)->not->toBe($nullable);
    });

    it('validates pure (non-backed) enum by case name', function (): void {
        $rule = EnumRule::for(RequestState::class);
        $failed = false;

        $rule->validate('state', 'APPROVED', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails for invalid pure enum case name', function (): void {
        $rule = EnumRule::for(RequestState::class);
        $failed = false;

        $rule->validate('state', 'UNKNOWN', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails for non-string value on pure enum', function (): void {
        $rule = EnumRule::for(RequestState::class);
        $failed = false;

        $rule->validate('state', 42, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails when enum class is neither UnitEnum nor BackedEnum subclass', function (): void {
        $rule = EnumRule::for(stdClass::class);
        $message = null;

        $rule->validate('field', 'test', function ($msg) use (&$message): void {
            $message = $msg;
        });

        expect($message)->toContain('must be a valid enum');
    });

    it('passes valid int value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);
        $failed = false;

        $rule->validate('priority', 1, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails for invalid int value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);
        $failed = false;

        $rule->validate('priority', 999, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});
