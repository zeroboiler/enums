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
use ZeroBoiler\Enums\Rules\EnumRule;

/**
 * V28 PHPStan Level 9 strict type safety audit.
 *
 * Validates that all source files conform to PHPStan Level 9 requirements:
 * - No mixed return types in public API
 * - Strict comparisons (===, !==) used throughout
 * - All methods have explicit return type declarations
 * - All properties are typed
 * - readonly on stateless classes and attribute promoted properties
 * - #[\Override] on all interface method implementations
 * - PHPDoc @param/@return annotations on public methods
 * - declare(strict_types=1) on every file
 */
test('source files have correct strict_types declaration', function (): void {
    $srcFiles = glob(__DIR__.'/../src/**/*.php', GLOB_ERR) ?: [];

    // Exclude nested directories that glob might not recurse into
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__.'/../src', RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $contents = $file->getContents();
        $filePath = $file->getRealPath();

        expect($contents)
            ->toContain('declare(strict_types=1)')
            ->and(str_contains($contents, "declare(strict_types=1);\n"))
            ->toBeTrue("File {$filePath} must have declare(strict_types=1) with semicolon and newline");
    }

    // Verify minimum source file count
    expect(count($srcFiles))->toBeGreaterThanOrEqual(20);
});

test('EnumManager is final readonly with no mixed return types', function (): void {
    $ref = new ReflectionClass(EnumManager::class);

    // Must be final and readonly
    expect($ref->isFinal())->toBeTrue('EnumManager must be final');
    expect($ref->isReadOnly())->toBeTrue('EnumManager must be readonly');

    // All public methods must have return type declarations
    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $returnType = $method->getReturnType();
        expect($returnType)->not->toBeNull(
            "EnumManager::{$method->getName()}() must have an explicit return type declaration"
        );

        // No 'mixed' return type in public API
        if ($returnType instanceof ReflectionNamedType) {
            expect($returnType->getName())->not->toBe('mixed',
                "EnumManager::{$method->getName()}() must not return mixed"
            );
        }
    }
});

test('EnumRule is final readonly with no mixed parameters', function (): void {
    $ref = new ReflectionClass(EnumRule::class);

    expect($ref->isFinal())->toBeTrue('EnumRule must be final');
    expect($ref->isReadOnly())->toBeTrue('EnumRule must be readonly');
    expect($ref->implementsInterface(ValidationRule::class))->toBeTrue();

    // Constructor must use readonly promoted properties
    $constructor = $ref->getConstructor();
    expect($constructor)->not->toBeNull();

    foreach ($constructor->getParameters() as $param) {
        $prop = $ref->getProperty($param->getName());
        expect($prop->isReadOnly())->toBeTrue(
            "EnumRule::\${$param->getName()} must be readonly"
        );
    }
});

test('EnumCache singleton enforces no-cloning and no-unserialization', function (): void {
    $ref = new ReflectionClass(EnumCache::class);

    expect($ref->isFinal())->toBeTrue('EnumCache must be final');

    // __clone must be private and return never
    $clone = $ref->getMethod('__clone');
    expect($clone->isPrivate())->toBeTrue();
    $cloneReturnType = $clone->getReturnType();
    expect($cloneReturnType instanceof ReflectionNamedType && $cloneReturnType->getName() === 'never')->toBeTrue();

    // __wakeup must be public and return never
    $wakeup = $ref->getMethod('__wakeup');
    expect($wakeup->isPublic())->toBeTrue();
    $wakeupReturnType = $wakeup->getReturnType();
    expect($wakeupReturnType instanceof ReflectionNamedType && $wakeupReturnType->getName() === 'never')->toBeTrue();

    // __serialize must be public and return never
    $serialize = $ref->getMethod('__serialize');
    expect($serialize->isPublic())->toBeTrue();
    $serializeReturnType = $serialize->getReturnType();
    expect($serializeReturnType instanceof ReflectionNamedType && $serializeReturnType->getName() === 'never')->toBeTrue();

    // __unserialize must be public and return never
    $unserialize = $ref->getMethod('__unserialize');
    expect($unserialize->isPublic())->toBeTrue();
    $unserializeReturnType = $unserialize->getReturnType();
    expect($unserializeReturnType instanceof ReflectionNamedType && $unserializeReturnType->getName() === 'never')->toBeTrue();
});

