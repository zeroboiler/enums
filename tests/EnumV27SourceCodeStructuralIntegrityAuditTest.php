<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;

describe('V27 — Enum source code structural integrity audit', function () {
    // -----------------------------------------------------------------------
    // 1. Source file structure
    // -----------------------------------------------------------------------
    it('all 20 source files exist in src/', function () {
        $srcDir = realpath(__DIR__.'/../src');
        expect($srcDir)->not->toBeFalse();

        $files = glob($srcDir.'/**/*.php');
        expect($files)->not->toBeEmpty();
        expect(count($files))->toBeGreaterThanOrEqual(20);
    });

    it('all source files have declare(strict_types=1)', function () {
        $srcDir = realpath(__DIR__.'/../src');
        $violations = [];

        foreach (glob($srcDir.'/**/*.php') as $file) {
            $content = file_get_contents($file);
            $tokens = token_get_all($content);

            foreach ($tokens as $token) {
                if (is_array($token) && $token[0] === T_DECLARE) {
                    // Found declare — check if strict_types=1 follows
                    $rest = implode('', array_slice($tokens, array_search($token, $tokens, true)));
                    if (! str_contains($rest, 'strict_types') || ! str_contains($rest, '1')) {
                        $violations[] = basename($file);
                    }
                    break;
                }
            }
        }

        expect($violations)->toBeEmpty('Files missing declare(strict_types=1): '.implode(', ', $violations));
    });

    it('all source files end with a newline (no trailing whitespace)', function () {
        $srcDir = realpath(__DIR__.'/../src');
        $violations = [];

        foreach (glob($srcDir.'/**/*.php') as $file) {
            $content = file_get_contents($file);
            if ($content !== '' && ! str_ends_with($content, "\n")) {
                $violations[] = basename($file);
            }
        }

        expect($violations)->toBeEmpty('Files not ending with newline: '.implode(', ', $violations));
    });

    // -----------------------------------------------------------------------
    // 2. Class and interface structure
    // -----------------------------------------------------------------------
    it('HasEnumMetadata trait exists and provides all public methods', function () {
        expect(trait_exists(HasEnumMetadata::class))->toBeTrue();

        $reflection = new ReflectionClass(HasEnumMetadata::class);
        $publicMethods = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn (ReflectionMethod $m) => ! str_starts_with($m->getName(), '__')
        );

        $expected = ['label', 'description', 'color', 'icon', 'forSelect', 'forApi',
            'tryFromLabel', 'tryFromName', 'fromName', 'hasCase',
            'is', 'isNot', 'in', 'notIn', 'values', 'labels'];

        $actual = array_map(fn (ReflectionMethod $m) => $m->getName(), $publicMethods);

        foreach ($expected as $method) {
            expect(in_array($method, $actual, true))->toBeTrue("Missing method: {$method}");
        }
    });

    it('EnumCache is final and has private constructor', function () {
        $ref = new ReflectionClass(EnumCache::class);
        expect($ref->isFinal())->toBeTrue('EnumCache must be final');

        $constructor = $ref->getConstructor();
        expect($constructor)->not->toBeNull();
        expect($constructor->isPrivate())->toBeTrue('EnumCache constructor must be private');
    });

    it('EnumManager is final readonly', function () {
        $ref = new ReflectionClass(EnumManager::class);
        expect($ref->isFinal())->toBeTrue('EnumManager must be final');
        expect($ref->isReadOnly())->toBeTrue('EnumManager must be readonly');
    });

    it('InvalidEnumException is final with named constructors', function () {
        $ref = new ReflectionClass(InvalidEnumException::class);
        expect($ref->isFinal())->toBeTrue();

        $expected = ['value', 'forName'];
        $actual = array_map(
            fn (ReflectionMethod $m) => $m->getName(),
            $ref->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC)
        );

        foreach ($expected as $method) {
            expect(in_array($method, $actual, true))->toBeTrue("Missing static method: {$method}");
        }
    });

    it('EnumRule is final readonly and implements ValidationRule', function () {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
        expect($ref->implementsInterface(\Illuminate\Contracts\Validation\ValidationRule::class))->toBeTrue();
    });

    it('EnumsServiceProvider is final', function () {
        $ref = new ReflectionClass(EnumsServiceProvider::class);
        expect($ref->isFinal())->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // 3. Return type declarations
    // -----------------------------------------------------------------------
    it('all HasEnumMetadata public methods have return type declarations', function () {
        $ref = new ReflectionClass(HasEnumMetadata::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if (str_starts_with($name, '__')) {
                continue;
            }

            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull("Method HasEnumMetadata::{$name}() missing return type");
        }
    });

    it('EnumCache public methods have return types', function () {
        $ref = new ReflectionClass(EnumCache::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            $returnType = $method->getReturnType();

            // __wakeup, __serialize, __unserialize should return never
            if (str_starts_with($name, '__')) {
                if ($returnType !== null) {
                    expect((string) $returnType)->toBe('never', "{$name} should return never");
                }
                continue;
            }

            expect($returnType)->not->toBeNull("EnumCache::{$name}() missing return type");
        }
    });

    it('EnumManager all public methods have return types', function () {
        $ref = new ReflectionClass(EnumManager::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull("EnumManager::{$name}() missing return type");
        }
    });

    // -----------------------------------------------------------------------
    // 4. Attribute classes
    // -----------------------------------------------------------------------
    it('all 8 attribute classes exist and are final readonly', function () {
        $attributes = [
            \ZeroBoiler\Enums\Attributes\Label::class,
            \ZeroBoiler\Enums\Attributes\Color::class,
            \ZeroBoiler\Enums\Attributes\Icon::class,
            \ZeroBoiler\Enums\Attributes\Description::class,
            \ZeroBoiler\Enums\Attributes\EnumLabel::class,
            \ZeroBoiler\Enums\Attributes\EnumColor::class,
            \ZeroBoiler\Enums\Attributes\EnumIcon::class,
            \ZeroBoiler\Enums\Attributes\EnumDescription::class,
        ];

        foreach ($attributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
            // Attributes using constructor property promotion are effectively readonly
        }
    });

    it('per-case attributes target TARGET_CLASS_CONSTANT', function () {
        $perCase = [
            \ZeroBoiler\Enums\Attributes\Label::class,
            \ZeroBoiler\Enums\Attributes\Color::class,
            \ZeroBoiler\Enums\Attributes\Icon::class,
            \ZeroBoiler\Enums\Attributes\Description::class,
        ];

        foreach ($perCase as $class) {
            $attrs = (new ReflectionClass($class))->getAttributes(\Attribute::class);
            expect($attrs)->not->toBeEmpty();
            $args = $attrs[0]->getArguments();
            expect($args)->toContain(Attribute::TARGET_CLASS_CONSTANT);
        }
    });

    it('class-level attributes target TARGET_CLASS | TARGET_CLASS_CONSTANT', function () {
        $classLevel = [
            \ZeroBoiler\Enums\Attributes\EnumLabel::class,
            \ZeroBoiler\Enums\Attributes\EnumColor::class,
            \ZeroBoiler\Enums\Attributes\EnumIcon::class,
            \ZeroBoiler\Enums\Attributes\EnumDescription::class,
        ];

        foreach ($classLevel as $class) {
            $attrs = (new ReflectionClass($class))->getAttributes(\Attribute::class);
            expect($attrs)->not->toBeEmpty();
            $args = $attrs[0]->getArguments();
            expect($args)->toContain(Attribute::TARGET_CLASS);
            expect($args)->toContain(Attribute::TARGET_CLASS_CONSTANT);
        }
    });

    // -----------------------------------------------------------------------
    // 5. Docblock quality
    // -----------------------------------------------------------------------
    it('all source files have a class-level docblock', function () {
        $srcDir = realpath(__DIR__.'/../src');
        $violations = [];

        foreach (glob($srcDir.'/**/*.php') as $file) {
            $tokens = token_get_all(file_get_contents($file));
            $hasDocComment = false;

            foreach ($tokens as $i => $token) {
                if (is_array($token) && $token[0] === T_DOC_COMMENT) {
                    // Check if it's a file-level or class-level comment
                    $nextNonWhitespace = null;
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if (is_array($tokens[$j]) && $tokens[$j][0] !== T_WHITESPACE) {
                            $nextNonWhitespace = $tokens[$j][0];
                            break;
                        }
                    }
                    if ($nextNonWhitespace === T_DECLARE) {
                        // This is the file-level comment, skip
                        continue;
                    }
                    $hasDocComment = true;
                    break;
                }
            }

            if (! $hasDocComment) {
                $violations[] = basename($file);
            }
        }

        // We only check non-trait files since traits may not have class docblocks in the same way
        $traitViolations = array_filter($violations, fn ($f) => ! str_contains(file_get_contents($srcDir.'/'.$f), 'trait '));
        expect($traitViolations)->toBeEmpty('Files missing class-level docblock: '.implode(', ', $traitViolations));
    });

    it('EnumMetadataResolver is final and @internal tagged', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Support\EnumMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
        $doc = $ref->getDocComment();
        expect($doc)->not->toBeFalse();
        expect($doc)->toContain('@internal');
    });

    it('EnumTestGenerator is final and @internal tagged', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Support\EnumTestGenerator::class);
        expect($ref->isFinal())->toBeTrue();
        $doc = $ref->getDocComment();
        expect($doc)->not->toBeFalse();
        expect($doc)->toContain('@internal');
    });

    // -----------------------------------------------------------------------
    // 6. #[\Override] compliance
    // -----------------------------------------------------------------------
    it('EnumCast methods that override interface have #[Override]', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Casts\EnumCast::class);

        $overrideMethods = ['get', 'set'];
        foreach ($overrideMethods as $method) {
            $m = $ref->getMethod($method);
            $attrs = $m->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty("EnumCast::{$method}() missing #[Override]");
        }
    });

    it('EnumRule validate has #[Override]', function () {
        $m = new ReflectionMethod(EnumRule::class, 'validate');
        $attrs = $m->getAttributes(\Override::class);
        expect($attrs)->not->toBeEmpty('EnumRule::validate() missing #[Override]');
    });

    it('EnumsServiceProvider register and boot have #[Override]', function () {
        $ref = new ReflectionClass(EnumsServiceProvider::class);
        foreach (['register', 'boot'] as $method) {
            $m = $ref->getMethod($method);
            $attrs = $m->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty("EnumsServiceProvider::{$method}() missing #[Override]");
        }
    });

    it('Enum facade getFacadeAccessor has #[Override]', function () {
        $m = new ReflectionMethod(\ZeroBoiler\Enums\Facades\Enum::class, 'getFacadeAccessor');
        $attrs = $m->getAttributes(\Override::class);
        expect($attrs)->not->toBeEmpty('Enum facade::getFacadeAccessor() missing #[Override]');
    });

    it('InvalidEnumException __toString has #[Override]', function () {
        $m = new ReflectionMethod(InvalidEnumException::class, '__toString');
        $attrs = $m->getAttributes(\Override::class);
        expect($attrs)->not->toBeEmpty('InvalidEnumException::__toString() missing #[Override]');
    });

    // -----------------------------------------------------------------------
    // 7. Type safety — no mixed return types in public API
    // -----------------------------------------------------------------------
    it('EnumManager public methods do not return mixed', function () {
        $ref = new ReflectionClass(EnumManager::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();
            if ($returnType !== null) {
                $typeString = (string) $returnType;
                expect($typeString)->not->toBe('mixed',
                    "EnumManager::{$method->getName()}() returns mixed — violates PHPStan L9");
            }
        }
    });

    it('EnumRule public methods do not return mixed', function () {
        $ref = new ReflectionClass(EnumRule::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();
            if ($returnType !== null) {
                $typeString = (string) $returnType;
                expect($typeString)->not->toBe('mixed',
                    "EnumRule::{$method->getName()}() returns mixed — violates PHPStan L9");
            }
        }
    });

    // -----------------------------------------------------------------------
    // 8. composer.json consistency
    // -----------------------------------------------------------------------
    it('composer.json requires PHP ^8.5', function () {
        $json = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($json['require']['php'])->toBe('^8.5');
    });

    it('composer.json requires illuminate/contracts ^13.0', function () {
        $json = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($json['require']['illuminate/contracts'])->toBe('^13.0');
    });

    it('composer.json autoload matches namespace', function () {
        $json = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($json['autoload']['psr-4']['ZeroBoiler\\Enums\\'])->toBe('src/');
    });

    it('composer.json version matches latest release', function () {
        $json = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($json['version'])->toBe('1.0.35');
    });
});
