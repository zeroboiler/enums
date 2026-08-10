<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ReflectionClass;
use ReflectionEnum;
use ReflectionMethod;
use ReflectionProperty;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Console\Commands\InspectEnumCommand;
use ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

/**
 * Source code quality audit — verifies production-ready invariants via reflection.
 *
 * This test validates that every source file in the package adheres to
 * the quality standards required for PHPStan level 9 compliance:
 *
 * 1. `declare(strict_types=1)` in every PHP file
 * 2. All service classes are `final`
 * 3. All attribute classes are `final`
 * 4. `readonly` promoted properties on all attribute constructors
 * 5. All public methods have explicit return types
 * 6. Docblocks present on all public classes and methods
 * 7. No `mixed` parameter or return types in public API (manual assertion)
 */
describe('Enum Source Code Quality', function () {
    /**
     * Get all PHP source files in the src/ directory.
     *
     * @return list<string>
     */
    function getSrcFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__.'/../src', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    it('has declare(strict_types=1) in every source file', function () {
        $files = getSrcFiles();
        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            expect($contents)->not->toBeFalse();
            expect((bool) preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)/', $contents))
                ->toBeTrue("File {$file} is missing declare(strict_types=1)");
        }
    });

    it('marks all service classes as final', function () {
        $serviceClasses = [
            EnumCache::class,
            EnumManager::class,
            EnumRule::class,
            EnumCast::class,
            EnumMetadataResolver::class,
            EnumTestGenerator::class,
            InvalidEnumException::class,
            EnumsServiceProvider::class,
            InspectEnumCommand::class,
            MakeEnumTestCommand::class,
            Enum::class,
        ];

        foreach ($serviceClasses as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())
                ->toBeTrue("{$class} must be final");
        }
    });

    it('marks all attribute classes as final', function () {
        $attributeClasses = [
            Label::class,
            Color::class,
            Icon::class,
            Description::class,
            EnumLabel::class,
            EnumColor::class,
            EnumIcon::class,
            EnumDescription::class,
        ];

        foreach ($attributeClasses as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())
                ->toBeTrue("{$class} must be final");
        }
    });

    it('uses readonly promoted properties on all attribute constructors', function () {
        $attributeClasses = [
            Label::class,      // string $value
            Color::class,      // string $value
            Icon::class,       // string $value
            Description::class, // string $value
            EnumLabel::class,  // ?array $labels, ?string $label
            EnumColor::class,  // array $success, $danger, $warning, $info, $secondary
            EnumIcon::class,   // ?string $default
            EnumDescription::class, // ?array $descriptions, ?string $description
        ];

        foreach ($attributeClasses as $class) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();

            expect($constructor)->not->toBeNull("{$class} must have a constructor");

            foreach ($constructor->getParameters() as $param) {
                expect($param->isPromoted())
                    ->toBeTrue("{$class}::\${$param->name} must be a promoted property");

                // Check readonly via the property reflection
                $prop = $ref->getProperty($param->name);
                expect($prop->isReadOnly())
                    ->toBeTrue("{$class}::\${$param->name} must be readonly");
            }
        }
    });

    it('has explicit return types on all public methods of service classes', function () {
        $classesWithPublicMethods = [
            EnumCache::class,
            EnumManager::class,
            EnumRule::class,
            EnumCast::class,
            EnumMetadataResolver::class,
            EnumTestGenerator::class,
            InvalidEnumException::class,
        ];

        foreach ($classesWithPublicMethods as $class) {
            $ref = new ReflectionClass($class);

            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // Skip constructor
                if ($method->isConstructor()) {
                    continue;
                }

                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "{$class}::{$method->getName()}() must have an explicit return type"
                );
            }
        }
    });

    it('has docblocks on all public classes', function () {
        $publicClasses = [
            EnumCache::class,
            EnumManager::class,
            EnumRule::class,
            EnumCast::class,
            EnumMetadataResolver::class,
            EnumTestGenerator::class,
            InvalidEnumException::class,
            EnumsServiceProvider::class,
            Enum::class,
            InspectEnumCommand::class,
            MakeEnumTestCommand::class,
        ];

        foreach ($publicClasses as $class) {
            $ref = new ReflectionClass($class);
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse(
                "{$class} must have a class-level docblock"
            );
        }
    });

    it('prevents cloning of the EnumCache singleton', function () {
        $ref = new ReflectionClass(EnumCache::class);
        $cloneMethod = $ref->getMethod('__clone');

        expect($cloneMethod->isPrivate())->toBeTrue('__clone() must be private');
        expect($cloneMethod->getReturnType()?->getName())->toBe('never');
    });

    it('prevents unserialization of the EnumCache singleton', function () {
        $ref = new ReflectionClass(EnumCache::class);
        $wakeupMethod = $ref->getMethod('__wakeup');

        expect($wakeupMethod->isPublic())->toBeTrue('__wakeup() must be public (PHP requirement)');
        expect($wakeupMethod->getReturnType()?->getName())->toBe('never');
    });

    it('has EnumManager as a readonly class', function () {
        $ref = new ReflectionClass(EnumManager::class);
        expect($ref->isReadOnly())->toBeTrue('EnumManager must be readonly');
    });

    it('has EnumRule as a readonly class', function () {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->isReadOnly())->toBeTrue('EnumRule must be readonly');
    });

    it('uses #[Override] on Enum facade getFacadeAccessor', function () {
        $ref = new ReflectionClass(Enum::class);
        $method = $ref->getMethod('getFacadeAccessor');

        $attributes = $method->getAttributes();
        $hasOverride = false;

        foreach ($attributes as $attr) {
            if ($attr->getName() === 'Override') {
                $hasOverride = true;
                break;
            }
        }

        expect($hasOverride)->toBeTrue('Enum::getFacadeAccessor() must have #[Override]');
    });

    it('uses #[Override] on EnumCast interface methods', function () {
        $ref = new ReflectionClass(EnumCast::class);
        $methodsNeedingOverride = ['get', 'set'];

        foreach ($methodsNeedingOverride as $methodName) {
            $method = $ref->getMethod($methodName);
            $attributes = $method->getAttributes();
            $hasOverride = false;

            foreach ($attributes as $attr) {
                if ($attr->getName() === 'Override') {
                    $hasOverride = true;
                    break;
                }
            }

            expect($hasOverride)->toBeTrue("EnumCast::{$methodName}() must have #[Override]");
        }
    });

    it('uses #[Override] on EnumRule validate method', function () {
        $ref = new ReflectionClass(EnumRule::class);
        $method = $ref->getMethod('validate');

        $attributes = $method->getAttributes();
        $hasOverride = false;

        foreach ($attributes as $attr) {
            if ($attr->getName() === 'Override') {
                $hasOverride = true;
                break;
            }
        }

        expect($hasOverride)->toBeTrue('EnumRule::validate() must have #[Override]');
    });

    it('uses #[Override] on EnumsServiceProvider methods', function () {
        $ref = new ReflectionClass(EnumsServiceProvider::class);
        $methodsNeedingOverride = ['register'];

        foreach ($methodsNeedingOverride as $methodName) {
            $method = $ref->getMethod($methodName);
            $attributes = $method->getAttributes();
            $hasOverride = false;

            foreach ($attributes as $attr) {
                if ($attr->getName() === 'Override') {
                    $hasOverride = true;
                    break;
                }
            }

            expect($hasOverride)->toBeTrue("EnumsServiceProvider::{$methodName}() must have #[Override]");
        }
    });
});
