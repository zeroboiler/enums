<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('EnumRule with string-backed enums', function (): void {
    it('validates correct string value', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $fail = false;

        $rule->validate('status', 'active', function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeFalse();
    });

    it('rejects invalid string value', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $fail = false;

        $rule->validate('status', 'nonexistent', function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeTrue();
    });

    it('rejects int value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $fail = false;

        $rule->validate('status', 42, function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeTrue();
    });

    it('allows null when nullable is enabled', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $fail = false;

        $rule->validate('status', null, function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeFalse();
    });

    it('rejects null when nullable is disabled', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $fail = false;

        $rule->validate('status', null, function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeTrue();
    });
});

describe('EnumRule with int-backed enums', function (): void {
    it('validates correct int value', function (): void {
        $rule = EnumRule::for(Priority::class);
        $fail = false;

        $rule->validate('priority', 1, function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeFalse();
    });

    it('rejects string value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);
        $fail = false;

        $rule->validate('priority', 'HIGH', function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeTrue();
    });

    it('rejects out-of-range int value', function (): void {
        $rule = EnumRule::for(Priority::class);
        $fail = false;

        $rule->validate('priority', 99, function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeTrue();
    });

    it('validates zero value for int-backed enum', function (): void {
        $rule = EnumRule::for(ZeroPriority::class);
        $fail = false;

        $rule->validate('priority', 0, function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeFalse();
    });
});

describe('EnumRule with pure enums', function (): void {
    it('validates correct case name string', function (): void {
        $rule = EnumRule::for(RequestState::class);
        $fail = false;

        $rule->validate('state', 'DRAFT', function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeFalse();
    });

    it('rejects non-existent case name', function (): void {
        $rule = EnumRule::for(RequestState::class);
        $fail = false;

        $rule->validate('state', 'DESTROYED', function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeTrue();
    });

    it('rejects int value for pure enum', function (): void {
        $rule = EnumRule::for(RequestState::class);
        $fail = false;

        $rule->validate('state', 1, function (string $message) use (&$fail): void {
            $fail = true;
        });

        expect($fail)->toBeTrue();
    });

    it('validates all pure enum case names', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $cases = PureFeatureFlag::cases();

        foreach ($cases as $case) {
            $fail = false;
            $rule->validate('feature', $case->name, function (string $message) use (&$fail): void {
                $fail = true;
            });
            expect($fail)->toBeFalse("Case {$case->name} should be valid");
        }
    });
});

describe('EnumCast with string-backed enums', function (): void {
    it('gets enum instance from stored value', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->get(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'status',
            'active',
            [],
        );

        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('returns null for null stored value', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->get(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'status',
            null,
            [],
        );

        expect($result)->toBeNull();
    });

    it('returns null for non-matching stored value', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->get(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'status',
            'nonexistent',
            [],
        );

        expect($result)->toBeNull();
    });

    it('sets enum value to backed value', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->set(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'status',
            UserStatus::ACTIVE,
            [],
        );

        expect($result)->toBe('active');
    });

    it('sets raw string value when valid', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->set(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'status',
            'banned',
            [],
        );

        expect($result)->toBe('banned');
    });

    it('sets null for null value', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->set(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'status',
            null,
            [],
        );

        expect($result)->toBeNull();
    });

    it('serializes enum instance to backed value', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'status',
            UserStatus::BANNED,
            [],
        );

        expect($result)->toBe('banned');
    });

    it('serializes raw string value as-is', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'status',
            'active',
            [],
        );

        expect($result)->toBe('active');
    });

    it('serializes null as null', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'status',
            null,
            [],
        );

        expect($result)->toBeNull();
    });
});

describe('EnumCast with int-backed enums', function (): void {
    it('gets int-backed enum from stored value', function (): void {
        $cast = new EnumCast(Priority::class);
        $result = $cast->get(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'priority',
            3,
            [],
        );

        expect($result)->toBe(Priority::HIGH);
    });

    it('sets int-backed enum to int value', function (): void {
        $cast = new EnumCast(Priority::class);
        $result = $cast->set(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'priority',
            Priority::URGENT,
            [],
        );

        expect($result)->toBe(4);
    });

    it('rejects wrong enum type on set', function (): void {
        $cast = new EnumCast(Priority::class);

        expect(fn () => $cast->set(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'priority',
            UserStatus::ACTIVE,
            [],
        ))->toThrow(\InvalidArgumentException::class);
    });

    it('serializes int-backed enum to int', function (): void {
        $cast = new EnumCast(Priority::class);
        $result = $cast->serialize(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'priority',
            Priority::LOW,
            [],
        );

        expect($result)->toBe(1);
    });
});

describe('EnumCast with zero-valued int-backed enum', function (): void {
    it('correctly gets zero value', function (): void {
        $cast = new EnumCast(ZeroPriority::class);
        $result = $cast->get(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'priority',
            0,
            [],
        );

        expect($result)->toBe(ZeroPriority::NONE);
    });

    it('correctly sets zero value', function (): void {
        $cast = new EnumCast(ZeroPriority::class);
        $result = $cast->set(
            new class {
                public function __get(string $key): mixed { return null; }
            },
            'priority',
            ZeroPriority::NONE,
            [],
        );

        expect($result)->toBe(0);
    });
});

