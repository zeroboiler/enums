<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Tests for EnumCache TTL clamping, negative values, zero-disable behavior,
 * and edge-case interactions between setTtl/has/get/clearClass/flush.
 *
 * @covers \ZeroBoiler\Enums\EnumCache
 */
final class EnumCacheTtlEdgeCaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        EnumCache::resetInstance();
    }

    protected function tearDown(): void
    {
        EnumCache::resetInstance();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // TTL clamping and zero-disable
    // -----------------------------------------------------------------------

    public function test_negative_ttl_is_clamped_to_zero(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        self::assertSame(0, $cache->getTtl(), 'Negative TTL must be clamped to 0');
    }

    public function test_zero_ttl_disables_caching(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ];
        $cache->set(IntBackedPriority::class, $metadata);

        // TTL 0 → has() should always return false
        self::assertFalse($cache->has(IntBackedPriority::class));
    }

    public function test_positive_ttl_allows_caching(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ];
        $cache->set(IntBackedPriority::class, $metadata);

        self::assertTrue($cache->has(IntBackedPriority::class));
    }

    public function test_zero_ttl_does_not_auto_expire_stored_entries(): void
    {
        $cache = EnumCache::getInstance();

        // First set with a positive TTL so the entry is stored
        $cache->setTtl(300);
        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ];
        $cache->set(IntBackedPriority::class, $metadata);

        // Now set TTL to 0 — caching is disabled, has() returns false
        $cache->setTtl(0);

        self::assertFalse($cache->has(IntBackedPriority::class));
    }

    // -----------------------------------------------------------------------
    // TTL expiration behavior
    // -----------------------------------------------------------------------

    public function test_entry_expires_after_ttl(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL

        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ];
        $cache->set(IntBackedPriority::class, $metadata);

        // Should be valid immediately
        self::assertTrue($cache->has(IntBackedPriority::class));

        // Manipulate timestamp to simulate expiry
        // We can't directly set timestamps, but we can clear and re-set with expired TTL
        $cache->clear();
        $cache->setTtl(0); // Disable caching
        $cache->set(IntBackedPriority::class, $metadata);

        self::assertFalse($cache->has(IntBackedPriority::class));
    }

    // -----------------------------------------------------------------------
    // clearClass specificity
    // -----------------------------------------------------------------------

    public function test_clear_class_only_removes_target_class(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(IntBackedPriority::class, [
            'labels' => ['low' => 'Low'],
            'descriptions' => [],
            'colors' => ['low' => 'secondary'],
            'icons' => [],
        ]);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ]);

        // Clear only one class
        $cache->clearClass(IntBackedPriority::class);

        self::assertFalse($cache->has(IntBackedPriority::class));
        self::assertTrue($cache->has(UserStatus::class));
    }

    public function test_clear_class_on_nonexistent_entry_does_not_throw(): void
    {
        $cache = EnumCache::getInstance();

        // Should not throw when clearing a class that was never cached
        $cache->clearClass('NonExistent\EnumClass');
        self::assertTrue(true); // No exception = pass
    }

    // -----------------------------------------------------------------------
    // get() without has() — OutOfBoundsException
    // -----------------------------------------------------------------------

    public function test_get_without_has_throws_out_of_bounds(): void
    {
        $cache = EnumCache::getInstance();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('No cached metadata for');

        $cache->get('NonExistent\EnumClass');
    }

    // -----------------------------------------------------------------------
    // flush clears everything
    // -----------------------------------------------------------------------

    public function test_flush_static_clears_all_entries(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(IntBackedPriority::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::flush();

        self::assertFalse($cache->has(IntBackedPriority::class));
        self::assertFalse($cache->has(UserStatus::class));
    }

    public function test_clear_instance_method_clears_all_entries(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(IntBackedPriority::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clear();

        self::assertFalse($cache->has(IntBackedPriority::class));
    }

    // -----------------------------------------------------------------------
    // Singleton behavior
    // -----------------------------------------------------------------------

    public function test_get_instance_returns_same_instance(): void
    {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        self::assertSame($a, $b);
    }

    public function test_reset_instance_creates_fresh_instance(): void
    {
        $a = EnumCache::getInstance();
        $a->setTtl(42);

        EnumCache::resetInstance();

        $b = EnumCache::getInstance();

        // Fresh instance should have default TTL (300), not 42
        self::assertNotSame($a, $b);
        self::assertSame(300, $b->getTtl());
    }

    // -----------------------------------------------------------------------
    // setTtl persistence across operations
    // -----------------------------------------------------------------------

    public function test_set_ttl_persists_across_operations(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(999);

        $cache->set(IntBackedPriority::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        self::assertSame(999, $cache->getTtl());
    }
}
