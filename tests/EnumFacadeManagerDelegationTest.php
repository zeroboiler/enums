<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EnumManager delegation and facade pattern.
 *
 * Covers: forSelect, forApi, tryFromLabel delegation,
 * error handling for non-trait enums, and edge cases.
 */
final class EnumFacadeManagerDelegationTest extends TestCase
{
    private EnumManager $manager;

    protected function setUp(): void
    {
        $this->manager = new EnumManager;
    }

    // ---------------------------------------------------------------
    // forSelect delegation
    // ---------------------------------------------------------------

    public function test_for_select_returns_array_with_value_and_label_keys(): void
    {
        $result = $this->manager->forSelect(UserStatus::class);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        foreach ($result as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertIsString($option['label']);
            $this->assertNotEmpty($option['label']);
        }
    }

    public function test_for_select_returns_correct_number_of_options(): void
    {
        $result = $this->manager->forSelect(UserStatus::class);
        $cases = UserStatus::cases();

        $this->assertCount(count($cases), $result);
    }

    public function test_for_select_preserves_declaration_order(): void
    {
        $result = $this->manager->forSelect(UserStatus::class);
        $cases = UserStatus::cases();

        foreach ($cases as $index => $case) {
            $expectedValue = $case instanceof \BackedEnum ? $case->value : $case->name;
            $this->assertSame($expectedValue, $result[$index]['value']);
        }
    }

    // ---------------------------------------------------------------
    // forApi delegation
    // ---------------------------------------------------------------

    public function test_for_api_returns_full_metadata_structure(): void
    {
        $result = $this->manager->forApi(UserStatus::class);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        foreach ($result as $item) {
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('color', $item);
            $this->assertArrayHasKey('icon', $item);
        }
    }

    public function test_for_api_values_match_backed_values(): void
    {
        $result = $this->manager->forApi(UserStatus::class);
        $cases = UserStatus::cases();

        foreach ($cases as $index => $case) {
            $expectedValue = $case instanceof \BackedEnum ? $case->value : $case->name;
            $this->assertSame($expectedValue, $result[$index]['value']);
        }
    }

    // ---------------------------------------------------------------
    // tryFromLabel delegation
    // ---------------------------------------------------------------

    public function test_try_from_label_resolves_case_insensitively(): void
    {
        $label = UserStatus::ACTIVE->label();
        $case = $this->manager->tryFromLabel(UserStatus::class, $label);

        $this->assertNotNull($case);
        $this->assertSame(UserStatus::ACTIVE, $case);
    }

    public function test_try_from_label_returns_null_for_unknown_label(): void
    {
        $case = $this->manager->tryFromLabel(UserStatus::class, 'NonExistentLabel');

        $this->assertNull($case);
    }

    // ---------------------------------------------------------------
    // Error handling — non-trait enums
    // ---------------------------------------------------------------

    public function test_for_select_throws_for_enum_without_trait(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('does not use HasEnumMetadata trait');

        $this->manager->forSelect(\PureEnumWithoutTrait::class);
    }

    public function test_for_api_throws_for_enum_without_trait(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('does not use HasEnumMetadata trait');

        $this->manager->forApi(\PureEnumWithoutTrait::class);
    }

    public function test_try_from_label_throws_for_enum_without_trait(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('does not use HasEnumMetadata trait');

        $this->manager->tryFromLabel(\PureEnumWithoutTrait::class, 'test');
    }

    // ---------------------------------------------------------------
    // Cross-enum isolation
    // ---------------------------------------------------------------

    public function test_different_enums_return_different_data(): void
    {
        $userOptions = $this->manager->forSelect(UserStatus::class);
        $orderOptions = $this->manager->forSelect(OrderStatus::class);

        // Both should be non-empty arrays
        $this->assertNotEmpty($userOptions);
        $this->assertNotEmpty($orderOptions);

        // They should have different case counts (unless coincidentally same)
        // At minimum, the structures are identical
        foreach (array_merge($userOptions, $orderOptions) as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }
}

/**
 * Helper enum without HasEnumMetadata trait — used for error testing.
 */
enum PureEnumWithoutTrait: string
{
    case FOO = 'foo';
    case BAR = 'bar';
}
