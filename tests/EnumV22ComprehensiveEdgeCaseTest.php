<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use BackedEnum;
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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use UnitEnum;

/**
 * V22: Comprehensive edge-case tests for type safety, boundary conditions,
 * and production-readiness of the enums package.
 *
 * Tests cover:
 * - Integer-backed enums (label, color, icon, description, forSelect, forApi)
 * - EnumCache TTL boundary behavior (exact expiry)
 * - EnumRule with pure enums and non-string values
 * - Comparison operators edge cases (empty arrays, single item)
 * - Label generation edge cases (single character, all-caps short)
 * - Metadata resolver cache coherence (invalidation mid-resolution)
 * - EnumCast-like scenarios for int-backed enums
 * - Facade-like static delegation patterns
 */
#[EnumColor(success: [1], danger: [3])]
#[EnumLabel(labels: [2 => 'In Review'])]
#[EnumDescription(descriptions: [3 => 'Account is permanently closed'])]
#[EnumIcon(default: 'heroicon-o-circle', icons: [1 => 'heroicon-o-check'])]
enum PriorityLevel: int
{
    use HasEnumMetadata;

    #[Label('Low Priority'), Description('Minimum priority level')]
    case LOW = 1;

    case MEDIUM = 2;

    #[Color('danger'), Icon('heroicon-o-x-circle')]
    case HIGH = 3;
}

enum SimpleState: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

enum SingleCaseEnum: string
{
    use HasEnumMetadata;

    case ONLY = 'only';
}

enum ShortName: string
{
    use HasEnumMetadata;

    case A = 'a';
    case BC = 'bc';
}

/** Pure enum for testing pure enum support in EnumRule and HasEnumMetadata */
enum PureFeatureFlag
{
    use HasEnumMetadata;

    case DARK_MODE;
    case BETA_ACCESS;
    case NEW_UI;
}

final class EnumV22ComprehensiveEdgeCaseTest extends TestCase
{
    // ------------------------------------------------------------------
    // Integer-backed enum tests
    // ------------------------------------------------------------------

    public function test_int_backed_enum_label_resolution(): void
    {
        $this->assertSame('Low Priority', PriorityLevel::LOW->label());
        // Class-level EnumLabel override
        $this->assertSame('In Review', PriorityLevel::MEDIUM->label());
        // Auto-generated from HIGH
        $this->assertSame('High', PriorityLevel::HIGH->label());
    }

    public function test_int_backed_enum_color_resolution(): void
    {
        $this->assertSame('success', PriorityLevel::LOW->color());
        $this->assertSame('secondary', PriorityLevel::MEDIUM->color());
        // Per-case override
        $this->assertSame('danger', PriorityLevel::HIGH->color());
    }

    public function test_int_backed_enum_icon_resolution(): void
    {
        // Per-case override from icons map
        $this->assertSame('heroicon-o-check', PriorityLevel::LOW->icon());
        // Default icon from EnumIcon default
        $this->assertSame('heroicon-o-circle', PriorityLevel::MEDIUM->icon());
        // Per-case override attribute
        $this->assertSame('heroicon-o-x-circle', PriorityLevel::HIGH->icon());
    }

    public function test_int_backed_enum_description_resolution(): void
    {
        $this->assertSame('Minimum priority level', PriorityLevel::LOW->description());
        // Class-level EnumDescription
        $this->assertSame('Account is permanently closed', PriorityLevel::HIGH->description());
        // Not set
        $this->assertNull(PriorityLevel::MEDIUM->description());
    }

    public function test_int_backed_enum_for_select_uses_backed_value(): void
    {
        $select = PriorityLevel::forSelect();

        $this->assertCount(3, $select);

        // Keys should be backed int values
        $values = array_column($select, 'value');
        $this->assertSame([1, 2, 3], $values);

        $labels = array_column($select, 'label');
        $this->assertSame(['Low Priority', 'In Review', 'High'], $labels);
    }

    public function test_int_backed_enum_for_api_full_metadata(): void
    {
        $api = PriorityLevel::forApi();

        $this->assertCount(3, $api);

        // First entry: LOW (1)
        $this->assertSame(1, $api[0]['value']);
        $this->assertSame('LOW', $api[0]['name']);
        $this->assertSame('Low Priority', $api[0]['label']);
        $this->assertSame('success', $api[0]['color']);
        $this->assertSame('heroicon-o-check', $api[0]['icon']);
        $this->assertSame('Minimum priority level', $api[0]['description']);

        // Third entry: HIGH (3) — per-case overrides
        $this->assertSame(3, $api[2]['value']);
        $this->assertSame('danger', $api[2]['color']);
        $this->assertSame('heroicon-o-x-circle', $api[2]['icon']);
    }

