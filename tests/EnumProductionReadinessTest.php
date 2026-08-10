<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderWorkflowStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;

/**
 * Comprehensive production readiness audit for the Enums package.
 *
 * Tests strict type safety, metadata resolution edge cases,
 * attribute handling, and PHPStan level 9 compliance patterns.
 */
final class EnumProductionReadinessTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Metadata Resolution: EnumIcon with per-case icon map
    // -----------------------------------------------------------------------

    public function test_enum_icon_map_resolves_specific_icons_for_int_backed_enum(): void
    {
        $this->assertSame('heroicon-o-check', SystemStatus::ENABLED->icon());
        $this->assertSame('heroicon-o-x-mark', SystemStatus::DISABLED->icon());
    }

    public function test_enum_icon_map_falls_back_to_default_for_unmapped_cases(): void
    {
        // MAINTENANCE has no specific icon in the map, so it falls back to the default
        $this->assertSame('heroicon-o-cog-6-tooth', SystemStatus::MAINTENANCE->icon());
    }

    public function test_enum_icon_default_without_map_works_for_all_cases(): void
    {
        $this->assertSame('heroicon-o-circle-question-mark', DefaultIconFeature::SEARCH->icon());
        $this->assertSame('heroicon-o-circle-question-mark', DefaultIconFeature::FILTER->icon());
    }

    public function test_per_case_icon_overrides_class_level_default(): void
    {
        // ADMIN has a per-case Icon override
        $this->assertSame('heroicon-o-user', OverriddenIconRole::ADMIN->icon());
        // VIEWER falls back to class-level default
        $this->assertSame('heroicon-o-circle-question-mark', OverriddenIconRole::VIEWER->icon());
    }

    // -----------------------------------------------------------------------
    // Metadata Resolution: EnumColor, EnumLabel, EnumDescription
    // -----------------------------------------------------------------------

    public function test_class_level_enum_color_maps_correctly(): void
    {
        $this->assertSame('success', OrderWorkflowStatus::ACTIVE->color());
        $this->assertSame('danger', OrderWorkflowStatus::FAILED->color());
        $this->assertSame('warning', OrderWorkflowStatus::PENDING->color());
        $this->assertSame('secondary', OrderWorkflowStatus::DRAFT->color());
    }

    public function test_class_level_enum_description_maps_correctly(): void
    {
        $this->assertSame('Payment has been approved', PaymentStatus::APPROVED->description());
        $this->assertSame('Payment was rejected', PaymentStatus::REJECTED->description());
        $this->assertSame('Payment is under review', PaymentStatus::REVIEW->description());
    }

    public function test_class_level_enum_label_maps_correctly(): void
    {
        $this->assertSame('Approved Payment', PaymentStatus::APPROVED->label());
        $this->assertSame('Rejected Payment', PaymentStatus::REJECTED->label());
    }

    public function test_per_case_description_overrides_class_level(): void
    {
        // IN_PROGRESS has a per-case Description override
        $this->assertSame('Ticket is currently being worked on', DetailedTicketStatus::IN_PROGRESS->description());
    }

    public function test_int_backed_enum_color_resolution(): void
    {
        $this->assertSame('danger', IntBackedPriority::CRITICAL->color());
        $this->assertSame('warning', IntBackedPriority::HIGH->color());
        $this->assertSame('success', IntBackedPriority::LOW->color());
        $this->assertSame('success', IntBackedPriority::NONE->color());
    }

    public function test_int_backed_enum_label_resolution(): void
    {
        $this->assertSame('Critical Priority', IntBackedPriority::CRITICAL->label());
        $this->assertSame('High Priority', IntBackedPriority::HIGH->label());
        $this->assertSame('Low Priority', IntBackedPriority::LOW->label());
    }

    // -----------------------------------------------------------------------
    // Bulk Methods: forSelect, forApi, values, labels
    // -----------------------------------------------------------------------

    public function test_for_select_returns_correct_structure_for_string_backed(): void
    {
        $options = PaymentStatus::forSelect();

        $this->assertCount(3, $options);
        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertIsString($option['value']);
            $this->assertIsString($option['label']);
            $this->assertNotEmpty($option['label']);
        }
    }

    public function test_for_select_returns_correct_structure_for_int_backed(): void
    {
        $options = SystemStatus::forSelect();

        $this->assertCount(3, $options);
        $this->assertSame(1, $options[0]['value']);
        $this->assertSame('Enabled', $options[0]['label']);
    }

    public function test_for_select_returns_correct_structure_for_pure_enum(): void
    {
        $options = PureFeatureFlag::forSelect();

        $this->assertCount(3, $options);
        $this->assertSame('DARK_MODE', $options[0]['value']);
        $this->assertSame('Dark Mode', $options[0]['label']);
    }

    public function test_for_api_returns_complete_metadata(): void
    {
        $api = PaymentStatus::forApi();

        $this->assertCount(3, $api);
        foreach ($api as $item) {
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('color', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertIsString($item['color']);
            $this->assertNotEmpty($item['color']);
        }
    }

    public function test_values_returns_backed_values(): void
    {
        $this->assertSame(['approved', 'rejected', 'review'], PaymentStatus::values());
    }

    public function test_values_returns_int_backed_values(): void
    {
        $this->assertSame([0, 1, 2], SystemStatus::values());
    }

    public function test_values_returns_case_names_for_pure_enum(): void
    {
        $values = PureFeatureFlag::values();
        $this->assertSame(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE'], $values);
    }

    public function test_labels_returns_non_empty_strings(): void
    {
        $labels = OrderWorkflowStatus::labels();
        $this->assertCount(20, $labels);
        foreach ($labels as $label) {
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    // -----------------------------------------------------------------------
    // Comparison Methods: is, isNot, in
    // -----------------------------------------------------------------------

    public function test_is_comparison_with_instance(): void
    {
        $this->assertTrue(PaymentStatus::APPROVED->is(PaymentStatus::APPROVED));
        $this->assertFalse(PaymentStatus::APPROVED->is(PaymentStatus::REJECTED));
    }

    public function test_is_comparison_with_string(): void
    {
        $this->assertTrue(PaymentStatus::APPROVED->is('APPROVED'));
        $this->assertFalse(PaymentStatus::APPROVED->is('REJECTED'));
    }

    public function test_is_comparison_is_case_sensitive(): void
    {
        $this->assertFalse(PaymentStatus::APPROVED->is('approved'));
        $this->assertFalse(PaymentStatus::APPROVED->is('Approved'));
    }

    public function test_is_not_negation(): void
    {
        $this->assertFalse(PaymentStatus::APPROVED->isNot(PaymentStatus::APPROVED));
        $this->assertTrue(PaymentStatus::APPROVED->isNot(PaymentStatus::REJECTED));
    }

    public function test_in_group_matching(): void
    {
        $this->assertTrue(PaymentStatus::APPROVED->in([PaymentStatus::APPROVED, PaymentStatus::REVIEW]));
        $this->assertFalse(PaymentStatus::APPROVED->in([PaymentStatus::REJECTED, PaymentStatus::REVIEW]));
    }

    public function test_in_group_matching_with_strings(): void
    {
        $this->assertTrue(PaymentStatus::APPROVED->in(['APPROVED', 'REVIEW']));
        $this->assertFalse(PaymentStatus::APPROVED->in(['REJECTED', 'REVIEW']));
    }

    public function test_in_group_matching_with_mixed_types(): void
    {
        $this->assertTrue(PaymentStatus::APPROVED->in([PaymentStatus::APPROVED, 'REVIEW']));
    }

    // -----------------------------------------------------------------------
    // Lookup Methods: tryFromLabel, tryFromName, fromName, hasCase
    // -----------------------------------------------------------------------

    public function test_try_from_label_case_insensitive(): void
    {
        $result = PaymentStatus::tryFromLabel('Approved Payment');
        $this->assertNotNull($result);
        $this->assertSame(PaymentStatus::APPROVED, $result);
    }

    public function test_try_from_label_lower_case(): void
    {
        $result = PaymentStatus::tryFromLabel('approved payment');
        $this->assertNotNull($result);
        $this->assertSame(PaymentStatus::APPROVED, $result);
    }

    public function test_try_from_label_returns_null_for_non_existent(): void
    {
        $this->assertNull(PaymentStatus::tryFromLabel('non-existent-label-xyz'));
    }

    public function test_try_from_name(): void
    {
        $result = PaymentStatus::tryFromName('APPROVED');
        $this->assertNotNull($result);
        $this->assertSame(PaymentStatus::APPROVED, $result);
    }

    public function test_try_from_name_returns_null_for_non_existent(): void
    {
        $this->assertNull(PaymentStatus::tryFromName('NON_EXISTENT'));
    }

    public function test_from_name_returns_correct_case(): void
    {
        $case = PaymentStatus::fromName('REVIEW');
        $this->assertSame(PaymentStatus::REVIEW, $case);
    }

    public function test_from_name_throws_for_non_existent(): void
    {
        $this->expectException(InvalidEnumException::class);
        $this->expectExceptionMessage('NON_EXISTENT');

        PaymentStatus::fromName('NON_EXISTENT');
    }

    public function test_has_case(): void
    {
        $this->assertTrue(PaymentStatus::hasCase('APPROVED'));
        $this->assertFalse(PaymentStatus::hasCase('NON_EXISTENT'));
    }

    // -----------------------------------------------------------------------
    // Pure Enum Specific Tests
    // -----------------------------------------------------------------------

    public function test_pure_enum_label_generation_from_screaming_snake(): void
    {
        $this->assertSame('Dark Mode', PureFeatureFlag::DARK_MODE->label());
        $this->assertSame('Beta Features', PureFeatureFlag::BETA_FEATURES->label());
        $this->assertSame('Maintenance Mode', PureFeatureFlag::MAINTENANCE_MODE->label());
    }

    public function test_pure_enum_per_case_attributes(): void
    {
        $this->assertSame('heroicon-o-moon', PureFeatureFlag::DARK_MODE->icon());
        $this->assertSame('secondary', PureFeatureFlag::DARK_MODE->color());
        $this->assertSame('Toggle dark mode for the UI', PureFeatureFlag::DARK_MODE->description());
    }

    public function test_pure_enum_values_returns_case_names(): void
    {
        $values = PureFeatureFlag::values();
        $this->assertSame(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE'], $values);
    }

    public function test_pure_enum_for_select_uses_case_names(): void
    {
        $options = PureFeatureFlag::forSelect();
        $this->assertSame('DARK_MODE', $options[0]['value']);
    }

    // -----------------------------------------------------------------------
    // Auto-generated label for cases without attributes
    // -----------------------------------------------------------------------

    public function test_auto_generated_label_for_case_without_attributes(): void
    {
        // MAINTENANCE_MODE has no Label attribute
        $this->assertSame('Maintenance Mode', PureFeatureFlag::MAINTENANCE_MODE->label());
    }

    public function test_auto_generated_label_for_order_workflow_status(): void
    {
        // OrderWorkflowStatus has no per-case or class-level labels
        $this->assertSame('Draft', OrderWorkflowStatus::DRAFT->label());
        $this->assertSame('Pending', OrderWorkflowStatus::PENDING->label());
        $this->assertSame('Processing', OrderWorkflowStatus::PROCESSING->label());
        $this->assertSame('Completed', OrderWorkflowStatus::COMPLETED->label());
    }

    // -----------------------------------------------------------------------
    // Default color behavior
    // -----------------------------------------------------------------------

    public function test_default_color_is_secondary(): void
    {
        // DetailedTicketStatus has no EnumColor attribute at class or case level
        $this->assertSame('secondary', DetailedTicketStatus::OPEN->color());
        $this->assertSame('secondary', DetailedTicketStatus::IN_PROGRESS->color());
    }

    // -----------------------------------------------------------------------
    // Cache and Invalidation
    // -----------------------------------------------------------------------

    public function test_cache_invalidate_forces_re_resolve(): void
    {
        // Resolve once to populate cache
        $first = EnumMetadataResolver::resolve(PaymentStatus::class);
        $this->assertNotEmpty($first['labels']);

        // Invalidate and resolve again
        EnumMetadataResolver::invalidate(PaymentStatus::class);
        $second = EnumMetadataResolver::resolve(PaymentStatus::class);

        // Same data, but was rebuilt from scratch
        $this->assertSame($first, $second);
    }

    public function test_cache_invalidate_all_clears_everything(): void
    {
        // Populate cache for multiple enums
        EnumMetadataResolver::resolve(PaymentStatus::class);
        EnumMetadataResolver::resolve(OrderWorkflowStatus::class);

        // Invalidate all
        EnumMetadataResolver::invalidateAll();

        // Next resolve should work fine (rebuilt from scratch)
        $result = EnumMetadataResolver::resolve(PaymentStatus::class);
        $this->assertNotEmpty($result['labels']);
    }

    // -----------------------------------------------------------------------
    // EnumRule validation
    // -----------------------------------------------------------------------

    public function test_enum_rule_accepts_valid_string_backed_value(): void
    {
        $rule = new EnumRule(PaymentStatus::class);
        $fail = fn (): string => throw new \LogicException('Should not fail');

        // Should not throw
        $rule->validate('status', 'approved', $fail);
    }

    public function test_enum_rule_rejects_invalid_value(): void
    {
        $rule = new EnumRule(PaymentStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', 'invalid_value', $fail);
        $this->assertTrue($failed);
    }

    public function test_enum_rule_rejects_null_when_not_nullable(): void
    {
        $rule = new EnumRule(PaymentStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', null, $fail);
        $this->assertTrue($failed);
    }

    public function test_enum_rule_accepts_null_when_nullable(): void
    {
        $rule = (new EnumRule(PaymentStatus::class))->nullable();
        $fail = fn (): string => throw new \LogicException('Should not fail');

        // Should not throw for null
        $rule->validate('status', null, $fail);
    }

    public function test_enum_rule_for_int_backed_enum(): void
    {
        $rule = new EnumRule(SystemStatus::class);
        $fail = fn (): string => throw new \LogicException('Should not fail');

        // Should not throw for valid int
        $rule->validate('status', 1, $fail);
    }

    public function test_enum_rule_rejects_wrong_type_for_int_backed(): void
    {
        $rule = new EnumRule(SystemStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        // String to int-backed enum should fail
        $rule->validate('status', '1', $fail);
        $this->assertTrue($failed);
    }

    public function test_enum_rule_for_pure_enum_validates_case_names(): void
    {
        $rule = new EnumRule(PureFeatureFlag::class);
        $fail = fn (): string => throw new \LogicException('Should not fail');

        // Valid case name
        $rule->validate('flag', 'DARK_MODE', $fail);
    }

    public function test_enum_rule_for_pure_enum_rejects_invalid_name(): void
    {
        $rule = new EnumRule(PureFeatureFlag::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('flag', 'NON_EXISTENT', $fail);
        $this->assertTrue($failed);
    }

    // -----------------------------------------------------------------------
    // EnumCast
    // -----------------------------------------------------------------------

    public function test_enum_cast_get_converts_string_to_enum(): void
    {
        $cast = new EnumCast(PaymentStatus::class);
        $result = $cast->get(
            new \stdClass(),
            'status',
            'approved',
            [],
        );

        $this->assertSame(PaymentStatus::APPROVED, $result);
    }

    public function test_enum_cast_get_returns_null_for_null_value(): void
    {
        $cast = new EnumCast(PaymentStatus::class);
        $result = $cast->get(
            new \stdClass(),
            'status',
            null,
            [],
        );

        $this->assertNull($result);
    }

    public function test_enum_cast_get_returns_null_for_invalid_value(): void
    {
        $cast = new EnumCast(PaymentStatus::class);
        $result = $cast->get(
            new \stdClass(),
            'status',
            'invalid',
            [],
        );

        $this->assertNull($result);
    }

    public function test_enum_cast_set_stores_backed_value(): void
    {
        $cast = new EnumCast(PaymentStatus::class);
        $result = $cast->set(
            new \stdClass(),
            'status',
            PaymentStatus::APPROVED,
            [],
        );

        $this->assertSame('approved', $result);
    }

    public function test_enum_cast_set_returns_null_for_null(): void
    {
        $cast = new EnumCast(PaymentStatus::class);
        $result = $cast->set(
            new \stdClass(),
            'status',
            null,
            [],
        );

        $this->assertNull($result);
    }

    public function test_enum_cast_serializes_to_backed_value(): void
    {
        $cast = new EnumCast(PaymentStatus::class);
        $result = $cast->serialize(
            new \stdClass(),
            'status',
            PaymentStatus::REVIEW,
            [],
        );

        $this->assertSame('review', $result);
    }

    // -----------------------------------------------------------------------
    // InvalidEnumException
    // -----------------------------------------------------------------------

    public function test_invalid_enum_exception_value_format(): void
    {
        $exception = InvalidEnumException::value(PaymentStatus::class, 'invalid');
        $this->assertStringContainsString('invalid', $exception->getMessage());
        $this->assertStringContainsString(PaymentStatus::class, $exception->getMessage());
    }

    public function test_invalid_enum_exception_null_value_format(): void
    {
        $exception = InvalidEnumException::value(PaymentStatus::class, null);
        $this->assertStringContainsString('null', $exception->getMessage());
    }

    public function test_invalid_enum_exception_for_name_format(): void
    {
        $exception = InvalidEnumException::forName(PaymentStatus::class, 'BOGUS');
        $this->assertStringContainsString('BOGUS', $exception->getMessage());
        $this->assertStringContainsString(PaymentStatus::class, $exception->getMessage());
    }

    // -----------------------------------------------------------------------
    // Large Enum Performance
    // -----------------------------------------------------------------------

    public function test_large_enum_bulk_operations_are_consistent(): void
    {
        $cases = OrderWorkflowStatus::cases();
        $this->assertCount(20, $cases);

        $selectOptions = OrderWorkflowStatus::forSelect();
        $this->assertCount(20, $selectOptions);

        $apiData = OrderWorkflowStatus::forApi();
        $this->assertCount(20, $apiData);

        $values = OrderWorkflowStatus::values();
        $this->assertCount(20, $values);

        $labels = OrderWorkflowStatus::labels();
        $this->assertCount(20, $labels);

        // Verify forSelect values are unique
        $selectValues = array_column($selectOptions, 'value');
        $this->assertSame($selectValues, array_unique($selectValues));

        // Verify all API items have required keys
        foreach ($apiData as $item) {
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('color', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertIsString($item['color']);
            $this->assertNotEmpty($item['color']);
        }
    }

    // -----------------------------------------------------------------------
    // Select option values uniqueness across different enum types
    // -----------------------------------------------------------------------

    public function test_for_select_values_unique_for_int_backed(): void
    {
        $values = array_column(SystemStatus::forSelect(), 'value');
        $this->assertSame($values, array_unique($values));
    }

    public function test_for_select_values_unique_for_pure_enum(): void
    {
        $values = array_column(PureFeatureFlag::forSelect(), 'value');
        $this->assertSame($values, array_unique($values));
    }

    // -----------------------------------------------------------------------
    // Strict type safety checks (PHPStan Level 9 patterns)
    // -----------------------------------------------------------------------

    public function test_all_methods_have_strict_return_types(): void
    {
        $ref = new \ReflectionClass(PaymentStatus::class);
        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            // Static factory methods from HasEnumMetadata
            $returnType = $method->getReturnType();
            $this->assertNotNull(
                $returnType,
                "Method {$method->getName()} in PaymentStatus must have a return type"
            );
        }
    }

    public function test_enum_rule_is_readonly(): void
    {
        $ref = new \ReflectionClass(EnumRule::class);
        $this->assertTrue($ref->isFinal());
        $this->assertTrue($ref->isReadOnly());
    }

    public function test_enum_manager_is_readonly(): void
    {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);
        $this->assertTrue($ref->isFinal());
        $this->assertTrue($ref->isReadOnly());
    }

    public function test_enum_cache_is_final_singleton(): void
    {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumCache::class);
        $this->assertTrue($ref->isFinal());
        $this->assertNotNull($ref->getMethod('getInstance'));
        $this->assertTrue($ref->getMethod('getInstance')->isPublic());
        $this->assertTrue($ref->getMethod('getInstance')->isStatic());
    }

    public function test_enum_metadata_resolver_is_final(): void
    {
        $ref = new \ReflectionClass(EnumMetadataResolver::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_all_attribute_classes_are_final(): void
    {
        $attributes = [
            EnumColor::class,
            EnumDescription::class,
            EnumIcon::class,
            EnumLabel::class,
            \ZeroBoiler\Enums\Attributes\Color::class,
            \ZeroBoiler\Enums\Attributes\Description::class,
            \ZeroBoiler\Enums\Attributes\Icon::class,
            \ZeroBoiler\Enums\Attributes\Label::class,
        ];

        foreach ($attributes as $attrClass) {
            $ref = new \ReflectionClass($attrClass);
            $this->assertTrue(
                $ref->isFinal(),
                "Attribute {$attrClass} must be final"
            );
        }
    }

    public function test_all_attribute_classes_use_readonly_properties(): void
    {
        $attributes = [
            EnumColor::class,
            EnumDescription::class,
            EnumIcon::class,
            EnumLabel::class,
            \ZeroBoiler\Enums\Attributes\Color::class,
            \ZeroBoiler\Enums\Attributes\Description::class,
            \ZeroBoiler\Enums\Attributes\Icon::class,
            \ZeroBoiler\Enums\Attributes\Label::class,
        ];

        foreach ($attributes as $attrClass) {
            $ref = new \ReflectionClass($attrClass);
            foreach ($ref->getProperties() as $prop) {
                $this->assertTrue(
                    $prop->isReadOnly(),
                    "Property {$prop->getName()} in {$attrClass} must be readonly"
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // EnumIcon attribute: icons parameter type safety
    // -----------------------------------------------------------------------

    public function test_enum_icon_attribute_accepts_icons_map(): void
    {
        $attr = new EnumIcon(
            default: 'heroicon-o-flag',
            icons: [1 => 'heroicon-o-check', 0 => 'heroicon-o-x-mark'],
        );

        $this->assertSame('heroicon-o-flag', $attr->default);
        $this->assertSame([1 => 'heroicon-o-check', 0 => 'heroicon-o-x-mark'], $attr->icons);
    }

    public function test_enum_icon_attribute_works_with_only_default(): void
    {
        $attr = new EnumIcon(default: 'heroicon-o-circle-question-mark');

        $this->assertSame('heroicon-o-circle-question-mark', $attr->default);
        $this->assertSame([], $attr->icons);
    }

    public function test_enum_icon_attribute_works_with_only_icons(): void
    {
        $attr = new EnumIcon(icons: ['active' => 'heroicon-o-check']);

        $this->assertNull($attr->default);
        $this->assertSame(['active' => 'heroicon-o-check'], $attr->icons);
    }

    public function test_enum_icon_attribute_works_without_arguments(): void
    {
        $attr = new EnumIcon();

        $this->assertNull($attr->default);
        $this->assertSame([], $attr->icons);
    }
}
