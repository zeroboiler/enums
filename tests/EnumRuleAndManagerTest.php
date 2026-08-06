<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

beforeEach(function (): void {
    EnumCache::flush();
});

describe('EnumRule — validation for all enum types', function (): void {
    it('validates string-backed enum values', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(OrderStatus::class);
        $fail = fn (): mixed => throw new \InvalidArgumentException('should not fail');

        // Valid values should not trigger $fail
        $rule->validate('status', 'pending', $fail);
        $rule->validate('status', 'shipped', $fail);
    });

    it('rejects invalid string-backed enum value', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(OrderStatus::class);
        $failed = false;

        $rule->validate('status', 'nonexistent', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('validates int-backed enum values', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(Priority::class);
        $fail = fn (): mixed => throw new \InvalidArgumentException('should not fail');

        $rule->validate('priority', 1, $fail);
        $rule->validate('priority', 4, $fail);
    });

    it('rejects invalid int-backed enum value', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(Priority::class);
        $failed = false;

        $rule->validate('priority', 99, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('validates pure enum by case name', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(RequestState::class);
        $fail = fn (): mixed => throw new \InvalidArgumentException('should not fail');

        $rule->validate('state', 'DRAFT', $fail);
        $rule->validate('state', 'APPROVED', $fail);
    });

    it('rejects invalid pure enum case name', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(RequestState::class);
        $failed = false;

        $rule->validate('state', 'NONEXISTENT', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('allows null when nullable is set', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(OrderStatus::class)->nullable();
        $failed = false;

        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects null when nullable is not set', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(OrderStatus::class);
        $failed = false;

        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('rejects wrong type for string-backed enum', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(OrderStatus::class);
        $failed = false;

        $rule->validate('status', 123, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('generates error message with allowed values', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(OrderStatus::class);
        $failed = false;
        $message = '';

        $rule->validate('status', 'invalid', function (string $msg) use (&$failed, &$message): void {
            $failed = true;
            $message = $msg;
        });

        expect($failed)->toBeTrue();
        expect($message)->toContain('status');
        expect($message)->toContain('pending');
    });
});

describe('EnumManager — runtime access', function (): void {
    it('generates select options', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $options = $manager->forSelect(UserStatus::class);

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('generates API metadata', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $api = $manager->forApi(UserStatus::class);

        expect($api)->toBeArray();
        expect($api)->not->toBeEmpty();
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('resolves by label', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $case = $manager->tryFromLabel(UserStatus::class, 'Active User');

        expect($case)->toBe(UserStatus::ACTIVE);
    });

    it('returns null for unknown label', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $case = $manager->tryFromLabel(UserStatus::class, 'Nonexistent');

        expect($case)->toBeNull();
    });

    it('throws for non-metadata enum', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        // Using a standard PHP enum without HasEnumMetadata should throw
        expect(fn (): mixed => $manager->forSelect(\SomeStandardEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });
});

describe('EnumCache — TTL behavior', function (): void {
    it('flushes all metadata', function (): void {
        EnumCache::flush();

        // Access metadata to populate cache
        OrderStatus::ACTIVE->label();

        // Flush
        EnumCache::flush();

        // Re-access should work fine after flush
        expect(OrderStatus::ACTIVE->label())->toBe('Pending');
    });

    it('resets singleton instance', function (): void {
        EnumCache::flush();
        EnumCache::resetInstance();

        // After reset, getInstance should return a fresh instance
        $cache = EnumCache::getInstance();
        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('respects TTL of zero (always fresh)', function (): void {
        EnumCache::flush();
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        // TTL of 0 means has() always returns false
        expect($cache->has(OrderStatus::class))->toBeFalse();
    });
});

/**
 * Standard PHP enum WITHOUT HasEnumMetadata — used to test EnumManager rejection.
 */
enum SomeStandardEnum: string
{
    case FOO = 'foo';
    case BAR = 'bar';
}
