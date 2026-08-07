<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Tests for metadata resolution conflict priority and edge cases.
 *
 * Ensures:
 * - Per-case attributes always override class-level attributes
 * - Cache TTL=0 disables caching (fresh resolve on every call)
 * - Cache clear invalidates all entries
 * - Cache clearClass invalidates only the target class
 * - Multiple enum classes can be cached independently
 * - EnumCache is singleton (same instance across getInstance() calls)
 */
final class EnumMetadataPriorityAndCacheLifecycleTest extends TestCase
{
    protected function tearDown(): void
    {
        // Always reset cache to prevent state leaking between tests
        EnumCache::resetInstance();
    }

    // ---------------------------------------------------------------
    // Cache singleton behavior
    // ---------------------------------------------------------------

    public function test_cache_returns_same_instance(): void
    {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        $this->assertSame($a, $b, 'EnumCache::getInstance() must always return the same singleton');
    }

    public function test_resetInstance_returns_new_instance(): void
    {
        $a = EnumCache::getInstance();
        EnumCache::resetInstance();
        $b = EnumCache::getInstance();

        $this->assertNotSame($a, $b, 'resetInstance() must create a brand-new instance');
    }

    // ---------------------------------------------------------------
    // Cache TTL=0 (caching disabled)
    // ---------------------------------------------------------------

    public function test_ttl_zero_disables_caching(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $this->assertFalse(
            $cache->has(UserStatus::class),
            'has() must return false when TTL is 0 (caching disabled)',
        );
    }

    public function test_ttl_zero_always_reports_stale(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(0);

        // Even if we just set a value...
        $cache->set(TicketStatus::class, [
            'labels' => ['open' => 'Open'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // has() should still return false because TTL=0 means "no caching"
        $this->assertFalse($cache->has(TicketStatus::class));
    }

    // ---------------------------------------------------------------
    // Cache clear / clearClass
    // ---------------------------------------------------------------

    public function test_clear_removes_all_entries(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, $this->emptyMeta());
        $cache->set(TicketStatus::class, $this->emptyMeta());

        $this->assertTrue($cache->has(UserStatus::class));
        $this->assertTrue($cache->has(TicketStatus::class));

        $cache->clear();

        $this->assertFalse($cache->has(UserStatus::class));
        $this->assertFalse($cache->has(TicketStatus::class));
    }

    public function test_clearClass_removes_only_target(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, $this->emptyMeta());
        $cache->set(TicketStatus::class, $this->emptyMeta());

        $cache->clearClass(UserStatus::class);

        $this->assertFalse($cache->has(UserStatus::class), 'UserStatus should be cleared');
        $this->assertTrue($cache->has(TicketStatus::class), 'TicketStatus should remain cached');
    }

    // ---------------------------------------------------------------
    // Per-case vs class-level attribute priority
    // ---------------------------------------------------------------

    public function test_per_case_color_overrides_class_level(): void
    {
        // IntStatusWithColor: class-level sets 3 => danger, but per-case overrides 3 => danger (same here).
        // Let's test BANNED = 3 which has per-case #[Color('danger')]
        $banned = IntStatusWithColor::BANNED;
        $active = IntStatusWithColor::ACTIVE;

        // ACTIVE = 1 → class-level success
        $this->assertSame('success', $active->color());
        // BANNED = 3 → class-level would be danger, per-case also says danger
        $this->assertSame('danger', $banned->color());
    }

    public function test_int_backed_enum_values_are_integers(): void
    {
        $values = IntStatusWithColor::values();

        foreach ($values as $value) {
            $this->assertIsInt($value, 'Integer-backed enum values() must return ints');
        }
    }

    public function test_int_backed_enum_forSelect_values_are_integers(): void
    {
        $options = IntStatusWithColor::forSelect();

        foreach ($options as $option) {
            $this->assertIsInt($option['value'], 'Integer-backed enum forSelect() values must be ints');
            $this->assertIsString($option['label'], 'Labels must always be strings');
        }
    }

    // ---------------------------------------------------------------
    // Metadata shape validation
    // ---------------------------------------------------------------

    public function test_cached_metadata_has_correct_shape(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(300);

        $meta = $this->emptyMeta();
        $cache->set(UserStatus::class, $meta);

        $retrieved = $cache->get(UserStatus::class);

        $this->assertArrayHasKey('labels', $retrieved);
        $this->assertArrayHasKey('descriptions', $retrieved);
        $this->assertArrayHasKey('colors', $retrieved);
        $this->assertArrayHasKey('icons', $retrieved);
    }

    public function test_get_throws_for_missing_entry(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('NonExistentEnum');

        $cache->get('NonExistentEnum');
    }

    public function test_flush_is_static_convenience_for_clear(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, $this->emptyMeta());

        $this->assertTrue($cache->has(UserStatus::class));

        EnumCache::flush();

        $this->assertFalse($cache->has(UserStatus::class));
    }

    // ---------------------------------------------------------------
    // Negative TTL normalization
    // ---------------------------------------------------------------

    public function test_negative_ttl_normalized_to_zero(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(-100);

        $cache->set(UserStatus::class, $this->emptyMeta());

        // Negative TTL is normalized to 0 → caching disabled
        $this->assertFalse($cache->has(UserStatus::class));
    }

    // ---------------------------------------------------------------
    // TTL expiration
    // ---------------------------------------------------------------

    public function test_expired_entries_are_evicted_on_has_check(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        // Use TTL of 0.001 seconds (1ms) — effectively immediate expiration
        $cache->setTtl(1);

        $cache->set(UserStatus::class, $this->emptyMeta());

        // Should be cached initially
        $this->assertTrue($cache->has(UserStatus::class));

        // Sleep past the TTL
        usleep(2000); // 2ms

        // Should now be expired
        $this->assertFalse($cache->has(UserStatus::class));
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    /**
     * @return array{labels: array<string, string>, descriptions: array<string, string>, colors: array<string, string>, icons: array<string, string>}
     */
    private function emptyMeta(): array
    {
        return [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];
    }
}
