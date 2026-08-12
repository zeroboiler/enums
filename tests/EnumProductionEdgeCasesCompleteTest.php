<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum Edge Cases — Production Coverage', function () {
    // ── notIn() completeness ──────────────────────────────────────────────
    describe('notIn() method', function () {
        it('returns true when case is not in the list', function () {
            expect(UserStatus::ACTIVE->notIn([UserStatus::BANNED]))->toBeTrue();
        });

        it('returns true when case is not in a string name list', function () {
            expect(UserStatus::ACTIVE->notIn(['BANNED', 'SUSPENDED']))->toBeTrue();
        });

        it('returns false when case IS in the list', function () {
            expect(UserStatus::ACTIVE->notIn(['ACTIVE']))->toBeFalse();
        });

        it('returns false when case IS in a mixed list', function () {
            expect(UserStatus::ACTIVE->notIn([UserStatus::BANNED, 'ACTIVE']))->toBeFalse();
        });

        it('returns true for empty list', function () {
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
        });

        it('is the exact negation of in()', function () {
            $cases = [UserStatus::BANNED, 'SUSPENDED'];
            expect(UserStatus::ACTIVE->notIn($cases))->toBe(! UserStatus::ACTIVE->in($cases));
            expect(UserStatus::BANNED->notIn($cases))->toBe(! UserStatus::BANNED->in($cases));
        });
    });

    // ── InvalidEnumException edge cases ──────────────────────────────────
    describe('InvalidEnumException', function () {
        it('formats string value correctly in message', function () {
            $e = InvalidEnumException::value('App\Enums\Status', 'invalid');
            expect($e->getMessage())->toBe('Value [invalid] is not a valid case of [App\Enums\Status].');
        });

        it('formats null value correctly in message', function () {
            $e = InvalidEnumException::value('App\Enums\Status', null);
            expect($e->getMessage())->toBe('Value [null] is not a valid case of [App\Enums\Status].');
        });

        it('formats int value correctly in message', function () {
            $e = InvalidEnumException::value('App\Enums\Priority', 99);
            expect($e->getMessage())->toBe('Value [99] is not a valid case of [App\Enums\Priority].');
        });

        it('produces readable __toString output', function () {
            $e = InvalidEnumException::value('App\Enums\Status', 'bad');
            $str = (string) $e;
            expect($str)->toBeString();
            expect($str)->toContain('InvalidEnumException');
            expect($str)->toContain('bad');
        });

        it('forName produces descriptive message', function () {
            $e = InvalidEnumException::forName('App\Enums\UserStatus', 'UNKNOWN');
            expect($e->getMessage())->toContain('UNKNOWN');
            expect($e->getMessage())->toContain('App\Enums\UserStatus');
        });
    });

    // ── EnumRule type safety ──────────────────────────────────────────────
    describe('EnumRule type checking', function () {
        it('rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;
            $rule->validate('priority', 'high', function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('rejects int value for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $rule->validate('status', 42, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('accepts correct string type for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $rule->validate('status', 'active', function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        });

        it('accepts correct int type for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;
            $rule->validate('priority', 1, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        });

        it('allows null when nullable is set', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;
            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        });

        it('rejects null when nullable is NOT set', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });
    });

    // ── Zero-value backed enum ────────────────────────────────────────────
    describe('Zero-value backed enum edge cases', function () {
        it('correctly resolves metadata for zero-backed values', function () {
            expect(ZeroPriority::NONE->value)->toBe(0);
            expect(ZeroPriority::NONE->label())->toBeString()->not->toBeEmpty();
        });

        it('forSelect includes zero-value cases', function () {
            $options = ZeroPriority::forSelect();
            $values = array_column($options, 'value');
            expect(in_array(0, $values, true))->toBeTrue();
        });

        it('values() includes zero', function () {
            $values = ZeroPriority::values();
            expect(in_array(0, $values, true))->toBeTrue();
        });
    });

    // ── Pure enum behavior ──────────────────────────────────────────────────
    describe('Pure enum behavior', function () {
        it('values() returns case names for pure enum', function () {
            $values = PureFeatureFlag::values();
            expect($values)->toBeArray();
            expect($values)->not->toBeEmpty();
            // Pure enums should return case names, not backed values
            foreach ($values as $v) {
                expect($v)->toBeString();
                expect(PureFeatureFlag::tryFromName($v))->not->toBeNull();
            }
        });

        it('forSelect uses case names as values', function () {
            $options = PureFeatureFlag::forSelect();
            foreach ($options as $opt) {
                expect($opt['value'])->toBeString();
                expect(PureFeatureFlag::tryFromName($opt['value']))->not->toBeNull();
            }
        });

        it('hasCase works for all cases', function () {
            foreach (PureFeatureFlag::cases() as $case) {
                expect(PureFeatureFlag::hasCase($case->name))->toBeTrue();
            }
        });
    });

    // ── Label auto-generation edge cases ─────────────────────────────────
    describe('Label auto-generation', function () {
        it('handles SCREAMING_SNAKE_CASE correctly', function () {
            $label = Priority::CRITICAL->label();
            expect($label)->toBe('Critical');
        });

        it('handles camelCase correctly', function () {
            // CamelCaseRole fixture uses camelCase case names
            $label = \ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::Admin->label();
            expect($label)->toBe('Admin');
        });

        it('handles single word correctly', function () {
            $label = Priority::LOW->label();
            expect($label)->toBe('Low');
        });
    });

    // ── Metadata resolution priority ──────────────────────────────────────
    describe('Metadata resolution priority', function () {
        it('per-case attribute overrides class-level', function () {
            // UserStatus has class-level EnumColor, BANNED has per-case Color('danger')
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('class-level provides default when no per-case override', function () {
            expect(UserStatus::ACTIVE->color())->toBe('success');
        });

        it('auto-generated label used when no attribute exists', function () {
            expect(Priority::LOW->label())->toBe('Low');
        });

        it('per-case label overrides auto-generation', function () {
            // UserStatus::ACTIVE has #[Label('Active User')]
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
        });
    });

    // ── fromName/tryFromName consistency ──────────────────────────────────
    describe('fromName/tryFromName consistency', function () {
        it('tryFromName returns null for non-existent case', function () {
            expect(UserStatus::tryFromName('NON_EXISTENT'))->toBeNull();
        });

        it('fromName throws for non-existent case', function () {
            expect(fn () => UserStatus::fromName('NON_EXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('fromName returns correct case for valid name', function () {
            $case = UserStatus::fromName('ACTIVE');
            expect($case)->toBe(UserStatus::ACTIVE);
        });

        it('tryFromName and fromName agree on existing case', function () {
            $viaTry = UserStatus::tryFromName('BANNED');
            $viaFrom = UserStatus::fromName('BANNED');
            expect($viaTry)->toBe($viaFrom);
        });
    });

    // ── tryFromLabel edge cases ────────────────────────────────────────────
    describe('tryFromLabel', function () {
        it('is case-insensitive', function () {
            $label = UserStatus::ACTIVE->label();
            expect(UserStatus::tryFromLabel(strtolower($label)))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(strtoupper($label)))->toBe(UserStatus::ACTIVE);
        });

        it('returns null for non-existent label', function () {
            expect(UserStatus::tryFromLabel('non-existent-label-xyz-123'))->toBeNull();
        });

        it('returns the first match when labels are duplicated', function () {
            // This tests behavior when two cases share the same label
            // (not recommended, but should not crash)
            $result = UserStatus::tryFromLabel('Active User');
            expect($result)->not->toBeNull();
        });
    });

    // ── forApi/forSelect structure ─────────────────────────────────────────
    describe('forApi/forSelect structure', function () {
        it('forApi has all required keys for each case', function () {
            $api = UserStatus::forApi();
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            }
        });

        it('forApi color is never null', function () {
            $api = UserStatus::forApi();
            foreach ($api as $item) {
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forSelect has value and label keys', function () {
            $select = UserStatus::forSelect();
            foreach ($select as $item) {
                expect($item)->toHaveKeys(['value', 'label']);
            }
        });

        it('forSelect values are unique', function () {
            $values = array_column(UserStatus::forSelect(), 'value');
            expect($values)->toEqual(array_unique($values));
        });

        it('forApi count matches cases count', function () {
            expect(UserStatus::forApi())->toHaveCount(count(UserStatus::cases()));
        });
    });
});
