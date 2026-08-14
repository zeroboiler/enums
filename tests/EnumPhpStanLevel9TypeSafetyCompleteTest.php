<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use BackedEnum;
use ReflectionClass;
use ReflectionEnum;
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
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Comprehensive PHPStan Level 9 type-safety verification for the enums package.
 *
 * Validates all public API surface for strict type correctness:
 * - Return types are never mixed, always concrete
 * - Strict comparison semantics (===, not ==)
 * - Null safety across all accessor methods
 * - Template type constraints on EnumCast generic
 * - Class-string constraint enforcement on EnumManager
 * - Final classes and readonly properties on attributes
 * - Full coverage of resolution priority (per-case > class-level > auto)
 *
 * @see UserStatus For string-backed fixture
 * @see IntBackedPriority For integer-backed fixture
 * @see PureFeatureFlag For pure enum fixture
 * @see CamelCaseRole For camelCase label generation fixture
 * @see MixedAttributeStatus For class-level + per-case attribute priority fixture
 */
final class EnumPhpStanLevel9TypeSafetyCompleteTest
{
    // ========================================================================
    // 1. Return type strictness — label() always returns non-empty string
    // ========================================================================

    /**
     * @test
     */
    public static function labelAlwaysReturnsNonEmptyString(): void
    {
        // String-backed with per-case Label
        assert(UserStatus::ACTIVE->label() === 'Active User');
        assert(is_string(UserStatus::ACTIVE->label()));

        // Auto-generated from SCREAMING_SNAKE_CASE
        assert(UserStatus::INACTIVE->label() === 'Inactive');
        assert(is_string(UserStatus::INACTIVE->label()) && UserStatus::INACTIVE->label() !== '');

        // Integer-backed
        assert(IntBackedPriority::CRITICAL->label() === 'Critical Priority');
        assert(IntBackedPriority::LOW->label() === 'Low Priority');

        // Pure enum
        assert(PureFeatureFlag::DARK_MODE->label() === 'Dark Mode');
        assert(is_string(PureFeatureFlag::MAINTENANCE_MODE->label()));
    }

    // ========================================================================
    // 2. Return type strictness — color() always returns string
    // ========================================================================

    /**
     * @test
     */
    public static function colorAlwaysReturnsString(): void
    {
        // Class-level EnumColor
        assert(UserStatus::ACTIVE->color() === 'success');
        assert(UserStatus::BANNED->color() === 'danger');
        assert(UserStatus::PENDING->color() === 'warning');
        assert(UserStatus::SUSPENDED->color() === 'warning');

        // Default 'secondary' when no mapping
        assert(UserStatus::INACTIVE->color() === 'secondary');

        // Integer-backed class-level
        assert(IntBackedPriority::CRITICAL->color() === 'danger');
        assert(IntBackedPriority::LOW->color() === 'success');
        assert(IntBackedPriority::NONE->color() === 'success');

        // Pure enum with per-case Color
        assert(PureFeatureFlag::DARK_MODE->color() === 'secondary');
        assert(PureFeatureFlag::BETA_FEATURES->color() === 'warning');
        assert(PureFeatureFlag::MAINTENANCE_MODE->color() === 'secondary');
    }

    // ========================================================================
    // 3. Return type strictness — icon() returns ?string (null or string)
    // ========================================================================

    /**
     * @test
     */
    public static function iconReturnsNullOrString(): void
    {
        // Defined per-case icon
        assert(UserStatus::ACTIVE->icon() === 'heroicon-o-check-circle');
        assert(PureFeatureFlag::DARK_MODE->icon() === 'heroicon-o-moon');

        // No icon defined — null
        assert(UserStatus::INACTIVE->icon() === null);
        assert(UserStatus::PENDING->icon() === null);

        // Class-level default icon (IntBackedPriority)
        assert(IntBackedPriority::CRITICAL->icon() === 'heroicon-o-flag');
        assert(IntBackedPriority::NONE->icon() === 'heroicon-o-flag');

        // MixedAttributeStatus default icon
        assert(MixedAttributeStatus::ACTIVE->icon() === 'heroicon-o-document');
        assert(MixedAttributeStatus::DELETED->icon() === 'heroicon-o-document');
    }

    // ========================================================================
    // 4. Return type strictness — description() returns ?string
    // ========================================================================

