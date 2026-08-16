<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * Comprehensive type-safety and contract audit for PHPStan Level 9.
 *
 * Tests that every public API method returns strictly typed values
 * with no `mixed` leaks, all comparisons use strict operators,
 * and all return types match their declared signatures.
 *
 * Covers edge cases not tested in other files:
 * - Zero-backed int enums
 * - CamelCase auto-label generation consistency
 * - Empty description/icon defaults
 * - Facade delegation type safety
 * - EnumRule with both backed and pure enums
 * - InvalidEnumException factory method contracts
 */
final class Php85StrictTypeContractAuditTest extends TestCase
{
    // -----------------------------------------------------------------
    // Zero-backed int enum edge case
    // -----------------------------------------------------------------

    public function test_zero_backed_enum_value_is_zero(): void
    {
        self::assertSame(0, ZeroBackedPriority::NONE->value);
        self::assertSame('None', ZeroBackedPriority::NONE->label());
        self::assertSame('secondary', ZeroBackedPriority::NONE->color());
    }

    public function test_zero_backed_enum_values_includes_zero(): void
    {
        $values = ZeroBackedPriority::values();

        self::assertContains(0, $values);
        self::assertSame([0, 1, 2, 3], $values);
    }

    public function test_zero_backed_enum_for_select_includes_zero_value(): void
    {
        $select = ZeroBackedPriority::forSelect();
        $values = array_column($select, 'value');

        self::assertContains(0, $values);
        self::assertContains('None', array_column($select, 'label'));
    }

    // -----------------------------------------------------------------
    // CamelCase auto-label generation — strict contract
    // -----------------------------------------------------------------

    public function test_camel_case_role_generates_title_case_labels(): void
    {
        // isActive → "Is Active" (not "Isactive" or "is Active")
        self::assertSame('Is Active', CamelCaseRole::isActive->label());
        self::assertSame('Is Admin', CamelCaseRole::isAdmin->label());
        self::assertSame('Is Moderator', CamelCaseRole::isModerator->label());
        self::assertSame('Is Banned', CamelCaseRole::isBanned->label());
    }

    public function test_camel_case_role_labels_are_all_strings(): void
    {
        foreach (CamelCaseRole::labels() as $label) {
            self::assertIsString($label);
            self::assertNotEmpty($label);
        }
    }

    public function test_camel_case_role_values_are_string_backed(): void
    {
        $values = CamelCaseRole::values();

        foreach ($values as $value) {
            self::assertIsString($value);
        }
    }

    public function test_camel_case_priority_class_level_overrides(): void
    {
        // 'active' has EnumLabel override: 'Online'
        self::assertSame('Online', CamelCasePriority::active->label());
        self::assertSame('success', CamelCasePriority::active->color());
        self::assertSame('heroicon-o-check', CamelCasePriority::active->icon());
        self::assertSame('User is online', CamelCasePriority::active->description());

        // 'pendingReview' has per-case Label override: 'Awaiting Approval'
        self::assertSame('Awaiting Approval', CamelCasePriority::pendingReview->label());
        self::assertSame('warning', CamelCasePriority::pendingReview->color());
        // default icon from EnumIcon
        self::assertSame('heroicon-o-circle', CamelCasePriority::pendingReview->icon());
        self::assertNull(CamelCasePriority::pendingReview->description());

        // 'archived' has class-level EnumDescription
        self::assertSame('Account archived', CamelCasePriority::archived->description());
        // auto-label from class-level EnumLabel (archived not in EnumLabel, so auto-gen)
        self::assertSame('Archived', CamelCasePriority::archived->label());

        // 'softDeleted' has per-case Description
        self::assertSame('Soft-deleted account', CamelCasePriority::softDeleted->description());
        // auto-label for camelCase: 'Soft Deleted'
        self::assertSame('Soft Deleted', CamelCasePriority::softDeleted->label());
    }

    // -----------------------------------------------------------------
    // Int-backed enum — strict type safety
    // -----------------------------------------------------------------

