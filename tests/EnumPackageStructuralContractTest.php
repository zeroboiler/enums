<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Attribute;
use ReflectionClass as CoreReflectionClass;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

/**
 * Structural contract test — verifies that every public class in the enums
 * package has the correct modifiers, parent types, and attribute targets.
 *
 * This test prevents accidental breaking changes (e.g., removing `final`,
 * changing an attribute target, or removing a constructor parameter).
 */
describe('Enum Package Structural Contract', function () {
    // -------------------------------------------------------------------
    // Attribute target correctness
    // -------------------------------------------------------------------

    it('Label attribute targets class constants only', function () {
        $ref = new CoreReflectionClass(Label::class);
        $attrs = $ref->getAttributes(Attribute::class);
        expect($attrs)->toHaveCount(1);

        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(Attribute::TARGET_CLASS_CONSTANT);
    });

    it('Color attribute targets class constants only', function () {
        $ref = new CoreReflectionClass(Color::class);
        $attrs = $ref->getAttributes(Attribute::class);
        expect($attrs)->toHaveCount(1);

        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(Attribute::TARGET_CLASS_CONSTANT);
    });

    it('Description attribute targets class constants only', function () {
        $ref = new CoreReflectionClass(Description::class);
        $attrs = $ref->getAttributes(Attribute::class);
        expect($attrs)->toHaveCount(1);

        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(Attribute::TARGET_CLASS_CONSTANT);
    });

    it('Icon attribute targets class constants only', function () {
        $ref = new CoreReflectionClass(Icon::class);
        $attrs = $ref->getAttributes(Attribute::class);
        expect($attrs)->toHaveCount(1);

        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(Attribute::TARGET_CLASS_CONSTANT);
    });

    it('EnumLabel attribute targets class and class constants', function () {
        $ref = new CoreReflectionClass(EnumLabel::class);
        $attrs = $ref->getAttributes(Attribute::class);
        expect($attrs)->toHaveCount(1);

        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(
            Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT
        );
    });

    it('EnumColor attribute targets class and class constants', function () {
        $ref = new CoreReflectionClass(EnumColor::class);
        $attrs = $ref->getAttributes(Attribute::class);
        expect($attrs)->toHaveCount(1);

        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(
            Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT
        );
    });

    it('EnumDescription attribute targets class and class constants', function () {
        $ref = new CoreReflectionClass(EnumDescription::class);
        $attrs = $ref->getAttributes(Attribute::class);
        expect($attrs)->toHaveCount(1);

        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(
            Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT
        );
    });

    it('EnumIcon attribute targets class and class constants', function () {
        $ref = new CoreReflectionClass(EnumIcon::class);
        $attrs = $ref->getAttributes(Attribute::class);
        expect($attrs)->toHaveCount(1);

        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(
            Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT
        );
    });

    // -------------------------------------------------------------------
    // Final class enforcement
    // -------------------------------------------------------------------

    it('all per-case attributes are final classes', function () {
        foreach ([Label::class, Color::class, Description::class, Icon::class] as $class) {
            $ref = new CoreReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('all class-level attributes are final classes', function () {
        foreach ([EnumLabel::class, EnumColor::class, EnumDescription::class, EnumIcon::class] as $class) {
            $ref = new CoreReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('EnumManager is final and readonly', function () {
        $ref = new CoreReflectionClass(EnumManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('EnumCache is final but not readonly (mutable singleton)', function () {
        $ref = new CoreReflectionClass(EnumCache::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeFalse();
    });

    it('EnumRule is final and readonly', function () {
        $ref = new CoreReflectionClass(EnumRule::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('EnumCast is final', function () {
        $ref = new CoreReflectionClass(EnumCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('InvalidEnumException is final', function () {
        $ref = new CoreReflectionClass(InvalidEnumException::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumMetadataResolver is final', function () {
        $ref = new CoreReflectionClass(EnumMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumTestGenerator is final', function () {
        $ref = new CoreReflectionClass(EnumTestGenerator::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumsServiceProvider is final', function () {
        $ref = new CoreReflectionClass(EnumsServiceProvider::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('Enum facade is final', function () {
        $ref = new CoreReflectionClass(Enum::class);
        expect($ref->isFinal())->toBeTrue();
    });

    // -------------------------------------------------------------------
    // Constructor parameter correctness
    // -------------------------------------------------------------------

    it('per-case attributes have a single readonly string $value property', function () {
        foreach ([Label::class, Color::class, Description::class, Icon::class] as $class) {
            $ref = new CoreReflectionClass($class);
            $prop = $ref->getProperty('value');
            expect($prop->isReadOnly())->toBeTrue();
            expect($prop->getType()->getName())->toBe('string');
        }
    });

    it('EnumLabel has nullable labels array and nullable label string', function () {
        $ref = new CoreReflectionClass(EnumLabel::class);
        $labelsProp = $ref->getProperty('labels');
        expect($labelsProp->getType()->allowsNull())->toBeTrue();

        $labelProp = $ref->getProperty('label');
        expect($labelProp->getType()->allowsNull())->toBeTrue();
    });

    it('EnumColor has five array properties for color groups', function () {
        $ref = new CoreReflectionClass(EnumColor::class);
        $expectedProps = ['success', 'danger', 'warning', 'info', 'secondary'];

        foreach ($expectedProps as $propName) {
            expect($ref->hasProperty($propName))->toBeTrue("EnumColor::\${$propName} must exist");
        }
    });

    it('EnumIcon has nullable default and non-null icons array', function () {
        $ref = new CoreReflectionClass(EnumIcon::class);

        $defaultProp = $ref->getProperty('default');
        expect($defaultProp->getType()->allowsNull())->toBeTrue();

        $iconsProp = $ref->getProperty('icons');
        expect($iconsProp->getType()->allowsNull())->toBeFalse();
    });

    it('EnumDescription has nullable descriptions array and nullable description string', function () {
        $ref = new CoreReflectionClass(EnumDescription::class);
        $descsProp = $ref->getProperty('descriptions');
        expect($descsProp->getType()->allowsNull())->toBeTrue();

        $descProp = $ref->getProperty('description');
        expect($descProp->getType()->allowsNull())->toBeTrue();
    });

    // -------------------------------------------------------------------
    // Interface implementations
    // -------------------------------------------------------------------

    it('EnumCast implements CastsAttributes', function () {
        expect(in_array(
            \Illuminate\Contracts\Database\Eloquent\CastsAttributes::class,
            class_implements(EnumCast::class) ?: [],
            true
        ))->toBeTrue();
    });

    it('EnumRule implements ValidationRule', function () {
        expect(in_array(
            \Illuminate\Contracts\Validation\ValidationRule::class,
            class_implements(EnumRule::class) ?: [],
            true
        ))->toBeTrue();
    });

    it('InvalidEnumException extends Exception', function () {
        expect(is_subclass_of(InvalidEnumException::class, \Exception::class))->toBeTrue();
    });

    // -------------------------------------------------------------------
    // HasEnumMetadata trait completeness
    // -------------------------------------------------------------------

    it('HasEnumMetadata trait provides all expected methods', function () {
        $expectedMethods = [
            'label', 'description', 'color', 'icon',
            'forSelect', 'forApi',
            'tryFromLabel', 'tryFromName', 'fromName', 'hasCase',
            'is', 'isNot', 'in', 'notIn',
            'values', 'labels',
        ];

        $ref = new CoreReflectionClass(HasEnumMetadata::class);

        foreach ($expectedMethods as $method) {
            expect($ref->hasMethod($method))->toBeTrue("HasEnumMetadata must have method {$method}()");
        }
    });

    it('HasEnumMetadata trait static methods return correct types', function () {
        $ref = new CoreReflectionClass(HasEnumMetadata::class);

        // forSelect should return array
        expect($ref->getMethod('forSelect')->getReturnType()?->getName())->toBe('array');

        // forApi should return array
        expect($ref->getMethod('forApi')->getReturnType()?->getName())->toBe('array');

        // tryFromLabel should be nullable
        expect($ref->getMethod('tryFromLabel')->getReturnType()?->allowsNull())->toBeTrue();

        // tryFromName should be nullable
        expect($ref->getMethod('tryFromName')->getReturnType()?->allowsNull())->toBeTrue();

        // fromName should NOT be nullable
        expect($ref->getMethod('fromName')->getReturnType()?->allowsNull())->toBeFalse();

        // hasCase should return bool
        expect($ref->getMethod('hasCase')->getReturnType()?->getName())->toBe('bool');
    });

    // -------------------------------------------------------------------
    // EnumManager method completeness
    // -------------------------------------------------------------------

    it('EnumManager delegates all trait methods', function () {
        $expectedMethods = [
            'forSelect', 'forApi', 'tryFromLabel',
            'tryFromName', 'fromName', 'hasCase',
            'values', 'labels',
        ];

        $ref = new CoreReflectionClass(EnumManager::class);

        foreach ($expectedMethods as $method) {
            expect($ref->hasMethod($method))->toBeTrue("EnumManager must have method {$method}()");
        }
    });

    // -------------------------------------------------------------------
    // EnumCache singleton contract
    // -------------------------------------------------------------------

    it('EnumCache has private constructor', function () {
        $ref = new CoreReflectionClass(EnumCache::class);
        expect($ref->getConstructor()?->isPrivate())->toBeTrue();
    });

    it('EnumCache prevents cloning', function () {
        $ref = new CoreReflectionClass(EnumCache::class);
        $cloneMethod = $ref->getMethod('__clone');
        expect($cloneMethod->isPrivate())->toBeTrue();
        expect($cloneMethod->getReturnType()?->getName())->toBe('never');
    });

    it('EnumCache prevents serialization via __serialize', function () {
        $ref = new CoreReflectionClass(EnumCache::class);
        $method = $ref->getMethod('__serialize');
        expect($method->getReturnType()?->getName())->toBe('never');
    });

    it('EnumCache prevents unserialization via __unserialize', function () {
        $ref = new CoreReflectionClass(EnumCache::class);
        $method = $ref->getMethod('__unserialize');
        expect($method->getReturnType()?->getName())->toBe('never');
    });

    // -------------------------------------------------------------------
    // declare(strict_types=1) enforcement
    // -------------------------------------------------------------------

    it('all source files declare strict types', function () {
        $srcDir = dirname((new CoreReflectionClass(HasEnumMetadata::class))->getFileName());
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        expect($phpFiles)->not->toBeEmpty();

        foreach ($phpFiles as $filePath) {
            $contents = file_get_contents($filePath);
            $hasStrict = str_contains($contents, 'declare(strict_types=1)');
            expect($hasStrict)->toBeTrue("{$filePath} must declare strict_types=1");
        }
    });
});
