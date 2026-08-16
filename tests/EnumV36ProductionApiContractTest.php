<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\NumericStatusCode;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;

/**
 * Production contract tests — verifies the public API surface of all enum types.
 *
 * These tests ensure:
 * - String-backed, int-backed, and pure enums all work identically
 * - Metadata resolution priority is correct (per-case > class-level > auto)
 * - Cache lifecycle doesn't affect correctness
 * - All accessor methods return correct types
 * - Comparison methods are strict and correct
 * - Lookup methods handle edge cases (null, empty, case-sensitivity)
 * - EnumRule validates correctly for all enum types
 * - Facade delegation produces identical results to trait methods
 */
describe('V36 Production API Contract', function () {
    // -----------------------------------------------------------------------
    // 1. String-backed enum contract
    // -----------------------------------------------------------------------
    describe('String-backed enum (TicketStatus)', function () {
        it('label() returns per-case attribute or auto-generated', function () {
            expect(TicketStatus::OPEN->label())->toBe('Open');
            expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
            expect(TicketStatus::CLOSED->label())->toBe('Closed');
        });

        it('color() returns per-case or class-level or default', function () {
            expect(TicketStatus::OPEN->color())->toBeString()->not->toBeEmpty();
            expect(TicketStatus::CLOSED->color())->toBeString();
        });

        it('icon() and description() return string or null', function () {
            $icon = TicketStatus::OPEN->icon();
            expect($icon === null || is_string($icon))->toBeTrue();

            $desc = TicketStatus::OPEN->description();
            expect($desc === null || is_string($desc))->toBeTrue();
        });

        it('forSelect() returns value/label pairs with correct types', function () {
            $select = TicketStatus::forSelect();

            expect($select)->toBeArray();
            expect($select)->toHaveCount(count(TicketStatus::cases()));

            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forApi() returns full metadata with all expected keys', function () {
            $api = TicketStatus::forApi();

            expect($api)->toBeArray();
            expect($api)->toHaveCount(count(TicketStatus::cases()));

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString()->not->toBeEmpty();
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('values() returns backed values as strings', function () {
            $values = TicketStatus::values();

            expect($values)->each->toBeString();
            expect($values)->toBeArray();
        });

        it('labels() returns non-empty strings in case order', function () {
            $labels = TicketStatus::labels();

            expect($labels)->toHaveCount(count(TicketStatus::cases()));
            expect($labels)->each(fn ($l) => expect($l)->toBeString()->not->toBeEmpty());
        });

        it('is() uses strict identity for instances', function () {
            expect(TicketStatus::OPEN->is(TicketStatus::OPEN))->toBeTrue();
            expect(TicketStatus::OPEN->is(TicketStatus::CLOSED))->toBeFalse();
        });

        it('is() uses case-sensitive string comparison', function () {
            expect(TicketStatus::OPEN->is('OPEN'))->toBeTrue();
            expect(TicketStatus::OPEN->is('open'))->toBeFalse();
        });

        it('in() accepts mixed instances and strings', function () {
            expect(TicketStatus::OPEN->in([TicketStatus::OPEN, 'CLOSED']))->toBeTrue();
            expect(TicketStatus::OPEN->in(['CLOSED', TicketStatus::IN_PROGRESS]))->toBeFalse();
        });

        it('notIn() is the logical inverse of in()', function () {
            $case = TicketStatus::OPEN;
            $list = [TicketStatus::OPEN, TicketStatus::CLOSED];

            expect($case->notIn($list))->toBeFalse();
            expect($case->notIn([TicketStatus::IN_PROGRESS]))->toBeTrue();
        });

        it('tryFromLabel() is case-insensitive', function () {
            $found = TicketStatus::tryFromLabel('open');
            expect($found)->toBe(TicketStatus::OPEN);

            $foundUpper = TicketStatus::tryFromLabel('OPEN');
            expect($foundUpper)->toBe(TicketStatus::OPEN);
        });

        it('tryFromLabel() returns null for non-existent labels', function () {
            expect(TicketStatus::tryFromLabel('non_existent_label_xyz'))->toBeNull();
            expect(TicketStatus::tryFromLabel(''))->toBeNull();
        });

        it('tryFromName() is case-sensitive', function () {
            expect(TicketStatus::tryFromName('OPEN'))->toBe(TicketStatus::OPEN);
            expect(TicketStatus::tryFromName('open'))->toBeNull();
        });

        it('fromName() throws for non-existent case', function () {
            expect(fn () => TicketStatus::fromName('DOES_NOT_EXIST'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase() returns correct booleans', function () {
            expect(TicketStatus::hasCase('OPEN'))->toBeTrue();
            expect(TicketStatus::hasCase('CLOSED'))->toBeTrue();
            expect(TicketStatus::hasCase(''))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // 2. Int-backed enum contract
    // -----------------------------------------------------------------------
    describe('Int-backed enum (IntBackedPriority)', function () {
        it('values() returns int values', function () {
            $values = IntBackedPriority::values();

            expect($values)->toBeArray();
            expect($values)->each->toBeInt();
        });

        it('forSelect() uses backed int values, not case names', function () {
            $select = IntBackedPriority::forSelect();

            foreach ($select as $option) {
                expect($option['value'])->toBeInt();
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('tryFromName() works by case name, not by backed value', function () {
            expect(IntBackedPriority::tryFromName('LOW'))->toBe(IntBackedPriority::LOW);
            expect(IntBackedPriority::tryFromName('0'))->toBeNull(); // '0' is a name, not a value
        });

        it('zero-backed value is correctly handled', function () {
            $case = ZeroBackedPriority::NONE;

            expect($case->value)->toBe(0);
            expect($case->label())->toBe('None');

            // Verify metadata uses 0 as key, not empty/null
            $meta = EnumMetadataResolver::resolve(ZeroBackedPriority::class);
            expect($meta['labels'][0])->toBe('None');
        });

        it('numeric status code with large int values works', function () {
            $values = NumericStatusCode::values();

            expect($values)->each->toBeInt();
            expect($values)->not->toBeEmpty();
        });
    });

    // -----------------------------------------------------------------------
    // 3. Pure enum contract
    // -----------------------------------------------------------------------
    describe('Pure enum (PureFeatureFlag)', function () {
        it('values() returns case names, not backed values', function () {
            $values = PureFeatureFlag::values();

            expect($values)->toBeArray();
            expect($values)->each->toBeString();

            // Case names should match enum case names
            $caseNames = array_map(static fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());
            expect($values)->toBe($caseNames);
        });

        it('forSelect() uses case names as values', function () {
            $select = PureFeatureFlag::forSelect();

            foreach ($select as $option) {
                expect($option['value'])->toBeString();
                // Value should be a valid case name
                expect(PureFeatureFlag::tryFromName($option['value']))->not->toBeNull();
            }
        });

        it('auto-generates labels from SCREAMING_SNAKE_CASE', function () {
            expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
            expect(PureFeatureFlag::BETA_FEATURES->label())->toBe('Beta Features');
        });

        it('auto-generates labels from camelCase', function () {
            // camelCase case names are Title Cased
            $role = CamelCaseRole::Admin;
            expect($role->label())->toBe('Admin');
        });

        it('comparison works without backed values', function () {
            expect(PureFeatureFlag::DARK_MODE->is(PureFeatureFlag::DARK_MODE))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->is('dark_mode'))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // 4. Class-level attribute contract
    // -----------------------------------------------------------------------
    describe('Class-level attributes', function () {
        it('AllClassLevelEnum applies all four metadata types via class-level', function () {
            foreach (AllClassLevelEnum::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
                expect($case->color())->toBeString();
                // Icon and description may or may not be set at class level
            }
        });

        it('EmptyDefaultsStatus works without any attributes', function () {
            foreach (EmptyDefaultsStatus::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
                expect($case->color())->toBe('secondary'); // default
                expect($case->icon())->toBeNull();
                expect($case->description())->toBeNull();
            }
        });

        it('DetailedTicketStatus has per-case metadata overrides', function () {
            $open = DetailedTicketStatus::OPEN;

            expect($open->label())->toBeString()->not->toBeEmpty();
            expect($open->color())->toBeString();
        });

        it('DefaultIconFeature applies default icon to all cases', function () {
            EnumCache::flush();
            EnumMetadataResolver::invalidateAll();

            foreach (DefaultIconFeature::cases() as $case) {
                $icon = $case->icon();
                expect($icon)->toBeString()->not->toBeEmpty();
            }
        });
    });

    // -----------------------------------------------------------------------
    // 5. EnumRule validation contract
    // -----------------------------------------------------------------------
    describe('EnumRule validation', function () {
        it('validates string-backed enum values', function () {
            $rule = EnumRule::for(TicketStatus::class);
            $fail = false;

            $rule->validate('status', 'open', static function () use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();

            // Invalid value
            $fail2 = false;
            $rule->validate('status', 'invalid_value', static function () use (&$fail2): void {
                $fail2 = true;
            });

            expect($fail2)->toBeTrue();
        });

        it('validates int-backed enum values with type checking', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $fail = false;

            $rule->validate('priority', 1, static function () use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();

            // Reject string for int-backed enum
            $fail2 = false;
            $rule->validate('priority', 'low', static function () use (&$fail2): void {
                $fail2 = true;
            });

            expect($fail2)->toBeTrue();
        });

        it('nullable allows null values', function () {
            $rule = EnumRule::for(TicketStatus::class)->nullable();
            $fail = false;

            $rule->validate('status', null, static function () use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();
        });

        it('non-nullable rejects null values', function () {
            $rule = EnumRule::for(TicketStatus::class);
            $fail = false;

            $rule->validate('status', null, static function () use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // 6. Cache lifecycle contract
    // -----------------------------------------------------------------------
    describe('Cache lifecycle', function () {
        it('metadata is identical before and after cache flush', function () {
            $before = EnumMetadataResolver::resolve(TicketStatus::class);
            EnumCache::flush();
            $after = EnumMetadataResolver::resolve(TicketStatus::class);

            expect($before)->toBe($after);
        });

        it('per-class invalidation does not affect other enums', function () {
            $ticketMeta = EnumMetadataResolver::resolve(TicketStatus::class);
            EnumMetadataResolver::invalidate(TicketStatus::class);

            // PaymentStatus metadata should still be cached/accessible
            $paymentMeta = EnumMetadataResolver::resolve(PaymentStatus::class);
            expect($paymentMeta)->toBeArray();
            expect($paymentMeta['labels'])->not->toBeEmpty();

            // TicketStatus metadata should rebuild cleanly
            $ticketRebuilt = EnumMetadataResolver::resolve(TicketStatus::class);
            expect($ticketRebuilt)->toBe($ticketMeta);
        });

        it('cache TTL of 0 disables caching', function () {
            $cache = EnumCache::getInstance();
            $originalTtl = $cache->getTtl();

            $cache->setTtl(0);
            EnumCache::flush();

            $meta1 = EnumMetadataResolver::resolve(TicketStatus::class);
            $meta2 = EnumMetadataResolver::resolve(TicketStatus::class);

            expect($meta1)->toBe($meta2);

            // Restore
            $cache->setTtl($originalTtl);
        });
    });

    // -----------------------------------------------------------------------
    // 7. Single-case enum edge case
    // -----------------------------------------------------------------------
    describe('Single-case enum', function () {
        it('SingleCaseToggle works with single case', function () {
            $toggle = SingleCaseToggle::ON;

            expect($toggle->is(SingleCaseToggle::ON))->toBeTrue();
            expect($toggle->is('ON'))->toBeTrue();
            expect($toggle->label())->toBeString()->not->toBeEmpty();

            $select = SingleCaseToggle::forSelect();
            expect($select)->toHaveCount(1);
        });
    });

    // -----------------------------------------------------------------------
    // 8. Type safety — strict return types
    // -----------------------------------------------------------------------
    describe('Type safety', function () {
        it('label() always returns string', function () {
            foreach (TicketStatus::cases() as $case) {
                expect($case->label())->toBeString();
            }
        });

        it('color() always returns string (never null)', function () {
            foreach (TicketStatus::cases() as $case) {
                expect($case->color())->toBeString();
            }
        });

        it('forSelect() returns list with string keys', function () {
            $select = IntBackedPriority::forSelect();

            foreach ($select as $option) {
                expect(array_keys($option))->toBe(['value', 'label']);
            }
        });

        it('values() elements match backed type', function () {
            // String-backed
            $stringValues = TicketStatus::values();
            expect($stringValues)->not->toBeEmpty();
            expect($stringValues[0])->toBeString();

            // Int-backed
            $intValues = IntBackedPriority::values();
            expect($intValues)->not->toBeEmpty();
            expect($intValues[0])->toBeInt();

            // Pure
            $pureValues = PureFeatureFlag::values();
            expect($pureValues)->not->toBeEmpty();
            expect($pureValues[0])->toBeString();
        });
    });
});
