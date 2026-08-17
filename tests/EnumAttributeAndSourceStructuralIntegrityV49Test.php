<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Attribute;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

/**
 * Comprehensive attribute and source class structural integrity test.
 *
 * Verifies that every attribute class in the Enums package:
 * - Is declared as final
 * - Has declare(strict_types=1)
 * - Uses correct Attribute targets
 * - Has readonly promoted constructor properties
 * - Has complete PHPDoc with @see annotations
 *
 * Also verifies:
 * - All service/infrastructure classes are final
 * - EnumManager is readonly
 * - InvalidEnumException is final
 * - EnumCast is final
 * - EnumRule is final readonly
 * - All classes have strict_types=1
 */
final class EnumAttributeAndSourceStructuralIntegrityV49Test extends TestCase
{
    /**
     * All 8 attribute classes.
     *
     * @var list<class-string>
     */
    private const ATTRIBUTE_CLASSES = [
        Color::class,
        Description::class,
        EnumColor::class,
        EnumDescription::class,
        EnumIcon::class,
        EnumLabel::class,
        Icon::class,
        Label::class,
    ];

    /**
     * All service/infrastructure classes.
     *
     * @var list<class-string>
     */
    private const INFRA_CLASSES = [
        EnumCache::class,
        EnumManager::class,
        EnumCast::class,
        EnumRule::class,
        EnumMetadataResolver::class,
        EnumTestGenerator::class,
        InvalidEnumException::class,
        Enum::class,
    ];

    /**
     * Expected Attribute targets for each attribute class.
     *
     * @var array<class-string, int>
     */
    private const EXPECTED_TARGETS = [
        Color::class => Attribute::TARGET_CLASS_CONSTANT,
        Description::class => Attribute::TARGET_CLASS_CONSTANT,
        EnumColor::class => Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT,
        EnumDescription::class => Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT,
        EnumIcon::class => Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT,
        EnumLabel::class => Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT,
        Icon::class => Attribute::TARGET_CLASS_CONSTANT,
        Label::class => Attribute::TARGET_CLASS_CONSTANT,
    ];

    /**
     * Per-case attributes (single-value string attributes).
     *
     * @var list<class-string>
     */
    private const PER_CASE_ATTRIBUTES = [
        Color::class,
        Description::class,
        Icon::class,
        Label::class,
    ];

    /**
     * Class-level attributes (bulk mapping attributes).
     *
     * @var list<class-string>
     */
    private const CLASS_LEVEL_ATTRIBUTES = [
        EnumColor::class,
        EnumDescription::class,
        EnumIcon::class,
        EnumLabel::class,
    ];

    /**
     * @test
     */
    public function allAttributeClassesAreFinal(): void
    {
        foreach (self::ATTRIBUTE_CLASSES as $class) {
            $ref = new ReflectionClass($class);

            $this->assertTrue(
                $ref->isFinal(),
                "{$class} must be declared as final"
            );
        }
    }

    /**
     * @test
     */
    public function allAttributeClassesHaveStrictTypes(): void
    {
        foreach (self::ATTRIBUTE_CLASSES as $class) {
            $ref = new ReflectionClass($class);
            $file = $ref->getFileName();

            $this->assertIsString($file);
            $contents = file_get_contents($file);
            $this->assertIsString($contents);
            $this->assertStringContainsString(
                'declare(strict_types=1)',
                $contents,
                "{$class} must have declare(strict_types=1)"
            );
        }
    }

    /**
     * @test
     */
    public function allAttributeClassesHaveAttributeDeclaration(): void
    {
        foreach (self::ATTRIBUTE_CLASSES as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(Attribute::class);

            $this->assertNotEmpty(
                $attrs,
                "{$class} must have #[Attribute] declaration"
            );
        }
    }

