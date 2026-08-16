<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;

/**
 * Tests for generateLabel() edge cases and EnumCache lifecycle behavior.
 *
 * Ensures:
 * - Label generation handles single-character, numeric, and Unicode case names
 * - Cache TTL expiration works correctly
 * - Cache clear/fresh lifecycle is consistent
 * - EnumMetadataResolver::invalidate() clears individual class caches
 * - EnumCache::resetInstance() creates a fresh singleton
 */
final class EnumLabelGenerationAndCacheLifecycleTest extends TestCase
{
    // ---------------------------------------------------------------
    // generateLabel() edge cases
    // ---------------------------------------------------------------

    public function test_single_character_snake_case_label(): void
    {
        $label = SingleCharEnum::A->label();
        $this->assertSame('A', $label);
    }

    public function test_two_word_snake_case_label(): void
    {
        $label = TwoWordEnum::ACTIVE_USER->label();
        $this->assertSame('Active User', $label);
    }

    public function test_three_word_snake_case_label(): void
    {
        $label = ThreeWordEnum::FIRST_LEVEL_ADMIN->label();
        $this->assertSame('First Level Admin', $label);
    }

    public function test_camel_case_label(): void
    {
        $label = CamelLabelEnum::ActiveUser->label();
        $this->assertSame('Active User', $label);
    }

    public function test_pascal_case_label(): void
    {
        $label = PascalLabelEnum::ActiveUser->label();
        $this->assertSame('Active User', $label);
    }

    public function test_single_word_uppercase_label(): void
    {
        $label = SingleWordEnum::ACTIVE->label();
        $this->assertSame('Active', $label);
    }

    public function test_number_in_snake_case_label(): void
    {
        $label = NumberedEnum::LEVEL_3_ADMIN->label();
        $this->assertSame('Level 3 Admin', $label);
    }

    public function test_consecutive_uppercase_letters_label(): void
    {
        $label = AcronymEnum::HTTPS_PROTOCOL->label();
        $this->assertSame('Https Protocol', $label);
    }

    // ---------------------------------------------------------------
    // Cache lifecycle
    // ---------------------------------------------------------------

    public function test_cache_returns_same_result_on_second_call(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->clear();

        $meta1 = EnumMetadataResolver::resolve(TicketStatus::class);
        $meta2 = EnumMetadataResolver::resolve(TicketStatus::class);

        $this->assertSame($meta1, $meta2);
        $this->assertTrue($cache->has(TicketStatus::class));
    }

    public function test_invalidate_removes_specific_class(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->clear();

        EnumMetadataResolver::resolve(TicketStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        $this->assertTrue($cache->has(TicketStatus::class));
        $this->assertTrue($cache->has(Priority::class));

        EnumMetadataResolver::invalidate(TicketStatus::class);

        $this->assertFalse($cache->has(TicketStatus::class));
        $this->assertTrue($cache->has(Priority::class));
    }

    public function test_invalidate_all_removes_everything(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->clear();

        EnumMetadataResolver::resolve(TicketStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumMetadataResolver::invalidateAll();

        $this->assertFalse($cache->has(TicketStatus::class));
        $this->assertFalse($cache->has(Priority::class));
    }

    public function test_zero_ttl_disables_caching(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->clear();

        EnumMetadataResolver::resolve(TicketStatus::class);

        // With TTL=0, has() should always return false
        $this->assertFalse($cache->has(TicketStatus::class));
    }

    public function test_reset_instance_creates_fresh_singleton(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(999);
        $cache->clear();

        EnumMetadataResolver::resolve(TicketStatus::class);
        $this->assertTrue($cache->has(TicketStatus::class));

        EnumCache::resetInstance();

        $newCache = EnumCache::getInstance();
        // Fresh instance should not have the cached entry
        $this->assertFalse($newCache->has(TicketStatus::class));
        // TTL should be reset to default (300)
        $this->assertSame(300, $newCache->getTtl());
    }

    public function test_set_ttl_clamps_negative_to_zero(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-10);
        $this->assertSame(0, $cache->getTtl());
    }

    public function test_get_throws_when_no_cached_entry(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('No cached metadata');

        $cache->get('NonExistentEnum');
    }

    public function test_clear_class_removes_single_entry(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->clear();

        EnumMetadataResolver::resolve(TicketStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        $cache->clearClass(TicketStatus::class);

        $this->assertFalse($cache->has(TicketStatus::class));
        $this->assertTrue($cache->has(Priority::class));
    }

    // ---------------------------------------------------------------
    // forApi() shape consistency
    // ---------------------------------------------------------------

    public function test_for_api_returns_consistent_shape_for_all_enum_types(): void
    {
        $stringApi = TicketStatus::forApi();
        $intApi = Priority::forApi();

        foreach ($stringApi as $entry) {
            $this->assertArrayHasKey('value', $entry);
            $this->assertArrayHasKey('name', $entry);
            $this->assertArrayHasKey('label', $entry);
            $this->assertArrayHasKey('description', $entry);
            $this->assertArrayHasKey('color', $entry);
            $this->assertArrayHasKey('icon', $entry);
            $this->assertIsString($entry['value']);
            $this->assertIsString($entry['name']);
            $this->assertIsString($entry['label']);
            $this->assertIsString($entry['color']);
        }

        foreach ($intApi as $entry) {
            $this->assertArrayHasKey('value', $entry);
            $this->assertArrayHasKey('name', $entry);
            $this->assertArrayHasKey('label', $entry);
            $this->assertArrayHasKey('description', $entry);
            $this->assertArrayHasKey('color', $entry);
            $this->assertArrayHasKey('icon', $entry);
            $this->assertIsInt($entry['value']);
            $this->assertIsString($entry['name']);
            $this->assertIsString($entry['label']);
            $this->assertIsString($entry['color']);
        }
    }
}

// ---------------------------------------------------------------
// Inline fixtures for label generation edge cases
// ---------------------------------------------------------------

enum SingleCharEnum: string
{
    use HasEnumMetadata;

    case A = 'a';
}

enum TwoWordEnum: string
{
    use HasEnumMetadata;

    case ACTIVE_USER = 'active_user';
}

enum ThreeWordEnum: string
{
    use HasEnumMetadata;

    case FIRST_LEVEL_ADMIN = 'first_level_admin';
}

enum CamelLabelEnum: string
{
    use HasEnumMetadata;

    case ActiveUser = 'active_user';
}

enum PascalLabelEnum: string
{
    use HasEnumMetadata;

    case ActiveUser = 'active_user';
}

enum SingleWordEnum: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
}

enum NumberedEnum: string
{
    use HasEnumMetadata;

    case LEVEL_3_ADMIN = 'level_3_admin';
}

enum AcronymEnum: string
{
    use HasEnumMetadata;

    case HTTPS_PROTOCOL = 'https_protocol';
}
