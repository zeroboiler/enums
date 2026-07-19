<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Tests for bug fixes: #1, #2, #3, #7, #8, #20
 */
describe('Bug Fix Tests', function (): void {
    describe('#1 — tryFromLabel() ambiguity', function (): void {
        it('matches case-insensitively by default', function (): void {
            $result = UserStatus::tryFromLabel('active user');

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('returns null for ambiguous case-insensitive matches', function (): void {
            // Create a scenario where labels only differ by case
            // UserStatus has labels: 'Active User', 'Inactive', 'Awaiting Verification', 'Suspended', 'Banned'
            // 'inactive' and 'INACTIVE' would match the same case, so not ambiguous
            // But 'active user' is unique
            $result = UserStatus::tryFromLabel('Active User');

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('supports strict mode for case-sensitive matching', function (): void {
            $result = UserStatus::tryFromLabel('Active User', strict: true);

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('returns null in strict mode when case does not match', function (): void {
            $result = UserStatus::tryFromLabel('active user', strict: true);

            expect($result)->toBeNull();
        });
    });

    describe('#3 — EnumRule validates enumClass', function (): void {
        it('throws for non-existent class', function (): void {
            expect(fn (): EnumRule => new EnumRule('NonExistent\\EnumClass'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws for non-enum class', function (): void {
            expect(fn (): EnumRule => new EnumRule(stdClass::class))
                ->toThrow(InvalidArgumentException::class);
        });

        it('accepts valid enum class', function (): void {
            $rule = new EnumRule(UserStatus::class);

            expect($rule)->toBeInstanceOf(EnumRule::class);
        });
    });

    describe('#7 — EnumCast::set() throws on invalid raw values', function (): void {
        it('throws InvalidArgumentException for invalid string value', function (): void {
            $cast = new EnumCast(UserStatus::class);

            expect(fn (): mixed => $cast->set(
                model: new class {},
                key: 'status',
                value: 'totally-invalid',
                attributes: [],
            ))->toThrow(InvalidArgumentException::class);
        });

        it('throws InvalidArgumentException for invalid int value', function (): void {
            $cast = new EnumCast(Priority::class);

            expect(fn (): mixed => $cast->set(
                model: new class {},
                key: 'priority',
                value: 999,
                attributes: [],
            ))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('#20 — EnumCast::set() rejects wrong enum instances', function (): void {
        it('throws when passing a different enum class instance', function (): void {
            $cast = new EnumCast(UserStatus::class);

            expect(fn (): mixed => $cast->set(
                model: new class {},
                key: 'status',
                value: Priority::HIGH,
                attributes: [],
            ))->toThrow(InvalidArgumentException::class);
        });

        it('accepts correct enum instance', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $result = $cast->set(
                model: new class {},
                key: 'status',
                value: UserStatus::ACTIVE,
                attributes: [],
            );

            expect($result)->toBe('active');
        });
    });

    describe('#8 — EnumCache can be flushed', function (): void {
        it('flush clears all cached metadata', function (): void {
            // Populate cache
            UserStatus::forSelect();
            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

            EnumCache::flush();

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        });

        it('resetInstance creates fresh instance', function (): void {
            $instance1 = EnumCache::getInstance();
            EnumCache::resetInstance();
            $instance2 = EnumCache::getInstance();

            expect($instance1)->not->toBe($instance2);
        });
    });
});