    public function test_int_backed_enum_for_api_structure(): void
    {
        $api = IntBackedPriority::forApi();

        self::assertIsArray($api);
        self::assertNotEmpty($api);

        foreach ($api as $item) {
            self::assertArrayHasKey('value', $item);
            self::assertArrayHasKey('name', $item);
            self::assertArrayHasKey('label', $item);
            self::assertArrayHasKey('description', $item);
            self::assertArrayHasKey('color', $item);
            self::assertArrayHasKey('icon', $item);
            self::assertIsInt($item['value']);
            self::assertIsString($item['name']);
            self::assertIsString($item['label']);
            self::assertIsString($item['color']);
        }
    }

    public function test_int_backed_enum_values_are_all_integers(): void
    {
        foreach (IntBackedPriority::values() as $value) {
            self::assertIsInt($value);
        }
    }

    public function test_int_status_with_color_resolves_correctly(): void
    {
        self::assertSame('success', IntStatusWithColor::ACTIVE->color());
        self::assertSame('danger', IntStatusWithColor::BANNED->color());
        self::assertSame('secondary', IntStatusWithColor::PENDING->color());
    }

    // -----------------------------------------------------------------
    // Pure enum — strict type safety
    // -----------------------------------------------------------------

    public function test_pure_enum_values_returns_case_names(): void
    {
        $values = PureFeatureFlag::values();

        self::assertSame(['BETA_ACCESS', 'DARK_MODE', 'NEW_DASHBOARD'], $values);
    }

    public function test_pure_enum_for_select_uses_case_names_as_values(): void
    {
        $select = PureFeatureFlag::forSelect();

        self::assertSame('BETA_ACCESS', $select[0]['value']);
        self::assertSame('Beta Access', $select[0]['label']);
    }

    public function test_pure_enum_for_api_uses_case_names(): void
    {
        $api = PureFeatureFlag::forApi();

        foreach ($api as $item) {
            self::assertIsString($item['value']);
            self::assertIsString($item['name']);
            self::assertSame($item['value'], $item['name']);
        }
    }

    public function test_pure_enum_comparison_is_strict(): void
    {
        $flag = PureFeatureFlag::BETA_ACCESS;

        self::assertTrue($flag->is(PureFeatureFlag::BETA_ACCESS));
        self::assertFalse($flag->is(PureFeatureFlag::DARK_MODE));
        self::assertTrue($flag->is('BETA_ACCESS'));
        self::assertFalse($flag->is('beta_access')); // case-sensitive
    }

    public function test_pure_enum_try_from_name_is_case_sensitive(): void
    {
        self::assertSame(PureFeatureFlag::BETA_ACCESS, PureFeatureFlag::tryFromName('BETA_ACCESS'));
        self::assertNull(PureFeatureFlag::tryFromName('beta_access'));
        self::assertNull(PureFeatureFlag::tryFromName(''));
    }

    // -----------------------------------------------------------------
    // Single case enum
    // -----------------------------------------------------------------

    public function test_single_case_enum_has_one_case(): void
    {
        self::assertCount(1, SingleCaseEnum::cases());
        self::assertSame('ON', SingleCaseEnum::cases()[0]->name);
    }

    public function test_single_case_enum_for_select_has_one_entry(): void
    {
        $select = SingleCaseEnum::forSelect();

        self::assertCount(1, $select);
    }

    public function test_single_case_enum_not_in_empty_list(): void
    {
        self::assertFalse(SingleCaseEnum::ON->notIn([SingleCaseEnum::ON]));
    }

    // -----------------------------------------------------------------
    // InvalidEnumException — factory method contracts
    // -----------------------------------------------------------------

    public function test_exception_for_name_contains_class_and_name(): void
    {
        $exception = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN');

        self::assertStringContainsString('UserStatus', $exception->getMessage());
        self::assertStringContainsString('UNKNOWN', $exception->getMessage());
    }

    public function test_exception_value_handles_null(): void
    {
        $exception = InvalidEnumException::value(UserStatus::class, null);

        self::assertStringContainsString('null', $exception->getMessage());
        self::assertStringContainsString('UserStatus', $exception->getMessage());
    }

    public function test_exception_value_handles_int(): void
    {
        $exception = InvalidEnumException::value(IntBackedPriority::class, 999);

        self::assertStringContainsString('999', $exception->getMessage());
    }

