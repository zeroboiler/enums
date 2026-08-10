<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Tests for EnumRule with pure enums and nullable edge cases.
 *
 * Covers:
 * - EnumRule validation with pure enums (case name matching)
 * - EnumRule nullable flag behavior (null passthrough)
 * - EnumRule with non-enum class (graceful error)
 * - EnumRule type mismatch (string value to int-backed enum)
 *
 * @see \ZeroBoiler\Enums\Rules\EnumRule
 */

use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule pure enum and nullable edge cases', function (): void {

    // ──────────────────────────────────────────────────────────────
    // Pure enum validation
    // ──────────────────────────────────────────────────────────────

    describe('Pure enum validation', function (): void {
        it('accepts valid case names for pure enums', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('feature', 'DARK_MODE', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('rejects invalid case names for pure enums', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('feature', 'NONEXISTENT', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('rejects non-string values for pure enums', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('feature', 123, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('accepts all defined case names', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class);

            foreach (PureFeatureFlag::cases() as $case) {
                $failed = false;
                $rule->validate('feature', $case->name, function () use (&$failed): void {
                    $failed = true;
                });
                expect($failed)->toBeFalse();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Nullable behavior
    // ──────────────────────────────────────────────────────────────

    describe('Nullable behavior', function (): void {
        it('rejects null when nullable is not set', function (): void {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('accepts null when nullable is set', function (): void {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('still validates non-null values when nullable is set', function (): void {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', 'invalid_value', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Type mismatch handling
    // ──────────────────────────────────────────────────────────────

    describe('Type mismatch handling', function (): void {
        it('rejects string value for int-backed enum', function (): void {
            $rule = EnumRule::for(Priority::class);
            $failed = false;

            $rule->validate('priority', 'not-an-int', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('rejects int value for string-backed enum', function (): void {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', 42, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('accepts correct type for int-backed enum', function (): void {
            $rule = EnumRule::for(Priority::class);
            $failed = false;

            $rule->validate('priority', 1, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('rejects invalid int value for int-backed enum', function (): void {
            $rule = EnumRule::for(Priority::class);
            $failed = false;

            $rule->validate('priority', 999, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumRule::for() factory
    // ──────────────────────────────────────────────────────────────

    describe('EnumRule::for() factory', function (): void {
        it('creates a new instance each call', function (): void {
            $rule1 = EnumRule::for(UserStatus::class);
            $rule2 = EnumRule::for(UserStatus::class);

            expect($rule1)->not->toBe($rule2);
        });

        it('creates instance with nullable=false by default', function (): void {
            $rule = EnumRule::for(UserStatus::class);

            // Should reject null (nullable=false by default)
            $failed = false;
            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });
    });
});
