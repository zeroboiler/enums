<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Support;

use BackedEnum;
use ReflectionEnum;
use UnitEnum;

/**
 * Utility for generating Pest tests for enums.
 *
 * @internal Not part of the public API — do not use directly.
 *           Used by the `zeroboiler:enum-test` artisan command.
 *
 * Produces a comprehensive Pest test file covering:
 * - Case existence and count
 * - Bulk methods (forSelect, forApi, values, labels)
 * - Uniqueness of backed values
 * - Per-case label, color, icon, description accessors
 * - Comparison methods (is, isNot, in) with instances and strings
 * - Reverse lookups (tryFromLabel, tryFromName, fromName, hasCase)
 * - fromName() throw behavior for invalid names
 * - Type consistency checks (values/labels return types)
 */
final class EnumTestGenerator
{
    /**
     * Generate test file content for an enum class.
     *
     * Produces a complete Pest test file with tests for each case's label,
     * color, icon, and description, plus bulk method tests (forSelect, forApi,
     * uniqueness), comparison methods, reverse lookups, and edge cases.
     *
     * @param  class-string<UnitEnum>  $enumClass  Fully-qualified enum class name
     *
     * @return string Complete PHP test file content ready to write to disk
     *
     * @throws \ReflectionException If the class does not exist or is not an enum
     */
    public static function generate(string $enumClass): string
    {
        $reflection = new ReflectionEnum($enumClass);
        $shortName = $reflection->getShortName();
        $cases = $enumClass::cases();
        $caseCount = count($cases);
        $isBacked = $reflection->isBacked();
        $backingType = $isBacked ? $reflection->getBackingType()?->getName() : null;

        $caseTests = '';
        foreach ($cases as $case) {
            $caseName = $case->name;
            $caseTests .= <<<PHP

it('has a non-empty label for case {$caseName}', function () {
    expect({$shortName}::{$caseName}->label())->toBeString()->not->toBeEmpty();
});

it('has a string color for case {$caseName}', function () {
    expect({$shortName}::{$caseName}->color())->toBeString();
});

it('returns a string or null icon for case {$caseName}', function () {
    \\\$icon = {$shortName}::{$caseName}->icon();
    expect(\\\$icon)->toBeNull()->or()->toBeString();
});

it('returns a string or null description for case {$caseName}', function () {
    \\\$desc = {$shortName}::{$caseName}->description();
    expect(\\\$desc)->toBeNull()->or()->toBeString();
});

PHP;
        }

        // Generate comparison tests for the first two cases
        $comparisonTests = '';
        if ($caseCount >= 2) {
            $firstCase = $cases[0]->name;
            $secondCase = $cases[1]->name;
            $comparisonTests = <<<PHP

it('supports is() comparison with instance', function () {
    expect({$shortName}::{$firstCase}->is({$shortName}::{$firstCase}))->toBeTrue();
    expect({$shortName}::{$firstCase}->is({$shortName}::{$secondCase}))->toBeFalse();
});

it('supports is() comparison with string name', function () {
    expect({$shortName}::{$firstCase}->is('{$firstCase}'))->toBeTrue();
    expect({$shortName}::{$firstCase}->is('{$secondCase}'))->toBeFalse();
});

it('supports is() case-sensitive string comparison', function () {
    expect({$shortName}::{$firstCase}->is('{$firstCase}'))->toBeTrue();
    expect({$shortName}::{$firstCase}->is(strtolower('{$firstCase}')))->toBeFalse();
});

it('supports isNot() comparison', function () {
    expect({$shortName}::{$firstCase}->isNot({$shortName}::{$secondCase}))->toBeTrue();
    expect({$shortName}::{$firstCase}->isNot({$shortName}::{$firstCase}))->toBeFalse();
});

it('supports in() group matching with instances', function () {
    expect({$shortName}::{$firstCase}->in([{$shortName}::{$firstCase}, {$shortName}::{$secondCase}]))->toBeTrue();
    expect({$shortName}::{$firstCase}->in([{$shortName}::{$secondCase}]))->toBeFalse();
});

it('supports in() group matching with string names', function () {
    expect({$shortName}::{$firstCase}->in(['{$firstCase}', '{$secondCase}']))->toBeTrue();
    expect({$shortName}::{$firstCase}->in(['{$secondCase}']))->toBeFalse();
});

it('supports in() with mixed instances and strings', function () {
    expect({$shortName}::{$firstCase}->in([{$shortName}::{$firstCase}, '{$secondCase}']))->toBeTrue();
});

it('supports tryFromLabel reverse lookup', function () {
    \\\$case = {$shortName}::tryFromLabel({$shortName}::{$firstCase}->label());
    expect(\\\$case)->toBeInstanceOf({$shortName}::class);
    expect(\\\$case?->name)->toBe('{$firstCase}');
});

it('returns null for non-existent label in tryFromLabel', function () {
    expect({$shortName}::tryFromLabel('non-existent-label-xyz'))->toBeNull();
});

it('tryFromLabel lookup is case-insensitive', function () {
    \\\$label = {$shortName}::{$firstCase}->label();
    expect({$shortName}::tryFromLabel(strtolower(\\\$label)))->toBeInstanceOf({$shortName}::class);
});

PHP;
        }

        // Backing-type specific tests
        $backingTests = '';
        if ($isBacked && $backingType === 'int') {
            $backingTests = <<<PHP

it('values() returns int backed values', function () {
    \\\$values = {$shortName}::values();
    expect(\\\$values)->each->toBeInt();
});

PHP;
        } elseif ($isBacked && $backingType === 'string') {
            $backingTests = <<<PHP

it('values() returns string backed values', function () {
    \\\$values = {$shortName}::values();
    expect(\\\$values)->each->toBeString();
});

PHP;
        } else {
            $backingTests = <<<PHP

it('values() returns case names for pure enum', function () {
    \\\$values = {$shortName}::values();
    expect(\\\$values)->toBe(array_map(fn (\\UnitEnum \\\$c): string => \\\$c->name, {$shortName}::cases()));
});

PHP;
        }

        /** @var string $firstCaseName */
        $firstCaseName = $cases[0]->name;

        return <<<PHP
<?php

declare(strict_types=1);

use {$enumClass};
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;

describe('{$shortName} enum', function () {
    it('has cases', function () {
        expect({$shortName}::cases())->not->toBeEmpty();
    });

    it('has the expected number of cases', function () {
        expect({$shortName}::cases())->toHaveCount({$caseCount});
    });

    it('can generate select options', function () {
        \\\$options = {$shortName}::forSelect();
        expect(\\\$options)->toBeArray();
        expect(\\\$options)->toHaveCount({$caseCount});
        expect(\\\$options[0])->toHaveKeys(['value', 'label']);
    });

    it('select option values are unique', function () {
        \\\$values = array_column({$shortName}::forSelect(), 'value');
        expect(\\\$values)->each->toBeUnique();
    });

    it('select option labels are non-empty strings', function () {
        \\\$options = {$shortName}::forSelect();
        foreach (\\\$options as \\\$option) {
            expect(\\\$option['label'])->toBeString()->not->toBeEmpty();
        }
    });

    it('can generate API response array', function () {
        \\\$api = {$shortName}::forApi();
        expect(\\\$api)->toBeArray();
        expect(\\\$api)->toHaveCount({$caseCount});
        expect(\\\$api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('API response color is always a string', function () {
        \\\$api = {$shortName}::forApi();
        foreach (\\\$api as \\\$item) {
            expect(\\\$item['color'])->toBeString()->not->toBeEmpty();
        }
    });

    it('values() returns correct count and types', function () {
        expect({$shortName}::values())->toHaveCount({$caseCount});
    });

    it('labels() returns correct count and non-empty strings', function () {
        \\\$labels = {$shortName}::labels();
        expect(\\\$labels)->toHaveCount({$caseCount});
        expect(\\\$labels)->each->toBeString()->not->toBeEmpty();
    });

    it('supports tryFromName lookup', function () {
        expect({$shortName}::tryFromName('{$firstCaseName}'))->toBeInstanceOf({$shortName}::class);
        expect({$shortName}::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('fromName() returns the correct case', function () {
        expect({$shortName}::fromName('{$firstCaseName}')->name)->toBe('{$firstCaseName}');
    });

    it('fromName() throws InvalidEnumException for non-existent name', function () {
        expect(fn () => {$shortName}::fromName('NON_EXISTENT'))->toThrow(InvalidEnumException::class);
    });

    it('supports hasCase check', function () {
        expect({$shortName}::hasCase('{$firstCaseName}'))->toBeTrue();
        expect({$shortName}::hasCase('NON_EXISTENT'))->toBeFalse();
    });

    {$backingTests}{$caseTests}{$comparisonTests}
});
PHP;
    }
}
