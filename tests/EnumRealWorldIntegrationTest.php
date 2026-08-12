<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Real-world integration patterns for ZeroBoiler Enums.
 *
 * Tests enum usage in common application contexts:
 * - State machine transitions
 * - API response metadata generation
 * - Form validation with EnumRule
 * - Eloquent model cast simulation
 * - Multi-enum workflow coordination
 * - Cache lifecycle in production vs development
 * - Label/description fallback chains
 *
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata
 */

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Real-world Enum Integration Patterns', function (): void {

    // ──────────────────────────────────────────────────────────────
    // State Machine Transitions
    // ──────────────────────────────────────────────────────────────

    describe('State machine transitions', function (): void {
        it('models a valid order status workflow with allowed transitions', function (): void {
            // Order workflow: CREATED → PAID → SHIPPED → DELIVERED
            $allowedTransitions = [
                OrderStatus::CREATED->value   => [OrderStatus::PAID->value, OrderStatus::CANCELLED->value],
                OrderStatus::PAID->value      => [OrderStatus::SHIPPED->value, OrderStatus::REFUNDED->value],
                OrderStatus::SHIPPED->value   => [OrderStatus::DELIVERED->value],
                OrderStatus::CANCELLED->value => [],
                OrderStatus::REFUNDED->value  => [],
                OrderStatus::DELIVERED->value => [],
            ];

            $current = OrderStatus::CREATED;

            // CREATED → PAID: valid
            $next = OrderStatus::PAID;
            expect(in_array($next->value, $allowedTransitions[$current->value], true))->toBeTrue();

            // PAID → SHIPPED: valid
            $current = OrderStatus::PAID;
            $next = OrderStatus::SHIPPED;
            expect(in_array($next->value, $allowedTransitions[$current->value], true))->toBeTrue();

            // SHIPPED → DELIVERED: valid
            $current = OrderStatus::SHIPPED;
            $next = OrderStatus::DELIVERED;
            expect(in_array($next->value, $allowedTransitions[$current->value], true))->toBeTrue();

            // DELIVERED → any: invalid (terminal state)
            $current = OrderStatus::DELIVERED;
            expect($allowedTransitions[$current->value])->toBeEmpty();
        });

        it('uses is() and in() for transition guards', function (): void {
            $status = OrderStatus::CREATED;

            // Only CREATED and CANCELLED can transition to terminal without payment
            expect($status->in([OrderStatus::CREATED, OrderStatus::PAID]))->toBeTrue();
            expect($status->is(OrderStatus::CREATED))->toBeTrue();
            expect($status->isNot(OrderStatus::DELIVERED))->toBeTrue();
        });

        it('prevents invalid state jumps using fromName', function (): void {
            // Verify that fromName correctly resolves valid cases
            expect(OrderStatus::fromName('CREATED')->value)->toBe('created');

            // And throws for invalid case names
            expect(fn (): mixed => OrderStatus::fromName('NON_EXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // API Response Metadata Generation
    // ──────────────────────────────────────────────────────────────

    describe('API response metadata', function (): void {
        it('generates complete metadata for frontend select components', function (): void {
            $options = UserStatus::forSelect();

            expect($options)->toBeArray();
            expect(count($options))->toBe(count(UserStatus::cases()));

            foreach ($options as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }

            // Verify values are unique (critical for <select> elements)
            $values = array_column($options, 'value');
            expect(array_unique($values))->toBe($values);
        });

        it('generates full API payload with all metadata fields', function (): void {
            $api = UserStatus::forApi();

            expect($api)->toBeArray();
            expect(count($api))->toBe(count(UserStatus::cases()));

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString()->not->toBeEmpty();
                expect($item['color'])->toBeString()->not->toBeEmpty();
                // description and icon may be null
                expect($item['description'] === null || is_string($item['description']))->toBeTrue();
                expect($item['icon'] === null || is_string($item['icon']))->toBeTrue();
            }
        });

        it('provides values() suitable for database seeding', function (): void {
            $values = UserStatus::values();
            expect($values)->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);

            // Int-backed enum
            $priorityValues = Priority::values();
            expect($priorityValues)->toBe([1, 2, 3, 4]);

            // Pure enum — returns case names
            $flagValues = PureFeatureFlag::values();
            expect($flagValues)->toBe(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Validation Rule Integration
    // ──────────────────────────────────────────────────────────────

    describe('EnumRule validation', function (): void {
        it('validates string-backed enum values', function (): void {
            $rule = EnumRule::for(UserStatus::class);
            $passed = true;

            $rule->validate('status', 'active', function () use (&$passed): void {
                $passed = false;
            });

            expect($passed)->toBeTrue();
        });

        it('rejects invalid string-backed enum values', function (): void {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', 'nonexistent', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('validates int-backed enum values', function (): void {
            $rule = EnumRule::for(Priority::class);
            $passed = true;

            $rule->validate('priority', 1, function () use (&$passed): void {
                $passed = false;
            });

            expect($passed)->toBeTrue();
        });

        it('rejects wrong PHP type for int-backed enum', function (): void {
            $rule = EnumRule::for(Priority::class);
            $failed = false;

            // String '1' should be rejected for int-backed enum
            $rule->validate('priority', '1', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('validates pure enum case names', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $passed = true;

            $rule->validate('flag', 'DARK_MODE', function () use (&$passed): void {
                $passed = false;
            });

            expect($passed)->toBeTrue();
        });

        it('nullable variant passes null values', function (): void {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $passed = true;

            $rule->validate('status', null, function () use (&$passed): void {
                $passed = false;
            });

            expect($passed)->toBeTrue();
        });

        it('non-nullable variant rejects null values', function (): void {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Label Generation Edge Cases
    // ──────────────────────────────────────────────────────────────

    describe('Label generation edge cases', function (): void {
        it('auto-generates labels from SCREAMING_SNAKE_CASE', function (): void {
            expect(Priority::LOW->label())->toBe('Low');
            expect(Priority::HIGH->label())->toBe('High');
            expect(Priority::URGENT->label())->toBe('Urgent');
            expect(OrderStatus::CREATED->label())->toBe('Created');
        });

        it('auto-generates labels from camelCase', function (): void {
            expect(CamelCaseRole::isActive->label())->toBe('Is Active');
            expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
            expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
            expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
        });

        it('per-case Label attribute overrides auto-generation', function (): void {
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
            expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
        });

        it('class-level EnumLabel maps values to labels', function (): void {
            // MixedAttributeStatus has class-level EnumLabel(labels: [...])
            expect(MixedAttributeStatus::NEW->label())->toBe('Brand New Item');
        });

        it('labels() returns all labels in case order', function (): void {
            $labels = UserStatus::labels();
            expect($labels)->toHaveCount(count(UserStatus::cases()));
            expect($labels[0])->toBe('Active User');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Color and Icon Resolution Priority
    // ──────────────────────────────────────────────────────────────

    describe('Metadata resolution priority', function (): void {
        it('per-case color overrides class-level EnumColor', function (): void {
            // IntStatusWithColor: class-level maps 3→danger, but per-case Color('danger') on BANNED
            expect(IntStatusWithColor::BANNED->color())->toBe('danger');

            // UserStatus: class-level maps 'banned'→danger, per-case Color('danger') on BANNED
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('class-level color applies when no per-case override', function (): void {
            expect(IntStatusWithColor::ACTIVE->color())->toBe('success');
            expect(UserStatus::ACTIVE->color())->toBe('success');
        });

        it('default color is secondary when nothing is set', function (): void {
            // Priority has no color attributes at all
            expect(Priority::LOW->color())->toBe('secondary');
        });

        it('class-level EnumIcon provides default icons', function (): void {
            // MixedAttributeStatus has EnumIcon(default: 'heroicon-o-document')
            expect(MixedAttributeStatus::ACTIVE->icon())->toBe('heroicon-o-document');
        });

        it('per-case icon overrides class-level default', function (): void {
            expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
        });

        it('description resolves from per-case then class-level', function (): void {
            expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
            expect(MixedAttributeStatus::ACTIVE->description())->toBe('Currently active');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Zero Value Edge Cases
    // ──────────────────────────────────────────────────────────────

    describe('Zero value edge cases', function (): void {
        it('zero-backed int enum handles value 0 correctly', function (): void {
            expect(ZeroPriority::NONE->value)->toBe(0);
            expect(ZeroPriority::NONE->label())->toBe('None');

            // forSelect should include zero value
            $select = ZeroPriority::forSelect();
            $values = array_column($select, 'value');
            expect(in_array(0, $values, true))->toBeTrue();

            // values() should include zero
            expect(ZeroPriority::values())->toBe([0, 1, 2]);
        });

        it('zero string-backed value works with tryFromLabel', function (): void {
            expect(ZeroBackedPriority::NONE->value)->toBe('0');
            expect(ZeroBackedPriority::NONE->label())->toBeString()->not->toBeEmpty();
        });

        it('single case enum works with all methods', function (): void {
            expect(SingleCaseEnum::cases())->toHaveCount(1);
            expect(SingleCaseEnum::ONLY->label())->toBeString();
            expect(SingleCaseEnum::ONLY->color())->toBe('secondary');
            expect(SingleCaseEnum::forSelect())->toHaveCount(1);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Cache Lifecycle
    // ──────────────────────────────────────────────────────────────

    describe('Cache lifecycle', function (): void {
        it('EnumCache caches and retrieves metadata', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();

            $metadata = EnumMetadataResolver::resolve(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeTrue();
            $cached = $cache->get(UserStatus::class);
            expect($cached)->toBe($metadata);

            $cache->clear();
        });

        it('EnumMetadataResolver::invalidate() forces rebuild', function (): void {
            EnumMetadataResolver::invalidate(UserStatus::class);

            $cache = EnumCache::getInstance();
            expect($cache->has(UserStatus::class))->toBeFalse();

            // Resolve again — should populate cache
            EnumMetadataResolver::resolve(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeTrue();

            $cache->clear();
        });

        it('EnumCache TTL expiration works', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(0); // Disable caching

            $cache->set('TestEnum', [
                'labels' => ['x' => 'X'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            // With TTL=0, has() should return false (always stale)
            expect($cache->has('TestEnum'))->toBeFalse();

            $cache->clear();
        });

        it('EnumCache::flush() clears all entries', function (): void {
            $cache = EnumCache::getInstance();

            $cache->set('Enum1', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);
            $cache->set('Enum2', [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            expect($cache->has('Enum1'))->toBeTrue();
            expect($cache->has('Enum2'))->toBeTrue();

            EnumCache::flush();

            expect($cache->has('Enum1'))->toBeFalse();
            expect($cache->has('Enum2'))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Reverse Lookup
    // ──────────────────────────────────────────────────────────────

    describe('Reverse lookup', function (): void {
        it('tryFromLabel finds case by label (case-insensitive)', function (): void {
            $case = UserStatus::tryFromLabel('Active User');
            expect($case)->toBe(UserStatus::ACTIVE);

            // Case-insensitive
            $case = UserStatus::tryFromLabel('active user');
            expect($case)->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel returns null for non-existent label', function (): void {
            expect(UserStatus::tryFromLabel('Non Existent Label'))->toBeNull();
        });

        it('tryFromName finds case by exact name', function (): void {
            expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromName('active'))->toBeNull(); // Case-sensitive
        });

        it('fromName throws for invalid case name', function (): void {
            expect(fn (): mixed => UserStatus::fromName('INVALID'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase checks existence without returning', function (): void {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Comparison Methods — strict type behavior
    // ──────────────────────────────────────────────────────────────

    describe('Comparison method strictness', function (): void {
        it('is() uses strict identity for instances', function (): void {
            $active = UserStatus::ACTIVE;
            expect($active->is(UserStatus::ACTIVE))->toBeTrue();
            expect($active->is(UserStatus::BANNED))->toBeFalse();
        });

        it('is() uses strict string comparison for names', function (): void {
            $active = UserStatus::ACTIVE;
            expect($active->is('ACTIVE'))->toBeTrue();
            expect($active->is('active'))->toBeFalse(); // Case-sensitive
            expect($active->is('Active'))->toBeFalse();
        });

        it('in() works with mixed instances and strings', function (): void {
            $status = UserStatus::ACTIVE;
            expect($status->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
            expect($status->in([UserStatus::BANNED, 'SUSPENDED']))->toBeFalse();
        });

        it('in() returns false for empty array', function (): void {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Cross-Type Compatibility
    // ──────────────────────────────────────────────────────────────

    describe('Cross-type compatibility', function (): void {
        it('string-backed, int-backed, and pure enums all support label()', function (): void {
            expect(UserStatus::ACTIVE->label())->toBeString()->not->toBeEmpty();
            expect(Priority::LOW->label())->toBeString()->not->toBeEmpty();
            expect(PureFeatureFlag::DARK_MODE->label())->toBeString()->not->toBeEmpty();
        });

        it('all enum types support forSelect() with correct value types', function (): void {
            $stringSelect = UserStatus::forSelect();
            expect(is_string($stringSelect[0]['value']))->toBeTrue();

            $intSelect = Priority::forSelect();
            expect(is_int($intSelect[0]['value']))->toBeTrue();

            $pureSelect = PureFeatureFlag::forSelect();
            expect(is_string($pureSelect[0]['value']))->toBeTrue();
            expect($pureSelect[0]['value'])->toBe('DARK_MODE');
        });

        it('all enum types support forApi() with consistent shape', function (): void {
            foreach ([UserStatus::ACTIVE, Priority::LOW, PureFeatureFlag::DARK_MODE] as $case) {
                $api = $case::forApi();
                expect($api)->not->toBeEmpty();
                expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Exception Factory Methods
    // ──────────────────────────────────────────────────────────────

    describe('InvalidEnumException factory methods', function (): void {
        it('value() creates exception with string value', function (): void {
            $e = InvalidEnumException::value(UserStatus::class, 'invalid');
            expect($e->getMessage())->toContain('invalid');
            expect($e->getMessage())->toContain(UserStatus::class);
        });

        it('value() handles null value gracefully', function (): void {
            $e = InvalidEnumException::value(UserStatus::class, null);
            expect($e->getMessage())->toContain('null');
        });

        it('value() handles int value', function (): void {
            $e = InvalidEnumException::value(Priority::class, 99);
            expect($e->getMessage())->toContain('99');
        });

        it('forName() includes class and case name', function (): void {
            $e = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');
            expect($e->getMessage())->toContain('NONEXISTENT');
            expect($e->getMessage())->toContain(UserStatus::class);
        });

        it('__toString() returns class name and message', function (): void {
            $e = InvalidEnumException::forName(UserStatus::class, 'X');
            $str = (string) $e;
            expect($str)->toContain(InvalidEnumException::class);
            expect($str)->toContain('X');
        });
    });
});
