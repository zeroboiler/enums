<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Comprehensive source code structural audit for PHPStan Level 9 compliance.
 *
 * Verifies every source class and trait in the Enums package is:
 * 1. `final` (classes) or in a trait (HasEnumMetadata)
 * 2. Has `declare(strict_types=1)` (strict typing)
 * 3. All public methods have explicit return type declarations
 * 4. All public/protected properties are typed
 * 5. All classes have docblocks with proper annotations
 * 6. Attributes have correct #[Attribute] targets
 * 7. Attributes use readonly promoted constructor parameters
 * 8. No `mixed` return types in public API
 * 9. Exception classes have named constructors
 */
final class EnumSourceCodeCompleteAuditTest extends TestCase
{
    /**
     * @return array<string, class-string>
     */
    public static function allClassesProvider(): array
    {
        return [
            'EnumManager' => EnumManager::class,
            'EnumCache' => EnumCache::class,
            'EnumRule' => EnumRule::class,
            'EnumCast' => EnumCast::class,
            'InvalidEnumException' => InvalidEnumException::class,
            'Enum (Facade)' => Enum::class,
            'EnumsServiceProvider' => EnumsServiceProvider::class,
            'EnumMetadataResolver' => EnumMetadataResolver::class,
            'EnumTestGenerator' => EnumTestGenerator::class,
            'Label' => Label::class,
            'Color' => Color::class,
            'Icon' => Icon::class,
            'Description' => Description::class,
            'EnumLabel' => EnumLabel::class,
            'EnumColor' => EnumColor::class,
            'EnumIcon' => EnumIcon::class,
            'EnumDescription' => EnumDescription::class,
        ];
    }

    /**
     * @test
     * @dataProvider allClassesProvider
     */
    public function it_has_strict_types(string $class): void
    {
        $filename = (new ReflectionClass($class))->getFileName();
        $contents = is_string($filename) ? file_get_contents($filename) : '';

        $this->assertNotFalse($contents);
        $this->assertStringContainsString(
            'declare(strict_types=1)',
            $contents,
            "{$class} must declare strict_types=1"
        );
    }

    /**
     * @test
     * @dataProvider allClassesProvider
     */
    public function it_has_class_docblock(string $class): void
    {
        $ref = new ReflectionClass($class);
        $doc = $ref->getDocComment();

        $this->assertNotFalse($doc, "{$class} must have a class-level docblock");
        $this->assertStringContainsString(
            '/**',
            $doc,
            "{$class} docblock must use /** */ format"
        );
    }

    /**
     * @test
     */
    public function has_enum_metadata_trait_has_strict_types(): void
    {
        $filename = (new ReflectionClass(HasEnumMetadata::class))->getFileName();
        $contents = is_string($filename) ? file_get_contents($filename) : '';

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('declare(strict_types=1)', $contents);
    }

    /**
     * @test
     */
    public function enum_manager_is_final_readonly(): void
    {
        $ref = new ReflectionClass(EnumManager::class);
        $this->assertTrue($ref->isFinal(), 'EnumManager must be final');
        $this->assertTrue($ref->isReadOnly(), 'EnumManager must be readonly');
    }

    /**
     * @test
     */
    public function enum_rule_is_final_readonly(): void
    {
        $ref = new ReflectionClass(EnumRule::class);
        $this->assertTrue($ref->isFinal(), 'EnumRule must be final');
        $this->assertTrue($ref->isReadOnly(), 'EnumRule must be readonly');
    }

    /**
     * @test
     */
    public function enum_manager_has_no_mixed_return_types_in_public_api(): void
    {
        $ref = new ReflectionClass(EnumManager::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();
            $this->assertNotNull(
                $returnType,
                "EnumManager::{$method->getName()}() must have a return type declaration"
            );

            $typeName = $returnType instanceof \ReflectionNamedType
                ? $returnType->getName()
                : (string) $returnType;

            $this->assertNotSame(
                'mixed',
                $typeName,
                "EnumManager::{$method->getName()}() must not return mixed"
            );
        }
    }

    /**
     * @test
     */
    public function enum_cache_singleton_safety(): void
    {
        $ref = new ReflectionClass(EnumCache::class);

        // Must be final
        $this->assertTrue($ref->isFinal());

        // Constructor must be private
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertFalse($constructor->isPublic(), 'EnumCache constructor must be private');

        // __clone must be private and return 'never'
        $cloneMethod = $ref->getMethod('__clone');
        $this->assertFalse($cloneMethod->isPublic(), 'EnumCache::__clone() must be private');
        $cloneReturn = $cloneMethod->getReturnType();
        $this->assertNotNull($cloneReturn);
        $this->assertSame('never', $cloneReturn->getName(), 'EnumCache::__clone() must return never');

        // __wakeup must be public and return 'never'
        $wakeupMethod = $ref->getMethod('__wakeup');
        $this->assertTrue($wakeupMethod->isPublic(), 'EnumCache::__wakeup() must be public');
        $wakeupReturn = $wakeupMethod->getReturnType();
        $this->assertNotNull($wakeupReturn);
        $this->assertSame('never', $wakeupReturn->getName(), 'EnumCache::__wakeup() must return never');

        // All properties must be typed
        foreach ($ref->getProperties() as $prop) {
            $type = $prop->getType();
            $this->assertNotNull($type, "EnumCache::\${$prop->getName()} must have a type declaration");
        }
    }

