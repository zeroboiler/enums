<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * EnumCast serialization and Eloquent cast contract tests.
 *
 * Tests the EnumCast class's serialize() method, type normalization behavior,
 * and contract compliance with Laravel's CastsAttributes interface.
 *
 * @see \ZeroBoiler\Enums\Casts\EnumCast
 * @see \ZeroBoiler\Enums\Casts\EnumCast::serialize()
 * @see \ZeroBoiler\Enums\Casts\EnumCast::get()
 * @see \ZeroBoiler\Enums\Casts\EnumCast::set()
 */

use ReflectionClass;
use ReflectionMethod;
use ZeroBoiler\Enums\Casts\EnumCast;

// ── Test Enums ─────────────────────────────────────────────────

enum CastIntStatus: int
{
    case Active = 1;
    case Inactive = 0;
    case Pending = 2;
}

enum CastStringRole: string
{
    case Admin = 'admin';
    case User = 'user';
    case Guest = 'guest';
}

// ── Tests ─────────────────────────────────────────────────────

describe('EnumCast — Structural Contract', function () {
    it('is a final class', function () {
        $ref = new ReflectionClass(EnumCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('implements CastsAttributes interface', function () {
        $ref = new ReflectionClass(EnumCast::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))->toBeTrue();
    });

    it('accepts enum class in constructor', function () {
        $cast = new EnumCast(CastIntStatus::class);
        expect($cast)->toBeInstanceOf(EnumCast::class);
    });

    it('has readonly enumClass property', function () {
        $ref = new ReflectionProperty(EnumCast::class, 'enumClass');
        expect($ref->isReadOnly())->toBeTrue();
        expect($ref->isPrivate())->toBeTrue();
    });
});

describe('EnumCast — get() method', function () {
    it('returns null for null values', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};
        $result = $cast->get($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('returns enum instance for valid int backed value', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};
        $result = $cast->get($model, 'status', 1, []);
        expect($result)->toBe(CastIntStatus::Active);
    });

    it('returns enum instance for valid string backed value', function () {
        $cast = new EnumCast(CastStringRole::class);
        $model = new class {};
        $result = $cast->get($model, 'role', 'admin', []);
        expect($result)->toBe(CastStringRole::Admin);
    });

    it('returns null for invalid int value', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};
        // Value 99 doesn't match any case → tryFrom returns null
        $result = $cast->get($model, 'status', 99, []);
        expect($result)->toBeNull();
    });

    it('returns null for invalid string value', function () {
        $cast = new EnumCast(CastStringRole::class);
        $model = new class {};
        $result = $cast->get($model, 'role', 'nonexistent', []);
        expect($result)->toBeNull();
    });

    it('handles numeric string input by returning null (type mismatch)', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};
        // String '1' is a string, not an int — should return null
        $result = $cast->get($model, 'status', '1', []);
        expect($result)->toBeNull();
    });

    it('has get() with correct return type declaration', function () {
        $ref = new ReflectionMethod(EnumCast::class, 'get');
        $returnType = $ref->getReturnType();
        expect($returnType)->not->toBeNull();
        expect($returnType->getName())->toBe('?BackedEnum');
    });
});

describe('EnumCast — set() method', function () {
    it('returns null for null values', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};
        $result = $cast->set($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('returns backed value for enum instance', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};
        $result = $cast->set($model, 'status', CastIntStatus::Active, []);
        expect($result)->toBe(1);
    });

    it('returns string backed value for string enum instance', function () {
        $cast = new EnumCast(CastStringRole::class);
        $model = new class {};
        $result = $cast->set($model, 'role', CastStringRole::Admin, []);
        expect($result)->toBe('admin');
    });

    it('returns raw int value for valid int', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};
        $result = $cast->set($model, 'status', 2, []);
        expect($result)->toBe(2);
    });

    it('returns raw string value for valid string', function () {
        $cast = new EnumCast(CastStringRole::class);
        $model = new class {};
        $result = $cast->set($model, 'role', 'user', []);
        expect($result)->toBe('user');
    });

    it('throws InvalidArgumentException for wrong enum class', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};

        expect(fn () => $cast->set($model, 'status', CastStringRole::Admin, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('throws InvalidArgumentException for invalid int value', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};

        expect(fn () => $cast->set($model, 'status', 999, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('has set() with correct return type declaration', function () {
        $ref = new ReflectionMethod(EnumCast::class, 'set');
        $returnType = $ref->getReturnType();
        expect($returnType)->not->toBeNull();
        // Return type is int|string|null
        expect($returnType->allowsNull())->toBeTrue();
    });
});

describe('EnumCast — serialize() method', function () {
    it('returns backed value for enum instance', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};
        $result = $cast->serialize($model, 'status', CastIntStatus::Active, []);
        expect($result)->toBe(1);
    });

    it('returns string backed value for string enum instance', function () {
        $cast = new EnumCast(CastStringRole::class);
        $model = new class {};
        $result = $cast->serialize($model, 'role', CastStringRole::User, []);
        expect($result)->toBe('user');
    });

    it('passes through raw int values', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};
        $result = $cast->serialize($model, 'status', 2, []);
        expect($result)->toBe(2);
    });

    it('passes through raw string values', function () {
        $cast = new EnumCast(CastStringRole::class);
        $model = new class {};
        $result = $cast->serialize($model, 'role', 'guest', []);
        expect($result)->toBe('guest');
    });

    it('returns null for null input', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};
        $result = $cast->serialize($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('returns null for unsupported types (float, bool, array)', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};

        expect($cast->serialize($model, 'status', 3.14, []))->toBeNull();
        expect($cast->serialize($model, 'status', true, []))->toBeNull();
        expect($cast->serialize($model, 'status', ['active'], []))->toBeNull();
    });

    it('has serialize() with correct return type declaration', function () {
        $ref = new ReflectionMethod(EnumCast::class, 'serialize');
        $returnType = $ref->getReturnType();
        expect($returnType)->not->toBeNull();
        expect($returnType->allowsNull())->toBeTrue();
    });
});

