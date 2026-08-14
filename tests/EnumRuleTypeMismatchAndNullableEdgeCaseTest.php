<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * EnumRule type mismatch and edge-case tests.
 *
 * Validates EnumRule behavior with type mismatches, nullable semantics,
 * backing type enforcement, and edge-case inputs for both backed and pure enums.
 *
 * @see \ZeroBoiler\Enums\Rules\EnumRule
 * @see \ZeroBoiler\Enums\Rules\EnumRule::validate()
 */

use ZeroBoiler\Enums\Rules\EnumRule;

// ── Test Enums ─────────────────────────────────────────────────

enum RuleStringStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}

enum RuleIntPriority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;
}

enum RulePureFeature
{
    case TwoFactorAuth;
    case DarkMode;
    case RateLimiting;
}

// ── Tests ─────────────────────────────────────────────────────

describe('EnumRule — Type Mismatch Edge Cases', function (): void {
    it('rejects string value for int-backed enum (type safety)', function (): void {
        $rule = EnumRule::for(RuleIntPriority::class);
        $failCalled = false;
        $failMessage = '';

        $rule->validate('priority', 'high', function (string $message) use (&$failCalled, &$failMessage): void {
            $failCalled = true;
            $failMessage = $message;
        });

        expect($failCalled)->toBeTrue();
        expect($failMessage)->toBeString()->not->toBeEmpty();
    });

    it('rejects int value for string-backed enum (type safety)', function (): void {
        $rule = EnumRule::for(RuleStringStatus::class);
        $failCalled = false;

        $rule->validate('status', 42, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('rejects float value for int-backed enum (type safety)', function (): void {
        $rule = EnumRule::for(RuleIntPriority::class);
        $failCalled = false;

        $rule->validate('priority', 2.5, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('rejects array value for string-backed enum', function (): void {
        $rule = EnumRule::for(RuleStringStatus::class);
        $failCalled = false;

        $rule->validate('status', ['active'], function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('rejects boolean false for string-backed enum', function (): void {
        $rule = EnumRule::for(RuleStringStatus::class);
        $failCalled = false;

        $rule->validate('status', false, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('rejects boolean true for int-backed enum', function (): void {
        $rule = EnumRule::for(RuleIntPriority::class);
        $failCalled = false;

        $rule->validate('priority', true, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });
});

describe('EnumRule — Nullable Semantics', function (): void {
    it('rejects null by default', function (): void {
        $rule = EnumRule::for(RuleStringStatus::class);
        $failCalled = false;

        $rule->validate('status', null, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('accepts null when nullable() is called', function (): void {
        $rule = EnumRule::for(RuleStringStatus::class)->nullable();
        $failCalled = false;

        $rule->validate('status', null, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeFalse();
    });

    it('still validates non-null values when nullable is enabled', function (): void {
        $rule = EnumRule::for(RuleStringStatus::class)->nullable();
        $failCalled = false;

        $rule->validate('status', 'nonexistent', function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });
});

describe('EnumRule — Backed Enum Validation', function (): void {
    it('accepts valid string-backed value', function (): void {
        $rule = EnumRule::for(RuleStringStatus::class);
        $failCalled = false;

        $rule->validate('status', 'active', function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeFalse();
    });

    it('accepts valid int-backed value', function (): void {
        $rule = EnumRule::for(RuleIntPriority::class);
        $failCalled = false;

        $rule->validate('priority', 3, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeFalse();
    });

    it('rejects invalid string-backed value', function (): void {
        $rule = EnumRule::for(RuleStringStatus::class);
        $failCalled = false;

        $rule->validate('status', 'deleted', function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('rejects invalid int-backed value', function (): void {
        $rule = EnumRule::for(RuleIntPriority::class);
        $failCalled = false;

        $rule->validate('priority', 99, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('includes allowed values in error message', function (): void {
        $rule = EnumRule::for(RuleStringStatus::class);
        $failMessage = '';

        $rule->validate('status', 'unknown', function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        });

        expect($failMessage)->toContain('active');
        expect($failMessage)->toContain('inactive');
        expect($failMessage)->toContain('pending');
    });

    it('includes allowed int values in error message', function (): void {
        $rule = EnumRule::for(RuleIntPriority::class);
        $failMessage = '';

        $rule->validate('priority', 99, function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        });

        expect($failMessage)->toContain('1');
        expect($failMessage)->toContain('2');
        expect($failMessage)->toContain('3');
        expect($failMessage)->toContain('4');
    });
});

describe('EnumRule — Pure Enum Validation', function (): void {
    it('accepts valid case name', function (): void {
        $rule = EnumRule::for(RulePureFeature::class);
        $failCalled = false;

        $rule->validate('feature', 'DarkMode', function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeFalse();
    });

    it('rejects invalid case name', function (): void {
        $rule = EnumRule::for(RulePureFeature::class);
        $failCalled = false;

        $rule->validate('feature', 'NonExistent', function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('rejects non-string value for pure enum', function (): void {
        $rule = EnumRule::for(RulePureFeature::class);
        $failCalled = false;

        $rule->validate('feature', 42, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('rejects backed value string for pure enum', function (): void {
        $rule = EnumRule::for(RulePureFeature::class);
        $failCalled = false;

        // 'dark_mode' is snake_case but the case name is 'DarkMode'
        $rule->validate('feature', 'dark_mode', function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });
});

describe('EnumRule — Structural Contract', function (): void {
    it('is a final readonly class', function (): void {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->isFinal())->toBeTrue();

        $constructor = $ref->getConstructor();
        expect($constructor)->not->toBeNull();

        $params = $constructor->getParameters();
        // enumClass and nullable should both be readonly
        foreach ($params as $param) {
            $propRef = $ref->getProperty($param->getName());
            expect($propRef->isReadOnly(), "Property {$param->getName()} should be readonly")
                ->toBeTrue();
        }
    });

    it('implements ValidationRule interface', function (): void {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Validation\ValidationRule::class))
            ->toBeTrue();
    });

    it('has declare(strict_types=1)', function (): void {
        $ref = new ReflectionClass(EnumRule::class);
        $contents = file_get_contents((string) $ref->getFileName());
        expect($contents)->toContain('declare(strict_types=1)');
    });

    it('validate method has Override attribute', function (): void {
        $method = new ReflectionMethod(EnumRule::class, 'validate');
        $attrs = $method->getAttributes();
        $hasOverride = false;
        foreach ($attrs as $attr) {
            if ($attr->getName() === 'Override') {
                $hasOverride = true;
                break;
            }
        }
        expect($hasOverride)->toBeTrue();
    });

    it('nullable() returns new instance (immutable)', function (): void {
        $rule = EnumRule::for(RuleStringStatus::class);
        $nullable = $rule->nullable();
        expect($nullable)->not->toBe($rule);
        expect($nullable)->toBeInstanceOf(EnumRule::class);
    });
});

describe('EnumRule — Boundary Values', function (): void {
    it('accepts empty string for string-backed enum if valid', function (): void {
        // If '' is not a valid case, it should fail
        $rule = EnumRule::for(RuleStringStatus::class);
        $failCalled = false;

        $rule->validate('status', '', function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('accepts zero for int-backed enum if valid', function (): void {
        $rule = EnumRule::for(RuleIntPriority::class);
        $failCalled = false;

        // 1 is the lowest valid int, 0 is invalid
        $rule->validate('priority', 0, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('rejects negative int for int-backed enum', function (): void {
        $rule = EnumRule::for(RuleIntPriority::class);
        $failCalled = false;

        $rule->validate('priority', -1, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });
});
