<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule nullable chain method', function (): void {
    it('creates a non-nullable rule by default', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        // Non-nullable: null values should fail
        $failed = false;
        $rule->validate('status', null, function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('creates a nullable rule via nullable() method', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        // Nullable: null values should pass
        $failed = false;
        $rule->validate('status', null, function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('nullable rule still validates non-null values', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        $failed = false;
        $rule->validate('status', 'invalid_value', function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('nullable rule accepts valid non-null values', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        $failed = false;
        $rule->validate('status', 'active', function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });
});

describe('EnumRule with int-backed enums', function (): void {
    it('rejects string value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);

        $failed = false;
        $rule->validate('priority', 'high', function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('accepts valid int value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);

        $failed = false;
        $rule->validate('priority', 1, function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects invalid int value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);

        $failed = false;
        $rule->validate('priority', 999, function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('nullable int-backed enum accepts null', function (): void {
        $rule = EnumRule::for(Priority::class)->nullable();

        $failed = false;
        $rule->validate('priority', null, function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });
});

describe('EnumRule with pure enums', function (): void {
    it('accepts valid case name for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $failed = false;
        $rule->validate('flag', 'TWO_FACTOR_AUTH', function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects invalid case name for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $failed = false;
        $rule->validate('flag', 'NONEXISTENT_FLAG', function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('rejects non-string value for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $failed = false;
        $rule->validate('flag', 42, function (string $message) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('EnumRule message generation', function (): void {
    it('includes allowed values in error message when enum uses HasEnumMetadata', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $message = null;

        $rule->validate('status', 'nonexistent', function (string $msg) use (&$message): void {
            $message = $msg;
        });

        expect($message)->not->toBeNull();
        expect($message)->toContain('Allowed values');
        expect($message)->toContain('active');
    });

    it('uses generic message when enum does not use HasEnumMetadata', function (): void {
        // Use a non-HasEnumMetadata enum
        $rule = new EnumRule(\SomeStandardEnum::class);

        $message = null;
        $rule->validate('color', 'invalid', function (string $msg) use (&$message): void {
            $message = $msg;
        });

        expect($message)->not->toBeNull();
        expect($message)->toContain('invalid');
    });
});

describe('EnumRule readonly property', function (): void {
    it('is a readonly class', function (): void {
        $reflection = new ReflectionClass(EnumRule::class);

        expect($reflection->isReadOnly())->toBeTrue();
    });
});

// Standalone enum without HasEnumMetadata for testing generic error messages
enum SomeStandardEnum: string
{
    case RED = 'red';
    case GREEN = 'green';
    case BLUE = 'blue';
}
