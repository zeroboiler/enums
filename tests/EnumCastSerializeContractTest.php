<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * EnumCast::serialize() — contract compliance and edge case tests.
 *
 * Validates that the serialize() method correctly converts enum instances
 * and raw values to their storable/primitive forms. Covers:
 * - Backed enum instance → backed value
 * - Raw string value → passthrough
 * - Raw int value → passthrough
 * - Null → null
 * - Non-scalar/non-enum → null (graceful degradation)
 */
final class EnumCastSerializeContractTest extends TestCase
{
    // -----------------------------------------------------------------------
    // String-backed enum serialization
    // -----------------------------------------------------------------------

    public function testSerializeReturnsBackedValueForStringEnumInstance(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', UserStatus::ACTIVE, []);

        self::assertSame('active', $result);
    }

    public function testSerializePassesThroughRawStringValue(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', 'banned', []);

        self::assertSame('banned', $result);
    }

    public function testSerializePassesThroughRawIntValueForStringEnum(): void
    {
        // Even though UserStatus is string-backed, raw int should pass through
        // (serialize is a passthrough for non-enum non-null values)
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', 42, []);

        self::assertSame(42, $result);
    }

    // -----------------------------------------------------------------------
    // Int-backed enum serialization
    // -----------------------------------------------------------------------

    public function testSerializeReturnsBackedValueForIntEnumInstance(): void
    {
        $cast = new EnumCast(IntBackedPriority::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'priority', IntBackedPriority::CRITICAL, []);

        self::assertSame(1, $result);
    }

    public function testSerializePassesThroughRawIntValueForIntEnum(): void
    {
        $cast = new EnumCast(IntBackedPriority::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'priority', 3, []);

        self::assertSame(3, $result);
    }

    // -----------------------------------------------------------------------
    // Null and edge cases
    // -----------------------------------------------------------------------

    public function testSerializeReturnsNullForNullValue(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', null, []);

        self::assertNull($result);
    }

    public function testSerializeReturnsNullForNonScalarNonEnumValue(): void
    {
        // serialize() only extracts ->value from BackedEnum instances.
        // For all other types (objects, arrays, booleans, floats), it falls through
        // the if/elseif chain and returns null at the end.
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', ['array' => 'value'], []);

        self::assertNull($result);
    }

    public function testSerializeReturnsNullForFloatValue(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', 3.14, []);

        self::assertNull($result);
    }

    public function testSerializeReturnsNullForBoolFalse(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', false, []);

        self::assertNull($result);
    }

    public function testSerializeReturnsNullForBoolTrue(): void
    {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', true, []);

        self::assertNull($result);
    }

    // -----------------------------------------------------------------------
    // Cross-enum type safety (serialize is per-instance, not per-class)
    // -----------------------------------------------------------------------

    public function testSerializeExtractsValueFromDifferentEnumClass(): void
    {
        // serialize() only checks if value is BackedEnum, not if it's the same class.
        // It blindly extracts ->value. This is by design (passthrough behavior).
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'status', IntBackedPriority::HIGH, []);

        self::assertSame(2, $result);
    }
}
