<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager', function (): void {
    beforeEach(function (): void {
        $this->manager = new EnumManager;
    });

    describe('forSelect()', function (): void {
        it('delegates to enum forSelect', function (): void {
            $options = $this->manager->forSelect(UserStatus::class);

            expect($options)->toBeArray();
            expect($options)->toHaveCount(5);
            expect($options[0])->toHaveKeys(['value', 'label']);
            expect($options[0]['value'])->toBe('active');
            expect($options[0]['label'])->toBe('Active User');
        });

        it('works with int-backed enums', function (): void {
            $options = $this->manager->forSelect(Priority::class);

            expect($options[0]['value'])->toBe(1);
            expect($options[3]['value'])->toBe(4);
        });

        it('throws BadMethodCallException for enum without trait', function (): void {
            expect(fn () => $this->manager->forSelect('NonExistentClass'))
                ->toThrow(BadMethodCallException::class);
        });
    });

    describe('forApi()', function (): void {
        it('delegates to enum forApi with full metadata', function (): void {
            $api = $this->manager->forApi(UserStatus::class);

            expect($api)->toHaveCount(5);
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });
    });

    describe('tryFromLabel()', function (): void {
        it('finds enum case by exact label', function (): void {
            $result = $this->manager->tryFromLabel(UserStatus::class, 'Active User');

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('finds enum case by auto-generated label', function (): void {
            $result = $this->manager->tryFromLabel(OrderStatus::class, 'Pending');

            expect($result)->toBe(OrderStatus::PENDING);
        });

        it('finds enum case with case-insensitive match', function (): void {
            $result = $this->manager->tryFromLabel(UserStatus::class, 'ACTIVE USER');

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('returns null for unknown label', function (): void {
            $result = $this->manager->tryFromLabel(UserStatus::class, 'Unknown Status');

            expect($result)->toBeNull();
        });

        it('returns null for empty string', function (): void {
            $result = $this->manager->tryFromLabel(UserStatus::class, '');

            expect($result)->toBeNull();
        });

        it('works with int-backed enums', function (): void {
            $result = $this->manager->tryFromLabel(Priority::class, 'Low');

            expect($result)->toBe(Priority::LOW);
        });

        it('returns a BackedEnum instance', function (): void {
            $result = $this->manager->tryFromLabel(UserStatus::class, 'Active User');

            expect($result)->toBeInstanceOf(BackedEnum::class);
        });

        it('throws BadMethodCallException for enum without trait', function (): void {
            expect(fn () => $this->manager->tryFromLabel('NonExistentClass', 'test'))
                ->toThrow(BadMethodCallException::class);
        });
    });
});
