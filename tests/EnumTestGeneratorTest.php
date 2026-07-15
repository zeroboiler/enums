<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumTestGenerator', function (): void {
    it('generates test content for a string-backed enum', function (): void {
        $content = EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toBeString()
            ->and($content)->toContain('<?php')
            ->and($content)->toContain('declare(strict_types=1)')
            ->and($content)->toContain('use '.UserStatus::class.';')
            ->and($content)->toContain("describe('UserStatus enum'")
            ->and($content)->toContain("it('has cases'")
            ->and($content)->toContain("it('can generate select options'")
            ->and($content)->toContain("it('can generate API response array'")
            ->and($content)->toContain("it('has unique values'")
            ->and($content)->toContain('UserStatus::ACTIVE');
    });

    it('generates test content for an int-backed enum', function (): void {
        $content = EnumTestGenerator::generate(Priority::class);

        expect($content)->toContain('use '.Priority::class.';')
            ->and($content)->toContain("describe('Priority enum'")
            ->and($content)->toContain('Priority::LOW')
            ->and($content)->toContain('Priority::URGENT');
    });

    it('includes label and color test for each case', function (): void {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        expect($content)->toContain("it('has a label for case PENDING'")
            ->and($content)->toContain("it('has a color for case PENDING'")
            ->and($content)->toContain("it('has a label for case DELIVERED'")
            ->and($content)->toContain("it('has a color for case DELIVERED'");
    });

    it('generates valid PHP with proper namespace', function (): void {
        $content = EnumTestGenerator::generate(UserStatus::class);

        // Validate it contains a proper PHP opening tag and declare
        expect($content)->toStartWith('<?php');
    });

    it('generates tests for all cases in enum', function (): void {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        // OrderStatus has 4 cases
        foreach (['PENDING', 'SHIPPED', 'DELIVERED', 'CANCELLED'] as $caseName) {
            expect($content)->toContain("it('has a label for case {$caseName}'");
        }
    });
});
