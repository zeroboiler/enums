<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

describe('PHPStan Level 9 — Structural Type Safety Contract', function () {
    // ─────────────────────────────────────────────────────────────
    // §1: All public methods have explicit return types (verified at source level).
    //     These tests exercise every public API to ensure return types match expectations.
    // ─────────────────────────────────────────────────────────────

    describe('HasEnumMetadata trait — return type compliance', function () {
        it('label() returns non-empty string', function () {
            $result = UserStatus::ACTIVE->label();

            expect($result)->toBeString();
            expect(strlen($result))->toBeGreaterThan(0);
        });

        it('description() returns string or null', function () {
            $withDesc = UserStatus::ACTIVE->description();
            $withoutDesc = UserStatus::INACTIVE->description();

            // At least one should be a string, null is valid for missing descriptions
            if ($withDesc !== null) {
                expect($withDesc)->toBeString();
            }
            expect($withoutDesc)->toBeNull();
        });

        it('color() returns non-empty string (never null)', function () {
            $result = UserStatus::ACTIVE->color();

            expect($result)->toBeString();
            expect(strlen($result))->toBeGreaterThan(0);
        });

        it('icon() returns string or null', function () {
            $result = UserStatus::ACTIVE->icon();

            expect($result === null || is_string($result))->toBeTrue();
        });

        it('forSelect() returns list with value+label keys', function () {
            $result = UserStatus::forSelect();

            expect($result)->toBeArray();
            expect($result)->not->toBeEmpty();

            foreach ($result as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }

            // Values should be unique
            $values = array_column($result, 'value');
            expect($values)->toEqual(array_unique($values));
        });

        it('forApi() returns list with full metadata keys', function () {
            $result = UserStatus::forApi();

            expect($result)->toBeArray();
            expect($result)->not->toBeEmpty();

            foreach ($result as $item) {
                expect($item)->toBeArray();
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('values() returns list of scalar values', function () {
            $stringValues = UserStatus::values();
            $intValues = IntBackedPriority::values();

            foreach ($stringValues as $v) {
                expect($v)->toBeString();
            }

            foreach ($intValues as $v) {
                expect($v)->toBeInt();
            }
        });

        it('labels() returns list of non-empty strings', function () {
            $result = UserStatus::labels();

            expect($result)->toBeArray();
            expect($result)->not->toBeEmpty();

            foreach ($result as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }
        });

        it('tryFromLabel() returns enum or null (never throws)', function () {
            $found = UserStatus::tryFromLabel('Active User');
            $notFound = UserStatus::tryFromLabel('nonexistent_xyz');

            expect($found)->toBeInstanceOf(UserStatus::class);
            expect($notFound)->toBeNull();
        });

        it('tryFromName() returns enum or null (never throws)', function () {
            $found = UserStatus::tryFromName('ACTIVE');
            $notFound = UserStatus::tryFromName('NONEXISTENT');

            expect($found)->toBeInstanceOf(UserStatus::class);
            expect($notFound)->toBeNull();
        });

        it('fromName() returns enum or throws InvalidEnumException', function () {
            $found = UserStatus::fromName('ACTIVE');

            expect($found)->toBeInstanceOf(UserStatus::class);
        });

        it('fromName() throws InvalidEnumException for invalid name', function () {
            expect(fn () => UserStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase() returns bool', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeBool()->toBeTrue();
            expect(UserStatus::hasCase('NONEXISTENT'))->toBeBool()->toBeFalse();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §2: Comparison methods accept union types (static|string) and return bool
    // ─────────────────────────────────────────────────────────────

    describe('Comparison methods — union type input handling', function () {
        it('is() accepts enum instance and returns bool', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
        });

        it('is() accepts string name and returns bool', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('BANNED'))->toBeFalse();
        });

        it('isNot() negates is() correctly', function () {
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
            expect(UserStatus::ACTIVE->isNot('BANNED'))->toBeTrue();
        });

        it('in() accepts mixed array of instances and strings', function () {
            $status = UserStatus::ACTIVE;

            // All instances
            expect($status->in([UserStatus::ACTIVE, UserStatus::BANNED]))->toBeTrue();
            expect($status->in([UserStatus::BANNED]))->toBeFalse();

            // All strings
            expect($status->in(['ACTIVE', 'BANNED']))->toBeTrue();
            expect($status->in(['BANNED']))->toBeFalse();

            // Mixed
            expect($status->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
            expect($status->in(['BANNED', 'PENDING']))->toBeFalse();
        });

        it('notIn() negates in() correctly', function () {
            expect(UserStatus::ACTIVE->notIn(['BANNED']))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn(['ACTIVE']))->toBeFalse();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §3: Pure enum support — no backed value, name used as key
    // ─────────────────────────────────────────────────────────────

    describe('Pure enum type safety', function () {
        it('pure enum label() returns auto-generated string', function () {
            expect(PureFeatureFlag::SEARCH->label())->toBeString()->not->toBeEmpty();
        });

        it('pure enum color() returns non-empty string', function () {
            expect(PureFeatureFlag::SEARCH->color())->toBeString()->not->toBeEmpty();
        });

        it('pure enum values() returns case names (not backed values)', function () {
            $values = PureFeatureFlag::values();

            foreach ($values as $v) {
                expect($v)->toBeString();
                // Should match a case name, not a numeric index
                expect(PureFeatureFlag::tryFromName($v))->not->toBeNull();
            }
        });

        it('pure enum forSelect() uses case name as value', function () {
            $select = PureFeatureFlag::forSelect();

            foreach ($select as $option) {
                expect($option['value'])->toBeString();
                expect(PureFeatureFlag::tryFromName($option['value']))->not->toBeNull();
            }
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §4: Integer-backed enum — strict type handling
    // ─────────────────────────────────────────────────────────────

    describe('Int-backed enum type safety', function () {
        it('values() returns int array', function () {
            $values = IntBackedPriority::values();

            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('forSelect() uses int as value', function () {
            $select = IntBackedPriority::forSelect();

            foreach ($select as $option) {
                expect($option['value'])->toBeInt();
            }
        });

        it('tryFromName works with int-backed enums', function () {
            $found = IntBackedPriority::tryFromName('LOW');

            expect($found)->toBeInstanceOf(IntBackedPriority::class);
            expect($found->value)->toBeInt();
        });

        it('zero-backed enum works correctly', function () {
            $zero = ZeroBackedPriority::NONE;

            expect($zero->value)->toBe(0);
            expect($zero->label())->toBeString()->not->toBeEmpty();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §5: EnumCache singleton — lifecycle and TTL safety
    // ─────────────────────────────────────────────────────────────

    describe('EnumCache — singleton lifecycle', function () {
        it('getInstance() returns same instance', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('setTtl() clamps negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);

            expect($cache->getTtl())->toBe(0);

            // Reset to default for other tests
            $cache->setTtl(300);
        });

        it('has() returns false when TTL is 0', function () {
            $cache = EnumCache::getInstance();
            $originalTtl = $cache->getTtl();

            $cache->setTtl(0);
            expect($cache->has('NonExistentEnum'))->toBeFalse();

            $cache->setTtl($originalTtl);
        });

        it('clear() removes all entries', function () {
            $cache = EnumCache::getInstance();
            $cache->set('TestClass', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestClass'))->toBeTrue();
            $cache->clear();
            expect($cache->has('TestClass'))->toBeFalse();
        });

        it('clearClass() removes only specified class', function () {
            $cache = EnumCache::getInstance();
            $cache->set('ClassA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('ClassB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $cache->clearClass('ClassA');

            expect($cache->has('ClassA'))->toBeFalse();
            expect($cache->has('ClassB'))->toBeTrue();

            $cache->clear();
        });

        it('get() throws OutOfBoundsException for missing entry', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            expect(fn () => $cache->get('NonExistent'))
                ->toThrow(\OutOfBoundsException::class);
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §6: EnumRule — strict type validation for backed + pure enums
    // ─────────────────────────────────────────────────────────────

    describe('EnumRule — strict type validation', function () {
        it('rejects null for non-nullable string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', null, function (string $message) use (&$failed): void {
                $failed = true;
                expect($message)->toBeString()->not->toBeEmpty();
            });

            expect($failed)->toBeTrue();
        });

        it('accepts null for nullable string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('accepts valid string value for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', 'active', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('rejects invalid string value for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', 'nonexistent', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('rejects int value for string-backed enum (strict type check)', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', 42, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('accepts valid int value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;

            $rule->validate('priority', 1, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('rejects string value for int-backed enum (strict type check)', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;

            $rule->validate('priority', 'high', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('accepts valid case name for pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('feature', 'SEARCH', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('rejects int for pure enum (only case names allowed)', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('feature', 42, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §7: InvalidEnumException — named constructor types
    // ─────────────────────────────────────────────────────────────

    describe('InvalidEnumException — factory methods', function () {
        it('value() creates exception with string value', function () {
            $ex = InvalidEnumException::value(UserStatus::class, 'bad_value');

            expect($ex)->toBeInstanceOf(InvalidEnumException::class);
            expect($ex->getMessage())->toContain('bad_value');
            expect($ex->getMessage())->toContain(UserStatus::class);
        });

        it('value() creates exception with null value', function () {
            $ex = InvalidEnumException::value(UserStatus::class, null);

            expect($ex->getMessage())->toContain('null');
        });

        it('value() creates exception with int value', function () {
            $ex = InvalidEnumException::value(IntBackedPriority::class, 999);

            expect($ex->getMessage())->toContain('999');
        });

        it('forName() creates exception with case name', function () {
            $ex = InvalidEnumException::forName(UserStatus::class, 'BAD_CASE');

            expect($ex->getMessage())->toContain('BAD_CASE');
            expect($ex->getMessage())->toContain('Case name');
        });

        it('__toString() returns class name + message', function () {
            $ex = InvalidEnumException::value(UserStatus::class, 'test');

            $str = (string) $ex;

            expect($str)->toBeString();
            expect($str)->toContain(InvalidEnumException::class);
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §8: Enum facade delegates to EnumManager correctly
    // ─────────────────────────────────────────────────────────────

    describe('Enum facade — delegation contract', function () {
        it('forSelect() returns same result as trait method', function () {
            $facadeResult = Enum::forSelect(UserStatus::class);
            $traitResult = UserStatus::forSelect();

            expect($facadeResult)->toBe($traitResult);
        });

        it('forApi() returns same result as trait method', function () {
            $facadeResult = Enum::forApi(UserStatus::class);
            $traitResult = UserStatus::forApi();

            expect($facadeResult)->toBe($traitResult);
        });

        it('tryFromLabel() works via facade', function () {
            $result = Enum::tryFromLabel(UserStatus::class, 'Active User');

            expect($result)->toBeInstanceOf(UserStatus::class);
        });

        it('tryFromName() works via facade', function () {
            $result = Enum::tryFromName(UserStatus::class, 'ACTIVE');

            expect($result)->toBeInstanceOf(UserStatus::class);
        });

        it('hasCase() works via facade', function () {
            expect(Enum::hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
            expect(Enum::hasCase(UserStatus::class, 'NONEXISTENT'))->toBeFalse();
        });

        it('values() works via facade', function () {
            $values = Enum::values(UserStatus::class);

            expect($values)->toBeArray()->not->toBeEmpty();
            expect($values)->toBe(UserStatus::values());
        });

        it('labels() works via facade', function () {
            $labels = Enum::labels(UserStatus::class);

            expect($labels)->toBeArray()->not->toBeEmpty();
            expect($labels)->toBe(UserStatus::labels());
        });

        it('throws BadMethodCallException for enum without trait', function () {
            expect(fn () => Enum::forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });
    });
});
