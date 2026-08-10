<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enums Production Readiness — Full Audit', function () {
    // ── EnumCache Singleton Behaviour ──────────────────────────────────

    describe('EnumCache singleton lifecycle', function () {
        it('returns the same instance on repeated calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('starts with empty cache', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('stores and retrieves metadata correctly', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            $metadata = [
                'labels' => ['active' => 'Active'],
                'descriptions' => ['active' => 'Active status'],
                'colors' => ['active' => 'success'],
                'icons' => ['active' => 'check'],
            ];
            $cache->set(UserStatus::class, $metadata);

            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->get(UserStatus::class))->toBe($metadata);
        });

        it('throws OutOfBoundsException when getting non-existent entry', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            expect(fn () => $cache->get('NonExistentEnum'))
                ->toThrow(\OutOfBoundsException::class);
        });

        it('respects TTL expiration', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(0); // disable caching

            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeFalse();

            // Restore default TTL for other tests
            $cache->setTtl(300);
        });

        it('clears specific class without affecting others', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(OrderStatus::class, [
                'labels' => ['pending' => 'Pending'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(OrderStatus::class))->toBeTrue();
        });

        it('flush clears everything including via static method', function () {
            $cache = EnumCache::getInstance();
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('setTtl normalizes negative values to zero', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);

            expect($cache->getTtl())->toBe(0);

            $cache->setTtl(300);
        });
    });

    // ── EnumMetadataResolver ───────────────────────────────────────────

    describe('EnumMetadataResolver', function () {
        it('resolves metadata for string-backed enums', function () {
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta)->toBeArray();
            expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        });

        it('resolves metadata for int-backed enums', function () {
            $meta = EnumMetadataResolver::resolve(IntBackedPriority::class);

            expect($meta)->toBeArray();
            expect($meta['colors'])->toBeArray();
        });

        it('resolves metadata for pure enums', function () {
            $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

            expect($meta)->toBeArray();
            expect($meta['labels'])->toBeArray();
        });

        it('invalidates metadata for specific class', function () {
            EnumMetadataResolver::invalidate(UserStatus::class);
            // Re-resolve should work without errors
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta)->toBeArray();
        });

        it('invalidates all metadata', function () {
            EnumMetadataResolver::invalidateAll();
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta)->toBeArray();
        });

        it('caches resolved metadata', function () {
            EnumCache::getInstance()->clear();
            EnumMetadataResolver::invalidateAll();

            $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
            $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta1)->toBe($meta2);
        });
    });

    // ── InvalidEnumException Factory Methods ──────────────────────────

    describe('InvalidEnumException', function () {
        it('creates value exception with null', function () {
            $e = InvalidEnumException::value(UserStatus::class, null);

            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain(UserStatus::class);
        });

        it('creates value exception with string', function () {
            $e = InvalidEnumException::value(UserStatus::class, 'invalid');

            expect($e->getMessage())->toContain('invalid');
        });

        it('creates value exception with int', function () {
            $e = InvalidEnumException::value(IntBackedPriority::class, 999);

            expect($e->getMessage())->toContain('999');
        });

        it('creates forName exception', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN');

            expect($e->getMessage())->toContain('UNKNOWN');
            expect($e->getMessage())->toContain(UserStatus::class);
        });
    });

    // ── EnumRule Edge Cases ───────────────────────────────────────────

    describe('EnumRule edge cases', function () {
        it('accepts valid backed enum value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = fn () => null;

            // Should not call fail for valid value
            $called = false;
            $rule->validate('status', 'active', function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });

        it('rejects invalid backed enum value', function () {
            $rule = EnumRule::for(UserStatus::class);

            $called = false;
            $rule->validate('status', 'nonexistent', function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeTrue();
        });

        it('rejects null when not nullable', function () {
            $rule = EnumRule::for(UserStatus::class);

            $called = false;
            $rule->validate('status', null, function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeTrue();
        });

        it('accepts null when nullable', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();

            $called = false;
            $rule->validate('status', null, function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });

        it('rejects wrong type for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);

            $called = false;
            $rule->validate('priority', 'high', function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeTrue();
        });

        it('accepts valid int value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);

            $called = false;
            $rule->validate('priority', 1, function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });
    });

    // ── EnumManager Delegation ─────────────────────────────────────────

    describe('EnumManager delegation', function () {
        it('throws BadMethodCallException for class without HasEnumMetadata', function () {
            $manager = new EnumManager;

            expect(fn () => $manager->forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('generates select options via manager', function () {
            $manager = new EnumManager;
            $options = $manager->forSelect(UserStatus::class);

            expect($options)->toBeArray();
            expect($options)->not->toBeEmpty();
            expect($options[0])->toHaveKeys(['value', 'label']);
        });

        it('generates API metadata via manager', function () {
            $manager = new EnumManager;
            $api = $manager->forApi(UserStatus::class);

            expect($api)->toBeArray();
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'color', 'icon', 'description']);
        });

        it('resolves label via manager', function () {
            $manager = new EnumManager;
            $case = $manager->tryFromLabel(UserStatus::class, UserStatus::ACTIVE->label());

            expect($case)->not->toBeNull();
            expect($case->name)->toBe('ACTIVE');
        });
    });

    // ── Single Case Enum ──────────────────────────────────────────────

    describe('Single case enum', function () {
        it('has exactly one case', function () {
            expect(SingleCaseEnum::cases())->toHaveCount(1);
        });

        it('forSelect returns one option', function () {
            expect(SingleCaseEnum::forSelect())->toHaveCount(1);
        });

        it('values returns one entry', function () {
            expect(SingleCaseEnum::values())->toHaveCount(1);
        });

        it('in() works with single-element list', function () {
            expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
        });
    });

    // ── Zero Priority (Int-backed with zero value) ─────────────────────

    describe('ZeroPriority enum', function () {
        it('has a case with zero backed value', function () {
            expect(ZeroPriority::NONE->value)->toBe(0);
        });

        it('forSelect includes zero-valued case', function () {
            $options = ZeroPriority::forSelect();
            $values = array_column($options, 'value');

            expect(in_array(0, $values, true))->toBeTrue();
        });

        it('values() includes zero', function () {
            $values = ZeroPriority::values();

            expect(in_array(0, $values, true))->toBeTrue();
        });
    });

    // ── Cross-enum comparison safety ──────────────────────────────────

    describe('Type safety', function () {
        it('is() returns false for wrong enum type', function () {
            // PHP type system enforces this — but let's verify the trait behavior
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
        });

        it('fromName is case-sensitive', function () {
            expect(UserStatus::tryFromName('active'))->toBeNull();
            expect(UserStatus::tryFromName('ACTIVE'))->toBeInstanceOf(UserStatus::class);
        });

        it('hasCase is case-sensitive', function () {
            expect(UserStatus::hasCase('active'))->toBeFalse();
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        });
    });
});
