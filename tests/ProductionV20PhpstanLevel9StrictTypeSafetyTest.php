<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

describe('V20 — PHPStan Level 9 Strict Type Safety And Edge Case Coverage', function () {
    // ─── Trait: Strict type safety on return types ────────────────────────

    it('label() returns strict string for all fixture enums', function () {
        foreach ([UserStatus::class, Priority::class, IntBackedPriority::class, PaymentStatus::class] as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                expect(is_string($case->label()))->toBeTrue();
            }
        }
    });

    it('description() returns nullable string for all fixture enums', function () {
        foreach ([UserStatus::class, Priority::class, OrderStatus::class] as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                $desc = $case->description();
                expect($desc === null || is_string($desc))->toBeTrue();
            }
        }
    });

    it('color() returns strict string (never null)', function () {
        foreach ([UserStatus::class, Priority::class, PaymentStatus::class, EmptyDefaultsStatus::class] as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                $color = $case->color();
                expect(is_string($color))->toBeTrue();
                expect($color)->not->toBeEmpty();
            }
        }
    });

    it('icon() returns nullable string for all fixture enums', function () {
        foreach ([UserStatus::class, OrderStatus::class, AllClassLevelEnum::class] as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                $icon = $case->icon();
                expect($icon === null || is_string($icon))->toBeTrue();
            }
        }
    });

    // ─── Trait: forSelect structure type safety ──────────────────────────

    it('forSelect() returns list with strict value+label structure for string-backed enum', function () {
        $result = UserStatus::forSelect();
        expect(is_array($result))->toBeTrue();
        expect($result)->not->toBeEmpty();

        foreach ($result as $item) {
            expect($item)->toBeArray();
            expect(array_key_exists('value', $item))->toBeTrue();
            expect(array_key_exists('label', $item))->toBeTrue();
            expect(is_string($item['value']))->toBeTrue();
            expect(is_string($item['label']))->toBeTrue();
        }
    });

    it('forSelect() returns list with int values for int-backed enum', function () {
        $result = IntBackedPriority::forSelect();
        expect($result)->not->toBeEmpty();

        foreach ($result as $item) {
            expect(is_int($item['value']))->toBeTrue();
        }
    });

    it('forSelect() returns case names for pure enum', function () {
        $result = PureFeatureFlag::forSelect();
        expect($result)->not->toBeEmpty();

        foreach ($result as $item) {
            expect(is_string($item['value']))->toBeTrue();
            // Pure enum uses case name as value
            expect($item['value'])->toBeIn(
                array_map(fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases())
            );
        }
    });

    // ─── Trait: forApi structure type safety ─────────────────────────────

    it('forApi() returns full metadata structure for all fixture enums', function () {
        foreach ([UserStatus::class, Priority::class, IntBackedPriority::class] as $enumClass) {
            $result = $enumClass::forApi();
            expect($result)->not->toBeEmpty();

            $expectedKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];
            foreach ($result as $item) {
                foreach ($expectedKeys as $key) {
                    expect(array_key_exists($key, $item))->toBeTrue("Missing key '{$key}' in {$enumClass}::forApi()");
                }
                expect(is_string($item['name']))->toBeTrue();
                expect(is_string($item['label']))->toBeTrue();
                expect(is_string($item['color']))->toBeTrue();
            }
        }
    });

    // ─── Trait: values/labels return types ───────────────────────────────

    it('values() returns list of int|string for backed enums', function () {
        $stringValues = UserStatus::values();
        $intValues = IntBackedPriority::values();

        foreach ($stringValues as $v) {
            expect(is_string($v))->toBeTrue();
        }

        foreach ($intValues as $v) {
            expect(is_int($v))->toBeTrue();
        }
    });

    it('values() returns case names for pure enum', function () {
        $values = PureFeatureFlag::values();
        expect($values)->not->toBeEmpty();

        foreach ($values as $v) {
            expect(is_string($v))->toBeTrue();
        }
    });

    it('labels() returns list of non-empty strings', function () {
        foreach ([UserStatus::class, Priority::class, IntBackedPriority::class] as $enumClass) {
            $labels = $enumClass::labels();
            expect(count($labels))->toEqual(count($enumClass::cases()));

            foreach ($labels as $label) {
                expect(is_string($label))->toBeTrue();
                expect($label)->not->toBeEmpty();
            }
        }
    });

    // ─── Comparison: strict identity checks ──────────────────────────────

    it('is() uses strict identity for same instance', function () {
        $active = UserStatus::ACTIVE;
        expect($active->is(UserStatus::ACTIVE))->toBeTrue();
        expect($active->is(UserStatus::BANNED))->toBeFalse();
    });

    it('is() handles string case names with case-sensitive comparison', function () {
        $status = UserStatus::ACTIVE;
        expect($status->is('ACTIVE'))->toBeTrue();
        expect($status->is('Active'))->toBeFalse();
        expect($status->is('active'))->toBeFalse();
    });

    it('isNot() is correct negation', function () {
        $active = UserStatus::ACTIVE;
        expect($active->isNot(UserStatus::BANNED))->toBeTrue();
        expect($active->isNot(UserStatus::ACTIVE))->toBeFalse();
    });

    it('in() handles mixed instances and strings', function () {
        $active = UserStatus::ACTIVE;
        expect($active->in([UserStatus::ACTIVE, UserStatus::BANNED]))->toBeTrue();
        expect($active->in(['ACTIVE', 'BANNED']))->toBeTrue();
        expect($active->in([UserStatus::BANNED, UserStatus::INACTIVE]))->toBeFalse();
        expect($active->in(['BANNED', 'INACTIVE']))->toBeFalse();
    });

    it('notIn() handles mixed instances and strings', function () {
        $active = UserStatus::ACTIVE;
        expect($active->notIn(['BANNED', 'INACTIVE']))->toBeTrue();
        expect($active->notIn([UserStatus::BANNED, UserStatus::INACTIVE]))->toBeTrue();
        expect($active->notIn(['ACTIVE', 'BANNED']))->toBeFalse();
    });

    // ─── Lookup: strict return types ─────────────────────────────────────

    it('tryFromLabel() returns null for non-existent label', function () {
        expect(UserStatus::tryFromLabel('NONEXISTENT_LABEL_XYZ'))->toBeNull();
    });

    it('tryFromLabel() is case-insensitive', function () {
        $result = UserStatus::tryFromLabel('active user');
        expect($result)->not->toBeNull();
        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('tryFromName() returns null for non-existent name', function () {
        expect(UserStatus::tryFromName('NONEXISTENT'))->toBeNull();
    });

    it('tryFromName() is case-sensitive', function () {
        expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromName('Active'))->toBeNull();
    });

    it('fromName() throws InvalidEnumException for non-existent name', function () {
        expect(fn () => UserStatus::fromName('NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('fromName() exception message contains class and name', function () {
        try {
            UserStatus::fromName('FAKE_CASE');
            $this->fail('Expected InvalidEnumException');
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('FAKE_CASE');
            expect($e->getMessage())->toContain(UserStatus::class);
        }
    });

    it('hasCase() returns bool strictly', function () {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('active'))->toBeFalse();
        expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
    });

    // ─── EnumRule: strict validation ──────────────────────────────────────

    it('EnumRule rejects value with wrong backing type (string to int enum)', function () {
        $rule = EnumRule::for(IntBackedPriority::class);
        $fail = fn (string $m): string => $m;
        // Passing a string to an int-backed enum should fail
        $errors = [];
        $rule->validate('priority', 'high', function (string $message) use (&$errors): void {
            $errors[] = $message;
        });
        expect($errors)->not->toBeEmpty();
    });

    it('EnumRule rejects value with wrong backing type (int to string enum)', function () {
        $rule = EnumRule::for(UserStatus::class);
        $errors = [];
        $rule->validate('status', 42, function (string $message) use (&$errors): void {
            $errors[] = $message;
        });
        expect($errors)->not->toBeEmpty();
    });

    it('EnumRule nullable passes null values', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $errors = [];
        $rule->validate('status', null, function (string $message) use (&$errors): void {
            $errors[] = $message;
        });
        expect($errors)->toBeEmpty();
    });

    it('EnumRule non-nullable rejects null values', function () {
        $rule = EnumRule::for(UserStatus::class);
        $errors = [];
        $rule->validate('status', null, function (string $message) use (&$errors): void {
            $errors[] = $message;
        });
        expect($errors)->not->toBeEmpty();
    });

    it('EnumRule validates pure enums by case name', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $errors = [];
        $rule->validate('flag', 'FEATURE_A', function (string $message) use (&$errors): void {
            $errors[] = $message;
        });
        expect($errors)->toBeEmpty();

        $errors = [];
        $rule->validate('flag', 'NONEXISTENT_FEATURE', function (string $message) use (&$errors): void {
            $errors[] = $message;
        });
        expect($errors)->not->toBeEmpty();
    });

    it('EnumRule rejects non-string values for pure enums', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $errors = [];
        $rule->validate('flag', 123, function (string $message) use (&$errors): void {
            $errors[] = $message;
        });
        expect($errors)->not->toBeEmpty();
    });

    // ─── EnumCache: singleton lifecycle ─────────────────────────────────

    it('EnumCache returns same instance on multiple getInstance() calls', function () {
        EnumCache::resetInstance();
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();
        expect($a)->toBe($b);
        EnumCache::resetInstance();
    });

    it('EnumCache TTL of 0 disables caching', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set('TestEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        expect($cache->has('TestEnum'))->toBeFalse();
        EnumCache::resetInstance();
    });

    it('EnumCache flush clears all entries', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('EnumB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        expect($cache->has('EnumA'))->toBeTrue();
        expect($cache->has('EnumB'))->toBeTrue();

        $cache->flush();

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeFalse();
        EnumCache::resetInstance();
    });

    it('EnumCache clearClass() clears only the specified class', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('EnumB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clearClass('EnumA');

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeTrue();
        EnumCache::resetInstance();
    });

    // ─── EnumManager: delegation with strict type checks ─────────────────

    it('EnumManager throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;
        expect(fn () => $manager->forSelect(PlainTestEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('EnumManager forSelect returns correct structure', function () {
        $manager = new EnumManager;
        $result = $manager->forSelect(UserStatus::class);

        expect($result)->not->toBeEmpty();
        foreach ($result as $item) {
            expect(array_key_exists('value', $item))->toBeTrue();
            expect(array_key_exists('label', $item))->toBeTrue();
        }
    });

    it('EnumManager fromName throws on invalid name', function () {
        $manager = new EnumManager;
        expect(fn () => $manager->fromName(UserStatus::class, 'NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('EnumManager tryFromName returns null for non-existent', function () {
        $manager = new EnumManager;
        expect($manager->tryFromName(UserStatus::class, 'NONEXISTENT'))->toBeNull();
    });

    // ─── EnumMetadataResolver: cache isolation ──────────────────────────

    it('EnumMetadataResolver::invalidate clears only the target class', function () {
        EnumCache::resetInstance();
        EnumCache::getInstance()->setTtl(300);

        // Resolve metadata for two classes
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        // Invalidate one
        EnumMetadataResolver::invalidate(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
        EnumCache::resetInstance();
    });

    it('EnumMetadataResolver::invalidateAll clears everything', function () {
        EnumCache::resetInstance();
        EnumCache::getInstance()->setTtl(300);

        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
        EnumCache::resetInstance();
    });

    // ─── EnumCast: strict type validation ───────────────────────────────

    it('EnumCast get() returns null for non-matching value', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->get(new \stdClass, 'status', 'nonexistent_value', []);
        expect($result)->toBeNull();
    });

    it('EnumCast get() returns null for null input', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->get(new \stdClass, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('EnumCast set() throws for wrong enum class', function () {
        $cast = new EnumCast(UserStatus::class);
        expect(fn () => $cast->set(new \stdClass, 'status', Priority::LOW, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('EnumCast set() throws for invalid string value', function () {
        $cast = new EnumCast(UserStatus::class);
        expect(fn () => $cast->set(new \stdClass, 'status', 'invalid_status_value_xyz', []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('EnumCast serialize() returns backed value for enum instance', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', UserStatus::ACTIVE, []);
        expect($result)->toBe('active');
    });

    it('EnumCast serialize() returns null for null', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', null, []);
        expect($result)->toBeNull();
    });

    // ─── Single case enum edge case ─────────────────────────────────────

    it('single case enum works correctly with all trait methods', function () {
        expect(SingleCaseToggle::cases())->toHaveCount(1);

        $case = SingleCaseToggle::ENABLED;
        expect(is_string($case->label()))->toBeTrue();
        expect(is_string($case->color()))->toBeTrue();
        expect($case->is(SingleCaseToggle::ENABLED))->toBeTrue();
        expect($case->isNot(SingleCaseToggle::ENABLED))->toBeFalse();
        expect($case->in([SingleCaseToggle::ENABLED]))->toBeTrue();
        expect($case->notIn([SingleCaseToggle::ENABLED]))->toBeFalse();
        expect(SingleCaseToggle::tryFromName('ENABLED'))->toBe(SingleCaseToggle::ENABLED);
        expect(SingleCaseToggle::hasCase('ENABLED'))->toBeTrue();
    });

    // ─── Int-backed enum with zero value ────────────────────────────────

    it('int-backed enum with zero value resolves metadata correctly', function () {
        $zero = ZeroBackedPriority::NONE;
        expect($zero->value)->toBe(0);
        expect(is_string($zero->label()))->toBeTrue();
        expect(is_string($zero->color()))->toBeTrue();
        expect(ZeroBackedPriority::tryFrom(0))->toBe(ZeroBackedPriority::NONE);
    });

    // ─── CamelCase enum ──────────────────────────────────────────────────

    it('camelCase enum generates label from camelCase', function () {
        $case = CamelCasePriority::pendingReview;
        // Per-case #[Label('Awaiting Approval')] overrides class-level label
        expect($case->label())->toBe('Awaiting Approval');
        expect($case->name)->toBe('pendingReview');
    });

    it('camelCase enum resolves class-level label for cases without per-case label', function () {
        $case = CamelCasePriority::active;
        // Class-level EnumLabel maps 'active' → 'Online'
        expect($case->label())->toBe('Online');
    });

    // ─── Empty defaults enum ───────────────────────────────────────────

    it('empty defaults enum returns secondary color and null icon', function () {
        foreach (EmptyDefaultsStatus::cases() as $case) {
            expect($case->color())->toBe('secondary');
            expect($case->icon())->toBeNull();
            expect($case->description())->toBeNull();
        }
    });

    // ─── AllClassLevel enum ──────────────────────────────────────────────

    it('all-class-level enum resolves labels from class attribute', function () {
        foreach (AllClassLevelEnum::cases() as $case) {
            $label = $case->label();
            expect(is_string($label))->toBeTrue();
            expect($label)->not->toBeEmpty();
        }
    });

    // ─── InvalidEnumException ───────────────────────────────────────────

    it('InvalidEnumException::forName() produces consistent message format', function () {
        $e = InvalidEnumException::forName(UserStatus::class, 'BAD_NAME');
        expect($e->getMessage())->toContain('BAD_NAME');
        expect($e->getMessage())->toContain(UserStatus::class);
        expect((string) $e)->toContain('InvalidEnumException');
    });

    it('InvalidEnumException::value() handles null value', function () {
        $e = InvalidEnumException::value(UserStatus::class, null);
        expect($e->getMessage())->toContain('null');
    });

    it('InvalidEnumException::value() handles int value', function () {
        $e = InvalidEnumException::value(IntBackedPriority::class, 42);
        expect($e->getMessage())->toContain('42');
    });
});
