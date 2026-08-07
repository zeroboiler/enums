<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum full metadata resolution edge cases', function () {
    describe('class-level defaults with per-case overrides', function () {
        it('returns class-level color when no per-case override exists', function () {
            $status = AllClassLevelEnum::ACTIVE;

            expect($status->color())->toBe('success');
        });

        it('prefers per-case color over class-level color', function () {
            $status = AllClassLevelEnum::BANNED;

            expect($status->color())->toBe('danger');
        });
    });

    describe('auto-generated labels', function () {
        it('generates Title Case from SCREAMING_SNAKE_CASE', function () {
            $priority = Priority::CRITICAL;

            expect($priority->label())->toBe('Critical');
        });

        it('generates Title Case from camelCase', function () {
            $role = CamelCaseRole::SuperAdmin;

            expect($role->label())->toBe('Super Admin');
        });
    });

    describe('pure enum without backing value', function () {
        it('values() returns case names for pure enums', function () {
            $values = SingleCaseEnum::values();

            expect($values)->toContain('ONLY_CASE');
        });

        it('forSelect() uses case names for pure enums', function () {
            $options = SingleCaseEnum::forSelect();

            expect($options)->not->toBeEmpty();
            expect($options[0])->toHaveKey('value');
            expect($options[0])->toHaveKey('label');
            expect($options[0]['value'])->toBe('ONLY_CASE');
        });

        it('forApi() uses case names for pure enums', function () {
            $api = SingleCaseEnum::forApi();

            expect($api)->not->toBeEmpty();
            expect($api[0]['value'])->toBe('ONLY_CASE');
            expect($api[0]['name'])->toBe('ONLY_CASE');
        });
    });

    describe('int-backed enum', function () {
        it('values() returns int values', function () {
            $values = Priority::values();

            foreach ($values as $value) {
                expect(is_int($value))->toBeTrue();
            }
        });

        it('forSelect() uses int values', function () {
            $options = Priority::forSelect();

            foreach ($options as $option) {
                expect(is_int($option['value']))->toBeTrue();
                expect($option['label'])->toBeString();
            }
        });

        it('is() works with int-backed enum instances', function () {
            expect(Priority::HIGH->is(Priority::HIGH))->toBeTrue();
            expect(Priority::HIGH->is(Priority::LOW))->toBeFalse();
        });

        it('is() works with case name strings', function () {
            expect(Priority::HIGH->is('HIGH'))->toBeTrue();
            expect(Priority::HIGH->is('LOW'))->toBeFalse();
        });
    });

    describe('label case-insensitive lookup', function () {
        it('tryFromLabel matches case-insensitively', function () {
            $result = UserStatus::tryFromLabel('active user');

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel returns null for non-matching label', function () {
            $result = UserStatus::tryFromLabel('nonexistent label');

            expect($result)->toBeNull();
        });

        it('tryFromLabel works with auto-generated labels', function () {
            // Priority::CRITICAL has auto-generated label "Critical"
            $result = Priority::tryFromLabel('Critical');

            expect($result)->toBe(Priority::CRITICAL);
        });
    });

    describe('zero-value edge cases', function () {
        it('handles int value 0 correctly', function () {
            $zero = ZeroPriority::ZERO;

            expect($zero->value)->toBe(0);
            expect($zero->label())->toBeString()->not->toBeEmpty();
            expect($zero->color())->toBeString();
        });
    });

    describe('bulk methods consistency', function () {
        it('forSelect() count matches cases() count', function () {
            $selectCount = count(UserStatus::forSelect());
            $casesCount = count(UserStatus::cases());

            expect($selectCount)->toBe($casesCount);
        });

        it('forApi() count matches cases() count', function () {
            $apiCount = count(UserStatus::forApi());
            $casesCount = count(UserStatus::cases());

            expect($apiCount)->toBe($casesCount);
        });

        it('values() count matches cases() count', function () {
            $valuesCount = count(UserStatus::values());
            $casesCount = count(UserStatus::cases());

            expect($valuesCount)->toBe($casesCount);
        });

        it('labels() count matches cases() count', function () {
            $labelsCount = count(UserStatus::labels());
            $casesCount = count(UserStatus::cases());

            expect($labelsCount)->toBe($casesCount);
        });
    });

    describe('in() with mixed types', function () {
        it('accepts mix of instances and strings', function () {
            $status = UserStatus::ACTIVE;

            // Mix of instance and string
            expect($status->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        });

        it('rejects when none match', function () {
            $status = UserStatus::ACTIVE;

            expect($status->in(['PENDING', 'BANNED']))->toBeFalse();
        });

        it('works with single-element array', function () {
            $status = UserStatus::ACTIVE;

            expect($status->in(['ACTIVE']))->toBeTrue();
        });

        it('returns false for empty array', function () {
            $status = UserStatus::ACTIVE;

            expect($status->in([]))->toBeFalse();
        });
    });
});
