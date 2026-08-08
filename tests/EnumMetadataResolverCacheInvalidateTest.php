<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;

describe('EnumMetadataResolver cache invalidation', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('caches metadata on first resolve and returns same instance on second call', function () {
        $cache = EnumCache::getInstance();

        expect($cache->has(OrderStatus::class))->toBeFalse();

        $meta1 = EnumMetadataResolver::resolve(OrderStatus::class);
        $meta2 = EnumMetadataResolver::resolve(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeTrue();
        expect($meta1)->toBe($meta2); // strict identity — same cached array
    });

    it('invalidates cache for a single class', function () {
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(OrderStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        EnumMetadataResolver::invalidate(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue(); // unaffected
    });

    it('invalidates all cached metadata across all classes', function () {
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);
        EnumMetadataResolver::resolve(MixedAttributeStatus::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(OrderStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();
        expect($cache->has(MixedAttributeStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
        expect($cache->has(MixedAttributeStatus::class))->toBeFalse();
    });

    it('rebuilds metadata correctly after invalidation', function () {
        $meta1 = EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        $originalLabels = $meta1['labels'];

        EnumMetadataResolver::invalidate(MixedAttributeStatus::class);

        $meta2 = EnumMetadataResolver::resolve(MixedAttributeStatus::class);

        // Same structure, same data after rebuild
        expect($meta2['labels'])->toEqual($originalLabels);
    });

    it('resolves multiple enum types independently without cross-contamination', function () {
        $orderMeta = EnumMetadataResolver::resolve(OrderStatus::class);
        $priorityMeta = EnumMetadataResolver::resolve(Priority::class);

        // String-backed enum has string-backed-value keys
        expect($orderMeta['labels'])->toHaveKey('pending');
        expect($orderMeta['labels'])->toHaveKey('shipped');

        // Int-backed enum has int-backed-value keys
        expect($priorityMeta['labels'])->toHaveKey(1);
        expect($priorityMeta['labels'])->toHaveKey(2);
        expect($priorityMeta['labels'])->not->toHaveKey('pending');
    });

    it('resolves class-level EnumLabel for string-backed enums', function () {
        $meta = EnumMetadataResolver::resolve(MixedAttributeStatus::class);

        // From #[EnumLabel(labels: ['new' => 'Brand New Item', ...])]
        expect($meta['labels']['new'])->toBe('Brand New Item');
        expect($meta['labels']['used'])->toBe('Previously Owned');
    });

    it('resolves class-level EnumColor for string-backed enums', function () {
        $meta = EnumMetadataResolver::resolve(MixedAttributeStatus::class);

        // From #[EnumColor(success: ['active', 'new'], warning: ['pending', 'used'], danger: ['archived'])]
        expect($meta['colors']['active'])->toBe('success');
        expect($meta['colors']['new'])->toBe('success');
        expect($meta['colors']['pending'])->toBe('warning');
        expect($meta['colors']['archived'])->toBe('danger');
    });

    it('resolves class-level EnumDescription for string-backed enums', function () {
        $meta = EnumMetadataResolver::resolve(MixedAttributeStatus::class);

        // From #[EnumDescription(descriptions: ['active' => 'Currently active', ...])]
        expect($meta['descriptions']['active'])->toBe('Currently active');
        expect($meta['descriptions']['pending'])->toBe('Awaiting review');
    });

    it('resolves class-level EnumIcon default for all cases', function () {
        $meta = EnumMetadataResolver::resolve(MixedAttributeStatus::class);

        // From #[EnumIcon(default: 'heroicon-o-document')]
        expect($meta['icons']['active'])->toBe('heroicon-o-document');
        expect($meta['icons']['pending'])->toBe('heroicon-o-document');
        expect($meta['icons']['deleted'])->toBe('heroicon-o-document');
    });

    it('returns empty arrays for unspecified metadata types', function () {
        // OrderStatus has no attributes at all
        $meta = EnumMetadataResolver::resolve(OrderStatus::class);

        expect($meta['labels'])->toBeArray()->toBeEmpty();
        expect($meta['descriptions'])->toBeArray()->toBeEmpty();
        expect($meta['colors'])->toBeArray()->toBeEmpty();
        expect($meta['icons'])->toBeArray()->toBeEmpty();
    });

    it('metadata structure has exactly four keys', function () {
        $meta = EnumMetadataResolver::resolve(OrderStatus::class);

        expect(array_keys($meta))->toEqual(['labels', 'descriptions', 'colors', 'icons']);
    });
});

describe('EnumCache TTL behaviour', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('TTL of 0 disables caching — has() always returns false', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        EnumMetadataResolver::resolve(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('negative TTL is normalized to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-10);

        expect($cache->getTtl())->toBe(0);
    });

    it('setTtl returns correct value via getTtl', function () {
        $cache = EnumCache::getInstance();

        $cache->setTtl(60);
        expect($cache->getTtl())->toBe(60);

        $cache->setTtl(300);
        expect($cache->getTtl())->toBe(300);
    });

    it('clearClass removes only the targeted class', function () {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        $cache->clearClass(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });

    it('clear removes all entries', function () {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        $cache->clear();

        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
    });

    it('flush is a static convenience for clear', function () {
        EnumCache::getInstance(); // ensure singleton exists

        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumCache::flush();

        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
    });

    it('get throws OutOfBoundsException when entry does not exist', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))
            ->toThrow(OutOfBoundsException::class);
    });
});

describe('fromName edge cases', function () {
    it('throws InvalidEnumException for non-existent case', function () {
        expect(fn () => OrderStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class, 'Case name [NON_EXISTENT] does not exist on enum');
    });

    it('exception includes class name in message', function () {
        try {
            OrderStatus::fromName('UNKNOWN');
            expect(false)->toBeTrue(); // should not reach here
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain(OrderStatus::class);
            expect($e->getMessage())->toContain('UNKNOWN');
        }
    });

    it('exception value() factory includes value in message', function () {
        $e = InvalidEnumException::value('TestEnum', 'invalid_value');

        expect($e->getMessage())->toContain('invalid_value');
        expect($e->getMessage())->toContain('TestEnum');
    });

    it('exception value() factory handles null value', function () {
        $e = InvalidEnumException::value('TestEnum', null);

        expect($e->getMessage())->toContain('null');
    });

    it('exception value() factory handles int value', function () {
        $e = InvalidEnumException::value('TestEnum', 42);

        expect($e->getMessage())->toContain('42');
    });
});
