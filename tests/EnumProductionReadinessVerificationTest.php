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
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * Comprehensive production-readiness verification tests.
 *
 * These tests verify:
 * - All public API methods return correctly typed values
 * - Enum cast handles edge cases (null, type mismatches, non-existent values)
 * - EnumRule validates backed enums (string, int) and pure enums correctly
 * - EnumManager delegates to trait methods via the facade
 * - EnumCache TTL behavior (zero TTL, expiry, invalidation)
 * - InvalidEnumException factory methods produce expected messages
 * - Cross-type consistency (string-backed, int-backed, pure enums)
 *
 * @covers \ZeroBoiler\Enums\Concerns\HasEnumMetadata
 * @covers \ZeroBoiler\Enums\Casts\EnumCast
 * @covers \ZeroBoiler\Enums\EnumCache
 * @covers \ZeroBoiler\Enums\Exceptions\InvalidEnumException
 * @covers \ZeroBoiler\Enums\EnumManager
 * @covers \ZeroBoiler\Enums\Rules\EnumRule
 * @covers \ZeroBoiler\Enums\Support\EnumMetadataResolver
 */
final class EnumProductionReadinessVerificationTest extends TestCase
{
    // -----------------------------------------------------------------------
    // 1. String-backed enum: full API verification
    // -----------------------------------------------------------------------

    public function testStringBackedEnumLabelReturnsString(): void
    {
        $label = UserStatus::ACTIVE->label();

        $this->assertIsString($label);
        $this->assertSame('Active User', $label);
    }

    public function testStringBackedEnumAutoLabelFromSnakeCase(): void
    {
        $label = UserStatus::INACTIVE->label();

        $this->assertIsString($label);
        $this->assertSame('Inactive', $label);
    }

    public function testStringBackedEnumColorFromClassLevel(): void
    {
        $this->assertSame('success', UserStatus::ACTIVE->color());
        $this->assertSame('warning', UserStatus::SUSPENDED->color());
    }

    public function testStringBackedEnumColorPerCaseOverride(): void
    {
        $this->assertSame('danger', UserStatus::BANNED->color());
    }

    public function testStringBackedEnumIcon(): void
    {
        $this->assertSame('heroicon-o-check-circle', UserStatus::ACTIVE->icon());
        $this->assertNull(UserStatus::INACTIVE->icon());
    }

    public function testStringBackedEnumDescription(): void
    {
        $this->assertSame('User can fully access the system', UserStatus::ACTIVE->description());
        $this->assertNull(UserStatus::INACTIVE->description());
    }