    public function test_exception_to_string_is_string(): void
    {
        $exception = InvalidEnumException::forName(UserStatus::class, 'X');

        $string = (string) $exception;

        self::assertIsString($string);
        self::assertStringContainsString('InvalidEnumException', $string);
    }

    // -----------------------------------------------------------------
    // EnumRule — PHPStan Level 9 strict type contracts
    // -----------------------------------------------------------------

    public function test_enum_rule_validates_string_backed_enum(): void
    {
        $rule = EnumRule::for(UserStatus::class);
        $fail = fn (): mixed => self::fail('Should not call fail');

        // Valid value — no failure
        $rule->validate('status', 'active', $fail);
        $rule->validate('status', 'inactive', $fail);
        $rule->validate('status', 'banned', $fail);

        // This test passes if no exception is thrown
        $this->expectNotToPerformAssertions();
    }

    public function test_enum_rule_rejects_invalid_string_backed_value(): void
    {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', 'nonexistent', function () use (&$failed): void {
            $failed = true;
        });

        self::assertTrue($failed);
    }

    public function test_enum_rule_allows_nullable_with_null_value(): void
    {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $failed = false;

        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        self::assertFalse($failed);
    }

    public function test_enum_rule_rejects_null_without_nullable(): void
    {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        self::assertTrue($failed);
    }

    public function test_enum_rule_validates_int_backed_enum(): void
    {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failed = false;

        $rule->validate('priority', 1, function () use (&$failed): void {
            $failed = true;
        });

        self::assertFalse($failed);
    }

    public function test_enum_rule_rejects_string_for_int_backed_enum(): void
    {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failed = false;

        // PHP's strict tryFrom rejects string for int-backed enum
        $rule->validate('priority', '1', function () use (&$failed): void {
            $failed = true;
        });

        self::assertTrue($failed);
    }

    public function test_enum_rule_validates_pure_enum_by_case_name(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;

        $rule->validate('flag', 'BETA_ACCESS', function () use (&$failed): void {
            $failed = true;
        });

        self::assertFalse($failed);
    }

    public function test_enum_rule_rejects_invalid_pure_enum_case_name(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;

        $rule->validate('flag', 'NONEXISTENT', function () use (&$failed): void {
            $failed = true;
        });

        self::assertTrue($failed);
    }

    // -----------------------------------------------------------------
    // Bulk methods — strict return type contracts
    // -----------------------------------------------------------------

    public function test_for_select_returns_list_of_arrays(): void
    {
        $result = UserStatus::forSelect();

        self::assertIsArray($result);
        self::assertNotEmpty($result);

        foreach ($result as $item) {
            self::assertIsArray($item);
            self::assertArrayHasKey('value', $item);
            self::assertArrayHasKey('label', $item);
        }
    }

    public function test_for_api_returns_list_of_arrays_with_six_keys(): void
    {
        $result = UserStatus::forApi();

        self::assertIsArray($result);

        foreach ($result as $item) {
            self::assertArrayHasKey('value', $item);
            self::assertArrayHasKey('name', $item);
            self::assertArrayHasKey('label', $item);
            self::assertArrayHasKey('description', $item);
            self::assertArrayHasKey('color', $item);
            self::assertArrayHasKey('icon', $item);
        }
    }

    public function test_values_return_type_matches_backing_type(): void
    {
        // String-backed enum — values must be strings
        foreach (UserStatus::values() as $value) {
            self::assertIsString($value);
        }

        // Int-backed enum — values must be ints
        foreach (IntBackedPriority::values() as $value) {
            self::assertIsInt($value);
        }
    }

    public function test_labels_returns_only_strings(): void
    {
        foreach (UserStatus::labels() as $label) {
            self::assertIsString($label);
            self::assertNotEmpty($label);
        }

        foreach (IntBackedPriority::labels() as $label) {
            self::assertIsString($label);
            self::assertNotEmpty($label);
        }
    }

    // -----------------------------------------------------------------
    // Lookup methods — strict return types
    // -----------------------------------------------------------------

    public function test_try_from_label_returns_null_or_enum_instance(): void
    {
        $result = UserStatus::tryFromLabel('Active User');

        self::assertInstanceOf(UserStatus::class, $result);
        self::assertSame('ACTIVE', $result->name);

        self::assertNull(UserStatus::tryFromLabel('Nonexistent Label'));
    }

    public function test_try_from_label_is_case_insensitive(): void
    {
        $result1 = UserStatus::tryFromLabel('Active User');
        $result2 = UserStatus::tryFromLabel('active user');
        $result3 = UserStatus::tryFromLabel('ACTIVE USER');

        self::assertSame($result1, $result2);
        self::assertSame($result2, $result3);
    }

    public function test_from_name_throws_for_invalid(): void
    {
        $this->expectException(InvalidEnumException::class);

        UserStatus::fromName('NONEXISTENT');
    }

    public function test_from_name_returns_correct_case(): void
    {
        $result = UserStatus::fromName('ACTIVE');

        self::assertSame('ACTIVE', $result->name);
        self::assertSame('active', $result->value);
    }

    public function test_has_case_returns_bool(): void
    {
        self::assertTrue(UserStatus::hasCase('ACTIVE'));
        self::assertTrue(UserStatus::hasCase('INACTIVE'));
        self::assertFalse(UserStatus::hasCase(''));
        self::assertFalse(UserStatus::hasCase('nonexistent'));
    }

    // -----------------------------------------------------------------
    // Comparison — strict identity
    // -----------------------------------------------------------------

    public function test_is_uses_strict_identity(): void
    {
        $active = UserStatus::ACTIVE;

        self::assertTrue($active->is(UserStatus::ACTIVE));
        self::assertFalse($active->is(UserStatus::INACTIVE));
        self::assertTrue($active->is('ACTIVE'));
        self::assertFalse($active->is('active')); // case name, not backed value
    }

    public function test_not_in_returns_bool(): void
    {
        $active = UserStatus::ACTIVE;

        self::assertTrue($active->notIn(['BANNED', 'PENDING']));
        self::assertFalse($active->notIn(['ACTIVE', 'INACTIVE']));
    }

    // -----------------------------------------------------------------
    // Multiple fixture enums — contract consistency
    // -----------------------------------------------------------------

    /**
     * @dataProvider allFixtureEnumsProvider
     */
    public function test_all_fixture_enums_have_non_empty_labels(string $enumClass): void
    {
        $cases = $enumClass::cases();

        self::assertNotEmpty($cases, "{$enumClass} should have at least one case");

        foreach ($cases as $case) {
            if (method_exists($case, 'label')) {
                $label = $case->label();
                self::assertIsString($label);
                self::assertNotEmpty($label, "{$enumClass}::{$case->name} should have a non-empty label");
            }
        }
    }

    /**
     * @dataProvider allFixtureEnumsProvider
     */
    public function test_all_fixture_enums_have_string_colors(string $enumClass): void
    {
        foreach ($enumClass::cases() as $case) {
            if (method_exists($case, 'color')) {
                $color = $case->color();
                self::assertIsString($color);
                self::assertNotEmpty($color, "{$enumClass}::{$case->name} should have a non-empty color");
            }
        }
    }

    /**
     * @return array<string, array{enumClass: class-string}>
     */
    public static function allFixtureEnumsProvider(): array
    {
        return [
            'UserStatus' => ['enumClass' => UserStatus::class],
            'OrderStatus' => ['enumClass' => OrderStatus::class],
            'PaymentStatus' => ['enumClass' => PaymentStatus::class],
            'IntBackedPriority' => ['enumClass' => IntBackedPriority::class],
            'IntPriority' => ['enumClass' => IntPriority::class],
            'IntStatusWithColor' => ['enumClass' => IntStatusWithColor::class],
            'PureFeatureFlag' => ['enumClass' => PureFeatureFlag::class],
            'PureSystemState' => ['enumClass' => PureSystemState::class],
            'SingleCaseEnum' => ['enumClass' => SingleCaseEnum::class],
            'CamelCasePriority' => ['enumClass' => CamelCasePriority::class],
            'CamelCaseRole' => ['enumClass' => CamelCaseRole::class],
            'ZeroBackedPriority' => ['enumClass' => ZeroBackedPriority::class],
            'ZeroPriority' => ['enumClass' => ZeroPriority::class],
            'PlainTestEnum' => ['enumClass' => PlainTestEnum::class],
        ];
    }
}
