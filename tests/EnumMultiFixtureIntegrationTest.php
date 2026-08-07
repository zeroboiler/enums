<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum Multi-Fixture Integration Tests', function () {
    describe('metadata resolution across different enum types', function () {
        it('resolves string-backed enum metadata correctly', function () {
            $meta = UserStatus::forApi();
            expect($meta)->toBeArray();
            expect(count($meta))->toBe(5);

            $active = collect($meta)->first(fn (array $item): bool => $item['name'] === 'ACTIVE');
            expect($active)->not->toBeNull();
            expect($active['value'])->toBe('active');
            expect($active['label'])->toBe('Active User');
            expect($active['description'])->toBe('User can fully access the system');
            expect($active['color'])->toBe('success');
            expect($active['icon'])->toBe('heroicon-o-check-circle');
        });

        it('resolves int-backed enum metadata correctly', function () {
            $meta = Priority::forApi();
            expect($meta)->toBeArray();
            expect(count($meta))->toBe(4);

            $low = collect($meta)->first(fn (array $item): bool => $item['name'] === 'LOW');
            expect($low)->not->toBeNull();
            expect($low['value'])->toBe(1);
            expect($low['label'])->toBe('Low');
            expect($low['color'])->toBe('secondary');
        });

        it('resolves pure enum metadata correctly', function () {
            $meta = RequestState::forApi();
            expect($meta)->toBeArray();

            $open = collect($meta)->first(fn (array $item): bool => $item['name'] === 'OPEN');
            expect($open)->not->toBeNull();
            expect($open['value'])->toBe('OPEN');
            expect($open['label'])->toBeString()->not->toBeEmpty();
        });
    });

    describe('cross-enum type safety', function () {
        it('values() returns correct types for string enums', function () {
            $values = UserStatus::values();
            expect($values)->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);
        });

        it('values() returns correct types for int enums', function () {
            $values = Priority::values();
            expect($values)->toBe([1, 2, 3, 4]);
        });

        it('values() returns case names for pure enums', function () {
            $values = RequestState::values();
            expect($values)->toBeArray();
            expect($values)->toContain('OPEN');
        });

        it('forSelect() produces consistent value types per enum', function () {
            $userOptions = UserStatus::forSelect();
            foreach ($userOptions as $option) {
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
            }

            $priorityOptions = Priority::forSelect();
            foreach ($priorityOptions as $option) {
                expect($option['value'])->toBeInt();
                expect($option['label'])->toBeString();
            }
        });
    });

    describe('lookup methods across fixtures', function () {
        it('tryFromLabel works across all fixtures', function () {
            expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('INACTIVE'))->toBe(UserStatus::INACTIVE);
            expect(UserStatus::tryFromLabel('invalid'))->toBeNull();

            expect(Priority::tryFromLabel('High'))->toBe(Priority::HIGH);
            expect(Priority::tryFromLabel('nonexistent'))->toBeNull();
        });

        it('tryFromName is case-sensitive across fixtures', function () {
            expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromName('active'))->toBeNull();
            expect(Priority::tryFromName('HIGH'))->toBe(Priority::HIGH);
            expect(Priority::tryFromName('high'))->toBeNull();
        });

        it('fromName throws for invalid names across fixtures', function () {
            expect(fn () => UserStatus::fromName('INVALID'))
                ->toThrow(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class);

            expect(fn () => Priority::fromName('NONEXISTENT'))
                ->toThrow(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class);
        });

        it('hasCase returns correct boolean across fixtures', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('INVALID'))->toBeFalse();
            expect(Priority::hasCase('LOW'))->toBeTrue();
            expect(Priority::hasCase('LOWEST'))->toBeFalse();
        });
    });

    describe('comparison methods across fixtures', function () {
        it('is() works with instances and strings for all enum types', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();

            expect(Priority::HIGH->is(Priority::HIGH))->toBeTrue();
            expect(Priority::HIGH->is('HIGH'))->toBeTrue();
            expect(Priority::HIGH->is(3))->toBeFalse();
        });

        it('isNot() negates is() correctly', function () {
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot('BANNED'))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
        });

        it('in() works with mixed instances and strings', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
            expect(UserStatus::BANNED->in([UserStatus::ACTIVE, 'PENDING']))->toBeFalse();
            expect(Priority::HIGH->in(['HIGH', 'URGENT']))->toBeTrue();
        });

        it('in() returns false for empty array', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
            expect(Priority::LOW->in([]))->toBeFalse();
        });
    });

    describe('label generation edge cases', function () {
        it('generates label from camelCase enum', function () {
            $label = CamelCaseRole::Admin->label();
            expect($label)->toBeString()->not->toBeEmpty();
        });

        it('handles zero value in int-backed enum', function () {
            $label = ZeroPriority::NONE->label();
            expect($label)->toBeString()->not->toBeEmpty();
        });

        it('handles single case enum', function () {
            expect(SingleCaseEnum::ONLY_ONE->label())->toBeString();
            expect(SingleCaseEnum::cases())->toHaveCount(1);
            expect(SingleCaseEnum::forSelect())->toHaveCount(1);
            expect(SingleCaseEnum::values())->toHaveCount(1);
            expect(SingleCaseEnum::labels())->toHaveCount(1);
        });
    });

    describe('default color fallback', function () {
        it('returns secondary for enums without color attributes', function () {
            expect(Priority::LOW->color())->toBe('secondary');
            expect(Priority::HIGH->color())->toBe('secondary');
        });

        it('returns null for undefined icons', function () {
            expect(Priority::LOW->icon())->toBeNull();
            expect(Priority::HIGH->icon())->toBeNull();
        });

        it('returns null for undefined descriptions', function () {
            expect(Priority::LOW->description())->toBeNull();
        });
    });

    describe('bulk method consistency', function () {
        it('forSelect and forApi have same order as cases()', function () {
            $cases = UserStatus::cases();
            $select = UserStatus::forSelect();
            $api = UserStatus::forApi();

            expect(count($select))->toBe(count($cases));
            expect(count($api))->toBe(count($cases));

            for ($i = 0; $i < count($cases); $i++) {
                expect($select[$i]['value'])->toBe($api[$i]['value']);
                expect($select[$i]['label'])->toBe($api[$i]['label']);
            }
        });

        it('labels() returns same labels as individual label() calls', function () {
            $labels = UserStatus::labels();
            $cases = UserStatus::cases();

            for ($i = 0; $i < count($cases); $i++) {
                expect($labels[$i])->toBe($cases[$i]->label());
            }
        });

        it('values() returns same values as individual value access', function () {
            $values = Priority::values();
            $cases = Priority::cases();

            for ($i = 0; $i < count($cases); $i++) {
                expect($values[$i])->toBe($cases[$i]->value);
            }
        });
    });
});