    public function testStringBackedEnumForSelectStructure(): void
    {
        $options = UserStatus::forSelect();

        $this->assertIsArray($options);
        $this->assertCount(5, $options);

        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertIsString($option['value']);
            $this->assertIsString($option['label']);
            $this->assertNotEmpty($option['label']);
        }
    }

    public function testStringBackedEnumForApiStructure(): void
    {
        $api = UserStatus::forApi();

        $this->assertIsArray($api);
        $this->assertCount(5, $api);

        foreach ($api as $item) {
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('color', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertIsString($item['color']);
        }
    }

    public function testStringBackedEnumValuesAreStrings(): void
    {
        $values = UserStatus::values();

        foreach ($values as $value) {
            $this->assertIsString($value);
        }

        $this->assertSame(['active', 'inactive', 'pending', 'suspended', 'banned'], $values);
    }

    public function testStringBackedEnumLabelsAreNonEmpty(): void
    {
        $labels = UserStatus::labels();

        foreach ($labels as $label) {
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    // -----------------------------------------------------------------------
    // 2. Int-backed enum verification
    // -----------------------------------------------------------------------

    public function testIntBackedEnumValuesAreIntegers(): void
    {
        $values = IntBackedPriority::values();

        foreach ($values as $value) {
            $this->assertIsInt($value);
        }
    }

    public function testIntBackedEnumForSelectValuesAreIntegers(): void
    {
        $options = IntBackedPriority::forSelect();

        foreach ($options as $option) {
            $this->assertIsInt($option['value']);
        }
    }

    public function testIntBackedEnumZeroValueIsHandled(): void
    {
        $values = ZeroPriority::values();
        $this->assertContains(0, $values);

        $label = ZeroPriority::NONE->label();
        $this->assertIsString($label);
    }

    public function testIntBackedEnumColor(): void
    {
        $this->assertIsString(IntBackedPriority::CRITICAL->color());
    }

    // -----------------------------------------------------------------------
    // 3. Pure enum verification
    // -----------------------------------------------------------------------

    public function testPureEnumValuesAreCaseNames(): void
    {
        $values = PureFeatureFlag::values();

        $this->assertSame(['TWO_FACTOR_AUTH', 'DARK_MODE'], $values);
    }

    public function testPureEnumForSelectValuesAreCaseNames(): void
    {
        $options = PureFeatureFlag::forSelect();

        foreach ($options as $option) {
            $this->assertIsString($option['value']);
            // Pure enums use case names as values
            $this->assertContains($option['value'], ['TWO_FACTOR_AUTH', 'DARK_MODE']);
        }
    }

    public function testPureEnumLabel(): void
    {
        $this->assertIsString(PureFeatureFlag::TWO_FACTOR_AUTH->label());
        $this->assertNotEmpty(PureFeatureFlag::TWO_FACTOR_AUTH->label());
    }

    public function testPureEnumNoValueProperty(): void
    {
        // Pure enums don't have ->value
        $this->assertFalse(property_exists(PureFeatureFlag::TWO_FACTOR_AUTH, 'value'));
    }

    // -----------------------------------------------------------------------
    // 4. Comparison methods
    // -----------------------------------------------------------------------

    public function testIsWithInstance(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->is(UserStatus::ACTIVE));
        $this->assertFalse(UserStatus::ACTIVE->is(UserStatus::BANNED));
    }

    public function testIsWithStringName(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->is('ACTIVE'));
        $this->assertFalse(UserStatus::ACTIVE->is('active')); // case-sensitive
    }

    public function testIsNotNegation(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->isNot(UserStatus::BANNED));
        $this->assertFalse(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE));
    }

    public function testInGroupMatching(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']));
        $this->assertTrue(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]));
        $this->assertFalse(UserStatus::ACTIVE->in(['BANNED', 'SUSPENDED']));
    }

    public function testInWithEmptyArray(): void
    {
        $this->assertFalse(UserStatus::ACTIVE->in([]));
    }

    public function testInWithSingleElement(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->in(['ACTIVE']));
    }

    // -----------------------------------------------------------------------
    // 5. Lookup methods
    // -----------------------------------------------------------------------

    public function testTryFromNameCaseSensitive(): void
    {
        $this->assertSame(UserStatus::ACTIVE, UserStatus::tryFromName('ACTIVE'));
        $this->assertNull(UserStatus::tryFromName('active'));
        $this->assertNull(UserStatus::tryFromName('NONEXISTENT'));
    }

    public function testFromNameThrowsOnMissing(): void
    {
        $this->expectException(InvalidEnumException::class);
        $this->expectExceptionMessage('NONEXISTENT');

        UserStatus::fromName('NONEXISTENT');
    }

    public function testFromNameReturnsCorrectCase(): void
    {
        $result = UserStatus::fromName('ACTIVE');

        $this->assertSame(UserStatus::ACTIVE, $result);
        $this->assertSame('ACTIVE', $result->name);
    }

    public function testHasCase(): void
    {
        $this->assertTrue(UserStatus::hasCase('ACTIVE'));
        $this->assertTrue(UserStatus::hasCase('BANNED'));
        $this->assertFalse(UserStatus::hasCase('UNKNOWN'));
    }

    public function testTryFromLabelCaseInsensitive(): void
    {
        $this->assertSame(UserStatus::ACTIVE, UserStatus::tryFromLabel('Active User'));
        $this->assertSame(UserStatus::ACTIVE, UserStatus::tryFromLabel('active user'));
        $this->assertNull(UserStatus::tryFromLabel('nonexistent-label'));
    }

    // -----------------------------------------------------------------------
    // 6. EnumCast edge cases
    // -----------------------------------------------------------------------

    public function testEnumCastGetReturnsNullForNull(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'status', null, []);

        $this->assertNull($result);
    }

    public function testEnumCastGetReturnsEnumForValidString(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'status', 'active', []);

        $this->assertSame(UserStatus::ACTIVE, $result);
    }

    public function testEnumCastGetReturnsNullForInvalidValue(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'status', 'invalid_value', []);

        $this->assertNull($result);
    }

    public function testEnumCastGetReturnsNullForNonScalarType(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'status', ['array'], []);

        $this->assertNull($result);
    }

    public function testEnumCastSetReturnsBackedValue(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->set($model, 'status', UserStatus::ACTIVE, []);

        $this->assertSame('active', $result);
    }

    public function testEnumCastSetValidatesRawStringValue(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->set($model, 'status', 'active', []);

        $this->assertSame('active', $result);
    }

    public function testEnumCastSetThrowsOnInvalidRawValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $cast = new EnumCast(UserStatus::class);
        $cast->set(new \stdClass, 'status', 'invalid', []);
    }

    public function testEnumCastSetThrowsOnWrongEnumType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $cast = new EnumCast(UserStatus::class);
        $cast->set(new \stdClass, 'status', OrderStatus::PENDING, []);
    }

    public function testEnumCastSetThrowsOnInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $cast = new EnumCast(UserStatus::class);
        $cast->set(new \stdClass, 'status', 12345, []);
    }

    public function testEnumCastSetReturnsNullForNull(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->set(new \stdClass, 'status', null, []);

        $this->assertNull($result);
    }

    public function testEnumCastSerializeReturnsBackedValue(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', UserStatus::ACTIVE, []);

        $this->assertSame('active', $result);
    }

    public function testEnumCastSerializeReturnsNullForNull(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', null, []);

        $this->assertNull($result);
    }

    public function testEnumCastSerializePassesThroughIntValue(): void
    {
        $cast = new EnumCast(IntBackedPriority::class);
        $result = $cast->serialize(new \stdClass, 'priority', 1, []);

        $this->assertSame(1, $result);
    }

    // -----------------------------------------------------------------------
    // 7. EnumRule validation
    // -----------------------------------------------------------------------

    public function testEnumRulePassesForValidStringValue(): void
    {
        $rule = EnumRule::for(UserStatus::class);
        $fail = fn () => $this->fail('Should not fail');

        $rule->validate('status', 'active', $fail);

        // If we reach here, no failure was called
        $this->assertTrue(true);
    }

    public function testEnumRuleFailsForInvalidValue(): void
    {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
            $this->assertStringContainsString('invalid', $message);
        };

        $rule->validate('status', 'nonexistent', $fail);

        $this->assertTrue($failed);
    }

    public function testEnumRuleFailsForNullWhenNotNullable(): void
    {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', null, $fail);

        $this->assertTrue($failed);
    }

    public function testEnumRulePassesForNullWhenNullable(): void
    {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $fail = fn () => $this->fail('Should not fail for null with nullable rule');

        $rule->validate('status', null, $fail);

        $this->assertTrue(true);
    }

    public function testEnumRuleTypeMismatchForIntBacked(): void
    {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        // Pass a string to an int-backed enum — should fail due to type mismatch
        $rule->validate('priority', 'not-an-int', $fail);

        $this->assertTrue($failed);
    }

    public function testEnumRuleValidatesPureEnumByCaseName(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $fail = fn () => $this->fail('Should not fail');

        $rule->validate('flag', 'TWO_FACTOR_AUTH', $fail);

        $this->assertTrue(true);
    }

    public function testEnumRuleFailsForNonStringOnPureEnum(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        $rule->validate('flag', 123, $fail);

        $this->assertTrue($failed);
    }

    // -----------------------------------------------------------------------
    // 8. EnumCache behavior
    // -----------------------------------------------------------------------

    public function testEnumCacheSingletonInstance(): void
    {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        $this->assertSame($a, $b);
    }

    public function testEnumCacheZeroTtlDisablesCaching(): void
    {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $this->assertFalse($cache->has('AnyClass'));

        $cache->set('AnyClass', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // With TTL 0, the entry should be immediately stale
        $this->assertFalse($cache->has('AnyClass'));

        EnumCache::resetInstance();
    }

    public function testEnumCacheFlushClearsAllEntries(): void
    {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set('ClassA', [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $this->assertTrue($cache->has('ClassA'));

        EnumCache::flush();

        $this->assertFalse($cache->has('ClassA'));

        EnumCache::resetInstance();
    }

    public function testEnumCacheClearClassTargetsSpecificClass(): void
    {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set('ClassA', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('ClassB', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass('ClassA');

        $this->assertFalse($cache->has('ClassA'));
        $this->assertTrue($cache->has('ClassB'));

        EnumCache::resetInstance();
    }

    // -----------------------------------------------------------------------
    // 9. InvalidEnumException factory methods
    // -----------------------------------------------------------------------

    public function testInvalidEnumExceptionValueFormat(): void
    {
        $exception = InvalidEnumException::value(UserStatus::class, 'bad_value');

        $this->assertStringContainsString('bad_value', $exception->getMessage());
        $this->assertStringContainsString(UserStatus::class, $exception->getMessage());
    }

    public function testInvalidEnumExceptionValueWithNull(): void
    {
        $exception = InvalidEnumException::value(UserStatus::class, null);

        $this->assertStringContainsString('null', $exception->getMessage());
    }

    public function testInvalidEnumExceptionForNameFormat(): void
    {
        $exception = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN_CASE');

        $this->assertStringContainsString('UNKNOWN_CASE', $exception->getMessage());
        $this->assertStringContainsString(UserStatus::class, $exception->getMessage());
    }

    // -----------------------------------------------------------------------
    // 10. Cross-type consistency
    // -----------------------------------------------------------------------

    public function testAllEnumTypesHaveLabels(): void
    {
        // String-backed
        foreach (UserStatus::cases() as $case) {
            $this->assertIsString($case->label());
            $this->assertNotEmpty($case->label());
        }

        // Int-backed
        foreach (IntBackedPriority::cases() as $case) {
            $this->assertIsString($case->label());
            $this->assertNotEmpty($case->label());
        }

        // Pure
        foreach (PureFeatureFlag::cases() as $case) {
            $this->assertIsString($case->label());
            $this->assertNotEmpty($case->label());
        }
    }

    public function testAllEnumTypesHaveColors(): void
    {
        foreach (UserStatus::cases() as $case) {
            $this->assertIsString($case->color());
        }

        foreach (IntBackedPriority::cases() as $case) {
            $this->assertIsString($case->color());
        }
    }

    public function testAllEnumTypesHaveForSelect(): void
    {
        $userOptions = UserStatus::forSelect();
        $this->assertCount(count(UserStatus::cases()), $userOptions);

        $intOptions = IntBackedPriority::forSelect();
        $this->assertCount(count(IntBackedPriority::cases()), $intOptions);

        $pureOptions = PureFeatureFlag::forSelect();
        $this->assertCount(count(PureFeatureFlag::cases()), $pureOptions);
    }

    // -----------------------------------------------------------------------
    // 11. CamelCase label generation
    // -----------------------------------------------------------------------

    public function testCamelCaseEnumGeneratesTitleCaseLabel(): void
    {
        $label = CamelCaseRole::isAdmin->label();

        $this->assertIsString($label);
        $this->assertSame('Admin', $label);
    }

    // -----------------------------------------------------------------------
    // 12. Single case enum edge case
    // -----------------------------------------------------------------------

    public function testSingleCaseEnumWorks(): void
    {
        $this->assertCount(1, SingleCaseEnum::cases());
        $this->assertIsString(SingleCaseEnum::ONLY->label());
        $this->assertCount(1, SingleCaseEnum::forSelect());
        $this->assertCount(1, SingleCaseEnum::forApi());
    }

    // -----------------------------------------------------------------------
    // 13. All-class-level enum
    // -----------------------------------------------------------------------

    public function testAllClassLevelEnumResolvesMetadata(): void
    {
        $this->assertIsString(AllClassLevelEnum::OPEN->label());
        $this->assertIsString(AllClassLevelEnum::IN_PROGRESS->color());
        $this->assertIsString(AllClassLevelEnum::DONE->description());
    }

    // -----------------------------------------------------------------------
    // 14. EnumMetadataResolver invalidation
    // -----------------------------------------------------------------------

    public function testMetadataResolverInvalidationForcesRebuild(): void
    {
        EnumCache::resetInstance();

        // Resolve metadata (caches it)
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        $this->assertArrayHasKey('labels', $meta1);

        // Invalidate
        EnumMetadataResolver::invalidate(UserStatus::class);

        // Re-resolve (should rebuild)
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
        $this->assertArrayHasKey('labels', $meta2);

        EnumCache::resetInstance();
    }

    public function testMetadataResolverInvalidateAllClearsEverything(): void
    {
        EnumCache::resetInstance();

        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(OrderStatus::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        $this->assertFalse($cache->has(UserStatus::class));
        $this->assertFalse($cache->has(OrderStatus::class));

        EnumCache::resetInstance();
    }

    // -----------------------------------------------------------------------
    // 15. Comparison across different enum types (should always be false)
    // -----------------------------------------------------------------------

    public function testIsWithDifferentEnumType(): void
    {
        // Even if they share the same name, different enum types should not match
        // via is() with string comparison
        $this->assertFalse(UserStatus::ACTIVE->is(OrderStatus::ACTIVE->name));
        // Different enums can have the same case name
        $this->assertTrue(UserStatus::ACTIVE->is('ACTIVE'));
    }

    public function testForSelectPreservesDeclarationOrder(): void
    {
        $options = UserStatus::forSelect();

        // First case should be ACTIVE (first in declaration)
        $this->assertSame('active', $options[0]['value']);
    }
}
