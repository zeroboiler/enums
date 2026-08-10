<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

/**
 * Tests for class-level attributes with per-case overrides via their case-level parameter.
 *
 * EnumLabel, EnumDescription, and EnumIcon can be used on individual cases
 * to set a single-case override (using their `$label`, `$description`, `$default` params).
 * These should take precedence over class-level bulk maps.
 */
final class EnumClassLevelAttributeCaseOverrideTest extends TestCase
{
    /**
     * EnumLabel used at case-level sets a single label override.
     */
    public function test_enum_label_on_case_sets_label(): void
    {
        $label = TestLabelOverrideCase::ACTIVE->label();

        $this->assertSame('Overridden Active', $label);
    }

    /**
     * EnumLabel class-level provides default when case-level EnumLabel is absent.
     */
    public function test_enum_label_class_level_fallback(): void
    {
        $label = TestLabelOverrideCase::INACTIVE->label();

        $this->assertSame('Inactive Default', $label);
    }

    /**
     * Per-case Label attribute takes precedence over class-level EnumLabel.
     */
    public function test_per_case_label_overrides_class_level_enum_label(): void
    {
        $label = TestLabelOverrideCase::BANNED->label();

        $this->assertSame('Banned User', $label);
    }

    /**
     * EnumDescription used at case-level sets a single description override.
     */
    public function test_enum_description_on_case_sets_description(): void
    {
        $desc = TestDescriptionOverrideCase::OPEN->description();

        $this->assertSame('Ticket is being actively worked on', $desc);
    }

    /**
     * EnumDescription class-level provides default when case-level is absent.
     */
    public function test_enum_description_class_level_fallback(): void
    {
        $desc = TestDescriptionOverrideCase::CLOSED->description();

        $this->assertSame('Ticket has been resolved', $desc);
    }

    /**
     * Per-case Description attribute takes precedence over class-level EnumDescription.
     */
    public function test_per_case_description_overrides_enum_description(): void
    {
        $desc = TestDescriptionOverrideCase::ESCALATED->description();

        $this->assertSame('Escalated to senior team', $desc);
    }

    /**
     * EnumIcon class-level provides default icon for all cases.
     */
    public function test_enum_icon_class_level_default_applies_to_all(): void
    {
        $this->assertSame('heroicon-o-circle-question-mark', TestIconOverrideCase::READER->icon());
        $this->assertSame('heroicon-o-circle-question-mark', TestIconOverrideCase::EDITOR->icon());
    }

    /**
     * Per-case Icon attribute overrides class-level EnumIcon default.
     */
    public function test_per_case_icon_overrides_enum_icon_default(): void
    {
        $this->assertSame('heroicon-o-shield', TestIconOverrideCase::ADMIN->icon());
    }

    /**
     * fromName() works correctly with class-level attribute enums.
     */
    public function test_from_name_works_with_class_level_attributes(): void
    {
        $case = TestLabelOverrideCase::fromName('ACTIVE');

        $this->assertSame('ACTIVE', $case->name);
    }

    /**
     * fromName() throws for invalid names on class-level attribute enums.
     */
    public function test_from_name_throws_for_invalid_name(): void
    {
        $this->expectException(InvalidEnumException::class);

        TestLabelOverrideCase::fromName('NONEXISTENT');
    }

    /**
     * forSelect() structure is correct with class-level overrides.
     */
    public function test_for_select_structure_with_class_level_overrides(): void
    {
        $select = TestLabelOverrideCase::forSelect();

        $this->assertCount(3, $select);
        foreach ($select as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertNotEmpty($option['label']);
        }
    }

    /**
     * forApi() returns full metadata with class-level overrides.
     */
    public function test_for_api_returns_full_metadata(): void
    {
        $api = TestLabelOverrideCase::forApi();

        $this->assertCount(3, $api);
        foreach ($api as $item) {
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('color', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertIsString($item['color']);
        }
    }

    /**
     * Cache invalidation works for enums with case-level overrides.
     */
    public function test_cache_invalidation_for_override_enums(): void
    {
        EnumMetadataResolver::invalidate(TestLabelOverrideCase::class);

        // Re-resolve — should still work after invalidation
        $label = TestLabelOverrideCase::ACTIVE->label();
        $this->assertSame('Overridden Active', $label);
    }

    /**
     * Comparison methods work with class-level attribute enums.
     */
    public function test_comparison_methods_with_class_level_attributes(): void
    {
        $active = TestLabelOverrideCase::ACTIVE;

        $this->assertTrue($active->is(TestLabelOverrideCase::ACTIVE));
        $this->assertTrue($active->is('ACTIVE'));
        $this->assertFalse($active->is(TestLabelOverrideCase::INACTIVE));
        $this->assertTrue($active->isNot(TestLabelOverrideCase::BANNED));
        $this->assertTrue($active->in([TestLabelOverrideCase::ACTIVE, TestLabelOverrideCase::INACTIVE]));
    }

    /**
     * values() returns backed values in declaration order.
     */
    public function test_values_returns_backed_values(): void
    {
        $values = TestLabelOverrideCase::values();

        $this->assertSame(['active', 'inactive', 'banned'], $values);
    }

    /**
     * labels() returns resolved labels in declaration order.
     */
    public function test_labels_returns_resolved_labels(): void
    {
        $labels = TestLabelOverrideCase::labels();

        $this->assertCount(3, $labels);
        $this->assertSame('Overridden Active', $labels[0]);
        $this->assertSame('Inactive Default', $labels[1]);
    }
}

// ── Test Fixtures ──────────────────────────────────────────────────────

#[EnumLabel(labels: ['active' => 'Active Default', 'inactive' => 'Inactive Default', 'banned' => 'Banned Default'])]
#[EnumColor(success: ['active'], danger: ['banned'], warning: ['inactive'])]
enum TestLabelOverrideCase: string
{
    use HasEnumMetadata;

    #[Label('Overridden Active')]
    case ACTIVE = 'active';

    // Uses class-level EnumLabel fallback
    case INACTIVE = 'inactive';

    #[Label('Banned User')]
    case BANNED = 'banned';
}

#[EnumDescription(descriptions: ['open' => 'Ticket is open', 'closed' => 'Ticket has been resolved'])]
enum TestDescriptionOverrideCase: string
{
    use HasEnumMetadata;

    // Uses class-level EnumDescription
    case OPEN = 'open';

    // Uses class-level EnumDescription
    case CLOSED = 'closed';

    #[EnumDescription(description: 'Escalated to senior team')]
    case ESCALATED = 'escalated';
}

#[EnumIcon(default: 'heroicon-o-circle-question-mark')]
enum TestIconOverrideCase: string
{
    use HasEnumMetadata;

    #[Icon('heroicon-o-shield')]
    case ADMIN = 'admin';

    // Uses class-level EnumIcon default
    case EDITOR = 'editor';

    case READER = 'reader';
}
