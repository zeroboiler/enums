<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;

describe('EnumTestGenerator', function (): void {
    it('generates valid PHP for string-backed enums', function (): void {
        $content = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        expect($content)->toBeString();
        expect($content)->toContain('declare(strict_types=1)');
        expect($content)->toContain('describe(');
        expect($content)->toContain('it(');
        expect($content)->toContain('forSelect');
        expect($content)->toContain('forApi');
        expect($content)->toContain('tryFromName');
        expect($content)->toContain('fromName');
        expect($content)->toContain('hasCase');
        expect($content)->toContain('InvalidEnumException');
    });

    it('generates valid PHP for int-backed enums', function (): void {
        $content = EnumTestGenerator::generate(IntStatusWithColor::class);

        expect($content)->toBeString();
        expect($content)->toContain('values() returns int backed values');
        expect($content)->toContain('expect(\\$values)->each->toBeInt()');
    });

    it('generates valid PHP for pure enums', function (): void {
        $content = EnumTestGenerator::generate(PureFeatureFlag::class);

        expect($content)->toBeString();
        expect($content)->toContain('values() returns case names for pure enum');
        expect($content)->toContain('UnitEnum');
    });

    it('generates per-case accessor tests for each case', function (): void {
        $content = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        foreach (\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::cases() as $case) {
            expect($content)->toContain("case {$case->name}");
            expect($content)->toContain("has a non-empty label for case {$case->name}");
            expect($content)->toContain("has a string color for case {$case->name}");
            expect($content)->toContain("returns a string or null icon for case {$case->name}");
            expect($content)->toContain("returns a string or null description for case {$case->name}");
        }
    });

    it('generates comparison tests when enum has 2+ cases', function (): void {
        $content = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        $cases = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::cases();

        if (count($cases) >= 2) {
            expect($content)->toContain('supports is() comparison with instance');
            expect($content)->toContain('supports is() comparison with string name');
            expect($content)->toContain('supports is() case-sensitive string comparison');
            expect($content)->toContain('supports isNot() comparison');
            expect($content)->toContain('supports in() group matching with instances');
            expect($content)->toContain('supports in() group matching with string names');
            expect($content)->toContain('supports in() with mixed instances and strings');
            expect($content)->toContain('supports tryFromLabel reverse lookup');
            expect($content)->toContain('returns null for non-existent label in tryFromLabel');
            expect($content)->toContain('tryFromLabel lookup is case-insensitive');
        }
    });

    it('includes correct case count assertion', function (): void {
        $content = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        $count = count(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::cases());

        expect($content)->toContain("toHaveCount({$count})");
    });

    it('generates valid PHP syntax (opening tag and declare)', function (): void {
        $content = EnumTestGenerator::generate(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        expect(str_starts_with(trim($content), '<?php'))->toBeTrue();
        expect($content)->toContain("declare(strict_types=1)");
    });

    it('throws ReflectionException for non-existent enum class', function (): void {
        expect(fn (): string => EnumTestGenerator::generate('NonExistent\EnumClass'))
            ->toThrow(\ReflectionException::class);
    });
});
