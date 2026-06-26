<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Support;

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ReflectionEnum;

/**
 * Utility for generating Pest tests for enums.
 * Used by the `zeroboiler:enum-test` artisan command.
 */
class EnumTestGenerator
{
    /**
     * Generate test file content for an enum class.
     *
     * @param  class-string  $enumClass
     */
    public static function generate(string $enumClass): string
    {
        $reflection = new ReflectionEnum($enumClass);
        $shortName  = $reflection->getShortName();
        $cases      = $enumClass::cases();
        $namespace  = $reflection->getNamespaceName();

        $testNamespace = str_replace('App\\', 'Tests\\', $namespace) . '\\' . $shortName;

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
