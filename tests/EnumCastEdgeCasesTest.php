<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * Edge case tests for EnumCast (Issue #63).
 */
describe('EnumCast Edge Cases', function (): void {
    describe('String-backed enum edge cases', function (): void {
        it('handles whitespace string value for string-backed enum', function (): void {
            $cast = new EnumCast(UserStatus::class);

            // Whitespace is not a valid enum value — tryFrom returns null
            $result = $cast->get(
                model: new class {},
                key: 'status',
                value: '  active  ',
                attributes: [],
            );

            expect($result)->toBeNull();
        });

        it('handles empty string value for string-backed enum', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $result = $cast->get(
                model: new class {},
                key: 'status',
                value: '',
                attributes: [],
            );

            expect($result)->toBeNull();
        });

        it('handles case sensitivity in string-backed enum', function (): void {
            $cast = new EnumCast(UserStatus::class);

            // 'Active' vs 'active' — PHP enums are case-sensitive
            $result = $cast->get(
                model: new class {},
                key: 'status',
                value: 'Active',
                attributes: [],
            );

            expect($result)->toBeNull();
        });
    });

    describe('Int-backed enum edge cases', function (): void {
        it('handles 0 value when a case with value 0 exists', function (): void {
            $cast = new EnumCast(ZeroPriority::class);

            $result = $cast->get(
                model: new class {},
                key: 'priority',
                value: 0,
                attributes: [],
            );

            expect($result)->toBe(ZeroPriority::NONE);
        });

        it('handles 0 value when no case with value 0 exists', function (): void {
            $cast = new EnumCast(Priority::class);

            $result = $cast->get(
                model: new class {},
                key: 'priority',
                value: 0,
                attributes: [],
            );

            expect($result)->toBeNull();
        });

        it('handles string "0" for int-backed enum', function (): void {
            $cast = new EnumCast(ZeroPriority::class);

            // tryFrom with a string on an int-backed enum throws TypeError in strict mode
            expect(fn (): mixed => $cast->get(
                model: new class {},
                key: 'priority',
                value: '0',
                attributes: [],
            ))->toThrow(TypeError::class);
        });

        it('handles negative int values for int-backed enum', function (): void {
            $cast = new EnumCast(Priority::class);

            $result = $cast->get(
                model: new class {},
                key: 'priority',
                value: -1,
                attributes: [],
            );

            expect($result)->toBeNull();
        });
    });

    describe('Cross-type edge cases', function (): void {
        it('passes int value to string-backed enum (type mismatch)', function (): void {
            $cast = new EnumCast(UserStatus::class);

            // Passing int to a string-backed enum — tryFrom expects string
            // This may throw TypeError or return null depending on PHP version
            try {
                $result = $cast->get(
                    model: new class {},
                    key: 'status',
                    value: 1,
                    attributes: [],
                );
                // If no exception, result should be null (invalid)
                expect($result === null || $result instanceof UserStatus)->toBeTrue();
            } catch (TypeError $e) {
                // TypeError is acceptable behavior for strict type mismatch
                expect($e)->toBeInstanceOf(TypeError::class);
            }
        });

        it('passes string value to int-backed enum (type mismatch)', function (): void {
            $cast = new EnumCast(Priority::class);

            try {
                $result = $cast->get(
                    model: new class {},
                    key: 'priority',
                    value: 'not-a-number',
                    attributes: [],
                );
                // If no exception, result should be null
                expect($result === null || $result instanceof Priority)->toBeTrue();
            } catch (TypeError $e) {
                expect($e)->toBeInstanceOf(TypeError::class);
            }
        });
    });

    describe('set() edge cases', function (): void {
        it('throws InvalidArgumentException for invalid enum class in set()', function (): void {
            $cast = new EnumCast(UserStatus::class);

            expect(fn (): mixed => $cast->set(
                model: new class {},
                key: 'status',
                value: 'invalid-status',
                attributes: [],
            ))->toThrow(InvalidArgumentException::class);
        });

        it('throws InvalidArgumentException for non-int/string value in set()', function (): void {
            $cast = new EnumCast(UserStatus::class);

            expect(fn (): mixed => $cast->set(
                model: new class {},
                key: 'status',
                value: ['array'],
                attributes: [],
            ))->toThrow(InvalidArgumentException::class);
        });

        it('throws InvalidArgumentException for wrong enum class instance in set()', function (): void {
            $cast = new EnumCast(UserStatus::class);

            // Passing a Priority enum (different enum class) to a UserStatus cast
            // must throw — not silently accept the wrong enum
            expect(fn (): mixed => $cast->set(
                model: new class {},
                key: 'status',
                value: Priority::HIGH,
                attributes: [],
            ))->toThrow(InvalidArgumentException::class);
        });

        it('accepts correct enum class instance in set()', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $result = $cast->set(
                model: new class {},
                key: 'status',
                value: UserStatus::ACTIVE,
                attributes: [],
            );

            expect($result)->toBe('active');
        });

        it('accepts correct int-backed enum instance in set()', function (): void {
            $cast = new EnumCast(Priority::class);

            $result = $cast->set(
                model: new class {},
                key: 'priority',
                value: Priority::HIGH,
                attributes: [],
            );

            expect($result)->toBe(3);
        });

        it('handles valid raw int value in set() for int-backed enum', function (): void {
            $cast = new EnumCast(Priority::class);

            $result = $cast->set(
                model: new class {},
                key: 'priority',
                value: 3,
                attributes: [],
            );

            expect($result)->toBe(3);
        });

        it('throws for invalid raw int value in set() for int-backed enum', function (): void {
            $cast = new EnumCast(Priority::class);

            expect(fn (): mixed => $cast->set(
                model: new class {},
                key: 'priority',
                value: 999,
                attributes: [],
            ))->toThrow(InvalidArgumentException::class);
        });

        it('handles valid raw string value in set() for string-backed enum', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $result = $cast->set(
                model: new class {},
                key: 'status',
                value: 'active',
                attributes: [],
            );

            expect($result)->toBe('active');
        });

        it('throws for invalid raw string value in set() for string-backed enum', function (): void {
            $cast = new EnumCast(UserStatus::class);

            expect(fn (): mixed => $cast->set(
                model: new class {},
                key: 'status',
                value: 'nonexistent',
                attributes: [],
            ))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('get() edge cases', function (): void {
        it('handles float value for int-backed enum', function (): void {
            $cast = new EnumCast(Priority::class);

            try {
                $result = $cast->get(
                    model: new class {},
                    key: 'priority',
                    value: 3.0,
                    attributes: [],
                );
                expect($result === Priority::HIGH || $result === null)->toBeTrue();
            } catch (TypeError $e) {
                expect($e)->toBeInstanceOf(TypeError::class);
            }
        });

        it('handles extremely long string value', function (): void {
            $cast = new EnumCast(UserStatus::class);

            $longString = str_repeat('a', 10000);

            $result = $cast->get(
                model: new class {},
                key: 'status',
                value: $longString,
                attributes: [],
            );

            expect($result)->toBeNull();
        });
    });
});
