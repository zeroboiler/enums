<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager readonly class contract', function () {
    it('is a final readonly class', function () {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);

        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('forSelect() delegates to the enum class static method', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $result = $manager->forSelect(UserStatus::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        expect($result[0])->toHaveKeys(['value', 'label']);
    });

    it('forApi() delegates and returns full metadata', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $result = $manager->forApi(UserStatus::class);

        expect($result)->toBeArray();
        expect($result[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('tryFromLabel() delegates label-based lookup', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $label = UserStatus::ACTIVE->label();
        $case = $manager->tryFromLabel(UserStatus::class, $label);

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('ACTIVE');
    });

    it('tryFromName() delegates name-based lookup', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $case = $manager->tryFromName(UserStatus::class, 'ACTIVE');

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('ACTIVE');
    });

    it('tryFromName() returns null for non-existent case', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $case = $manager->tryFromName(UserStatus::class, 'NON_EXISTENT');

        expect($case)->toBeNull();
    });

    it('hasCase() delegates existence check', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
        expect($manager->hasCase(UserStatus::class, 'NON_EXISTENT'))->toBeFalse();
    });

    it('throws BadMethodCallException for enum without trait', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $manager->forSelect(\stdClass::class);
    })->throws(\BadMethodCallException::class);

    it('tryFromLabel returns null for non-existent label', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $result = $manager->tryFromLabel(UserStatus::class, 'definitely-not-a-label-xyz');

        expect($result)->toBeNull();
    });

    it('works with int-backed enums', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $result = $manager->forSelect(IntBackedPriority::class);

        expect($result)->toBeArray();
        expect($result[0])->toHaveKeys(['value', 'label']);
    });

    it('works with pure enums', function () {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $result = $manager->forSelect(PureFeatureFlag::class);

        expect($result)->toBeArray();
        expect($result[0])->toHaveKey('value');
    });
});
