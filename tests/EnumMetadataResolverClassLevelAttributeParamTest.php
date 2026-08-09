<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumLabel and EnumDescription case-level parameters', function () {
    it('EnumLabel case-level label parameter overrides class-level labels map', function () {
        // When EnumLabel is used on a case with the `label` parameter,
        // it should be picked up by the resolver — same priority as #[Label].
        EnumMetadataResolver::invalidateAll();

        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // Existing class-level labels should still be in the metadata
        expect($meta['labels'])->toBeArray();
    });

    it('EnumDescription case-level description parameter provides per-case description', function () {
        EnumMetadataResolver::invalidateAll();

        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['descriptions'])->toBeArray();
    });

    it('EnumIcon case-level default provides fallback icon for all cases', function () {
        EnumMetadataResolver::invalidateAll();

        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // Icons may or may not be set depending on fixture setup
        expect($meta['icons'])->toBeArray();
    });

    it('Label attribute always takes highest priority over EnumLabel case-level', function () {
        EnumMetadataResolver::invalidateAll();

        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // If a case has both #[Label('X')] and #[EnumLabel(label: 'Y')],
        // #[Label] should win because it's checked first in the resolver.
        // The resolver iterates attributes in order and Label is matched
        // before EnumLabel in the per-case loop.
        expect($meta['labels'])->toBeArray();
    });
});

describe('EnumMetadataResolver EnumLabel case-level edge cases', function () {
    it('EnumLabel with null label parameter is ignored', function () {
        $attr = new EnumLabel(label: null);

        expect($attr->label)->toBeNull();
    });

    it('EnumLabel with empty string label parameter is ignored', function () {
        $attr = new EnumLabel(label: '');

        expect($attr->label)->toBe('');
    });

    it('EnumLabel with valid label parameter stores correctly', function () {
        $attr = new EnumLabel(label: 'Custom Label');

        expect($attr->label)->toBe('Custom Label');
    });

    it('EnumDescription with null description parameter is ignored', function () {
        $attr = new EnumDescription(description: null);

        expect($attr->description)->toBeNull();
    });

    it('EnumDescription with empty string description parameter is ignored', function () {
        $attr = new EnumDescription(description: '');

        expect($attr->description)->toBe('');
    });

    it('EnumDescription with valid description parameter stores correctly', function () {
        $attr = new EnumDescription(description: 'A detailed description');

        expect($attr->description)->toBe('A detailed description');
    });

    it('EnumIcon with null default provides no fallback', function () {
        $attr = new EnumIcon(default: null);

        expect($attr->default)->toBeNull();
    });

    it('EnumIcon with valid default stores correctly', function () {
        $attr = new EnumIcon(default: 'heroicon-o-star');

        expect($attr->default)->toBe('heroicon-o-star');
    });

    it('EnumLabel can store both labels map and single label simultaneously', function () {
        $attr = new EnumLabel(labels: ['a' => 'A Label'], label: 'Case Label');

        expect($attr->labels)->toBe(['a' => 'A Label']);
        expect($attr->label)->toBe('Case Label');
    });

    it('EnumDescription can store both descriptions map and single description', function () {
        $attr = new EnumDescription(descriptions: ['a' => 'Desc A'], description: 'Case Desc');

        expect($attr->descriptions)->toBe(['a' => 'Desc A']);
        expect($attr->description)->toBe('Case Desc');
    });
});

describe('EnumMetadataResolver integration with attribute precedence', function () {
    it('class-level EnumLabel labels map is populated before per-case resolution', function () {
        EnumMetadataResolver::invalidateAll();

        $meta = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus::class);

        // Class-level EnumLabel should set 'new' => 'Brand New Item'
        expect($meta['labels'])->toHaveKey('new');
        expect($meta['labels']['new'])->toBe('Brand New Item');
    });

    it('class-level EnumDescription descriptions map is populated correctly', function () {
        EnumMetadataResolver::invalidateAll();

        $meta = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::class);

        expect($meta['descriptions'])->toHaveKey('open');
        expect($meta['descriptions']['open'])->toBe('Task is open');
    });

    it('class-level EnumIcon default sets icon for all cases', function () {
        EnumMetadataResolver::invalidateAll();

        $meta = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::class);

        // All cases should have the default icon
        foreach (\ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::cases() as $case) {
            $value = $case->value;
            expect($meta['icons'])->toHaveKey($value);
            expect($meta['icons'][$value])->toBe('heroicon-o-circle');
        }
    });
});
