<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 *
 * Static PHPStan Level 9 compliance assertions.
 * These tests verify type system guarantees at the source level.
 * They do not run PHPStan but assert that the public API
 * conforms to strict typing expectations.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ReflectionClass;
use ReflectionMethod;

describe('Enums PHPStan L9 static compliance', function () {
    it('all public methods on HasEnumMetadata trait have explicit return types', function () {
        $reflection = new ReflectionClass(HasEnumMetadata::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull(
                "HasEnumMetadata::{$method->getName()}() must have a return type declaration"
            );
        }
    });

    it('all public methods on EnumCache have return types', function () {
        $reflection = new ReflectionClass(EnumCache::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull(
                "EnumCache::{$method->getName()}() must have a return type declaration"
            );
        }
    });

    it('EnumCache __wakeup returns never', function () {
        $method = new ReflectionMethod(EnumCache::class, '__wakeup');
        expect($method->getReturnType()?->getName())->toBe('never');
    });

    it('EnumRule validate() uses Closure type hint for $fail parameter', function () {
        $method = new ReflectionMethod(EnumRule::class, 'validate');
        $params = $method->getParameters();

        $failParam = $params[2]; // $attribute, $value, $fail
        $type = $failParam->getType();

        expect($type)->not->toBeNull();
        expect($type->getName())->toBe('Closure');
    });

    it('EnumRule validate() accepts mixed $value (for interface compliance)', function () {
        $method = new ReflectionMethod(EnumRule::class, 'validate');
        $params = $method->getParameters();

        $valueParam = $params[1];
        $type = $valueParam->getType();

        expect($type)->not->toBeNull();
        expect($type->getName())->toBe('mixed');
    });

    it('InvalidEnumException factory methods return self', function () {
        $e = InvalidEnumException::value(UserStatus::class, 'invalid');
        expect($e)->toBeInstanceOf(InvalidEnumException::class);

        $e2 = InvalidEnumException::forName(UserStatus::class, 'NON_EXISTENT');
        expect($e2)->toBeInstanceOf(InvalidEnumException::class);
    });

    it('all infrastructure classes are final', function () {
        $finalClasses = [
            EnumCache::class,
            EnumRule::class,
            EnumManager::class,
            InvalidEnumException::class,
            \ZeroBoiler\Enums\Support\EnumMetadataResolver::class,
            \ZeroBoiler\Enums\Support\EnumTestGenerator::class,
            \ZeroBoiler\Enums\Casts\EnumCast::class,
        ];

        foreach ($finalClasses as $class) {
            $reflection = new ReflectionClass($class);
            expect($reflection->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('EnumManager is readonly', function () {
        $reflection = new ReflectionClass(EnumManager::class);
        expect($reflection->isReadOnly())->toBeTrue();
    });

    it('EnumRule is readonly', function () {
        $reflection = new ReflectionClass(EnumRule::class);
        expect($reflection->isReadOnly())->toBeTrue();
    });

    it('all enum attributes are final classes', function () {
        $classes = [
            \ZeroBoiler\Enums\Attributes\Label::class,
            \ZeroBoiler\Enums\Attributes\Description::class,
            \ZeroBoiler\Enums\Attributes\Color::class,
            \ZeroBoiler\Enums\Attributes\Icon::class,
            \ZeroBoiler\Enums\Attributes\EnumLabel::class,
            \ZeroBoiler\Enums\Attributes\EnumDescription::class,
            \ZeroBoiler\Enums\Attributes\EnumColor::class,
            \ZeroBoiler\Enums\Attributes\EnumIcon::class,
        ];

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            expect($reflection->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('attribute properties are public readonly', function () {
        $labelReflection = new ReflectionClass(\ZeroBoiler\Enums\Attributes\Label::class);
        $prop = $labelReflection->getProperty('value');
        expect($prop->isPublic())->toBeTrue();
        expect($prop->isReadOnly())->toBeTrue();

        $colorReflection = new ReflectionClass(\ZeroBoiler\Enums\Attributes\Color::class);
        $colorProp = $colorReflection->getProperty('value');
        expect($colorProp->isPublic())->toBeTrue();
        expect($colorProp->isReadOnly())->toBeTrue();

        $enumColorReflection = new ReflectionClass(\ZeroBoiler\Enums\Attributes\EnumColor::class);
        foreach (['success', 'danger', 'warning', 'info', 'secondary'] as $propName) {
            $prop = $enumColorReflection->getProperty($propName);
            expect($prop->isPublic())->toBeTrue("EnumColor::\${$propName} must be public");
            expect($prop->isReadOnly())->toBeTrue("EnumColor::\${$propName} must be readonly");
        }
    });

    it('declare strict types is present in all source files', function () {
        $srcDir = dirname(__DIR__).'/src';
        $files = glob("{$srcDir}/**/*.php");

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            expect($contents)->toContain('declare(strict_types=1)', "File {$file} must declare strict types");
        }
    });

    it('EnumManager forSelect returns non-empty array for UserStatus', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $options = $manager->forSelect(UserStatus::class);

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();
    });

    it('EnumManager forApi returns array with all metadata keys', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $api = $manager->forApi(UserStatus::class);

        expect($api)->toBeArray();
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });
});
