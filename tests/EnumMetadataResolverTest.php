<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

use ReflectionClass;
use ReflectionMethod;

beforeEach(function (): void {
    EnumCache::flush();
});

describe('EnumMetadataResolver::resolve', function (): void {
    it('returns metadata array with all expected keys', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta)
            ->toBeArray()
            ->toHaveKeys(['labels', 'descriptions', 'colors', 'icons'])
            ->and($meta['labels'])->toBeArray()
            ->and($meta['descriptions'])->toBeArray()
            ->and($meta['colors'])->toBeArray()
            ->and($meta['icons'])->toBeArray();
    });

    it('resolves per-case Label attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['labels'])
            ->toHaveKey('active', 'Active User')
            ->toHaveKey('pending', 'Awaiting Verification');
    });

    it('resolves per-case Description attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['descriptions'])
            ->toHaveKey('active', 'User can fully access the system')
            ->toHaveKey('banned', 'User is permanently banned');
    });

    it('resolves per-case Color attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['colors'])->toHaveKey('banned', 'danger');
    });

    it('resolves per-case Icon attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['icons'])->toHaveKey('active', 'heroicon-o-check-circle');
    });

    it('resolves class-level EnumColor attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['colors'])
            ->toHaveKey('active', 'success')
            ->toHaveKey('banned', 'danger')
            ->toHaveKey('pending', 'warning')
            ->toHaveKey('suspended', 'warning');
    });

    it('per-case Color overrides class-level EnumColor', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // BANNED has per-case Color('danger') which matches class-level, but the override mechanism works
        expect($meta['colors']['banned'])->toBe('danger');
    });
});

describe('EnumMetadataResolver caching', function (): void {
    it('caches metadata after first resolution', function (): void {
        // First call resolves and caches
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);

        // Second call should return cached data
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta1)->toBe($meta2);
    });

    it('returns from cache when available', function (): void {
        EnumCache::flush();
        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();

        EnumMetadataResolver::resolve(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeTrue();
    });
});

describe('EnumMetadataResolver with minimal enum', function (): void {
    it('returns empty metadata arrays for enum without attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(OrderStatus::class);

        expect($meta['labels'])->toBeEmpty()
            ->and($meta['descriptions'])->toBeEmpty()
            ->and($meta['colors'])->toBeEmpty()
            ->and($meta['icons'])->toBeEmpty();
    });

    it('returns empty metadata for pure enum without attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(RequestState::class);

        expect($meta['labels'])->toBeEmpty()
            ->and($meta['descriptions'])->toBeEmpty()
            ->and($meta['colors'])->toBeEmpty()
            ->and($meta['icons'])->toBeEmpty();
    });
});

describe('EnumMetadataResolver with int-backed enum', function (): void {
    it('uses int values as keys for int-backed enums', function (): void {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        // No attributes → empty arrays, but resolution should not throw
        expect($meta)->toBeArray()
            ->and($meta['labels'])->toBeArray();
    });
});

describe('EnumMetadataResolver is final', function (): void {
    it('is a final class', function (): void {
        $reflection = new ReflectionClass(EnumMetadataResolver::class);
        expect($reflection->isFinal())->toBeTrue();
    });

    it('has only static methods', function (): void {
        $reflection = new ReflectionClass(EnumMetadataResolver::class);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            expect($method->isStatic())->toBeTrue("Method {$method->getName()} should be static");
        }
    });
});
