<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Contract compliance and type-safety tests for EnumMetadataResolver.
 *
 * Verifies that EnumMetadataResolver correctly extracts metadata from:
 * - Label attribute (per-case)
 * - EnumLabel attribute (class-level)
 * - Description attribute (per-case)
 * - EnumDescription attribute (class-level)
 * - Color attribute (per-case)
 * - EnumColor attribute (class-level)
 * - Icon attribute (per-case)
 * - EnumIcon attribute (class-level)
 * - Fallback behavior (defaults for missing metadata)
 */
describe('EnumMetadataResolver', function () {
    it('extracts per-case Label attribute', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['ACTIVE']['label'] ?? null)->toBe('Active User');
    });

    it('extracts per-case Description attribute', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['ACTIVE']['description'] ?? null)->toBe('User can fully access the system');
    });

    it('extracts per-case Color attribute', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // BANNED has explicit #[Color('danger')]
        expect($meta['BANNED']['color'])->toBe('danger');
    });

    it('extracts per-case Icon attribute', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['ACTIVE']['icon'] ?? null)->toBe('heroicon-o-check-circle');
    });

    it('resolves metadata for all cases in a backed string enum', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta)->toHaveKeys(['ACTIVE', 'INACTIVE', 'PENDING', 'SUSPENDED', 'BANNED']);
    });

    it('resolves metadata for all cases in a backed int enum', function () {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        expect($meta)->toHaveKeys(['LOW', 'MEDIUM', 'HIGH', 'URGENT']);
    });

    it('returns consistent structure for each case', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        foreach ($meta as $case => $data) {
            expect($data)->toBeArray();
            expect($data)->toHaveKeys(['label', 'description', 'color', 'icon']);
        }
    });

    it('falls back to secondary color when not specified', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // INACTIVE has no per-case Color, should default to 'secondary'
        expect($meta['INACTIVE']['color'])->toBe('secondary');
    });

    it('uses class-level EnumColor for unspecified cases', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // PENDING has EnumColor(warning: ['pending']), so should be 'warning'
        expect($meta['PENDING']['color'])->toBe('warning');
    });

    it('handles pure enum cases without backed values', function () {
        $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        expect($meta)->toBeArray();
        expect($meta)->not->toBeEmpty();
    });

    it('returns empty array for unknown attribute keys', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        foreach ($meta as $case => $data) {
            $unknownKeys = array_diff(array_keys($data), ['label', 'description', 'color', 'icon']);
            expect($unknownKeys)->toBeEmpty();
        }
    });

    it('returns null label for cases without Label or EnumLabel', function () {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        // Priority has no Label attributes at all
        expect($meta['LOW']['label'])->toBeNull();
    });

    it('returns null description for cases without Description', function () {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        expect($meta['LOW']['description'])->toBeNull();
    });

    it('per-case Label overrides class-level EnumLabel', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // ACTIVE has per-case #[Label('Active User')]
        expect($meta['ACTIVE']['label'])->toBe('Active User');
    });

    it('pure enum cases use case names as keys', function () {
        $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        expect($meta)->toHaveKey('DARK_MODE');
        expect($meta)->toHaveKey('BETA_FEATURES');
        expect($meta)->toHaveKey('MAINTENANCE_MODE');
    });
});
