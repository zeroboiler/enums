<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumTestGenerator output contract', function () {

    it('generates valid PHP for a string-backed enum with HasEnumMetadata', function () {
        $php = EnumTestGenerator::generate(UserStatus::class);

        // Must contain the class use statement
        expect($php)->toContain('use '.UserStatus::class.';');

        // Must contain declare(strict_types=1)
        expect($php)->toContain('declare(strict_types=1)');

        // Must contain InvalidEnumException import
        expect($php)->toContain('use ZeroBoiler\\Enums\\Exceptions\\InvalidEnumException;');

        // Must contain describe block
        expect($php)->toContain("describe('UserStatus enum'");

        // Must contain the expected number of case-specific label tests
        // (2 per case: non-empty label + string color)
        $cases = UserStatus::cases();
        $caseCount = count($cases);
        // Each case gets 4 test blocks: label, color, icon, description
        expect(substr_count($php, \"it('has a non-empty label for case\"))->toBe($caseCount);
        expect(substr_count($php, \"it('has a string color for case\"))->toBe($caseCount);
        expect(substr_count($php, \"it('returns a string or null icon for case\"))->toBe($caseCount);
        expect(substr_count($php, \"it('returns a string or null description for case\"))->toBe($caseCount);
    });

    it('generates string-backed value type test for string-backed enums', function () {
        $php = EnumTestGenerator::generate(MixedAttributeStatus::class);

        // Should assert string backed values
        expect($php)->toContain("values() returns string backed values");
        expect($php)->toContain('->each->toBeString()');
    });

    it('generates int-backed value type test for int-backed enums', function () {
        $php = EnumTestGenerator::generate(IntBackedPriority::class);

        // Should assert int backed values
        expect($php)->toContain("values() returns int backed values");
        expect($php)->toContain('->each->toBeInt()');
    });

    it('generates pure enum value test for pure enums', function () {
        $php = EnumTestGenerator::generate(PureSystemState::class);

        // Should assert case names for pure enums
        expect($php)->toContain("values() returns case names for pure enum");
    });

    it('generates comparison tests when enum has 2+ cases', function () {
        $php = EnumTestGenerator::generate(UserStatus::class);

        // Comparison tests (only generated when caseCount >= 2)
        expect($php)->toContain("supports is() comparison with instance");
        expect($php)->toContain("supports is() comparison with string name");
        expect($php)->toContain("supports isNot() comparison");
        expect($php)->toContain("supports in() group matching with instances");
        expect($php)->toContain("supports in() group matching with string names");
        expect($php)->toContain("supports in() with mixed instances and strings");
        expect($php)->toContain("supports notIn() group exclusion with instances");
        expect($php)->toContain("supports notIn() group exclusion with string names");
        expect($php)->toContain("supports notIn() with mixed instances and strings");
        expect($php)->toContain("supports tryFromLabel reverse lookup");
        expect($php)->toContain("returns null for non-existent label in tryFromLabel");
        expect($php)->toContain("tryFromLabel lookup is case-insensitive");
        expect($php)->toContain("fromName() rejects case-insensitive name lookup");
    });

    it('generates bulk method tests for all enums', function () {
        $php = EnumTestGenerator::generate(UserStatus::class);

        expect($php)->toContain("has cases");
        expect($php)->toContain("has the expected number of cases");
        expect($php)->toContain("can generate select options");
        expect($php)->toContain("select option values are unique");
        expect($php)->toContain("select option labels are non-empty strings");
        expect($php)->toContain("can generate API response array");
        expect($php)->toContain("API response color is always a string");
        expect($php)->toContain("values() returns correct count and types");
        expect($php)->toContain("labels() returns correct count and non-empty strings");
        expect($php)->toContain("supports tryFromName lookup");
        expect($php)->toContain("fromName() returns the correct case");
        expect($php)->toContain("fromName() throws InvalidEnumException for non-existent name");
        expect($php)->toContain("supports hasCase check");
    });

    it('generates valid PHP syntax (balanced braces)', function () {
        $php = EnumTestGenerator::generate(UserStatus::class);

        // Check that all { have matching }
        $openCount = substr_count($php, '{');
        $closeCount = substr_count($php, '}');
        expect($openCount)->toBe($closeCount);
    });

    it('generates first case name in fromName and hasCase tests', function () {
        $php = EnumTestGenerator::generate(MixedAttributeStatus::class);

        // First case is ACTIVE
        expect($php)->toContain("hasCase('ACTIVE')");
        expect($php)->toContain("fromName('ACTIVE')");
        expect($php)->toContain("tryFromName('ACTIVE')");
    });
});

describe('EnumCast serialize() method contract', function () {
    it('serializes BackedEnum to its backed value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->serialize($model, 'status', UserStatus::ACTIVE, []);
        expect($result)->toBe('active');
    });

    it('serializes int-backed enum to its int value', function () {
        $cast = new EnumCast(IntBackedPriority::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        // HIGH = 3 (assuming; test the contract, not the specific value)
        $case = IntBackedPriority::cases()[0];
        $result = $cast->serialize($model, 'priority', $case, []);
        expect($result)->toBeInt();
    });

    it('passes through string values unchanged', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->serialize($model, 'status', 'active', []);
        expect($result)->toBe('active');
    });

    it('passes through int values unchanged', function () {
        $cast = new EnumCast(IntBackedPriority::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->serialize($model, 'priority', 1, []);
        expect($result)->toBe(1);
    });

    it('returns null for null values', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->serialize($model, 'status', null, []);
        expect($result)->toBeNull();
    });
});