describe('EnumCast — Roundtrip Integrity', function () {
    it('roundtrips int enum through get/set', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};

        // set: enum → int
        $stored = $cast->set($model, 'status', CastIntStatus::Pending, []);
        expect($stored)->toBe(2);

        // get: int → enum
        $restored = $cast->get($model, 'status', 2, []);
        expect($restored)->toBe(CastIntStatus::Pending);
    });

    it('roundtrips string enum through get/set', function () {
        $cast = new EnumCast(CastStringRole::class);
        $model = new class {};

        $stored = $cast->set($model, 'role', CastStringRole::Guest, []);
        expect($stored)->toBe('guest');

        $restored = $cast->get($model, 'role', 'guest', []);
        expect($restored)->toBe(CastStringRole::Guest);
    });

    it('roundtrips through serialize/get for int enum', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};

        // serialize: enum → int
        $serialized = $cast->serialize($model, 'status', CastIntStatus::Active, []);
        expect($serialized)->toBe(1);

        // get: int → enum
        $restored = $cast->get($model, 'status', 1, []);
        expect($restored)->toBe(CastIntStatus::Active);
    });

    it('preserves all enum cases through full roundtrip', function () {
        $cast = new EnumCast(CastIntStatus::class);
        $model = new class {};

        foreach (CastIntStatus::cases() as $case) {
            $stored = $cast->set($model, 'status', $case, []);
            $restored = $cast->get($model, 'status', $stored, []);
            expect($restored)->toBe($case, "Failed roundtrip for {$case->name}");
        }
    });
});

describe('EnumCast — PHPStan Level 9 Type Safety', function () {
    it('has strict types declaration', function () {
        $ref = new ReflectionClass(EnumCast::class);
        $file = $ref->getFileName();
        $contents = file_get_contents($file);
        expect($contents)->toContain('declare(strict_types=1)');
    });

    it('all public methods have return type declarations', function () {
        $ref = new ReflectionClass(EnumCast::class);
        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->getName() === '__construct') {
                continue;
            }
            expect($method->getReturnType(), "Method {$method->getName()} missing return type")
                ->not->toBeNull();
        }
    });

    it('get() parameter types are properly declared', function () {
        $ref = new ReflectionMethod(EnumCast::class, 'get');
        $params = $ref->getParameters();

        expect($params[0]->getName())->toBe('model');
        expect($params[0]->getType()->getName())->toBe('object');

        expect($params[1]->getName())->toBe('key');
        expect($params[1]->getType()->getName())->toBe('string');

        // Third param: int|string|null
        expect($params[2]->getName())->toBe('value');
        expect($params[2]->getType()->allowsNull())->toBeTrue();

        // Fourth param: array
        expect($params[3]->getName())->toBe('attributes');
        expect($params[3]->getType()->getName())->toBe('array');
    });

    it('set() parameter types are properly declared', function () {
        $ref = new ReflectionMethod(EnumCast::class, 'set');
        $params = $ref->getParameters();

        expect($params[2]->getName())->toBe('value');
        expect($params[2]->getType()->allowsNull())->toBeTrue();
    });

    it('serialize() parameter types are properly declared', function () {
        $ref = new ReflectionMethod(EnumCast::class, 'serialize');
        $params = $ref->getParameters();

        expect($params[2]->getName())->toBe('value');
        expect($params[2]->getType()->allowsNull())->toBeTrue();
    });
});
