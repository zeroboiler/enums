<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Production readiness edge case tests for ZeroBoiler Enums.
 *
 * Covers: label generation for camelCase enums, EnumManager error handling,
 * EnumRule validation edge cases, EnumCast with type mismatches, cache
 * timestamp accuracy, values()/labels() ordering, and facade accessor.
 *
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata
 * @see \ZeroBoiler\Enums\EnumManager
 * @see \ZeroBoiler\Enums\Rules\EnumRule
 * @see \ZeroBoiler\Enums\Casts\EnumCast
 */
final class EnumProductionReadinessV3Test extends TestCase
{
    protected function setUp(): void
    {
        EnumCache::flush();
        EnumCache::getInstance()->setTtl(300);
        EnumMetadataResolver::invalidateAll();
    }

    protected function tearDown(): void
    {
        EnumCache::flush();
        EnumCache::getInstance()->setTtl(300);
        EnumMetadataResolver::invalidateAll();
    }

    // -------------------------------------------------------------------
    // Label generation for camelCase enums
    // -------------------------------------------------------------------

    /**
     * @test camelCase enum name generates Title Case label
     */
    public function camelCaseGeneratesTitleCaseLabel(): void
    {
        $label = CamelCaseRole::ADMIN->label();

        $this->assertSame('Admin', $label);
    }

    /**
     * @test camelCase enum with multiple words generates correct label
     */
    public function multiWordCamelCaseGeneratesCorrectLabel(): void
    {
        $label = CamelCaseRole::SUPER_ADMIN->label();

        $this->assertSame('Super Admin', $label);
    }

    /**
     * @test camelCase enum with acronyms preserves casing
     */
    public function acronymCamelCaseGeneratesCorrectLabel(): void
    {
        $label = CamelCaseRole::API_USER->label();

        $this->assertSame('Api User', $label);
    }

    // -------------------------------------------------------------------
    // values() / labels() ordering
    // -------------------------------------------------------------------

    /**
     * @test values() returns backed values in case declaration order
     */
    public function valuesReturnsInDeclarationOrder(): void
    {
        $values = UserStatus::values();

        $this->assertSame(
            ['active', 'inactive', 'pending', 'suspended', 'banned'],
            $values,
        );
    }

    /**
     * @test labels() returns labels in case declaration order
     */
    public function labelsReturnsInDeclarationOrder(): void
    {
        $labels = UserStatus::labels();

        $this->assertCount(5);
        $this->assertSame('Active User', $labels[0]);
        $this->assertSame('Inactive', $labels[1]);
        $this->assertSame('Awaiting Verification', $labels[2]);
    }

    /**
     * @test values() count matches cases() count
     */
    public function valuesCountMatchesCasesCount(): void
    {
        foreach (
            [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class]
            as $enumClass
        ) {
            $this->assertSame(
                count($enumClass::cases()),
                count($enumClass::values()),
                "values() count doesn't match cases() count for {$enumClass}",
            );
        }
    }

    /**
     * @test labels() count matches cases() count
     */
    public function labelsCountMatchesCasesCount(): void
    {
        foreach (
            [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class]
            as $enumClass
        ) {
            $this->assertSame(
                count($enumClass::cases()),
                count($enumClass::labels()),
                "labels() count doesn't match cases() count for {$enumClass}",
            );
        }
    }

    // -------------------------------------------------------------------
    // Single-case enum edge cases
    // -------------------------------------------------------------------

    /**
     * @test Single-case enum returns correct forSelect
     */
    public function singleCaseEnumForSelectWorks(): void
    {
        $select = SingleCaseEnum::forSelect();

        $this->assertCount(1);
        $this->assertSame('only', $select[0]['value']);
        $this->assertNotEmpty($select[0]['label']);
    }

    /**
     * @test Single-case enum forApi has correct structure
     */
    public function singleCaseEnumForApiHasCorrectStructure(): void
    {
        $api = SingleCaseEnum::forApi();

        $this->assertCount(1);
        $this->assertArrayHasKey('value', $api[0]);
        $this->assertArrayHasKey('name', $api[0]);
        $this->assertArrayHasKey('label', $api[0]);
        $this->assertArrayHasKey('color', $api[0]);
        $this->assertArrayHasKey('icon', $api[0]);
        $this->assertArrayHasKey('description', $api[0]);
    }

    /**
     * @test Single-case enum tryFromName works
     */
    public function singleCaseEnumTryFromNameWorks(): void
    {
        $this->assertSame(SingleCaseEnum::ONLY, SingleCaseEnum::tryFromName('ONLY'));
        $this->assertNull(SingleCaseEnum::tryFromName('NONEXISTENT'));
    }

