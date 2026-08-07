<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use PHPUnit\Framework\TestCase;

/**
 * Tests for enum values() and labels() bulk method edge cases.
 *
 * Ensures:
 * - values() returns backed values for backed enums, names for pure enums
 * - labels() returns auto-generated labels in declaration order
 * - Empty and single-case enums behave correctly
 * - Integer-backed enums return int values (not strings)
 */
final class EnumBulkMethodsEdgeCaseTest extends TestCase
{
    // ---------------------------------------------------------------
    // values() — string-backed enum
    // ---------------------------------------------------------------

    public function test_values_returns_string_backed_values(): void
    {
        $values = TicketStatus::values();

        $this->assertIsArray($values);
        $this->assertCount(count(TicketStatus::cases()), $values);

        foreach ($values as $value) {
            $this->assertIsString($value);
        }
    }

    public function test_values_preserves_declaration_order(): void
    {
        $values = TicketStatus::values();
        $cases = TicketStatus::cases();

        foreach ($cases as $index => $case) {
            $this->assertSame($case->value, $values[$index]);
        }
    }

    public function test_values_are_unique(): void
    {
        $values = TicketStatus::values();
        $unique = array_unique($values);

        $this->assertCount(count($values), count($unique), 'values() should not contain duplicates');
    }

    // ---------------------------------------------------------------
    // values() — int-backed enum
    // ---------------------------------------------------------------

    public function test_values_returns_int_for_int_backed_enum(): void
    {
        $values = Priority::values();

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);

        foreach ($values as $value) {
            $this->assertIsInt($value);
        }
    }

    // ---------------------------------------------------------------
    // values() — pure enum (no backing type)
    // ---------------------------------------------------------------

    public function test_values_returns_case_names_for_pure_enum(): void
    {
        $values = RequestState::values();

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);

        foreach ($values as $value) {
            $this->assertIsString($value);
            $this->assertSame(strtoupper($value), $value, 'Pure enum values should be uppercase case names');
        }
    }

    // ---------------------------------------------------------------
    // labels() — all enum types
    // ---------------------------------------------------------------

    public function test_labels_returns_strings_for_string_backed_enum(): void
    {
        $labels = TicketStatus::labels();

        $this->assertIsArray($labels);
        $this->assertCount(count(TicketStatus::cases()), $labels);

        foreach ($labels as $label) {
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_labels_returns_strings_for_int_backed_enum(): void
    {
        $labels = Priority::labels();

        $this->assertIsArray($labels);
        $this->assertCount(count(Priority::cases()), $labels);

        foreach ($labels as $label) {
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_labels_preserves_declaration_order(): void
    {
        $labels = TicketStatus::labels();
        $cases = TicketStatus::cases();

        $this->assertCount(count($cases), $labels);

        // Each label should correspond to a case in declaration order
        foreach ($cases as $index => $case) {
            $this->assertSame($case->label(), $labels[$index]);
        }
    }

    public function test_labels_and_values_have_same_count(): void
    {
        $values = TicketStatus::values();
        $labels = TicketStatus::labels();

        $this->assertCount(count($values), $labels);
    }

    // ---------------------------------------------------------------
    // Single-case enum
    // ---------------------------------------------------------------

    public function test_values_for_single_case_enum(): void
    {
        $values = SingleCaseFixture::values();

        $this->assertCount(1, $values);
        $this->assertSame('only', $values[0]);
    }

    public function test_labels_for_single_case_enum(): void
    {
        $labels = SingleCaseFixture::labels();

        $this->assertCount(1, $labels);
        $this->assertSame('Only', $labels[0]);
    }

    public function test_for_select_for_single_case_enum(): void
    {
        $options = SingleCaseFixture::forSelect();

        $this->assertCount(1, $options);
        $this->assertSame('only', $options[0]['value']);
        $this->assertSame('Only', $options[0]['label']);
    }

    public function test_for_api_for_single_case_enum(): void
    {
        $api = SingleCaseFixture::forApi();

        $this->assertCount(1, $api);
        $this->assertSame('only', $api[0]['value']);
        $this->assertSame('ONLY', $api[0]['name']);
        $this->assertSame('Only', $api[0]['label']);
        $this->assertArrayHasKey('description', $api[0]);
        $this->assertArrayHasKey('color', $api[0]);
        $this->assertArrayHasKey('icon', $api[0]);
    }
}

/**
 * Fixture: single-case string-backed enum for edge case testing.
 */
enum SingleCaseFixture: string
{
    use HasEnumMetadata;

    case ONLY = 'only';
}
