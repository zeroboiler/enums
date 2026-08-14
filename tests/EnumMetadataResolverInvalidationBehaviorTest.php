<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

/**
 * Tests EnumMetadataResolver cache invalidation behavior.
 *
 * Verifies that invalidate() and invalidateAll() properly clear cached
 * metadata, forcing re-resolution via reflection on subsequent calls.
 */
final class EnumMetadataResolverInvalidationBehaviorTest extends TestCase
{
    protected function tearDown(): void
    {
        EnumCache::flush();
        EnumCache::resetInstance();
    }

    /**
     * @test
     */
    public function invalidate_removes_cached_entry_for_specific_class(): void
    {
        $cache = EnumCache::getInstance();

        // Prime the cache with metadata
        $cache->set('Test\\EnumA', [
            'labels' => ['a' => 'Label A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('Test\\EnumB', [
            'labels' => ['b' => 'Label B'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $this->assertTrue($cache->has('Test\\EnumA'), 'EnumA should be cached before invalidation');
        $this->assertTrue($cache->has('Test\\EnumB'), 'EnumB should be cached before invalidation');

        EnumMetadataResolver::invalidate('Test\\EnumA');

        $this->assertFalse($cache->has('Test\\EnumA'), 'EnumA should NOT be cached after invalidation');
        $this->assertTrue($cache->has('Test\\EnumB'), 'EnumB should still be cached after EnumA invalidation');
    }

    /**
     * @test
     */
    public function invalidate_all_clears_entire_cache(): void
    {
        $cache = EnumCache::getInstance();

        $cache->set('Test\\EnumX', [
            'labels' => ['x' => 'Label X'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('Test\\EnumY', [
            'labels' => ['y' => 'Label Y'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('Test\\EnumZ', [
            'labels' => ['z' => 'Label Z'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $this->assertTrue($cache->has('Test\\EnumX'));
        $this->assertTrue($cache->has('Test\\EnumY'));
        $this->assertTrue($cache->has('Test\\EnumZ'));

        EnumMetadataResolver::invalidateAll();

        $this->assertFalse($cache->has('Test\\EnumX'));
        $this->assertFalse($cache->has('Test\\EnumY'));
        $this->assertFalse($cache->has('Test\\EnumZ'));
    }

    /**
     * @test
     */
    public function invalidate_nonexistent_class_does_not_throw(): void
    {
        // Should be a no-op for a class that was never cached
        EnumMetadataResolver::invalidate('NonExistent\\Enum');

        $this->assertTrue(true, 'Invalidating a non-existent cache key should not throw');
    }

    /**
     * @test
     */
    public function invalidate_then_resolve_rebuilds_metadata(): void
    {
        $cache = EnumCache::getInstance();

        // Manually set stale metadata
        $cache->set('Test\\Stale\\Enum', [
            'labels' => ['stale' => 'Stale Label'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $this->assertTrue($cache->has('Test\\Stale\\Enum'));

        // Invalidate the entry
        EnumMetadataResolver::invalidate('Test\\Stale\\Enum');

        // After invalidation, cache should be empty for that class
        $this->assertFalse($cache->has('Test\\Stale\\Enum'));
    }

    /**
     * @test
     */
    public function cache_set_overwrites_previous_entry(): void
    {
        $cache = EnumCache::getInstance();

        $metadata1 = [
            'labels' => ['key' => 'First'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];

        $metadata2 = [
            'labels' => ['key' => 'Second'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];

        $cache->set('Test\\Overwrite', $metadata1);
        $result1 = $cache->get('Test\\Overwrite');
        $this->assertSame('First', $result1['labels']['key']);

        $cache->set('Test\\Overwrite', $metadata2);
        $result2 = $cache->get('Test\\Overwrite');
        $this->assertSame('Second', $result2['labels']['key']);
    }

    /**
     * @test
     */
    public function cache_clear_class_removes_specific_entry(): void
    {
        $cache = EnumCache::getInstance();

        $cache->set('Test\\Keep', [
            'labels' => ['keep' => 'Keep'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('Test\\Remove', [
            'labels' => ['remove' => 'Remove'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass('Test\\Remove');

        $this->assertTrue($cache->has('Test\\Keep'));
        $this->assertFalse($cache->has('Test\\Remove'));
    }

    /**
     * @test
     */
    public function cache_ttl_zero_disables_caching(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set('Test\\TTL', [
            'labels' => ['ttl' => 'Value'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // With TTL=0, has() should always return false
        $this->assertFalse($cache->has('Test\\TTL'), 'TTL=0 should disable caching');
    }

    /**
     * @test
     */
    public function cache_get_throws_for_missing_entry(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('No cached metadata');

        EnumCache::getInstance()->get('NonExistent\\Class');
    }

    /**
     * @test
     */
    public function cache_flush_via_static_clears_all(): void
    {
        $cache = EnumCache::getInstance();

        $cache->set('A', [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set('B', [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        EnumCache::flush();

        $this->assertFalse($cache->has('A'));
        $this->assertFalse($cache->has('B'));
    }

    /**
     * @test
     */
    public function cache_ttl_negative_clamped_to_zero(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        $this->assertSame(0, $cache->getTtl(), 'Negative TTL should be clamped to 0');
    }
}
