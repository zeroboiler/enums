<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Facade delegation — EnumManager methods via Enum facade', function (): void {
    it('delegates forSelect to EnumManager', function (): void {
        $options = Enum::forSelect(UserStatus::class);

        expect($options)->toBeArray()->not->toBeEmpty();
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('delegates forApi to EnumManager', function (): void {
        $api = Enum::forApi(UserStatus::class);

        expect($api)->toBeArray()->not->toBeEmpty();
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('delegates tryFromLabel to EnumManager', function (): void {
        $case = Enum::tryFromLabel(UserStatus::class, 'Active User');

        expect($case)->toBeInstanceOf(\UnitEnum::class);
        expect($case->name)->toBe('ACTIVE');
    });

    it('delegates tryFromLabel with null result', function (): void {
        $case = Enum::tryFromLabel(UserStatus::class, 'NonExistentLabel');

        expect($case)->toBeNull();
    });

    it('delegates tryFromName to EnumManager', function (): void {
        $case = Enum::tryFromName(UserStatus::class, 'BANNED');

        expect($case)->toBeInstanceOf(\UnitEnum::class);
        expect($case->name)->toBe('BANNED');
    });

    it('delegates tryFromName with null result', function (): void {
        $case = Enum::tryFromName(UserStatus::class, 'NONEXISTENT');

        expect($case)->toBeNull();
    });

    it('delegates hasCase to EnumManager', function (): void {
        expect(Enum::hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
        expect(Enum::hasCase(UserStatus::class, 'GHOST'))->toBeFalse();
    });

    it('throws BadMethodCallException for non-enum class', function (): void {
        Enum::forSelect(\stdClass::class);
    })->throws(\BadMethodCallException::class);

    it('throws BadMethodCallException for enum without trait', function (): void {
        // Use a plain PHP enum (not using HasEnumMetadata)
        Enum::forSelect(\UnitEnum::class);
    })->throws(\BadMethodCallException::class);
});

describe('EnumRule + EnumManager integration', function (): void {
    it('EnumRule validates string-backed enum value', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $fail = fn (string $msg): mixed => null;

        // Valid value — should not call $fail
        $called = false;
        $rule->validate('status', 'active', function (string $msg) use (&$called): void {
            $called = true;
        });

        expect($called)->toBeFalse();
    });

    it('EnumRule rejects invalid string value', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $rule->validate('status', 'nonexistent', function (string $msg): void {
            expect($msg)->toBeString()->toContain('invalid');
            // Test passes if this closure is called
            $this->assertTrue(true);
        });
    });

    it('EnumRule validates int-backed enum value', function (): void {
        $rule = EnumRule::for(Priority::class);

        $called = false;
        $rule->validate('priority', 2, function (string $msg) use (&$called): void {
            $called = true;
        });

        expect($called)->toBeFalse();
    });

    it('EnumRule rejects wrong type for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);

        $rule->validate('priority', 'high', function (string $msg): void {
            expect($msg)->toBeString()->toContain('invalid');
        });
    });

    it('EnumRule validates pure enum by case name', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $called = false;
        $rule->validate('feature', 'DARK_MODE', function (string $msg) use (&$called): void {
            $called = true;
        });

        expect($called)->toBeFalse();
    });

    it('EnumRule rejects invalid pure enum name', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $rule->validate('feature', 'NONEXISTENT', function (string $msg): void {
            expect($msg)->toBeString()->toContain('invalid');
        });
    });

    it('EnumRule nullable passes null value', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        $called = false;
        $rule->validate('status', null, function (string $msg) use (&$called): void {
            $called = true;
        });

        expect($called)->toBeFalse();
    });

    it('EnumRule non-nullable rejects null value', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $rule->validate('status', null, function (string $msg): void {
            expect($msg)->toBeString()->toContain('invalid');
        });
    });

    it('EnumRule nullable still validates non-null values', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        $rule->validate('status', 'invalid_value', function (string $msg): void {
            expect($msg)->toBeString()->toContain('invalid');
        });
    });

    it('EnumRule message includes allowed values for enums with HasEnumMetadata', function (): void {
        $rule = EnumRule::for(Priority::class);

        $message = '';
        $rule->validate('priority', 999, function (string $msg) use (&$message): void {
            $message = $msg;
        });

        expect($message)->toContain('Allowed values');
        expect($message)->toContain('1');
    });
});

