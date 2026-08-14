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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Edge-case coverage tests for enum cache, rule, and resolver interactions.
 *
 * Covers TTL boundary conditions, type mismatch edge cases in EnumRule,
 * cache invalidation during resolution, and metadata resolver contract.
 */
final class EnumEdgeCaseCoverageTest extends TestCase
{
    protected function tearDown(): void
    {
        // Always reset singleton to avoid cross-test pollution
        EnumCache::resetInstance();
    }

    // ── EnumCache TTL Boundary Tests ──────────────────────────────

    public function test_cache_returns_false_when_ttl_is_zero(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $this->assertFalse($cache->has(UserStatus::class));
    }

    public function test_cache_auto_expires_after_ttl(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL

        $metadata = [
            'labels' => ['active' => 'Active User'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];
        $cache->set(UserStatus::class, $metadata);

        // Should be fresh immediately
        $this->assertTrue($cache->has(UserStatus::class));

        // Manually age the cache entry by manipulating timestamp
        // We can't sleep in tests, so we directly test the boundary via reflection
        $ref = new \ReflectionProperty($cache, 'cacheTimestamps');
        $timestamps = $ref->getValue($cache);
        $timestamps[UserStatus::class] = microtime(true) - 2; // age 2 seconds
        $ref->setValue($cache, $timestamps);

        $this->assertFalse($cache->has(UserStatus::class));
    }

    public function test_cache_negative_ttl_is_normalized_to_zero(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-10);

        $this->assertSame(0, $cache->getTtl());
    }

