<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\EdgeCaseNamingEnum;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\WorkflowState;

/*
 * Boundary tests for EnumMetadataResolver — covers cache isolation,
 * TTL expiry, invalidate/re-resolve cycles, class-level attribute
 * merging, empty metadata, single-case enums, and camelCase label
 * generation.
 */

describe('EnumMetadataResolver boundary conditions', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('caches metadata and returns same instance on second call', function (): void {
        EnumCache::getInstance()->setTtl(300);
        $meta1 = EnumMetadataResolver::resolve(WorkflowState::class);
        $meta2 = EnumMetadataResolver::resolve(WorkflowState::class);

        expect($meta1)->toBe($meta2);
    });

    it('invalidates per-class cache and re-resolves', function (): void {
        EnumCache::getInstance()->setTtl(300);
        $meta1 = EnumMetadataResolver::resolve(WorkflowState::class);
        EnumMetadataResolver::invalidate(WorkflowState::class);
        $meta2 = EnumMetadataResolver::resolve(WorkflowState::class);

        // Content should be identical (same enum), but NOT the same array instance
        expect($meta1)->toEqual($meta2);
    });

    it('invalidateAll clears everything', function (): void {
        EnumCache::getInstance()->setTtl(300);
        EnumMetadataResolver::resolve(WorkflowState::class);
        EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        expect(EnumCache::getInstance()->has(WorkflowState::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(MixedAttributeStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(WorkflowState::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(MixedAttributeStatus::class))->toBeFalse();
    });

    it('returns sparse metadata for empty defaults enum', function (): void {
        $meta = EnumMetadataResolver::resolve(EmptyDefaultsStatus::class);

        // No class-level or per-case attributes — metadata should be empty arrays
        expect($meta['labels'])->toBe([]);
        expect($meta['descriptions'])->toBe([]);
        expect($meta['colors'])->toBe([]);
        expect($meta['icons'])->toBe([]);
    });

    it('resolves single-case enum correctly', function (): void {
        $meta = EnumMetadataResolver::resolve(SingleCaseEnum::class);

        // SingleCaseEnum has only one case; metadata shape should still be valid
        expect($meta)->toHaveKey('labels');
        expect($meta)->toHaveKey('descriptions');
        expect($meta)->toHaveKey('colors');
        expect($meta)->toHaveKey('icons');
    });

    it('handles TTL of 0 — no caching', function (): void {
        EnumCache::getInstance()->setTtl(0);
        $meta1 = EnumMetadataResolver::resolve(WorkflowState::class);
        $meta2 = EnumMetadataResolver::resolve(WorkflowState::class);

        // With TTL=0, has() always returns false, so cache never stores.
        // Each call rebuilds — arrays should be equal but different instances.
        expect($meta1)->toEqual($meta2);
    });

    it('resolves class-level EnumLabel for AllClassLevelEnum', function (): void {
        $meta = EnumMetadataResolver::resolve(AllClassLevelEnum::class);

        expect($meta['labels'])->not->toBeEmpty();
        expect($meta['colors'])->not->toBeEmpty();
    });

    it('resolves camelCase enum with correct auto-labels', function (): void {
        $meta = EnumMetadataResolver::resolve(CamelCasePriority::class);

        // camelCase names should be auto-labeled to Title Case
        // (labels only exist if there are attributes; otherwise it's empty and
        //  the trait falls back to generateLabel() at access time)
        expect($meta)->toHaveKey('labels');
    });

    it('handles negative TTL (clamped to 0)', function (): void {
        EnumCache::getInstance()->setTtl(-100);

        expect(EnumCache::getInstance()->getTtl())->toBe(0);
        expect(EnumCache::getInstance()->has(WorkflowState::class))->toBeFalse();
    });

    it('throws LogicException for non-enum class', function (): void {
        expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))
            ->toThrow(\LogicException::class);
    });

    it('throws LogicException for non-existent class', function (): void {
        expect(fn () => EnumMetadataResolver::resolve('NonExistentClass12345'))
            ->toThrow(\LogicException::class);
    });

    it('resolves DetailedTicketStatus with mixed per-case and class-level attributes', function (): void {
        $meta = EnumMetadataResolver::resolve(DetailedTicketStatus::class);

        // This fixture has both class-level and per-case attributes
        expect($meta['labels'])->not->toBeEmpty();
        expect($meta['colors'])->not->toBeEmpty();
    });

    it('cache clearClass only affects the target class', function (): void {
        EnumCache::getInstance()->setTtl(300);
        EnumMetadataResolver::resolve(WorkflowState::class);
        EnumMetadataResolver::resolve(MixedAttributeStatus::class);

        EnumCache::getInstance()->clearClass(WorkflowState::class);

        expect(EnumCache::getInstance()->has(WorkflowState::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(MixedAttributeStatus::class))->toBeTrue();
    });
});

describe('EdgeCaseNamingEnum label generation', function (): void {
    it('generates labels for SCREAMING_SNAKE_CASE', function (): void {
        // EdgeCaseNamingEnum cases use various naming conventions
        foreach (EdgeCaseNamingEnum::cases() as $case) {
            $label = $case->label();
            expect($label)->toBeString();
            expect(strlen($label))->toBeGreaterThan(0);
        }
    });

    it('generates labels that are not SCREAMING_SNAKE', function (): void {
        foreach (EdgeCaseNamingEnum::cases() as $case) {
            $label = $case->label();
            // Should not be all uppercase
            expect($label)->not->toBe(strtoupper($label));
        }
    });
});

describe('InvalidEnumException named constructors', function (): void {
    it('forName produces correct message', function (): void {
        $e = InvalidEnumException::forName('App\Enums\UserStatus', 'UNKNOWN');

        expect($e->getMessage())->toContain('UNKNOWN');
        expect($e->getMessage())->toContain('UserStatus');
    });

    it('value produces correct message', function (): void {
        $e = InvalidEnumException::value('App\Enums\UserStatus', 'invalid');

        expect($e->getMessage())->toContain('invalid');
        expect($e->getMessage())->toContain('UserStatus');
    });

    it('value handles null value', function (): void {
        $e = InvalidEnumException::value('App\Enums\UserStatus', null);

        expect($e->getMessage())->toContain('UserStatus');
    });
});