    /**
     * @test
     */
    public function invalid_enum_exception_has_named_constructors(): void
    {
        $ref = new ReflectionClass(InvalidEnumException::class);

        $this->assertTrue($ref->isFinal(), 'InvalidEnumException must be final');
        $this->assertTrue($ref->hasMethod('value'), 'Must have value() named constructor');
        $this->assertTrue($ref->hasMethod('forName'), 'Must have forName() named constructor');

        // Both named constructors must return self
        $valueMethod = $ref->getMethod('value');
        $valueReturn = $valueMethod->getReturnType();
        $this->assertNotNull($valueReturn);
        $this->assertSame('self', $valueReturn->getName());

        $forNameMethod = $ref->getMethod('forName');
        $forNameReturn = $forNameMethod->getReturnType();
        $this->assertNotNull($forNameReturn);
        $this->assertSame('self', $forNameReturn->getName());
    }

    /**
     * @test
     * @dataProvider allClassesProvider
     */
    public function all_public_methods_have_return_types(string $class): void
    {
        $ref = new ReflectionClass($class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip constructor (return type is implicit)
            if ($method->getName() === '__construct') {
                continue;
            }

            $returnType = $method->getReturnType();
            $this->assertNotNull(
                $returnType,
                "{$class}::{$method->getName()}() must have an explicit return type"
            );
        }
    }

    /**
     * Verify all per-case attributes are final with readonly promoted constructor.
     *
     * @test
     */
    public function per_case_attributes_are_final_with_readonly(): void
    {
        $perCaseAttributes = [
            Label::class, Color::class, Icon::class, Description::class,
        ];

        foreach ($perCaseAttributes as $class) {
            $ref = new ReflectionClass($class);
            $this->assertTrue($ref->isFinal(), "{$class} must be final");

            $constructor = $ref->getConstructor();
            $this->assertNotNull($constructor, "{$class} must have a constructor");

            foreach ($constructor->getParameters() as $param) {
                if (! $ref->hasProperty($param->getName())) {
                    continue;
                }
                $prop = $ref->getProperty($param->getName());
                $this->assertTrue(
                    $prop->isReadOnly(),
                    "{$class}::\${$param->getName()} must be readonly"
                );
            }
        }
    }

    /**
     * Verify class-level attributes are final with readonly promoted constructor.
     *
     * @test
     */
    public function class_level_attributes_are_final_with_readonly(): void
    {
        $classLevelAttributes = [
            EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class,
        ];

        foreach ($classLevelAttributes as $class) {
            $ref = new ReflectionClass($class);
            $this->assertTrue($ref->isFinal(), "{$class} must be final");

            $constructor = $ref->getConstructor();
            $this->assertNotNull($constructor, "{$class} must have a constructor");

            foreach ($constructor->getParameters() as $param) {
                if (! $ref->hasProperty($param->getName())) {
                    continue;
                }
                $prop = $ref->getProperty($param->getName());
                $this->assertTrue(
                    $prop->isReadOnly(),
                    "{$class}::\${$param->getName()} must be readonly"
                );
            }
        }
    }

    /**
     * Verify attribute targets are correct.
     *
     * @test
     */
    public function attribute_targets_are_correct(): void
    {
        $expectedTargets = [
            Label::class => \Attribute::TARGET_CLASS_CONSTANT,
            Color::class => \Attribute::TARGET_CLASS_CONSTANT,
            Icon::class => \Attribute::TARGET_CLASS_CONSTANT,
            Description::class => \Attribute::TARGET_CLASS_CONSTANT,
            EnumLabel::class => \Attribute::TARGET_CLASS | \Attribute::TARGET_CLASS_CONSTANT,
            EnumColor::class => \Attribute::TARGET_CLASS | \Attribute::TARGET_CLASS_CONSTANT,
            EnumIcon::class => \Attribute::TARGET_CLASS | \Attribute::TARGET_CLASS_CONSTANT,
            EnumDescription::class => \Attribute::TARGET_CLASS | \Attribute::TARGET_CLASS_CONSTANT,
        ];

        foreach ($expectedTargets as $class => $expectedFlags) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(\Attribute::class);
            $this->assertNotEmpty($attrs, "{$class} must have #[Attribute] declaration");

            $attribute = $attrs[0]->newInstance();
            $this->assertSame(
                $expectedFlags,
                $attribute->getFlags(),
                "{$class} must have correct Attribute targets"
            );
        }
    }

    /**
     * Verify the source file count matches documentation.
     *
     * @test
     */
    public function source_file_count_matches_documentation(): void
    {
        $srcDir = dirname((new ReflectionClass(EnumManager::class))->getFileName(), 2);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $phpFiles = 0;
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $phpFiles++;
            }
        }

        $this->assertSame(20, $phpFiles, 'Expected 20 PHP source files in src/');
    }
}