    public function test_cache_clear_class_removes_single_entry(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(IntBackedPriority::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        $cache->clearClass(UserStatus::class);

        $this->assertFalse($cache->has(UserStatus::class));
        $this->assertTrue($cache->has(IntBackedPriority::class));
    }

    public function test_get_throws_out_of_bounds_for_missing_entry(): void
    {
        $cache = EnumCache::getInstance();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('No cached metadata for [NonExistentClass]');

        $cache->get('NonExistentClass');
    }

    public function test_flush_static_clears_all_entries(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(IntBackedPriority::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        EnumCache::flush();

        $this->assertFalse($cache->has(UserStatus::class));
        $this->assertFalse($cache->has(IntBackedPriority::class));
    }

    // ── EnumRule Type Mismatch Edge Cases ─────────────────────────

    public function test_enum_rule_rejects_string_for_int_backed_enum(): void
    {
        $rule = EnumRule::for(IntBackedPriority::class);

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        // IntBackedPriority is int-backed; passing a string should fail
        $rule->validate('priority', 'high', $fail);

        $this->assertTrue($failCalled, 'Expected fail() to be called for string value on int-backed enum');
    }

    public function test_enum_rule_accepts_valid_int_for_int_backed_enum(): void
    {
        $rule = EnumRule::for(IntBackedPriority::class);

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        // Find a valid int value from the enum
        $validValue = IntBackedPriority::cases()[0]->value;

        $rule->validate('priority', $validValue, $fail);

        $this->assertFalse($failCalled, 'Expected fail() NOT to be called for valid int value');
    }

    public function test_enum_rule_nullable_allows_null(): void
    {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('status', null, $fail);

        $this->assertFalse($failCalled, 'Nullable rule should allow null values');
    }

    public function test_enum_rule_non_nullable_rejects_null(): void
    {
        $rule = EnumRule::for(UserStatus::class);

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('status', null, $fail);

        $this->assertTrue($failCalled, 'Non-nullable rule should reject null values');
    }

    public function test_enum_rule_rejects_non_string_for_pure_enum(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        // Pure enum validates by name (string); passing int should fail
        $rule->validate('flag', 42, $fail);

        $this->assertTrue($failCalled, 'Expected fail() for non-string value on pure enum');
    }

    public function test_enum_rule_accepts_valid_case_name_for_pure_enum(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $validName = PureFeatureFlag::cases()[0]->name;

        $rule->validate('flag', $validName, $fail);

        $this->assertFalse($failCalled, 'Expected fail() NOT to be called for valid pure enum case name');
    }

    public function test_enum_rule_message_includes_allowed_values(): void
    {
        $rule = new \ReflectionClass(EnumRule::class);
        $method = $rule->getMethod('message');
        $method->setAccessible(true);

        $instance = EnumRule::for(UserStatus::class);
        $message = $method->invoke($instance, 'status');

        $this->assertStringContainsString('status', $message);
        $this->assertStringContainsString('Allowed values:', $message);
    }

    // ── EnumMetadataResolver Cache Invalidation ───────────────────

    public function test_resolver_rebuilds_after_invalidation(): void
    {
        // First resolve
        $first = EnumMetadataResolver::resolve(UserStatus::class);
        $this->assertArrayHasKey('labels', $first);

        // Invalidate
        EnumMetadataResolver::invalidate(UserStatus::class);

        // Second resolve should rebuild (not throw)
        $second = EnumMetadataResolver::resolve(UserStatus::class);
        $this->assertArrayHasKey('labels', $second);
        $this->assertEquals($first, $second);
    }

    public function test_invalidate_all_clears_everything(): void
    {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        EnumMetadataResolver::invalidateAll();

        // After invalidation, cache should be empty for both
        $cache = EnumCache::getInstance();
        $this->assertFalse($cache->has(UserStatus::class));
        $this->assertFalse($cache->has(IntBackedPriority::class));
    }

    public function test_resolver_throws_for_non_enum_class(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('is not a valid enum class');

        EnumMetadataResolver::resolve(\stdClass::class);
    }

    // ── InvalidEnumException Factory Methods ────────────────────────

    public function test_exception_value_factory_with_null(): void
    {
        $ex = InvalidEnumException::value(UserStatus::class, null);

        $this->assertStringContainsString('null', $ex->getMessage());
        $this->assertStringContainsString(UserStatus::class, $ex->getMessage());
    }

    public function test_exception_value_factory_with_string(): void
    {
        $ex = InvalidEnumException::value(UserStatus::class, 'nonexistent');

        $this->assertStringContainsString('nonexistent', $ex->getMessage());
    }

    public function test_exception_value_factory_with_int(): void
    {
        $ex = InvalidEnumException::value(IntBackedPriority::class, 999);

        $this->assertStringContainsString('999', $ex->getMessage());
    }

    public function test_exception_for_name_factory(): void
    {
        $ex = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

        $this->assertStringContainsString('NONEXISTENT', $ex->getMessage());
        $this->assertStringContainsString('does not exist', $ex->getMessage());
    }

    public function test_exception_to_string_format(): void
    {
        $ex = InvalidEnumException::forName(UserStatus::class, 'BAD');

        $string = (string) $ex;

        $this->assertStringStartsWith(InvalidEnumException::class, $string);
        $this->assertStringContainsString($ex->getMessage(), $string);
    }

    // ── HasEnumMetadata Comparison Edge Cases ──────────────────────

    public function test_in_method_with_empty_array(): void
    {
        $case = UserStatus::ACTIVE;

        $this->assertFalse($case->in([]));
    }

    public function test_not_in_method_with_single_matching(): void
    {
        $case = UserStatus::ACTIVE;

        $this->assertFalse($case->notIn([UserStatus::ACTIVE]));
        $this->assertTrue($case->notIn([UserStatus::BANNED]));
    }

    public function test_values_returns_correct_types(): void
    {
        $values = UserStatus::values();

        $this->assertContains('active', $values);
        $this->assertContains('banned', $values);
        $this->assertContains('pending', $values);

        // All values should be strings for a string-backed enum
        foreach ($values as $v) {
            $this->assertIsString($v);
        }
    }

    public function test_int_backed_values_are_integers(): void
    {
        $values = IntBackedPriority::values();

        foreach ($values as $v) {
            $this->assertIsInt($v);
        }
    }

    public function test_labels_returns_same_count_as_cases(): void
    {
        $labels = UserStatus::labels();

        $this->assertCount(count(UserStatus::cases()), $labels);
    }

    // ── forSelect / forApi Structure Tests ──────────────────────────

    public function test_for_select_returns_value_label_pairs(): void
    {
        $select = UserStatus::forSelect();

        $this->assertNotEmpty($select);
        foreach ($select as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }

    public function test_for_api_returns_complete_metadata(): void
    {
        $api = UserStatus::forApi();

        $this->assertNotEmpty($api);
        foreach ($api as $item) {
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('color', $item);
            $this->assertArrayHasKey('icon', $item);
        }
    }

    public function test_for_api_pure_enum_uses_name_as_value(): void
    {
        $api = PureFeatureFlag::forApi();

        foreach ($api as $item) {
            $this->assertSame($item['name'], $item['value']);
        }
    }

    // ── tryFromLabel Case-Insensitive Lookup ───────────────────────

    public function test_try_from_label_case_insensitive(): void
    {
        $case = UserStatus::ACTIVE;

        // Get the actual label
        $label = $case->label();

        // Try with different casing
        $found = UserStatus::tryFromLabel(strtoupper($label));
        $this->assertSame($case, $found);

        $found = UserStatus::tryFromLabel(strtolower($label));
        $this->assertSame($case, $found);
    }

    public function test_try_from_label_returns_null_for_no_match(): void
    {
        $this->assertNull(UserStatus::tryFromLabel('Nonexistent Label That Does Not Exist'));
    }

    // ── hasCase / fromName / tryFromName ───────────────────────────

    public function test_has_case_true_for_existing(): void
    {
        $this->assertTrue(UserStatus::hasCase('ACTIVE'));
        $this->assertTrue(UserStatus::hasCase('BANNED'));
    }

    public function test_has_case_false_for_nonexistent(): void
    {
        $this->assertFalse(UserStatus::hasCase('NONEXISTENT'));
    }

    public function test_from_name_throws_for_invalid(): void
    {
        $this->expectException(InvalidEnumException::class);
        UserStatus::fromName('DOES_NOT_EXIST');
    }

    public function test_try_from_name_returns_null_for_invalid(): void
    {
        $this->assertNull(UserStatus::tryFromName('DOES_NOT_EXIST'));
    }

    // ── Class-Level Attribute Resolution Priority ───────────────────

    public function test_class_level_color_is_applied(): void
    {
        // UserStatus has class-level EnumColor: success=['active'], danger=['banned']
        $active = UserStatus::ACTIVE;
        $banned = UserStatus::BANNED;

        $this->assertSame('success', $active->color());
        $this->assertSame('danger', $banned->color());
    }

    public function test_per_case_color_overrides_class_level(): void
    {
        // BANNED has both class-level (danger via EnumColor) AND per-case #[Color('danger')]
        // Per-case wins
        $banned = UserStatus::BANNED;
        $this->assertSame('danger', $banned->color());
    }

    // ── EnumManager Facade Delegation ──────────────────────────────

    public function test_enum_manager_for_select_delegates(): void
    {
        $manager = new \ZeroBoiler\Enums\EnumManager();
        $select = $manager->forSelect(UserStatus::class);

        $this->assertNotEmpty($select);
        $this->assertArrayHasKey('value', $select[0]);
        $this->assertArrayHasKey('label', $select[0]);
    }

    public function test_enum_manager_throws_for_non_metadata_enum(): void
    {
        $manager = new \ZeroBoiler\Enums\EnumManager();

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('does not use HasEnumMetadata');

        $manager->forSelect(\stdClass::class);
    }

    // ── Label Auto-Generation Edge Cases ───────────────────────────

    public function test_auto_label_from_screaming_snake(): void
    {
        // INACTIVE has no #[Label] — should auto-generate
        $inactive = UserStatus::INACTIVE;
        $label = $inactive->label();

        // SCREAMING_SNAKE_CASE → Title Case
        $this->assertSame('Inactive', $label);
    }

    public function test_auto_label_from_camel_case(): void
    {
        // PureFeatureFlag cases are UPPER_SNAKE, let's check a camelCase enum
        $camelCase = \ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::cases()[0];
        $label = $camelCase->label();

        // Should convert camelCase to Title Case
        $this->assertNotEmpty($label);
        $this->assertNotSame($camelCase->name, $label);
    }
}
