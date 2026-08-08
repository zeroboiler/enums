<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumTestGenerator', function (): void {
    it('generates test content for a string-backed enum', function (): void {
        $content = EnumTestGenerator::generate(UserStatus::class);

        expect($content)
            ->toContain('<?php')
            ->toContain('declare(strict_types=1)')
            ->toContain('use '.UserStatus::class.';')
            ->toContain("describe('UserStatus enum'")
            ->toContain('UserStatus::cases()')
            ->toContain('UserStatus::forSelect()')
            ->toContain('UserStatus::forApi()');
    });

    it('generates test content for an int-backed enum', function (): void {
        $content = EnumTestGenerator::generate(Priority::class);

        expect($content)
            ->toContain('Priority::cases()')
            ->toContain('Priority::forSelect()')
            ->toContain('Priority::forApi()')
            ->toContain("describe('Priority enum'");
    });

    it('generates test content for a pure (non-backed) enum', function (): void {
        $content = EnumTestGenerator::generate(RequestState::class);

        expect($content)
            ->toContain('RequestState::cases()')
            ->toContain("describe('RequestState enum'");
    });

    it('generates a case test for each enum case', function (): void {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        expect($content)
            ->toContain('OrderStatus::PENDING->label()')
            ->toContain('OrderStatus::SHIPPED->label()')
            ->toContain('OrderStatus::DELIVERED->label()')
            ->toContain('OrderStatus::CANCELLED->label()')
            ->toContain('OrderStatus::PENDING->color()')
            ->toContain('OrderStatus::SHIPPED->color()');
    });

    it('includes unique values test', function (): void {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        expect($content)
            ->toContain("'has unique values'")
            ->toContain("array_column(OrderStatus::forSelect(), 'value')")
            ->toContain('toBeUnique()');
    });

    it('includes forSelect key assertions', function (): void {
        $content = EnumTestGenerator::generate(Priority::class);

        expect($content)
            ->toContain("toHaveKeys(['value', 'label'])");
    });

    it('includes forApi key assertions', function (): void {
        $content = EnumTestGenerator::generate(UserStatus::class);

        expect($content)
            ->toContain("toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon'])");
    });

    it('generates valid PHP with proper open tag', function (): void {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        expect($content)->toStartWith('<?php')
            ->and(substr($content, 5, 1))->toBe("\n");
    });

    it('generates label and color tests for every case', function (): void {
        $content = EnumTestGenerator::generate(Priority::class);

        foreach (['LOW', 'MEDIUM', 'HIGH', 'URGENT'] as $caseName) {
            expect($content)
                ->toContain("Priority::{$caseName}->label()")
                ->toContain("Priority::{$caseName}->color()");
        }
    });
});

describe('EnumTestGenerator output validity', function (): void {
    it('produces syntactically valid PHP', function (): void {
        $content = EnumTestGenerator::generate(OrderStatus::class);
        $tmpFile = sys_get_temp_dir().'/enum_test_gen_'.uniqid().'.php';
        file_put_contents($tmpFile, $content);

        $output = shell_exec('php -l '.escapeshellarg($tmpFile).' 2>&1');
        unlink($tmpFile);

        expect($output)->toContain('No syntax errors');
    });

    it('produces valid PHP for int-backed enum', function (): void {
        $content = EnumTestGenerator::generate(Priority::class);
        $tmpFile = sys_get_temp_dir().'/enum_test_gen_int_'.uniqid().'.php';
        file_put_contents($tmpFile, $content);

        $output = shell_exec('php -l '.escapeshellarg($tmpFile).' 2>&1');
        unlink($tmpFile);

        expect($output)->toContain('No syntax errors');
    });

    it('produces valid PHP for pure (non-backed) enum', function (): void {
        $content = EnumTestGenerator::generate(RequestState::class);
        $tmpFile = sys_get_temp_dir().'/enum_test_gen_pure_'.uniqid().'.php';
        file_put_contents($tmpFile, $content);

        $output = shell_exec('php -l '.escapeshellarg($tmpFile).' 2>&1');
        unlink($tmpFile);

        expect($output)->toContain('No syntax errors');
    });
});
