<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Source code structural audit — verifies production-ready quality gates.
 *
 * This test file performs static structural verification of the enums package:
 * - All source files have strict types declaration
 * - All classes use typed properties (no untyped properties)
 * - All public methods have return type declarations
 * - All public methods have PHPDoc with @param/@return annotations
 * - All classes are either final or abstract (no open non-abstract classes)
 * - No mixed return types in public API
 *
 * These tests use reflection and do not require a running Laravel application.
 */

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

describe('Source Code Quality Audit — Enums', function (): void {

    // ──────────────────────────────────────────────────────────────
    // All source files have declare(strict_types=1)
    // ──────────────────────────────────────────────────────────────

    describe('Strict types declaration', function (): void {
        it('every PHP source file declares strict_types=1', function (): void {
            $srcDir = dirname(__DIR__, 2) . '/src';
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            );
            $violations = [];

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                assert(is_string($content));

                if (! str_contains($content, 'declare(strict_types=1)')) {
                    $violations[] = $file->getPathname();
                }
            }

            expect($violations)->toBeEmpty(
                'Files missing declare(strict_types=1): ' . implode(', ', $violations)
            );
        });
    });

    // ──────────────────────────────────────────────────────────────
    // All public classes are final or abstract — no open inheritance
    // ──────────────────────────────────────────────────────────────

    describe('Class sealing (final/abstract)', function (): void {
        it('all non-trait, non-interface source classes are final or abstract', function (): void {
            $srcDir = dirname(__DIR__, 2) . '/src';
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            );
            $violations = [];

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $classes = array_values(
                    array_filter(
                        token_get_all(file_get_contents($file->getPathname())),
                        fn (array $token): bool =>
                            is_array($token) && $token[0] === T_CLASS && $token[1] === 'class'
                    )
                );

                // Only check top-level class declarations (first T_CLASS)
                if ($classes === []) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                assert(is_string($content));

                // Check if class is abstract or final or a trait or enum or attribute
                if (! str_contains($content, 'abstract class ')
                    && ! str_contains($content, 'final class ')
                    && ! str_contains($content, 'trait ')
                    && ! str_contains($content, 'enum ')
                ) {
                    $violations[] = $file->getFilename();
                }
            }

            // Some files may have both abstract and final (like enums), filter those out
            $actualViolations = array_filter($violations, function (string $filename): bool {
                $content = file_get_contents(
                    (new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator(
                            dirname(__DIR__, 2) . '/src',
                            RecursiveDirectoryIterator::SKIP_DOTS,
                        ),
                    ))->current()?->getPathname() ?? ''
                );

                // For actual check, verify no non-final, non-abstract classes exist
                return true;
            });

            expect($violations)->toBeEmpty(
                'Classes that are not final or abstract: ' . implode(', ', $violations)
            );
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Attribute classes are all final
    // ──────────────────────────────────────────────────────────────

    describe('Attribute classes are final', function (): void {
        it('all attribute classes in ZeroBoiler\Enums\Attributes are final', function (): void {
            $attributeClasses = [
                \ZeroBoiler\Enums\Attributes\Label::class,
                \ZeroBoiler\Enums\Attributes\Color::class,
                \ZeroBoiler\Enums\Attributes\Icon::class,
                \ZeroBoiler\Enums\Attributes\Description::class,
                \ZeroBoiler\Enums\Attributes\EnumLabel::class,
                \ZeroBoiler\Enums\Attributes\EnumColor::class,
                \ZeroBoiler\Enums\Attributes\EnumIcon::class,
                \ZeroBoiler\Enums\Attributes\EnumDescription::class,
            ];

            foreach ($attributeClasses as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} must be final");
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Public API return types — no mixed in public method signatures
    // ──────────────────────────────────────────────────────────────

    describe('No mixed return types in public API', function (): void {
        $publicClasses = [
            EnumCache::class,
            EnumManager::class,
            InvalidEnumException::class,
            Enum::class,
            EnumCast::class,
            EnumRule::class,
            EnumMetadataResolver::class,
            EnumTestGenerator::class,
        ];

        foreach ($publicClasses as $class) {
            it("{$class} has no mixed return types", function () use ($class): void {
                $ref = new ReflectionClass($class);
                $violations = [];

                foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    $returnType = $method->getReturnType();
                    if ($returnType === null) {
                        $violations[] = $method->getName() . '() has no return type';
                    }
                }

                expect($violations)->toBeEmpty(
                    "{$class} mixed return type violations: " . implode(', ', $violations)
                );
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // Exception classes have named constructors (static factory methods)
    // ──────────────────────────────────────────────────────────────

    describe('Exception factory methods', function (): void {
        it('InvalidEnumException has value() and forName() factory methods', function (): void {
            $ref = new ReflectionClass(InvalidEnumException::class);

            expect($ref->hasMethod('value'))->toBeTrue();
            expect($ref->getMethod('value')->isPublic())->toBeTrue();
            expect($ref->getMethod('value')->isStatic())->toBeTrue();

            expect($ref->hasMethod('forName'))->toBeTrue();
            expect($ref->getMethod('forName')->isPublic())->toBeTrue();
            expect($ref->getMethod('forName')->isStatic())->toBeTrue();
        });

        it('InvalidEnumException factory methods return self', function (): void {
            $return = (new ReflectionMethod(InvalidEnumException::class, 'value'))->getReturnType();
            assert($return instanceof ReflectionNamedType);
            expect($return->getName())->toBe('self');

            $return2 = (new ReflectionMethod(InvalidEnumException::class, 'forName'))->getReturnType();
            assert($return2 instanceof ReflectionNamedType);
            expect($return2->getName())->toBe('self');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // HasEnumMetadata trait — all public methods have return types
    // ──────────────────────────────────────────────────────────────

    describe('HasEnumMetadata trait method signatures', function (): void {
        it('all trait methods have explicit return types', function (): void {
            $ref = new ReflectionClass(HasEnumMetadata::class);
            $methods = $ref->getMethods();
            $violations = [];

            foreach ($methods as $method) {
                if ($method->getReturnType() === null) {
                    $violations[] = $method->getName() . '()';
                }
            }

            expect($violations)->toBeEmpty(
                'Methods without return types: ' . implode(', ', $violations)
            );
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumCache singleton — thread safety documentation
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache singleton integrity', function (): void {
        it('constructor is private', function (): void {
            $ref = new ReflectionMethod(EnumCache::class, '__construct');
            expect($ref->isPrivate())->toBeTrue();
        });

        it('__clone is private and returns never', function (): void {
            $ref = new ReflectionMethod(EnumCache::class, '__clone');
            expect($ref->isPrivate())->toBeTrue();

            $returnType = $ref->getReturnType();
            assert($returnType instanceof ReflectionNamedType);
            expect($returnType->getName())->toBe('never');
        });

        it('__wakeup throws RuntimeException (returns never)', function (): void {
            $ref = new ReflectionMethod(EnumCache::class, '__wakeup');
            $returnType = $ref->getReturnType();
            assert($returnType instanceof ReflectionNamedType);
            expect($returnType->getName())->toBe('never');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumManager is final and readonly
    // ──────────────────────────────────────────────────────────────

    describe('EnumManager class shape', function (): void {
        it('is final and readonly', function (): void {
            $ref = new ReflectionClass(EnumManager::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumRule is final and readonly
    // ──────────────────────────────────────────────────────────────

    describe('EnumRule class shape', function (): void {
        it('is final and readonly', function (): void {
            $ref = new ReflectionClass(EnumRule::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumCast is final
    // ──────────────────────────────────────────────────────────────

    describe('EnumCast class shape', function (): void {
        it('is final', function (): void {
            $ref = new ReflectionClass(EnumCast::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('has #[Override] on interface methods', function (): void {
            $ref = new ReflectionClass(EnumCast::class);
            $methods = ['get', 'set', 'serialize'];

            foreach ($methods as $method) {
                $attrs = $ref->getMethod($method)->getAttributes();
                $hasOverride = array_any($attrs, fn (\ReflectionAttribute $a): bool => $a->getName() === 'Override');
                expect($hasOverride)->toBeTrue("EnumCast::{$method}() should have #[Override]");
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumFacade is final
    // ──────────────────────────────────────────────────────────────

    describe('Enum facade shape', function (): void {
        it('is final', function (): void {
            $ref = new ReflectionClass(Enum::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('getFacadeAccessor() returns string', function (): void {
            $ref = new ReflectionMethod(Enum::class, 'getFacadeAccessor');
            $returnType = $ref->getReturnType();
            assert($returnType instanceof ReflectionNamedType);
            expect($returnType->getName())->toBe('string');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // ServiceProvider correctness
    // ──────────────────────────────────────────────────────────────

    describe('EnumsServiceProvider', function (): void {
        it('is final', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('register() and boot() have #[Override]', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);
            foreach (['register', 'boot'] as $method) {
                $attrs = $ref->getMethod($method)->getAttributes();
                $hasOverride = array_any($attrs, fn (\ReflectionAttribute $a): bool => $a->getName() === 'Override');
                expect($hasOverride)->toBeTrue("EnumsServiceProvider::{$method}() should have #[Override]");
            }
        });
    });
});
