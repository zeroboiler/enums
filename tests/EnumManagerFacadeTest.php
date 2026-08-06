<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager', function () {
    it('delegates forSelect to the enum class', function () {
        $manager = new EnumManager;
        $options = $manager->forSelect(UserStatus::class);

        expect($options)->toBeArray()->not->toBeEmpty();
        expect($options[0])->toHaveKeys(['value', 'label']);
        expect($options)->toHaveCount(count(UserStatus::cases()));
    });

    it('delegates forApi to the enum class', function () {
        $manager = new EnumManager;
        $api = $manager->forApi(UserStatus::class);

        expect($api)->toBeArray()->not->toBeEmpty();
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('delegates tryFromLabel to the enum class', function () {
        $manager = new EnumManager;
        $case = $manager->tryFromLabel(UserStatus::class, 'Active User');

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('ACTIVE');
    });

    it('returns null for non-existent label', function () {
        $manager = new EnumManager;
        $result = $manager->tryFromLabel(UserStatus::class, 'Non Existent Label');

        expect($result)->toBeNull();
    });

    it('throws BadMethodCallException for non-metadata enum', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(\StdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException for non-metadata enum on forApi', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forApi(\StdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException for non-metadata enum on tryFromLabel', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->tryFromLabel(\StdClass::class, 'test'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('works with int-backed enums', function () {
        $manager = new EnumManager;
        $options = $manager->forSelect(Priority::class);

        expect($options)->toBeArray()->not->toBeEmpty();
        expect($options[0]['value'])->toBeInt();
    });

    it('tryFromLabel is case-insensitive', function () {
        $manager = new EnumManager;
        $case = $manager->tryFromLabel(UserStatus::class, 'active user');

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('ACTIVE');
    });
});