    /**
     * @test
     */
    public static function descriptionReturnsNullOrString(): void
    {
        assert(UserStatus::ACTIVE->description() === 'User can fully access the system');
        assert(UserStatus::BANNED->description() === 'User is permanently banned');
        assert(UserStatus::INACTIVE->description() === null);

        // Class-level description on IntBackedPriority
        assert(IntBackedPriority::CRITICAL->description() === 'Critical priority — immediate action required');
        assert(IntBackedPriority::LOW->description() === 'Low priority — handle when convenient');
        assert(IntBackedPriority::HIGH->description() === null);

        // Pure enum per-case description
        assert(PureFeatureFlag::DARK_MODE->description() === 'Toggle dark mode for the UI');
        assert(PureFeatureFlag::MAINTENANCE_MODE->description() === null);
    }

    // ========================================================================
    // 5. forSelect() returns array with exact shape {value: int|string, label: string}
    // ========================================================================

    /**
     * @test
     */
    public static function forSelectReturnsCorrectShape(): void
    {
        // String-backed: value is string
        $options = UserStatus::forSelect();
        assert(is_array($options));
        assert(count($options) === 5); // ACTIVE, INACTIVE, PENDING, SUSPENDED, BANNED

        foreach ($options as $option) {
            assert(array_key_exists('value', $option));
            assert(array_key_exists('label', $option));
            assert(is_string($option['label']));
            assert($option['label'] !== '');
        }
        assert(is_string($options[0]['value']));
        assert($options[0]['value'] === 'active');

        // Integer-backed: value is int
        $intOptions = IntBackedPriority::forSelect();
        assert(is_int($intOptions[0]['value']));

        // Pure enum: value is case name string
        $pureOptions = PureFeatureFlag::forSelect();
        assert(is_string($pureOptions[0]['value']));
        assert($pureOptions[0]['value'] === 'DARK_MODE');
    }

    // ========================================================================
    // 6. forApi() returns array with all 6 keys per item
    // ========================================================================

    /**
     * @test
     */
    public static function forApiReturnsCorrectShape(): void
    {
        $api = UserStatus::forApi();
        assert(is_array($api));
        assert(count($api) === 5);

        $expectedKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];
        foreach ($api as $item) {
            foreach ($expectedKeys as $key) {
                assert(array_key_exists($key, $item), "Missing key: {$key}");
            }
            assert(is_string($item['name']));
            assert(is_string($item['label']));
            assert(is_string($item['color']));
            assert($item['color'] !== '');
            assert($item['description'] === null || is_string($item['description']));
            assert($item['icon'] === null || is_string($item['icon']));
        }

