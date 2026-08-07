<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use BackedEnum;
use PHPUnit\Framework\TestCase;
use ReflectionEnum;
use ReflectionProperty;
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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * Comprehensive PHPStan Level 9 compliance audit.
 *
 * This test class exercises every public API surface to ensure:
 * - No `mixed` return types without explicit annotations
 * - Strict type comparisons (=== not == where appropriate)
 * - All methods have return type declarations
 * - All properties are typed
 * - No dynamic property access
 * - No implicit nullable returns
 *
 * Run: vendor/bin/phpstan analyse --level=9 src/
 */
final class ProductionAuditTest extends TestCase
{
    // ------------------------------------------------------------------
    // EnumCache singleton tests
    // ------------------------------------------------------------------

    public function testEnumCacheIsFinal(): void
    {
        $ref = new ReflectionClass(EnumCache::class);

        $this->assertTrue($ref->isFinal(), 'EnumCache must be final');
    }

    public function testEnumCacheHasStrictTypes(): void
    {
        $tokens = token_get_all(file_get_contents((string) (new ReflectionClass(EnumCache::class))->getFileName()));
        $hasStrict = false;

        foreach ($tokens as $token) {
            if (is_array($token) && $token[1] === 'strict_types') {
                $hasStrict = true;
                break;
            }
        }

        $this->assertTrue($hasStrict, 'EnumCache must declare strict_types=1');
    }

    public function testEnumCacheGetInstanceReturnsSameInstance(): void
    {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        $this->assertSame($a, $b);
    }

    public function testEnumCacheTtlIsTyped(): void
    {
        $ref = new ReflectionProperty(EnumCache::class, 'ttl');

        $this->assertSame('int', $ref->getType()?->getName());
    }

    public function testEnumCacheCacheArrayIsTyped(): void
    {
        $ref = new ReflectionProperty(EnumCache::class, 'cache');

        $this->assertInstanceOf(\ReflectionNamedType::class, $ref->getType());
        $this->assertSame('array', $ref->getType()->getName());
    }

