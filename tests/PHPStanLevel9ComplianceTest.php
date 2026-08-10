<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * PHPStan Level 9 strict type compliance verification.
 *
 * This test file documents and verifies that the enums package
 * meets PHPStan Level 9 requirements. Run with:
 *
 *   vendor/bin/phpstan analyse --level=9 src/
 *
 * Each test documents a specific PHPStan L9 rule compliance area.
 * These tests are structural assertions — they verify that the public API
 * has correct return types, parameter types, and no mixed types.
 *
 * @see https://phpstan.org/blog/introducing-phpstan-level-9
 */

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('PHPStan Level 9 Compliance', function (): void {

    // ──────────────────────────────────────────────────────────────
    // HasEnumMetadata trait — return type strictness
    // ──────────────────────────────────────────────────────────────

    describe('HasEnumMetadata return types', function (): void {
        it('label() returns string (never null, never mixed)', function (): void {
            $label = UserStatus::ACTIVE->label();

            // PHPStan L9: label() return type must be string
            assert(is_string($label));
            expect($label)->toBeString();
        });

        it('color() returns string (never null, never mixed)', function (): void {
            $color = UserStatus::ACTIVE->color();

            assert(is_string($color));
            expect($color)->toBeString();
        });

        it('description() returns string or null (explicit nullable return)', function (): void {
            $desc = UserStatus::ACTIVE->description();
            $nullDesc = UserStatus::INACTIVE->description();

            // PHPStan L9: nullable return type is explicit ?string
            expect($desc)->toBeString();
            expect($nullDesc)->toBeNull();
        });

        it('icon() returns string or null (explicit nullable return)', function (): void {
            $icon = UserStatus::ACTIVE->icon();
            $nullIcon = UserStatus::INACTIVE->icon();

            expect($icon)->toBeString();
            expect($nullIcon)->toBeNull();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Bulk method return types — strict array shape compliance
    // ──────────────────────────────────────────────────────────────

    describe('Bulk method type shapes', function (): void {
        it('forSelect() returns array with value+label keys', function (): void {
            $select = UserStatus::forSelect();

            expect($select)->toBeArray();
            foreach ($select as $item) {
                expect($item)->toBeArray();
                // PHPStan L9: each item has known shape {value: int|string, label: string}
                expect($item)->toHaveKeys(['value', 'label']);
                assert(is_int($item['value']) || is_string($item['value']));
                assert(is_string($item['label']));
            }
        });

        it('forApi() returns array with full metadata shape', function (): void {
            $api = UserStatus::forApi();

            expect($api)->toBeArray();
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                assert(is_int($item['value']) || is_string($item['value']));
                assert(is_string($item['name']));
                assert(is_string($item['label']));
                assert(is_string($item['color']));
                // description and icon are nullable
                assert($item['description'] === null || is_string($item['description']));
                assert($item['icon'] === null || is_string($item['icon']));
            }
        });

        it('values() returns list of int|string (not mixed)', function (): void {
            $stringValues = UserStatus::values();
            $intValues = Priority::values();

            foreach ($stringValues as $v) {
                assert(is_string($v), 'String-backed enum values must be strings');
            }
            foreach ($intValues as $v) {
                assert(is_int($v), 'Int-backed enum values must be ints');
            }
        });

        it('labels() returns list of strings (not mixed)', function (): void {
            $labels = UserStatus::labels();

            foreach ($labels as $l) {
                assert(is_string($l), 'Labels must be strings');
                expect($l)->not->toBeEmpty();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Lookup methods — strict parameter and return types
    // ──────────────────────────────────────────────────────────────

    describe('Lookup method type safety', function (): void {
        it('tryFromLabel() accepts string and returns static or null', function (): void {
            $result = UserStatus::tryFromLabel('Active User');

            expect($result === null || $result instanceof UserStatus)->toBeTrue();
        });

        it('tryFromName() accepts string and returns static or null', function (): void {
            $result = UserStatus::tryFromName('ACTIVE');

            assert($result instanceof UserStatus);
            expect($result->name)->toBe('ACTIVE');

            $nullResult = UserStatus::tryFromName('NONEXISTENT');
            expect($nullResult)->toBeNull();
        });

        it('fromName() throws InvalidEnumException for unknown names', function (): void {
            expect(fn () => UserStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase() returns bool (not mixed)', function (): void {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Comparison methods — strict type union parameters
    // ──────────────────────────────────────────────────────────────

    describe('Comparison method types', function (): void {
        it('is() accepts static|string and returns bool', function (): void {
            $case = UserStatus::ACTIVE;

            // Instance comparison
            expect($case->is(UserStatus::ACTIVE))->toBeTrue();
            expect($case->is(UserStatus::BANNED))->toBeFalse();

            // String name comparison
            expect($case->is('ACTIVE'))->toBeTrue();
            expect($case->is('BANNED'))->toBeFalse();
        });

        it('isNot() accepts static|string and returns bool', function (): void {
            $case = UserStatus::ACTIVE;

            expect($case->isNot(UserStatus::BANNED))->toBeTrue();
            expect($case->isNot(UserStatus::ACTIVE))->toBeFalse();
        });

        it('in() accepts array<static|string> and returns bool', function (): void {
            $case = UserStatus::ACTIVE;

            // All instances
            expect($case->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect($case->in([UserStatus::BANNED, UserStatus::SUSPENDED]))->toBeFalse();

            // All strings
            expect($case->in(['ACTIVE', 'PENDING']))->toBeTrue();
            expect($case->in(['BANNED', 'SUSPENDED']))->toBeFalse();

            // Mixed
            expect($case->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumCache — singleton lifecycle and type safety
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache type safety', function (): void {
        it('getInstance() returns EnumCache (not mixed)', function (): void {
            $cache = EnumCache::getInstance();

            assert($cache instanceof EnumCache);
        });

        it('has() returns bool', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();

            expect($cache->has('NonexistentEnum'))->toBeFalse();
        });

        it('get() throws OutOfBoundsException when no entry exists', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();

            expect(fn () => $cache->get('NonexistentEnum'))
                ->toThrow(\OutOfBoundsException::class);
        });

        it('set() and get() round-trip correctly typed metadata', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();

            $metadata = [
                'labels' => ['active' => 'Active'],
                'descriptions' => ['active' => 'Active status'],
                'colors' => ['active' => 'success'],
                'icons' => ['active' => 'heroicon-o-check'],
            ];

            $cache->set('TestEnum', $metadata);
            expect($cache->has('TestEnum'))->toBeTrue();

            $retrieved = $cache->get('TestEnum');
            expect($retrieved)->toBe($metadata);

            $cache->clear();
        });

        it('setTtl/getTtl return consistent int values', function (): void {
            $cache = EnumCache::getInstance();

            $cache->setTtl(60);
            expect($cache->getTtl())->toBe(60);

            $cache->setTtl(-5);
            expect($cache->getTtl())->toBe(0); // negative normalized to 0
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumManager — runtime delegation type safety
    // ──────────────────────────────────────────────────────────────

    describe('EnumManager type safety', function (): void {
        it('forSelect() returns typed array', function (): void {
            $manager = new EnumManager;
            $select = $manager->forSelect(UserStatus::class);

            expect($select)->toBeArray();
            expect($select[0])->toHaveKeys(['value', 'label']);
        });

        it('forSelect() throws BadMethodCallException for non-enum', function (): void {
            $manager = new EnumManager;

            expect(fn () => $manager->forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('forApi() returns typed array', function (): void {
            $manager = new EnumManager;
            $api = $manager->forApi(UserStatus::class);

            expect($api)->toBeArray();
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('tryFromLabel() returns UnitEnum or null', function (): void {
            $manager = new EnumManager;
            $case = $manager->tryFromLabel(UserStatus::class, 'Active User');

            assert($case instanceof UserStatus || $case === null);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumRule — validation rule type safety
    // ──────────────────────────────────────────────────────────────

    describe('EnumRule type safety', function (): void {
        it('for() returns EnumRule instance (not mixed)', function (): void {
            $rule = EnumRule::for(UserStatus::class);

            assert($rule instanceof EnumRule);
        });

        it('nullable() returns new EnumRule with nullable flag', function (): void {
            $rule = EnumRule::for(UserStatus::class)->nullable();

            assert($rule instanceof EnumRule);
        });

        it('validate() handles string values for string-backed enum', function (): void {
            $rule = EnumRule::for(UserStatus::class);
            $fail = fn () => null;
            $passed = true;

            $rule->validate('status', 'active', function () use (&$passed): void {
                $passed = false;
            });

            expect($passed)->toBeTrue();
        });

        it('validate() handles int values for int-backed enum', function (): void {
            $rule = EnumRule::for(Priority::class);
            $passed = true;

            $rule->validate('priority', 1, function () use (&$passed): void {
                $passed = false;
            });

            expect($passed)->toBeTrue();
        });

        it('validate() rejects wrong PHP type for int-backed enum', function (): void {
            $rule = EnumRule::for(Priority::class);
            $failed = false;

            $rule->validate('priority', 'not-an-int', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // InvalidEnumException — factory method return types
    // ──────────────────────────────────────────────────────────────

    describe('InvalidEnumException factory types', function (): void {
        it('value() creates exception with string/int/null value', function (): void {
            $e = InvalidEnumException::value(UserStatus::class, 'invalid');
            assert($e instanceof InvalidEnumException);
            expect($e->getMessage())->toContain('invalid');

            $e2 = InvalidEnumException::value(UserStatus::class, null);
            expect($e2->getMessage())->toContain('null');

            $e3 = InvalidEnumException::value(Priority::class, 99);
            expect($e3->getMessage())->toContain('99');
        });

        it('forName() creates exception with case name', function (): void {
            $e = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

            assert($e instanceof InvalidEnumException);
            expect($e->getMessage())->toContain('NONEXISTENT');
            expect($e->getMessage())->toContain(UserStatus::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Cross-type compatibility — all three enum types work identically
    // ──────────────────────────────────────────────────────────────

    describe('Cross-type enum compatibility', function (): void {
        it('string-backed enum has full API', function (): void {
            expect(UserStatus::ACTIVE->label())->toBeString();
            expect(UserStatus::ACTIVE->color())->toBeString();
            expect(UserStatus::forSelect())->toBeArray();
            expect(UserStatus::values())->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);
        });

        it('int-backed enum has full API', function (): void {
            expect(Priority::LOW->label())->toBeString();
            expect(Priority::LOW->color())->toBeString();
            expect(Priority::forSelect())->toBeArray();
            expect(Priority::values())->toBe([1, 2, 3, 4]);
        });

        it('pure enum uses case names as values', function (): void {
            expect(PureFeatureFlag::DARK_MODE->label())->toBeString();
            expect(PureFeatureFlag::DARK_MODE->color())->toBeString();
            expect(PureFeatureFlag::forSelect())->toBeArray();
            expect(PureFeatureFlag::values())->toBe(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
        });

        it('int-backed with class-level EnumColor resolves correctly', function (): void {
            expect(IntStatusWithColor::ACTIVE->color())->toBe('success');
            expect(IntStatusWithColor::ACTIVE->value)->toBe(1);
            expect(IntStatusWithColor::BANNED->color())->toBe('danger');
            expect(IntStatusWithColor::BANNED->value)->toBe(3);
        });

        it('mixed attributes resolve with correct priority', function (): void {
            // Class-level label from EnumLabel::labels
            expect(MixedAttributeStatus::NEW->label())->toBe('Brand New Item');

            // Class-level color from EnumColor
            expect(MixedAttributeStatus::ACTIVE->color())->toBe('success');

            // Class-level description from EnumDescription
            expect(MixedAttributeStatus::ACTIVE->description())->toBe('Currently active');

            // Default icon from EnumIcon
            expect(MixedAttributeStatus::ACTIVE->icon())->toBe('heroicon-o-document');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // No mixed types — explicit type assertions
    // ──────────────────────────────────────────────────────────────

    describe('No mixed types in public API', function (): void {
        it('EnumMetadataResolver::resolve() returns shaped array', function (): void {
            EnumMetadataResolver::invalidate(UserStatus::class);
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
            expect($meta['labels'])->toBeArray();
            expect($meta['descriptions'])->toBeArray();
            expect($meta['colors'])->toBeArray();
            expect($meta['icons'])->toBeArray();
        });

        it('EnumCache methods have no mixed return types', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();

            // has() → bool
            $result = $cache->has('Test');
            assert(is_bool($result));

            // getTtl() → int
            assert(is_int($cache->getTtl()));
        });
    });
});
