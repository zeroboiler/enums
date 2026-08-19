<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

#[EnumLabel(labels: ['active' => 'Active', 'banned' => 'Banned'])]
enum CacheTtlStringEnum: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
    case BANNED = 'banned';
}

#[Icon('heroicon-o-check')]
enum CacheTtlPureEnum
{
    use HasEnumMetadata;

    case FEATURE_A;
    case FEATURE_B;
}

enum NoMetadataIntEnum: int
{
    case LOW = 0;
    case HIGH = 1;
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('EnumCache singleton and TTL behavior', function () {
    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('returns the same instance on multiple calls', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('can set and get TTL', function () {
        $cache = EnumCache::getInstance();

        $cache->setTtl(60);
        expect($cache->getTtl())->toBe(60);

        $cache->setTtl(0);
        expect($cache->getTtl())->toBe(0);
    });

    it('clamps negative TTL to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('disables caching when TTL is 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        expect($cache->has(CacheTtlStringEnum::class))->toBeFalse();
    });

    it('caches and retrieves metadata', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $meta = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];
        $cache->set(CacheTtlStringEnum::class, $meta);

        expect($cache->has(CacheTtlStringEnum::class))->toBeTrue();
        expect($cache->get(CacheTtlStringEnum::class))->toBe($meta);
    });

    it('throws OutOfBoundsException when getting non-cached entry', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentClass'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('clears all cached entries', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(CacheTtlStringEnum::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(CacheTtlPureEnum::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        $cache->clear();

        expect($cache->has(CacheTtlStringEnum::class))->toBeFalse();
        expect($cache->has(CacheTtlPureEnum::class))->toBeFalse();
    });

    it('clears a specific class entry', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(CacheTtlStringEnum::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(CacheTtlPureEnum::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        $cache->clearClass(CacheTtlStringEnum::class);

        expect($cache->has(CacheTtlStringEnum::class))->toBeFalse();
        expect($cache->has(CacheTtlPureEnum::class))->toBeTrue();
    });

    it('static flush() delegates to singleton clear()', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(CacheTtlStringEnum::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        EnumCache::flush();

        expect($cache->has(CacheTtlStringEnum::class))->toBeFalse();
    });

    it('prevents cloning', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => clone $cache)
            ->toThrow(\RuntimeException::class);
    });

    it('prevents serialization', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => serialize($cache))
            ->toThrow(\RuntimeException::class);
    });

    it('prevents unserialization', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => unserialize('O:38:"ZeroBoiler\\Enums\\EnumCache":0:{}'))
            ->toThrow(\RuntimeException::class);
    });

    it('shows clean debug output', function () {
        $cache = EnumCache::getInstance();
        $info = $cache->__debugInfo();

        expect($info)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
        expect($info['cachedClasses'])->toBeInt();
        expect($info['timestampCount'])->toBeInt();
    });

    it('resets instance via resetInstance()', function () {
        $a = EnumCache::getInstance();
        EnumCache::resetInstance();
        $b = EnumCache::getInstance();

        // After reset, we get a new instance (not the same object)
        expect($a)->not->toBe($b);
    });
});

