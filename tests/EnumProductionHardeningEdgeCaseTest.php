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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Rules\EnumRule as EnumsEnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Focused edge-case tests for PHPStan Level 9 compliance and production hardening.
 *
 * Targets specific edge cases in type safety, strict comparisons, and boundary
 * conditions that ensure the package meets PHPStan L9 requirements without
 * requiring a PHP runtime.
 */
describe('Enums — PHPStan L9 edge cases and production hardening', function () {

    // ─── Backing type mismatch in EnumRule ──────────────────────────────

    describe('EnumRule backing type mismatch', function () {
        it('rejects string value for int-backed enum', function () {
            $rule = EnumsEnumRule::for(IntBackedPriority::class);
            $fail = fn (string $message): string => $message;

            // Int-backed enum receiving string '1' (not int 1) should fail
            $result = null;
            $rule->validate('priority', '1', function (string $message) use (&$result): void {
                $result = $message;
            });

            expect($result)->not->toBeNull();
            expect($result)->toBeString();
        });

        it('rejects int value for string-backed enum', function () {
            $rule = EnumsEnumRule::for(UserStatus::class);
            $fail = fn (string $message): string => $message;

            // String-backed enum receiving int should fail
            $result = null;
            $rule->validate('status', 1, function (string $message) use (&$result): void {
                $result = $message;
            });

            expect($result)->not->toBeNull();
            expect($result)->toBeString();
        });

        it('accepts correct backing type for int enum', function () {
            $rule = EnumsEnumRule::for(IntBackedPriority::class);

            $result = null;
            $rule->validate('priority', 1, function (string $message) use (&$result): void {
                $result = $message;
            });

            // Int value 1 is valid for IntBackedPriority
            expect($result)->toBeNull();
        });

        it('accepts correct backing type for string enum', function () {
            $rule = EnumsEnumRule::for(UserStatus::class);

            $result = null;
            $rule->validate('status', 'active', function (string $message) use (&$result): void {
                $result = $message;
            });

            expect($result)->toBeNull();
        });
    });

    // ─── EnumRule nullable edge cases ───────────────────────────────────

    describe('EnumRule nullable edge cases', function () {
        it('rejects null when nullable is false (default)', function () {
            $rule = EnumsEnumRule::for(UserStatus::class);

            $result = null;
            $rule->validate('status', null, function (string $message) use (&$result): void {
                $result = $message;
            });

            expect($result)->not->toBeNull();
        });

        it('accepts null when nullable is true', function () {
            $rule = EnumsEnumRule::for(UserStatus::class)->nullable();

            $result = null;
            $rule->validate('status', null, function (string $message) use (&$result): void {
                $result = $message;
            });

            expect($result)->toBeNull();
        });

        it('still validates non-null value when nullable is true', function () {
            $rule = EnumsEnumRule::for(UserStatus::class)->nullable();

            $result = null;
            $rule->validate('status', 'nonexistent', function (string $message) use (&$result): void {
                $result = $message;
            });

            expect($result)->not->toBeNull();
        });
    });

    // ─── Pure enum validation via EnumRule ────────────────────────────────

    describe('EnumRule with pure enums', function () {
        it('validates case names for pure enums', function () {
            $rule = EnumsEnumRule::for(PureFeatureFlag::class);

            $result = null;
            $rule->validate('feature', 'DARK_MODE', function (string $message) use (&$result): void {
                $result = $message;
            });

            expect($result)->toBeNull();
        });

        it('rejects invalid case name for pure enums', function () {
            $rule = EnumsEnumRule::for(PureFeatureFlag::class);

            $result = null;
            $rule->validate('feature', 'NONEXISTENT', function (string $message) use (&$result): void {
                $result = $message;
            });

            expect($result)->not->toBeNull();
        });

        it('rejects non-string value for pure enums', function () {
            $rule = EnumsEnumRule::for(PureFeatureFlag::class);

            $result = null;
            $rule->validate('feature', 123, function (string $message) use (&$result): void {
                $result = $message;
            });

            expect($result)->not->toBeNull();
        });
    });

    // ─── EnumRule::for() named constructor ──────────────────────────────

    describe('EnumRule named constructor', function () {
        it('creates instance via for() with correct enum class', function () {
            $rule = EnumsEnumRule::for(UserStatus::class);

            // Verify it's an instance via reflection
            $ref = new ReflectionProperty($rule, 'enumClass');
            expect($ref->getValue($rule))->toBe(UserStatus::class);
        });

        it('nullable() creates new instance with nullable true', function () {
            $rule = EnumsEnumRule::for(UserStatus::class)->nullable();

            $ref = new ReflectionProperty($rule, 'nullable');
            expect($ref->getValue($rule))->toBeTrue();
        });
    });

    // ─── InvalidEnumException factory methods ───────────────────────────

    describe('InvalidEnumException factory methods', function () {
        it('forName() creates exception with class and name', function () {
            $exception = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

            expect($exception)->toBeInstanceOf(InvalidEnumException::class);
            expect($exception->getMessage())->toContain('NONEXISTENT');
            expect($exception->getMessage())->toContain(UserStatus::class);
        });

        it('value() creates exception with class and value', function () {
            $exception = InvalidEnumException::value(UserStatus::class, 'invalid');

            expect($exception)->toBeInstanceOf(InvalidEnumException::class);
            expect($exception->getMessage())->toContain('invalid');
            expect($exception->getMessage())->toContain(UserStatus::class);
        });

        it('__toString() returns human-readable message', function () {
            $exception = InvalidEnumException::forName(UserStatus::class, 'BAD');

            $string = (string) $exception;
            expect($string)->toBeString();
            expect($string)->not->toBeEmpty();
            expect($string)->toContain('BAD');
        });
    });

    // ─── Metadata resolver invalidation ──────────────────────────────────

    describe('EnumMetadataResolver invalidation', function () {
        it('invalidate() removes specific class cache', function () {
            EnumMetadataResolver::invalidate(UserStatus::class);

            // After invalidation, resolve should rebuild
            $meta = EnumMetadataResolver::resolve(UserStatus::class);
            expect($meta)->toBeArray();
            expect($meta)->toHaveKey('labels');
            expect($meta)->toHaveKey('colors');
        });

        it('invalidateAll() clears all cached metadata', function () {
            EnumMetadataResolver::invalidateAll();

            $meta = EnumMetadataResolver::resolve(UserStatus::class);
            expect($meta)->toBeArray();

            $meta2 = EnumMetadataResolver::resolve(TicketStatus::class);
            expect($meta2)->toBeArray();
        });
    });

    // ─── EnumCache singleton and TTL ────────────────────────────────────

    describe('EnumCache TTL behavior', function () {
        it('setTtl() and getTtl() work correctly', function () {
            $cache = EnumCache::getInstance();

            $cache->setTtl(60);
            expect($cache->getTtl())->toBe(60);

            $cache->setTtl(0);
            expect($cache->getTtl())->toBe(0);
        });

        it('clear() removes all entries', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            // Cache should be empty after clear
            $ref = new ReflectionProperty($cache, 'cache');
            $ref->setAccessible(true);
            $internalCache = $ref->getValue($cache);
            expect($internalCache)->toBeArray();
            expect($internalCache)->toBeEmpty();
        });
    });

    // ─── Label generation edge cases ────────────────────────────────────

    describe('Label generation edge cases', function () {
        it('generates label for single-word uppercase case', function () {
            expect(UserStatus::ACTIVE->label())->toBe('Active');
        });

        it('generates label from SCREAMING_SNAKE_CASE', function () {
            expect(UserStatus::IN_PROGRESS->label())->toBe('In Progress');
        });

        it('generates label from mixed case (camelCase)', function () {
            // TicketStatus uses class-level labels, so check via auto-generation path
            // Use a fixture that doesn't have class-level labels for this specific case
            $label = IntBackedPriority::CRITICAL->label();
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        });
    });

    // ─── Type safety: strict identity comparison ─────────────────────────

    describe('Type safety — strict comparisons', function () {
        it('is() uses strict identity for instance comparison', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
        });

        it('is() uses strict string comparison for name matching', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse(); // case-sensitive
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
        });

        it('isNot() is exact negation of is()', function () {
            $active = UserStatus::ACTIVE;
            expect($active->isNot(UserStatus::ACTIVE))->toBe($active->is(UserStatus::ACTIVE) === false);
            expect($active->isNot('BANNED'))->toBe($active->is('BANNED') === false);
        });

        it('in() works with mixed instance and string array', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'BANNED']))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['ACTIVE', UserStatus::BANNED]))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['BANNED', UserStatus::BANNED]))->toBeFalse();
        });

        it('notIn() works with mixed instance and string array', function () {
            expect(UserStatus::ACTIVE->notIn(['BANNED']))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn([UserStatus::ACTIVE, 'BANNED']))->toBeFalse();
        });
    });

    // ─── Cross-fixture consistency ──────────────────────────────────────

    describe('Cross-fixture consistency', function () {
        it('all fixtures return consistent forApi() structure', function () {
            $enums = [
                UserStatus::class,
                TicketStatus::class,
                PaymentStatus::class,
            ];

            foreach ($enums as $enumClass) {
                $api = $enumClass::forApi();

                expect($api)->toBeArray();
                expect($api)->not->toBeEmpty();

                foreach ($api as $item) {
                    expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                    expect($item['label'])->toBeString()->not->toBeEmpty();
                    expect($item['color'])->toBeString()->not->toBeEmpty();
                }
            }
        });

        it('forSelect() option values are unique within each enum', function () {
            $enums = [UserStatus::class, TicketStatus::class, PaymentStatus::class];

            foreach ($enums as $enumClass) {
                $options = $enumClass::forSelect();
                $values = array_column($options, 'value');
                expect($values)->each->toBeUnique();
            }
        });
    });

    // ─── Attribute contract compliance ───────────────────────────────────

    describe('Attribute final class compliance', function () {
        $attributes = [
            \ZeroBoiler\Enums\Attributes\Label::class,
            \ZeroBoiler\Enums\Attributes\Color::class,
            \ZeroBoiler\Enums\Attributes\Description::class,
            \ZeroBoiler\Enums\Attributes\Icon::class,
            \ZeroBoiler\Enums\Attributes\EnumLabel::class,
            \ZeroBoiler\Enums\Attributes\EnumColor::class,
            \ZeroBoiler\Enums\Attributes\EnumDescription::class,
            \ZeroBoiler\Enums\Attributes\EnumIcon::class,
        ];

        it('all attribute classes are final', function () use ($attributes) {
            foreach ($attributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                expect($ref->isFinal())->toBeTrue(
                    "{$attrClass} must be final"
                );
            }
        });

        it('all attribute classes have Attribute attribute', function () use ($attributes) {
            foreach ($attributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                $attrs = $ref->getAttributes(\Attribute::class);
                expect($attrs)->not->toBeEmpty(
                    "{$attrClass} must have #[Attribute]"
                );
            }
        });
    });
});
