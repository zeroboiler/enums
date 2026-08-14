<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;

/**
 * Verifies consistency of attribute property types and naming conventions.
 *
 * Ensures that all attributes follow the package contract:
 * - Properties are named according to convention (value for per-case, specific for class-level)
 * - Property types match expected signatures (string for single values, array for maps)
 * - All properties are readonly and public
 */
final class EnumAttributePropertyConsistencyTest extends TestCase
{
    /**
     * Per-case attributes must have exactly one constructor parameter named 'value' of type string.
     *
     * @test
     */
    public function per_case_attributes_have_value_property(): void
    {
        $perCaseAttributes = [
            Label::class => 'value',
            Color::class => 'value',
            Icon::class => 'value',
            Description::class => 'value',
        ];

        foreach ($perCaseAttributes as $class => $expectedParam) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();
            $this->assertNotNull($constructor, "{$class} must have a constructor");

            $params = $constructor->getParameters();
            $this->assertCount(1, $params, "{$class} must have exactly 1 constructor parameter");

            $param = $params[0];
            $this->assertSame($expectedParam, $param->getName(), "{$class}::\${$expectedParam} must exist");

            $type = $param->getType();
            $this->assertNotNull($type, "{$class}::\${$expectedParam} must have a type");
            $this->assertSame('string', $type->getName(), "{$class}::\${$expectedParam} must be string");

            $prop = $ref->getProperty($expectedParam);
            $this->assertTrue($prop->isPublic(), "{$class}::\${$expectedParam} must be public");
            $this->assertTrue($prop->isReadOnly(), "{$class}::\${$expectedParam} must be readonly");
        }
    }

    /**
     * Class-level attributes have correct property names and types.
     *
     * @test
     */
    public function class_level_attributes_have_correct_properties(): void
    {
        // EnumLabel: labels (array|null), label (string|null)
        $enumLabelRef = new ReflectionClass(EnumLabel::class);
        $this->assertTrue($enumLabelRef->hasProperty('labels'));
        $this->assertTrue($enumLabelRef->hasProperty('label'));

        $labelsProp = $enumLabelRef->getProperty('labels');
        $labelsType = $labelsProp->getType();
        $this->assertNotNull($labelsType);
        $this->assertTrue($labelsType->allowsNull(), 'EnumLabel::$labels must be nullable');
        $this->assertSame('array', $labelsType->getName(), 'EnumLabel::$labels must be array');

        $labelProp = $enumLabelRef->getProperty('label');
        $labelType = $labelProp->getType();
        $this->assertNotNull($labelType);
        $this->assertTrue($labelType->allowsNull(), 'EnumLabel::$label must be nullable');
        $this->assertSame('string', $labelType->getName(), 'EnumLabel::$label must be string');

        // EnumDescription: descriptions (array|null), description (string|null)
        $enumDescRef = new ReflectionClass(EnumDescription::class);
        $this->assertTrue($enumDescRef->hasProperty('descriptions'));
        $this->assertTrue($enumDescRef->hasProperty('description'));

        $descriptionsProp = $enumDescRef->getProperty('descriptions');
        $descriptionsType = $descriptionsProp->getType();
        $this->assertNotNull($descriptionsType);
        $this->assertTrue($descriptionsType->allowsNull(), 'EnumDescription::$descriptions must be nullable');
        $this->assertSame('array', $descriptionsType->getName(), 'EnumDescription::$descriptions must be array');

        // EnumColor: success, danger, warning, info, secondary (all arrays)
        $enumColorRef = new ReflectionClass(EnumColor::class);
        $colorFields = ['success', 'danger', 'warning', 'info', 'secondary'];
        foreach ($colorFields as $field) {
            $this->assertTrue(
                $enumColorRef->hasProperty($field),
                "EnumColor::\${$field} must exist"
            );
            $prop = $enumColorRef->getProperty($field);
            $type = $prop->getType();
            $this->assertNotNull($type, "EnumColor::\${$field} must have a type");
            $this->assertSame('array', $type->getName(), "EnumColor::\${$field} must be array");
        }

        // EnumIcon: default (string|null), icons (array)
        $enumIconRef = new ReflectionClass(EnumIcon::class);
        $this->assertTrue($enumIconRef->hasProperty('default'));
        $this->assertTrue($enumIconRef->hasProperty('icons'));

        $defaultProp = $enumIconRef->getProperty('default');
        $defaultType = $defaultProp->getType();
        $this->assertNotNull($defaultType);
        $this->assertTrue($defaultType->allowsNull(), 'EnumIcon::$default must be nullable');
        $this->assertSame('string', $defaultType->getName(), 'EnumIcon::$default must be string');

        $iconsProp = $enumIconRef->getProperty('icons');
        $iconsType = $iconsProp->getType();
        $this->assertNotNull($iconsType);
        $this->assertSame('array', $iconsType->getName(), 'EnumIcon::$icons must be array');
    }

    /**
     * All attribute properties are public and readonly.
     *
     * @test
     */
    public function all_attribute_properties_are_public_readonly(): void
    {
        $attributeClasses = [
            Label::class, Color::class, Icon::class, Description::class,
            EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class,
        ];

        foreach ($attributeClasses as $class) {
            $ref = new ReflectionClass($class);

            foreach ($ref->getProperties() as $prop) {
                if ($prop->isStatic()) {
                    continue;
                }

                $this->assertTrue(
                    $prop->isPublic(),
                    "{$class}::\${$prop->getName()} must be public"
                );
                $this->assertTrue(
                    $prop->isReadOnly(),
                    "{$class}::\${$prop->getName()} must be readonly"
                );
            }
        }
    }

    /**
     * EnumColor default values must all be empty arrays.
     *
     * @test
     */
    public function enum_color_defaults_are_empty_arrays(): void
    {
        $ref = new ReflectionClass(EnumColor::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);

        $fields = ['success', 'danger', 'warning', 'info', 'secondary'];
        foreach ($fields as $field) {
            $param = null;
            foreach ($constructor->getParameters() as $p) {
                if ($p->getName() === $field) {
                    $param = $p;
                    break;
                }
            }
            $this->assertNotNull($param, "EnumColor constructor must have \${$field} parameter");
            $this->assertTrue(
                $param->isDefaultValueAvailable(),
                "EnumColor::\${$field} must have a default value"
            );
            $default = $param->getDefaultValue();
            $this->assertSame(
                [],
                $default,
                "EnumColor::\${$field} default must be empty array"
            );
        }
    }

    /**
     * EnumIcon default values: default=null, icons=[].
     *
     * @test
     */
    public function enum_icon_defaults_are_correct(): void
    {
        $ref = new ReflectionClass(EnumIcon::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);

        // 'default' parameter should default to null
        $defaultParam = null;
        $iconsParam = null;
        foreach ($constructor->getParameters() as $p) {
            if ($p->getName() === 'default') {
                $defaultParam = $p;
            } elseif ($p->getName() === 'icons') {
                $iconsParam = $p;
            }
        }

        $this->assertNotNull($defaultParam, 'EnumIcon must have $default parameter');
        $this->assertTrue($defaultParam->isDefaultValueAvailable(), 'EnumIcon::$default must have default');
        $this->assertNull($defaultParam->getDefaultValue(), 'EnumIcon::$default must default to null');

        $this->assertNotNull($iconsParam, 'EnumIcon must have $icons parameter');
        $this->assertTrue($iconsParam->isDefaultValueAvailable(), 'EnumIcon::$icons must have default');
        $this->assertSame([], $iconsParam->getDefaultValue(), 'EnumIcon::$icons must default to []');
    }

    /**
     * EnumLabel and EnumDescription default to null for both properties.
     *
     * @test
     */
    public function enum_label_and_description_defaults_are_null(): void
    {
        foreach ([EnumLabel::class, EnumDescription::class] as $class) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();
            $this->assertNotNull($constructor);

            foreach ($constructor->getParameters() as $param) {
                $this->assertTrue(
                    $param->isDefaultValueAvailable(),
                    "{$class}::\${$param->getName()} must have a default value"
                );
                $this->assertNull(
                    $param->getDefaultValue(),
                    "{$class}::\${$param->getName()} must default to null"
                );
            }
        }
    }
}
