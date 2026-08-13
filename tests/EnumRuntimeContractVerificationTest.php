<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * Runtime contract verification — ensures enum API contracts hold
 * across all enum types (string-backed, int-backed, pure, single-case, zero-backed).
 *
 * This test verifies the actual public API behavior — not structural/code quality —
 * making it complementary to static analysis (PHPStan) and architecture audit tests.
 */
describe('Enum Runtime Contract Verification', function (): void {
    // ──────────────────────────────────────────────────────────────
    // String-backed enums
    // ──────────────────────────────────────────────────────────────
    describe('string-backed enum (UserStatus)', function (): void {
        it('resolves label for all cases via trait', function (): void {
            foreach (UserStatus::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
        });

        it('resolves color with class-level fallback', function (): void {
            expect(UserStatus::ACTIVE->color())->toBe('success');
            expect(UserStatus::BANNED->color())->toBe('danger');
            // INACTIVE has no per-case or class-level color → default 'secondary'
            expect(UserStatus::INACTIVE->color())->toBe('secondary');
        });

        it('forSelect returns consistent shape with unique values', function (): void {
            $options = UserStatus::forSelect();
            $values = array_column($options, 'value');

            expect($options)->toHaveCount(count(UserStatus::cases()));
            expect($values)->toBeArray()->each->toBeString();
            expect(array_unique($values))->toHaveCount(count($values)); // all unique
        });

        it('forApi returns full metadata shape', function (): void {
            $api = UserStatus::forApi();

            expect($api)->toBeArray()->not->toBeEmpty();
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('tryFromLabel is case-insensitive', function (): void {
            $label = UserStatus::ACTIVE->label();

            expect(UserStatus::tryFromLabel($label))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(strtolower($label)))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(strtoupper($label)))->toBe(UserStatus::ACTIVE);
        });

        it('comparison methods work with both instance and string', function (): void {
            $status = UserStatus::ACTIVE;

            expect($status->is(UserStatus::ACTIVE))->toBeTrue();
            expect($status->is('ACTIVE'))->toBeTrue();
            expect($status->is('active'))->toBeFalse(); // case name, not value
            expect($status->isNot(UserStatus::BANNED))->toBeTrue();
            expect($status->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect($status->in(['ACTIVE', 'PENDING']))->toBeTrue();
            expect($status->notIn([UserStatus::BANNED]))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Integer-backed enums
    // ──────────────────────────────────────────────────────────────
    describe('int-backed enum (IntBackedPriority)', function (): void {
        it('uses int values in forSelect and values()', function (): void {
            $options = IntBackedPriority::forSelect();
            $values = IntBackedPriority::values();

            expect($values)->each->toBeInt();
            expect(array_column($options, 'value'))->each->toBeInt();
        });

        it('resolves class-level descriptions', function (): void {
            expect(IntBackedPriority::CRITICAL->description())->toBe(
                'Critical priority — immediate action required'
            );
            expect(IntBackedPriority::LOW->description())->toBe(
                'Low priority — handle when convenient'
            );
        });

        it('resolves per-case color over class-level', function (): void {
            // CRITICAL has per-case #[Color('danger')] and class-level EnumColor(danger: [1])
            // Both map to 'danger', but the per-case attribute takes priority
            expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
            // HIGH has per-case #[Color('warning')]
            expect(IntBackedPriority::HIGH->color())->toBe('warning');
        });

        it('resolves default icon from class-level EnumIcon', function (): void {
            // NONE has no per-case icon, should get default from EnumIcon
            expect(IntBackedPriority::NONE->icon())->toBe('heroicon-o-flag');
        });

        it('tryFromName works with string case names', function (): void {
            expect(IntBackedPriority::tryFromName('CRITICAL'))->toBe(IntBackedPriority::CRITICAL);
            expect(IntBackedPriority::tryFromName('NON_EXISTENT'))->toBeNull();
        });

        it('fromName throws InvalidEnumException for invalid name', function (): void {
            expect(fn () => IntBackedPriority::fromName('INVALID'))
                ->toThrow(InvalidEnumException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Pure enums (no backing type)
    // ──────────────────────────────────────────────────────────────
    describe('pure enum (PureFeatureFlag)', function (): void {
        it('uses case names as values in forSelect', function (): void {
            $options = PureFeatureFlag::forSelect();

            foreach ($options as $option) {
                expect($option['value'])->toBeString();
                // Pure enum values are case names
                expect(PureFeatureFlag::tryFromName($option['value']))->not->toBeNull();
            }
        });

        it('values() returns case names', function (): void {
            $values = PureFeatureFlag::values();
            $names = array_map(fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());

            expect($values)->toBe($names);
        });

        it('MAINTENANCE_MODE has auto-generated label and null description', function (): void {
            expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
            expect(PureFeatureFlag::MAINTENANCE_MODE->description())->toBeNull();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Zero-backed integer enum
    // ──────────────────────────────────────────────────────────────
    describe('zero-backed int enum (ZeroPriority)', function (): void {
        it('handles zero as a valid backed value', function (): void {
            expect(ZeroPriority::NONE->value)->toBe(0);
            expect(ZeroPriority::NONE->label())->toBeString()->not->toBeEmpty();
        });

        it('values() includes zero', function (): void {
            $values = ZeroPriority::values();

            expect($values)->toContain(0);
            expect($values)->toHaveCount(3);
        });

        it('forSelect uses backed value 0 (not boolean false)', function (): void {
            $options = ZeroPriority::forSelect();

            $noneOption = array_values(array_filter(
                $options,
                fn (array $opt): bool => $opt['value'] === 0,
            ));

            expect($noneOption)->toHaveCount(1);
            expect($noneOption[0]['value'])->toBe(0);
            expect($noneOption[0]['value'])->not->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Single-case enum
    // ──────────────────────────────────────────────────────────────
    describe('single-case enum (SingleCaseEnum)', function (): void {
        it('has exactly one case', function (): void {
            expect(SingleCaseEnum::cases())->toHaveCount(1);
        });

        it('forSelect returns a single option', function (): void {
            $options = SingleCaseEnum::forSelect();

            expect($options)->toHaveCount(1);
            expect($options[0])->toHaveKeys(['value', 'label']);
        });

        it('is() and in() work with single case', function (): void {
            $case = SingleCaseEnum::ONLY;

            expect($case->is(SingleCaseEnum::ONLY))->toBeTrue();
            expect($case->in([SingleCaseEnum::ONLY]))->toBeTrue();
            expect($case->notIn([]))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // CamelCase enum names
    // ──────────────────────────────────────────────────────────────
    describe('camelCase enum names (CamelCaseRole)', function (): void {
        it('generates Title Case labels from camelCase names', function (): void {
            expect(CamelCaseRole::isActive->label())->toBe('Is Active');
            expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
            expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
            expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
        });

        it('default color is secondary for all cases', function (): void {
            foreach (CamelCaseRole::cases() as $case) {
                expect($case->color())->toBe('secondary');
            }
        });

        it('default icon and description are null', function (): void {
            foreach (CamelCaseRole::cases() as $case) {
                expect($case->icon())->toBeNull();
                expect($case->description())->toBeNull();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumCast — Eloquent casting contract
    // ──────────────────────────────────────────────────────────────
    describe('EnumCast eloquent casting', function (): void {
        it('get() returns enum instance for valid string value', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $result = $cast->get(
                model: new class {},
                key: 'status',
                value: 'active',
                attributes: [],
            );

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('get() returns enum instance for valid int value', function (): void {
            $cast = new EnumCast(IntBackedPriority::class);

            $result = $cast->get(
                model: new class {},
                key: 'priority',
                value: 1,
                attributes: [],
            );

            expect($result)->toBe(IntBackedPriority::CRITICAL);
        });

        it('get() returns null for null value', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $result = $cast->get(
                model: new class {},
                key: 'status',
                value: null,
                attributes: [],
            );

            expect($result)->toBeNull();
        });

        it('get() returns null for non-int/non-string value', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $result = $cast->get(
                model: new class {},
                key: 'status',
                value: ['invalid' => 'array'],
                attributes: [],
            );

            expect($result)->toBeNull();
        });

        it('set() stores backed value from enum instance', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $result = $cast->set(
                model: new class {},
                key: 'status',
                value: UserStatus::ACTIVE,
                attributes: [],
            );

            expect($result)->toBe('active');
        });

        it('set() throws on wrong enum type', function (): void {
            $cast = new EnumCast(UserStatus::class);

            expect(fn () => $cast->set(
                model: new class {},
                key: 'status',
                value: IntBackedPriority::CRITICAL,
                attributes: [],
            ))->toThrow(\InvalidArgumentException::class);
        });

        it('set() throws on invalid raw value', function (): void {
            $cast = new EnumCast(UserStatus::class);

            expect(fn () => $cast->set(
                model: new class {},
                key: 'status',
                value: 'invalid_status_value',
                attributes: [],
            ))->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns backed value from enum instance', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $result = $cast->serialize(
                model: new class {},
                key: 'status',
                value: UserStatus::ACTIVE,
                attributes: [],
            );

            expect($result)->toBe('active');
        });

        it('serialize() returns null for null value', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $result = $cast->serialize(
                model: new class {},
                key: 'status',
                value: null,
                attributes: [],
            );

            expect($result)->toBeNull();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumRule — validation contract
    // ──────────────────────────────────────────────────────────────
    describe('EnumRule validation', function (): void {
        it('passes for valid string-backed enum value', function (): void {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', 'active', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('fails for invalid string-backed enum value', function (): void {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', 'invalid', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('passes for valid int-backed enum value', function (): void {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;

            $rule->validate('priority', 3, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('fails when int value given to string-backed enum', function (): void {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            // PHP's tryFrom() would TypeError on type mismatch
            $rule->validate('status', 42, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('fails for string value given to int-backed enum', function (): void {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;

            $rule->validate('priority', 'not-an-int', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('rejects null when not nullable', function (): void {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('allows null when nullable', function (): void {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('works with pure enum (case name matching)', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('feature', 'DARK_MODE', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('fails for non-string value with pure enum', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('feature', 42, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumCache — cache lifecycle contract
    // ──────────────────────────────────────────────────────────────
    describe('EnumCache lifecycle', function (): void {
        it('caches and retrieves metadata', function (): void {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(300);

            $metadata = [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => ['active' => 'success'],
                'icons' => [],
            ];

            $cache->set(UserStatus::class, $metadata);

            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->get(UserStatus::class))->toBe($metadata);

            $cache->clear();
        });

        it('expired cache entry returns has() false', function (): void {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(0); // disable caching

            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeFalse();

            $cache->setTtl(300);
        });

        it('clearClass only removes specified class', function (): void {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(300);

            $meta = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
            $cache->set(UserStatus::class, $meta);
            $cache->set(OrderStatus::class, $meta);

            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(OrderStatus::class))->toBeTrue();

            $cache->clear();
        });

        it('flush clears everything via static method', function (): void {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            \ZeroBoiler\Enums\EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // InvalidEnumException — factory methods
    // ──────────────────────────────────────────────────────────────
    describe('InvalidEnumException', function (): void {
        it('creates value exception with null display', function (): void {
            $e = InvalidEnumException::value(UserStatus::class, null);

            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain(UserStatus::class);
        });

        it('creates value exception with int display', function (): void {
            $e = InvalidEnumException::value(IntBackedPriority::class, 999);

            expect($e->getMessage())->toContain('999');
            expect($e->getMessage())->toContain(IntBackedPriority::class);
        });

        it('creates forName exception', function (): void {
            $e = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

            expect($e->getMessage())->toContain('NONEXISTENT');
            expect($e->getMessage())->toContain('does not exist');
        });

        it('__toString returns class name and message', function (): void {
            $e = InvalidEnumException::forName(UserStatus::class, 'X');

            $str = (string) $e;

            expect($str)->toContain(InvalidEnumException::class);
            expect($str)->toContain('X');
        });
    });
});