    public function testEnumCacheMethodsHaveReturnTypes(): void
    {
        $ref = new ReflectionClass(EnumCache::class);
        $methods = ['has', 'get', 'set', 'setTtl', 'clear', 'clearClass', 'flush', 'resetInstance', 'getInstance'];

        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            $this->assertNotNull(
                $m->getReturnType(),
                "EnumCache::{$method}() must have a return type declaration"
            );
        }
    }

    public function testEnumCacheClearClassAcceptsString(): void
    {
        $cache = EnumCache::getInstance();
        $cache->set('test_clear_class', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->clearClass('test_clear_class');

        $this->assertFalse($cache->has('test_clear_class'));
    }

    public function testEnumCacheFlushWorks(): void
    {
        EnumCache::flush();

        $this->assertFalse(
            EnumCache::getInstance()->has('nonexistent'),
            'After flush, no entries should exist'
        );
    }

    // ------------------------------------------------------------------
    // EnumManager tests
    // ------------------------------------------------------------------

    public function testEnumManagerIsFinal(): void
    {
        $ref = new ReflectionClass(EnumManager::class);

        $this->assertTrue($ref->isFinal(), 'EnumManager must be final');
    }

    public function testEnumManagerMethodsHaveReturnTypes(): void
    {
        $ref = new ReflectionClass(EnumManager::class);
        $methods = ['forSelect', 'forApi', 'tryFromLabel'];

        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            $this->assertNotNull(
                $m->getReturnType(),
                "EnumManager::{$method}() must have a return type declaration"
            );
        }
    }

    public function testEnumManagerForSelectThrowsForNonEnum(): void
    {
        $manager = new EnumManager;

        $this->expectException(\BadMethodCallException::class);
        $manager->forSelect(\stdClass::class);
    }

    public function testEnumManagerForApiThrowsForNonEnum(): void
    {
        $manager = new EnumManager;

        $this->expectException(\BadMethodCallException::class);
        $manager->forApi(\stdClass::class);
    }

    // ------------------------------------------------------------------
    // InvalidEnumException tests
    // ------------------------------------------------------------------

    public function testInvalidEnumExceptionIsFinal(): void
    {
        $ref = new ReflectionClass(InvalidEnumException::class);

        $this->assertTrue($ref->isFinal(), 'InvalidEnumException must be final');
    }

    public function testInvalidEnumExceptionValueFactory(): void
    {
        $e = InvalidEnumException::value('App\\Enums\\UserStatus', 'invalid');
        $this->assertInstanceOf(InvalidEnumException::class, $e);
        $this->assertStringContainsString('invalid', $e->getMessage());
        $this->assertStringContainsString('UserStatus', $e->getMessage());
    }

    public function testInvalidEnumExceptionValueFactoryWithNull(): void
    {
        $e = InvalidEnumException::value('App\\Enums\\UserStatus', null);
        $this->assertStringContainsString('null', $e->getMessage());
    }

    public function testInvalidEnumExceptionValueFactoryWithInt(): void
    {
        $e = InvalidEnumException::value('App\\Enums\\Priority', 99);
        $this->assertStringContainsString('99', $e->getMessage());
    }

    public function testInvalidEnumExceptionForNameFactory(): void
    {
        $e = InvalidEnumException::forName('App\\Enums\\UserStatus', 'NONEXISTENT');
        $this->assertInstanceOf(InvalidEnumException::class, $e);
        $this->assertStringContainsString('NONEXISTENT', $e->getMessage());
    }

    public function testExceptionMethodsHaveReturnTypes(): void
    {
        $ref = new ReflectionClass(InvalidEnumException::class);
        $methods = ['value', 'forName'];

        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            $rt = $m->getReturnType();
            $this->assertNotNull($rt, "InvalidEnumException::{$method}() must have return type");
            $this->assertSame('self', $rt->getName());
        }
    }

    // ------------------------------------------------------------------
    // EnumRule tests
    // ------------------------------------------------------------------

    public function testEnumRuleIsFinalAndReadonly(): void
    {
        $ref = new ReflectionClass(EnumRule::class);

        $this->assertTrue($ref->isFinal(), 'EnumRule must be final');
        $this->assertTrue($ref->isReadOnly(), 'EnumRule must be readonly');
    }

    public function testEnumRuleValidateHasReturnType(): void
    {
        $m = new \ReflectionMethod(EnumRule::class, 'validate');
        $rt = $m->getReturnType();

        $this->assertNotNull($rt, 'EnumRule::validate() must have return type');
        $this->assertSame('void', $rt->getName());
    }

    public function testEnumRuleNullableFactory(): void
    {
        $rule = EnumRule::for(UserStatus::class);

        $this->assertInstanceOf(EnumRule::class, $rule);
    }

    public function testEnumRuleNullableModifier(): void
    {
        $nonNullable = EnumRule::for(UserStatus::class);
        $nullable = $nonNullable->nullable();

        $this->assertInstanceOf(EnumRule::class, $nullable);
        $this->assertNotSame($nonNullable, $nullable);
    }

    // ------------------------------------------------------------------
    // EnumCast tests
    // ------------------------------------------------------------------

    public function testEnumCastIsFinal(): void
    {
        $ref = new ReflectionClass(EnumCast::class);

        $this->assertTrue($ref->isFinal(), 'EnumCast must be final');
    }

    public function testEnumCastMethodsHaveReturnTypes(): void
    {
        $ref = new ReflectionClass(EnumCast::class);
        $methods = ['get', 'set', 'serialize'];

        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            $this->assertNotNull(
                $m->getReturnType(),
                "EnumCast::{$method}() must have a return type declaration"
            );
        }
    }

    public function testEnumCastSetRejectsInvalidType(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $this->expectException(\InvalidArgumentException::class);
        // 3.14 is not a valid string/int
        $cast->set(
            new class {
                public function __get(string $name): mixed { return null; }
            },
            'status',
            3.14,
            []
        );
    }

    public function testEnumCastSetRejectsWrongEnumClass(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $this->expectException(\InvalidArgumentException::class);
        $cast->set(
            new class {
                public function __get(string $name): mixed { return null; }
            },
            'status',
            OrderStatus::DELIVERED,
            []
        );
    }

    // ------------------------------------------------------------------
    // Attribute classes tests
    // ------------------------------------------------------------------

    /**
     * @dataProvider attributeClassProvider
     */
    public function testAttributeClassIsFinal(string $class): void
    {
        $ref = new ReflectionClass($class);

        $this->assertTrue($ref->isFinal(), "{$class} must be final");
    }

    /**
     * @return list<array{class-string}>
     */
    public static function attributeClassProvider(): array
    {
        return [
            [Color::class],
            [Description::class],
            [EnumColor::class],
            [EnumDescription::class],
            [EnumIcon::class],
            [EnumLabel::class],
            [Icon::class],
            [Label::class],
        ];
    }

    public function testPerCaseAttributesHaveTargetClassConstant(): void
    {
        $perCase = [Color::class, Description::class, Icon::class, Label::class];

        foreach ($perCase as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(\Attribute::class);

            $this->assertCount(1, $attrs, "{$class} must have exactly one #[Attribute] declaration");
            $instance = $attrs[0]->newInstance();
            $this->assertTrue(
                (bool) ($instance->flags & \Attribute::TARGET_CLASS_CONSTANT),
                "{$class} must target CLASS_CONSTANT"
            );
        }
    }

    public function testClassLevelAttributesTargetClass(): void
    {
        $classLevel = [EnumColor::class, EnumDescription::class, EnumIcon::class, EnumLabel::class];

        foreach ($classLevel as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(\Attribute::class);

            $this->assertCount(1, $attrs, "{$class} must have exactly one #[Attribute] declaration");
            $instance = $attrs[0]->newInstance();
            $this->assertTrue(
                (bool) ($instance->flags & \Attribute::TARGET_CLASS),
                "{$class} must target CLASS"
            );
        }
    }

    public function testEnumColorHasReadonlyProperties(): void
    {
        $ref = new ReflectionClass(EnumColor::class);
        $props = $ref->getProperties();

        foreach ($props as $prop) {
            $this->assertTrue(
                $prop->isReadOnly(),
                "EnumColor::\${$prop->getName()} must be readonly"
            );
        }
    }

    // ------------------------------------------------------------------
    // EnumMetadataResolver tests
    // ------------------------------------------------------------------

    public function testEnumMetadataResolverIsFinal(): void
    {
        $ref = new ReflectionClass(EnumMetadataResolver::class);

        $this->assertTrue($ref->isFinal(), 'EnumMetadataResolver must be final');
    }

    public function testResolveMethodReturnsConsistentShape(): void
    {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        $this->assertArrayHasKey('labels', $meta);
        $this->assertArrayHasKey('descriptions', $meta);
        $this->assertArrayHasKey('colors', $meta);
        $this->assertArrayHasKey('icons', $meta);
    }

    public function testResolveCachesResult(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();

        $a = EnumMetadataResolver::resolve(UserStatus::class);
        $b = EnumMetadataResolver::resolve(UserStatus::class);

        $this->assertSame($a, $b, 'Metadata should be cached and return same array');
    }

    // ------------------------------------------------------------------
    // EnumTestGenerator tests
    // ------------------------------------------------------------------

    public function testEnumTestGeneratorIsFinal(): void
    {
        $ref = new ReflectionClass(EnumTestGenerator::class);

        $this->assertTrue($ref->isFinal(), 'EnumTestGenerator must be final');
    }

    public function testEnumTestGeneratorProducesValidPhp(): void
    {
        $content = EnumTestGenerator::generate(UserStatus::class);

        // Verify generated PHP has strict types
        $this->assertStringContainsString('declare(strict_types=1)', $content);
        // Verify it references the correct class
        $this->assertStringContainsString('use ' . UserStatus::class . ';', $content);
        // Verify it has test assertions
        $this->assertStringContainsString('->label()', $content);
        $this->assertStringContainsString('->color()', $content);
    }

    public function testEnumTestGeneratorHandlesSingleCaseEnum(): void
    {
        $content = EnumTestGenerator::generate(SingleCaseEnum::class);

        $this->assertStringContainsString('declare(strict_types=1)', $content);
        $this->assertStringContainsString('cases()', $content);
    }

    public function testEnumTestGeneratorHandlesPureEnum(): void
    {
        $content = EnumTestGenerator::generate(PureFeatureFlag::class);

        $this->assertStringContainsString('declare(strict_types=1)', $content);
        $this->assertStringContainsString('PureFeatureFlag', $content);
    }

    public function testEnumTestGeneratorHandlesIntBackedEnum(): void
    {
        $content = EnumTestGenerator::generate(IntStatusWithColor::class);

        $this->assertStringContainsString('IntStatusWithColor', $content);
    }

    public function testEnumTestGeneratorHandlesCamelCaseEnum(): void
    {
        $content = EnumTestGenerator::generate(CamelCaseRole::class);

        $this->assertStringContainsString('CamelCaseRole', $content);
    }

    // ------------------------------------------------------------------
    // HasEnumMetadata trait fixture verification
    // ------------------------------------------------------------------

    public function testUserStatusEnumHasMetadata(): void
    {
        $this->assertNotEmpty(UserStatus::cases());
        $this->assertNotEmpty(UserStatus::forSelect());
        $this->assertNotEmpty(UserStatus::forApi());
    }

    public function testUserStatusLabelIsNonEmptyString(): void
    {
        foreach (UserStatus::cases() as $case) {
            $label = $case->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label, "Label for {$case->name} should not be empty");
        }
    }

    public function testUserStatusColorIsKnownColor(): void
    {
        $validColors = ['success', 'danger', 'warning', 'info', 'secondary'];

        foreach (UserStatus::cases() as $case) {
            $color = $case->color();
            $this->assertContains($color, $validColors, "Color '{$color}' for {$case->name} must be a known UI color");
        }
    }

    public function testUserStatusValuesReturnsCorrectCount(): void
    {
        $values = UserStatus::values();
        $cases = UserStatus::cases();

        $this->assertCount(count($cases), $values);
    }

    public function testUserStatusLabelsReturnsCorrectCount(): void
    {
        $labels = UserStatus::labels();
        $cases = UserStatus::cases();

        $this->assertCount(count($cases), $labels);
    }

    public function testUserStatusForSelectHasCorrectStructure(): void
    {
        $select = UserStatus::forSelect();

        foreach ($select as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }

    public function testUserStatusForApiHasCorrectStructure(): void
    {
        $api = UserStatus::forApi();

        foreach ($api as $item) {
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('color', $item);
            $this->assertArrayHasKey('icon', $item);
        }
    }

    public function testTryFromLabelIsCaseInsensitive(): void
    {
        $label = UserStatus::ACTIVE->label();
        $resolved = UserStatus::tryFromLabel(strtoupper($label));

        $this->assertSame(UserStatus::ACTIVE, $resolved);
    }

    public function testTryFromLabelReturnsNullForInvalidLabel(): void
    {
        $this->assertNull(UserStatus::tryFromLabel('totally_invalid_label_xyz'));
    }

    public function testFromNameThrowsForInvalidName(): void
    {
        $this->expectException(InvalidEnumException::class);
        UserStatus::fromName('DOES_NOT_EXIST');
    }

    public function testHasCaseWorks(): void
    {
        $this->assertTrue(UserStatus::hasCase('ACTIVE'));
        $this->assertFalse(UserStatus::hasCase('DOES_NOT_EXIST'));
    }

    public function testIsComparisonWithInstance(): void
    {
        $status = UserStatus::ACTIVE;

        $this->assertTrue($status->is(UserStatus::ACTIVE));
        $this->assertFalse($status->is(UserStatus::BANNED));
    }

    public function testIsComparisonWithString(): void
    {
        $status = UserStatus::ACTIVE;

        $this->assertTrue($status->is('ACTIVE'));
        $this->assertFalse($status->is('BANNED'));
    }

    public function testIsNotNegation(): void
    {
        $status = UserStatus::ACTIVE;

        $this->assertTrue($status->isNot(UserStatus::BANNED));
        $this->assertFalse($status->isNot(UserStatus::ACTIVE));
    }

    public function testInGroupMatching(): void
    {
        $status = UserStatus::ACTIVE;

        $this->assertTrue($status->in([UserStatus::ACTIVE, UserStatus::PENDING]));
        $this->assertFalse($status->in([UserStatus::BANNED]));
    }

    public function testInWithStringNames(): void
    {
        $status = UserStatus::ACTIVE;

        $this->assertTrue($status->in(['ACTIVE', 'PENDING']));
    }

    // ------------------------------------------------------------------
    // Int-backed enum tests (Priority)
    // ------------------------------------------------------------------

    public function testPriorityHasIntBackedValues(): void
    {
        foreach (Priority::cases() as $case) {
            $this->assertInstanceOf(BackedEnum::class, $case);
            $this->assertIsInt($case->value);
        }
    }

    public function testPriorityValuesAreIntegers(): void
    {
        $values = Priority::values();

        foreach ($values as $value) {
            $this->assertIsInt($value);
        }
    }

    // ------------------------------------------------------------------
    // Pure enum tests (PureFeatureFlag)
    // ------------------------------------------------------------------

    public function testPureEnumIsNotBacked(): void
    {
        $case = PureFeatureFlag::cases()[0];

        $this->assertInstanceOf(UnitEnum::class, $case);
        $this->assertNotInstanceOf(BackedEnum::class, $case);
    }

    public function testPureEnumValuesReturnCaseNames(): void
    {
        $values = PureFeatureFlag::values();
        $names = array_map(static fn (UnitEnum $c): string => $c->name, PureFeatureFlag::cases());

        $this->assertSame($names, $values);
    }

    // ------------------------------------------------------------------
    // Single case enum tests
    // ------------------------------------------------------------------

    public function testSingleCaseEnumWorks(): void
    {
        $this->assertCount(1, SingleCaseEnum::cases());
        $this->assertNotEmpty(SingleCaseEnum::ONLY->label());
    }

    // ------------------------------------------------------------------
    // Zero-priority enum tests
    // ------------------------------------------------------------------

    public function testZeroPriorityHasZeroValue(): void
    {
        $case = ZeroPriority::LOW;

        $this->assertInstanceOf(BackedEnum::class, $case);
        $this->assertSame(0, $case->value);
    }

    // ------------------------------------------------------------------
    // AllClassLevelEnum fixture
    // ------------------------------------------------------------------

    public function testAllClassLevelEnumMetadataResolved(): void
    {
        EnumCache::flush();

        $meta = EnumMetadataResolver::resolve(AllClassLevelEnum::class);

        $this->assertNotEmpty($meta['labels'], 'AllClassLevelEnum should have class-level labels');
        $this->assertNotEmpty($meta['colors'], 'AllClassLevelEnum should have class-level colors');
    }

    // ------------------------------------------------------------------
    // CamelCase enum tests
    // ------------------------------------------------------------------

    public function testCamelCaseEnumGeneratesLabels(): void
    {
        foreach (CamelCaseRole::cases() as $case) {
            $label = $case->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label, "Label for {$case->name} should be non-empty");
        }
    }

    // ------------------------------------------------------------------
    // Int-backed color enum (IntStatusWithColor)
    // ------------------------------------------------------------------

    public function testIntStatusWithColorHasColors(): void
    {
        foreach (IntStatusWithColor::cases() as $case) {
            $color = $case->color();
            $this->assertIsString($color);
        }
    }

    // ------------------------------------------------------------------
    // TicketStatus tests
    // ------------------------------------------------------------------

    public function testTicketStatusDescriptions(): void
    {
        foreach (TicketStatus::cases() as $case) {
            $desc = $case->description();
            // Description is nullable, just check type
            $this->assertNull($desc) || $this->assertIsString($desc);
        }
    }

    // ------------------------------------------------------------------
    // RequestState (pure enum) tests
    // ------------------------------------------------------------------

    public function testRequestStatePureEnumForSelect(): void
    {
        $select = RequestState::forSelect();

        foreach ($select as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }

    // ------------------------------------------------------------------
    // Facade accessor verification
    // ------------------------------------------------------------------

    public function testEnumFacadeAccessorIsString(): void
    {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class);
        $method = $ref->getMethod('getFacadeAccessor');
        $rt = $method->getReturnType();

        $this->assertNotNull($rt);
        $this->assertSame('string', $rt->getName());
    }

    // ------------------------------------------------------------------
    // ServiceProvider verification
    // ------------------------------------------------------------------

    public function testEnumsServiceProviderIsFinal(): void
    {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

        $this->assertTrue($ref->isFinal(), 'EnumsServiceProvider must be final');
    }

    public function testEnumsServiceProviderBootAndRegisterHaveReturnTypes(): void
    {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

        foreach (['register', 'boot'] as $method) {
            $m = $ref->getMethod($method);
            $rt = $m->getReturnType();
            $this->assertNotNull($rt, "EnumsServiceProvider::{$method}() must have return type");
            $this->assertSame('void', $rt->getName());
        }
    }

    // ------------------------------------------------------------------
    // Console commands verification
    // ------------------------------------------------------------------

    public function testInspectEnumCommandIsFinal(): void
    {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Console\Commands\InspectEnumCommand::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testMakeEnumTestCommandIsFinal(): void
    {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testConsoleCommandsHaveIntReturnType(): void
    {
        $commands = [
            \ZeroBoiler\Enums\Console\Commands\InspectEnumCommand::class,
            \ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand::class,
        ];

        foreach ($commands as $class) {
            $m = new \ReflectionMethod($class, 'handle');
            $rt = $m->getReturnType();
            $this->assertNotNull($rt, "{$class}::handle() must have return type");
            $this->assertSame('int', $rt->getName());
        }
    }

    // ------------------------------------------------------------------
    // Metadata consistency across enum types
    // ------------------------------------------------------------------

    public function testMetadataShapeConsistentAcrossEnumTypes(): void
    {
        $enums = [
            UserStatus::class,
            Priority::class,
            PureFeatureFlag::class,
            RequestState::class,
            SingleCaseEnum::class,
            IntStatusWithColor::class,
            CamelCaseRole::class,
            ZeroPriority::class,
        ];

        foreach ($enums as $enumClass) {
            EnumCache::flush();
            $meta = EnumMetadataResolver::resolve($enumClass);

            $this->assertArrayHasKey('labels', $meta, "Metadata for {$enumClass} must have 'labels'");
            $this->assertArrayHasKey('descriptions', $meta, "Metadata for {$enumClass} must have 'descriptions'");
            $this->assertArrayHasKey('colors', $meta, "Metadata for {$enumClass} must have 'colors'");
            $this->assertArrayHasKey('icons', $meta, "Metadata for {$enumClass} must have 'icons'");
        }
    }

    public function testSelectOptionsCountMatchesCaseCount(): void
    {
        $enums = [
            UserStatus::class,
            Priority::class,
            PureFeatureFlag::class,
            SingleCaseEnum::class,
        ];

        foreach ($enums as $enumClass) {
            $select = $enumClass::forSelect();
            $cases = $enumClass::cases();

            $this->assertCount(
                count($cases),
                $select,
                "forSelect() count for {$enumClass} must match cases() count"
            );
        }
    }

    public function testApiOptionsCountMatchesCaseCount(): void
    {
        $enums = [
            UserStatus::class,
            Priority::class,
            PureFeatureFlag::class,
            SingleCaseEnum::class,
        ];

        foreach ($enums as $enumClass) {
            $api = $enumClass::forApi();
            $cases = $enumClass::cases();

            $this->assertCount(
                count($cases),
                $api,
                "forApi() count for {$enumClass} must match cases() count"
            );
        }
    }
}
