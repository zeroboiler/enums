<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;

describe('MixedAttributeStatus enum', function () {
    it('resolves class-level EnumLabel labels correctly', function () {
        expect(MixedAttributeStatus::NEW->label())->toBe('Brand New Item');
        expect(MixedAttributeStatus::USED->label())->toBe('Previously Owned');
    });

    it('auto-generates labels when no class-level or per-case label exists', function () {
        expect(MixedAttributeStatus::ACTIVE->label())->toBe('Active');
        expect(MixedAttributeStatus::PENDING->label())->toBe('Pending');
        expect(MixedAttributeStatus::ARCHIVED->label())->toBe('Archived');
        expect(MixedAttributeStatus::DELETED->label())->toBe('Deleted');
    });

    it('resolves class-level EnumColor colors correctly', function () {
        expect(MixedAttributeStatus::ACTIVE->color())->toBe('success');
        expect(MixedAttributeStatus::NEW->color())->toBe('success');
        expect(MixedAttributeStatus::PENDING->color())->toBe('warning');
        expect(MixedAttributeStatus::USED->color())->toBe('warning');
        expect(MixedAttributeStatus::ARCHIVED->color())->toBe('danger');
        expect(MixedAttributeStatus::DELETED->color())->toBe('secondary');
    });

    it('resolves class-level EnumDescription descriptions correctly', function () {
        expect(MixedAttributeStatus::ACTIVE->description())->toBe('Currently active');
        expect(MixedAttributeStatus::PENDING->description())->toBe('Awaiting review');
    });

    it('returns null description when not defined', function () {
        expect(MixedAttributeStatus::NEW->description())->toBeNull();
        expect(MixedAttributeStatus::USED->description())->toBeNull();
        expect(MixedAttributeStatus::ARCHIVED->description())->toBeNull();
        expect(MixedAttributeStatus::DELETED->description())->toBeNull();
    });

    it('resolves class-level EnumIcon default icon for all cases', function () {
        expect(MixedAttributeStatus::ACTIVE->icon())->toBe('heroicon-o-document');
        expect(MixedAttributeStatus::PENDING->icon())->toBe('heroicon-o-document');
        expect(MixedAttributeStatus::DELETED->icon())->toBe('heroicon-o-document');
    });

    it('forSelect returns correct value-label pairs', function () {
        $options = MixedAttributeStatus::forSelect();

        expect($options)->toHaveCount(6);
        expect($options[0])->toHaveKeys(['value', 'label']);

        // Class-level label should take precedence over auto-generated
        $newOption = collect($options)->first(fn (array $o): bool => $o['value'] === 'new');
        expect($newOption['label'])->toBe('Brand New Item');
    });

    it('forApi returns full metadata with correct resolution', function () {
        $api = MixedAttributeStatus::forApi();

        expect($api)->toHaveCount(6);

        $active = collect($api)->first(fn (array $a): bool => $a['value'] === 'active');
        expect($active['label'])->toBe('Active');
        expect($active['color'])->toBe('success');
        expect($active['description'])->toBe('Currently active');
        expect($active['icon'])->toBe('heroicon-o-document');
    });

    it('values returns all backed values', function () {
        expect(MixedAttributeStatus::values())->toBe([
            'active',
            'new',
            'pending',
            'used',
            'archived',
            'deleted',
        ]);
    });

    it('labels returns labels in declaration order', function () {
        $labels = MixedAttributeStatus::labels();

        expect($labels)->toHaveCount(6);
        expect($labels[0])->toBe('Active');
        expect($labels[1])->toBe('Brand New Item');
    });

    it('supports tryFromLabel with class-level labels', function () {
        expect(MixedAttributeStatus::tryFromLabel('Brand New Item'))
            ->toBe(MixedAttributeStatus::NEW);
        expect(MixedAttributeStatus::tryFromLabel('Previously Owned'))
            ->toBe(MixedAttributeStatus::USED);
    });

    it('supports tryFromLabel with auto-generated labels', function () {
        expect(MixedAttributeStatus::tryFromLabel('Deleted'))
            ->toBe(MixedAttributeStatus::DELETED);
    });

    it('tryFromLabel returns null for unknown label', function () {
        expect(MixedAttributeStatus::tryFromLabel('NonExistent'))->toBeNull();
    });

    it('supports comparison methods', function () {
        $status = MixedAttributeStatus::ACTIVE;

        expect($status->is(MixedAttributeStatus::ACTIVE))->toBeTrue();
        expect($status->is('ACTIVE'))->toBeTrue();
        expect($status->isNot(MixedAttributeStatus::DELETED))->toBeTrue();
        expect($status->in([MixedAttributeStatus::ACTIVE, MixedAttributeStatus::NEW]))->toBeTrue();
        expect($status->in(['ACTIVE', 'NEW']))->toBeTrue();
        expect($status->in([MixedAttributeStatus::DELETED]))->toBeFalse();
    });

    it('supports tryFromName and fromName', function () {
        expect(MixedAttributeStatus::tryFromName('ACTIVE'))->toBe(MixedAttributeStatus::ACTIVE);
        expect(MixedAttributeStatus::tryFromName('NON_EXISTENT'))->toBeNull();
        expect(MixedAttributeStatus::hasCase('PENDING'))->toBeTrue();
        expect(MixedAttributeStatus::hasCase('UNKNOWN'))->toBeFalse();
    });

    it('fromName throws InvalidEnumException for unknown case', function () {
        expect(fn (): mixed => MixedAttributeStatus::fromName('BOGUS'))
            ->toThrow(InvalidEnumException::class);
    });

    it('has unique values in forSelect', function () {
        $values = array_column(MixedAttributeStatus::forSelect(), 'value');
        expect($values)->each->toBeUnique();
    });
});
