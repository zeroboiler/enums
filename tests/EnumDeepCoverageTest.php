<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

describe('EnumCache TTL edge cases', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('expires entries immediately when TTL is 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set(TestStatusTTL::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ]);

        // TTL 0 means entries are always stale
        expect($cache->has(TestStatusTTL::class))->toBeFalse();
    });

    it('expires entries immediately when TTL is negative', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-1);

        $cache->set(TestStatusTTL::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(TestStatusTTL::class))->toBeFalse();
    });

    it('keeps entries fresh within TTL', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ];
        $cache->set(TestStatusTTL::class, $metadata);

        expect($cache->has(TestStatusTTL::class))->toBeTrue();
        expect($cache->get(TestStatusTTL::class))->toBe($metadata);
    });

    it('clears a single class without affecting others', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(TestStatusTTL::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->set(TestPriorityTTL::class, [
            'labels' => [1 => 'Critical'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(TestStatusTTL::class);

        expect($cache->has(TestStatusTTL::class))->toBeFalse();
        expect($cache->has(TestPriorityTTL::class))->toBeTrue();
    });

    it('throws OutOfBoundsException when getting non-existent entry', function () {
        $cache = EnumCache::getInstance();

        $cache->get('NonExistentEnum');
    })->throws(\OutOfBoundsException::class, 'No cached metadata for [NonExistentEnum]');

    it('flush clears all entries including timestamps', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(TestStatusTTL::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::flush();

        // After flush, getInstance returns same instance but cache is empty
        $fresh = EnumCache::getInstance();
        expect($fresh->has(TestStatusTTL::class))->toBeFalse();
    });
});

describe('EnumMetadataResolver cache interaction', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('resolves metadata and caches it', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        // First call — cold cache
        $meta1 = EnumMetadataResolver::resolve(TestStatusTTL::class);

        // Second call — should hit cache
        expect($cache->has(TestStatusTTL::class))->toBeTrue();
        $meta2 = EnumMetadataResolver::resolve(TestStatusTTL::class);

        expect($meta1)->toBe($meta2);
    });

    it('bypasses stale cache on TTL expiry', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Always stale

        // Even though we manually set, TTL=0 means always expired
        $cache->set(TestStatusTTL::class, [
            'labels' => ['stale' => 'Stale Label'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // resolve() should re-build because TTL=0 means has() returns false
        $meta = EnumMetadataResolver::resolve(TestStatusTTL::class);

        // Should have fresh metadata, not the stale 'stale' label
        expect($meta['labels'])->toHaveKey('active');
        expect($meta['labels']['active'])->toBe('Active');
    });
});

describe('HasEnumMetadata label fallback chain', function () {
    it('uses per-case Label when available', function () {
        expect(TestLabelChain::OVERRIDE->label())->toBe('Custom Override');
    });

    it('falls back to class-level EnumLabel for non-overridden cases', function () {
        expect(TestLabelChain::FROM_CLASS_LEVEL->label())->toBe('Class Level Label');
    });

    it('falls back to auto-generated label when no attribute set', function () {
        expect(TestLabelChain::AUTO_GENERATED->label())->toBe('Auto Generated');
    });

    it('resolves color with per-case override', function () {
        expect(TestLabelChain::OVERRIDE->color())->toBe('danger');
    });

    it('falls back to class-level EnumColor', function () {
        expect(TestLabelChain::FROM_CLASS_LEVEL->color())->toBe('success');
    });

    it('defaults color to secondary when no attribute', function () {
        expect(TestLabelChain::AUTO_GENERATED->color())->toBe('secondary');
    });

    it('resolves description with per-case override', function () {
        expect(TestLabelChain::OVERRIDE->description())->toBe('Override description');
    });

    it('falls back to class-level description', function () {
        expect(TestLabelChain::FROM_CLASS_LEVEL->description())->toBe('Class level desc');
    });

    it('returns null description when no attribute', function () {
        expect(TestLabelChain::AUTO_GENERATED->description())->toBeNull();
    });

    it('resolves icon with per-case override', function () {
        expect(TestLabelChain::OVERRIDE->icon())->toBe('heroicon-o-x-circle');
    });

    it('falls back to class-level default icon', function () {
        expect(TestLabelChain::FROM_CLASS_LEVEL->icon())->toBe('heroicon-o-check');
    });

    it('returns null icon when no attribute', function () {
        expect(TestLabelChain::AUTO_GENERATED->icon())->toBeNull();
    });
});

describe('EnumRule nullable edge cases', function () {
    it('rejects null when nullable is not set', function () {
        $rule = EnumRule::for(TestStatusTTL::class);

        $fail = fn (string $attr, string $msg = ''): string => $msg;
        $rule->validate('status', null, $fail);
    })->throws(\TypeError::class);

    it('accepts null when nullable is set', function () {
        $rule = EnumRule::for(TestStatusTTL::class)->nullable();

        $failed = false;
        $fail = function (string $attr, string $msg = '') use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', null, $fail);

        expect($failed)->toBeFalse();
    });

    it('validates int-backed enum rejects string value', function () {
        $rule = EnumRule::for(TestPriorityTTL::class);

        $failed = false;
        $fail = function (string $attr, string $msg = '') use (&$failed): void {
            $failed = true;
        };

        // TestPriorityTTL is int-backed; passing a string should fail
        $rule->validate('priority', 'not-an-int', $fail);

        expect($failed)->toBeTrue();
    });
});

describe('InvalidEnumException factories', function () {
    it('creates value exception with type info', function () {
        $exception = InvalidEnumException::value(TestStatusTTL::class, 42);

        expect($exception->getMessage())->toContain('int');
        expect($exception->getMessage())->toContain(TestStatusTTL::class);
    });

    it('creates forName exception with case name', function () {
        $exception = InvalidEnumException::forName(TestStatusTTL::class, 'INVALID_CASE');

        expect($exception->getMessage())->toContain('INVALID_CASE');
        expect($exception->getMessage())->toContain(TestStatusTTL::class);
    });
});

// ─── Test Fixtures ───────────────────────────────────────────────

#[EnumColor(success: ['active', 'from_class_level'], danger: ['override'])]
#[EnumLabel(labels: ['from_class_level' => 'Class Level Label'])]
#[EnumDescription(descriptions: ['from_class_level' => 'Class level desc'])]
#[EnumIcon(default: 'heroicon-o-check')]
enum TestStatusTTL: string
{
    use HasEnumMetadata;

    #[Label('Active')]
    case ACTIVE = 'active';
}

enum TestPriorityTTL: int
{
    use HasEnumMetadata;

    case CRITICAL = 1;
    case HIGH = 2;
}

#[EnumColor(success: ['from_class_level'], danger: ['override'])]
#[EnumLabel(labels: ['from_class_level' => 'Class Level Label'])]
#[EnumDescription(descriptions: ['from_class_level' => 'Class level desc'])]
#[EnumIcon(default: 'heroicon-o-check')]
enum TestLabelChain: string
{
    use HasEnumMetadata;

    #[Label('Custom Override')]
    #[Color('danger')]
    #[Icon('heroicon-o-x-circle')]
    #[Description('Override description')]
    case OVERRIDE = 'override';

    // Uses class-level label, color, description, icon
    case FROM_CLASS_LEVEL = 'from_class_level';

    // No attributes at all — should auto-generate label, default color/icon
    case AUTO_GENERATED = 'auto_generated';
}
