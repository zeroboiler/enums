<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule — int-backed enum validation', function (): void {
    it('passes valid int value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);
        $fail = fn (): mixed => throw new Exception('Should not fail');

        // The rule's validate() returns void (passes) or calls $fail
        $passed = true;
        $rule->validate('priority', 1, function () use (&$passed): void {
            $passed = false;
        });

        expect($passed)->toBeTrue();
    });

    it('fails invalid int value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);
        $failed = false;
        $rule->validate('priority', 99, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('rejects string value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);
        $failed = false;
        $rule->validate('priority', 'HIGH', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('allows null when nullable is set', function (): void {
        $rule = EnumRule::for(Priority::class)->nullable();
        $passed = true;
        $rule->validate('priority', null, function () use (&$passed): void {
            $passed = false;
        });

        expect($passed)->toBeTrue();
    });

    it('rejects null when nullable is not set', function (): void {
        $rule = EnumRule::for(Priority::class);
        $failed = false;
        $rule->validate('priority', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('EnumRule — pure enum validation', function (): void {
    it('passes valid case name for pure enum', function (): void {
        $rule = EnumRule::for(RequestState::class);
        $passed = true;
        $rule->validate('state', 'DRAFT', function () use (&$passed): void {
            $passed = false;
        });

        expect($passed)->toBeTrue();
    });

    it('fails invalid case name for pure enum', function (): void {
        $rule = EnumRule::for(RequestState::class);
        $failed = false;
        $rule->validate('state', 'NONEXISTENT', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('rejects non-string value for pure enum', function (): void {
        $rule = EnumRule::for(RequestState::class);
        $failed = false;
        $rule->validate('state', 123, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('EnumRule — string-backed enum validation', function (): void {
    it('passes valid string value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $passed = true;
        $rule->validate('status', 'active', function () use (&$passed): void {
            $passed = false;
        });

        expect($passed)->toBeTrue();
    });

    it('fails invalid string value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;
        $rule->validate('status', 'unknown_status', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('rejects int value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;
        $rule->validate('status', 42, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('EnumRule — message generation', function (): void {
    it('includes allowed values when enum uses HasEnumMetadata', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $message = '';
        $rule->validate('status', 'invalid', function (string $msg) use (&$message): void {
            $message = $msg;
        });

        expect($message)->toContain('Allowed values:');
        expect($message)->toContain('active');
    });

    it('uses generic message when enum lacks values() method', function (): void {
        // RequestState is a pure enum without HasEnumMetadata → no values() method
        $rule = EnumRule::for(RequestState::class);
        $message = '';
        $rule->validate('state', 'NONEXISTENT', function (string $msg) use (&$message): void {
            $message = $msg;
        });

        expect($message)->toContain('invalid');
        expect($message)->not->toContain('Allowed values:');
    });
});

describe('EnumRule — readonly class', function (): void {
    it('is a readonly class', function (): void {
        $reflection = new ReflectionClass(EnumRule::class);
        expect($reflection->isFinal())->toBeTrue();
    });
});
