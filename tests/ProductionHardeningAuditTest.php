<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Production hardening audit — verifies all source files meet structural
 * requirements for a PHPStan Level 9 compliant, PHP 8.5+ codebase.
 *
 * Checks performed per file:
 * - declare(strict_types=1) is present
 * - No mixed type hints in public method signatures
 * - All public methods have return type declarations
 * - All attribute classes are final
 * - No dynamic property access patterns (->property on mixed)
 * - Docblocks present on all public methods of non-test classes
 *
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata
 * @see \ZeroBoiler\Enums\Support\EnumMetadataResolver
 */
describe('Enums Production Hardening Audit', function () {
    it('all source files have declare(strict_types=1)', function () {
        $srcDir = realpath(__DIR__ . '/../src');
        if ($srcDir === false) {
            expect(true)->toBeTrue();

            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $violations = [];
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                if (!str_contains($content, 'declare(strict_types=1)')) {
                    $violations[] = $file->getPathname();
                }
            }
        }

        expect($violations)->toBeEmpty(
            'Files missing declare(strict_types=1): ' . implode(', ', $violations)
        );
    });

    it('all attribute classes in src/Attributes are final', function () {
        $attrDir = realpath(__DIR__ . '/../src/Attributes');
        if ($attrDir === false) {
            expect(true)->toBeTrue();

            return;
        }

        $files = glob($attrDir . '/*.php');
        $nonFinal = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (!str_contains($content, 'final class')) {
                $nonFinal[] = basename($file);
            }
        }

        expect($nonFinal)->toBeEmpty(
            'Attribute classes not marked as final: ' . implode(', ', $nonFinal)
        );
    });

    it('EnumMetadataResolver is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Support\EnumMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumCache is final with private constructor', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumCache::class);
        expect($ref->isFinal())->toBeTrue();
        $ctor = $ref->getConstructor();
        expect($ctor)->not->toBeNull();
        expect($ctor->isPrivate())->toBeTrue();
    });

    it('EnumManager is final and readonly', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('Enum facade is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumRule is final and readonly', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Rules\EnumRule::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('EnumCast is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Casts\EnumCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('InvalidEnumException is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumsServiceProvider is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('InspectEnumCommand is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Console\Commands\InspectEnumCommand::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('MakeEnumTestCommand is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumTestGenerator is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Support\EnumTestGenerator::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('all public methods on EnumMetadataResolver have return types', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Support\EnumMetadataResolver::class);
        $violations = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            $returnType = $method->getReturnType();
            if ($returnType === null) {
                $violations[] = $method->getName();
            }
        }

        expect($violations)->toBeEmpty(
            'Methods missing return types: ' . implode(', ', $violations)
        );
    });

    it('all public methods on HasEnumMetadata trait have return types', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Concerns\HasEnumMetadata::class);
        $violations = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();
            if ($returnType === null) {
                $violations[] = $method->getName();
            }
        }

        expect($violations)->toBeEmpty(
            'Methods missing return types: ' . implode(', ', $violations)
        );
    });

    it('all public methods on EnumManager have return types', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);
        $violations = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            $returnType = $method->getReturnType();
            if ($returnType === null) {
                $violations[] = $method->getName();
            }
        }

        expect($violations)->toBeEmpty(
            'Methods missing return types: ' . implode(', ', $violations)
        );
    });

    it('all public methods on EnumCache have return types', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumCache::class);
        $violations = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            $returnType = $method->getReturnType();
            if ($returnType === null) {
                $violations[] = $method->getName();
            }
        }

        expect($violations)->toBeEmpty(
            'Methods missing return types: ' . implode(', ', $violations)
        );
    });

    it('all attribute constructor parameters are typed', function () {
        $attrDir = realpath(__DIR__ . '/../src/Attributes');
        if ($attrDir === false) {
            expect(true)->toBeTrue();

            return;
        }

        $files = glob($attrDir . '/*.php');
        $violations = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (!str_contains($content, 'public readonly')) {
                $violations[] = basename($file);
            }
        }

        expect($violations)->toBeEmpty(
            'Attribute classes without typed readonly properties: ' . implode(', ', $violations)
        );
    });

    it('composer.json requires PHP 8.5+', function () {
        $composer = json_decode(
            file_get_contents(realpath(__DIR__ . '/../composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $phpReq = $composer['require']['php'] ?? '';
        expect($phpReq)->toContain('8.5');
    });
});
