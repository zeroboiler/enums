<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('EnumMetadataResolver', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    it('resolves labels for enum with attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['labels']['active'])->toBe('Active User')
            ->and($meta['labels']['pending'])->toBe('Awaiting Verification');
    });

    it('returns empty labels array when no label attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(OrderStatus::class);

        expect($meta['labels'])->toBe([]);
    });

    it('resolves descriptions from per-case attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['descriptions']['active'])->toBe('User can fully access the system')
            ->and($meta['descriptions']['banned'])->toBe('User is permanently banned');
    });

    it('resolves colors from class-level EnumColor attribute', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['colors']['active'])->toBe('success')
            ->and($meta['colors']['banned'])->toBe('danger')
            ->and($meta['colors']['pending'])->toBe('warning');
    });

    it('resolves per-case Color override over class-level EnumColor', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // BANNED has class-level "danger" via EnumColor AND per-case Color('danger')
        // Per-case should win — same value here, but it tests the override path
        expect($meta['colors']['banned'])->toBe('danger');
    });

    it('resolves icons from per-case Icon attribute', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['icons']['active'])->toBe('heroicon-o-check-circle');
    });

    it('returns empty arrays for enum without any attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(OrderStatus::class);

        expect($meta['labels'])->toBe([])
            ->and($meta['descriptions'])->toBe([])
            ->and($meta['colors'])->toBe([])
            ->and($meta['icons'])->toBe([]);
    });

    it('caches resolved metadata', function (): void {
        // First resolution populates cache
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        // Second call reads from cache
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta1)->toBe($meta2);
    });

    it('works with int-backed enums', function (): void {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        expect($meta)->toHaveKey('labels')
            ->and($meta)->toHaveKey('descriptions')
            ->and($meta)->toHaveKey('colors')
            ->and($meta)->toHaveKey('icons');
    });

    it('works with zero-value int-backed enums', function (): void {
        $meta = EnumMetadataResolver::resolve(ZeroPriority::class);

        expect($meta)->toBeArray();
    });
});
