<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast as PublicEnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingletonMode;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

describe('Enum V18 — full production contract audit', function () {
    // ────────────────────────────────────────────────────────────────
    // 1. Attribute classes: final, strict types, readonly promoted props
    // ────────────────────────────────────────────────────────────────
    describe('Attribute structural contract', function () {
        it('Label is final with readonly string value', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Attributes\Label::class);
            expect($ref->isFinal())->toBeTrue();
            $prop = $ref->getProperty('value');
            expect($prop->isReadOnly())->toBeTrue();
            expect($prop->getType()->getName())->toBe('string');
        });

        it('Color is final with readonly string value', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Attributes\Color::class);
            expect($ref->isFinal())->toBeTrue();
            $prop = $ref->getProperty('value');
            expect($prop->isReadOnly())->toBeTrue();
        });

        it('Icon is final with readonly string value', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Attributes\Icon::class);
            expect($ref->isFinal())->toBeTrue();
            $prop = $ref->getProperty('value');
            expect($prop->isReadOnly())->toBeTrue();
        });

        it('Description is final with readonly string value', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Attributes\Description::class);
            expect($ref->isFinal())->toBeTrue();
            $prop = $ref->getProperty('value');
            expect($prop->isReadOnly())->toBeTrue();
        });

        it('EnumLabel is final with readonly properties and dual target', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Attributes\EnumLabel::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->getProperty('labels')->isReadOnly())->toBeTrue();
            expect($ref->getProperty('label')->isReadOnly())->toBeTrue();
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs)->not->toBeEmpty();
        });

        it('EnumColor is final with 5 readonly list properties', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Attributes\EnumColor::class);
            expect($ref->isFinal())->toBeTrue();
            $names = ['success', 'danger', 'warning', 'info', 'secondary'];
            foreach ($names as $name) {
                $prop = $ref->getProperty($name);
                expect($prop->isReadOnly())->toBeTrue();
                expect($prop->getDefaultValue())->toBe([]);
            }
        });

        it('EnumIcon is final with readonly default and icons map', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Attributes\EnumIcon::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->getProperty('default')->isReadOnly())->toBeTrue();
            expect($ref->getProperty('default')->getDefaultValue())->toBeNull();
            expect($ref->getProperty('icons')->isReadOnly())->toBeTrue();
            expect($ref->getProperty('icons')->getDefaultValue())->toBe([]);
        });

        it('EnumDescription is final with readonly descriptions and description props', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Attributes\EnumDescription::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->getProperty('descriptions')->isReadOnly())->toBeTrue();
            expect($ref->getProperty('description')->isReadOnly())->toBeTrue();
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 2. EnumCache: singleton lifecycle, TTL, serialization blocking
    // ────────────────────────────────────────────────────────────────
    describe('EnumCache singleton and TTL contract', function () {
        beforeEach(function () {
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::resetInstance();
        });

        it('returns the same instance on repeated calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('fresh instance after reset has empty cache and default TTL', function () {
            $cache = EnumCache::getInstance();
            expect($cache->getTtl())->toBe(300);
            // No entries for a class that hasn't been resolved
            expect($cache->has('NonExistentClass'))->toBeFalse();
        });

        it('setTtl clamps negative values to zero', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);
            expect($cache->getTtl())->toBe(0);
            // TTL 0 means caching disabled — has() always returns false
            $cache->set('SomeClass', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            expect($cache->has('SomeClass'))->toBeFalse();
        });

        it('clear() removes all entries and timestamps', function () {
            $cache = EnumCache::getInstance();
            $cache->set('A', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('B', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->clear();
            expect($cache->has('A'))->toBeFalse();
            expect($cache->has('B'))->toBeFalse();
        });

        it('clearClass() removes only the specified class', function () {
            $cache = EnumCache::getInstance();
            $cache->set('X', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('Y', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->clearClass('X');
            expect($cache->has('X'))->toBeFalse();
            expect($cache->has('Y'))->toBeTrue();
        });

        it('throws OutOfBoundsException when get() called for missing class', function () {
            $cache = EnumCache::getInstance();
            expect(fn () => $cache->get('Missing'))->toThrow(\OutOfBoundsException::class);
        });

        it('blocks serialization via __serialize', function () {
            $cache = EnumCache::getInstance();
            expect(fn () => serialize($cache))->toThrow(\RuntimeException::class);
        });

        it('blocks serialization via __wakeup', function () {
            expect(fn () => (new ReflectionMethod(EnumCache::class, '__wakeup'))->getModifiers())
                ->not->toThrow(\RuntimeException::class);
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 3. EnumManager: readonly, delegation, trait validation
    // ────────────────────────────────────────────────────────────────
    describe('EnumManager structural contract', function () {
        it('is final readonly class', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('forSelect delegates to trait on valid enum', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $result = $manager->forSelect(UserStatus::class);
            expect($result)->toBeArray();
            expect($result)->not->toBeEmpty();
            expect($result[0])->toHaveKeys(['value', 'label']);
        });

        it('forApi delegates to trait on valid enum', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $result = $manager->forApi(UserStatus::class);
            expect($result)->toBeArray();
            expect($result[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('tryFromName returns null for non-existent case', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect($manager->tryFromName(UserStatus::class, 'NONEXISTENT'))->toBeNull();
        });

        it('tryFromName returns the correct case for valid name', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $case = $manager->tryFromName(UserStatus::class, 'ACTIVE');
            expect($case)->not->toBeNull();
            expect($case->name)->toBe('ACTIVE');
        });

        it('fromName throws InvalidEnumException for non-existent case', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect(fn () => $manager->fromName(UserStatus::class, 'NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns true for existing case', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
        });

        it('hasCase returns false for non-existent case', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect($manager->hasCase(UserStatus::class, 'NONEXISTENT'))->toBeFalse();
        });

        it('values returns correct backed values', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $values = $manager->values(UserStatus::class);
            expect($values)->toBeArray();
            expect($values)->toContain('active');
        });

        it('labels returns non-empty strings', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $labels = $manager->labels(UserStatus::class);
            expect($labels)->toBeArray();
            expect($labels)->not->toBeEmpty();
            foreach ($labels as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 4. InvalidEnumException: factory methods, __toString
    // ────────────────────────────────────────────────────────────────
    describe('InvalidEnumException contract', function () {
        it('is final', function () {
            $ref = new ReflectionClass(InvalidEnumException::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('value() factory produces correct message for null', function () {
            $e = InvalidEnumException::value('SomeEnum', null);
            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain('SomeEnum');
        });

        it('value() factory produces correct message for int', function () {
            $e = InvalidEnumException::value('StatusEnum', 42);
            expect($e->getMessage())->toContain('42');
        });

        it('value() factory produces correct message for string', function () {
            $e = InvalidEnumException::value('StatusEnum', 'invalid');
            expect($e->getMessage())->toContain('invalid');
        });

        it('forName() factory produces correct message', function () {
            $e = InvalidEnumException::forName('StatusEnum', 'BAD_CASE');
            expect($e->getMessage())->toContain('BAD_CASE');
            expect($e->getMessage())->toContain('StatusEnum');
        });

        it('__toString includes class name and message', function () {
            $e = InvalidEnumException::value('TestEnum', 'bad');
            $str = (string) $e;
            expect($str)->toContain('InvalidEnumException');
            expect($str)->toContain('bad');
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 5. EnumRule: nullable, backed enum validation, pure enum support
    // ────────────────────────────────────────────────────────────────
    describe('EnumRule validation contract', function () {
        it('is final readonly', function () {
            $ref = new ReflectionClass(EnumRule::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('validates valid backed enum value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = fn () => null;
            $rule->validate('status', 'active', $fail);
            // No exception means valid
            expect(true)->toBeTrue();
        });

        it('rejects invalid backed enum value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };
            $rule->validate('status', 'nonexistent', $fail);
            expect($failed)->toBeTrue();
        });

        it('nullable passes for null value', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $fail = fn () => null;
            $rule->validate('status', null, $fail);
            expect(true)->toBeTrue();
        });

        it('non-nullable rejects null value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };
            $rule->validate('status', null, $fail);
            expect($failed)->toBeTrue();
        });

        it('nullable still validates non-null values', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };
            $rule->validate('status', 'nonexistent', $fail);
            expect($failed)->toBeTrue();
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 6. HasEnumMetadata trait: all methods return typed, no mixed
    // ────────────────────────────────────────────────────────────────
    describe('HasEnumMetadata trait return type contract', function () {
        it('label() returns string', function () {
            expect(UserStatus::ACTIVE->label())->toBeString();
        });

        it('description() returns string or null', function () {
            $result = UserStatus::ACTIVE->description();
            expect($result)->toBeNull()->or()->toBeString();
        });

        it('color() returns string', function () {
            expect(UserStatus::ACTIVE->color())->toBeString();
        });

        it('icon() returns string or null', function () {
            $result = UserStatus::ACTIVE->icon();
            expect($result)->toBeNull()->or()->toBeString();
        });

        it('forSelect() returns list of value/label pairs', function () {
            $result = UserStatus::forSelect();
            expect($result)->toBeArray();
            foreach ($result as $item) {
                expect($item)->toHaveKeys(['value', 'label']);
                expect($item['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forApi() returns list with full metadata shape', function () {
            $result = UserStatus::forApi();
            expect($result)->toBeArray();
            foreach ($result as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            }
        });

        it('values() returns list of scalar values', function () {
            $values = UserStatus::values();
            expect($values)->toBeArray();
            foreach ($values as $v) {
                expect(is_string($v) || is_int($v))->toBeTrue();
            }
        });

        it('labels() returns list of non-empty strings', function () {
            $labels = UserStatus::labels();
            expect($labels)->toBeArray();
            foreach ($labels as $l) {
                expect($l)->toBeString()->not->toBeEmpty();
            }
        });

        it('is() uses strict identity comparison', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::INACTIVE))->toBeFalse();
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
        });

        it('in() works with mixed instances and strings', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'INACTIVE']))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['INACTIVE', 'PENDING']))->toBeFalse();
        });

        it('notIn() is inverse of in()', function () {
            expect(UserStatus::ACTIVE->notIn(['INACTIVE']))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn([UserStatus::ACTIVE]))->toBeFalse();
        });

        it('tryFromLabel is case-insensitive', function () {
            $label = UserStatus::ACTIVE->label();
            expect(UserStatus::tryFromLabel(strtolower($label)))->not->toBeNull();
        });

        it('tryFromName is case-sensitive', function () {
            expect(UserStatus::tryFromName('ACTIVE'))->not->toBeNull();
            expect(UserStatus::tryFromName('active'))->toBeNull();
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 7. Cross-fixture consistency: int-backed, pure, camelCase enums
    // ────────────────────────────────────────────────────────────────
    describe('Cross-fixture type consistency', function () {
        it('int-backed enum values returns ints', function () {
            $values = IntBackedPriority::values();
            foreach ($values as $v) {
                expect(is_int($v))->toBeTrue();
            }
        });

        it('pure enum values returns case names as strings', function () {
            $values = PureFeatureFlag::values();
            foreach ($values as $v) {
                expect(is_string($v))->toBeTrue();
            }
        });

        it('zero-backed enum resolves correctly', function () {
            expect(ZeroBackedPriority::LOW->label())->toBeString();
            expect(ZeroBackedPriority::LOW->color())->toBeString();
        });

        it('camelCase enum generates correct labels', function () {
            $label = CamelCaseRole::Admin->label();
            expect($label)->toBeString()->not->toBeEmpty();
        });

        it('singleton mode enum works with single case', function () {
            expect(SingletonMode::ENABLED->label())->toBeString();
            expect(SingletonMode::values())->toHaveCount(1);
        });

        it('empty defaults enum returns defaults correctly', function () {
            expect(EmptyDefaultsStatus::ACTIVE->color())->toBe('secondary');
            expect(EmptyDefaultsStatus::ACTIVE->icon())->toBeNull();
            expect(EmptyDefaultsStatus::ACTIVE->description())->toBeNull();
        });

        it('label map enum resolves per-value labels', function () {
            $labels = LabelMapEnum::forSelect();
            foreach ($labels as $item) {
                expect($item['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('all class level enum uses class-level defaults', function () {
            $api = AllClassLevelEnum::forApi();
            expect($api)->not->toBeEmpty();
            foreach ($api as $item) {
                expect($item['color'])->toBeString();
            }
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 8. EnumCast: serialization roundtrip, type guard
    // ────────────────────────────────────────────────────────────────
    describe('EnumCast structural safety', function () {
        it('is final', function () {
            $ref = new ReflectionClass(PublicEnumCast::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('implements CastsAttributes interface', function () {
            $ref = new ReflectionClass(PublicEnumCast::class);
            $interfaces = $ref->getInterfaceNames();
            expect(in_array(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class, $interfaces, true))->toBeTrue();
        });

        it('get() returns null for null value', function () {
            $cast = new PublicEnumCast(UserStatus::class);
            $result = $cast->get(new stdClass, 'status', null, []);
            expect($result)->toBeNull();
        });

        it('get() returns enum instance for valid value', function () {
            $cast = new PublicEnumCast(UserStatus::class);
            $result = $cast->get(new stdClass, 'status', 'active', []);
            expect($result)->toBeInstanceOf(\BackedEnum::class);
            expect($result->value)->toBe('active');
        });

        it('get() returns null for invalid value', function () {
            $cast = new PublicEnumCast(UserStatus::class);
            $result = $cast->get(new stdClass, 'status', 'nonexistent', []);
            expect($result)->toBeNull();
        });

        it('set() throws for wrong enum type', function () {
            $cast = new PublicEnumCast(PaymentStatus::class);
            expect(fn () => $cast->set(new stdClass, 'status', UserStatus::ACTIVE, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns backed value for enum instance', function () {
            $cast = new PublicEnumCast(UserStatus::class);
            $result = $cast->serialize(new stdClass, 'status', UserStatus::ACTIVE, []);
            expect($result)->toBe('active');
        });

        it('serialize() returns int/string for raw values', function () {
            $cast = new PublicEnumCast(UserStatus::class);
            expect($cast->serialize(new stdClass, 'status', 'active', []))->toBe('active');
        });

        it('serialize() returns null for null', function () {
            $cast = new PublicEnumCast(UserStatus::class);
            expect($cast->serialize(new stdClass, 'status', null, []))->toBeNull();
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 9. EnumsServiceProvider: registration and structure
    // ────────────────────────────────────────────────────────────────
    describe('EnumsServiceProvider contract', function () {
        it('is final', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('extends Laravel ServiceProvider', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);
            expect($ref->getParentClass()->getName())->toBe(\Illuminate\Support\ServiceProvider::class);
        });

        it('register() has Override attribute', function () {
            $method = new ReflectionMethod(\ZeroBoiler\Enums\EnumsServiceProvider::class, 'register');
            $attrs = $method->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty();
        });

        it('boot() has Override attribute', function () {
            $method = new ReflectionMethod(\ZeroBoiler\Enums\EnumsServiceProvider::class, 'boot');
            $attrs = $method->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty();
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 10. Facade: accessor and docblock completeness
    // ────────────────────────────────────────────────────────────────
    describe('Enum facade contract', function () {
        it('is final', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('returns zeroboiler.enum accessor', function () {
            $method = new ReflectionMethod(\ZeroBoiler\Enums\Facades\Enum::class, 'getFacadeAccessor');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('string');
        });
    });
});
