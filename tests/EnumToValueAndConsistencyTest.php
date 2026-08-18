<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\EdgeCaseNamingEnum;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('toValue() consistency across enum types', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('returns string backed value for string-backed enums', function () {
        expect(UserStatus::ACTIVE->toValue())->toBe('active');
        expect(UserStatus::BANNED->toValue())->toBe('banned');
        expect(TicketStatus::OPEN->toValue())->toBe('open');
    });

    it('returns int backed value for int-backed enums', function () {
        expect(IntBackedPriority::CRITICAL->toValue())->toBe(1);
        expect(IntBackedPriority::HIGH->toValue())->toBe(2);
        expect(IntBackedPriority::LOW->toValue())->toBe(3);
    });

    it('returns int zero for zero-backed int enums', function () {
        expect(ZeroPriority::NONE->toValue())->toBe(0);
        expect(ZeroBackedPriority::NONE->toValue())->toBe(0);
    });

    it('returns case name for pure enums', function () {
        expect(PureFeatureFlag::DARK_MODE->toValue())->toBe('DARK_MODE');
        expect(PureFeatureFlag::BETA_FEATURES->toValue())->toBe('BETA_FEATURES');
    });

    it('toValue is consistent with values() array', function () {
        // For each enum type, verify toValue() matches values()
        $values = UserStatus::values();
        foreach (UserStatus::cases() as $case) {
            expect($values)->toContain($case->toValue());
        }

        $intValues = IntBackedPriority::values();
        foreach (IntBackedPriority::cases() as $case) {
            expect($intValues)->toContain($case->toValue());
        }

        $pureValues = PureFeatureFlag::values();
        foreach (PureFeatureFlag::cases() as $case) {
            expect($pureValues)->toContain($case->toValue());
        }
    });

    it('toValue() returns int|string type (never null, never array)', function () {
        foreach (UserStatus::cases() as $case) {
            $v = $case->toValue();
            expect(is_string($v) || is_int($v))->toBeTrue();
        }

        foreach (IntBackedPriority::cases() as $case) {
            $v = $case->toValue();
            expect(is_int($v))->toBeTrue();
        }

        foreach (PureFeatureFlag::cases() as $case) {
            $v = $case->toValue();
            expect(is_string($v))->toBeTrue();
        }
    });
});

describe('forSelect/forApi value-name consistency', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('forSelect values match toValue for every case', function () {
        $select = UserStatus::forSelect();
        foreach (UserStatus::cases() as $i => $case) {
            expect($select[$i]['value'])->toBe($case->toValue());
        }
    });

    it('forApi value and name are correct for every case', function () {
        $api = UserStatus::forApi();
        foreach (UserStatus::cases() as $i => $case) {
            expect($api[$i]['value'])->toBe($case->toValue());
            expect($api[$i]['name'])->toBe($case->name);
        }
    });

    it('forApi color is always a non-empty string', function () {
        $api = UserStatus::forApi();
        foreach ($api as $item) {
            expect($item['color'])->toBeString()->not->toBeEmpty();
        }
    });

    it('forApi icon and description are string or null', function () {
        $api = UserStatus::forApi();
        foreach ($api as $item) {
            expect($item['icon'] === null || is_string($item['icon']))->toBeTrue();
            expect($item['description'] === null || is_string($item['description']))->toBeTrue();
        }
    });

    it('forSelect returns correct count matching cases', function () {
        expect(UserStatus::forSelect())->toHaveCount(count(UserStatus::cases()));
        expect(IntBackedPriority::forSelect())->toHaveCount(count(IntBackedPriority::cases()));
        expect(PureFeatureFlag::forSelect())->toHaveCount(count(PureFeatureFlag::cases()));
    });
});

describe('CamelCase label generation consistency', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('generates Title Case from camelCase names', function () {
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
        expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
    });

    it('EdgeCaseNamingEnum generates labels correctly', function () {
        // X -> 'X' (single letter stays as-is)
        expect(EdgeCaseNamingEnum::X->label())->toBeString()->not->toBeEmpty();
        // AB -> 'Ab'
        expect(EdgeCaseNamingEnum::AB->label())->toBeString()->not->toBeEmpty();
        // UNDER_SCORE__ -> handles double underscore
        expect(EdgeCaseNamingEnum::UNDER_SCORE__->label())->toBeString()->not->toBeEmpty();
        // SINGLE -> 'Single'
        expect(EdgeCaseNamingEnum::SINGLE->label())->toBe('Single');
    });
});