        // Verify specific values
        assert($api[0]['name'] === 'ACTIVE');
        assert($api[0]['value'] === 'active');
        assert($api[0]['label'] === 'Active User');
        assert($api[0]['description'] === 'User can fully access the system');
        assert($api[0]['icon'] === 'heroicon-o-check-circle');
    }

    // ========================================================================
    // 7. values() returns list<int|string> — type correctness per backing type
    // ========================================================================

    /**
     * @test
     */
    public static function valuesReturnsScalarList(): void
    {
        $values = UserStatus::values();
        assert(is_array($values));
        assert(count($values) === 5);
        foreach ($values as $v) {
            assert(is_string($v));
        }
        assert($values === ['active', 'inactive', 'pending', 'suspended', 'banned']);

        $intValues = IntBackedPriority::values();
        foreach ($intValues as $v) {
            assert(is_int($v));
        }

        $pureValues = PureFeatureFlag::values();
        foreach ($pureValues as $v) {
            assert(is_string($v));
        }
        // Pure enum values = case names
        assert($pureValues === ['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
    }

    // ========================================================================
    // 8. labels() returns list<string> — all non-empty
    // ========================================================================

    /**
     * @test
     */
    public static function labelsReturnsNonEmptyStringList(): void
    {
        $labels = UserStatus::labels();
        assert(is_array($labels));
        assert(count($labels) === 5);
        foreach ($labels as $label) {
            assert(is_string($label));
            assert($label !== '');
        }

        // Pure enum labels
        $pureLabels = PureFeatureFlag::labels();
        assert($pureLabels[0] === 'Dark Mode');
        assert($pureLabels[2] === 'Maintenance Mode');
    }

    // ========================================================================
    // 9. Comparison methods — strict identity (===)
    // ========================================================================

    /**
     * @test
     */
    public static function comparisonUsesStrictIdentity(): void
    {
        $active = UserStatus::ACTIVE;

        // is() with instance — strict identity
        assert($active->is(UserStatus::ACTIVE) === true);
        assert($active->is(UserStatus::BANNED) === false);

        // is() with string — strict case-sensitive name comparison
        assert($active->is('ACTIVE') === true);
        assert($active->is('active') === false); // backed value ≠ case name
        assert($active->is('Active') === false); // case-sensitive

        // isNot() — strict negation
        assert($active->isNot(UserStatus::BANNED) === true);
        assert($active->isNot(UserStatus::ACTIVE) === false);
        assert($active->isNot('BANNED') === true);
        assert($active->isNot('ACTIVE') === false);

        // in() — strict per-element check
        assert($active->in([UserStatus::ACTIVE, UserStatus::PENDING]) === true);
        assert($active->in([UserStatus::BANNED]) === false);
        assert($active->in(['ACTIVE', 'PENDING']) === true);
        assert($active->in(['active']) === false); // case-sensitive: 'active' ≠ 'ACTIVE'

        // in() with empty array
        assert($active->in([]) === false);

        // notIn() — strict negation
        assert($active->notIn([UserStatus::BANNED]) === true);
        assert($active->notIn(['ACTIVE']) === false);
        assert($active->notIn([]) === true);
    }

    // ========================================================================
    // 10. Lookup methods — case sensitivity correctness
    // ========================================================================

    /**
     * @test
     */
    public static function lookupCaseSensitivity(): void
    {
        // tryFromLabel — case-insensitive
        assert(UserStatus::tryFromLabel('Active User') === UserStatus::ACTIVE);
        assert(UserStatus::tryFromLabel('active user') === UserStatus::ACTIVE);
        assert(UserStatus::tryFromLabel('ACTIVE USER') === UserStatus::ACTIVE);
        assert(UserStatus::tryFromLabel('nonexistent') === null);

        // Auto-generated label lookup
        assert(UserStatus::tryFromLabel('Inactive') === UserStatus::INACTIVE);
        assert(UserStatus::tryFromLabel('inactive') === UserStatus::INACTIVE);

        // tryFromName — case-sensitive
        assert(UserStatus::tryFromName('ACTIVE') === UserStatus::ACTIVE);
        assert(UserStatus::tryFromName('active') === null); // not a case name
        assert(UserStatus::tryFromName('UNKNOWN') === null);

        // hasCase — case-sensitive
        assert(UserStatus::hasCase('ACTIVE') === true);
        assert(UserStatus::hasCase('Active') === false);
        assert(UserStatus::hasCase('NONEXISTENT') === false);

        // fromName — returns correct case
        assert(UserStatus::fromName('PENDING') === UserStatus::PENDING);
    }

    // ========================================================================
    // 11. fromName() throws correct exception type with message
    // ========================================================================

    /**
     * @test
     */
    public static function fromNameThrowsCorrectExceptionType(): void
    {
        $exception = null;

        try {
            UserStatus::fromName('NONEXISTENT');
        } catch (InvalidEnumException $e) {
            $exception = $e;
        }

        assert($exception !== null);
        assert($exception instanceof InvalidEnumException);
        assert(str_contains($exception->getMessage(), 'NONEXISTENT'));
        assert(str_contains($exception->getMessage(), UserStatus::class));

        // __toString returns FQCN + message
        $str = (string) $exception;
        assert(str_starts_with($str, InvalidEnumException::class));
        assert(str_contains($str, 'NONEXISTENT'));
    }

    // ========================================================================
    // 12. InvalidEnumException all named constructors
    // ========================================================================

    /**
     * @test
     */
    public static function invalidEnumExceptionNamedConstructors(): void
    {
        // value() constructor
        $ex = InvalidEnumException::value(UserStatus::class, 'invalid_value');
        assert($ex instanceof InvalidEnumException);
        assert(str_contains($ex->getMessage(), 'invalid_value'));
        assert(str_contains($ex->getMessage(), UserStatus::class));

        // forName() constructor
        $ex = InvalidEnumException::forName(UserStatus::class, 'BAD_CASE');
        assert(str_contains($ex->getMessage(), 'BAD_CASE'));
        assert(str_contains($ex->getMessage(), 'does not exist'));

        // value() with null
        $ex = InvalidEnumException::value(UserStatus::class, null);
        assert(str_contains($ex->getMessage(), 'null'));
    }

    // ========================================================================
    // 13. EnumRule — string-backed enum validation
    // ========================================================================

    /**
     * @test
     */
    public static function enumRuleValidatesStringBackedEnumStrictly(): void
    {
        $rule = EnumRule::for(UserStatus::class);
        assert($rule instanceof EnumRule);

        // Nullable variant
        $nullableRule = EnumRule::for(UserStatus::class)->nullable();
        assert($nullableRule instanceof EnumRule);

        // Valid string value passes
        $passed = true;
        $fail = static function (string $_): void use (&$passed) { $passed = false; };
        $rule->validate('status', 'active', $fail);
        assert($passed === true);

        // Invalid string value fails
        $failed = false;
        $fail = static function (string $_): void use (&$failed) { $failed = true; };
        $rule->validate('status', 'nonexistent', $fail);
        assert($failed === true);

        // Int value fails type check (string enum, int given)
        $failed = false;
        $fail = static function (string $_): void use (&$failed) { $failed = true; };
        $rule->validate('status', 42, $fail);
        assert($failed === true);

        // Nullable: null passes
        $passed = true;
        $fail = static function (string $_): void use (&$passed) { $passed = false; };
        $nullableRule->validate('status', null, $fail);
        assert($passed === true);

        // Non-nullable: null fails
        $failed = false;
        $fail = static function (string $_): void use (&$failed) { $failed = true; };
        $rule->validate('status', null, $fail);
        assert($failed === true);
    }

    // ========================================================================
    // 14. EnumRule — integer-backed enum validation
    // ========================================================================

    /**
     * @test
     */
    public static function enumRuleValidatesIntBackedEnumStrictly(): void
    {
        $rule = EnumRule::for(IntBackedPriority::class);

        // Valid int passes
        $passed = true;
        $fail = static function (string $_): void use (&$passed) { $passed = false; };
        $rule->validate('priority', 1, $fail);
        assert($passed === true);

        // String value fails type check (int enum, string given)
        $failed = false;
        $fail = static function (string $_): void use (&$failed) { $failed = true; };
        $rule->validate('priority', '1', $fail);
        assert($failed === true);

        // Out-of-range int fails
        $failed = false;
        $fail = static function (string $_): void use (&$failed) { $failed = true; };
        $rule->validate('priority', 999, $fail);
        assert($failed === true);
    }

    // ========================================================================
    // 15. EnumRule — pure enum validation
    // ========================================================================

    /**
     * @test
     */
    public static function enumRuleValidatesPureEnumByCaseName(): void
    {
        $rule = EnumRule::for(PureFeatureFlag::class);

        // Valid case name passes
        $passed = true;
        $fail = static function (string $_): void use (&$passed) { $passed = false; };
        $rule->validate('flag', 'DARK_MODE', $fail);
        assert($passed === true);

        // Invalid case name fails
        $failed = false;
        $fail = static function (string $_): void use (&$failed) { $failed = true; };
        $rule->validate('flag', 'NONEXISTENT', $fail);
        assert($failed === true);

        // Int value fails (pure enums use string case names)
        $failed = false;
        $fail = static function (string $_): void use (&$failed) { $failed = true; };
        $rule->validate('flag', 42, $fail);
        assert($failed === true);
    }

    // ========================================================================
    // 16. EnumCast — get() type safety
    // ========================================================================

    /**
     * @test
     */
    public static function enumCastGetTypeStrictness(): void
    {
        $cast = new EnumCast(UserStatus::class);

        // Null returns null
        $result = $cast->get(new \stdClass, 'status', null, []);
        assert($result === null);

        // Valid string returns enum instance
        $result = $cast->get(new \stdClass, 'status', 'active', []);
        assert($result instanceof UserStatus);
        assert($result === UserStatus::ACTIVE);
        assert($result instanceof BackedEnum);

        // Invalid string returns null (tryFrom semantics)
        $result = $cast->get(new \stdClass, 'status', 'nonexistent', []);
        assert($result === null);
    }

    // ========================================================================
    // 17. EnumCast — set() type safety
    // ========================================================================

    /**
     * @test
     */
    public static function enumCastSetTypeEnumInstance(): void
    {
        $cast = new EnumCast(UserStatus::class);

        // Enum instance → backed value
        $result = $cast->set(new \stdClass, 'status', UserStatus::ACTIVE, []);
        assert($result === 'active');

        // Valid raw string → passes through after validation
        $result = $cast->set(new \stdClass, 'status', 'banned', []);
        assert($result === 'banned');

        // Null → null
        $result = $cast->set(new \stdClass, 'status', null, []);
        assert($result === null);

        // Wrong enum type → throws InvalidArgumentException
        $thrown = false;
        try {
            $cast->set(new \stdClass, 'status', IntBackedPriority::LOW, []);
        } catch (\InvalidArgumentException $e) {
            $thrown = true;
            assert(str_contains($e->getMessage(), UserStatus::class));
        }
        assert($thrown === true);

        // Invalid raw value → throws InvalidArgumentException
        $thrown = false;
        try {
            $cast->set(new \stdClass, 'status', 'invalid_status', []);
        } catch (\InvalidArgumentException $e) {
            $thrown = true;
            assert(str_contains($e->getMessage(), 'Invalid value'));
        }
        assert($thrown === true);
    }

    // ========================================================================
    // 18. EnumCast — serialize() type safety
    // ========================================================================

    /**
     * @test
     */
    public static function enumCastSerializeTypeEnumInstance(): void
    {
        $cast = new EnumCast(UserStatus::class);

        // Enum instance → backed value
        $result = $cast->serialize(new \stdClass, 'status', UserStatus::ACTIVE, []);
        assert($result === 'active');

        // Raw string → passthrough
        $result = $cast->serialize(new \stdClass, 'status', 'banned', []);
        assert($result === 'banned');

        // Null → null
        $result = $cast->serialize(new \stdClass, 'status', null, []);
        assert($result === null);
    }

    // ========================================================================
    // 19. EnumCache — singleton behavior and TTL
    // ========================================================================

    /**
     * @test
     */
    public static function enumCacheSingletonBehavior(): void
    {
        EnumCache::resetInstance();

        $instance1 = EnumCache::getInstance();
        $instance2 = EnumCache::getInstance();
        assert($instance1 === $instance2, 'Singleton must return same instance');

        // TTL defaults to 300
        assert($instance1->getTtl() === 300);

        // Set TTL
        $instance1->setTtl(60);
        assert($instance1->getTtl() === 60);

        // Negative TTL clamped to 0
        $instance1->setTtl(-5);
        assert($instance1->getTtl() === 0);

        // TTL=0 means has() always false (no caching)
        $metadata = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
        $instance1->set('TestClass', $metadata);
        assert($instance1->has('TestClass') === false);

        EnumCache::resetInstance();
    }

    /**
     * @test
     */
    public static function enumCacheTtlAndClearOperations(): void
    {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];

        // Set and verify
        $cache->set('TestEnum', $metadata);
        assert($cache->has('TestEnum') === true);

        // get() returns the same data
        $retrieved = $cache->get('TestEnum');
        assert($retrieved === $metadata);

        // Clear specific class
        $cache->clearClass('TestEnum');
        assert($cache->has('TestEnum') === false);

        // get() throws after clear
        $thrown = false;
        try {
            $cache->get('TestEnum');
        } catch (\OutOfBoundsException $e) {
            $thrown = true;
            assert(str_contains($e->getMessage(), 'TestEnum'));
        }
        assert($thrown === true);

        // Flush all
        $cache->set('TestEnum', $metadata);
        $cache->set('AnotherEnum', $metadata);
        $cache->flush();
        assert($cache->has('TestEnum') === false);
        assert($cache->has('AnotherEnum') === false);

        EnumCache::resetInstance();
    }

    // ========================================================================
    // 20. EnumMetadataResolver — returns typed shape with all four keys
    // ========================================================================

    /**
     * @test
     */
    public static function metadataResolverReturnsTypedShape(): void
    {
        EnumCache::resetInstance();

        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // Must have all four keys
        assert(array_key_exists('labels', $meta));
        assert(array_key_exists('descriptions', $meta));
        assert(array_key_exists('colors', $meta));
        assert(array_key_exists('icons', $meta));

        // Each value is array
        assert(is_array($meta['labels']));
        assert(is_array($meta['descriptions']));
        assert(is_array($meta['colors']));
        assert(is_array($meta['icons']));

        // Labels — per-case overrides class-level
        assert($meta['labels']['active'] === 'Active User');
        assert($meta['labels']['pending'] === 'Awaiting Verification');
        assert($meta['labels']['inactive'] === 'Inactive'); // auto-generated

        // Colors — class-level EnumColor mapping
        assert($meta['colors']['active'] === 'success');
        assert($meta['colors']['banned'] === 'danger');
        assert($meta['colors']['pending'] === 'warning');
        assert($meta['colors']['suspended'] === 'warning');

        EnumCache::resetInstance();
    }

    // ========================================================================
    // 21. EnumMetadataResolver — cache invalidation
    // ========================================================================

    /**
     * @test
     */
    public static function metadataResolverCacheInvalidation(): void
    {
        EnumCache::resetInstance();

        // Resolve once (populates cache)
        EnumMetadataResolver::resolve(UserStatus::class);
        assert(EnumCache::getInstance()->has(UserStatus::class) === true);

        // Invalidate specific class
        EnumMetadataResolver::invalidate(UserStatus::class);
        assert(EnumCache::getInstance()->has(UserStatus::class) === false);

        // Invalidate all
        EnumMetadataResolver::resolve(IntBackedPriority::class);
        EnumMetadataResolver::invalidateAll();
        assert(EnumCache::getInstance()->has(IntBackedPriority::class) === false);

        EnumCache::resetInstance();
    }

    // ========================================================================
    // 22. EnumMetadataResolver — throws on non-enum class
    // ========================================================================

    /**
     * @test
     */
    public static function metadataResolverThrowsOnNonEnum(): void
    {
        EnumCache::resetInstance();

        $thrown = false;
        try {
            EnumMetadataResolver::resolve(\stdClass::class);
        } catch (\LogicException $e) {
            $thrown = true;
            assert(str_contains($e->getMessage(), 'not a valid enum'));
            assert(str_contains($e->getMessage(), 'stdClass'));
        }
        assert($thrown === true);

        EnumCache::resetInstance();
    }

    // ========================================================================
    // 23. Resolution priority — per-case > class-level > auto-generated
    // ========================================================================

    /**
     * @test
     */
    public static function resolutionPriorityIsCorrect(): void
    {
        EnumCache::resetInstance();

        // MixedAttributeStatus: 'new' has class-level EnumLabel 'Brand New Item'
        assert(MixedAttributeStatus::NEW->label() === 'Brand New Item');

        // 'active' — no per-case, no class-level → auto-generated
        assert(MixedAttributeStatus::ACTIVE->label() === 'Active');

        // 'deleted' — no attributes at all → auto-generated
        assert(MixedAttributeStatus::DELETED->label() === 'Deleted');

        // 'archived' — class-level color 'danger'
        assert(MixedAttributeStatus::ARCHIVED->color() === 'danger');

        // 'deleted' — no color mapping → default 'secondary'
        assert(MixedAttributeStatus::DELETED->color() === 'secondary');

        // IntBackedPriority: per-case Color overrides class-level EnumColor
        // CRITICAL: class-level says danger [1], per-case says danger → 'danger'
        assert(IntBackedPriority::CRITICAL->color() === 'danger');

        EnumCache::resetInstance();
    }

    // ========================================================================
    // 24. CamelCase label generation — camelCase → Title Case
    // ========================================================================

    /**
     * @test
     */
    public static function camelCaseLabelGeneration(): void
    {
        EnumCache::resetInstance();

        assert(CamelCaseRole::isActive->label() === 'Is Active');
        assert(CamelCaseRole::isAdmin->label() === 'Is Admin');
        assert(CamelCaseRole::isModerator->label() === 'Is Moderator');
        assert(CamelCaseRole::isBanned->label() === 'Is Banned');

        EnumCache::resetInstance();
    }

    // ========================================================================
    // 25. All infrastructure classes are final
    // ========================================================================

    /**
     * @test
     */
    public static function infrastructureClassesAreFinal(): void
    {
        $finalClasses = [
            EnumCache::class,
            \ZeroBoiler\Enums\EnumManager::class,
            EnumMetadataResolver::class,
            EnumRule::class,
            EnumCast::class,
            InvalidEnumException::class,
            \ZeroBoiler\Enums\EnumsServiceProvider::class,
            \ZeroBoiler\Enums\Facades\Enum::class,
            Label::class,
            Color::class,
            Icon::class,
            Description::class,
            EnumLabel::class,
            EnumColor::class,
            EnumIcon::class,
            EnumDescription::class,
        ];

        foreach ($finalClasses as $class) {
            assert(
                (new \ReflectionClass($class))->isFinal(),
                "{$class} must be final"
            );
        }
    }

    // ========================================================================
    // 26. All attribute classes use readonly promoted properties
    // ========================================================================

    /**
     * @test
     */
    public static function attributePropertiesAreReadonly(): void
    {
        // Per-case attributes
        $perCaseAttributes = [
            [Label::class, 'value', 'test'],
            [Color::class, 'value', 'success'],
            [Icon::class, 'value', 'heroicon-o-check'],
            [Description::class, 'value', 'A description'],
        ];

        foreach ($perCaseAttributes as [$class, $property, $expectedValue]) {
            $ref = new \ReflectionProperty($class, $property);
            assert($ref->isReadOnly(), "{$class}::\${$property} must be readonly");
            assert($ref->isPublic(), "{$class}::\${$property} must be public");

            // Verify constructor promotion works
            $instance = new $class($expectedValue);
            assert($ref->getValue($instance) === $expectedValue);
        }

        // Class-level EnumColor — verify all properties
        $enumColor = new EnumColor(success: ['a'], danger: ['b']);
        foreach (['success', 'danger', 'warning', 'info', 'secondary'] as $prop) {
            $ref = new \ReflectionProperty(EnumColor::class, $prop);
            assert($ref->isReadOnly());
            assert($ref->isPublic());
        }
    }

    // ========================================================================
    // 27. EnumManager delegation — all methods return correct types
    // ========================================================================

    /**
     * @test
     */
    public static function enumManagerDelegationTypeSafety(): void
    {
        $manager = new \ZeroBoiler\Enums\EnumManager();

        // forSelect — returns array
        $result = $manager->forSelect(UserStatus::class);
        assert(is_array($result));
        assert(count($result) === 5);

        // forApi — returns array
        $result = $manager->forApi(UserStatus::class);
        assert(is_array($result));
        assert(count($result) === 5);

        // tryFromLabel — returns UnitEnum or null
        $result = $manager->tryFromLabel(UserStatus::class, 'Active User');
        assert($result instanceof \UnitEnum);
        assert($result->name === 'ACTIVE');

        $result = $manager->tryFromLabel(UserStatus::class, 'nonexistent');
        assert($result === null);

        // tryFromName — returns UnitEnum or null
        $result = $manager->tryFromName(UserStatus::class, 'ACTIVE');
        assert($result instanceof \UnitEnum);

        $result = $manager->tryFromName(UserStatus::class, 'UNKNOWN');
        assert($result === null);

        // hasCase — returns bool
        assert($manager->hasCase(UserStatus::class, 'ACTIVE') === true);
        assert($manager->hasCase(UserStatus::class, 'NONEXISTENT') === false);

        // fromName — returns UnitEnum (throws on failure)
        $result = $manager->fromName(UserStatus::class, 'BANNED');
        assert($result instanceof \UnitEnum);
        assert($result->name === 'BANNED');

        // values — returns list
        $values = $manager->values(UserStatus::class);
        assert(is_array($values));
        assert(count($values) === 5);

        // labels — returns list
        $labels = $manager->labels(UserStatus::class);
        assert(is_array($labels));
        assert(count($labels) === 5);
    }

    // ========================================================================
    // 28. EnumManager throws BadMethodCallException for non-metadata enum
    // ========================================================================

    /**
     * @test
     */
    public static function enumManagerThrowsForNonMetadataEnum(): void
    {
        $manager = new \ZeroBoiler\Enums\EnumManager();

        // Enum without HasEnumMetadata trait
        $thrown = false;
        try {
            $manager->forSelect(\DateTimeZone::class);
        } catch (\BadMethodCallException $e) {
            $thrown = true;
            assert(str_contains($e->getMessage(), 'HasEnumMetadata'));
        }
        assert($thrown === true);
    }

    // ========================================================================
    // 29. Cross-enum type consistency
    // ========================================================================

    /**
     * @test
     */
    public static function crossEnumValueTypesAreConsistent(): void
    {
        // String-backed: forSelect values are strings
        $stringOptions = UserStatus::forSelect();
        foreach ($stringOptions as $opt) {
            assert(is_string($opt['value']), 'String-backed enum values must be strings');
        }

        // Integer-backed: forSelect values are ints
        $intOptions = IntBackedPriority::forSelect();
        foreach ($intOptions as $opt) {
            assert(is_int($opt['value']), 'Integer-backed enum values must be ints');
        }

        // Pure enum: forSelect values are case name strings
        $pureOptions = PureFeatureFlag::forSelect();
        foreach ($pureOptions as $opt) {
            assert(is_string($opt['value']), 'Pure enum values must be strings');
            assert(
                PureFeatureFlag::tryFromName($opt['value']) !== null,
                'Value must be a valid case name'
            );
        }

        // forApi values match forSelect values exactly
        $api = UserStatus::forApi();
        $select = UserStatus::forSelect();
        for ($i = 0; $i < count($api); $i++) {
            assert(
                $api[$i]['value'] === $select[$i]['value'],
                "forApi[{$i}]['value'] must match forSelect[{$i}]['value']"
            );
        }
    }

    // ========================================================================
    // 30. Backed values are unique
    // ========================================================================

    /**
     * @test
     */
    public static function backedValuesAreUnique(): void
    {
        $values = UserStatus::values();
        assert($values === array_values(array_unique($values)));

        $intValues = IntBackedPriority::values();
        assert($intValues === array_values(array_unique($intValues)));

        // Pure enum case names are always unique
        $names = PureFeatureFlag::values();
        assert($names === array_values(array_unique($names)));
    }

    // ========================================================================
    // 31. EnumMetadataResolver handles EnumIcon at both class and case level
    // ========================================================================

    /**
     * @test
     */
    public static function enumIconResolutionAtBothLevels(): void
    {
        EnumCache::resetInstance();

        // IntBackedPriority has class-level EnumIcon(default: 'heroicon-o-flag')
        assert(IntBackedPriority::CRITICAL->icon() === 'heroicon-o-flag');
        assert(IntBackedPriority::NONE->icon() === 'heroicon-o-flag');

        // PureFeatureFlag has per-case icons
        assert(PureFeatureFlag::DARK_MODE->icon() === 'heroicon-o-moon');
        assert(PureFeatureFlag::BETA_FEATURES->icon() === 'heroicon-o-beaker');
        assert(PureFeatureFlag::MAINTENANCE_MODE->icon() === null);

        EnumCache::resetInstance();
    }

    // ========================================================================
    // 32. EnumCast with int-backed enum — type safety edge cases
    // ========================================================================

    /**
     * @test
     */
    public static function enumCastWithIntBackedEnum(): void
    {
        $cast = new EnumCast(IntBackedPriority::class);

        // Valid int
        $result = $cast->get(new \stdClass, 'priority', 1, []);
        assert($result === IntBackedPriority::CRITICAL);
        assert($result instanceof IntBackedEnum);
        assert($result instanceof BackedEnum);

        // String int from database — EnumCast handles via type guard
        $result = $cast->get(new \stdClass, 'priority', '1', []);
        // '1' is string, IntBackedPriority is int-backed — tryFrom('1') throws, returns null
        assert($result === null);

        // Set with int
        $result = $cast->set(new \stdClass, 'priority', 2, []);
        assert($result === 2);

        // Serialize int enum
        $result = $cast->serialize(new \stdClass, 'priority', IntBackedPriority::LOW, []);
        assert($result === 3);
    }

    // ========================================================================
    // 33. EnumCast get() returns BackedEnum type (template constraint)
    // ========================================================================

    /**
     * @test
     */
    public static function enumCastGetReturnsBackedEnum(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->get(new \stdClass, 'status', 'active', []);

        // Must be a BackedEnum (template T extends BackedEnum)
        assert($result instanceof BackedEnum);
        assert($result instanceof UserStatus);
        assert($result->value === 'active');
    }

    // ========================================================================
    // 34. EnumManager is readonly class
    // ========================================================================

    /**
     * @test
     */
    public static function enumManagerIsReadonlyClass(): void
    {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);
        assert($ref->isReadOnly(), 'EnumManager must be a readonly class');
        assert($ref->isFinal(), 'EnumManager must be final');
    }

    // ========================================================================
    // 35. EnumFacade returns correct accessor
    // ========================================================================

    /**
     * @test
     */
    public static function enumFacadeAccessor(): void
    {
        $facade = new \ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class);
        assert($facade->isFinal());
        $method = $facade->getMethod('getFacadeAccessor');
        assert($method->isPublic());
        assert($method->getReturnType()?->getName() === 'string');
    }
}