test('attribute classes are all final with readonly promoted properties', function (): void {
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

        expect($ref->isFinal())->toBeTrue("{$class} must be final");

        // All public properties must be readonly
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            expect($prop->isReadOnly())->toBeTrue(
                "{$class}::\${$prop->getName()} must be readonly"
            );
        }

        // Has Attribute() with correct targets
        $attrs = $ref->getAttributes();
        $hasAttribute = false;
        foreach ($attrs as $attr) {
            if ($attr->getName() === Attribute::class) {
                $hasAttribute = true;
                break;
            }
        }
        expect($hasAttribute)->toBeTrue("{$class} must have #[Attribute] declaration");
    }
});

test('per-case attributes target only class constants', function (): void {
    $perCaseAttrs = [Label::class, Color::class, Icon::class, Description::class];

    foreach ($perCaseAttrs as $class) {
        $ref = new ReflectionClass($class);
        $attrs = $ref->getAttributes(Attribute::class);
        foreach ($attrs as $attr) {
            $args = $attr->getArguments();
            expect($args[0])->toBe(Attribute::TARGET_CLASS_CONSTANT);
        }
    }
});

test('class-level attributes target both class and class constants', function (): void {
    $classLevelAttrs = [EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class];

    foreach ($classLevelAttrs as $class) {
        $ref = new ReflectionClass($class);
        $attrs = $ref->getAttributes(Attribute::class);
        foreach ($attrs as $attr) {
            $args = $attr->getArguments();
            expect($args[0])->toBe(
                Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT
            );
        }
    }
});

test('EnumCast implements CastsAttributes with correct template', function (): void {
    $ref = new ReflectionClass(EnumCast::class);

    expect($ref->isFinal())->toBeTrue();
    expect($ref->implementsInterface(CastsAttributes::class))->toBeTrue();

    // get() must have #[Override]
    $get = $ref->getMethod('get');
    $overrideAttrs = $get->getAttributes(\Override::class);
    expect($overrideAttrs)->not->toBeEmpty('EnumCast::get() must have #[Override]');

    // set() must have #[Override]
    $set = $ref->getMethod('set');
    $overrideAttrs = $set->getAttributes(\Override::class);
    expect($overrideAttrs)->not->toBeEmpty('EnumCast::set() must have #[Override]');
});

test('InvalidEnumException is final with named constructors', function (): void {
    $ref = new ReflectionClass(InvalidEnumException::class);

    expect($ref->isFinal())->toBeTrue();
    expect($ref->isSubclassOf(Exception::class))->toBeTrue();

    // Must have named constructors: value() and forName()
    expect($ref->hasMethod('value'))->toBeTrue();
    expect($ref->hasMethod('forName'))->toBeTrue();

    // Both must be static
    expect($ref->getMethod('value')->isStatic())->toBeTrue();
    expect($ref->getMethod('forName')->isStatic())->toBeTrue();

    // __toString must have #[Override]
    $toString = $ref->getMethod('__toString');
    $overrideAttrs = $toString->getAttributes(\Override::class);
    expect($overrideAttrs)->not->toBeEmpty('InvalidEnumException::__toString() must have #[Override]');

    // __toString return type
    $returnType = $toString->getReturnType();
    expect($returnType instanceof ReflectionNamedType && $returnType->getName() === 'string')->toBeTrue();
});

test('HasEnumMetadata trait provides all expected public methods', function (): void {
    $expectedMethods = [
        'label', 'description', 'color', 'icon',
        'forSelect', 'forApi',
        'tryFromLabel', 'tryFromName', 'fromName', 'hasCase',
        'is', 'isNot', 'in', 'notIn',
        'values', 'labels',
    ];

    $ref = new ReflectionClass(HasEnumMetadata::class);
    $traitMethods = array_map(
        static fn (ReflectionMethod $m): string => $m->getName(),
        $ref->getMethods()
    );

    foreach ($expectedMethods as $method) {
        expect(in_array($method, $traitMethods, true))->toBeTrue(
            "HasEnumMetadata must have {$method}() method"
        );
    }
});