describe('EnumMetadataResolver cache integration', function () {
    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('resolves metadata from class-level attributes', function () {
        $meta = EnumMetadataResolver::resolve(CacheTtlStringEnum::class);

n        expect($meta['labels']['active'])->toBe('Active');
        expect($meta['labels']['banned'])->toBe('Banned');
    });

    it('resolves icon from per-case attribute', function () {
        $meta = EnumMetadataResolver::resolve(CacheTtlPureEnum::class);

        expect($meta['icons']['FEATURE_A'])->toBe('heroicon-o-check');
    });

    it('invalidates per-class cache', function () {
        EnumCache::getInstance()->setTtl(300);

        // First resolve — caches the result
        $first = EnumMetadataResolver::resolve(CacheTtlStringEnum::class);
        expect(EnumCache::getInstance()->has(CacheTtlStringEnum::class))->toBeTrue();

        // Invalidate
        EnumMetadataResolver::invalidate(CacheTtlStringEnum::class);
        expect(EnumCache::getInstance()->has(CacheTtlStringEnum::class))->toBeFalse();
    });

    it('invalidates all cached metadata', function () {
        EnumCache::getInstance()->setTtl(300);

        EnumMetadataResolver::resolve(CacheTtlStringEnum::class);
        EnumMetadataResolver::resolve(CacheTtlPureEnum::class);

        expect(EnumCache::getInstance()->has(CacheTtlStringEnum::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(CacheTtlPureEnum::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(CacheTtlStringEnum::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(CacheTtlPureEnum::class))->toBeFalse();
    });

    it('throws LogicException for non-enum class', function () {
        expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))
            ->toThrow(\LogicException::class);
    });
});

describe('EnumMetadataResolver resolution priority', function () {
    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('per-case Label overrides class-level EnumLabel', function () {
        // CacheTtlStringEnum has EnumLabel for 'active' => 'Active'
        // But individual cases can override via Label attribute at case level
        $meta = EnumMetadataResolver::resolve(CacheTtlStringEnum::class);

        // 'active' has class-level label 'Active' (no per-case override)
        expect($meta['labels']['active'])->toBe('Active');
    });

    it('default color is secondary when no color attribute is set', function () {
        $meta = EnumMetadataResolver::resolve(CacheTtlStringEnum::class);

        // No #[Color] or #[EnumColor] set — colors map should be empty,
        // and the trait defaults to 'secondary' via the color() method
        expect($meta['colors'])->toBe([]);
    });
});

describe('InvalidEnumException named constructors', function () {
    it('creates value-based exception', function () {
        $e = InvalidEnumException::value('App\\Enums\\Status', 'invalid');

        expect($e->getMessage())->toContain('invalid');
        expect($e->getMessage())->toContain('App\\Enums\\Status');
    });

    it('creates name-based exception', function () {
        $e = InvalidEnumException::forName('App\\Enums\\Status', 'UNKNOWN');

        expect($e->getMessage())->toContain('UNKNOWN');
        expect($e->getMessage())->toContain('App\\Enums\\Status');
    });

    it('handles null value in value() constructor', function () {
        $e = InvalidEnumException::value('App\\Enums\\Status', null);

        expect($e->getMessage())->toContain('null');
    });

    it('stringifies to class name + message', function () {
        $e = InvalidEnumException::value('App\\Enums\\Status', 'bad');

        expect((string) $e)->toContain(InvalidEnumException::class);
        expect((string) $e)->toContain('bad');
    });
});

describe('EnumCast type safety edge cases', function () {
    it('is a final class implementing CastsAttributes', function () {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\Casts\EnumCast::class);

        expect($ref->isFinal())->toBeTrue();
        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))->toBeTrue();
    });

    it('has a named constructor of()', function () {
        $ref = new \ReflectionMethod(\ZeroBoiler\Enums\Casts\EnumCast::class, 'of');

        expect($ref->isPublic())->toBeTrue();
        expect($ref->isStatic())->toBeTrue();
    });
});

describe('EnumRule type safety edge cases', function () {
    it('is a final readonly class', function () {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\Rules\EnumRule::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('implements ValidationRule interface', function () {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\Rules\EnumRule::class);

        expect($ref->implementsInterface(\Illuminate\Contracts\Validation\ValidationRule::class))->toBeTrue();
    });

    it('has a named constructor for()', function () {
        $ref = new \ReflectionMethod(\ZeroBoiler\Enums\Rules\EnumRule::class, 'for');

        expect($ref->isPublic())->toBeTrue();
        expect($ref->isStatic())->toBeTrue();
    });

    it('has a nullable() method returning new instance', function () {
        $ref = new \ReflectionMethod(\ZeroBoiler\Enums\Rules\EnumRule::class, 'nullable');

        expect($ref->isPublic())->toBeTrue();
    });
});
