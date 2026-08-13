<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Support\EnumTestGenerator;

describe('EnumTestGenerator output validation', function () {
    it('generates valid PHP for a string-backed enum', function () {
        $output = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus::class);

        expect($output)->toBeString();
        expect($output)->toContain('<?php');
        expect($output)->toContain('declare(strict_types=1)');
        expect($output)->toContain('describe(');
        expect($output)->toContain('it(');
        expect($output)->toContain('PaymentStatus');
        expect($output)->toContain('forSelect');
        expect($output)->toContain('forApi');
        expect($output)->toContain('fromName');
        expect($output)->toContain('tryFromLabel');
        expect($output)->toContain('hasCase');
        expect($output)->toContain('values()');
        expect($output)->toContain('labels()');
        expect($output)->toContain('InvalidEnumException');
    });

    it('generates per-case tests for all cases', function () {
        $output = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus::class);

        // Should have per-case tests for each case
        foreach (\ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus::cases() as $case) {
            expect($output)->toContain("case {$case->name}");
            expect($output)->toContain("label for case {$case->name}");
            expect($output)->toContain("color for case {$case->name}");
            expect($output)->toContain("icon for case {$case->name}");
            expect($output)->toContain("description for case {$case->name}");
        }
    });

    it('generates comparison tests when enum has at least 2 cases', function () {
        $output = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus::class);

        expect($output)->toContain('is() comparison');
        expect($output)->toContain('isNot() comparison');
        expect($output)->toContain('in() group matching');
        expect($output)->toContain('notIn() group exclusion');
        expect($output)->toContain('tryFromLabel reverse lookup');
        expect($output)->toContain('case-insensitive');
    });

    it('generates int-specific backing type test for int-backed enums', function () {
        $output = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority::class);

        expect($output)->toContain('values() returns int backed values');
        expect($output)->toContain('->each->toBeInt()');
    });

    it('generates pure-enum case name test for pure enums', function () {
        $output = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag::class);

        expect($output)->toContain('values() returns case names for pure enum');
    });

    it('generates single-case enum without comparison tests', function () {
        $output = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum::class);

        // Single-case enum should have basic tests but NOT comparison tests
        expect($output)->toContain('has cases');
        expect($output)->not->toContain('is() comparison');
    });

    it('produces syntactically valid PHP that can be tokenized', function () {
        $output = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\OrderStatus::class);

        // Verify the output can be tokenized as valid PHP
        $tokens = token_get_all($output);
        expect($tokens)->toBeArray();
        expect($tokens)->not->toBeEmpty();

        // Check for balanced braces
        $openCount = substr_count($output, '{');
        $closeCount = substr_count($output, '}');
        expect($openCount)->toBe($closeCount);
    });

    it('includes fromName throw test and hasCase existence test', function () {
        $output = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        expect($output)->toContain("fromName() throws InvalidEnumException");
        expect($output)->toContain('hasCase check');
        expect($output)->toContain('NON_EXISTENT');
    });

    it('generates API response structure validation', function () {
        $output = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\TicketStatus::class);

        expect($output)->toContain('API response');
        expect($output)->toContain("'value', 'name', 'label', 'description', 'color', 'icon'");
        expect($output)->toContain('color is always a string');
    });
});