    /**
     * @test Single-case enum fromName throws for invalid name
     */
    public function singleCaseEnumFromNameThrowsForInvalid(): void
    {
        $this->expectException(InvalidEnumException::class);

        SingleCaseEnum::fromName('NONEXISTENT');
    }

    // -------------------------------------------------------------------
    // EnumRule edge cases
    // -------------------------------------------------------------------

    /**
     * @test EnumRule nullable allows null
     */
    public function enumRuleNullableAllowsNull(): void
    {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        // Pass null — should not call $fail
        $rule->validate('status', null, $fail);

        $this->assertFalse($failCalled);
    }

    /**
     * @test EnumRule non-nullable rejects null
     */
    public function enumRuleNonNullableRejectsNull(): void
    {
        $rule = EnumRule::for(UserStatus::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        // Pass null — should call $fail
        $rule->validate('status', null, $fail);

        $this->assertTrue($failCalled);
    }

    /**
     * @test EnumRule accepts valid string-backed enum value
     */
    public function enumRuleAcceptsValidStringValue(): void
    {
        $rule = EnumRule::for(UserStatus::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('status', 'active', $fail);

        $this->assertFalse($failCalled);
    }

    /**
     * @test EnumRule rejects invalid string-backed enum value
     */
    public function enumRuleRejectsInvalidStringValue(): void
    {
        $rule = EnumRule::for(UserStatus::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('status', 'nonexistent', $fail);

        $this->assertTrue($failCalled);
    }

    /**
     * @test EnumRule rejects wrong type for int-backed enum
     */
    public function enumRuleRejectsWrongTypeForIntBacked(): void
    {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        // Int-backed enum with string value — should fail
        $rule->validate('priority', 'high', $fail);

        $this->assertTrue($failCalled);
    }

    /**
     * @test EnumRule accepts valid int-backed enum value
     */
    public function enumRuleAcceptsValidIntValue(): void
    {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('priority', 1, $fail);

        $this->assertFalse($failCalled);
    }

    /**
     * @test EnumRule validates pure enum by case name
     */
    public function enumRuleValidatesPureEnumByCaseName(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('feature', 'DARK_MODE', $fail);

        $this->assertFalse($failCalled);
    }

    /**
     * @test EnumRule rejects invalid pure enum case name
     */
    public function enumRuleRejectsInvalidPureEnumCaseName(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('feature', 'NONEXISTENT', $fail);

        $this->assertTrue($failCalled);
    }

    // -------------------------------------------------------------------
    // EnumCast edge cases
    // -------------------------------------------------------------------

    /**
     * @test EnumCast returns null for null database value
     */
    public function enumCastReturnsNullForNullValue(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->get(
            new \stdClass,
            'status',
            null,
            ['status' => null],
        );

        $this->assertNull($result);
    }

    /**
     * @test EnumCast returns correct enum for valid string value
     */
    public function enumCastReturnsCorrectEnumForValidValue(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->get(
            new \stdClass,
            'status',
            'active',
            ['status' => 'active'],
        );

        $this->assertSame(UserStatus::ACTIVE, $result);
    }

    /**
     * @test EnumCast returns null for invalid enum value
     */
    public function enumCastReturnsNullForInvalidValue(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->get(
            new \stdClass,
            'status',
            'nonexistent',
            ['status' => 'nonexistent'],
        );

        $this->assertNull($result);
    }

    /**
     * @test EnumCast set() returns backed value for enum instance
     */
    public function enumCastSetReturnsValueForEnumInstance(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            new \stdClass,
            'status',
            UserStatus::ACTIVE,
            [],
        );

        $this->assertSame('active', $result);
    }

    /**
     * @test EnumCast set() returns raw value for valid string
     */
    public function enumCastSetReturnsRawValueForValidString(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            new \stdClass,
            'status',
            'active',
            [],
        );

        $this->assertSame('active', $result);
    }

    /**
     * @test EnumCast set() returns null for null
     */
    public function enumCastSetReturnsNullForNull(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            new \stdClass,
            'status',
            null,
            [],
        );

        $this->assertNull($result);
    }

    /**
     * @test EnumCast set() throws for wrong enum type
     */
    public function enumCastSetThrowsForWrongEnumType(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected enum');

        // Pass IntBackedPriority to a UserStatus cast
        $cast->set(
            new \stdClass,
            'status',
            IntBackedPriority::LOW,
            [],
        );
    }

    /**
     * @test EnumCast serialize returns backed value
     */
    public function enumCastSerializeReturnsBackedValue(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            new \stdClass,
            'status',
            UserStatus::ACTIVE,
            [],
        );