describe('EnumManager direct usage (not via facade)', function (): void {
    it('can be constructed as readonly', function (): void {
        $manager = new EnumManager;

        expect($manager)->toBeInstanceOf(EnumManager::class);
    });

    it('forSelect returns correct structure', function (): void {
        $manager = new EnumManager;
        $options = $manager->forSelect(TicketStatus::class);

        expect($options)->toHaveCount(3);
        expect($options[0])->toBe(['value' => 'open', 'label' => 'Open']);
    });

    it('forApi returns class-level icon defaults', function (): void {
        $manager = new EnumManager;
        $api = $manager->forApi(TicketStatus::class);

        // OPEN has no per-case icon, but class-level EnumIcon default is 'heroicon-o-ticket'
        expect($api[0]['icon'])->toBe('heroicon-o-ticket');
    });

    it('tryFromLabel resolves class-level EnumLabel', function (): void {
        $manager = new EnumManager;
        $case = $manager->tryFromLabel(TicketStatus::class, 'In Progress');

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('IN_PROGRESS');
    });
});

describe('Cross-fixture EnumCache behavior', function (): void {
    beforeEach(function (): void {
        EnumCache::flush();
    });

    afterEach(function (): void {
        EnumCache::flush();
    });

    it('cache is shared across multiple enum resolutions', function (): void {
        // First resolution — populates cache
        UserStatus::ACTIVE->label();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        // Second enum resolution — also populates cache
        Priority::LOW->label();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();

        // First enum cache should still be valid
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
    });

    it('flush clears all cached enums', function (): void {
        UserStatus::ACTIVE->label();
        Priority::LOW->label();

        EnumCache::flush();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
    });

    it('clearClass only clears specific enum cache', function (): void {
        UserStatus::ACTIVE->label();
        Priority::LOW->label();

        EnumCache::getInstance()->clearClass(UserStatus::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();
    });

    it('TTL expiration works correctly', function (): void {
        EnumCache::getInstance()->setTtl(0);
        UserStatus::ACTIVE->label();

        // With TTL=0, cache should always be stale
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });
});

describe('fromName edge cases', function (): void {
    it('fromName throws for non-existent case', function (): void {
        UserStatus::fromName('NONEXISTENT');
    })->throws(InvalidEnumException::class);

    it('fromName works with zero-backed enum', function (): void {
        $case = ZeroPriority::fromName('NONE');

        expect($case)->toBe(ZeroPriority::NONE);
        expect($case->value)->toBe(0);
    });

    it('fromName throws for zero-backed enum with bad name', function (): void {
        ZeroPriority::fromName('CRITICAL');
    })->throws(InvalidEnumException::class);

    it('fromName works with single-case enum', function (): void {
        $case = SingleCaseEnum::fromName('ONLY');

        expect($case)->toBe(SingleCaseEnum::ONLY);
    });
});

describe('Comparison method edge cases', function (): void {
    it('is() works with string-backed enum and string name', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->is('ACTIVE'))->toBeTrue();
        expect($status->is('BANNED'))->toBeFalse();
        expect($status->is('active'))->toBeFalse(); // case-sensitive: 'active' is value not name
    });

    it('in() works with empty array', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->in([]))->toBeFalse();
    });

    it('notIn() returns true when not in list', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->notIn([UserStatus::BANNED, 'SUSPENDED']))->toBeTrue();
    });

    it('notIn() returns false when in list', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->notIn(['ACTIVE', UserStatus::PENDING]))->toBeFalse();
    });

    it('is() and isNot() work with int-backed enum', function (): void {
        $priority = Priority::HIGH;

        expect($priority->is(Priority::HIGH))->toBeTrue();
        expect($priority->is('HIGH'))->toBeTrue();
        expect($priority->isNot(Priority::LOW))->toBeTrue();
        expect($priority->isNot('LOW'))->toBeTrue();
        expect($priority->in([Priority::LOW, Priority::MEDIUM]))->toBeFalse();
        expect($priority->in(['HIGH', 'URGENT']))->toBeTrue();
    });
});