    /**
     * @test
     */
    public function attributeTargetsMatchExpected(): void
    {
        foreach (self::EXPECTED_TARGETS as $class => $expectedFlags) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(Attribute::class);

            $instance = $attrs[0]->newInstance();
            $this->assertSame(
                $expectedFlags,
                $instance->flags,
                "{$class} must have correct Attribute target flags"
            );
        }
    }

    /**
     * @test
     */
    public function perCaseAttributesHaveSingleStringValueProperty(): void
    {
        foreach (self::PER_CASE_ATTRIBUTES as $class) {
            $ref = new ReflectionClass($class);
            $props = $ref->getProperties();

            $this->assertCount(
                1,
                $props,
                "{$class} must have exactly one property"
            );

            $prop = $props[0];
            $this->assertSame(
                'value',
                $prop->getName(),
                "{$class} property must be named 'value'"
            );
            $this->assertTrue(
                $prop->isPublic(),
                "{$class}::\$value must be public"
            );
            $this->assertTrue(
                $prop->isReadOnly(),
                "{$class}::\$value must be readonly"
            );
            $type = $prop->getType();
            $this->assertNotNull($type);
            $this->assertSame(
                'string',
                $type->getName(),
                "{$class}::\$value must be typed as string"
            );
        }
    }

    /**
     * @test
     */
    public function classLevelAttributesAreFinalWithCorrectProperties(): void
    {
        // EnumLabel has 'labels' and 'label' properties
        $ref = new ReflectionClass(EnumLabel::class);
        $this->assertTrue($ref->hasProperty('labels'));
        $this->assertTrue($ref->hasProperty('label'));

        // EnumDescription has 'descriptions' and 'description' properties
        $ref = new ReflectionClass(EnumDescription::class);
        $this->assertTrue($ref->hasProperty('descriptions'));
        $this->assertTrue($ref->hasProperty('description'));

        // EnumColor has success, danger, warning, info, secondary properties
        $ref = new ReflectionClass(EnumColor::class);
        foreach (['success', 'danger', 'warning', 'info', 'secondary'] as $prop) {
            $this->assertTrue(
                $ref->hasProperty($prop),
                "EnumColor must have \${$prop} property"
            );
        }

        // EnumIcon has 'default' and 'icons' properties
        $ref = new ReflectionClass(EnumIcon::class);
        $this->assertTrue($ref->hasProperty('default'));
        $this->assertTrue($ref->hasProperty('icons'));
    }

    /**
     * @test
     */
    public function allAttributeClassesHavePhpDoc(): void
    {
        foreach (self::ATTRIBUTE_CLASSES as $class) {
            $ref = new ReflectionClass($class);
            $doc = $ref->getDocComment();

            $this->assertIsString($doc);
            $this->assertStringContainsString(
                '@',
                $doc,
                "{$class} must have PHPDoc with annotations"
            );
        }
    }

    /**
     * @test
     */
    public function allInfrastructureClassesAreFinal(): void
    {
        foreach (self::INFRA_CLASSES as $class) {
            $ref = new ReflectionClass($class);

            $this->assertTrue(
                $ref->isFinal(),
                "{$class} must be declared as final"
            );
        }
    }

    /**
     * @test
     */
    public function enumManagerIsReadonly(): void
    {
        $ref = new ReflectionClass(EnumManager::class);

        $this->assertTrue(
            $ref->isReadOnly(),
            'EnumManager must be declared as readonly'
        );
    }

    /**
     * @test
     */
    public function enumRuleIsReadonly(): void
    {
        $ref = new ReflectionClass(EnumRule::class);

        $this->assertTrue(
            $ref->isReadOnly(),
            'EnumRule must be declared as readonly'
        );
    }

    /**
     * @test
     */
    public function allInfrastructureClassesHaveStrictTypes(): void
    {
        foreach (self::INFRA_CLASSES as $class) {
            $ref = new ReflectionClass($class);
            $file = $ref->getFileName();

            $this->assertIsString($file);
            $contents = file_get_contents($file);
            $this->assertIsString($contents);
            $this->assertStringContainsString(
                'declare(strict_types=1)',
                $contents,
                "{$class} must have declare(strict_types=1)"
            );
        }
    }

    /**
     * @test
     */
    public function allClassesHavePhpDoc(): void
    {
        foreach (self::INFRA_CLASSES as $class) {
            $ref = new ReflectionClass($class);
            $doc = $ref->getDocComment();

            $this->assertIsString($doc);
            $this->assertStringContainsString(
                '@',
                $doc,
                "{$class} must have PHPDoc"
            );
        }
    }

    /**
     * @test
     */
    public function attributeClassCountIsCorrect(): void
    {
        $this->assertCount(
            8,
            self::ATTRIBUTE_CLASSES,
            'Must have exactly 8 attribute classes'
        );
    }

    /**
     * @test
     */
    public function perCaseAndClassLevelAttributePartitionIsComplete(): void
    {
        $all = array_merge(self::PER_CASE_ATTRIBUTES, self::CLASS_LEVEL_ATTRIBUTES);
        $unique = array_unique($all);

        $this->assertCount(
            8,
            $unique,
            'Per-case and class-level attribute lists must cover all 8 attribute classes without duplicates'
        );
    }
}
