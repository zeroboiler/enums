<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Tests\Fixtures\PlainEnum;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager', function (): void {
    it('generates forSelect via manager', function (): void {
        $manager = new EnumManager;

        $result = $manager->forSelect(UserStatus::class);

        expect($result)->toBeArray();
        expect($result)->toHaveCount(5);
        expect($result[0])->toHaveKeys(['value', 'label']);
    });

    it('generates forApi via manager', function (): void {
        $manager = new EnumManager;

        $result = $manager->forApi(UserStatus::class);

        expect($result)->toBeArray();
        expect($result)->toHaveCount(5);
        expect($result[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('performs tryFromLabel via manager', function (): void {
        $manager = new EnumManager;

        expect($manager->tryFromLabel(UserStatus::class, 'Active User'))->toBe(UserStatus::ACTIVE);
        expect($manager->tryFromLabel(UserStatus::class, 'NonExistent'))->toBeNull();
    });

    it('works with int-backed enums via manager', function (): void {
        $manager = new EnumManager;

        $result = $manager->forSelect(Priority::class);

        expect($result[0]['value'])->toBe(1);
        expect($result[3]['value'])->toBe(4);
    });

    it('throws BadMethodCallException for non-HasEnumMetadata enum in forSelect', function (): void {
        $manager = new EnumManager;

        // Plain enum without HasEnumMetadata trait
        $plainEnum = PlainEnum::class;

        expect(fn (): mixed => $manager->forSelect($plainEnum))->toThrow(BadMethodCallException::class);
    });

    it('throws BadMethodCallException for non-HasEnumMetadata enum in forApi', function (): void {
        $manager = new EnumManager;

        expect(fn (): mixed => $manager->forApi(PlainEnum::class))->toThrow(BadMethodCallException::class);
    });

    it('throws BadMethodCallException for non-HasEnumMetadata enum in tryFromLabel', function (): void {
        $manager = new EnumManager;

        expect(fn (): mixed => $manager->tryFromLabel(PlainEnum::class, 'test'))->toThrow(BadMethodCallException::class);
    });
});