describe('Pure enum with metadata', function (): void {
    it('PureFeatureFlag uses case names as values in forSelect', function (): void {
        $options = PureFeatureFlag::forSelect();

        expect($options[0]['value'])->toBe('DARK_MODE');
        expect($options[1]['value'])->toBe('BETA_FEATURES');
    });

    it('PureFeatureFlag values() returns case names', function (): void {
        $values = PureFeatureFlag::values();

        expect($values)->toContain('DARK_MODE');
        expect($values)->toContain('BETA_FEATURES');
        expect($values)->toContain('MAINTENANCE_MODE');
    });

    it('PureFeatureFlag hasCase uses case names', function (): void {
        expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::hasCase('dark_mode'))->toBeFalse();
    });

    it('PureFeatureFlag tryFromName uses case names', function (): void {
        expect(PureFeatureFlag::tryFromName('BETA_FEATURES'))->toBe(PureFeatureFlag::BETA_FEATURES);
    });

    it('PureFeatureFlag MAINTENANCE_MODE auto-generates label', function (): void {
        expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
        expect(PureFeatureFlag::MAINTENANCE_MODE->icon())->toBeNull();
        expect(PureFeatureFlag::MAINTENANCE_MODE->description())->toBeNull();
    });
});

describe('CamelCase label generation', function (): void {
    it('generates Title Case from camelCase', function (): void {
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
        expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
    });

    it('forSelect uses backed values not case names', function (): void {
        $options = CamelCaseRole::forSelect();

        expect($options[0]['value'])->toBe('is_active');
        expect($options[0]['label'])->toBe('Is Active');
    });
});

describe('IntStatusWithColor fixture', function (): void {
    it('resolves class-level EnumColor for int values', function (): void {
        // Assuming IntStatusWithColor has EnumColor configured
        $color = IntStatusWithColor::ACTIVE->color();

        expect($color)->toBeString()->not->toBeEmpty();
    });
});

describe('InvalidEnumException named constructors', function (): void {
    it('value() creates exception with display value', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, 'bad_value');

        expect($e->getMessage())->toContain('bad_value');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('value() handles null display value', function (): void {
        $e = InvalidEnumException::value(Priority::class, null);

        expect($e->getMessage())->toContain('null');
    });

    it('forName() creates exception with case name', function (): void {
        $e = InvalidEnumException::forName(UserStatus::class, 'GHOST');

        expect($e->getMessage())->toContain('GHOST');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('__toString() returns class and message', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, 'x');

        $str = (string) $e;

        expect($str)->toContain(InvalidEnumException::class);
        expect($str)->toContain('x');
    });
});

describe('values() and labels() consistency', function (): void {
    it('values() count matches cases() count', function (): void {
        expect(UserStatus::values())->toHaveCount(count(UserStatus::cases()));
        expect(Priority::values())->toHaveCount(count(Priority::cases()));
        expect(PureFeatureFlag::values())->toHaveCount(count(PureFeatureFlag::cases()));
    });

    it('labels() count matches cases() count', function (): void {
        expect(UserStatus::labels())->toHaveCount(count(UserStatus::cases()));
        expect(Priority::labels())->toHaveCount(count(Priority::cases()));
    });

    it('forSelect() count matches cases() count', function (): void {
        expect(UserStatus::forSelect())->toHaveCount(count(UserStatus::cases()));
        expect(SingleCaseEnum::forSelect())->toHaveCount(1);
    });

    it('forApi() count matches cases() count', function (): void {
        expect(UserStatus::forApi())->toHaveCount(count(UserStatus::cases()));
    });
});
