<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Support;

use ReflectionEnum;
use UnitEnum;

/**
 * Utility for generating Pest tests for enums.
 * Used by the `zeroboiler:enum-test` artisan command.
 */
final class EnumTestGenerator
{
    /**
     * Generate test file content for an enum class.
     *
     * Produces a complete Pest test file with tests for each case's label
     * and color, plus bulk method tests (forSelect, forApi, uniqueness),
     * comparison methods, and reverse lookups.
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

        $caseTests = '';
        foreach ($cases as $case) {
            $caseName = $case->name;
            $caseTests .= <<<PHP

it('has a label for case {$caseName}', function () {
    expect({$shortName}::{$caseName}->label())->toBeString()->not->toBeEmpty();
});

it('has a color for case {$caseName}', function () {
    expect({$shortName}::{$caseName}->color())->toBeString();
});

PHP;
        }

        // Generate comparison tests for the first case (if cases exist)
        $comparisonTests = '';
        if (count($cases) >= 2) {
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

it('supports isNot() comparison', function () {
    expect({$shortName}::{$firstCase}->isNot({$shortName}::{$secondCase}))->toBeTrue();
    expect({$shortName}::{$firstCase}->isNot({$shortName}::{$firstCase}))->toBeFalse();
});

it('supports in() group matching', function () {
    expect({$shortName}::{$firstCase}->in([{$shortName}::{$firstCase}, {$shortName}::{$secondCase}]))->toBeTrue();
    expect({$shortName}::{$firstCase}->in([{$shortName}::{$secondCase}]))->toBeFalse();
});

PHP;

            // Generate reverse lookup test using the first case's label
            $reverseLookupTest = <<<PHP

it('supports tryFromLabel reverse lookup', function () {
    \$case = {$shortName}::tryFromLabel({$shortName}::{$firstCase}->label());
    expect(\$case)->toBeInstanceOf({$shortName}::class);
    expect(\$case?->name)->toBe('{$firstCase}');
});

PHP;
            $comparisonTests .= $reverseLookupTest;
        }

        return <<<PHP
<?php

declare(strict_types=1);

use {$enumClass};

describe('{$shortName} enum', function () {
    it('has cases', function () {
        expect({$shortName}::cases())->not->toBeEmpty();
    });

    it('can generate select options', function () {
        \$options = {$shortName}::forSelect();
        expect(\$options)->toBeArray();
        expect(\$options[0])->toHaveKeys(['value', 'label']);
    });

    it('can generate API response array', function () {
        \$api = {$shortName}::forApi();
        expect(\$api)->toBeArray();
        expect(\$api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('has unique values', function () {
        \$values = array_column({$shortName}::forSelect(), 'value');
        expect(\$values)->each->toBeUnique();
    });

    it('supports tryFromName lookup', function () {
        expect({$shortName}::tryFromName('{$cases[0]->name}'))->toBeInstanceOf({$shortName}::class);
        expect({$shortName}::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('supports hasCase check', function () {
        expect({$shortName}::hasCase('{$cases[0]->name}'))->toBeTrue();
        expect({$shortName}::hasCase('NON_EXISTENT'))->toBeFalse();
    });

    it('values() returns correct count', function () {
        expect({$shortName}::values())->toHaveCount({count($cases)});
    });

    it('labels() returns correct count', function () {
        expect({$shortName}::labels())->toHaveCount({count($cases)});
    });
    {$caseTests}{$comparisonTests}
    });
PHP;
    }
}
