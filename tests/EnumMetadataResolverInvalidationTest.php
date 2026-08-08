<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace Tests\Enums;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

enum CacheTestStatus: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

/**
 * @covers \ZeroBoiler\Enums\Support\EnumMetadataResolver
 * @covers \ZeroBoiler\Enums\EnumCache
 *
 * Tests for EnumMetadataResolver::invalidate(), invalidateAll(),
 * and EnumCache::getTtl().
 */
final class EnumMetadataResolverInvalidationTest extends TestCase
{
    protected function tearDown(): void
    {
        EnumCache::resetInstance();
    }

    // ── invalidate() ────────────────────────────────────────────

    public function test_invalidate_removes_cached_metadata_for_class(): void
    {
        // Resolve to populate cache
        $first = EnumMetadataResolver::resolve(CacheTestStatus::class);

        // Verify cache exists
        $cache = EnumCache::getInstance();
        $this->assertTrue($cache->has(CacheTestStatus::class));

        // Invalidate
        EnumMetadataResolver::invalidate(CacheTestStatus::class);

        // Cache should be gone
        $this->assertFalse($cache->has(CacheTestStatus::class));

        // Re-resolve should rebuild from reflection
        $second = EnumMetadataResolver::resolve(CacheTestStatus::class);
        $this->assertEquals($first, $second);
    }

    public function test_invalidate_does_not_affect_other_classes(): void
    {
        $cache = EnumCache::getInstance();

        // Populate cache for CacheTestStatus
        EnumMetadataResolver::resolve(CacheTestStatus::class);

        // Invalidate a different (non-existent) class
        EnumMetadataResolver::invalidate('NonExistentEnum');

        // CacheTestStatus cache should still exist
        $this->assertTrue($cache->has(CacheTestStatus::class));
    }

    public function test_invalidate_then_resolve_rebuilds_cache(): void
    {
        $cache = EnumCache::getInstance();

        // First resolve
        EnumMetadataResolver::resolve(CacheTestStatus::class);
        $this->assertTrue($cache->has(CacheTestStatus::class));

        // Invalidate
        EnumMetadataResolver::invalidate(CacheTestStatus::class);
        $this->assertFalse($cache->has(CacheTestStatus::class));

        // Re-resolve
        $meta = EnumMetadataResolver::resolve(CacheTestStatus::class);

        // Cache should be rebuilt
        $this->assertTrue($cache->has(CacheTestStatus::class));
        $this->assertArrayHasKey('labels', $meta);
        $this->assertArrayHasKey('colors', $meta);
    }

    // ── invalidateAll() ─────────────────────────────────────────

    public function test_invalidateAll_clears_all_cached_metadata(): void
    {
        $cache = EnumCache::getInstance();

        // Populate cache
        EnumMetadataResolver::resolve(CacheTestStatus::class);
        $this->assertTrue($cache->has(CacheTestStatus::class));

        // Invalidate all
        EnumMetadataResolver::invalidateAll();

        // Cache should be completely empty
        $this->assertFalse($cache->has(CacheTestStatus::class));
    }

    public function test_invalidateAll_allows_re_resolve(): void
    {
        // Resolve
        $meta = EnumMetadataResolver::resolve(CacheTestStatus::class);

        // Clear all
        EnumMetadataResolver::invalidateAll();

        // Re-resolve
        $meta2 = EnumMetadataResolver::resolve(CacheTestStatus::class);

        // Should produce the same result
        $this->assertEquals($meta, $meta2);
    }

    // ── getTtl() ───────────────────────────────────────────────

    public function test_getTtl_returns_default(): void
    {
        $cache = EnumCache::getInstance();
        $this->assertSame(300, $cache->getTtl());
    }

    public function test_getTtl_after_setTtl(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(60);
        $this->assertSame(60, $cache->getTtl());
    }

    public function test_getTtl_with_zero(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $this->assertSame(0, $cache->getTtl());
    }

    public function test_getTtl_normalizes_negative(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-10);
        $this->assertSame(0, $cache->getTtl());
    }

    public function test_getTtl_is_isolated_between_resets(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(42);
        $this->assertSame(42, $cache->getTtl());

        // Reset instance
        EnumCache::resetInstance();

        // New instance should have default TTL
        $newCache = EnumCache::getInstance();
        $this->assertSame(300, $newCache->getTtl());
    }
}
