<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;

describe('Enum Attributes Production Audit', function () {
    it('per-case attributes (Label, Color, Icon, Description) are final', function () {
        $attributes = [Label::class, Color::class, Icon::class, Description::class];

        foreach ($attributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('class-level attributes (EnumLabel, EnumColor, EnumIcon, EnumDescription) are final', function () {
        $attributes = [EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class];

        foreach ($attributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('all per-case attributes target enum cases', function () {
        $perCaseClasses = [Label::class, Color::class, Icon::class, Description::class];

        foreach ($perCaseClasses as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs)->not->toBeEmpty("{$class} must have #[Attribute]");

            $instance = $attrs[0]->newInstance();
            $flags = $instance->getFlags();
            expect($flags & \Attribute::TARGET_CLASS_CONSTANT)->not->toBe(0,
                "{$class} must target class constants (enum cases)");
        }
    });

    it('all class-level attributes target both enum and cases', function () {
        $classLevelClasses = [EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class];

        foreach ($classLevelClasses as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs)->not->toBeEmpty("{$class} must have #[Attribute]");

            $instance = $attrs[0]->newInstance();
            $flags = $instance->getFlags();
            expect($flags & \Attribute::TARGET_CLASS)->not->toBe(0,
                "{$class} must target classes");
            expect($flags & \Attribute::TARGET_CLASS_CONSTANT)->not->toBe(0,
                "{$class} must also target class constants (enum cases)");
        }
    });

    it('all attributes have declare(strict_types=1)', function () {
        $attributeDir = dirname(__DIR__) . '/src/Attributes';
        $files = glob($attributeDir . '/*.php');

        expect($files)->not->toBeEmpty('Attributes directory must contain PHP files');

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            expect($contents)->toContain('declare(strict_types=1)',
                basename($file) . ' must declare strict types');
        }
    });

    it('per-case attributes have readonly string value property', function () {
        $instances = [
            new Label('Test Label'),
            new Color('success'),
            new Icon('heroicon-o-check'),
            new Description('Test description'),
        ];

        foreach ($instances as $instance) {
            $ref = new ReflectionClass($instance);
            $prop = $ref->getProperty('value');
            expect($prop->isReadOnly())->toBeTrue(get_class($instance) . '::$value must be readonly');
            expect($prop->getType()->getName())->toBe('string');
        }
    });

    it('EnumColor has named constructor parameters', function () {
        $ref = new ReflectionClass(EnumColor::class);
        $constructor = $ref->getConstructor();
        expect($constructor)->not->toBeNull();

        $params = $constructor->getParameters();
        $paramNames = array_map(fn (\ReflectionParameter $p) => $p->getName(), $params);

        // EnumColor should have at least: success, danger, warning, info, secondary
        expect($paramNames)->toContain('success');
        expect($paramNames)->toContain('danger');

        // All parameters should be typed as array
        foreach ($params as $param) {
            $type = $param->getType();
            expect($type)->not->toBeNull();
            expect($type->getName())->toBe('array');
        }
    });

    it('EnumLabel has labels array parameter', function () {
        $ref = new ReflectionClass(EnumLabel::class);
        $constructor = $ref->getConstructor();

        $params = $constructor->getParameters();
        $paramNames = array_map(fn (\ReflectionParameter $p) => $p->getName(), $params);
        expect($paramNames)->toContain('labels');

        $labelsParam = $constructor->getParameters()[array_search('labels', $paramNames)];
        $type = $labelsParam->getType();
        expect($type)->not->toBeNull();
        expect($type->getName())->toBe('array');
    });

    it('all attribute source files have proper license header', function () {
        $attributeDir = dirname(__DIR__) . '/src/Attributes';
        $files = glob($attributeDir . '/*.php');

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            expect($contents)->toContain('This file is part of ZeroBoiler',
                basename($file) . ' must have license header');
        }
    });
});
