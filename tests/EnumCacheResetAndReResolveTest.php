<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

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
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

/**
 * Enum cache reset and re-resolve integration test.
 *
 * Verifies that:
 * - Cache invalidation works correctly across resolve() calls
 * - Resetting the singleton instance clears all cached data
 * - Metadata is rebuilt from scratch after invalidation
 * - TTL-based expiration triggers re-resolution
 * - Multiple enum classes maintain independent cache entries
 */
describe('EnumCacheResetAndReResolve', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('re-resolves metadata after cache invalidation', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        // First resolve — populates cache
        $meta1 = EnumMetadataResolver::resolve(EnumCacheResetFixture::class);

        // Invalidate the specific class
        EnumMetadataResolver::invalidate(EnumCacheResetFixture::class);

        // Second resolve — should rebuild from scratch
        $meta2 = EnumMetadataResolver::resolve(EnumCacheResetFixture::class);

        expect($meta1)->toBe($meta2);
    });

    it('invalidates all cached metadata via invalidateAll', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        EnumMetadataResolver::resolve(EnumCacheResetFixture::class);
        EnumMetadataResolver::resolve(EnumCacheResetSecondFixture::class);

        expect($cache->has(EnumCacheResetFixture::class))->toBeTrue();
        expect($cache->has(EnumCacheResetSecondFixture::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect($cache->has(EnumCacheResetFixture::class))->toBeFalse();
        expect($cache->has(EnumCacheResetSecondFixture::class))->toBeFalse();
    });

    it('maintains independent cache entries for multiple enum classes', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $meta1 = EnumMetadataResolver::resolve(EnumCacheResetFixture::class);
        $meta2 = EnumMetadataResolver::resolve(EnumCacheResetSecondFixture::class);

        // Labels should be different between the two enums
        expect($meta1['labels'])->not->toBe($meta2['labels']);

        // Invalidate only the first
        EnumMetadataResolver::invalidate(EnumCacheResetFixture::class);

        // Second should still be cached
        expect($cache->has(EnumCacheResetSecondFixture::class))->toBeTrue();

        // First should be re-resolved
        $meta1Rebuilt = EnumMetadataResolver::resolve(EnumCacheResetFixture::class);
        expect($meta1Rebuilt)->toBe($meta1);
    });

    it('respects TTL-based expiration', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        // TTL of 0 means caching is disabled — entries are always stale
        EnumMetadataResolver::resolve(EnumCacheResetFixture::class);
        expect($cache->has(EnumCacheResetFixture::class))->toBeFalse();

        // Set a positive TTL
        $cache->setTtl(300);
        EnumMetadataResolver::resolve(EnumCacheResetFixture::class);
        expect($cache->has(EnumCacheResetFixture::class))->toBeTrue();
    });

    it('singleton reset creates fresh instance', function () {
        $instance1 = EnumCache::getInstance();
        EnumCache::resetInstance();
        $instance2 = EnumCache::getInstance();

        expect($instance1)->not->toBe($instance2);
    });

    it('flush clears all entries on singleton', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        EnumMetadataResolver::resolve(EnumCacheResetFixture::class);
        EnumMetadataResolver::resolve(EnumCacheResetSecondFixture::class);

        EnumCache::flush();

        expect($cache->has(EnumCacheResetFixture::class))->toBeFalse();
        expect($cache->has(EnumCacheResetSecondFixture::class))->toBeFalse();
    });

    it('getTtl and setTtl work correctly', function () {
        $cache = EnumCache::getInstance();

        $cache->setTtl(100);
        expect($cache->getTtl())->toBe(100);

        $cache->setTtl(0);
        expect($cache->getTtl())->toBe(0);

        // Negative values are clamped to 0
        $cache->setTtl(-10);
        expect($cache->getTtl())->toBe(0);
    });

    it('clearClass removes specific entry without affecting others', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        EnumMetadataResolver::resolve(EnumCacheResetFixture::class);
        EnumMetadataResolver::resolve(EnumCacheResetSecondFixture::class);

        $cache->clearClass(EnumCacheResetFixture::class);

        expect($cache->has(EnumCacheResetFixture::class))->toBeFalse();
        expect($cache->has(EnumCacheResetSecondFixture::class))->toBeTrue();
    });
});

#[EnumColor(success: ['alpha'], danger: ['beta'])]
#[EnumLabel(labels: ['alpha' => 'Alpha State', 'beta' => 'Beta State'])]
#[EnumDescription(descriptions: ['alpha' => 'First state'])]
#[EnumIcon(default: 'heroicon-o-flag')]
enum EnumCacheResetFixture: string
{
    use HasEnumMetadata;

    #[Label('Alpha Active')]
    #[Description('Alpha is active')]
    case ALPHA = 'alpha';

    #[Color('danger')]
    case BETA = 'beta';
}

#[EnumColor(info: ['ready', 'done'])]
#[EnumIcon(default: 'heroicon-o-check')]
enum EnumCacheResetSecondFixture: string
{
    use HasEnumMetadata;

    case READY = 'ready';

    #[Icon('heroicon-o-star')]
    case DONE = 'done';
}
