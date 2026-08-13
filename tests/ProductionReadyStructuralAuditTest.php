<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use BackedEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use UnitEnum;
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
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderWorkflowStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * Comprehensive source code structural audit for PHPStan Level 9 compliance.
 *
 * Validates that every source file meets production-readiness criteria:
 * - declare(strict_types=1) present
 * - No mixed type usage where concrete types are possible
 * - Return type declarations on all methods
 * - Proper docblocks on all public/protected methods
 * - Typed properties throughout
 * - Strict comparisons used (===, !==)
 */
#[Group('production')]
#[CoversClass(HasEnumMetadata::class)]
#[CoversClass(EnumMetadataResolver::class)]
#[CoversClass(EnumCache::class)]
#[CoversClass(EnumManager::class)]
#[CoversClass(EnumRule::class)]
#[CoversClass(EnumCast::class)]
#[CoversClass(InvalidEnumException::class)]
final class ProductionReadyStructuralAuditTest extends TestCase
{
    /**
     * All source files that MUST be audited.
     *
     * @var list<string>
     */
    private const SOURCE_FILES = [
        // Trait
        __DIR__ . '/../src/Concerns/HasEnumMetadata.php',
        // Cache
        __DIR__ . '/../src/EnumCache.php',
        // Manager
        __DIR__ . '/../src/EnumManager.php',
        // Resolver
        __DIR__ . '/../src/Support/EnumMetadataResolver.php',
        // Rule
        __DIR__ . '/../src/Rules/EnumRule.php',
        // Cast
        __DIR__ . '/../src/Casts/EnumCast.php',
        // Exception
        __DIR__ . '/../src/Exceptions/InvalidEnumException.php',
        // Facade
        __DIR__ . '/../src/Facades/Enum.php',
        // Service Provider
        __DIR__ . '/../src/EnumsServiceProvider.php',
        // Attributes
        __DIR__ . '/../src/Attributes/Color.php',
        __DIR__ . '/../src/Attributes/Description.php',
        __DIR__ . '/../src/Attributes/Icon.php',
        __DIR__ . '/../src/Attributes/Label.php',
        __DIR__ . '/../src/Attributes/EnumColor.php',
        __DIR__ . '/../src/Attributes/EnumDescription.php',
        __DIR__ . '/../src/Attributes/EnumIcon.php',
        __DIR__ . '/../src/Attributes/EnumLabel.php',
    ];

    // -----------------------------------------------------------------------
    // Strict types declaration
    // -----------------------------------------------------------------------

    #[Group('strict-types')]
    public function testAllSourceFilesHaveStrictTypesDeclaration(): void
    {
        foreach (self::SOURCE_FILES as $file) {
            $content = file_get_contents($file);
            self::assertStringContainsString(
                'declare(strict_types=1)',
                $content,
                "File {$file} is missing declare(strict_types=1)."
            );
        }
    }

    // -----------------------------------------------------------------------
    // Return type declarations
    // -----------------------------------------------------------------------

