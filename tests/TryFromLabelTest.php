<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ShippingService;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('tryFromLabel() exact match', function (): void {
    it('matches label exactly (case-sensitive)', function (): void {
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
        expect(OrderStatus::tryFromLabel('Pending'))->toBe(OrderStatus::PENDING);
    });

    it('returns null for unknown label', function (): void {
        expect(UserStatus::tryFromLabel('Unknown'))->toBeNull();
    });
});

describe('tryFromLabel() case-insensitive fallback', function (): void {
    it('falls back to case-insensitive match when no exact match', function (): void {
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('inactive'))->toBe(UserStatus::INACTIVE);
    });

    it('prefers exact match over case-insensitive', function (): void {
        // When exact and case-insensitive both exist, exact wins
        expect(ShippingService::tryFromLabel('DHL Express'))->toBe(ShippingService::DHL);
        expect(ShippingService::tryFromLabel('FedEx'))->toBe(ShippingService::FEDEX);
    });
});

describe('tryFromLabel() ambiguous detection', function (): void {
    it('throws InvalidEnumException when case-insensitive match is ambiguous', function (): void {
        try {
            ShippingService::tryFromLabel('dhl express');
            expect(false)->toBeTrue('Expected InvalidEnumException to be thrown');
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('ambiguously')
                ->and($e->getMessage())->toContain('DHL Express')
                ->and($e->getMessage())->toContain('DHL EXPRESS');
        }
    });

    it('does not throw for exact match even when ambiguous case-insensitive exists', function (): void {
        // Exact match should never throw
        expect(ShippingService::tryFromLabel('DHL EXPRESS'))->toBe(ShippingService::DHL_PREMIUM);
    });
});
