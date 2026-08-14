<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;

/**
 * Tests for EnumCache singleton isolation, TTL boundaries, and edge cases.
 *
 * Verifies the cache behaves correctly across multiple enum classes,
 * TTL expiration, and reset behavior — all critical for production
 * reliability in long-running processes (Octane/Swoole).
 */
describe('EnumCache singleton and TTL boundary tests', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('returns the same singleton instance on repeated getInstance() calls', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
        expect(spl_object_id($a))->toBe(spl_object_id($b));
    });

    it('returns a new instance after resetInstance()', function () {
        $a = EnumCache::getInstance();
        EnumCache::resetInstance();
        $b = EnumCache::getInstance();

        expect($a)->not->toBe($b);
        expect(spl_object_id($a))->not->toBe(spl_object_id($b));
    });

    it('isolates metadata between different enum classes', function () {
        EnumMetadataResolver::resolve(PaymentStatus::class);
        EnumMetadataResolver::resolve(SystemStatus::class);

        $cache = EnumCache::getInstance();

        expect($cache->has(PaymentStatus::class))->toBeTrue();
        expect($cache->has(SystemStatus::class))->toBeTrue();

        $paymentMeta = $cache->get(PaymentStatus::class);
        $systemMeta = $cache->get(SystemStatus::class);

        // Verify they are different metadata arrays
        expect($paymentMeta)->not->toBe($systemMeta);
        expect($paymentMeta['labels'])->not->toBeEmpty();
        expect($systemMeta['labels'])->not->toBeEmpty();
    });

    it('clears all entries with clear() but keeps singleton', function () {
        EnumMetadataResolver::resolve(PaymentStatus::class);
        EnumMetadataResolver::resolve(SystemStatus::class);

        $cache = EnumCache::getInstance();
        $cache->clear();

        expect($cache->has(PaymentStatus::class))->toBeFalse();
        expect($cache->has(SystemStatus::class))->toBeFalse();
    });

    it('clears only specific class with clearClass()', function () {
        EnumMetadataResolver::resolve(PaymentStatus::class);
        EnumMetadataResolver::resolve(SystemStatus::class);

        $cache = EnumCache::getInstance();
        $cache->clearClass(PaymentStatus::class);

        expect($cache->has(PaymentStatus::class))->toBeFalse();
        expect($cache->has(SystemStatus::class))->toBeTrue();
    });

    it('expires entries after TTL', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Disable caching — entries should always be stale

        $cache->set(PaymentStatus::class, [
            'labels' => ['paid' => 'Paid'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(PaymentStatus::class))->toBeFalse();
    });

    it('get() throws OutOfBoundsException when no entry exists', function () {
        EnumCache::getInstance();

        expect(fn () => EnumCache::getInstance()->get('NonExistentEnum'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('TTL is normalized to 0 for negative values', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('flush() is a static convenience that clears all entries', function () {
        EnumMetadataResolver::resolve(PaymentStatus::class);

        EnumCache::flush();

        expect(EnumCache::getInstance()->has(PaymentStatus::class))->toBeFalse();
    });

    it('supports multiple sequential set/get/clear cycles', function () {
        $cache = EnumCache::getInstance();

        for ($i = 0; $i < 5; $i++) {
            $className = 'TestClass'.$i;
            $cache->set($className, [
                'labels' => [$i => 'Label '.$i],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has($className))->toBeTrue();

            $meta = $cache->get($className);
            expect($meta['labels'][$i])->toBe('Label '.$i);
        }

        $cache->clear();

        for ($i = 0; $i < 5; $i++) {
            expect($cache->has('TestClass'.$i))->toBeFalse();
        }
    });
});

describe('InvalidEnumException factory methods', function () {
    it('creates value exception with null display', function () {
        $ex = InvalidEnumException::value('TestEnum', null);
        expect($ex->getMessage())->toContain('null');
        expect($ex->getMessage())->toContain('TestEnum');
    });

    it('creates value exception with string value', function () {
        $ex = InvalidEnumException::value('TestEnum', 'invalid');
        expect($ex->getMessage())->toContain('invalid');
    });

    it('creates value exception with int value', function () {
        $ex = InvalidEnumException::value('TestEnum', 999);
        expect($ex->getMessage())->toContain('999');
    });

    it('creates forName exception with correct message', function () {
        $ex = InvalidEnumException::forName('TestEnum', 'UNKNOWN_CASE');
        expect($ex->getMessage())->toContain('UNKNOWN_CASE');
        expect($ex->getMessage())->toContain('TestEnum');
    });

    it('__toString returns class name and message', function () {
        $ex = InvalidEnumException::forName('TestEnum', 'UNKNOWN_CASE');
        $str = (string) $ex;
        expect($str)->toContain(InvalidEnumException::class);
        expect($str)->toContain('UNKNOWN_CASE');
    });
});

describe('Single case enum edge cases', function () {
    it('SingleCaseToggle returns label and color for its single case', function () {
        $case = SingleCaseToggle::ON;
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->color())->toBeString();
    });

    it('forSelect returns single entry for single-case enum', function () {
        $select = SingleCaseToggle::forSelect();
        expect($select)->toHaveCount(1);
        expect($select[0])->toHaveKeys(['value', 'label']);
    });

    it('forApi returns single entry with full metadata', function () {
        $api = SingleCaseToggle::forApi();
        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('values returns single value', function () {
        $values = SingleCaseToggle::values();
        expect($values)->toHaveCount(1);
    });

    it('labels returns single label', function () {
        $labels = SingleCaseToggle::labels();
        expect($labels)->toHaveCount(1);
        expect($labels[0])->toBeString()->not->toBeEmpty();
    });
});

describe('Multiple enum type consistency', function () {
    it('string-backed, int-backed, and pure enum all have consistent metadata structure', function () {
        $stringMeta = EnumMetadataResolver::resolve(PaymentStatus::class);
        $intMeta = EnumMetadataResolver::resolve(SystemStatus::class);

        // All metadata has the same four keys
        expect(array_keys($stringMeta))->toBe(['labels', 'descriptions', 'colors', 'icons']);
        expect(array_keys($intMeta))->toBe(['labels', 'descriptions', 'colors', 'icons']);
    });

    it('each enum type resolves independently without cross-contamination', function () {
        $cache = EnumCache::getInstance();

        // Resolve all three types
        EnumMetadataResolver::resolve(PaymentStatus::class);
        EnumMetadataResolver::resolve(SystemStatus::class);

        // Invalidate one
        EnumMetadataResolver::invalidate(PaymentStatus::class);

        // PaymentStatus should be invalidated but SystemStatus should remain cached
        expect($cache->has(PaymentStatus::class))->toBeFalse();
        expect($cache->has(SystemStatus::class))->toBeTrue();

        // Re-resolve PaymentStatus should work
        $meta = EnumMetadataResolver::resolve(PaymentStatus::class);
        expect($meta['labels'])->not->toBeEmpty();
    });
});
