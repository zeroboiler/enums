<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager null guard and edge cases', function () {
    beforeEach(function () {
        $this->manager = new EnumManager;
    });

    it('throws BadMethodCallException for a non-enum class', function () {
        expect(fn () => $this->manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException for an enum without HasEnumMetadata trait', function () {
        // Plain PHP enum without trait
        expect(fn () => $this->manager->forSelect(\PureNonMetadataEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException when calling tryFromLabel on non-metadata enum', function () {
        expect(fn () => $this->manager->tryFromLabel(\PureNonMetadataEnum::class, 'Value One'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException when calling tryFromName on non-metadata enum', function () {
        expect(fn () => $this->manager->tryFromName(\PureNonMetadataEnum::class, 'ONE'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException when calling fromName on non-metadata enum', function () {
        expect(fn () => $this->manager->fromName(\PureNonMetadataEnum::class, 'ONE'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException when calling hasCase on non-metadata enum', function () {
        expect(fn () => $this->manager->hasCase(\PureNonMetadataEnum::class, 'ONE'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException when calling values on non-metadata enum', function () {
        expect(fn () => $this->manager->values(\PureNonMetadataEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException when calling labels on non-metadata enum', function () {
        expect(fn () => $this->manager->labels(\PureNonMetadataEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException when calling forApi on non-metadata enum', function () {
        expect(fn () => $this->manager->forApi(\PureNonMetadataEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('returns null for tryFromLabel with non-existent label', function () {
        expect($this->manager->tryFromLabel(UserStatus::class, 'nonexistent-label-xyz'))
            ->toBeNull();
    });

    it('returns null for tryFromName with non-existent case name', function () {
        expect($this->manager->tryFromName(UserStatus::class, 'NON_EXISTENT'))
            ->toBeNull();
    });

    it('returns false for hasCase with non-existent name', function () {
        expect($this->manager->hasCase(UserStatus::class, 'NON_EXISTENT'))
            ->toBeFalse();
    });

    it('returns true for hasCase with existing name', function () {
        expect($this->manager->hasCase(UserStatus::class, 'ACTIVE'))
            ->toBeTrue();
    });

    it('throws InvalidEnumException from fromName with non-existent name', function () {
        expect(fn () => $this->manager->fromName(UserStatus::class, 'NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('correctly resolves forSelect with int-backed enum', function () {
        $result = $this->manager->forSelect(IntBackedPriority::class);

        expect($result)->toBeArray();
        expect($result)->toHaveCount(4);
        expect($result[0])->toHaveKeys(['value', 'label']);
        expect($result[0]['value'])->toBeInt();
    });

    it('correctly resolves forApi with int-backed enum including all metadata keys', function () {
        $result = $this->manager->forApi(IntBackedPriority::class);

        expect($result)->toBeArray();
        expect($result)->toHaveCount(4);
        expect($result[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('works correctly with single-case enum', function () {
        expect($this->manager->forSelect(SingleCaseEnum::class))->toHaveCount(1);
        expect($this->manager->values(SingleCaseEnum::class))->toHaveCount(1);
        expect($this->manager->labels(SingleCaseEnum::class))->toHaveCount(1);
        expect($this->manager->hasCase(SingleCaseEnum::class, 'TOGGLE'))->toBeTrue();
    });

    it('returns correct values for string-backed enum', function () {
        $values = $this->manager->values(UserStatus::class);

        expect($values)->toBe([
            'active',
            'inactive',
            'pending',
            'suspended',
            'banned',
        ]);
    });

    it('returns correct labels with class-level and per-case overrides', function () {
        $labels = $this->manager->labels(UserStatus::class);

        expect($labels)->toBeArray();
        expect($labels)->toHaveCount(5);
        // ACTIVE has a per-case #[Label('Active User')]
        expect($labels[0])->toBe('Active User');
        // INACTIVE auto-generates from case name
        expect($labels[1])->toBe('Inactive');
    });

    it('tryFromLabel is case-insensitive', function () {
        $case = $this->manager->tryFromLabel(UserStatus::class, 'active user');
        expect($case)->not->toBeNull();
        expect($case->name)->toBe('ACTIVE');
    });

    it('fromName returns the correct case for valid names', function () {
        $case = $this->manager->fromName(UserStatus::class, 'BANNED');
        expect($case->name)->toBe('BANNED');
    });

    it('forApi returns consistent structure across all cases', function () {
        $api = $this->manager->forApi(UserStatus::class);

        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['value'])->toBeString();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString();
            expect($item['color'])->toBeString();
        }
    });
});

/**
 * Plain enum without HasEnumMetadata trait — for testing manager guards.
 */
enum PureNonMetadataEnum: string
{
    case ONE = 'one';
    case TWO = 'two';
}
