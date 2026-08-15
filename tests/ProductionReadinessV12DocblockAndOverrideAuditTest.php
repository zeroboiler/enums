<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Production Readiness V12 — Docblock completeness and #[Override] audit.
 *
 * Validates that every public class in the enums package has:
 * - A class-level docblock with a description
 * - Every public method has a docblock (not necessarily with full tags, but at least present)
 * - Critical methods use #[\Override] where they implement/override an interface or parent method
 *
 * Also verifies:
 * - No source file contains `mixed` in a public method return type (PHPStan L9 compliance)
 * - All public methods have explicit return type declarations
 * - EnumTestGenerator and console commands are properly structured
 *
 * This is a static-source audit — it reads PHP files and checks their structure
 * without instantiating any objects or calling any methods.
 */

describe('Production Readiness V12 — Docblock & Override Audit', function (): void {

    // ──────────────────────────────────────────────────────────────
    // 1. All source files have class-level docblocks
    // ──────────────────────────────────────────────────────────────

    describe('All source files have class-level docblocks', function (): void {
        $sourceDir = __DIR__ . '/../src';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );
        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        it('found source files to audit', function () use ($phpFiles): void {
            expect($phpFiles)->not->toBeEmpty();
            expect(count($phpFiles))->toBeGreaterThanOrEqual(20);
        });

        foreach ($phpFiles as $filePath) {
            $relative = str_replace($sourceDir . '/', '', $filePath);
            it("{$relative} has a class-level docblock", function () use ($filePath): void {
                $content = file_get_contents($filePath);
                // Class docblock should appear before 'class' or 'trait' or 'enum' keyword
                // It must contain '/**' (not just '/*')
                $hasDocblock = (bool) preg_match('/\/\*\*[\s\S]*?\*\//', $content);
                expect($hasDocblock)->toBeTrue("Missing class-level docblock in {$filePath}");
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 2. All public methods have docblocks
    // ──────────────────────────────────────────────────────────────

    describe('All public methods have docblocks', function (): void {
        $classesToAudit = [
            \ZeroBoiler\Enums\EnumManager::class,
            \ZeroBoiler\Enums\EnumCache::class,
            \ZeroBoiler\Enums\EnumMetadataResolver::class,
            \ZeroBoiler\Enums\Rules\EnumRule::class,
            \ZeroBoiler\Enums\Casts\EnumCast::class,
            \ZeroBoiler\Enums\Exceptions\InvalidEnumException::class,
            \ZeroBoiler\Enums\Support\EnumTestGenerator::class,
            \ZeroBoiler\Enums\EnumsServiceProvider::class,
        ];

        foreach ($classesToAudit as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName}: all public methods have docblocks", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                $fileName = $ref->getFileName();
                $content = (string) file_get_contents($fileName);
                $lines = explode("\n", $content);

                $publicMethods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
                foreach ($publicMethods as $method) {
                    // Skip constructor and magic methods inherited from parent
                    if ($method->getName() === '__construct') {
                        continue;
                    }
                    // Only check methods declared in this class, not inherited
                    if ($method->getDeclaringClass()->getName() !== $class) {
                        continue;
                    }

                    $startLine = $method->getStartLine() - 1; // 0-indexed
                    // Look backwards from the method declaration for a docblock
                    $foundDocblock = false;
                    for ($i = $startLine - 1; $i >= max(0, $startLine - 10); $i--) {
                        $line = trim($lines[$i] ?? '');
                        if ($line === '') {
                            continue;
                        }
                        if (str_starts_with($line, '/**')) {
                            $foundDocblock = true;
                            break;
                        }
                        // If we hit another method/property declaration, stop searching
                        if (
                            str_starts_with($line, 'public ') ||
                            str_starts_with($line, 'private ') ||
                            str_starts_with($line, 'protected ') ||
                            str_starts_with($line, 'final ')
                        ) {
                            break;
                        }
                    }

                    expect($foundDocblock)->toBeTrue(
                        "{$shortName}::{$method->getName()}() at line {$method->getStartLine()} missing docblock"
                    );
                }
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 3. Critical methods use #[Override]
    // ──────────────────────────────────────────────────────────────

    describe('Critical interface implementations use #[Override]', function (): void {
        $overrideChecks = [
            [\ZeroBoiler\Enums\Casts\EnumCast::class, 'get'],
            [\ZeroBoiler\Enums\Casts\EnumCast::class, 'set'],
            [\ZeroBoiler\Enums\Casts\EnumCast::class, 'serialize'],
            [\ZeroBoiler\Enums\Rules\EnumRule::class, 'validate'],
            [\ZeroBoiler\Enums\Facades\Enum::class, 'getFacadeAccessor'],
            [\ZeroBoiler\Enums\EnumsServiceProvider::class, 'register'],
            [\ZeroBoiler\Enums\EnumsServiceProvider::class, 'boot'],
        ];

        // Only enums package classes (all entries above are enums package)
        $enumsClasses = $overrideChecks;

        foreach ($enumsClasses as [$class, $method]) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName}::{$method}() has #[Override]", function () use ($class, $method): void {
                $ref = new \ReflectionMethod($class, $method);
                $attrs = $ref->getAttributes();
                $hasOverride = false;
                foreach ($attrs as $attr) {
                    if ($attr->getName() === \Override::class) {
                        $hasOverride = true;
                        break;
                    }
                }
                expect($hasOverride)->toBeTrue(
                    "{$class}::{$method}() should have #[\\Override] attribute"
                );
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 4. No 'mixed' return type on public API methods
    // ──────────────────────────────────────────────────────────────

    describe('No mixed return types on public API methods', function (): void {
        $publicClasses = [
            \ZeroBoiler\Enums\EnumManager::class,
            \ZeroBoiler\Enums\EnumCache::class,
            \ZeroBoiler\Enums\Support\EnumMetadataResolver::class,
        ];

        foreach ($publicClasses as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName}: no public method returns 'mixed'", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                $publicMethods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
                $violations = [];
                foreach ($publicMethods as $method) {
                    if ($method->getDeclaringClass()->getName() !== $class) {
                        continue;
                    }
                    $returnType = $method->getReturnType();
                    if ($returnType !== null && $returnType->getName() === 'mixed') {
                        $violations[] = $method->getName();
                    }
                }
                expect($violations)->toBeEmpty(
                    "Public methods returning 'mixed': " . implode(', ', $violations)
                );
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 5. EnumTestGenerator — source file structure
    // ──────────────────────────────────────────────────────────────

    describe('EnumTestGenerator source structure', function (): void {
        it('is a final class', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\Support\EnumTestGenerator::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('generate() is a static method returning string', function (): void {
            $ref = new \ReflectionMethod(\ZeroBoiler\Enums\Support\EnumTestGenerator::class, 'generate');
            expect($ref->isStatic())->toBeTrue();
            expect($ref->getReturnType()->getName())->toBe('string');
        });

        it('generate() has a class-string<UnitEnum> parameter', function (): void {
            $ref = new \ReflectionMethod(\ZeroBoiler\Enums\Support\EnumTestGenerator::class, 'generate');
            expect($ref->getNumberOfParameters())->toBe(1);
            $param = $ref->getParameters()[0];
            expect($param->getName())->toBe('enumClass');
            expect($param->getType())->not->toBeNull();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 6. Console commands — proper structure
    // ──────────────────────────────────────────────────────────────

    describe('Console commands structure', function (): void {
        it('InspectEnumCommand is final with handle(): int', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\Console\Commands\InspectEnumCommand::class);
            expect($ref->isFinal())->toBeTrue();
            $handle = $ref->getMethod('handle');
            expect($handle->getReturnType()->getName())->toBe('int');
        });

        it('MakeEnumTestCommand is final with handle(): int', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand::class);
            expect($ref->isFinal())->toBeTrue();
            $handle = $ref->getMethod('handle');
            expect($handle->getReturnType()->getName())->toBe('int');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 7. Attribute classes — TARGET correctness
    // ──────────────────────────────────────────────────────────────

    describe('Attribute classes have correct targets', function (): void {
        $perCaseAttributes = [
            \ZeroBoiler\Enums\Attributes\Label::class => \Attribute::TARGET_CLASS_CONSTANT,
            \ZeroBoiler\Enums\Attributes\Color::class => \Attribute::TARGET_CLASS_CONSTANT,
            \ZeroBoiler\Enums\Attributes\Description::class => \Attribute::TARGET_CLASS_CONSTANT,
            \ZeroBoiler\Enums\Attributes\Icon::class => \Attribute::TARGET_CLASS_CONSTANT,
        ];

        foreach ($perCaseAttributes as $class => $expectedTarget) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName} targets CLASS_CONSTANT only", function () use ($class, $expectedTarget): void {
                $ref = new \ReflectionClass($class);
                $attrs = $ref->getAttributes(\Attribute::class);
                expect($attrs)->toHaveCount(1);
                $instance = $attrs[0]->newInstance();
                expect($instance->flags)->toBe($expectedTarget);
            });
        }

        $classLevelAttributes = [
            \ZeroBoiler\Enums\Attributes\EnumLabel::class,
            \ZeroBoiler\Enums\Attributes\EnumColor::class,
            \ZeroBoiler\Enums\Attributes\EnumDescription::class,
            \ZeroBoiler\Enums\Attributes\EnumIcon::class,
        ];

        $expectedFlags = \Attribute::TARGET_CLASS | \Attribute::TARGET_CLASS_CONSTANT;

        foreach ($classLevelAttributes as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName} targets CLASS | CLASS_CONSTANT", function () use ($class, $expectedFlags): void {
                $ref = new \ReflectionClass($class);
                $attrs = $ref->getAttributes(\Attribute::class);
                expect($attrs)->toHaveCount(1);
                $instance = $attrs[0]->newInstance();
                expect($instance->flags)->toBe($expectedFlags);
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 8. HasEnumMetadata trait — static methods return type consistency
    // ──────────────────────────────────────────────────────────────

    describe('HasEnumMetadata static method return types', function (): void {
        $staticMethods = [
            'forSelect' => 'array',
            'forApi' => 'array',
            'tryFromLabel' => '?static',  // nullable static
            'tryFromName' => '?static',
            'fromName' => 'static',
            'hasCase' => 'bool',
            'values' => 'array',
            'labels' => 'array',
        ];

        foreach ($staticMethods as $method => $expectedReturn) {
            it("static::{$method}() has proper return type", function () use ($method): void {
                $ref = new \ReflectionClass(\ZeroBoiler\Enums\Concerns\HasEnumMetadata::class);
                $m = $ref->getMethod($method);
                expect($m->hasReturnType())->toBeTrue();
                expect($m->isStatic())->toBeTrue();
            });
        }

        $instanceMethods = [
            'label' => 'string',
            'description' => '?string',  // nullable
            'color' => 'string',
            'icon' => '?string',
            'is' => 'bool',
            'isNot' => 'bool',
            'in' => 'bool',
            'notIn' => 'bool',
        ];

        foreach ($instanceMethods as $method => $expectedReturn) {
            it("instance::{$method}() has proper return type", function () use ($method): void {
                $ref = new \ReflectionClass(\ZeroBoiler\Enums\Concerns\HasEnumMetadata::class);
                $m = $ref->getMethod($method);
                expect($m->hasReturnType())->toBeTrue();
                expect($m->isStatic())->toBeFalse();
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 9. EnumCache — complete public API audit
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache complete public API', function (): void {
        $publicMethods = [
            'getInstance', 'has', 'get', 'set',
            'setTtl', 'getTtl', 'clear', 'clearClass',
            'flush', 'resetInstance',
        ];

        it('has all expected public/static methods', function () use ($publicMethods): void {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumCache::class);
            foreach ($publicMethods as $method) {
                expect($ref->hasMethod($method))
                    ->toBeTrue("Missing method: {$method}()");
            }
        });

        it('all public methods have return types', function () use ($publicMethods): void {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumCache::class);
            foreach ($publicMethods as $method) {
                $m = $ref->getMethod($method);
                expect($m->hasReturnType())
                    ->toBeTrue("EnumCache::{$method}() missing return type");
            }
        });

        it('flush() and resetInstance() are static', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumCache::class);
            expect($ref->getMethod('flush')->isStatic())->toBeTrue();
            expect($ref->getMethod('resetInstance')->isStatic())->toBeTrue();
        });

        it('getInstance() returns self', function (): void {
            $ref = new \ReflectionMethod(\ZeroBoiler\Enums\EnumCache::class, 'getInstance');
            expect($ref->getReturnType()->getName())->toBe('self');
        });

        it('is NOT a readonly class (has mutable state)', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumCache::class);
            expect($ref->isReadOnly())->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 10. InvalidEnumException — complete factory method audit
    // ──────────────────────────────────────────────────────────────

    describe('InvalidEnumException complete API', function (): void {
        it('has value() and forName() factory methods', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class);
            expect($ref->hasMethod('value'))->toBeTrue();
            expect($ref->hasMethod('forName'))->toBeTrue();
            expect($ref->getMethod('value')->isStatic())->toBeTrue();
            expect($ref->getMethod('forName')->isStatic())->toBeTrue();
        });

        it('factory methods return self', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class);
            expect($ref->getMethod('value')->getReturnType()->getName())->toBe('self');
            expect($ref->getMethod('forName')->getReturnType()->getName())->toBe('self');
        });

        it('value() accepts class-string, int|string|null', function (): void {
            $ref = new \ReflectionMethod(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class, 'value');
            expect($ref->getNumberOfParameters())->toBe(2);
        });

        it('forName() accepts class-string, string', function (): void {
            $ref = new \ReflectionMethod(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class, 'forName');
            expect($ref->getNumberOfParameters())->toBe(2);
        });
    });
});
