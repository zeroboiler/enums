<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Tests for class-level EnumLabel, EnumDescription, EnumColor, EnumIcon resolution.
 *
 * Verifies that:
 * - Class-level labels are applied to all cases
 * - Per-case labels override class-level labels
 * - Class-level colors map case values to color names
 * - Class-level icons set a default icon for all cases
 * - Cache invalidation correctly refreshes class-level metadata
 */
final class EnumClassLevelLabelResolutionTest extends TestCase
{
    protected function tearDown(): void
    {
        // Ensure clean state between tests
        EnumCache::flush();

        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // Class-level EnumLabel resolution
    // ---------------------------------------------------------------

    public function test_class_level_enum_label_provides_labels_for_all_cases(): void
    {
        $meta = EnumMetadataResolver::resolve(AllClassLevelEnum::class);

        // AllClassLevelEnum should have class-level labels for all its cases
        $this->assertNotEmpty($meta['labels']);
    }

    public function test_per_case_label_overrides_class_level_label(): void
    {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // ACTIVE has a per-case #[Label('Active User')]
        $this->assertArrayHasKey('active', $meta['labels']);
        $this->assertSame('Active User', $meta['labels']['active']);
    }

    public function test_missing_class_level_label_falls_back_to_generated(): void
    {
        // INACTIVE has no per-case label and no class-level label
        $case = UserStatus::INACTIVE;
        $label = $case->label();

        // Should auto-generate from SCREAMING_SNAKE_CASE
        $this->assertSame('Inactive', $label);
    }

    // ---------------------------------------------------------------
    // Class-level EnumColor resolution
    // ---------------------------------------------------------------

    public function test_class_level_enum_color_maps_values(): void
    {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // #[EnumColor(success: ['active'], danger: ['banned'], ...)]
        $this->assertArrayHasKey('active', $meta['colors']);
        $this->assertSame('success', $meta['colors']['active']);

        $this->assertArrayHasKey('banned', $meta['colors']);
        $this->assertSame('danger', $meta['colors']['banned']);

        $this->assertArrayHasKey('pending', $meta['colors']);
        $this->assertSame('warning', $meta['colors']['pending']);
    }

    public function test_per_case_color_overrides_class_level(): void
    {
        // BANNED has both class-level color 'danger' and per-case #[Color('danger')]
        // Both are 'danger' so they match, but the per-case wins in the resolver
        $case = UserStatus::BANNED;
        $color = $case->color();

        $this->assertSame('danger', $color);
    }

    // ---------------------------------------------------------------
    // Cache lifecycle with class-level attributes
    // ---------------------------------------------------------------

    public function test_cache_invalidates_class_level_metadata(): void
    {
        // Resolve once (populates cache)
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);

        // Invalidate
        EnumMetadataResolver::invalidate(UserStatus::class);

        // Resolve again (should rebuild from scratch)
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        $this->assertSame($meta1, $meta2);
    }

    public function test_invalidate_all_clears_all_cached_class_level_data(): void
    {
        EnumMetadataResolver::resolve(UserStatus::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        $this->assertFalse($cache->has(UserStatus::class));
    }

    // ---------------------------------------------------------------
    // Metadata shape consistency
    // ---------------------------------------------------------------

    public function test_metadata_shape_has_all_required_keys(): void
    {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        $this->assertArrayHasKey('labels', $meta);
        $this->assertArrayHasKey('descriptions', $meta);
        $this->assertArrayHasKey('colors', $meta);
        $this->assertArrayHasKey('icons', $meta);

        $this->assertIsArray($meta['labels']);
        $this->assertIsArray($meta['descriptions']);
        $this->assertIsArray($meta['colors']);
        $this->assertIsArray($meta['icons']);
    }

    public function test_all_label_values_are_strings(): void
    {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        foreach ($meta['labels'] as $key => $label) {
            $this->assertIsString($key, "Label key must be string, got {$key}");
            $this->assertIsString($label, "Label value must be string for key {$key}");
        }
    }

    public function test_all_color_values_are_strings(): void
    {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        foreach ($meta['colors'] as $key => $color) {
            $this->assertIsString($key, "Color key must be string, got {$key}");
            $this->assertIsString($color, "Color value must be string for key {$key}");
        }
    }

    // ---------------------------------------------------------------
    // Class-level EnumDescription resolution
    // ---------------------------------------------------------------

    public function test_per_case_description_is_resolved(): void
    {
        $case = UserStatus::ACTIVE;
        $desc = $case->description();

        $this->assertSame('User can fully access the system', $desc);
    }

    public function test_case_without_description_returns_null(): void
    {
        $case = UserStatus::INACTIVE;
        $desc = $case->description();

        $this->assertNull($desc);
    }

    // ---------------------------------------------------------------
    // Class-level EnumIcon resolution
    // ---------------------------------------------------------------

    public function test_per_case_icon_overrides_class_level(): void
    {
        $case = UserStatus::ACTIVE;
        $icon = $case->icon();

        $this->assertSame('heroicon-o-check-circle', $icon);
    }

    public function test_case_without_icon_returns_null(): void
    {
        $case = UserStatus::INACTIVE;
        $icon = $case->icon();

        $this->assertNull($icon);
    }
}
