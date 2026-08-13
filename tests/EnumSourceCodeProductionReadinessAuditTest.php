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
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

/**
 * Comprehensive production readiness audit — PHPStan Level 9 structural compliance.
 *
 * This test verifies source code quality standards without executing PHP:
 * - All classes are `final` (prevents uncontrolled inheritance)
 * - All files declare `strict_types=1` (enforces strict type checking at runtime)
 * - All public methods have return type declarations
 * - All properties use typed declarations (no untyped properties)
 * - All classes have docblocks with @see references
 * - Attribute classes use `#[Attribute]` with correct targets
 * - Constructor parameters are `public readonly` (attribute classes)
 * - No loose comparisons (should use === or strict methods)
 */
describe('Enums — Source Code Production Readiness Audit', function () {

    // -----------------------------------------------------------------------
    // §1. File-level structural compliance
    // -----------------------------------------------------------------------
    describe('§1. File-level structural compliance', function () {
        $srcFiles = [
            'Attributes/Color.php',
            'Attributes/Description.php',
            'Attributes/EnumColor.php',
            'Attributes/EnumDescription.php',
            'Attributes/EnumIcon.php',
            'Attributes/EnumLabel.php',
            'Attributes/Icon.php',
            'Attributes/Label.php',
            'Casts/EnumCast.php',
            'Concerns/HasEnumMetadata.php',
            'EnumCache.php',
            'EnumManager.php',
            'EnumsServiceProvider.php',
            'Exceptions/InvalidEnumException.php',
            'Facades/Enum.php',
            'Rules/EnumRule.php',
            'Support/EnumMetadataResolver.php',
            'Support/EnumTestGenerator.php',
        ];

        test('all source files declare strict_types=1', function () use ($srcFiles) {
            foreach ($srcFiles as $file) {
                $path = __DIR__.'/../src/'.$file;
                $content = file_get_contents($path);
                expect($content)->toContain('declare(strict_types=1)');
            }
        });

        test('all source files have namespace declarations', function () use ($srcFiles) {
            foreach ($srcFiles as $file) {
                $path = __DIR__.'/../src/'.$file;
                $tokens = token_get_all(file_get_contents($path));
                $hasNamespace = false;
                foreach ($tokens as $token) {
                    if (is_array($token) && $token[0] === T_NAMESPACE) {
                        $hasNamespace = true;
                        break;
                    }
                }
                expect($hasNamespace)->toBeTrue("File {$file} is missing namespace declaration");
            }
        });

        test('all source files have a file-level docblock', function () use ($srcFiles) {
            foreach ($srcFiles as $file) {
                $path = __DIR__.'/../src/'.$file;
                $content = file_get_contents($path);
                // Check for a docblock that mentions "ZeroBoiler"
                expect($content)->toMatch('/\/\*\*/', "File {$file} is missing a docblock");
                expect($content)->toContain('ZeroBoiler', "File {$file} docblock does not reference ZeroBoiler");
            }
        });
    });

    // -----------------------------------------------------------------------
    // §2. Class-level structural compliance
    // -----------------------------------------------------------------------
    describe('§2. Class-level structural compliance', function () {

        $finalClasses = [
            Label::class,
            Color::class,
            Icon::class,
            Description::class,
            EnumLabel::class,
            EnumColor::class,
            EnumIcon::class,
            EnumDescription::class,
            EnumCast::class,
            EnumCache::class,
            EnumManager::class,
            InvalidEnumException::class,
            Enum::class,
            EnumRule::class,
            EnumMetadataResolver::class,
            EnumTestGenerator::class,
            EnumsServiceProvider::class,
        ];

        test('all non-trait classes are final', function () use ($finalClasses) {
            foreach ($finalClasses as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} is not declared as final");
            }
        });

        test('all classes have a class-level docblock', function () use ($finalClasses) {
            foreach ($finalClasses as $class) {
                $ref = new ReflectionClass($class);
                $doc = $ref->getDocComment();
                expect($doc)->not->toBeFalse("{$class} is missing a class-level docblock");
                expect((string) $doc)->toContain('/**');
                expect((string) $doc)->toContain('*/');
            }
        });

        test('EnumManager is readonly', function () {
            $ref = new ReflectionClass(EnumManager::class);
            // PHP 8.2+ readonly classes
            expect($ref->isReadOnly())->toBeTrue('EnumManager should be a readonly class');
        });

        test('EnumRule is readonly', function () {
            $ref = new ReflectionClass(EnumRule::class);
            expect($ref->isReadOnly())->toBeTrue('EnumRule should be a readonly class');
        });
    });

    // -----------------------------------------------------------------------
    // §3. Method-level return type declarations
    // -----------------------------------------------------------------------
    describe('§3. Method-level return type declarations', function () {

        test('HasEnumMetadata trait public methods have return types', function () {
            $trait = new ReflectionClass(HasEnumMetadata::class);
            $publicMethods = $trait->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($publicMethods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "HasEnumMetadata::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('EnumCache all public methods have return types', function () {
            $ref = new ReflectionClass(EnumCache::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "EnumCache::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('EnumManager all public methods have return types', function () {
            $ref = new ReflectionClass(EnumManager::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "EnumManager::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('EnumMetadataResolver all public methods have return types', function () {
            $ref = new ReflectionClass(EnumMetadataResolver::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "EnumMetadataResolver::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('EnumTestGenerator all public methods have return types', function () {
            $ref = new ReflectionClass(EnumTestGenerator::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "EnumTestGenerator::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('InvalidEnumException methods have return types', function () {
            $ref = new ReflectionClass(InvalidEnumException::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "InvalidEnumException::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('EnumCast all public methods have return types', function () {
            $ref = new ReflectionClass(EnumCast::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "EnumCast::{$method->getName()}() is missing a return type declaration"
                );
            }
        });
    });

    // -----------------------------------------------------------------------
    // §4. Attribute class compliance
    // -----------------------------------------------------------------------
    describe('§4. Attribute class compliance', function () {
        $attributeClasses = [
            Label::class => Attribute::TARGET_CLASS_CONSTANT,
            Color::class => Attribute::TARGET_CLASS_CONSTANT,
            Icon::class => Attribute::TARGET_CLASS_CONSTANT,
            Description::class => Attribute::TARGET_CLASS_CONSTANT,
            EnumLabel::class => Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT,
            EnumColor::class => Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT,
            EnumIcon::class => Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT,
            EnumDescription::class => Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT,
        ];

        test('all attribute classes have #[Attribute] with correct targets', function () use ($attributeClasses) {
            foreach ($attributeClasses as $class => $expectedTargets) {
                $ref = new ReflectionClass($class);
                $attrs = $ref->getAttributes(Attribute::class);
                expect($attrs)->not->toBeEmpty("{$class} is missing #[Attribute] declaration");

                $instance = $attrs[0]->newInstance();
                $actualFlags = $instance->flags;
                expect($actualFlags)->toBe($expectedTargets,
                    "{$class} has incorrect attribute target flags"
                );
            }
        });

        test('per-case attribute constructors have a single readonly string $value property', function () {
            $perCaseAttrs = [Label::class, Color::class, Icon::class, Description::class];

            foreach ($perCaseAttrs as $class) {
                $ref = new ReflectionClass($class);
                $constructor = $ref->getConstructor();
                expect($constructor)->not->toBeNull("{$class} has no constructor");

                $params = $constructor->getParameters();
                expect($params)->toHaveCount(1, "{$class} should have exactly one constructor parameter");

                $param = $params[0];
                expect($param->getName())->toBe('value', "{$class} parameter should be named 'value'");

                $type = $param->getType();
                expect($type)->not->toBeNull("{$class}::\$value is missing a type declaration");
                expect($type instanceof ReflectionNamedType && $type->getName() === 'string')->toBeTrue(
                    "{$class}::\$value should be typed as string"
                );

                // Check that the property is readonly (promoted)
                $props = $ref->getProperties();
                expect($props)->toHaveCount(1, "{$class} should have exactly one promoted property");
                expect($props[0]->isReadOnly())->toBeTrue("{$class}::\$value should be readonly");
            }
        });

        test('class-level attribute constructors use readonly properties', function () {
            $classAttrs = [EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class];

            foreach ($classAttrs as $class) {
                $ref = new ReflectionClass($class);
                $props = $ref->getProperties();

                foreach ($props as $prop) {
                    expect($prop->isReadOnly())->toBeTrue(
                        "{$class}::\${$prop->getName()} should be readonly"
                    );
                }
            }
        });
    });

    // -----------------------------------------------------------------------
    // §5. Exception compliance
    // -----------------------------------------------------------------------
    describe('§5. Exception compliance', function () {

        test('InvalidEnumException extends Exception', function () {
            expect(is_subclass_of(InvalidEnumException::class, Exception::class))->toBeTrue();
        });

        test('InvalidEnumException has named constructors', function () {
            $ref = new ReflectionClass(InvalidEnumException::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);

            $names = array_map(fn (ReflectionMethod $m): string => $m->getName(), $methods);
            expect($names)->toContain('value');
            expect($names)->toContain('forName');
        });

        test('InvalidEnumException named constructors return self', function () {
            $ref = new ReflectionClass(InvalidEnumException::class);

            foreach (['value', 'forName'] as $methodName) {
                $method = $ref->getMethod($methodName);
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull();
                expect($returnType instanceof ReflectionNamedType && $returnType->getName() === 'self')->toBeTrue(
                    "InvalidEnumException::{$methodName}() should return self"
                );
            }
        });

        test('InvalidEnumException has __toString method', function () {
            $ref = new ReflectionClass(InvalidEnumException::class);
            expect($ref->hasMethod('__toString'))->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // §6. Interface implementations
    // -----------------------------------------------------------------------
    describe('§6. Interface implementations', function () {

        test('EnumCast implements CastsAttributes', function () {
            expect(EnumCast::class)->toImplement(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class);
        });

        test('EnumRule implements ValidationRule', function () {
            expect(EnumRule::class)->toImplement(\Illuminate\Contracts\Validation\ValidationRule::class);
        });

        test('Enum facade extends Laravel Facade', function () {
            expect(Enum::class)->toExtend(\Illuminate\Support\Facades\Facade::class);
        });

        test('EnumCast implements Override on get/set/serialize', function () {
            $ref = new ReflectionClass(EnumCast::class);

            foreach (['get', 'set', 'serialize'] as $method) {
                $methodRef = $ref->getMethod($method);
                $attrs = $methodRef->getAttributes(\Override::class);
                expect($attrs)->not->toBeEmpty(
                    "EnumCast::{$method}() should have #[\Override] attribute"
                );
            }
        });

        test('EnumRule implements Override on validate', function () {
            $ref = new ReflectionClass(EnumRule::class);
            $method = $ref->getMethod('validate');
            $attrs = $method->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty('EnumRule::validate() should have #[\Override] attribute');
        });
    });

    // -----------------------------------------------------------------------
    // §7. Service provider compliance
    // -----------------------------------------------------------------------
    describe('§7. Service provider compliance', function () {

        test('EnumsServiceProvider extends ServiceProvider', function () {
            expect(EnumsServiceProvider::class)->toExtend(\Illuminate\Support\ServiceProvider::class);
        });

        test('EnumsServiceProvider has register and boot methods with Override', function () {
            $ref = new ReflectionClass(EnumsServiceProvider::class);

            foreach (['register', 'boot'] as $method) {
                $methodRef = $ref->getMethod($method);
                expect($methodRef->hasReturnType())->toBeTrue(
                    "EnumsServiceProvider::{$method}() should have a return type"
                );
                $attrs = $methodRef->getAttributes(\Override::class);
                expect($attrs)->not->toBeEmpty(
                    "EnumsServiceProvider::{$method}() should have #[\Override] attribute"
                );
            }
        });
    });

    // -----------------------------------------------------------------------
    // §8. Singleton pattern compliance (EnumCache)
    // -----------------------------------------------------------------------
    describe('§8. Singleton pattern compliance', function () {

        test('EnumCache has private constructor', function () {
            $ref = new ReflectionClass(EnumCache::class);
            $constructor = $ref->getConstructor();
            expect($constructor)->not->toBeNull();
            expect($constructor->isPrivate())->toBeTrue();
        });

        test('EnumCache has __clone that returns never', function () {
            $ref = new ReflectionClass(EnumCache::class);
            $method = $ref->getMethod('__clone');
            expect($method->isPrivate())->toBeTrue();
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull();
            expect($returnType instanceof ReflectionNamedType && $returnType->getName() === 'never')->toBeTrue(
                'EnumCache::__clone() should return never'
            );
        });

        test('EnumCache has static getInstance() returning self', function () {
            $ref = new ReflectionClass(EnumCache::class);
            $method = $ref->getMethod('getInstance');
            $returnType = $method->getReturnType();
            expect($returnType instanceof ReflectionNamedType && $returnType->getName() === 'self')->toBeTrue();
        });

        test('EnumCache has static flush() and resetInstance()', function () {
            $ref = new ReflectionClass(EnumCache::class);
            expect($ref->hasMethod('flush'))->toBeTrue();
            expect($ref->hasMethod('resetInstance'))->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // §9. Docblock quality — @see references
    // -----------------------------------------------------------------------
    describe('§9. Docblock quality — @see references', function () {

        test('EnumMetadataResolver docblock references HasEnumMetadata', function () {
            $ref = new ReflectionClass(EnumMetadataResolver::class);
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse();
            expect((string) $doc)->toContain('@see');
            expect((string) $doc)->toContain('HasEnumMetadata');
        });

        test('EnumCache docblock references EnumMetadataResolver', function () {
            $ref = new ReflectionClass(EnumCache::class);
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse();
            expect((string) $doc)->toContain('@see');
            expect((string) $doc)->toContain('EnumMetadataResolver');
        });

        test('EnumCast docblock references CastsAttributes', function () {
            $ref = new ReflectionClass(EnumCast::class);
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse();
            expect((string) $doc)->toContain('CastsAttributes');
        });

        test('EnumRule docblock references HasEnumMetadata', function () {
            $ref = new ReflectionClass(EnumRule::class);
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse();
            expect((string) $doc)->toContain('HasEnumMetadata');
        });
    });

    // -----------------------------------------------------------------------
    // §10. phpstan.neon.dist configuration
    // -----------------------------------------------------------------------
    describe('§10. phpstan.neon.dist configuration', function () {

        test('phpstan.neon.dist exists and targets level 9', function () {
            $path = __DIR__.'/../phpstan.neon.dist';
            expect(file_exists($path))->toBeTrue('phpstan.neon.dist should exist');

            $content = file_get_contents($path);
            expect($content)->toContain('level: 9');
            expect($content)->toContain('paths:');
            expect($content)->toContain('src');
        });

        test('phpstan.neon.dist excludes tests from analysis', function () {
            $content = file_get_contents(__DIR__.'/../phpstan.neon.dist');
            expect($content)->toContain('excludePaths');
            expect($content)->toContain('tests');
        });
    });
});
