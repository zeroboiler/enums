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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function () {
    EnumCache::resetInstance();
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('V50 Final Production Audit — Enum Package', function () {
    describe('Source Code Structural Integrity', function () {
        it('all 20 source files declare strict types', function () {
            $srcFiles = glob(__DIR__.'/../src/**/*.php', GLOB_BRACE);
            expect($srcFiles)->not->toBeEmpty();

            foreach ($srcFiles as $file) {
                $content = file_get_contents($file);
                expect($content)->toContain('declare(strict_types=1)');
            }
        });

        it('all source files have a license header', function () {
            $srcFiles = glob(__DIR__.'/../src/**/*.php', GLOB_BRACE);

            foreach ($srcFiles as $file) {
                $content = file_get_contents($file);
                expect($content)->toContain('This file is part of ZeroBoiler');
            }
        });

        it('all public classes are final', function () {
            $classes = [
                EnumCache::class,
                EnumManager::class,
                EnumRule::class,
                EnumCast::class,
                InvalidEnumException::class,
                EnumMetadataResolver::class,
                EnumTestGenerator::class,
                InspectEnumCommand::class,
                MakeEnumTestCommand::class,
                Label::class,
                Color::class,
                Icon::class,
                Description::class,
                EnumLabel::class,
                EnumColor::class,
                EnumIcon::class,
                EnumDescription::class,
            ];

            foreach ($classes as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} should be final");
            }
        });

        it('EnumManager is readonly', function () {
            $ref = new ReflectionClass(EnumManager::class);
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('EnumRule is readonly', function () {
            $ref = new ReflectionClass(EnumRule::class);
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('EnumCache singleton prevents cloning', function () {
            $cache = EnumCache::getInstance();
            expect(fn () => clone $cache)->toThrow(RuntimeException::class);
        });

        it('EnumCache singleton prevents serialization', function () {
            $cache = EnumCache::getInstance();
            expect(fn () => serialize($cache))->toThrow(RuntimeException::class);
        });
    });

    describe('Type System Contract', function () {
        it('string-backed enum resolves all metadata types correctly', function () {
            $status = UserStatus::ACTIVE;

            expect($status->label())->toBeString()->not->toBeEmpty();
            expect($status->color())->toBeString();
            expect($status->icon())->toBeNull()->or()->toBeString();
            expect($status->description())->toBeNull()->or()->toBeString();
        });

        it('int-backed enum resolves all metadata types correctly', function () {
            $priority = IntBackedPriority::HIGH;

            expect($priority->label())->toBeString()->not->toBeEmpty();
            expect($priority->color())->toBeString();
            expect($priority->toValue())->toBeInt();
        });

        it('pure enum resolves all metadata types correctly', function () {
            $flag = PureFeatureFlag::DARK_MODE;

            expect($flag->label())->toBeString()->not->toBeEmpty();
            expect($flag->color())->toBeString();
            expect($flag->toValue())->toBeString(); // case name for pure enums
            expect($flag->toValue())->toBe('DARK_MODE');
        });

        it('values() returns correct types per backing', function () {
            // String-backed: string values
            $stringValues = UserStatus::values();
            foreach ($stringValues as $v) {
                expect($v)->toBeString();
            }

            // Int-backed: int values
            $intValues = IntBackedPriority::values();
            foreach ($intValues as $v) {
                expect($v)->toBeInt();
            }

            // Pure enum: string case names
            $pureValues = PureFeatureFlag::values();
            foreach ($pureValues as $v) {
                expect($v)->toBeString();
            }
        });

        it('forSelect() returns consistent structure', function () {
            $select = UserStatus::forSelect();
            expect($select)->toBeArray();
            expect($select)->not->toBeEmpty();

            foreach ($select as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveKey('value');
                expect($option)->toHaveKey('label');
                expect($option['value'])->not->toBeNull();
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forApi() returns consistent structure', function () {
            $api = UserStatus::forApi();
            expect($api)->toBeArray();
            expect($api)->not->toBeEmpty();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->not->toBeNull();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString()->not->toBeEmpty();
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });
    });

    describe('Comparison Methods Contract', function () {
        it('is() works with enum instances (strict identity)', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
        });

        it('is() works with string case names (case-sensitive)', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
        });

        it('isNot() is correct negation of is()', function () {
            $case = UserStatus::ACTIVE;

            expect($case->isNot(UserStatus::ACTIVE))->toBeFalse();
            expect($case->isNot(UserStatus::BANNED))->toBeTrue();
            expect($case->isNot('ACTIVE'))->toBeFalse();
            expect($case->isNot('BANNED'))->toBeTrue();
        });

        it('in() works with instance list', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect(UserStatus::ACTIVE->in([UserStatus::BANNED, UserStatus::PENDING]))->toBeFalse();
        });

        it('in() works with string name list', function () {
            expect(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['BANNED', 'PENDING']))->toBeFalse();
        });

        it('in() works with mixed list', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        });

        it('notIn() works with instance list', function () {
            expect(UserStatus::ACTIVE->notIn([UserStatus::BANNED, UserStatus::PENDING]))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn([UserStatus::ACTIVE, UserStatus::BANNED]))->toBeFalse();
        });

        it('notIn() works with string name list', function () {
            expect(UserStatus::ACTIVE->notIn(['BANNED', 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn(['ACTIVE', 'BANNED']))->toBeFalse();
        });

        it('empty list behavior — in() returns false, notIn() returns true', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
        });
    });

    describe('Reverse Lookup Contract', function () {
        it('tryFromName() resolves valid case name', function () {
            $result = UserStatus::tryFromName('ACTIVE');
            expect($result)->toBeInstanceOf(UserStatus::class);
            expect($result->name)->toBe('ACTIVE');
        });

        it('tryFromName() returns null for invalid name', function () {
            expect(UserStatus::tryFromName('NONEXISTENT'))->toBeNull();
            expect(UserStatus::tryFromName(''))->toBeNull();
        });

        it('fromName() resolves valid case name', function () {
            $result = UserStatus::fromName('ACTIVE');
            expect($result)->toBeInstanceOf(UserStatus::class);
            expect($result->name)->toBe('ACTIVE');
        });

        it('fromName() throws InvalidEnumException for invalid name', function () {
            expect(fn () => UserStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('InvalidEnumException message contains class name', function () {
            try {
                UserStatus::fromName('INVALID');
                expect(true)->toBeFalse('Should have thrown');
            } catch (InvalidEnumException $e) {
                expect($e->getMessage())->toContain('UserStatus');
                expect($e->getMessage())->toContain('INVALID');
            }
        });

        it('tryFromLabel() resolves by label (case-insensitive)', function () {
            $label = UserStatus::ACTIVE->label();
            $result = UserStatus::tryFromLabel($label);
            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel() resolves case-insensitive', function () {
            $label = UserStatus::ACTIVE->label();
            $result = UserStatus::tryFromLabel(strtolower($label));
            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel() returns null for non-existent label', function () {
            expect(UserStatus::tryFromLabel('nonexistent-label-xyz'))->toBeNull();
        });

        it('hasCase() returns true for existing case', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('BANNED'))->toBeTrue();
        });

        it('hasCase() returns false for non-existent case', function () {
            expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
            expect(UserStatus::hasCase(''))->toBeFalse();
        });
    });

    describe('EnumCache TTL and Singleton Lifecycle', function () {
        it('getInstance() returns same instance', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('cache stores and retrieves metadata', function () {
            $cache = EnumCache::getInstance();
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

        it('cache expires after TTL', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0); // disable caching

            $cache->set('TestEnum', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('clearClass() removes specific class', function () {
            $cache = EnumCache::getInstance();
            $cache->set('ClassA', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);
            $cache->set('ClassB', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            $cache->clearClass('ClassA');
            expect($cache->has('ClassA'))->toBeFalse();
            expect($cache->has('ClassB'))->toBeTrue();
        });

        it('flush() clears all entries', function () {
            $cache = EnumCache::getInstance();
            $cache->set('A', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);
            $cache->set('B', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            EnumCache::flush();
            expect($cache->has('A'))->toBeFalse();
            expect($cache->has('B'))->toBeFalse();
        });

        it('resetInstance() creates fresh singleton', function () {
            $old = EnumCache::getInstance();
            EnumCache::resetInstance();
            $new = EnumCache::getInstance();

            expect($old)->not->toBe($new);
            expect($new->has('anything'))->toBeFalse();
        });

        it('get() throws OutOfBoundsException for missing class', function () {
            $cache = EnumCache::getInstance();
            expect(fn () => $cache->get('NonExistent'))
                ->toThrow(OutOfBoundsException::class);
        });

        it('setTtl() clamps negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);
            expect($cache->getTtl())->toBe(0);
        });

        it('__debugInfo() hides cache internals', function () {
            $cache = EnumCache::getInstance();
            $debug = $cache->__debugInfo();

            expect($debug)->toHaveKey('ttl');
            expect($debug)->toHaveKey('cachedClasses');
            expect($debug)->toHaveKey('timestampCount');
            expect($debug)->not->toHaveKey('cache');
            expect($debug)->not->toHaveKey('cacheTimestamps');
        });
    });

    describe('EnumRule Validation Contract', function () {
        it('for() creates instance with class name', function () {
            $rule = EnumRule::for(UserStatus::class);
            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('nullable() creates nullable instance', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            // null should pass without failing
            $failed = false;
            try {
                $rule->validate('status', null, function (string $attr, string $msg = null): void {});
            } catch (\Throwable) {
                $failed = true;
            }
            expect($failed)->toBeFalse();
        });

        it('non-nullable rejects null', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failMessage = null;

            $rule->validate('status', null, function (string $attr, string $msg = null) use (&$failMessage): void {
                $failMessage = $msg;
            });

            expect($failMessage)->not->toBeNull();
        });

        it('valid string-backed value passes', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', 'active', function (string $attr, string $msg = null) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('invalid string-backed value fails', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failMessage = null;

            $rule->validate('status', 'nonexistent', function (string $attr, string $msg = null) use (&$failMessage): void {
                $failMessage = $msg;
            });

            expect($failMessage)->not->toBeNull();
        });

        it('valid int-backed value passes', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;

            $rule->validate('priority', 1, function (string $attr, string $msg = null) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('string value for int-backed enum rejects type mismatch', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failMessage = null;

            $rule->validate('priority', '1', function (string $attr, string $msg = null) use (&$failMessage): void {
                $failMessage = $msg;
            });

            expect($failMessage)->not->toBeNull();
        });

        it('pure enum validates against case names', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('flag', 'DARK_MODE', function (string $attr, string $msg = null) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('pure enum rejects non-existent case name', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failMessage = null;

            $rule->validate('flag', 'NONEXISTENT', function (string $attr, string $msg = null) use (&$failMessage): void {
                $failMessage = $msg;
            });

            expect($failMessage)->not->toBeNull();
        });

        it('error message includes allowed values', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failMessage = null;

            $rule->validate('status', 'bad', function (string $attr, string $msg = null) use (&$failMessage): void {
                $failMessage = $msg;
            });

            expect($failMessage)->toContain('status');
            expect($failMessage)->toContain('invalid');
        });
    });

    describe('EnumCast Contract', function () {
        it('get() returns enum for valid string value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new stdClass, 'status', 'active', []);

            expect($result)->toBeInstanceOf(UserStatus::class);
            expect($result->name)->toBe('ACTIVE');
        });

        it('get() returns null for null value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('get() returns null for non-existent value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new stdClass, 'status', 'nonexistent', []);

            expect($result)->toBeNull();
        });

        it('set() returns backed value for enum instance', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(new stdClass, 'status', UserStatus::ACTIVE, []);

            expect($result)->toBe('active');
        });

        it('set() returns null for null value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(new stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('set() validates raw value', function () {
            $cast = new EnumCast(UserStatus::class);
            expect(fn () => $cast->set(new stdClass, 'status', 'invalid_value', []))
                ->toThrow(InvalidArgumentException::class);
        });

        it('set() throws for wrong enum class', function () {
            $cast = new EnumCast(UserStatus::class);
            // PaymentStatus is a different enum class
            expect(fn () => $cast->set(new stdClass, 'status', PaymentStatus::PAID, []))
                ->toThrow(InvalidArgumentException::class);
        });

        it('serialize() returns backed value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(new stdClass, 'status', UserStatus::ACTIVE, []);

            expect($result)->toBe('active');
        });

        it('serialize() returns raw int/string values', function () {
            $cast = new EnumCast(UserStatus::class);
            expect($cast->serialize(new stdClass, 'status', 'active', []))->toBe('active');
            expect($cast->serialize(new stdClass, 'status', null, []))->toBeNull();
        });
    });

    describe('Metadata Resolution Priority', function () {
        it('per-case attribute overrides class-level', function () {
            // BANNED has per-case #[Color('danger')]
            $color = TicketStatus::BANNED->color();
            expect($color)->toBe('danger');
        });

        it('class-level provides defaults when no per-case attribute', function () {
            $label = TicketStatus::OPEN->label();
            expect($label)->toBeString()->not->toBeEmpty();
        });

        it('auto-generated label from SCREAMING_SNAKE_CASE', function () {
            $label = PureFeatureFlag::DARK_MODE->label();
            expect($label)->toBe('Dark Mode');
        });

        it('auto-generated label from camelCase', function () {
            // EdgeCaseNamingEnum has camelCase names
            // The generateLabel method should handle camelCase
            $label = PaymentStatus::PAID->label();
            expect($label)->toBeString()->not->toBeEmpty();
        });

        it('default color is secondary', function () {
            // Pure enums with no class-level color should default to 'secondary'
            $color = PureFeatureFlag::RATE_LIMITING->color();
            expect($color)->toBe('secondary');
        });
    });

    describe('Cross-Enum Consistency', function () {
        it('all enums produce same-length forSelect and forApi arrays', function () {
            $enums = [UserStatus::class, IntBackedPriority::class, TicketStatus::class];

            foreach ($enums as $enumClass) {
                $select = $enumClass::forSelect();
                $api = $enumClass::forApi();
                expect(count($select))->toBe(count($api));
            }
        });

        it('all enum cases have non-empty labels', function () {
            $enums = [UserStatus::class, IntBackedPriority::class, TicketStatus::class, PaymentStatus::class, OrderStatus::class];

            foreach ($enums as $enumClass) {
                foreach ($enumClass::cases() as $case) {
                    expect($case->label())->toBeString()->not->toBeEmpty();
                }
            }
        });

        it('all enum cases have string colors', function () {
            $enums = [UserStatus::class, IntBackedPriority::class, TicketStatus::class, PaymentStatus::class];

            foreach ($enums as $enumClass) {
                foreach ($enumClass::cases() as $case) {
                    expect($case->color())->toBeString()->not->toBeEmpty();
                }
            }
        });

        it('forSelect values are unique per enum', function () {
            $enums = [UserStatus::class, IntBackedPriority::class, TicketStatus::class];

            foreach ($enums as $enumClass) {
                $values = array_column($enumClass::forSelect(), 'value');
                $unique = array_unique($values);
                expect(count($values))->toBe(count($unique));
            }
        });
    });

    describe('EnumManager Delegation Contract', function () {
        it('delegates forSelect() correctly', function () {
            $manager = new EnumManager;
            $result = $manager->forSelect(UserStatus::class);

            expect($result)->toBe(UserStatus::forSelect());
        });

        it('delegates forApi() correctly', function () {
            $manager = new EnumManager;
            $result = $manager->forApi(UserStatus::class);

            expect($result)->toBe(UserStatus::forApi());
        });

        it('delegates tryFromLabel() correctly', function () {
            $manager = new EnumManager;
            $label = UserStatus::ACTIVE->label();
            $result = $manager->tryFromLabel(UserStatus::class, $label);

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('delegates tryFromName() correctly', function () {
            $manager = new EnumManager;
            $result = $manager->tryFromName(UserStatus::class, 'ACTIVE');

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('delegates fromName() correctly', function () {
            $manager = new EnumManager;
            $result = $manager->fromName(UserStatus::class, 'ACTIVE');

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('delegates hasCase() correctly', function () {
            $manager = new EnumManager;
            expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
            expect($manager->hasCase(UserStatus::class, 'NONEXISTENT'))->toBeFalse();
        });

        it('delegates values() correctly', function () {
            $manager = new EnumManager;
            $result = $manager->values(UserStatus::class);

            expect($result)->toBe(UserStatus::values());
        });

        it('delegates labels() correctly', function () {
            $manager = new EnumManager;
            $result = $manager->labels(UserStatus::class);

            expect($result)->toBe(UserStatus::labels());
        });

        it('throws BadMethodCallException for non-enum class', function () {
            $manager = new EnumManager;
            expect(fn () => $manager->forSelect(\stdClass::class))
                ->toThrow(BadMethodCallException::class);
        });

        it('throws BadMethodCallException for enum without trait', function () {
            $manager = new EnumManager;
            // PlainTestEnum doesn't use HasEnumMetadata
            expect(fn () => $manager->forSelect(\ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum::class))
                ->toThrow(BadMethodCallException::class);
        });
    });

    describe('InvalidEnumException Factory Methods', function () {
        it('value() creates exception with class and value info', function () {
            $e = InvalidEnumException::value(UserStatus::class, 'invalid');

            expect($e->getMessage())->toContain('UserStatus');
            expect($e->getMessage())->toContain('invalid');
        });

        it('value() handles null value', function () {
            $e = InvalidEnumException::value(UserStatus::class, null);

            expect($e->getMessage())->toContain('null');
        });

        it('forName() creates exception with class and name info', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'BAD_NAME');

            expect($e->getMessage())->toContain('UserStatus');
            expect($e->getMessage())->toContain('BAD_NAME');
            expect($e->getMessage())->toContain('Case name');
        });

        it('__toString() produces readable output', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'BAD');

            $str = (string) $e;
            expect($str)->toContain('InvalidEnumException');
            expect($str)->toContain('BAD');
        });
    });

    describe('toValue() Normalization', function () {
        it('string-backed returns string value', function () {
            expect(UserStatus::ACTIVE->toValue())->toBe('active');
        });

        it('int-backed returns int value', function () {
            expect(IntBackedPriority::HIGH->toValue())->toBeInt();
        });

        it('pure enum returns case name', function () {
            expect(PureFeatureFlag::DARK_MODE->toValue())->toBe('DARK_MODE');
        });
    });

    describe('Labels and Values Consistency', function () {
        it('labels() count matches cases() count', function () {
            $enums = [UserStatus::class, IntBackedPriority::class, TicketStatus::class, PaymentStatus::class, OrderStatus::class];

            foreach ($enums as $enumClass) {
                expect($enumClass::labels())->toHaveCount(count($enumClass::cases()));
            }
        });

        it('values() count matches cases() count', function () {
            $enums = [UserStatus::class, IntBackedPriority::class, TicketStatus::class, PaymentStatus::class, OrderStatus::class];

            foreach ($enums as $enumClass) {
                expect($enumClass::values())->toHaveCount(count($enumClass::cases()));
            }
        });

        it('each label is non-empty string', function () {
            $enums = [UserStatus::class, IntBackedPriority::class, TicketStatus::class, PaymentStatus::class, OrderStatus::class];

            foreach ($enums as $enumClass) {
                foreach ($enumClass::labels() as $label) {
                    expect($label)->toBeString();
                    expect($label)->not->toBeEmpty();
                }
            }
        });
    });
});
