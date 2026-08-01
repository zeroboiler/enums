<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    it('delegates forSelect to enum trait method', function (): void {
        $manager = new EnumManager;

        $result = $manager->forSelect(UserStatus::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        expect($result[0])->toHaveKey('value');
        expect($result[0])->toHaveKey('label');
    });

    it('delegates forApi to enum trait method', function (): void {
        $manager = new EnumManager;

        $result = $manager->forApi(UserStatus::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        expect($result[0])->toHaveKey('value');
        expect($result[0])->toHaveKey('label');
    });

    it('delegates tryFromLabel to enum trait method', function (): void {
        $manager = new EnumManager;

        // UserStatus::ACTIVE has #[Label('Active User')]
        $result = $manager->tryFromLabel(UserStatus::class, 'Active User');

        expect($result)->toBeInstanceOf(BackedEnum::class);
        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel returns null for unknown label', function (): void {
        $manager = new EnumManager;

        $result = $manager->tryFromLabel(UserStatus::class, 'Nonexistent');

        expect($result)->toBeNull();
    });

    it('throws BadMethodCallException for forSelect on non-trait enum', function (): void {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect('stdClass'))
            ->toThrow(BadMethodCallException::class);
    });

    it('throws BadMethodCallException for forApi on non-trait enum', function (): void {
        $manager = new EnumManager;

        expect(fn () => $manager->forApi('stdClass'))
            ->toThrow(BadMethodCallException::class);
    });

    it('throws BadMethodCallException for tryFromLabel on non-trait enum', function (): void {
        $manager = new EnumManager;

        expect(fn () => $manager->tryFromLabel('stdClass', 'x'))
            ->toThrow(BadMethodCallException::class);
    });

    it('works with int-backed enums via forSelect', function (): void {
        $manager = new EnumManager;

        $result = $manager->forSelect(Priority::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        expect($result[0]['value'])->toBeInt();
    });

    it('works with int-backed enums via forApi', function (): void {
        $manager = new EnumManager;

        $result = $manager->forApi(Priority::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
    });
});
