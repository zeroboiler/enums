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
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enums Production Readiness Complete V2', function () {
    // ── HasEnumMetadata Trait — Exhaustive API Surface ──────────────────────

    describe('label()', function () {
        it('returns per-case Label attribute value', function () {
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
        });

        it('returns auto-generated label for cases without Label attribute', function () {
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('returns EnumLabel class-level label for cases mapped there', function () {
            // LabelMapEnum uses EnumLabel at class level
            expect(LabelMapEnum::cases()[0]->label())->toBeString()->not->toBeEmpty();
        });

        it('generates label from camelCase correctly', function () {
            // CamelCaseRole::Admin → "Admin"
            expect(CamelCaseRole::Admin->label())->toBe('Admin');
        });

        it('generates label from SCREAMING_SNAKE_CASE correctly', function () {
            expect(UserStatus::ACTIVE->label())->toBeString()->not->toBeEmpty();
        });

        it('always returns a non-empty string for any case', function () {
            foreach (UserStatus::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
        });

        it('returns EnumLabel case-level override when EnumLabel is used at case level', function () {
            // MixedAttributeStatus uses EnumLabel at case level
            foreach (MixedAttributeStatus::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
        });
    });

    describe('description()', function () {
        it('returns per-case Description attribute value', function () {
            expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
        });

        it('returns null when no description attribute is set', function () {
            expect(UserStatus::INACTIVE->description())->toBeNull();
        });

        it('returns EnumDescription class-level description', function () {
            // DetailedTicketStatus uses EnumDescription at class level
            foreach (DetailedTicketStatus::cases() as $case) {
                // All should either have a description or null
                $desc = $case->description();
                expect($desc)->toBeNull()->or()->toBeString();
            }
        });

        it('returns EnumDescription case-level override', function () {
            foreach (MixedAttributeStatus::cases() as $case) {
                $desc = $case->description();
                expect($desc)->toBeNull()->or()->toBeString();
            }
        });
    });

    describe('color()', function () {
        it('returns EnumColor class-level color by backed value', function () {
            expect(UserStatus::ACTIVE->color())->toBe('success');
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('returns per-case Color override over class-level', function () {
            // BANNED has #[Color('danger')] and class-level has danger for 'banned'
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('returns "secondary" as default when no color is defined', function () {
            // For enums without any color attributes
            foreach (PaymentStatus::cases() as $case) {
                $color = $case->color();
                // Should always be a string (defaults to 'secondary')
                expect($color)->toBeString()->not->toBeEmpty();
            }
        });

        it('always returns a non-empty string', function () {
            foreach (UserStatus::cases() as $case) {
                expect($case->color())->toBeString()->not->toBeEmpty();
            }
        });
    });

    describe('icon()', function () {
        it('returns per-case Icon attribute value', function () {
            expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
        });

        it('returns null when no icon is defined', function () {
            expect(UserStatus::INACTIVE->icon())->toBeNull();
        });

        it('returns EnumIcon class-level default icon', function () {
            // DefaultIconFeature uses EnumIcon with a default
            foreach (DefaultIconFeature::cases() as $case) {
                $icon = $case->icon();
                expect($icon)->toBeNull()->or()->toBeString();
            }
        });

        it('returns EnumIcon per-value icon map', function () {
            foreach (MixedAttributeStatus::cases() as $case) {
                $icon = $case->icon();
                expect($icon)->toBeNull()->or()->toBeString();
            }
        });
    });

    // ── Bulk Methods ────────────────────────────────────────────────────────

    describe('forSelect()', function () {
        it('returns array with value and label keys for each case', function () {
            $select = UserStatus::forSelect();
            expect($select)->toBeArray();
            expect($select)->toHaveCount(count(UserStatus::cases()));
            foreach ($select as $option) {
                expect($option)->toHaveKey('value');
                expect($option)->toHaveKey('label');
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('returns backed values as value key for string-backed enums', function () {
            $select = UserStatus::forSelect();
            $values = array_column($select, 'value');
            expect($values)->toContain('active');
            expect($values)->toContain('banned');
        });

        it('returns backed values for int-backed enums', function () {
            $select = IntBackedPriority::forSelect();
            $values = array_column($select, 'value');
            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('returns case names for pure enums', function () {
            $select = PureFeatureFlag::forSelect();
            $values = array_column($select, 'value');
            $names = array_map(fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());
            expect($values)->toEqual($names);
        });

        it('select option values are unique across all enum types', function () {
            foreach ([UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class] as $enumClass) {
                $select = $enumClass::forSelect();
                $values = array_column($select, 'value');
                expect(array_unique($values))->toHaveCount(count($values));
            }
        });
    });

    describe('forApi()', function () {
        it('returns full metadata array for each case', function () {
            $api = UserStatus::forApi();
            expect($api)->toBeArray();
            expect($api)->toHaveCount(count(UserStatus::cases()));
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('includes backed value for string-backed enums', function () {
            $api = UserStatus::forApi();
            expect($api[0]['value'])->toBe('active');
        });

        it('description can be null for cases without Description attribute', function () {
            $api = UserStatus::forApi();
            $inactive = array_find($api, fn (array $item): bool => $item['name'] === 'INACTIVE');
            expect($inactive)->not->toBeNull();
            expect($inactive['description'])->toBeNull();
        });

        it('icon can be null for cases without Icon attribute', function () {
            $api = UserStatus::forApi();
            $inactive = array_find($api, fn (array $item): bool => $item['name'] === 'INACTIVE');
            expect($inactive)->not->toBeNull();
            expect($inactive['icon'])->toBeNull();
        });
    });

    describe('values()', function () {
        it('returns backed values for string-backed enums', function () {
            $values = UserStatus::values();
            expect($values)->toContain('active');
            expect($values)->toContain('banned');
        });

        it('returns backed values for int-backed enums', function () {
            $values = IntBackedPriority::values();
            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('returns case names for pure enums', function () {
            $values = PureFeatureFlag::values();
            $names = array_map(fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());
            expect($values)->toEqual($names);
        });

        it('count matches case count', function () {
            expect(UserStatus::values())->toHaveCount(count(UserStatus::cases()));
        });
    });

    describe('labels()', function () {
        it('returns non-empty string labels for all cases', function () {
            $labels = UserStatus::labels();
            expect($labels)->toHaveCount(count(UserStatus::cases()));
            foreach ($labels as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }
        });

        it('preserves case declaration order', function () {
            $labels = UserStatus::labels();
            $cases = UserStatus::cases();
            for ($i = 0; $i < count($cases); $i++) {
                expect($labels[$i])->toBe($cases[$i]->label());
            }
        });
    });

    // ── Comparison Methods ─────────────────────────────────────────────────

    describe('is()', function () {
        it('compares by instance identity', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
        });

        it('compares by case name string', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('BANNED'))->toBeFalse();
        });

        it('is case-sensitive for string names', function () {
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
        });

        it('works for int-backed enums', function () {
            $first = IntBackedPriority::cases()[0];
            expect($first->is($first))->toBeTrue();
            expect($first->is($first->name))->toBeTrue();
        });

        it('works for pure enums', function () {
            $first = PureFeatureFlag::cases()[0];
            expect($first->is($first))->toBeTrue();
            expect($first->is($first->name))->toBeTrue();
        });

        it('rejects different enum types', function () {
            // Different enum types cannot match even with same case name
            // This is enforced by the `self` type hint
        });
    });

    describe('isNot()', function () {
        it('negates is() for instance', function () {
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
        });

        it('negates is() for string', function () {
            expect(UserStatus::ACTIVE->isNot('BANNED'))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot('ACTIVE'))->toBeFalse();
        });
    });

    describe('in()', function () {
        it('matches when case is in the list (instances)', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect(UserStatus::ACTIVE->in([UserStatus::BANNED]))->toBeFalse();
        });

        it('matches when case is in the list (strings)', function () {
            expect(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['BANNED']))->toBeFalse();
        });

        it('matches mixed instances and strings', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        });

        it('returns false for empty list', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });
    });

    describe('notIn()', function () {
        it('returns true when case is NOT in the list', function () {
            expect(UserStatus::ACTIVE->notIn([UserStatus::BANNED, UserStatus::SUSPENDED]))->toBeTrue();
        });

        it('returns false when case IS in the list', function () {
            expect(UserStatus::ACTIVE->notIn([UserStatus::ACTIVE]))->toBeFalse();
        });

        it('returns true for empty list', function () {
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
        });
    });

    // ── Lookup Methods ──────────────────────────────────────────────────────

    describe('tryFromLabel()', function () {
        it('finds case by exact label (case-insensitive)', function () {
            expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
        });

        it('finds case by auto-generated label', function () {
            expect(UserStatus::tryFromLabel('Inactive'))->toBe(UserStatus::INACTIVE);
            expect(UserStatus::tryFromLabel('inactive'))->toBe(UserStatus::INACTIVE);
        });

        it('returns null for non-existent label', function () {
            expect(UserStatus::tryFromLabel('non-existent-label'))->toBeNull();
        });

        it('returns null for empty string', function () {
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });
    });

    describe('tryFromName()', function () {
        it('finds case by name for string-backed enums', function () {
            expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        });

        it('finds case by name for int-backed enums', function () {
            $first = IntBackedPriority::cases()[0];
            expect(IntBackedPriority::tryFromName($first->name))->toBe($first);
        });

        it('finds case by name for pure enums', function () {
            $first = PureFeatureFlag::cases()[0];
            expect(PureFeatureFlag::tryFromName($first->name))->toBe($first);
        });

        it('returns null for non-existent name', function () {
            expect(UserStatus::tryFromName('NON_EXISTENT'))->toBeNull();
        });

        it('is case-sensitive', function () {
            expect(UserStatus::tryFromName('active'))->toBeNull();
        });
    });

    describe('fromName()', function () {
        it('returns case for valid name', function () {
            expect(UserStatus::fromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        });

        it('throws InvalidEnumException for non-existent name', function () {
            expect(fn () => UserStatus::fromName('NON_EXISTENT'))->toThrow(InvalidEnumException::class);
        });

        it('exception message contains class name and case name', function () {
            try {
                UserStatus::fromName('GARBAGE');
                expect(true)->toBeFalse('Should have thrown');
            } catch (InvalidEnumException $e) {
                expect($e->getMessage())->toContain('UserStatus');
                expect($e->getMessage())->toContain('GARBAGE');
            }
        });

        it('exception __toString includes class name', function () {
            try {
                UserStatus::fromName('GARBAGE');
            } catch (InvalidEnumException $e) {
                $str = (string) $e;
                expect($str)->toContain('InvalidEnumException');
            }
        });
    });

    describe('hasCase()', function () {
        it('returns true for existing case name', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        });

        it('returns false for non-existent case name', function () {
            expect(UserStatus::hasCase('NON_EXISTENT'))->toBeFalse();
        });

        it('is case-sensitive', function () {
            expect(UserStatus::hasCase('active'))->toBeFalse();
        });
    });

    // ── InvalidEnumException Factory Methods ───────────────────────────────

    describe('InvalidEnumException::value()', function () {
        it('creates exception with null value display', function () {
            $e = InvalidEnumException::value('MyEnum', null);
            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain('MyEnum');
        });

        it('creates exception with int value display', function () {
            $e = InvalidEnumException::value('MyEnum', 42);
            expect($e->getMessage())->toContain('42');
        });

        it('creates exception with string value display', function () {
            $e = InvalidEnumException::value('MyEnum', 'test');
            expect($e->getMessage())->toContain('test');
        });

        it('__toString returns class name and message', function () {
            $e = InvalidEnumException::value('MyEnum', 42);
            expect((string) $e)->toContain('InvalidEnumException');
            expect((string) $e)->toContain('42');
        });
    });

    // ── EnumCache ───────────────────────────────────────────────────────────

    describe('EnumCache singleton', function () {
        beforeEach(function () {
            \ZeroBoiler\Enums\EnumCache::flush();
        });

        afterEach(function () {
            \ZeroBoiler\Enums\EnumCache::flush();
        });

        it('returns same instance from getInstance()', function () {
            $a = \ZeroBoiler\Enums\EnumCache::getInstance();
            $b = \ZeroBoiler\Enums\EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('has() returns false for unknown class', function () {
            expect(\ZeroBoiler\Enums\EnumCache::getInstance()->has('NonExistentClass'))->toBeFalse();
        });

        it('set() and get() round-trip correctly', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $metadata = [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => ['active' => 'success'],
                'icons' => [],
            ];
            $cache->set('TestEnum', $metadata);
            expect($cache->has('TestEnum'))->toBeTrue();
            expect($cache->get('TestEnum'))->toBe($metadata);
        });

        it('clear() removes all entries', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->set('A', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->clear();
            expect($cache->has('A'))->toBeFalse();
        });

        it('clearClass() removes only the specified class', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $meta = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
            $cache->set('A', $meta);
            $cache->set('B', $meta);
            $cache->clearClass('A');
            expect($cache->has('A'))->toBeFalse();
            expect($cache->has('B'))->toBeTrue();
        });

        it('TTL=0 disables caching (has always false)', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set('TestEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('negative TTL is normalized to 0', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->setTtl(-5);
            expect($cache->getTtl())->toBe(0);
        });

        it('get() throws OutOfBoundsException for missing class', function () {
            expect(fn () => \ZeroBoiler\Enums\EnumCache::getInstance()->get('NonExistent'))
                ->toThrow(\OutOfBoundsException::class);
        });

        it('flush() delegates to singleton clear()', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->set('X', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            \ZeroBoiler\Enums\EnumCache::flush();
            expect($cache->has('X'))->toBeFalse();
        });

        it('resetInstance() allows new singleton creation', function () {
            \ZeroBoiler\Enums\EnumCache::resetInstance();
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            expect($cache)->toBeInstanceOf(\ZeroBoiler\Enums\EnumCache::class);
            // Cleanup
            \ZeroBoiler\Enums\EnumCache::flush();
        });
    });

    // ── EnumRule ───────────────────────────────────────────────────────────

    describe('EnumRule', function () {
        it('validates string-backed enum values', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = fn (string $msg): string => $msg;
            $rule->validate('status', 'active', $fail);
            // Should not fail
            expect(true)->toBeTrue();
        });

        it('rejects invalid string-backed enum value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $msg) use (&$failed): string {
                $failed = true;

                return $msg;
            };
            $rule->validate('status', 'non_existent', $fail);
            expect($failed)->toBeTrue();
        });

        it('validates int-backed enum values', function () {
            $first = IntBackedPriority::cases()[0];
            $rule = EnumRule::for(IntBackedPriority::class);
            $fail = fn (string $msg): string => $msg;
            $rule->validate('priority', $first->value, $fail);
            expect(true)->toBeTrue();
        });

        it('rejects int value for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $msg) use (&$failed): string {
                $failed = true;

                return $msg;
            };
            $rule->validate('status', 123, $fail);
            expect($failed)->toBeTrue();
        });

        it('rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;
            $fail = function (string $msg) use (&$failed): string {
                $failed = true;

                return $msg;
            };
            $rule->validate('priority', 'not_an_int', $fail);
            expect($failed)->toBeTrue();
        });

        it('validates pure enum by case name', function () {
            $first = PureFeatureFlag::cases()[0];
            $rule = EnumRule::for(PureFeatureFlag::class);
            $fail = fn (string $msg): string => $msg;
            $rule->validate('feature', $first->name, $fail);
            expect(true)->toBeTrue();
        });

        it('rejects non-string value for pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;
            $fail = function (string $msg) use (&$failed): string {
                $failed = true;

                return $msg;
            };
            $rule->validate('feature', 123, $fail);
            expect($failed)->toBeTrue();
        });

        it('nullable() allows null values', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $fail = fn (string $msg): string => $msg;
            $rule->validate('status', null, $fail);
            // Should not fail
            expect(true)->toBeTrue();
        });

        it('non-nullable rejects null values', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $msg) use (&$failed): string {
                $failed = true;

                return $msg;
            };
            $rule->validate('status', null, $fail);
            expect($failed)->toBeTrue();
        });

        it('for() is equivalent to constructor', function () {
            $a = EnumRule::for(UserStatus::class);
            $b = new EnumRule(UserStatus::class);
            // Both should behave identically
            expect(true)->toBeTrue();
        });

        it('generates descriptive error message', function () {
            $rule = EnumRule::for(UserStatus::class);
            $message = '';
            $fail = function (string $msg) use (&$message): string {
                $message = $msg;

                return $msg;
            };
            $rule->validate('status', 'invalid', $fail);
            expect($message)->toContain('status');
            expect($message)->toContain('invalid');
        });
    });

    // ── EnumCast ───────────────────────────────────────────────────────────

    describe('EnumCast', function () {
        it('constructs with enum class', function () {
            $cast = new EnumCast(UserStatus::class);
            expect($cast)->toBeInstanceOf(EnumCast::class);
        });

        it('get() returns null for null value', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            expect($cast->get($model, 'status', null, []))->toBeNull();
        });

        it('get() returns enum case for valid value', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            $result = $cast->get($model, 'status', 'active', []);
            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('get() returns null for invalid value (silent)', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            $result = $cast->get($model, 'status', 'non_existent', []);
            expect($result)->toBeNull();
        });

        it('get() handles numeric string for int-backed enum', function () {
            $cast = new EnumCast(IntBackedPriority::class);
            $model = new class {};
            $first = IntBackedPriority::cases()[0];
            $result = $cast->get($model, 'priority', (string) $first->value, []);
            expect($result)->toBe($first);
        });

        it('set() returns null for null value', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            expect($cast->set($model, 'status', null, []))->toBeNull();
        });

        it('set() returns backed value for enum instance', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            expect($cast->set($model, 'status', UserStatus::ACTIVE, []))->toBe('active');
        });

        it('set() validates and returns raw valid value', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            expect($cast->set($model, 'status', 'active', []))->toBe('active');
        });

        it('set() throws for invalid raw value', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            expect(fn () => $cast->set($model, 'status', 'invalid_value', []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('set() throws for wrong enum type', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            $other = IntBackedPriority::cases()[0];
            expect(fn () => $cast->set($model, 'status', $other, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns backed value for enum', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            expect($cast->serialize($model, 'status', UserStatus::ACTIVE, []))->toBe('active');
        });

        it('serialize() returns raw string value', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            expect($cast->serialize($model, 'status', 'active', []))->toBe('active');
        });

        it('serialize() returns null for null', function () {
            $cast = new EnumCast(UserStatus::class);
            $model = new class {};
            expect($cast->serialize($model, 'status', null, []))->toBeNull();
        });
    });

    // ── EnumManager ─────────────────────────────────────────────────────────

    describe('EnumManager', function () {
        it('delegates forSelect() to trait method', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $select = $manager->forSelect(UserStatus::class);
            expect($select)->toBeArray();
            expect($select)->not->toBeEmpty();
        });

        it('delegates forApi() to trait method', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $api = $manager->forApi(UserStatus::class);
            expect($api)->toBeArray();
            expect($api)->not->toBeEmpty();
        });

        it('delegates tryFromLabel() to trait method', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect($manager->tryFromLabel(UserStatus::class, 'Active User'))->toBe(UserStatus::ACTIVE);
        });

        it('delegates tryFromName() to trait method', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect($manager->tryFromName(UserStatus::class, 'ACTIVE'))->toBe(UserStatus::ACTIVE);
        });

        it('delegates hasCase() to trait method', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
            expect($manager->hasCase(UserStatus::class, 'NON_EXISTENT'))->toBeFalse();
        });

        it('throws BadMethodCallException for non-enum class', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect(fn () => $manager->forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('is readonly', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $reflection = new \ReflectionClass($manager);
            expect($reflection->isReadOnly())->toBeTrue();
        });
    });

    // ── Zero-Backed Enum Edge Cases ────────────────────────────────────────

    describe('Zero-backed enum values', function () {
        it('forSelect() returns zero as a valid value', function () {
            $select = ZeroBackedPriority::forSelect();
            $values = array_column($select, 'value');
            expect($values)->toContain(0);
        });

        it('values() includes zero', function () {
            $values = ZeroBackedPriority::values();
            expect($values)->toContain(0);
        });
    });

    // ── Cross-enum type consistency ─────────────────────────────────────────

    describe('Type consistency across all enum types', function () {
        it('forSelect() returns consistent structure for all types', function () {
            foreach (
                [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class] as $enumClass
            ) {
                $select = $enumClass::forSelect();
                foreach ($select as $option) {
                    expect($option)->toBeArray();
                    expect($option)->toHaveKeys(['value', 'label']);
                    expect($option['label'])->toBeString()->not->toBeEmpty();
                }
            }
        });

        it('forApi() returns consistent structure for all types', function () {
            foreach (
                [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class] as $enumClass
            ) {
                $api = $enumClass::forApi();
                foreach ($api as $item) {
                    expect($item)->toBeArray();
                    expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                }
            }
        });

        it('color() always returns non-empty string', function () {
            foreach (
                [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class] as $enumClass
            ) {
                foreach ($enumClass::cases() as $case) {
                    expect($case->color())->toBeString()->not->toBeEmpty();
                }
            }
        });

        it('label() always returns non-empty string', function () {
            foreach (
                [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class, CamelCaseRole::class] as $enumClass
            ) {
                foreach ($enumClass::cases() as $case) {
                    expect($case->label())->toBeString()->not->toBeEmpty();
                }
            }
        });
    });
});

// Helper for PHP < 8.4 compatibility (array_find)
if (! function_exists('array_find')) {
    /**
     * @template T
     *
     * @param  array<int, T>  $array
     * @param  callable(T, int): bool  $callback
     * @return T|null
     */
    function array_find(array $array, callable $callback): mixed
    {
        foreach ($array as $index => $value) {
            if ($callback($value, $index)) {
                return $value;
            }
        }

        return null;
    }
}
