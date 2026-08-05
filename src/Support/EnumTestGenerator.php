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
     * @param  class-string<UnitEnum>  $enumClass
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
{$caseTests}
});
PHP;
    }
}
