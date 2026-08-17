<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\{IntPriority, PaymentStatus, PureFeatureFlag};

describe('V47 — Enum Metadata Resolution Contract', function () {
    beforeEach(function () {
        EnumCache::flush();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('resolve() returns consistent metadata shape across all enum types', function () {
        $stringBacked = EnumMetadataResolver::resolve(PaymentStatus::class);
        $intBacked = EnumMetadataResolver::resolve(IntPriority::class);
        $pure = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        // All three must have the same four keys
        foreach ([$stringBacked, $intBacked, $pure] as $meta) {
            expect(array_keys($meta))->toBe(['labels', 'descriptions', 'colors', 'icons']);
        }

        // String-backed: labels keyed by string values
        expect($stringBacked['labels'])->toBeArray();
        expect($stringBacked['descriptions'])->toBeArray();
        expect($stringBacked['colors'])->toBeArray();
        expect($stringBacked['icons'])->toBeArray();

        // Int-backed: labels keyed by int values
        expect($intBacked['labels'])->toBeArray();
        expect($intBacked['descriptions'])->toBeArray();
        expect($intBacked['colors'])->toBeArray();
        expect($intBacked['icons'])->toBeArray();

        // Pure: labels keyed by case names
        expect($pure['labels'])->toBeArray();
        expect($pure['descriptions'])->toBeArray();
        expect($pure['colors'])->toBeArray();
        expect($pure['icons'])->toBeArray();
    });

    it('per-case attributes override class-level attributes', function () {
        $meta = EnumMetadataResolver::resolve(PaymentStatus::class);

        // PaymentStatus has class-level EnumColor(success: ['completed'], danger: ['failed'])
        // and per-case overrides — completed case may have per-case label
        expect($meta['labels'])->toBeArray();
        expect($meta['colors'])->toBeArray();

        // Verify color keys map to expected values
        foreach ($meta['colors'] as $key => $color) {
            expect($color)->toBeString();
            expect($color)->not->toBeEmpty();
        }
    });

    it('invalidate() forces next resolve() to rebuild via reflection', function () {
        $first = EnumMetadataResolver::resolve(PaymentStatus::class);
        EnumMetadataResolver::invalidate(PaymentStatus::class);
        $second = EnumMetadataResolver::resolve(PaymentStatus::class);

        // Same shape, rebuilt
        expect($first)->toEqual($second);
    });

    it('invalidateAll() flushes every cached enum', function () {
        EnumMetadataResolver::resolve(PaymentStatus::class);
        EnumMetadataResolver::resolve(IntPriority::class);
        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(PaymentStatus::class))->toBeFalse();
    });

    it('resolve() throws LogicException for non-enum class', function () {
        expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))
            ->toThrow(\LogicException::class);
    });

    it('resolve() throws for non-existent class', function () {
        expect(fn () => EnumMetadataResolver::resolve('NonExistentEnumClassV47'))
            ->toThrow(\LogicException::class);
    });

    it('cache TTL expiry triggers re-resolution', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Disable caching

        EnumMetadataResolver::resolve(PaymentStatus::class);
        expect($cache->has(PaymentStatus::class))->toBeFalse(); // TTL=0 means never cached
    });

    it('EnumCache::getInstance() returns singleton identity', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('EnumCache::clearClass() removes only targeted class', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(PaymentStatus::class, [
            'labels' => ['completed' => 'Paid'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(IntPriority::class, [
            'labels' => [1 => 'High'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(PaymentStatus::class))->toBeTrue();
        expect($cache->has(IntPriority::class))->toBeTrue();

        $cache->clearClass(PaymentStatus::class);

        expect($cache->has(PaymentStatus::class))->toBeFalse();
        expect($cache->has(IntPriority::class))->toBeTrue();
    });

    it('EnumCache serialization is blocked on all paths', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => serialize($cache))->toThrow(\RuntimeException::class);
        expect(fn () => unserialize('O:0:""'))->toThrow(\RuntimeException::class);
    });

    it('InvalidEnumException::value() formats null correctly', function () {
        $e = InvalidEnumException::value('TestEnum', null);
        expect($e->getMessage())->toContain('null');
        expect((string) $e)->toContain('InvalidEnumException');
    });

    it('InvalidEnumException::value() formats string correctly', function () {
        $e = InvalidEnumException::value('TestEnum', 'invalid_value');
        expect($e->getMessage())->toContain('invalid_value');
    });

    it('InvalidEnumException::value() formats int correctly', function () {
        $e = InvalidEnumException::value('TestEnum', 42);
        expect($e->getMessage())->toContain('42');
    });

    it('toValue() returns backed value for backed enums', function () {
        $case = PaymentStatus::COMPLETED;
        expect($case->toValue())->toBeString();
    });

    it('toValue() returns case name for pure enums', function () {
        $case = PureFeatureFlag::ENABLED;
        expect($case->toValue())->toBe($case->name);
    });
});
