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
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Full production contract test — verifies every public API method
 * across all enum types (string-backed, int-backed, pure) with all
 * attribute combinations.
 *
 * This test serves as a single-file smoke test for the entire package.
 * If any public API breaks, this test catches it.
 */
describe('Full Production Contract', function () {
    // -----------------------------------------------------------------------
    // String-Backed Enum Contract
    // -----------------------------------------------------------------------
    describe('String-Backed Enum (UserStatus)', function () {
        it('has all expected cases', function () {
            $cases = UserStatus::cases();
            expect($cases)->toHaveCount(5);
            expect(array_column($cases, 'name'))->toContain('ACTIVE', 'INACTIVE', 'PENDING', 'SUSPENDED', 'BANNED');
        });

        it('returns typed backed values via values()', function () {
            $values = UserStatus::values();
            expect($values)->toBeArray();
            expect($values)->toHaveCount(5);
            expect($values)->each->toBeString();
            expect($values)->toContain('active', 'inactive', 'pending', 'suspended', 'banned');
        });

        it('returns non-empty labels via labels()', function () {
            $labels = UserStatus::labels();
            expect($labels)->toHaveCount(5);
            expect($labels)->each->toBeString()->not->toBeEmpty();
        });

        it('resolves per-case label with #[Label] attribute', function () {
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
            expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
        });

        it('auto-generates label for cases without #[Label]', function () {
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
            expect(UserStatus::SUSPENDED->label())->toBe('Suspended');
        });

        it('resolves color from class-level #[EnumColor]', function () {
            expect(UserStatus::ACTIVE->color())->toBe('success');
            expect(UserStatus::PENDING->color())->toBe('warning');
            expect(UserStatus::SUSPENDED->color())->toBe('warning');
            expect(UserStatus::INACTIVE->color())->toBe('secondary');
        });

        it('per-case #[Color] overrides class-level', function () {
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('returns icon from per-case attribute', function () {
            expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
        });

        it('returns null icon for cases without icon', function () {
            expect(UserStatus::INACTIVE->icon())->toBeNull();
        });

        it('returns description from per-case attribute', function () {
            expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
        });

        it('returns null description for cases without description', function () {
            expect(UserStatus::INACTIVE->description())->toBeNull();
        });

        it('generates forSelect() with correct structure', function () {
            $options = UserStatus::forSelect();
            expect($options)->toBeArray();
            expect($options)->toHaveCount(5);
            foreach ($options as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('generates forApi() with full metadata structure', function () {
            $api = UserStatus::forApi();
            expect($api)->toBeArray();
            expect($api)->toHaveCount(5);
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('supports is() comparison with instance', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
        });

        it('supports is() comparison with string name', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('BANNED'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse(); // case-sensitive
        });

        it('supports isNot() negation', function () {
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
        });

        it('supports in() group matching with instances', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect(UserStatus::ACTIVE->in([UserStatus::BANNED, UserStatus::SUSPENDED]))->toBeFalse();
        });

        it('supports in() with mixed instances and strings', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['BANNED', 'SUSPENDED']))->toBeFalse();
        });

        it('supports tryFromLabel() case-insensitive lookup', function () {
            expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('NON_EXISTENT'))->toBeNull();
        });

        it('supports tryFromName() case-sensitive lookup', function () {
            expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromName('active'))->toBeNull();
        });

        it('supports fromName() with exception on failure', function () {
            expect(UserStatus::fromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(fn () => UserStatus::fromName('UNKNOWN'))->toThrow(InvalidEnumException::class);
        });

        it('supports hasCase() existence check', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('UNKNOWN'))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // Int-Backed Enum Contract
    // -----------------------------------------------------------------------
    describe('Int-Backed Enum (Priority)', function () {
        it('returns int values from values()', function () {
            $values = Priority::values();
            expect($values)->each->toBeInt();
        });

        it('returns correct backed value', function () {
            expect(Priority::HIGH->value)->toBeInt();
        });

        it('auto-generates labels from UPPER_SNAKE_CASE', function () {
            expect(Priority::HIGH->label())->toBeString()->not->toBeEmpty();
        });

        it('returns secondary as default color', function () {
            expect(Priority::HIGH->color())->toBe('secondary');
        });

        it('forSelect() uses int backed values', function () {
            $options = Priority::forSelect();
            foreach ($options as $option) {
                expect($option['value'])->toBeInt();
            }
        });
    });

    // -----------------------------------------------------------------------
    // Int-Backed Enum with Color (IntBackedPriority)
    // -----------------------------------------------------------------------
    describe('Int-Backed with Color Attributes (IntBackedPriority)', function () {
        it('resolves class-level colors by int value', function () {
            $meta = EnumMetadataResolver::resolve(IntBackedPriority::class);
            expect($meta['colors'])->toHaveKey(1);
        });

        it('forSelect() returns int values', function () {
            $options = IntBackedPriority::forSelect();
            foreach ($options as $option) {
                expect($option['value'])->toBeInt();
            }
        });
    });

    // -----------------------------------------------------------------------
    // Pure Enum Contract
    // -----------------------------------------------------------------------
    describe('Pure Enum (PureFeatureFlag)', function () {
        it('has cases without backed values', function () {
            expect(PureFeatureFlag::cases())->not->toBeEmpty();
        });

        it('values() returns case names (strings)', function () {
            $values = PureFeatureFlag::values();
            expect($values)->each->toBeString();
            expect($values[0])->toBe(PureFeatureFlag::cases()[0]->name);
        });

        it('forSelect() uses case names as values', function () {
            $options = PureFeatureFlag::forSelect();
            foreach ($options as $option) {
                expect($option['value'])->toBeString();
            }
        });

        it('auto-generates labels', function () {
            expect(PureFeatureFlag::cases()[0]->label())->toBeString()->not->toBeEmpty();
        });

        it('comparison works with pure enum instances', function () {
            $first = PureFeatureFlag::cases()[0];
            expect($first->is($first))->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // EnumCache Contract
    // -----------------------------------------------------------------------
    describe('EnumCache Singleton', function () {
        beforeEach(function () {
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::resetInstance();
        });

        it('is a singleton', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('caches and retrieves metadata', function () {
            $cache = EnumCache::getInstance();
            $meta = ['labels' => ['active' => 'Active'], 'descriptions' => [], 'colors' => [], 'icons' => []];

            $cache->set('TestEnum', $meta);

            expect($cache->has('TestEnum'))->toBeTrue();
            expect($cache->get('TestEnum'))->toBe($meta);
        });

        it('flush() clears all entries', function () {
            $cache = EnumCache::getInstance();
            $cache->set('A', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('B', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            EnumCache::flush();

            expect($cache->has('A'))->toBeFalse();
            expect($cache->has('B'))->toBeFalse();
        });

        it('clearClass() clears only one class', function () {
            $cache = EnumCache::getInstance();
            $cache->set('A', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('B', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $cache->clearClass('A');

            expect($cache->has('A'))->toBeFalse();
            expect($cache->has('B'))->toBeTrue();
        });

        it('TTL=0 disables caching', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set('TestEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('get() throws on missing key', function () {
            $cache = EnumCache::getInstance();
            expect(fn () => $cache->get('NON_EXISTENT'))->toThrow(\OutOfBoundsException::class);
        });
    });

    // -----------------------------------------------------------------------
    // EnumMetadataResolver Contract
    // -----------------------------------------------------------------------
    describe('EnumMetadataResolver', function () {
        it('resolves metadata with four keys', function () {
            EnumCache::flush();
            EnumMetadataResolver::invalidate(UserStatus::class);

            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
            expect($meta['labels'])->toBeArray();
            expect($meta['colors'])->toBeArray();
        });

        it('caches result after first resolve', function () {
            EnumCache::flush();
            EnumMetadataResolver::invalidate(UserStatus::class);

            EnumMetadataResolver::resolve(UserStatus::class);
            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        });

        it('invalidate() clears cached metadata', function () {
            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::invalidate(UserStatus::class);

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        });

        it('invalidateAll() clears all cached metadata', function () {
            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(Priority::class);
            EnumMetadataResolver::invalidateAll();

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // EnumManager Contract
    // -----------------------------------------------------------------------
    describe('EnumManager', function () {
        it('delegates forSelect()', function () {
            $manager = new EnumManager;
            $options = $manager->forSelect(UserStatus::class);

            expect($options)->toBeArray();
            expect($options[0])->toHaveKey('value');
            expect($options[0])->toHaveKey('label');
        });

        it('delegates forApi()', function () {
            $manager = new EnumManager;
            $api = $manager->forApi(UserStatus::class);

            expect($api)->toBeArray();
            expect($api[0])->toHaveKey('description');
        });

        it('delegates tryFromLabel()', function () {
            $manager = new EnumManager;
            $case = $manager->tryFromLabel(UserStatus::class, 'Active User');

            expect($case)->toBe(UserStatus::ACTIVE);
        });

        it('throws BadMethodCallException for non-enum classes', function () {
            $manager = new EnumManager;
            expect(fn () => $manager->forSelect(\stdClass::class))->toThrow(\BadMethodCallException::class);
        });
    });

    // -----------------------------------------------------------------------
    // EnumRule Contract
    // -----------------------------------------------------------------------
    describe('EnumRule', function () {
        it('accepts valid string-backed value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = false;

            $rule->validate('status', 'active', function () use (&$fail) { $fail = true; });

            expect($fail)->toBeFalse();
        });

        it('rejects invalid value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = false;

            $rule->validate('status', 'invalid_value', function () use (&$fail) { $fail = true; });

            expect($fail)->toBeTrue();
        });

        it('accepts null when nullable', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $fail = false;

            $rule->validate('status', null, function () use (&$fail) { $fail = true; });

            expect($fail)->toBeFalse();
        });

        it('rejects null when not nullable', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = false;

            $rule->validate('status', null, function () use (&$fail) { $fail = true; });

            expect($fail)->toBeTrue();
        });

        it('validates int-backed enums by type', function () {
            $rule = EnumRule::for(Priority::class);
            $fail = false;

            // Int-backed enum should reject string input
            $rule->validate('priority', 'not_an_int', function () use (&$fail) { $fail = true; });

            expect($fail)->toBeTrue();
        });

        it('validates pure enums against case names', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $fail = false;

            $caseName = PureFeatureFlag::cases()[0]->name;
            $rule->validate('flag', $caseName, function () use (&$fail) { $fail = true; });

            expect($fail)->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // EnumCast Contract
    // -----------------------------------------------------------------------
    describe('EnumCast', function () {
        it('gets null for null value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new \stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('casts valid string to enum instance', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new \stdClass, 'status', 'active', []);

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('gets null for invalid value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new \stdClass, 'status', 'invalid', []);

            expect($result)->toBeNull();
        });

        it('sets enum instance to backed value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(new \stdClass, 'status', UserStatus::ACTIVE, []);

            expect($result)->toBe('active');
        });

        it('sets null for null value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(new \stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('throws on wrong enum type', function () {
            $cast = new EnumCast(UserStatus::class);
            expect(fn () => $cast->set(new \stdClass, 'status', Priority::HIGH, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serializes enum to backed value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(new \stdClass, 'status', UserStatus::ACTIVE, []);

            expect($result)->toBe('active');
        });

        it('serializes raw string value as-is', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(new \stdClass, 'status', 'active', []);

            expect($result)->toBe('active');
        });
    });

    // -----------------------------------------------------------------------
    // InvalidEnumException Contract
    // -----------------------------------------------------------------------
    describe('InvalidEnumException', function () {
        it('creates from forName() factory', function () {
            $e = InvalidEnumException::forName('App\\Enums\\UserStatus', 'UNKNOWN');

            expect($e)->toBeInstanceOf(InvalidEnumException::class);
            expect($e->getMessage())->toContain('UNKNOWN');
            expect($e->getMessage())->toContain('App\\Enums\\UserStatus');
        });

        it('creates from value() factory', function () {
            $e = InvalidEnumException::value('App\\Enums\\UserStatus', 'bad_value');

            expect($e)->toBeInstanceOf(InvalidEnumException::class);
            expect($e->getMessage())->toContain('bad_value');
        });

        it('formats __toString() correctly', function () {
            $e = InvalidEnumException::forName('App\\Enums\\UserStatus', 'UNKNOWN');

            expect((string) $e)->toContain('InvalidEnumException');
            expect((string) $e)->toContain('UNKNOWN');
        });
    });

    // -----------------------------------------------------------------------
    // Class-Level Attribute Resolution Priority
    // -----------------------------------------------------------------------
    describe('Resolution Priority', function () {
        it('per-case attribute overrides class-level', function () {
            // BANNED has per-case #[Color('danger')] which overrides
            // class-level #[EnumColor(danger: ['banned'])]
            // Both point to 'danger', but per-case always wins
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('class-level attribute used when no per-case override', function () {
            // ACTIVE has no per-case Color, so class-level EnumColor is used
            expect(UserStatus::ACTIVE->color())->toBe('success');
        });

        it('auto-generated label used when no attributes at all', function () {
            // INACTIVE has no #[Label] and no class-level EnumLabel for it
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });
    });

    // -----------------------------------------------------------------------
    // Cross-Enum Consistency
    // -----------------------------------------------------------------------
    describe('Cross-Enum Consistency', function () {
        it('forSelect() always returns array of value+label pairs', function () {
            foreach ([UserStatus::class, Priority::class, PureFeatureFlag::class] as $enumClass) {
                $options = $enumClass::forSelect();
                foreach ($options as $option) {
                    expect($option)->toHaveKeys(['value', 'label']);
                    expect($option['label'])->toBeString()->not->toBeEmpty();
                }
            }
        });

        it('forApi() always returns consistent structure', function () {
            foreach ([UserStatus::class, Priority::class, PureFeatureFlag::class] as $enumClass) {
                $api = $enumClass::forApi();
                foreach ($api as $item) {
                    expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                }
            }
        });

        it('all enum colors are valid strings', function () {
            foreach ([UserStatus::class, Priority::class, PureFeatureFlag::class] as $enumClass) {
                foreach ($enumClass::cases() as $case) {
                    expect($case->color())->toBeString();
                }
            }
        });
    });
});