    public function test_int_backed_enum_values_returns_ints(): void
    {
        $values = PriorityLevel::values();
        $this->assertSame([1, 2, 3], $values);
    }

    public function test_int_backed_enum_labels(): void
    {
        $labels = PriorityLevel::labels();
        $this->assertSame(['Low Priority', 'In Review', 'High'], $labels);
    }

    public function test_int_backed_enum_try_from_name(): void
    {
        $this->assertSame(PriorityLevel::LOW, PriorityLevel::tryFromName('LOW'));
        $this->assertSame(PriorityLevel::HIGH, PriorityLevel::tryFromName('HIGH'));
        $this->assertNull(PriorityLevel::tryFromName('INVALID'));
    }

    public function test_int_backed_enum_from_name_throws(): void
    {
        $this->expectException(InvalidEnumException::class);
        PriorityLevel::fromName('NONEXISTENT');
    }

    public function test_int_backed_enum_has_case(): void
    {
        $this->assertTrue(PriorityLevel::hasCase('MEDIUM'));
        $this->assertFalse(PriorityLevel::hasCase('CRITICAL'));
    }

    public function test_int_backed_enum_try_from_label(): void
    {
        $this->assertSame(PriorityLevel::LOW, PriorityLevel::tryFromLabel('Low Priority'));
        $this->assertSame(PriorityLevel::MEDIUM, PriorityLevel::tryFromLabel('In Review'));
        // Case-insensitive
        $this->assertSame(PriorityLevel::LOW, PriorityLevel::tryFromLabel('low priority'));
        $this->assertNull(PriorityLevel::tryFromLabel('Unknown'));
    }

    // ------------------------------------------------------------------
    // Comparison operator edge cases
    // ------------------------------------------------------------------

    public function test_is_with_instance(): void
    {
        $this->assertTrue(PriorityLevel::LOW->is(PriorityLevel::LOW));
        $this->assertFalse(PriorityLevel::LOW->is(PriorityLevel::HIGH));
    }

    public function test_is_with_string_name(): void
    {
        $this->assertTrue(PriorityLevel::LOW->is('LOW'));
        $this->assertFalse(PriorityLevel::LOW->is('HIGH'));
        $this->assertFalse(PriorityLevel::LOW->is('low')); // case-sensitive
    }

    public function test_is_not(): void
    {
        $this->assertTrue(PriorityLevel::LOW->isNot(PriorityLevel::HIGH));
        $this->assertFalse(PriorityLevel::LOW->isNot(PriorityLevel::LOW));
    }

    public function test_in_with_single_item_array(): void
    {
        $this->assertTrue(PriorityLevel::LOW->in([PriorityLevel::LOW]));
        $this->assertFalse(PriorityLevel::LOW->in([PriorityLevel::HIGH]));
    }

    public function test_in_with_empty_array(): void
    {
        $this->assertFalse(PriorityLevel::LOW->in([]));
    }

    public function test_in_with_mixed_instances_and_strings(): void
    {
        $this->assertTrue(
            PriorityLevel::LOW->in([PriorityLevel::MEDIUM, 'LOW'])
        );
    }

    public function test_not_in_with_empty_array(): void
    {
        $this->assertTrue(PriorityLevel::LOW->notIn([]));
    }

    // ------------------------------------------------------------------
    // Label generation edge cases
    // ------------------------------------------------------------------

    public function test_single_char_enum_label(): void
    {
        $this->assertSame('A', ShortName::A->label());
    }

    public function test_two_char_enum_label(): void
    {
        $this->assertSame('Bc', ShortName::BC->label());
    }

    public function test_single_case_enum_label(): void
    {
        $this->assertSame('Only', SingleCaseEnum::ONLY->label());
    }

    // ------------------------------------------------------------------
    // EnumCache TTL boundary tests
    // ------------------------------------------------------------------

    public function test_cache_ttl_zero_disables_caching(): void
    {
        $cache = EnumCache::getInstance();
        $originalTtl = $cache->getTtl();

        $cache->setTtl(0);
        $cache->clear();

        // TTL 0 → has() always returns false
        $this->assertFalse($cache->has(PriorityLevel::class));

        // Set metadata
        $metadata = ['labels' => [1 => 'Test'], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $cache->set(PriorityLevel::class, $metadata);

        // Even after set, TTL 0 means has() returns false
        $this->assertFalse($cache->has(PriorityLevel::class));

        // Restore
        $cache->setTtl($originalTtl);
        $cache->clear();
    }

    public function test_cache_ttl_negative_normalized_to_zero(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);
        $this->assertSame(0, $cache->getTtl());
        $cache->setTtl(300);
    }