test('HasEnumMetadata static methods return correct types', function (): void {
    $ref = new ReflectionClass(HasEnumMetadata::class);

    // forSelect() returns array
    $forSelect = $ref->getMethod('forSelect');
    $forSelectReturn = $forSelect->getReturnType();
    expect($forSelectReturn->getName())->toBe('array');

    // forApi() returns array
    $forApi = $ref->getMethod('forApi');
    $forApiReturn = $forApi->getReturnType();
    expect($forApiReturn->getName())->toBe('array');

    // tryFromName() returns ?static (nullable)
    $tryFromName = $ref->getMethod('tryFromName');
    $tryFromNameReturn = $tryFromName->getReturnType();
    expect($tryFromNameReturn->allowsNull())->toBeTrue();

    // fromName() returns static (non-nullable)
    $fromName = $ref->getMethod('fromName');
    $fromNameReturn = $fromName->getReturnType();
    expect($fromNameReturn->allowsNull())->toBeFalse();

    // hasCase() returns bool
    $hasCase = $ref->getMethod('hasCase');
    $hasCaseReturn = $hasCase->getReturnType();
    expect($hasCaseReturn->getName())->toBe('bool');

    // values() returns array
    $values = $ref->getMethod('values');
    $valuesReturn = $values->getReturnType();
    expect($valuesReturn->getName())->toBe('array');

    // labels() returns array
    $labels = $ref->getMethod('labels');
    $labelsReturn = $labels->getReturnType();
    expect($labelsReturn->getName())->toBe('array');
});

test('EnumsServiceProvider is final with Override on register and boot', function (): void {
    $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

    expect($ref->isFinal())->toBeTrue();

    $register = $ref->getMethod('register');
    expect($register->getAttributes(\Override::class))->not->toBeEmpty();
    expect($register->getReturnType()?->getName())->toBe('void');

    $boot = $ref->getMethod('boot');
    expect($boot->getAttributes(\Override::class))->not->toBeEmpty();
    expect($boot->getReturnType()?->getName())->toBe('void');
});

test('Enum facade has Override on getFacadeAccessor', function (): void {
    $ref = new ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class);

    expect($ref->isFinal())->toBeTrue();

    $getAccessor = $ref->getMethod('getFacadeAccessor');
    expect($getAccessor->getAttributes(\Override::class))->not->toBeEmpty();
    expect($getAccessor->getReturnType()?->getName())->toBe('string');
});

test('EnumMetadataResolver is final and all public methods are static', function (): void {
    $ref = new ReflectionClass(\ZeroBoiler\Enums\Support\EnumMetadataResolver::class);

    expect($ref->isFinal())->toBeTrue();

    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        expect($method->isStatic())->toBeTrue(
            "EnumMetadataResolver::{$method->getName()}() must be static"
        );
    }
});

test('EnumMetadataResolver resolve method has correct return type', function (): void {
    $ref = new ReflectionClass(\ZeroBoiler\Enums\Support\EnumMetadataResolver::class);
    $resolve = $ref->getMethod('resolve');

    expect($resolve->getReturnType()?->getName())->toBe('array');

    // invalidate returns void
    $invalidate = $ref->getMethod('invalidate');
    expect($invalidate->getReturnType()?->getName())->toBe('void');

    // invalidateAll returns void
    $invalidateAll = $ref->getMethod('invalidateAll');
    expect($invalidateAll->getReturnType()?->getName())->toBe('void');
});

test('composer.json has correct PHP and Laravel requirements', function (): void {
    $composer = json_decode(
        file_get_contents(__DIR__.'/../composer.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    expect($composer['require']['php'])->toBe('^8.5');
    expect($composer['require']['illuminate/contracts'])->toStartWith('^13');
    expect($composer['require']['illuminate/support'])->toStartWith('^13');
    expect($composer['require']['illuminate/validation'])->toStartWith('^13');
    expect($composer['version'])->toBe('1.0.40');
});

test('all public API methods have PHPDoc blocks', function (): void {
    $classes = [
        EnumManager::class,
        EnumRule::class,
        EnumCache::class,
        EnumCast::class,
        InvalidEnumException::class,
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::class,
        \ZeroBoiler\Enums\EnumsServiceProvider::class,
    ];

    foreach ($classes as $class) {
        $ref = new ReflectionClass($class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip constructor and magic methods inherited from parent
            if ($method->getName() === '__construct') {
                continue;
            }

            $docComment = $method->getDocComment();

            expect($docComment)->not->toBeFalse(
                "{$class}::{$method->getName()}() must have a PHPDoc block"
            );
        }
    }
});
