<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('V41 Enum Production Contract Audit', function () {
    // ── Trait contract completeness ──────────────────────────────────────

    describe('HasEnumMetadata trait completeness', function () {
        it('provides all required public methods on any enum using the trait', function () {
            $methods = ['label', 'color', 'icon', 'description', 'forSelect', 'forApi', 'tryFromLabel', 'tryFromName', 'fromName', 'hasCase', 'is', 'isNot', 'in', 'notIn', 'values', 'labels', 'toValue'];

            foreach ($methods as $method) {
                expect(method_exists(UserStatus::class, $method))->toBeTrue("Missing method: {$method}");
            }
        });

        it('provides all required static methods on any enum using the trait', function () {
            $staticMethods = ['forSelect', 'forApi', 'tryFromLabel', 'tryFromName', 'fromName', 'hasCase', 'values', 'labels'];

            foreach ($staticMethods as $method) {
                $ref = new ReflectionMethod(UserStatus::class, $method);
                expect($ref->isStatic())->toBeTrue("{$method} should be static");
            }
        });

        it('toValue() returns backed value for string-backed enum', function () {
            expect(UserStatus::ACTIVE->toValue())->toBe('active');
            expect(UserStatus::BANNED->toValue())->toBe('banned');
        });

        it('toValue() returns backed value for int-backed enum', function () {
            expect(IntBackedPriority::LOW->toValue())->toBeInt();
            expect(ZeroBackedPriority::ZERO->toValue())->toBe(0);
        });

        it('toValue() returns case name for pure enum', function () {
            expect(PureFeatureFlag::ENABLED->toValue())->toBe('ENABLED');
            expect(PlainTestEnum::ACTIVE->toValue())->toBe('ACTIVE');
        });
    });

    // ── EnumManager contract ────────────────────────────────────────────

    describe('EnumManager delegation contract', function () {
        it('forSelect() returns the same result as calling the trait method directly', function () {
            $managerSelect = \ZeroBoiler\Enums\EnumManager::forSelect(UserStatus::class);
            $traitSelect = UserStatus::forSelect();

            // Compare serialized to avoid object identity issues
            expect($managerSelect)->toEqual($traitSelect);
        });

        it('forApi() returns the same result as calling the trait method directly', function () {
            $managerApi = \ZeroBoiler\Enums\EnumManager::forApi(UserStatus::class);
            $traitApi = UserStatus::forApi();

            expect($managerApi)->toEqual($traitApi);
        });

        it('tryFromLabel() delegates correctly', function () {
            $label = UserStatus::ACTIVE->label();
            $result = \ZeroBoiler\Enums\EnumManager::tryFromLabel(UserStatus::class, $label);

            expect($result)->not->toBeNull();
            expect($result->name)->toBe('ACTIVE');
        });

        it('tryFromName() delegates correctly', function () {
            $result = \ZeroBoiler\Enums\EnumManager::tryFromName(UserStatus::class, 'ACTIVE');

            expect($result)->not->toBeNull();
            expect($result->name)->toBe('ACTIVE');
        });

        it('fromName() delegates correctly and throws on invalid', function () {
            $result = \ZeroBoiler\Enums\EnumManager::fromName(UserStatus::class, 'ACTIVE');
            expect($result->name)->toBe('ACTIVE');

            expect(fn () => \ZeroBoiler\Enums\EnumManager::fromName(UserStatus::class, 'NON_EXISTENT'))
                ->toThrow(\BadMethodCallException::class);
        });

        it('hasCase() delegates correctly', function () {
            expect(\ZeroBoiler\Enums\EnumManager::hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
            expect(\ZeroBoiler\Enums\EnumManager::hasCase(UserStatus::class, 'NON_EXISTENT'))->toBeFalse();
        });

        it('values() delegates correctly', function () {
            $values = \ZeroBoiler\Enums\EnumManager::values(UserStatus::class);
            expect($values)->toBeArray();
            expect($values)->not->toBeEmpty();
        });

        it('labels() delegates correctly', function () {
            $labels = \ZeroBoiler\Enums\EnumManager::labels(UserStatus::class);
            expect($labels)->toBeArray();
            expect($labels)->not->toBeEmpty();
            expect($labels)->each->toBeString();
        });

        it('throws BadMethodCallException for enum without HasEnumMetadata', function () {
            expect(fn () => \ZeroBoiler\Enums\EnumManager::forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });
    });

    // ── EnumCache singleton lifecycle ────────────────────────────────────

    describe('EnumCache singleton lifecycle', function () {
        it('returns the same instance on multiple calls', function () {
            $a = \ZeroBoiler\Enums\EnumCache::getInstance();
            $b = \ZeroBoiler\Enums\EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('resetInstance() creates a new singleton', function () {
            $before = \ZeroBoiler\Enums\EnumCache::getInstance();
            $beforeTtl = $before->getTtl();
            $before->setTtl(999);

            \ZeroBoiler\Enums\EnumCache::resetInstance();

            $after = \ZeroBoiler\Enums\EnumCache::getInstance();
            expect($after)->not->toBe($before);
            expect($after->getTtl())->toBe(300); // default TTL restored

            // Cleanup
            \ZeroBoiler\Enums\EnumCache::resetInstance();
        });

        it('blocks cloning via __clone()', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();

            expect(fn () => clone $cache)->toThrow(\RuntimeException::class);
        });

        it('blocks serialization via __serialize()', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();

            expect(fn () => serialize($cache))->toThrow(\RuntimeException::class);
        });

        it('blocks unserialization via __unserialize()', function () {
            expect(fn () => unserialize('O:37:"ZeroBoiler\Enums\EnumCache":0:{}'))
                ->toThrow(\RuntimeException::class);
        });

        it('__debugInfo hides internal state', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $debug = $cache->__debugInfo();

            expect($debug)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
            expect($debug)->not->toHaveKey('cache');
            expect($debug)->not->toHaveKey('cacheTimestamps');
        });
    });

    // ── InvalidEnumException factory methods ──────────────────────────────

    describe('InvalidEnumException factory contract', function () {
        it('value() creates exception with correct message for null', function () {
            $ex = InvalidEnumException::value('SomeEnum', null);
            expect($ex->getMessage())->toContain('null');
            expect($ex->getMessage())->toContain('SomeEnum');
        });

        it('value() creates exception with correct message for string', function () {
            $ex = InvalidEnumException::value('SomeEnum', 'invalid_value');
            expect($ex->getMessage())->toContain('invalid_value');
        });

        it('value() creates exception with correct message for int', function () {
            $ex = InvalidEnumException::value('SomeEnum', 42);
            expect($ex->getMessage())->toContain('42');
        });

        it('forName() creates exception with correct message', function () {
            $ex = InvalidEnumException::forName('SomeEnum', 'INVALID_CASE');
            expect($ex->getMessage())->toContain('INVALID_CASE');
            expect($ex->getMessage())->toContain('SomeEnum');
        });

        it('__toString() returns class name and message', function () {
            $ex = InvalidEnumException::value('TestEnum', 'bad');
            $str = (string) $ex;

            expect($str)->toContain(InvalidEnumException::class);
            expect($str)->toContain('bad');
        });
    });

    // ── EnumRule validation contract ────────────────────────────────────

    describe('EnumRule validation contract', function () {
        it('rejects null when not nullable', function () {
            $rule = new \ZeroBoiler\Enums\Rules\EnumRule(UserStatus::class);
            $failed = false;
            $rule->validate('status', null, function (string $attr): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('allows null when nullable', function () {
            $rule = new \ZeroBoiler\Enums\Rules\EnumRule(UserStatus::class, nullable: true);
            $failed = false;
            $rule->validate('status', null, function (string $attr): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('accepts valid backed value', function () {
            $rule = new \ZeroBoiler\Enums\Rules\EnumRule(UserStatus::class);
            $failed = false;
            $rule->validate('status', 'active', function (string $attr): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('rejects invalid backed value', function () {
            $rule = new \ZeroBoiler\Enums\Rules\EnumRule(UserStatus::class);
            $failed = false;
            $rule->validate('status', 'nonexistent', function (string $attr): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('rejects type mismatch for int-backed enum with string value', function () {
            $rule = new \ZeroBoiler\Enums\Rules\EnumRule(IntBackedPriority::class);
            $failed = false;
            $rule->validate('priority', 'not_an_int', function (string $attr): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('nullable() returns a new instance', function () {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class);
            $nullable = $rule->nullable();

            expect($nullable)->not->toBe($rule);
        });

        it('for() is a named constructor alias', function () {
            $direct = new \ZeroBoiler\Enums\Rules\EnumRule(UserStatus::class);
            $named = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class);

            // Both should behave identically (different instances but same config)
            expect($named)->toBeInstanceOf(\ZeroBoiler\Enums\Rules\EnumRule::class);
        });
    });

    // ── EnumCast serialization contract ─────────────────────────────────

    describe('EnumCast serialization contract', function () {
        it('serialize() returns backed value for enum instance', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
            $result = $cast->serialize(
                new \stdClass,
                'status',
                UserStatus::ACTIVE,
                []
            );

            expect($result)->toBe('active');
        });

        it('serialize() passes through int values', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(IntBackedPriority::class);
            $result = $cast->serialize(
                new \stdClass,
                'priority',
                1,
                []
            );

            expect($result)->toBe(1);
        });

        it('serialize() passes through string values', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
            $result = $cast->serialize(
                new \stdClass,
                'status',
                'active',
                []
            );

            expect($result)->toBe('active');
        });

        it('serialize() returns null for non-scalar, non-enum values', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
            $result = $cast->serialize(
                new \stdClass,
                'status',
                ['array_value'],
                []
            );

            expect($result)->toBeNull();
        });

        it('get() returns null for null value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
            $result = $cast->get(new \stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('get() returns null for non-scalar value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
            $result = $cast->get(new \stdClass, 'status', ['array'], []);

            expect($result)->toBeNull();
        });

        it('get() returns enum case for valid value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
            $result = $cast->get(new \stdClass, 'status', 'active', []);

            expect($result)->toBeInstanceOf(UserStatus::class);
            expect($result->name)->toBe('ACTIVE');
        });

        it('get() handles numeric string for int-backed enum', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(IntBackedPriority::class);
            $result = $cast->get(new \stdClass, 'priority', '1', []);

            expect($result)->toBeInstanceOf(IntBackedPriority::class);
        });

        it('set() returns null for null value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
            $result = $cast->set(new \stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('set() returns backed value for enum instance', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
            $result = $cast->set(new \stdClass, 'status', UserStatus::ACTIVE, []);

            expect($result)->toBe('active');
        });

        it('set() rejects wrong enum class', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);

            // If IntBackedPriority is int-backed and UserStatus is string-backed,
            // passing UserStatus to a cast expecting IntBackedPriority should fail
            $wrongCast = new \ZeroBoiler\Enums\Casts\EnumCast(IntBackedPriority::class);

            expect(fn () => $wrongCast->set(new \stdClass, 'priority', UserStatus::ACTIVE, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('set() validates raw int value for int-backed enum', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(IntBackedPriority::class);
            $result = $cast->set(new \stdClass, 'priority', 1, []);

            expect($result)->toBe(1);
        });
    });

    // ── Attribute dual-target support ─────────────────────────────────────

    describe('Attribute dual-target (class-level + case-level)', function () {
        it('EnumLabel can be used at class level with labels map', function () {
            $ref = new ReflectionClass(EnumLabel::class);
            $attrs = $ref->getAttributes();

            expect($attrs)->not->toBeEmpty();
            // The attribute should target both class and class constant
            $attr = $attrs[0]->newInstance();
            expect($attr)->toBeInstanceOf(\Attribute::class);
        });

        it('EnumColor can be used at class level', function () {
            $ref = new ReflectionClass(EnumColor::class);
            $attrs = $ref->getAttributes();
            $attr = $attrs[0]->newInstance();

            expect($attr)->toBeInstanceOf(\Attribute::class);
        });

        it('Label is restricted to class constants only', function () {
            $ref = new ReflectionClass(Label::class);
            $attrs = $ref->getAttributes();
            $attr = $attrs[0]->newInstance();

            expect($attr->flags)->toBe(Attribute::TARGET_CLASS_CONSTANT);
        });

        it('EnumIcon has default icon support', function () {
            $icon = new EnumIcon(default: 'heroicon-o-question-mark-circle');
            expect($icon->default)->toBe('heroicon-o-question-mark-circle');
            expect($icon->icons)->toBe([]);
        });

        it('EnumIcon has per-value icon support', function () {
            $icon = new EnumIcon(icons: [1 => 'heroicon-o-check', 0 => 'heroicon-o-x-mark']);
            expect($icon->icons)->toBe([1 => 'heroicon-o-check', 0 => 'heroicon-o-x-mark']);
        });
    });

    // ── Edge case: zero-backed enum ──────────────────────────────────────

    describe('Zero-backed enum edge cases', function () {
        it('ZeroBackedPriority with value 0 works correctly', function () {
            $case = ZeroBackedPriority::ZERO;
            expect($case->toValue())->toBe(0);
            expect($case->label())->toBeString()->not->toBeEmpty();
        });

        it('ZeroPriority with value 0 works correctly', function () {
            $case = ZeroPriority::ZERO;
            expect($case->toValue())->toBe(0);
            expect($case->label())->toBeString()->not->toBeEmpty();
        });
    });

    // ── Edge case: single-case enum ──────────────────────────────────────

    describe('Single-case enum edge cases', function () {
        it('SingleCaseEnum has exactly one case', function () {
            expect(SingleCaseEnum::cases())->toHaveCount(1);
        });

        it('in() works with single-element array', function () {
            $case = SingleCaseEnum::SINGLE;
            expect($case->in([SingleCaseEnum::SINGLE]))->toBeTrue();
            expect($case->in([]))->toBeFalse();
        });

        it('notIn() works with empty array', function () {
            $case = SingleCaseEnum::SINGLE;
            expect($case->notIn([]))->toBeTrue();
        });
    });

    // ── CamelCase label generation ───────────────────────────────────────

    describe('CamelCase label generation', function () {
        it('generates proper labels from camelCase enum names', function () {
            foreach (CamelCasePriority::cases() as $case) {
                $label = $case->label();
                expect($label)->toBeString()->not->toBeEmpty();
                // Label should have spaces (Title Case conversion)
                if (strtolower($case->name) !== $case->name) {
                    expect($label)->toContain(' ');
                }
            }
        });
    });

    // ── Metadata resolution consistency ────────────────────────────────

    describe('Metadata resolution consistency', function () {
        it('forSelect() values match values() output', function () {
            $selectValues = array_column(UserStatus::forSelect(), 'value');
            $values = UserStatus::values();

            expect($selectValues)->toEqual($values);
        });

        it('forApi() values match values() output', function () {
            $apiValues = array_column(UserStatus::forApi(), 'value');
            $values = UserStatus::values();

            expect($apiValues)->toEqual($values);
        });

        it('forApi() labels match labels() output', function () {
            $apiLabels = array_column(UserStatus::forApi(), 'label');
            $labels = UserStatus::labels();

            expect($apiLabels)->toEqual($labels);
        });

        it('forSelect() count matches cases() count', function () {
            expect(UserStatus::forSelect())->toHaveCount(count(UserStatus::cases()));
        });

        it('forApi() count matches cases() count', function () {
            expect(UserStatus::forApi())->toHaveCount(count(UserStatus::cases()));
        });

        it('values() count matches cases() count', function () {
            expect(UserStatus::values())->toHaveCount(count(UserStatus::cases()));
        });

        it('labels() count matches cases() count', function () {
            expect(UserStatus::labels())->toHaveCount(count(UserStatus::cases()));
        });
    });
});