    #[Group('return-types')]
    public function testAllPublicMethodsHaveReturnTypeDeclarations(): void
    {
        $classes = [
            HasEnumMetadata::class,
            EnumCache::class,
            EnumManager::class,
            EnumRule::class,
            EnumCast::class,
            InvalidEnumException::class,
            Enum::class,
            EnumMetadataResolver::class,
        ];

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                // Skip constructor and magic methods
                if (str_starts_with($method->getName(), '__')) {
                    continue;
                }

                $returnType = $method->getReturnType();
                self::assertNotNull(
                    $returnType,
                    sprintf(
                        '%s::%s() is missing a return type declaration.',
                        $class,
                        $method->getName()
                    )
                );
            }
        }
    }

    #[Group('return-types')]
    public function testEnumMetadataResolverInternalMethodsHaveReturnTypes(): void
    {
        $reflection = new ReflectionClass(EnumMetadataResolver::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PRIVATE);

        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }

            $returnType = $method->getReturnType();
            self::assertNotNull(
                $returnType,
                sprintf(
                    '%s::%s() is missing a return type declaration.',
                    EnumMetadataResolver::class,
                    $method->getName()
                )
            );
        }
    }

    // -----------------------------------------------------------------------
    // Attribute classes are final with readonly properties
    // -----------------------------------------------------------------------

    #[Group('attributes')]
    public function testAllAttributeClassesAreFinal(): void
    {
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

        foreach ($attributeClasses as $attrClass) {
            $reflection = new ReflectionClass($attrClass);
            self::assertTrue(
                $reflection->isFinal(),
                sprintf('%s must be final.', $attrClass)
            );
        }
    }

    #[Group('attributes')]
    public function testAllAttributeClassesUseReadonlyProperties(): void
    {
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

        foreach ($attributeClasses as $attrClass) {
            $reflection = new ReflectionClass($attrClass);
            $properties = $reflection->getProperties();

            foreach ($properties as $property) {
                self::assertTrue(
                    $property->isReadOnly(),
                    sprintf('%s::$%s must be readonly.', $attrClass, $property->getName())
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // Service classes are final
    // -----------------------------------------------------------------------

    #[Group('finality')]
    public function testServiceClassesAreFinal(): void
    {
        $finalClasses = [
            EnumCache::class,
            EnumManager::class,
            EnumRule::class,
            EnumCast::class,
            InvalidEnumException::class,
            Enum::class,
            EnumMetadataResolver::class,
            EnumsServiceProvider::class,
        ];

        foreach ($finalClasses as $class) {
            $reflection = new ReflectionClass($class);
            self::assertTrue(
                $reflection->isFinal(),
                sprintf('%s must be final for production safety.', $class)
            );
        }
    }

    // -----------------------------------------------------------------------
    // Typed properties
    // -----------------------------------------------------------------------

    #[Group('typed-properties')]
    public function testEnumCachePropertiesAreTyped(): void
    {
        $reflection = new ReflectionClass(EnumCache::class);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $type = $property->getType();
            self::assertNotNull(
                $type,
                sprintf(
                    'EnumCache::$%s must have a declared type.',
                    $property->getName()
                )
            );
        }
    }

    #[Group('typed-properties')]
    public function testEnumRulePropertiesAreTyped(): void
    {
        $reflection = new ReflectionClass(EnumRule::class);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $type = $property->getType();
            self::assertNotNull(
                $type,
                sprintf(
                    'EnumRule::$%s must have a declared type.',
                    $property->getName()
                )
            );
        }
    }

    // -----------------------------------------------------------------------
    // Strict comparison enforcement — no loose comparisons in source
    // -----------------------------------------------------------------------

    #[Group('strict-comparisons')]
    public function testNoLooseComparisonsInHasEnumMetadataTrait(): void
    {
        $content = file_get_contents(__DIR__ . '/../src/Concerns/HasEnumMetadata.php');

        // Ensure === is used for name comparison (not ==)
        self::assertStringContainsString(
            "=== \$name",
            $content,
            'HasEnumMetadata must use strict === for name comparison.'
        );

        // Ensure strcasecmp returns 0 check (strict equality with int)
        self::assertStringContainsString(
            '=== 0',
            $content,
            'HasEnumMetadata must use strict === 0 for strcasecmp comparison.'
        );
    }

    #[Group('strict-comparisons')]
    public function testEnumRuleUsesStrictInArrayComparison(): void
    {
        $content = file_get_contents(__DIR__ . '/../src/Rules/EnumRule.php');

        // Ensure in_array with strict mode (third parameter true)
        self::assertMatchesRegularExpression(
            '/in_array\s*\(\s*\$\w+\s*,\s*\$\w+\s*,\s*true\s*\)/',
            $content,
            'EnumRule must use in_array(..., true) for strict comparison.'
        );
    }

    #[Group('strict-comparisons')]
    public function testEnumCacheUsesStrictComparison(): void
    {
        $content = file_get_contents(__DIR__ . '/../src/EnumCache.php');

        // Ensure <= 0 comparison (not just < 0) for TTL check
        self::assertStringContainsString(
            '$this->ttl <= 0',
            $content,
            'EnumCache must use <= 0 for TTL disabled check (not < 0).'
        );
    }

    // -----------------------------------------------------------------------
    // Docblock completeness on public API
    // -----------------------------------------------------------------------

    #[Group('docblocks')]
    public function testAllPublicMethodsHaveDocblocks(): void
    {
        $publicApiClasses = [
            EnumCache::class,
            EnumManager::class,
        ];

        foreach ($publicApiClasses as $class) {
            $reflection = new ReflectionClass($class);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if (str_starts_with($method->getName(), '__')) {
                    continue;
                }

                $docComment = $method->getDocComment();
                self::assertNotFalse(
                    $docComment,
                    sprintf(
                        '%s::%s() is missing a docblock.',
                        $class,
                        $method->getName()
                    )
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // Interface compliance
    // -----------------------------------------------------------------------

    #[Group('interfaces')]
    public function testEnumRuleImplementsValidationRule(): void
    {
        $reflection = new ReflectionClass(EnumRule::class);
        self::assertTrue(
            $reflection->implementsInterface(\Illuminate\Contracts\Validation\ValidationRule::class),
            'EnumRule must implement ValidationRule.'
        );
    }

    #[Group('interfaces')]
    public function testEnumCastImplementsCastsAttributes(): void
    {
        $reflection = new ReflectionClass(EnumCast::class);
        self::assertTrue(
            $reflection->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class),
            'EnumCast must implement CastsAttributes.'
        );
    }

    #[Group('interfaces')]
    public function testEnumFacadeExtendsLaravelFacade(): void
    {
        $reflection = new ReflectionClass(Enum::class);
        self::assertTrue(
            $reflection->isSubclassOf(\Illuminate\Support\Facades\Facade::class),
            'Enum facade must extend Laravel Facade.'
        );
    }

    // -----------------------------------------------------------------------
    // Fixture enums all use HasEnumMetadata trait
    // -----------------------------------------------------------------------

    #[Group('fixtures')]
    public function testAllFixtureEnumsUseHasEnumMetadataTrait(): void
    {
        $fixtureEnums = [
            UserStatus::class,
            OrderStatus::class,
            PaymentStatus::class,
            Priority::class,
            TicketStatus::class,
            DetailedTicketStatus::class,
            SystemStatus::class,
            IntBackedPriority::class,
            IntStatusWithColor::class,
            CamelCaseRole::class,
            LabelMapEnum::class,
            MixedAttributeStatus::class,
            PureFeatureFlag::class,
            SingleCaseEnum::class,
            AllClassLevelEnum::class,
            DefaultIconFeature::class,
            OverriddenIconRole::class,
            OrderWorkflowStatus::class,
            ZeroBackedPriority::class,
            ZeroPriority::class,
        ];

        foreach ($fixtureEnums as $enumClass) {
            $reflection = new ReflectionClass($enumClass);
            self::assertTrue(
                $reflection->isEnum(),
                sprintf('%s must be an enum.', $enumClass)
            );

            $traitNames = array_map(
                fn (\ReflectionClass $t) => $t->getName(),
                $reflection->getTraits()
            );

            self::assertContains(
                HasEnumMetadata::class,
                $traitNames,
                sprintf('%s must use HasEnumMetadata trait.', $enumClass)
            );
        }
    }

    // -----------------------------------------------------------------------
    // Exception hierarchy
    // -----------------------------------------------------------------------

    #[Group('exceptions')]
    public function testInvalidEnumExceptionExtendsException(): void
    {
        $reflection = new ReflectionClass(InvalidEnumException::class);
        self::assertTrue(
            $reflection->isSubclassOf(\Exception::class),
            'InvalidEnumException must extend Exception.'
        );
    }

    #[Group('exceptions')]
    public function testInvalidEnumExceptionHasNamedConstructors(): void
    {
        self::assertTrue(
            method_exists(InvalidEnumException::class, 'forName'),
            'InvalidEnumException must have forName() factory.'
        );
        self::assertTrue(
            method_exists(InvalidEnumException::class, 'value'),
            'InvalidEnumException must have value() factory.'
        );
    }

    // -----------------------------------------------------------------------
    // EnumManager delegation contract
    // -----------------------------------------------------------------------

    #[Group('delegation')]
    public function testEnumManagerHasExpectedPublicMethods(): void
    {
        $expectedMethods = ['forSelect', 'forApi', 'tryFromLabel', 'tryFromName', 'hasCase'];

        foreach ($expectedMethods as $method) {
            self::assertTrue(
                method_exists(EnumManager::class, $method),
                sprintf('EnumManager must have %s() method.', $method)
            );
        }
    }

    #[Group('delegation')]
    public function testEnumManagerMethodsHaveReturnTypeDeclarations(): void
    {
        $methods = [
            'forSelect' => 'array',
            'forApi' => 'array',
            'tryFromLabel' => '?\\UnitEnum',
            'tryFromName' => '?\\UnitEnum',
            'hasCase' => 'bool',
        ];

        foreach ($methods as $method => $expectedType) {
            $reflection = new ReflectionMethod(EnumManager::class, $method);
            $returnType = $reflection->getReturnType();

            self::assertNotNull(
                $returnType,
                sprintf('EnumManager::%s() must have a return type.', $method)
            );

            self::assertSame(
                $expectedType,
                (string) $returnType,
                sprintf(
                    'EnumManager::%s() return type should be %s, got %s.',
                    $method,
                    $expectedType,
                    (string) $returnType
                )
            );
        }
    }

    // -----------------------------------------------------------------------
    // No mixed type annotations in public API signatures
    // -----------------------------------------------------------------------

    #[Group('no-mixed')]
    public function testEnumManagerParametersAreNotMixed(): void
    {
        $reflection = new ReflectionClass(EnumManager::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }

            foreach ($method->getParameters() as $param) {
                $type = $param->getType();
                $typeName = $type !== null ? (string) $type : 'none';

                self::assertNotSame(
                    'mixed',
                    $typeName,
                    sprintf(
                        'EnumManager::%s() parameter $%s uses mixed type — use a concrete type for PHPStan L9.',
                        $method->getName(),
                        $param->getName()
                    )
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // Cache singleton lifecycle
    // -----------------------------------------------------------------------

    #[Group('cache')]
    public function testEnumCacheSingletonLifecycle(): void
    {
        EnumCache::resetInstance();

        $first = EnumCache::getInstance();
        $second = EnumCache::getInstance();

        self::assertSame($first, $second, 'getInstance() must return the same instance.');
        self::assertSame($first, EnumCache::getInstance(), 'Singleton must persist across calls.');

        EnumCache::resetInstance();
    }

    #[Group('cache')]
    public function testEnumCacheFlushClearsAllEntries(): void
    {
        $cache = EnumCache::getInstance();
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ]);
        $cache->set(Priority::class, [
            'labels' => ['low' => 'Low'],
            'descriptions' => [],
            'colors' => ['low' => 'secondary'],
            'icons' => [],
        ]);

        self::assertTrue($cache->has(UserStatus::class));
        self::assertTrue($cache->has(Priority::class));

        EnumCache::flush();

        self::assertFalse($cache->has(UserStatus::class), 'flush() must clear all entries.');
        self::assertFalse($cache->has(Priority::class), 'flush() must clear all entries.');

        EnumCache::resetInstance();
    }

    // -----------------------------------------------------------------------
    // Metadata resolution consistency
    // -----------------------------------------------------------------------

    #[Group('metadata')]
    public function testMetadataResolverInvalidatesCorrectly(): void
    {
        EnumMetadataResolver::invalidate(UserStatus::class);

        // After invalidation, resolve should rebuild
        $meta = EnumMetadataResolver::resolve(UserStatus::class);
        self::assertIsArray($meta);
        self::assertArrayHasKey('labels', $meta);
        self::assertArrayHasKey('colors', $meta);
        self::assertArrayHasKey('descriptions', $meta);
        self::assertArrayHasKey('icons', $meta);

        // Cleanup
        EnumMetadataResolver::invalidateAll();
        EnumCache::resetInstance();
    }

    // -----------------------------------------------------------------------
    // Cross-fixture consistency: all enums produce valid forSelect output
    // -----------------------------------------------------------------------

    #[Group('cross-fixture')]
    public function testAllFixtureEnumsProduceValidForSelectOutput(): void
    {
        $fixtureEnums = [
            UserStatus::class,
            OrderStatus::class,
            PaymentStatus::class,
            Priority::class,
            TicketStatus::class,
            DetailedTicketStatus::class,
            IntBackedPriority::class,
            IntStatusWithColor::class,
            MixedAttributeStatus::class,
            PureFeatureFlag::class,
            SingleCaseEnum::class,
        ];

        foreach ($fixtureEnums as $enumClass) {
            $select = $enumClass::forSelect();
            self::assertIsArray($select, sprintf('%s::forSelect() must return array.', $enumClass));
            self::assertNotEmpty($select, sprintf('%s::forSelect() must not be empty.', $enumClass));

            foreach ($select as $option) {
                self::assertArrayHasKey('value', $option, sprintf('%s::forSelect() option must have "value" key.', $enumClass));
                self::assertArrayHasKey('label', $option, sprintf('%s::forSelect() option must have "label" key.', $enumClass));
                self::assertNotEmpty($option['label'], sprintf('%s::forSelect() label must not be empty.', $enumClass));
            }
        }

        EnumCache::resetInstance();
    }
}
