<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use BackedEnum;
use UnitEnum;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * V55 strict type safety and return type contract tests.
 *
 * Validates that all public API methods return the exact types declared
 * * in their signatures. No `mixed` leaks. No `null` where `string` is declared.
 * * Tests the PHPStan Level 9 contract from a runtime perspective.
 */
describe('V55 Strict Type Safety & Return Contract', function (): void {
    // -----------------------------------------------------------------------
    // String-backed enum: UserStatus
    // -----------------------------------------------------------------------
    describe('UserStatus (string-backed) return types', function (): void {
        it('label() returns strictly a string (never null)', function (): void {
            foreach (UserStatus::cases() as $case) {
                $label = $case->label();
                expect($label)->toBeString();
            }
        });

        it('color() returns strictly a string (never null, defaults to secondary)', function (): void {
            foreach (UserStatus::cases() as $case) {
                $color = $case->color();
                expect($color)->toBeString();
                expect($color)->not->toBeEmpty();
            }
        });

        it('icon() returns string|null (nullable)', function (): void {
            $hasNull = false;
            $hasString = false;

            foreach (UserStatus::cases() as $case) {
                $icon = $case->icon();
                if ($icon === null) {
                    $hasNull = true;
                } else {
                    expect($icon)->toBeString();
                    $hasString = true;
                }
            }

            // At least one case has an icon and one does not in the fixture
            expect($hasString)->toBeTrue();
            expect($hasNull)->toBeTrue();
        });

        it('description() returns string|null (nullable)', function (): void {
            $hasNull = false;
            $hasString = false;

            foreach (UserStatus::cases() as $case) {
                $desc = $case->description();
                if ($desc === null) {
                    $hasNull = true;
                } else {
                    expect($desc)->toBeString();
                    $hasString = true;
                }
            }

            expect($hasString)->toBeTrue();
            expect($hasNull)->toBeTrue();
        });

        it('toValue() returns int|string — specifically string for string-backed', function (): void {
            foreach (UserStatus::cases() as $case) {
                $value = $case->toValue();
                expect($value)->toBeString();
            }
        });

        it('is() returns bool for both instance and string arg', function (): void {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeBool();
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeBool();
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('BANNED'))->toBeBool();
            expect(UserStatus::ACTIVE->is('BANNED'))->toBeFalse();
        });

        it('isNot() returns bool', function (): void {
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
            expect(UserStatus::ACTIVE->isNot('BANNED'))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot('ACTIVE'))->toBeFalse();
        });

        it('in() returns bool with empty array', function (): void {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['ACTIVE']))->toBeTrue();
        });

        it('notIn() returns bool with empty array', function (): void {
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn([UserStatus::ACTIVE]))->toBeFalse();
        });

        it('forSelect() returns list with string values for string-backed enum', function (): void {
            $select = UserStatus::forSelect();

            expect($select)->toBeArray();
            expect(count($select))->toBe(count(UserStatus::cases()));

            foreach ($select as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
                expect($option['label'])->not->toBeEmpty();
            }
        });

        it('forApi() returns list with full metadata shape', function (): void {
            $api = UserStatus::forApi();

            foreach ($api as $item) {
                expect($item)->toBeArray();
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
                expect($item['color'])->toBeString();
                // description and icon are nullable
                expect($item['description'])->toBeNull()->or()->toBeString();
                expect($item['icon'])->toBeNull()->or()->toBeString();
            }
        });

        it('values() returns list of strings for string-backed enum', function (): void {
            $values = UserStatus::values();

            foreach ($values as $v) {
                expect($v)->toBeString();
            }
        });

        it('labels() returns list of non-empty strings', function (): void {
            $labels = UserStatus::labels();

            foreach ($labels as $l) {
                expect($l)->toBeString();
                expect($l)->not->toBeEmpty();
            }
        });

        it('tryFromName() returns null|static', function (): void {
            $result = UserStatus::tryFromName('ACTIVE');
            expect($result)->toBeInstanceOf(UserStatus::class);

            $null = UserStatus::tryFromName('NONEXISTENT');
            expect($null)->toBeNull();
        });

        it('fromName() returns static or throws InvalidEnumException', function (): void {
            $result = UserStatus::fromName('ACTIVE');
            expect($result)->toBeInstanceOf(UserStatus::class);
            expect($result->name)->toBe('ACTIVE');

            expect(fn () => UserStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase() returns bool', function (): void {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('active'))->toBeFalse(); // case-sensitive
            expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
        });

        it('tryFromLabel() is case-insensitive and returns null|static', function (): void {
            $result1 = UserStatus::tryFromLabel('Active User');
            expect($result1)->toBeInstanceOf(UserStatus::class);
            expect($result1?->name)->toBe('ACTIVE');

            $result2 = UserStatus::tryFromLabel('active user');
            expect($result2)->toBeInstanceOf(UserStatus::class);
            expect($result2?->name)->toBe('ACTIVE');

            $result3 = UserStatus::tryFromLabel('ACTIVE USER');
            expect($result3)->toBeInstanceOf(UserStatus::class);

            expect(UserStatus::tryFromLabel('Nonexistent Label'))->toBeNull();
        });
    });

    // -----------------------------------------------------------------------
    // Int-backed enum: Priority
    // -----------------------------------------------------------------------
    describe('Priority (int-backed) return types', function (): void {
        it('toValue() returns int for int-backed enum', function (): void {
            foreach (Priority::cases() as $case) {
                $value = $case->toValue();
                expect($value)->toBeInt();
            }
        });

        it('values() returns list of ints', function (): void {
            $values = Priority::values();

            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('forSelect() values are ints, not strings', function (): void {
            $select = Priority::forSelect();

            foreach ($select as $option) {
                expect($option['value'])->toBeInt();
                expect($option['label'])->toBeString();
            }
        });

        it('forApi() values are ints', function (): void {
            $api = Priority::forApi();

            foreach ($api as $item) {
                expect($item['value'])->toBeInt();
                expect($item['name'])->toBeString();
            }
        });
    });

    // -----------------------------------------------------------------------
    // Int-backed with full attributes: IntBackedPriority
    // -----------------------------------------------------------------------
    describe('IntBackedPriority (int-backed with attributes) type safety', function (): void {
        it('class-level EnumLabel resolves with int keys', function (): void {
            expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
            expect(IntBackedPriority::HIGH->label())->toBe('High Priority'); // per-case override
            expect(IntBackedPriority::LOW->label())->toBe('Low Priority');
            expect(IntBackedPriority::NONE->label())->toBe('None'); // auto-generated
        });

        it('class-level EnumColor resolves with int keys', function (): void {
            expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
            expect(IntBackedPriority::HIGH->color())->toBe('warning');
            expect(IntBackedPriority::LOW->color())->toBe('success');
            expect(IntBackedPriority::NONE->color())->toBe('success');
        });

        it('class-level EnumIcon default applies to cases without per-case icon', function (): void {
            expect(IntBackedPriority::CRITICAL->icon())->toBe('heroicon-o-flag');
            expect(IntBackedPriority::HIGH->icon())->toBe('heroicon-o-flag');
            expect(IntBackedPriority::LOW->icon())->toBe('heroicon-o-flag');
            expect(IntBackedPriority::NONE->icon())->toBe('heroicon-o-flag');
        });

        it('class-level EnumDescription resolves with int keys', function (): void {
            expect(IntBackedPriority::CRITICAL->description())->toBe('Critical priority — immediate action required');
            expect(IntBackedPriority::LOW->description())->toBe('Low priority — handle when convenient');
            expect(IntBackedPriority::HIGH->description())->toBeNull();
            expect(IntBackedPriority::NONE->description())->toBeNull();
        });

        it('toValue() returns int', function (): void {
            expect(IntBackedPriority::CRITICAL->toValue())->toBe(1);
            expect(IntBackedPriority::NONE->toValue())->toBe(4);
        });

        it('tryFromLabel resolves by label with int backing', function (): void {
            expect(IntBackedPriority::tryFromLabel('Critical Priority'))->toBe(IntBackedPriority::CRITICAL);
        });
    });

    // -----------------------------------------------------------------------
    // Pure enum: PureFeatureFlag
    // -----------------------------------------------------------------------
    describe('PureFeatureFlag (pure enum) return types', function (): void {
        it('toValue() returns string (case name) for pure enum', function (): void {
            foreach (PureFeatureFlag::cases() as $case) {
                $value = $case->toValue();
                expect($value)->toBeString();
                expect($value)->toBe($case->name);
            }
        });

        it('values() returns list of case name strings', function (): void {
            $values = PureFeatureFlag::values();
            $names = array_map(static fn (UnitEnum $c): string => $c->name, PureFeatureFlag::cases());

            expect($values)->toBe($names);
        });

        it('forSelect() uses case names as values', function (): void {
            $select = PureFeatureFlag::forSelect();

            foreach ($select as $i => $option) {
                expect($option['value'])->toBe(PureFeatureFlag::cases()[$i]->name);
                expect($option['label'])->toBeString();
            }
        });

        it('forApi() uses case names as values', function (): void {
            $api = PureFeatureFlag::forApi();

            foreach ($api as $i => $item) {
                expect($item['value'])->toBe(PureFeatureFlag::cases()[$i]->name);
            }
        });

        it('label() returns string for all cases', function (): void {
            foreach (PureFeatureFlag::cases() as $case) {
                expect($case->label())->toBeString();
            }
        });

        it('color() returns string (defaults to secondary for auto)', function (): void {
            foreach (PureFeatureFlag::cases() as $case) {
                expect($case->color())->toBeString();
            }
        });

        it('comparison methods work with pure enum instances', function (): void {
            expect(PureFeatureFlag::DARK_MODE->is(PureFeatureFlag::DARK_MODE))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->is('dark_mode'))->toBeFalse(); // case-sensitive
            expect(PureFeatureFlag::DARK_MODE->isNot(PureFeatureFlag::BETA_FEATURES))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->in([PureFeatureFlag::BETA_FEATURES, PureFeatureFlag::DARK_MODE]))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->notIn([PureFeatureFlag::BETA_FEATURES]))->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // Minimal enum: OrderStatus (no attributes)
    // -----------------------------------------------------------------------
    describe('OrderStatus (no attributes, auto-generated labels)', function (): void {
        it('all labels are auto-generated non-empty strings', function (): void {
            foreach (OrderStatus::cases() as $case) {
                $label = $case->label();
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        });

        it('all colors default to secondary', function (): void {
            foreach (OrderStatus::cases() as $case) {
                expect($case->color())->toBe('secondary');
            }
        });

        it('all icons and descriptions are null', function (): void {
            foreach (OrderStatus::cases() as $case) {
                expect($case->icon())->toBeNull();
                expect($case->description())->toBeNull();
            }
        });

        it('forSelect preserves declaration order', function (): void {
            $select = OrderStatus::forSelect();
            $expectedValues = array_map(
                static fn (UnitEnum $c): string|int => $c instanceof BackedEnum ? $c->value : $c->name,
                OrderStatus::cases(),
            );
            $actualValues = array_column($select, 'value');

            expect($actualValues)->toBe($expectedValues);
        });
    });

    // -----------------------------------------------------------------------
    // Infrastructure class contracts
    // -----------------------------------------------------------------------
    describe('EnumCache singleton contract', function (): void {
        beforeEach(function (): void {
            EnumCache::resetInstance();
        });

        afterEach(function (): void {
            EnumCache::resetInstance();
        });

        it('getInstance() returns same instance', function (): void {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b); // same object identity
        });

        it('setTtl() clamps negative values to 0', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-100);

            expect($cache->getTtl())->toBe(0);
        });

        it('has() returns false when TTL is 0', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('get() throws OutOfBoundsException when not cached', function (): void {
            expect(fn () => EnumCache::getInstance()->get('NonexistentEnum'))
                ->toThrow(\OutOfBoundsException::class);
        });

        it('clearClass() removes only the target class', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(OrderStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(OrderStatus::class))->toBeTrue();
        });

        it('flush() clears all entries', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('__debugInfo() returns array with expected keys', function (): void {
            $debug = EnumCache::getInstance()->__debugInfo();

            expect($debug)->toBeArray();
            expect($debug)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
            expect($debug['ttl'])->toBeInt();
            expect($debug['cachedClasses'])->toBeInt();
        });

        it('clone throws RuntimeException', function (): void {
            expect(fn () => clone EnumCache::getInstance())
                ->toThrow(\RuntimeException::class);
        });
    });

    // -----------------------------------------------------------------------
    // EnumManager delegation contract
    // -----------------------------------------------------------------------
    describe('EnumManager delegation contract', function (): void {
        it('forSelect() delegates correctly', function (): void {
            $manager = new EnumManager;
            $result = $manager->forSelect(UserStatus::class);
            $direct = UserStatus::forSelect();

            expect($result)->toBe($direct);
        });

        it('forApi() delegates correctly', function (): void {
            $manager = new EnumManager;
            $result = $manager->forApi(UserStatus::class);
            $direct = UserStatus::forApi();

            expect($result)->toBe($direct);
        });

        it('tryFromLabel() delegates correctly', function (): void {
            $manager = new EnumManager;
            $result = $manager->tryFromLabel(UserStatus::class, 'Active User');

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('tryFromName() delegates correctly', function (): void {
            $manager = new EnumManager;
            $result = $manager->tryFromName(UserStatus::class, 'ACTIVE');

            expect($result)->toBe(UserStatus::ACTIVE);
            expect($manager->tryFromName(UserStatus::class, 'NONEXISTENT'))->toBeNull();
        });

        it('fromName() throws on invalid name', function (): void {
            $manager = new EnumManager;

            expect(fn () => $manager->fromName(UserStatus::class, 'NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase() delegates correctly', function (): void {
            $manager = new EnumManager;

            expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
            expect($manager->hasCase(UserStatus::class, 'NONEXISTENT'))->toBeFalse();
        });

        it('values() delegates correctly', function (): void {
            $manager = new EnumManager;
            $result = $manager->values(UserStatus::class);

            expect($result)->toBe(UserStatus::values());
        });

        it('labels() delegates correctly', function (): void {
            $manager = new EnumManager;
            $result = $manager->labels(UserStatus::class);

            expect($result)->toBe(UserStatus::labels());
        });

        it('throws BadMethodCallException for enum without trait', function (): void {
            $manager = new EnumManager;

            // \stdClass is not an enum with HasEnumMetadata
            expect(fn () => $manager->forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });
    });

    // -----------------------------------------------------------------------
    // InvalidEnumException contract
    // -----------------------------------------------------------------------
    describe('InvalidEnumException contract', function (): void {
        it('forName() creates exception with correct message', function (): void {
            $e = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

            expect($e)->toBeInstanceOf(InvalidEnumException::class);
            expect($e->getMessage())->toContain('NONEXISTENT');
            expect($e->getMessage())->toContain(UserStatus::class);
        });

        it('value() creates exception with correct message for null', function (): void {
            $e = InvalidEnumException::value(UserStatus::class, null);

            expect($e->getMessage())->toContain('null');
        });

        it('value() creates exception with correct message for int', function (): void {
            $e = InvalidEnumException::value(Priority::class, 99);

            expect($e->getMessage())->toContain('99');
        });

        it('value() creates exception with correct message for string', function (): void {
            $e = InvalidEnumException::value(UserStatus::class, 'invalid');

            expect($e->getMessage())->toContain('invalid');
        });

        it('__toString() returns class name and message', function (): void {
            $e = InvalidEnumException::forName(UserStatus::class, 'X');

            $str = (string) $e;
            expect($str)->toStartWith(InvalidEnumException::class);
            expect($str)->toContain($e->getMessage());
        });
    });

    // -----------------------------------------------------------------------
    // EnumMetadataResolver invalidation
    // -----------------------------------------------------------------------
    describe('EnumMetadataResolver invalidation', function (): void {
        afterEach(function (): void {
            EnumCache::resetInstance();
        });

        it('invalidate() clears cache for specific class', function (): void {
            EnumCache::getInstance()->setTtl(300);

            // Resolve to populate cache
            EnumMetadataResolver::resolve(UserStatus::class);
            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

            EnumMetadataResolver::invalidate(UserStatus::class);
            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        });

        it('invalidateAll() clears all cached metadata', function (): void {
            EnumCache::getInstance()->setTtl(300);

            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(OrderStatus::class);

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
            expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeTrue();

            EnumMetadataResolver::invalidateAll();

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // Strict comparison edge cases
    // -----------------------------------------------------------------------
    describe('Strict comparison edge cases', function (): void {
        it('is() rejects loosely matching strings', function (): void {
            // 'active' is the VALUE, not the name — should not match
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
        });

        it('is() only matches exact case name string', function (): void {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
        });

        it('in() performs strict comparison on each element', function (): void {
            // 'active' (value) should not match ACTIVE (name)
            expect(UserStatus::ACTIVE->in(['active', 'pending']))->toBeFalse();
            expect(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
        });

        it('hasCase() is case-sensitive', function (): void {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('Active'))->toBeFalse();
            expect(UserStatus::hasCase('active'))->toBeFalse();
        });

        it('fromName() is case-sensitive', function (): void {
            expect(fn () => UserStatus::fromName('active'))->toThrow(InvalidEnumException::class);
            expect(fn () => UserStatus::fromName('Active'))->toThrow(InvalidEnumException::class);
            expect(UserStatus::fromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        });

        it('tryFromName() is case-sensitive', function (): void {
            expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromName('Active'))->toBeNull();
            expect(UserStatus::tryFromName('active'))->toBeNull();
        });

        it('tryFromLabel() is case-insensitive but whitespace-sensitive', function (): void {
            expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('Active  User'))->toBeNull(); // double space
        });

        it('comparison between different enum types throws no error (just returns false)', function (): void {
            // is() uses self type hint, but we test with string comparison
            // which is the string overload
            expect(UserStatus::ACTIVE->is('DARK_MODE'))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // Structural: class final/readonly/declare(strict_types=1) checks
    // -----------------------------------------------------------------------
    describe('Structural: class type declarations', function (): void {
        it('EnumCache is final', function (): void {
            $ref = new ReflectionClass(EnumCache::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('EnumManager is final and readonly', function (): void {
            $ref = new ReflectionClass(EnumManager::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('EnumRule is final and readonly', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Rules\EnumRule::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('EnumMetadataResolver is final', function (): void {
            $ref = new ReflectionClass(EnumMetadataResolver::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('InvalidEnumException is final', function (): void {
            $ref = new ReflectionClass(InvalidEnumException::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('EnumCast is final', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Casts\EnumCast::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('Enum facade is final', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('all source files have declare(strict_types=1)', function (): void {
            $srcDir = dirname(__DIR__, 2).'/src';
            $files = glob("{$srcDir}/**/*.php");

            expect($files)->not->toBeEmpty();

            foreach ($files as $file) {
                $content = file_get_contents($file);
                expect($content)->toContain('declare(strict_types=1)');
            }
        });

        it('all attribute classes are final', function (): void {
            $attributes = [
                \ZeroBoiler\Enums\Attributes\Label::class,
                \ZeroBoiler\Enums\Attributes\Color::class,
                \ZeroBoiler\Enums\Attributes\Icon::class,
                \ZeroBoiler\Enums\Attributes\Description::class,
                \ZeroBoiler\Enums\Attributes\EnumLabel::class,
                \ZeroBoiler\Enums\Attributes\EnumColor::class,
                \ZeroBoiler\Enums\Attributes\EnumIcon::class,
                \ZeroBoiler\Enums\Attributes\EnumDescription::class,
            ];

            foreach ($attributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);

                expect($ref->isFinal())->toBeTrue("{$attrClass} should be final");
            }
        });

        it('all attribute classes use readonly promoted properties', function (): void {
            $attributes = [
                \ZeroBoiler\Enums\Attributes\Label::class,
                \ZeroBoiler\Enums\Attributes\Color::class,
                \ZeroBoiler\Enums\Attributes\Icon::class,
                \ZeroBoiler\Enums\Attributes\Description::class,
            ];

            foreach ($attributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                $props = $ref->getProperties();

                foreach ($props as $prop) {
                    $name = $prop->getName();
                    $fqn = $attrClass.'::'.$name;
                    expect($prop->isReadOnly())->toBeTrue("{$fqn} should be readonly");
                    expect($prop->isPublic())->toBeTrue("{$fqn} should be public (promoted)");
                }
            }
        });
    });
});