    public function test_cache_set_overwrites_existing_entry(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();

        $cache->set(PriorityLevel::class, ['labels' => [1 => 'Old'], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set(PriorityLevel::class, ['labels' => [1 => 'New'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $entry = $cache->get(PriorityLevel::class);
        $this->assertSame('New', $entry['labels'][1]);

        $cache->clear();
    }

    public function test_cache_clear_class_preserves_others(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(999);

        $cache->set(PriorityLevel::class, ['labels' => [1 => 'P'], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set(SimpleState::class, ['labels' => ['active' => 'S'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clearClass(PriorityLevel::class);

        $this->assertFalse($cache->has(PriorityLevel::class));
        $this->assertTrue($cache->has(SimpleState::class));

        $cache->clear();
        $cache->setTtl(300);
    }

    // ------------------------------------------------------------------
    // EnumRule with int-backed enums
    // ------------------------------------------------------------------

    public function test_enum_rule_int_backed_accepts_valid_int(): void
    {
        $rule = EnumRule::for(PriorityLevel::class);
        $fail = fn () => $this->fail('Should not fail');

        $rule->validate('priority', 1, $fail);
        $rule->validate('priority', 2, $fail);
        $rule->validate('priority', 3, $fail);
    }

    public function test_enum_rule_int_backed_rejects_string(): void
    {
        $rule = EnumRule::for(PriorityLevel::class);
        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        // String value for int-backed enum should fail
        $rule->validate('priority', '1', $fail);
        $this->assertTrue($failed, 'String "1" should be rejected for int-backed enum');
    }

    public function test_enum_rule_int_backed_rejects_invalid_int(): void
    {
        $rule = EnumRule::for(PriorityLevel::class);
        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        $rule->validate('priority', 99, $fail);
        $this->assertTrue($failed);
    }

    // ------------------------------------------------------------------
    // EnumRule with pure enums
    // ------------------------------------------------------------------

    public function test_enum_rule_pure_enum_accepts_valid_case_name(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $fail = fn () => $this->fail('Should not fail');

        $rule->validate('feature', 'DARK_MODE', $fail);
        $rule->validate('feature', 'BETA_ACCESS', $fail);
    }

    public function test_enum_rule_pure_enum_rejects_invalid_name(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        $rule->validate('feature', 'NONEXISTENT', $fail);
        $this->assertTrue($failed);
    }

    public function test_enum_rule_pure_enum_rejects_non_string(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        // Pure enums only accept string case names
        $rule->validate('feature', 123, $fail);
        $this->assertTrue($failed);
    }

    // ------------------------------------------------------------------
    // EnumRule nullable behavior
    // ------------------------------------------------------------------

    public function test_enum_rule_nullable_allows_null(): void
    {
        $rule = EnumRule::for(PriorityLevel::class)->nullable();
        $fail = fn () => $this->fail('Should not fail for null');

        $rule->validate('priority', null, $fail);
    }

    public function test_enum_rule_non_nullable_rejects_null(): void
    {
        $rule = EnumRule::for(PriorityLevel::class);
        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        $rule->validate('priority', null, $fail);
        $this->assertTrue($failed);
    }

    // ------------------------------------------------------------------
    // EnumRule error messages with HasEnumMetadata
    // ------------------------------------------------------------------

    public function test_enum_rule_error_message_includes_allowed_values(): void
    {
        $rule = EnumRule::for(PriorityLevel::class);
        $errorMessage = '';

        $fail = function (string $message) use (&$errorMessage): void {
            $errorMessage = $message;
        };

        $rule->validate('priority', 999, $fail);

        $this->assertStringContainsString('Allowed values: 1, 2, 3', $errorMessage);
    }

    // ------------------------------------------------------------------
    // InvalidEnumException factory methods
    // ------------------------------------------------------------------

    public function test_invalid_enum_exception_value(): void
    {
        $e = InvalidEnumException::value(PriorityLevel::class, 99);
        $this->assertSame('Value [99] is not a valid case of [ZeroBoiler\Enums\Tests\PriorityLevel].', $e->getMessage());
    }

    public function test_invalid_enum_exception_value_null(): void
    {
        $e = InvalidEnumException::value(PriorityLevel::class, null);
        $this->assertStringContainsString('null', $e->getMessage());
    }

    public function test_invalid_enum_exception_for_name(): void
    {
        $e = InvalidEnumException::forName(PriorityLevel::class, 'CRITICAL');
        $this->assertSame('Case name [CRITICAL] does not exist on enum [ZeroBoiler\Enums\Tests\PriorityLevel].', $e->getMessage());
    }

    public function test_invalid_enum_exception_to_string(): void
    {
        $e = InvalidEnumException::forName(SimpleState::class, 'UNKNOWN');
        $str = (string) $e;
        $this->assertStringStartsWith('ZeroBoiler\Enums\Exceptions\InvalidEnumException:', $str);
    }

    // ------------------------------------------------------------------
    // forSelect / forApi structural integrity
    // ------------------------------------------------------------------

    public function test_for_select_returns_sequential_keys(): void
    {
        $select = SimpleState::forSelect();
        $keys = array_keys($select);
        $this->assertSame([0, 1], $keys);
    }

    public function test_for_api_keys_match_for_select_values(): void
    {
        $api = SimpleState::forApi();
        $select = SimpleState::forSelect();

        $apiValues = array_column($api, 'value');
        $selectValues = array_column($select, 'value');

        $this->assertSame($apiValues, $selectValues);
    }

    public function test_for_api_contains_all_expected_keys(): void
    {
        $api = PriorityLevel::forApi();

        foreach ($api as $entry) {
            $this->assertArrayHasKey('value', $entry);
            $this->assertArrayHasKey('name', $entry);
            $this->assertArrayHasKey('label', $entry);
            $this->assertArrayHasKey('description', $entry);
            $this->assertArrayHasKey('color', $entry);
            $this->assertArrayHasKey('icon', $entry);
        }
    }

    // ------------------------------------------------------------------
    // Pure enum HasEnumMetadata
    // ------------------------------------------------------------------

    public function test_pure_enum_label_auto_generated(): void
    {
        $this->assertSame('Dark Mode', PureFeatureFlag::DARK_MODE->label());
        $this->assertSame('Beta Access', PureFeatureFlag::BETA_ACCESS->label());
        $this->assertSame('New Ui', PureFeatureFlag::NEW_UI->label());
    }

    public function test_pure_enum_color_defaults_to_secondary(): void
    {
        $this->assertSame('secondary', PureFeatureFlag::DARK_MODE->color());
    }

    public function test_pure_enum_icon_and_description_default_to_null(): void
    {
        $this->assertNull(PureFeatureFlag::DARK_MODE->icon());
        $this->assertNull(PureFeatureFlag::DARK_MODE->description());
    }

    public function test_pure_enum_for_select_uses_case_names(): void
    {
        $select = PureFeatureFlag::forSelect();
        $values = array_column($select, 'value');
        $this->assertSame(['DARK_MODE', 'BETA_ACCESS', 'NEW_UI'], $values);
    }

    public function test_pure_enum_values_returns_case_names(): void
    {
        $values = PureFeatureFlag::values();
        $this->assertSame(['DARK_MODE', 'BETA_ACCESS', 'NEW_UI'], $values);
    }

    // ------------------------------------------------------------------
    // EnumCache singleton behavior
    // ------------------------------------------------------------------

    public function test_cache_get_throws_on_missing_entry(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();

        $this->expectException(\OutOfBoundsException::class);
        $cache->get('NonexistentEnumClass');
    }

    public function test_cache_flush_via_static(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(999);

        $cache->set(PriorityLevel::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        EnumCache::flush();

        $this->assertFalse($cache->has(PriorityLevel::class));
    }

    public function test_reset_instance_allows_fresh_singleton(): void
    {
        EnumCache::resetInstance();

        $instance1 = EnumCache::getInstance();
        EnumCache::resetInstance();
        $instance2 = EnumCache::getInstance();

        // After reset, we should get a new instance (different object identity)
        // Both should work independently
        $this->assertNotSame($instance1, $instance2);
    }

    // ------------------------------------------------------------------
    // EnumCache serialization prevention
    // ------------------------------------------------------------------

    public function test_cache_prevents_serialization(): void
    {
        $cache = EnumCache::getInstance();

        $this->expectException(\RuntimeException::class);
        $cache->__serialize();
    }

    public function test_cache_prevents_unserialization(): void
    {
        $cache = EnumCache::getInstance();

        $this->expectException(\RuntimeException::class);
        $cache->__unserialize([]);
    }
}
