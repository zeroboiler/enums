<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule — int-backed type mismatch', function (): void {
    it('rejects string value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);

        $failed = false;
        $rule->validate('priority', 'high', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('accepts int value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);

        $failed = false;
        $rule->validate('priority', 1, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects int value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $failed = false;
        $rule->validate('status', 123, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('accepts valid string value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $failed = false;
        $rule->validate('status', 'active', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });
});

describe('EnumRule — nullable handling', function (): void {
    it('rejects null when not nullable', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $failed = false;
        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('accepts null when nullable', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        $failed = false;
        $rule->validate('status', null, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });
});

describe('EnumRule — pure enum validation', function (): void {
    it('accepts valid case name for pure enum', function (): void {
        $rule = EnumRule::for(RequestState::class);

        $failed = false;
        $rule->validate('state', 'DRAFT', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects invalid case name for pure enum', function (): void {
        $rule = EnumRule::for(RequestState::class);

        $failed = false;
        $rule->validate('state', 'NONEXISTENT', function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('rejects non-string value for pure enum', function (): void {
        $rule = EnumRule::for(RequestState::class);

        $failed = false;
        $rule->validate('state', 123, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('EnumRule — named constructor', function (): void {
    it('creates instance via for() with correct enum class', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        expect($rule)->toBeInstanceOf(EnumRule::class);
    });
});

describe('EnumMetadataResolver — class-level EnumIcon default icon on pure enum', function (): void {
    it('resolves default icon from class-level EnumIcon on pure enum', function (): void {
        expect(PureFeatureFlag::DARK_MODE->icon())->toBeNull();
        expect(PureFeatureFlag::TWO_FACTOR_AUTH->icon())->toBe('heroicon-o-shield-check');
    });

    it('resolves default icon from class-level EnumIcon on string-backed enum', function (): void {
        expect(\ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::OPEN->icon())->toBe('heroicon-o-circle');
        expect(\ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::IN_PROGRESS->icon())->toBe('heroicon-o-circle');
    });
});

describe('EnumMetadataResolver — class-level EnumDescription', function (): void {
    it('resolves descriptions from class-level EnumDescription', function (): void {
        expect(\ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::OPEN->description())->toBe('Task is open');
        expect(\ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::DONE->description())->toBe('Task is complete');
    });

    it('returns null for missing class-level description', function (): void {
        expect(\ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::OPEN->description())->toBeString();
    });
});

describe('HasEnumMetadata — in() with mixed instance and string', function (): void {
    it('matches mixed instances and strings', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->in([UserStatus::ACTIVE, 'BANNED']))->toBeTrue();
        expect($status->in(['INACTIVE', 'PENDING']))->toBeFalse();
    });

    it('handles empty array', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->in([]))->toBeFalse();
    });
});

describe('HasEnumMetadata — edge cases', function (): void {
    it('tryFromLabel is case-insensitive for auto-generated labels', function (): void {
        // 'Inactive' is auto-generated from INACTIVE
        expect(UserStatus::tryFromLabel('inactive'))->toBe(UserStatus::INACTIVE);
        expect(UserStatus::tryFromLabel('INACTIVE'))->toBe(UserStatus::INACTIVE);
        expect(UserStatus::tryFromLabel('InAcTiVe'))->toBe(UserStatus::INACTIVE);
    });

    it('values() returns backed values for string-backed enum', function (): void {
        $values = UserStatus::values();

        expect($values)->toContain('active');
        expect($values)->toContain('banned');
        expect($values)->not->toContain('ACTIVE');
    });

    it('values() returns int values for int-backed enum', function (): void {
        $values = Priority::values();

        expect($values)->toContain(1);
        expect($values)->toContain(4);
        expect($values)->not->toContain('LOW');
    });
});
