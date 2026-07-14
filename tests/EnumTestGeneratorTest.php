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
    it('generates valid PHP for a string-backed enum', function (): void {
        $output = EnumTestGenerator::generate(UserStatus::class);

        expect($output)->toBeString()
            ->toStartWith('<?php')
            ->toContain('use '.UserStatus::class.';')
            ->toContain("describe('UserStatus enum'")
            ->toContain("it('has cases'")
            ->toContain("it('can generate select options'")
            ->toContain("it('can generate API response array'")
            ->toContain("it('has unique values'");
    });

    it('generates case-specific tests for each enum case', function (): void {
        $output = EnumTestGenerator::generate(UserStatus::class);

        foreach (UserStatus::cases() as $case) {
            expect($output)->toContain("it('has a label for case {$case->name}'")
                ->and($output)->toContain("it('has a color for case {$case->name}'");
        }
    });

    it('generates valid PHP for an int-backed enum', function (): void {
        $output = EnumTestGenerator::generate(Priority::class);

        expect($output)->toBeString()
            ->toStartWith('<?php')
            ->toContain('use '.Priority::class.';')
            ->toContain("describe('Priority enum'");
    });

    it('generates valid PHP for a minimal enum without attributes', function (): void {
        $output = EnumTestGenerator::generate(OrderStatus::class);

        expect($output)->toBeString()
            ->toStartWith('<?php')
            ->toContain('use '.OrderStatus::class.';')
            ->toContain("describe('OrderStatus enum'");
    });

    it('includes declare strict types', function (): void {
        $output = EnumTestGenerator::generate(UserStatus::class);

        expect($output)->toContain('declare(strict_types=1);');
    });

    it('does not apply App-to-Tests namespace transformation', function (): void {
        // Regression test for issue #5: str_replace result was discarded.
        // The dead code tried to convert App\ to Tests\ in the namespace.
        // Verify no transformed namespace line exists in output.
        $output = EnumTestGenerator::generate(UserStatus::class);

        // The generated file should NOT contain a namespace declaration
        // with Tests\ prefix (the dead code's intended but never-applied transformation)
        expect($output)->not->toContain('namespace Tests\\');
    });

    it('generates syntactically valid PHP code', function (): void {
        $output = EnumTestGenerator::generate(UserStatus::class);

        // Write to temp file and check syntax
        $tempFile = sys_get_temp_dir().'/enum_test_'.uniqid().'.php';
        file_put_contents($tempFile, $output);

        $result = shell_exec("php -l {$tempFile} 2>&1");
        unlink($tempFile);

        expect($result)->toContain('No syntax errors');
    });
});
