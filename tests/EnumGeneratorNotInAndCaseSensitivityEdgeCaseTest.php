<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

/**
 * Tests for EnumTestGenerator output: notIn() edge cases,
 * case-sensitivity strictness, and generated code completeness.
 */
enum FixtureForGeneratorTest: string
{
    use HasEnumMetadata;

    #[Label('Active User')]
    #[Color('success')]
    #[Description('Account is active')]
    #[Icon('heroicon-o-check')]
    case ACTIVE = 'active';

    #[Label('Banned User')]
    #[Color('danger')]
    #[Description('Account is banned')]
    case BANNED = 'banned';

    #[Label('Pending User')]
    #[Color('warning')]
    case PENDING = 'pending';
}

describe('EnumTestGenerator notIn and case-sensitivity edge cases', function () {
    it('notIn() returns true when case is completely absent from list', function () {
        expect(FixtureForGeneratorTest::ACTIVE->notIn(['BANNED', 'PENDING']))->toBeTrue();
    });

    it('notIn() returns false when case is present as instance', function () {
        expect(FixtureForGeneratorTest::ACTIVE->notIn([FixtureForGeneratorTest::ACTIVE]))->toBeFalse();
    });

    it('notIn() returns false when case is present as string', function () {
        expect(FixtureForGeneratorTest::ACTIVE->notIn(['ACTIVE']))->toBeFalse();
    });

    it('notIn() returns false with mixed instances and strings', function () {
        expect(
            FixtureForGeneratorTest::ACTIVE->notIn([FixtureForGeneratorTest::BANNED, 'ACTIVE'])
        )->toBeFalse();
    });

    it('notIn() returns true with only non-matching mixed input', function () {
        expect(
            FixtureForGeneratorTest::ACTIVE->notIn([FixtureForGeneratorTest::BANNED, 'PENDING'])
        )->toBeTrue();
    });

    it('notIn() handles empty array', function () {
        expect(FixtureForGeneratorTest::ACTIVE->notIn([]))->toBeTrue();
    });

    it('fromName() is case-sensitive — lowercase throws', function () {
        expect(
            fn () => FixtureForGeneratorTest::fromName('active')
        )->toThrow(InvalidEnumException::class);
    });

    it('fromName() is case-sensitive — uppercase works', function () {
        $case = FixtureForGeneratorTest::fromName('ACTIVE');
        expect($case)->toBe(FixtureForGeneratorTest::ACTIVE);
    });

    it('fromName() is case-sensitive — mixed case throws', function () {
        expect(
            fn () => FixtureForGeneratorTest::fromName('Active')
        )->toThrow(InvalidEnumException::class);
    });

    it('in() is case-sensitive — lowercase string does not match', function () {
        expect(FixtureForGeneratorTest::ACTIVE->in(['active']))->toBeFalse();
    });

    it('in() is case-sensitive — uppercase string matches', function () {
        expect(FixtureForGeneratorTest::ACTIVE->in(['ACTIVE']))->toBeTrue();
    });
});

describe('EnumTestGenerator generated code structure', function () {
    it('generates valid PHP for a 3-case string-backed enum', function () {
        $code = EnumTestGenerator::generate(FixtureForGeneratorTest::class);

        expect($code)->toBeString();
        expect($code)->toContain('declare(strict_types=1)');
        expect($code)->toContain('describe');
        expect($code)->toContain('it(');
    });

    it('generated code includes notIn() test with mixed instances and strings', function () {
        $code = EnumTestGenerator::generate(FixtureForGeneratorTest::class);

        expect($code)->toContain('notIn');
    });

    it('generated code includes fromName() throw test', function () {
        $code = EnumTestGenerator::generate(FixtureForGeneratorTest::class);

        expect($code)->toContain('fromName');
        expect($code)->toContain('toThrow(InvalidEnumException');
    });

    it('generated code includes per-case label, color, icon, description tests', function () {
        $code = EnumTestGenerator::generate(FixtureForGeneratorTest::class);

        expect($code)->toContain('has a non-empty label for case ACTIVE');
        expect($code)->toContain('has a string color for case ACTIVE');
        expect($code)->toContain('icon for case ACTIVE');
        expect($code)->toContain('description for case ACTIVE');
    });

    it('generated code includes is() case-sensitive string comparison test', function () {
        $code = EnumTestGenerator::generate(FixtureForGeneratorTest::class);

        expect($code)->toContain('case-sensitive string comparison');
    });

    it('generated code includes tryFromLabel case-insensitive test', function () {
        $code = EnumTestGenerator::generate(FixtureForGeneratorTest::class);

        expect($code)->toContain('tryFromLabel lookup is case-insensitive');
    });
});