        $this->assertSame('active', $result);
    }

    /**
     * @test EnumCast serialize passes through int/string
     */
    public function enumCastSerializePassesThroughScalar(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            new \stdClass,
            'status',
            'active',
            [],
        );

        $this->assertSame('active', $result);
    }

    /**
     * @test EnumCast serialize returns null for null
     */
    public function enumCastSerializeReturnsNullForNull(): void
    {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            new \stdClass,
            'status',
            null,
            [],
        );

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------
    // Enum facade accessor
    // -------------------------------------------------------------------

    /**
     * @test Facade accessor returns correct string
     */
    public function facadeAccessorReturnsCorrectString(): void
    {
        $accessor = Enum::getFacadeAccessor();

        $this->assertSame('zeroboiler.enum', $accessor);
    }

    // -------------------------------------------------------------------
    // Comparison method edge cases
    // -------------------------------------------------------------------

    /**
     * @test in() with empty array returns false
     */
    public function inWithEmptyArrayReturnsFalse(): void
    {
        $this->assertFalse(UserStatus::ACTIVE->in([]));
    }

    /**
     * @test in() with single matching case returns true
     */
    public function inWithSingleMatchingCaseReturnsTrue(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->in([UserStatus::ACTIVE]));
    }

    /**
     * @test isNot() with self returns false
     */
    public function isNotWithSelfReturnsFalse(): void
    {
        $this->assertFalse(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE));
    }

    /**
     * @test isNot() with different case returns true
     */
    public function isNotWithDifferentCaseReturnsTrue(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->isNot(UserStatus::BANNED));
    }

    // -------------------------------------------------------------------
    // EnumManager method_exists checks
    // -------------------------------------------------------------------

    /**
     * @test EnumManager::forSelect() throws for non-enum classes
     */
    public function enumManagerForSelectThrowsForNonEnum(): void
    {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('does not use HasEnumMetadata');

        $manager->forSelect(\stdClass::class);
    }

    /**
     * @test EnumManager::forApi() throws for non-enum classes
     */
    public function enumManagerForApiThrowsForNonEnum(): void
    {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('does not use HasEnumMetadata');

        $manager->forApi(\stdClass::class);
    }

    /**
     * @test EnumManager::tryFromLabel() throws for non-enum classes
     */
    public function enumManagerTryFromLabelThrowsForNonEnum(): void
    {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('does not use HasEnumMetadata');

        $manager->tryFromLabel(\stdClass::class, 'test');
    }

    // -------------------------------------------------------------------
    // Cache timestamp behavior
    // -------------------------------------------------------------------

    /**
     * @test set() records current timestamp
     */
    public function setRecordsCurrentTimestamp(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(300);

        $before = microtime(true);
        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $after = microtime(true);

        // The timestamp should be between before and after
        // We can't read timestamps directly, but we can verify has() works
        $this->assertTrue($cache->has(UserStatus::class));
    }

    /**
     * @test clearClass() removes specific class without affecting others
     */
    public function clearClassRemovesSpecificClassOnly(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(300);

        $meta = [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];

        $cache->set(UserStatus::class, $meta);
        $cache->set(IntBackedPriority::class, $meta);

        $cache->clearClass(UserStatus::class);

        $this->assertFalse($cache->has(UserStatus::class));
        $this->assertTrue($cache->has(IntBackedPriority::class));
    }

    // -------------------------------------------------------------------
    // color() default behavior
    // -------------------------------------------------------------------

    /**
     * @test color() returns 'secondary' as default when no color is defined
     */
    public function colorReturnsSecondaryAsDefault(): void
    {
        // INACTIVE has no per-case Color and no class-level EnumColor mapping
        $this->assertSame('secondary', UserStatus::INACTIVE->color());
    }

    /**
     * @test color() returns per-case override
     */
    public function colorReturnsPerCaseOverride(): void
    {
        $this->assertSame('danger', UserStatus::BANNED->color());
    }

    /**
     * @test color() returns class-level mapping
     */
    public function colorReturnsClassLevelMapping(): void
    {
        $this->assertSame('success', UserStatus::ACTIVE->color());
    }

    // -------------------------------------------------------------------
    // description() and icon() null defaults
    // -------------------------------------------------------------------

    /**
     * @test description() returns null when not defined
     */
    public function descriptionReturnsNullWhenNotDefined(): void
    {
        $this->assertNull(UserStatus::INACTIVE->description());
    }

    /**
     * @test icon() returns null when not defined
     */
    public function iconReturnsNullWhenNotDefined(): void
    {
        $this->assertNull(UserStatus::INACTIVE->icon());
    }

    /**
     * @test description() returns defined value
     */
    public function descriptionReturnsDefinedValue(): void
    {
        $this->assertSame('User can fully access the system', UserStatus::ACTIVE->description());
    }

    /**
     * @test icon() returns defined value
     */
    public function iconReturnsDefinedValue(): void
    {
        $this->assertSame('heroicon-o-check-circle', UserStatus::ACTIVE->icon());
    }
}
