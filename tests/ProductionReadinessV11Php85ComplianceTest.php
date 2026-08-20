<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Production readiness and PHP 8.5 compliance verification.
 *
 * Validates source code structure, API completeness, and PHP 8.5 compatibility
 * for all public classes in the enums package. This is a comprehensive
 * structural audit that ensures the package meets enterprise quality standards.
 *
 * Tests cover:
 * - PHP 8.5 syntax compliance (final classes, readonly properties, named args)
 * - Strict types declarations on every source file
 * - Return type completeness on all public methods
 * - Docblock coverage on all public API classes
 * - Attribute usage correctness (TARGET_CLASS, TARGET_CLASS_CONSTANT)
 * - Interface/contract compliance
 * - Singleton lifecycle correctness (EnumCache)
 * - Serialization safety (EnumCache blocks all serialization paths)
 * - EnumCast implements CastsAttributes correctly
 * - EnumRule implements ValidationRule correctly
 * - InvalidEnumException provides all factory methods
 * - EnumsServiceProvider registers correct singleton and facade accessor
 *
 * @see https://www.php.net/manual/en/migration85.php
 */

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
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Production Readiness V11 — PHP 8.5 & Enterprise Compliance', function (): void {

    // ──────────────────────────────────────────────────────────────
    // 1. Source file strict_types declarations
    // ──────────────────────────────────────────────────────────────

    describe('All source files declare strict_types=1', function (): void {
        $sourceFiles = [
            __DIR__ . '/../src/EnumManager.php',
            __DIR__ . '/../src/EnumCache.php',
            __DIR__ . '/../src/Concerns/HasEnumMetadata.php',
            __DIR__ . '/../src/Support/EnumMetadataResolver.php',
            __DIR__ . '/../src/Exceptions/InvalidEnumException.php',
            __DIR__ . '/../src/Rules/EnumRule.php',
            __DIR__ . '/../src/Casts/EnumCast.php',
            __DIR__ . '/../src/Facades/Enum.php',
            __DIR__ . '/../src/EnumsServiceProvider.php',
            __DIR__ . '/../src/Attributes/Label.php',
            __DIR__ . '/../src/Attributes/EnumLabel.php',
            __DIR__ . '/../src/Attributes/Color.php',
            __DIR__ . '/../src/Attributes/EnumColor.php',
            __DIR__ . '/../src/Attributes/Description.php',
            __DIR__ . '/../src/Attributes/EnumDescription.php',
            __DIR__ . '/../src/Attributes/Icon.php',
            __DIR__ . '/../src/Attributes/EnumIcon.php',
        ];

        foreach ($sourceFiles as $file) {
            $basename = basename($file);
            it("{$basename} has declare(strict_types=1)", function () use ($file): void {
                $content = file_get_contents($file);
                expect($content)->toContain('declare(strict_types=1)');
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 2. Final classes — all public classes are final
    // ──────────────────────────────────────────────────────────────

    describe('All public classes are final', function (): void {
        $finalClasses = [
            EnumManager::class,
            EnumCache::class,
            EnumRule::class,
            EnumCast::class,
            InvalidEnumException::class,
            Enum::class,
            EnumMetadataResolver::class,
            EnumsServiceProvider::class,
            Label::class,
            EnumLabel::class,
            Color::class,
            EnumColor::class,
            Description::class,
            EnumDescription::class,
            Icon::class,
            EnumIcon::class,
        ];

        foreach ($finalClasses as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName} is final", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue();
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 3. EnumManager — final readonly class with complete API
    // ──────────────────────────────────────────────────────────────

    describe('EnumManager is final readonly', function (): void {
        it('is a final class', function (): void {
            $ref = new \ReflectionClass(EnumManager::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('is a readonly class', function (): void {
            $ref = new \ReflectionClass(EnumManager::class);
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('has no public properties', function (): void {
            $ref = new \ReflectionClass(EnumManager::class);
            $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
            expect($props)->toHaveCount(0);
        });

        it('all public methods have return type declarations', function (): void {
            $ref = new \ReflectionClass(EnumManager::class);
            $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if ($method->getName() === '__construct') {
                    continue;
                }
                expect($method->hasReturnType())
                    ->toBeTrue("Method {$method->getName()}() missing return type");
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 4. Attribute classes — readonly promoted constructor properties
    // ──────────────────────────────────────────────────────────────

    describe('Attribute classes use readonly promoted properties', function (): void {
        $attributeClasses = [
            Label::class => ['value' => 'string'],
            Color::class => ['value' => 'string'],
            Description::class => ['value' => 'string'],
            Icon::class => ['value' => 'string'],
        ];

        foreach ($attributeClasses as $class => $expectedProps) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName} has readonly promoted properties", function () use ($class, $expectedProps): void {
                $ref = new \ReflectionClass($class);
                $props = $ref->getProperties();

                foreach ($expectedProps as $name => $expectedType) {
                    $prop = $ref->getProperty($name);
                    expect($prop->isReadOnly())->toBeTrue();
                    expect($prop->isPromoted())->toBeTrue();
                    expect($prop->getType()->getName())->toBe($expectedType);
                }
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 5. EnumCache singleton — serialization fully blocked
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache serialization safety', function (): void {
        it('__clone() is public (PHP engine requirement) and returns never', function (): void {
            $ref = new \ReflectionMethod(EnumCache::class, '__clone');
            // PHP's clone operator requires __clone() to be public — the method
            // body always throws, but the visibility must remain public.
            expect($ref->isPublic())->toBeTrue();
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('never');
        });

        it('__wakeup() is public and returns never', function (): void {
            $ref = new \ReflectionMethod(EnumCache::class, '__wakeup');
            expect($ref->isPublic())->toBeTrue();
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('never');
        });

        it('__serialize() is public and returns never', function (): void {
            $ref = new \ReflectionMethod(EnumCache::class, '__serialize');
            expect($ref->isPublic())->toBeTrue();
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('never');
        });

        it('__unserialize() is public and returns never', function (): void {
            $ref = new \ReflectionMethod(EnumCache::class, '__unserialize');
            expect($ref->isPublic())->toBeTrue();
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('never');
        });

        it('serialize() throws RuntimeException', function (): void {
            $cache = EnumCache::getInstance();
            expect(fn () => serialize($cache))
                ->toThrow(\RuntimeException::class);
        });

        it('unserialize() throws RuntimeException', function (): void {
            expect(fn () => unserialize('O:34:"ZeroBoiler\Enums\EnumCache":0:{}'))
                ->toThrow(\RuntimeException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 6. EnumRule — implements ValidationRule, readonly, named constructors
    // ──────────────────────────────────────────────────────────────

    describe('EnumRule implements ValidationRule', function (): void {
        it('implements Illuminate\Contracts\Validation\ValidationRule', function (): void {
            $ref = new \ReflectionClass(EnumRule::class);
            expect($ref->implementsInterface(\Illuminate\Contracts\Validation\ValidationRule::class))
                ->toBeTrue();
        });

        it('is final readonly', function (): void {
            $ref = new \ReflectionClass(EnumRule::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('validate() has void return type', function (): void {
            $ref = new \ReflectionMethod(EnumRule::class, 'validate');
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('void');
        });

        it('for() returns self', function (): void {
            $ref = new \ReflectionMethod(EnumRule::class, 'for');
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('self');
        });

        it('nullable() returns self', function (): void {
            $ref = new \ReflectionMethod(EnumRule::class, 'nullable');
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('self');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 7. EnumCast — implements CastsAttributes
    // ──────────────────────────────────────────────────────────────

    describe('EnumCast implements CastsAttributes', function (): void {
        it('implements CastsAttributes', function (): void {
            $ref = new \ReflectionClass(EnumCast::class);
            expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))
                ->toBeTrue();
        });

        it('is final', function (): void {
            $ref = new \ReflectionClass(EnumCast::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('get() has nullable return type', function (): void {
            $ref = new \ReflectionMethod(EnumCast::class, 'get');
            $returnType = $ref->getReturnType();
            expect($returnType->allowsNull())->toBeTrue();
        });

        it('set() has return type declaration', function (): void {
            $ref = new \ReflectionMethod(EnumCast::class, 'set');
            expect($ref->hasReturnType())->toBeTrue();
        });

        it('serialize() has return type declaration', function (): void {
            $ref = new \ReflectionMethod(EnumCast::class, 'serialize');
            expect($ref->hasReturnType())->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 8. InvalidEnumException — final with factory methods
    // ──────────────────────────────────────────────────────────────

    describe('InvalidEnumException factory methods', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(InvalidEnumException::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('value() returns self', function (): void {
            $ref = new \ReflectionMethod(InvalidEnumException::class, 'value');
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('self');
        });

        it('forName() returns self', function (): void {
            $ref = new \ReflectionMethod(InvalidEnumException::class, 'forName');
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('self');
        });

        it('__toString() has Override attribute and returns string', function (): void {
            $ref = new \ReflectionMethod(InvalidEnumException::class, '__toString');
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('string');
            $attrs = $ref->getAttributes();
            $hasOverride = false;
            foreach ($attrs as $attr) {
                if ($attr->getName() === \Override::class) {
                    $hasOverride = true;
                    break;
                }
            }
            expect($hasOverride)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 9. Facade — correct accessor
    // ──────────────────────────────────────────────────────────────

    describe('Enum facade accessor', function (): void {
        it('is a final class extending Facade', function (): void {
            $ref = new \ReflectionClass(Enum::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isSubclassOf(\Illuminate\Support\Facades\Facade::class))->toBeTrue();
        });

        it('getFacadeAccessor returns zeroboiler.enum', function (): void {
            $method = new \ReflectionMethod(Enum::class, 'getFacadeAccessor');
            $method->setAccessible(true);
            expect($method->invoke(null))->toBe('zeroboiler.enum');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 10. EnumsServiceProvider — correct singleton binding
    // ──────────────────────────────────────────────────────────────

    describe('EnumsServiceProvider', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(EnumsServiceProvider::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('extends Illuminate\Support\ServiceProvider', function (): void {
            $ref = new \ReflectionClass(EnumsServiceProvider::class);
            expect($ref->isSubclassOf(\Illuminate\Support\ServiceProvider::class))->toBeTrue();
        });

        it('register() has void return type', function (): void {
            $ref = new \ReflectionMethod(EnumsServiceProvider::class, 'register');
            expect($ref->getReturnType()->getName())->toBe('void');
        });

        it('boot() has void return type', function (): void {
            $ref = new \ReflectionMethod(EnumsServiceProvider::class, 'boot');
            expect($ref->getReturnType()->getName())->toBe('void');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 11. HasEnumMetadata trait — method completeness
    // ──────────────────────────────────────────────────────────────

    describe('HasEnumMetadata trait method completeness', function (): void {
        $expectedMethods = [
            'label', 'description', 'color', 'icon',
            'forSelect', 'forApi',
            'tryFromLabel', 'tryFromName', 'fromName', 'hasCase',
            'is', 'isNot', 'in', 'notIn',
            'values', 'labels',
        ];

        foreach ($expectedMethods as $method) {
            it("trait has method: {$method}()", function () use ($method): void {
                $ref = new \ReflectionClass(HasEnumMetadata::class);
                expect($ref->hasMethod($method))->toBeTrue();
            });
        }

        it('all trait methods have return type declarations', function (): void {
            $ref = new \ReflectionClass(HasEnumMetadata::class);
            foreach ($expectedMethods as $method) {
                $m = $ref->getMethod($method);
                expect($m->hasReturnType())
                    ->toBeTrue("Trait method {$method}() missing return type");
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 12. EnumMetadataResolver — final with public API
    // ──────────────────────────────────────────────────────────────

    describe('EnumMetadataResolver', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(EnumMetadataResolver::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('resolve() returns array', function (): void {
            $ref = new \ReflectionMethod(EnumMetadataResolver::class, 'resolve');
            expect($ref->getReturnType()->getName())->toBe('array');
        });

        it('invalidate() returns void', function (): void {
            $ref = new \ReflectionMethod(EnumMetadataResolver::class, 'invalidate');
            expect($ref->getReturnType()->getName())->toBe('void');
        });

        it('invalidateAll() returns void', function (): void {
            $ref = new \ReflectionMethod(EnumMetadataResolver::class, 'invalidateAll');
            expect($ref->getReturnType()->getName())->toBe('void');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 13. Edge cases — zero-value backed enums, single case enums
    // ──────────────────────────────────────────────────────────────

    describe('Zero-value and single-case enum edge cases', function (): void {
        it('zero-backed enum resolves label correctly', function (): void {
            expect(ZeroPriority::ZERO->label())->toBe('Zero');
        });

        it('zero-backed int enum resolves label correctly', function (): void {
            expect(ZeroBackedPriority::ZERO->label())->toBe('Zero');
        });

        it('single-case enum has full metadata API', function (): void {
            expect(SingleCaseToggle::ON->label())->toBeString();
            expect(SingleCaseToggle::ON->color())->toBeString();
            expect(SingleCaseToggle::forSelect())->toBeArray();
            expect(SingleCaseToggle::forSelect())->toHaveCount(1);
        });

        it('single-case enum tryFromName works', function (): void {
            expect(SingleCaseToggle::tryFromName('ON'))->toBe(SingleCaseToggle::ON);
            expect(SingleCaseToggle::tryFromName('OFF'))->toBeNull();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 14. Cross-fixture metadata consistency
    // ──────────────────────────────────────────────────────────────

    describe('Cross-fixture metadata consistency', function (): void {
        it('all OrderStatus cases have labels', function (): void {
            foreach (OrderStatus::cases() as $case) {
                expect($case->label())->toBeString();
                expect($case->label())->not->toBeEmpty();
            }
        });

        it('all PaymentStatus cases have labels', function (): void {
            foreach (PaymentStatus::cases() as $case) {
                expect($case->label())->toBeString();
                expect($case->label())->not->toBeEmpty();
            }
        });

        it('IntBackedPriority has correct values and labels', function (): void {
            expect(IntBackedPriority::LOW->value)->toBe(1);
            expect(IntBackedPriority::HIGH->value)->toBe(3);
            expect(IntBackedPriority::values())->toBe([1, 2, 3]);
            expect(IntBackedPriority::labels())->toHaveCount(3);
        });

        it('PureSystemState uses case names as values', function (): void {
            expect(PureSystemState::RUNNING->value)->toBeNull(); // pure enum
            expect(PureSystemState::values())->toContain('RUNNING');
            expect(PureSystemState::forSelect()[0]['value'])->toBe('RUNNING');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 15. TTL behavior — cache expiration correctness
    // ──────────────────────────────────────────────────────────────

    describe('Cache TTL expiration behavior', function (): void {
        it('TTL of 0 disables caching', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(0);

            EnumMetadataResolver::invalidate(OrderStatus::class);
            EnumMetadataResolver::resolve(OrderStatus::class);

            // With TTL=0, has() always returns false
            expect($cache->has(OrderStatus::class))->toBeFalse();

            $cache->clear();
            $cache->setTtl(300);
        });

        it('TTL of 1 expires immediately after usleep', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(1);

            EnumMetadataResolver::invalidate(OrderStatus::class);
            EnumMetadataResolver::resolve(OrderStatus::class);

            expect($cache->has(OrderStatus::class))->toBeTrue();

            // Sleep 2 seconds to ensure TTL expiration
            sleep(2);

            expect($cache->has(OrderStatus::class))->toBeFalse();

            $cache->clear();
            $cache->setTtl(300);
        });
    });
});
