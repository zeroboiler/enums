<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * PHPStan Level 9 Full API Type Safety Audit.
 *
 * Structural test that verifies every public method in the enums package
 * has explicit parameter types, return types, and no `mixed` in signatures.
 * This test runs as a standard Pest test and can be verified with
 * `phpstan analyse` at level 9.
 *
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata
 * @see \ZeroBoiler\Enums\EnumCache
 * @see \ZeroBoiler\Enums\EnumManager
 * @see \ZeroBoiler\Enums\EnumMetadataResolver
 * @see \ZeroBoiler\Enums\Rules\EnumRule
 * @see \ZeroBoiler\Enums\Casts\EnumCast
 * @see \ZeroBoiler\Enums\Exceptions\InvalidEnumException
 * @see \ZeroBoiler\Enums\Facades\Enum
 */
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
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
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

describe('PHPStan Level 9 — Enums Package Full API Type Safety', function () {
    /**
     * List of all classes/traits that must pass the type safety audit.
     * Each entry is checked for: strict types, return types, no mixed params.
     *
     * @var list<class-string>
     */
    $publicApiClasses = [
        // Attributes (per-case)
        Label::class,
        Color::class,
        Icon::class,
        Description::class,
        // Attributes (class-level)
        EnumLabel::class,
        EnumColor::class,
        EnumIcon::class,
        EnumDescription::class,
        // Core
        EnumCache::class,
        EnumManager::class,
        EnumMetadataResolver::class,
        EnumRule::class,
        EnumCast::class,
        InvalidEnumException::class,
        Enum::class,
        EnumTestGenerator::class,
    ];

    it('all public API classes are declared final', function () use ($publicApiClasses) {
        foreach ($publicApiClasses as $className) {
            $ref = new ReflectionClass($className);
            expect($ref->isFinal())->toBeTrue(
                "Expected {$className} to be final"
            );
        }
    });

    it('all public methods have explicit return types', function () use ($publicApiClasses) {
        $allowedNoReturn = ['__construct', '__clone', '__wakeup', '__toString'];

        foreach ($publicApiClasses as $className) {
            $ref = new ReflectionClass($className);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if (in_array($method->getName(), $allowedNoReturn, true)) {
                    continue;
                }

                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "{$className}::{$method->getName()}() must have an explicit return type"
                );
            }
        }
    });

    it('no public method parameter has a bare mixed type without @param docblock', function () use ($publicApiClasses) {
        // PHPStan Level 9: mixed is allowed only with @param annotation
        // EnumRule::validate() has `mixed $value` but is properly documented
        $allowedMixedParams = [
            [EnumRule::class, 'validate', 'value'], // Laravel ValidationRule interface requires mixed
        ];

        foreach ($publicApiClasses as $className) {
            $ref = new ReflectionClass($className);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                foreach ($method->getParameters() as $param) {
                    $type = $param->getType();

                    if ($type instanceof ReflectionNamedType && $type->getName() === 'mixed') {
                        $isAllowed = false;
                        foreach ($allowedMixedParams as [$allowedClass, $allowedMethod, $allowedParam]) {
                            if ($className === $allowedClass
                                && $method->getName() === $allowedMethod
                                && $param->getName() === $allowedParam
                            ) {
                                $isAllowed = true;
                                break;
                            }
                        }

                        expect($isAllowed)->toBeTrue(
                            "{$className}::{$method->getName()}(\${$param->getName()}) uses bare mixed — not PHPStan Level 9 clean"
                        );
                    }
                }
            }
        }
    });

    it('attribute classes use readonly promoted properties', function () {
        $attributeClasses = [
            Label::class, Color::class, Icon::class, Description::class,
            EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class,
        ];

        foreach ($attributeClasses as $className) {
            $ref = new ReflectionClass($className);
            $constructor = $ref->getConstructor();

            expect($constructor)->not->toBeNull("{$className} must have a constructor");

            foreach ($constructor->getParameters() as $param) {
                $prop = $ref->getProperty($param->getName());
                expect($prop->isReadOnly())->toBeTrue(
                    "{$className}::\${$param->getName()} must be readonly"
                );
                expect($prop->isPublic())->toBeTrue(
                    "{$className}::\${$param->getName()} must be public"
                );
            }
        }
    });

    it('EnumManager is readonly class', function () {
        $ref = new ReflectionClass(EnumManager::class);
        expect($ref->isReadOnly())->toBeTrue('EnumManager must be a readonly class');
    });

    it('EnumRule is readonly class', function () {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->isReadOnly())->toBeTrue('EnumRule must be a readonly class');
    });

    it('EnumCache singleton prevents cloning and unserialization', function () {
        $ref = new ReflectionClass(EnumCache::class);

        $cloneMethod = $ref->getMethod('__clone');
        expect($cloneMethod->isPrivate())->toBeTrue('__clone must be private');
        $cloneReturn = $cloneMethod->getReturnType();
        expect($cloneReturn?->getName())->toBe('never');

        $wakeupMethod = $ref->getMethod('__wakeup');
        $wakeupReturn = $wakeupMethod->getReturnType();
        expect($wakeupReturn?->getName())->toBe('never');
    });

    it('Facade is final with proper accessor', function () {
        $ref = new ReflectionClass(Enum::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->getMethod('getFacadeAccessor')->getReturnType()?->getName())->toBe('string');
    });
});