describe('fromName and tryFromName edge cases', function (): void {
    it('fromName throws InvalidEnumException for non-existent case', function (): void {
        expect(fn () => UserStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('tryFromName returns null for non-existent case', function (): void {
        expect(UserStatus::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('fromName works with int-backed enum', function (): void {
        expect(Priority::fromName('HIGH')->value)->toBe(3);
    });

    it('tryFromName returns null for empty string', function (): void {
        expect(UserStatus::tryFromName(''))->toBeNull();
    });

    it('hasCase returns correct boolean for all cases', function (): void {
        foreach (UserStatus::cases() as $case) {
            expect(UserStatus::hasCase($case->name))->toBeTrue();
        }
        expect(UserStatus::hasCase('GHOST_CASE'))->toBeFalse();
    });
});

describe('SingleCaseEnum edge case', function (): void {
    it('has exactly one case', function (): void {
        expect(SingleCaseEnum::cases())->toHaveCount(1);
    });

    it('forSelect returns single option', function (): void {
        $options = SingleCaseEnum::forSelect();

        expect($options)->toHaveCount(1);
        expect($options[0])->toBe(['value' => 'only', 'label' => 'Only']);
    });

    it('forApi returns single item with full metadata', function (): void {
        $api = SingleCaseEnum::forApi();

        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('values returns single value', function (): void {
        expect(SingleCaseEnum::values())->toBe(['only']);
    });

    it('in() works with single-element array', function (): void {
        expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
        expect(SingleCaseEnum::ONLY->in(['ONLY']))->toBeTrue();
    });
});

describe('CamelCaseRole label generation', function (): void {
    it('generates labels from camelCase case names', function (): void {
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
        expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
    });

    it('uses backed values in forSelect (not case names)', function (): void {
        $options = CamelCaseRole::forSelect();

        expect($options[0]['value'])->toBe('is_active');
        expect($options[0]['label'])->toBe('Is Active');
    });

    it('tryFromLabel works with auto-generated labels', function (): void {
        expect(CamelCaseRole::tryFromLabel('Is Admin'))->toBe(CamelCaseRole::isAdmin);
        expect(CamelCaseRole::tryFromLabel('is admin'))->toBe(CamelCaseRole::isAdmin); // case-insensitive
    });
});

describe('IntStatusWithColor — class-level EnumColor with int values', function (): void {
    it('resolves class-level color by int value', function (): void {
        expect(IntStatusWithColor::ACTIVE->color())->toBe('success');
        expect(IntStatusWithColor::PENDING->color())->toBe('warning');
    });

    it('per-case Color override takes precedence', function (): void {
        expect(IntStatusWithColor::BANNED->color())->toBe('danger');
    });

    it('forSelect uses int values', function (): void {
        $options = IntStatusWithColor::forSelect();

        expect($options[0]['value'])->toBe(1);
        expect($options[0])->toHaveKey('label');
    });

    it('forApi returns int values', function (): void {
        $api = IntStatusWithColor::forApi();

        expect($api[0]['value'])->toBe(1);
        expect($api[0]['name'])->toBe('ACTIVE');
    });
});

describe('PureFeatureFlag — pure enum metadata', function (): void {
    it('returns case name as value in forSelect', function (): void {
        $options = PureFeatureFlag::forSelect();

        expect($options[0]['value'])->toBe('TWO_FACTOR_AUTH');
    });

    it('returns case name as value in forApi', function (): void {
        $api = PureFeatureFlag::forApi();

        expect($api[0]['value'])->toBe('TWO_FACTOR_AUTH');
        expect($api[0]['name'])->toBe('TWO_FACTOR_AUTH');
    });

    it('values() returns case names', function (): void {
        $values = PureFeatureFlag::values();

        expect($values)->toBe(['TWO_FACTOR_AUTH', 'DARK_MODE', 'BETA_ACCESS']);
    });

    it('resolves per-case icon', function (): void {
        expect(PureFeatureFlag::TWO_FACTOR_AUTH->icon())->toBe('heroicon-o-shield-check');
        expect(PureFeatureFlag::DARK_MODE->icon())->toBeNull();
    });

    it('defaults color to secondary', function (): void {
        expect(PureFeatureFlag::DARK_MODE->color())->toBe('secondary');
    });

    it('tryFromLabel works with auto-generated labels', function (): void {
        expect(PureFeatureFlag::tryFromLabel('Two Factor Auth'))->toBe(PureFeatureFlag::TWO_FACTOR_AUTH);
        expect(PureFeatureFlag::tryFromLabel('Dark Mode'))->toBe(PureFeatureFlag::DARK_MODE);
    });

    it('is() comparison works with case names', function (): void {
        expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->is('LIGHT_MODE'))->toBeFalse();
    });
});
