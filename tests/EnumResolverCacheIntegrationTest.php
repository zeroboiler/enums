<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('EnumMetadataResolver — invalidate/invalidateAll integration', function (): void {
    it('invalidates a single class and rebuilds on next resolve', function (): void {
        EnumMetadataResolver::invalidate(OrderStatus::class);

        // Cache should be empty for this class
        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeFalse();

        // Resolve again — should rebuild
        $meta = EnumMetadataResolver::resolve(OrderStatus::class);
        expect($meta)->toBeArray()
            ->and($meta)->toHaveKey('labels')
            ->and($meta['labels'])->not->toBeEmpty();

        // Now cache should exist
        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeTrue();
    });

    it('invalidateAll clears all cached classes', function (): void {
        // Resolve two different enums to populate cache
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
    });

    it('resolve returns consistent metadata across multiple calls', function (): void {
        $meta1 = EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        $meta2 = EnumMetadataResolver::resolve(MixedAttributeStatus::class);

        expect($meta1)->toBe($meta2);
    });
});

describe('EnumCache — clearClass edge cases', function (): void {
    it('clearClass removes only the specified class', function (): void {
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumCache::getInstance()->clearClass(OrderStatus::class);

        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeFalse()
            ->and(EnumCache::getInstance()->has(Priority::class))->toBeTrue();
    });

    it('clearClass on non-cached class is a no-op', function (): void {
        EnumCache::getInstance()->clearClass('NonExistentEnum');

        // Should not throw
        expect(EnumCache::getInstance()->has('NonExistentEnum'))->toBeFalse();
    });

    it('get throws OutOfBoundsException when class is not cached', function (): void {
        EnumCache::getInstance()->clearClass(OrderStatus::class);

        expect(fn () => EnumCache::getInstance()->get(OrderStatus::class))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('setTtl with negative value normalizes to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);

        // Restore default
        $cache->setTtl(300);
    });

    it('TTL of 0 makes has() always return false', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        EnumMetadataResolver::resolve(OrderStatus::class);

        // Even after resolve, TTL 0 means has() returns false
        expect($cache->has(OrderStatus::class))->toBeFalse();

        // Restore default
        $cache->setTtl(300);
    });
});

describe('InvalidEnumException — factory methods', function (): void {
    it('value() formats null value as "null"', function (): void {
        $exception = InvalidEnumException::value('App\\Enums\\Status', null);

        expect($exception->getMessage())->toContain('null')
            ->and($exception->getMessage())->toContain('App\\Enums\\Status');
    });

    it('value() formats int value correctly', function (): void {
        $exception = InvalidEnumException::value('App\\Enums\\Priority', 99);

        expect($exception->getMessage())->toContain('99')
            ->and($exception->getMessage())->toContain('App\\Enums\\Priority');
    });

    it('value() formats string value correctly', function (): void {
        $exception = InvalidEnumException::value('App\\Enums\\Status', 'unknown');

        expect($exception->getMessage())->toContain('unknown')
            ->and($exception->getMessage())->toContain('App\\Enums\\Status');
    });

    it('forName() formats message correctly', function (): void {
        $exception = InvalidEnumException::forName('App\\Enums\\Status', 'NONEXISTENT');

        expect($exception->getMessage())->toContain('NONEXISTENT')
            ->and($exception->getMessage())->toContain('App\\Enums\\Status');
    });
});

describe('EnumRule — nullable and type edge cases', function (): void {
    it('nullable rule passes for null value', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(OrderStatus::class)->nullable();
        $failed = false;

        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('non-nullable rule fails for null value', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(OrderStatus::class);
        $failed = false;

        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('passes for int-backed enum with int value', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(ZeroPriority::class);
        $failed = false;

        $rule->validate('priority', 0, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails for int-backed enum with string value', function (): void {
        $rule = \ZeroBoiler\Enums\EnumRule::for(ZeroPriority::class);
        $failed = false;

        $rule->validate('priority', '0', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails for string-backed enum with int value', function (): void {
        $rule = \ZeroBoiler\Enums\EnumRule::for(OrderStatus::class);
        $failed = false;

        $rule->validate('status', 42, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('passes for pure enum with valid case name string', function (): void {
        $rule = \ZeroBoiler\Enums\EnumRule::for(\ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag::class,
        );
        $failed = false;

        $rule->validate('flag', 'TWO_FACTOR_AUTH', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails for pure enum with invalid case name', function (): void {
        $rule = \ZeroBoiler\Enums\EnumRule::for(
            \ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag::class,
        );
        $failed = false;

        $rule->validate('flag', 'NONEXISTENT', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails for pure enum with non-string value', function (): void {
        $rule = \ZeroBoiler\Enums\EnumRule::for(
            \ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag::class,
        );
        $failed = false;

        $rule->validate('flag', 123, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});
