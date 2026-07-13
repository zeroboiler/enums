<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumTestGenerator', function (): void {
    it('generates valid PHP starting with opening tag', function (): void {
        $content = EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toStartWith('<?php');
    });

    it('includes declare strict types', function (): void {
        $content = EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain('declare(strict_types=1);');
    });

    it('uses the correct enum class in use statement', function (): void {
        $content = EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain('use '.UserStatus::class.';');
    });

    it('wraps tests in a describe block named after the enum', function (): void {
        $content = EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain("describe('UserStatus enum'");
    });

    it('generates per-case test blocks for each case', function (): void {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        foreach (OrderStatus::cases() as $case) {
            expect($content)->toContain("it('has a label for case {$case->name}'");
            expect($content)->toContain("it('has a color for case {$case->name}'");
        }
    });

    it('generates forSelect and forApi test assertions', function (): void {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        expect($content)->toContain('forSelect()');
        expect($content)->toContain('forApi()');
    });

    it('does not contain stray namespace transformation artifacts', function (): void {
        $content = EnumTestGenerator::generate(UserStatus::class);

        // The old bug replaced App\ with Tests\ in a discarded str_replace call.
        // Ensure no leftover references to "Tests\" appear in the generated output.
        expect($content)->not->toContain('Tests\\');
    });

    it('generates unique values assertion', function (): void {
        $content = EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain('toBeUnique()');
    });
});
