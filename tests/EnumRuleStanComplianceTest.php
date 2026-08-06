<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule PHPStan Level 9 Compliance', function () {
    it('rejects int value for string-backed enum', function () {
        $rule = EnumRule::for(UserStatus::class);

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        $rule->validate('status', 42, $fail);

        expect($violations)->toBe(1);
    });

    it('rejects string value for int-backed enum', function () {
        $rule = EnumRule::for(Priority::class);

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        $rule->validate('priority', 'high', $fail);

        expect($violations)->toBe(1);
    });

    it('accepts null when nullable is enabled', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        $rule->validate('status', null, $fail);

        expect($violations)->toBe(0);
    });

    it('rejects null when nullable is disabled (default)', function () {
        $rule = EnumRule::for(UserStatus::class);

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        $rule->validate('status', null, $fail);

        expect($violations)->toBe(1);
    });

    it('accepts valid string-backed value', function () {
        $rule = EnumRule::for(UserStatus::class);

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        $rule->validate('status', 'active', $fail);

        expect($violations)->toBe(0);
    });

    it('accepts valid int-backed value', function () {
        $rule = EnumRule::for(Priority::class);

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        $rule->validate('priority', 1, $fail);

        expect($violations)->toBe(0);
    });

    it('rejects invalid backed value', function () {
        $rule = EnumRule::for(UserStatus::class);

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        $rule->validate('status', 'nonexistent', $fail);

        expect($violations)->toBe(1);
    });

    it('includes allowed values in error message for enums with HasEnumMetadata', function () {
        $rule = EnumRule::for(UserStatus::class);

        $message = '';
        $fail = static function (string $m) use (&$message): void {
            $message = $m;
        };

        $rule->validate('status', 'invalid', $fail);

        expect($message)->toContain('Allowed values');
        expect($message)->toContain('active');
    });

    it('uses strict null check in tryFrom result', function () {
        // Regression: previously used `instanceof BackedEnum` which is redundant
        // for tryFrom() return (returns null or BackedEnum). Now uses `=== null`.
        $rule = EnumRule::for(Priority::class);

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        // 999 is not a valid Priority value
        $rule->validate('priority', 999, $fail);

        expect($violations)->toBe(1);
    });

    it('accepts valid pure enum case name', function () {
        $rule = EnumRule::for(RequestState::class);

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        $rule->validate('state', 'DRAFT', $fail);

        expect($violations)->toBe(0);
    });

    it('rejects invalid pure enum case name', function () {
        $rule = EnumRule::for(RequestState::class);

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        $rule->validate('state', 'NONEXISTENT', $fail);

        expect($violations)->toBe(1);
    });

    it('rejects non-string value for pure enum', function () {
        $rule = EnumRule::for(RequestState::class);

        $violations = 0;
        $fail = static function (string $message) use (&$violations): void {
            $violations++;
        };

        $rule->validate('state', 123, $fail);

        expect($violations)->toBe(1);
    });
});

describe('EnumCache Singleton Pattern', function () {
    it('returns same instance on multiple getInstance calls', function () {
        $a = \ZeroBoiler\Enums\EnumCache::getInstance();
        $b = \ZeroBoiler\Enums\EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('creates new instance after resetInstance', function () {
        $a = \ZeroBoiler\Enums\EnumCache::getInstance();
        \ZeroBoiler\Enums\EnumCache::resetInstance();
        $b = \ZeroBoiler\Enums\EnumCache::getInstance();

        // Different object identity
        expect($a)->not->toBe($b);
    });

    it('flush clears cache and has returns false', function () {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $cache->set('TestEnum', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestEnum'))->toBeTrue();

        \ZeroBoiler\Enums\EnumCache::flush();

        expect($cache->has('TestEnum'))->toBeFalse();
    });
});
