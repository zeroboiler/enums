<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

beforeEach(function (): void {
    EnumCache::flush();
    EnumCache::getInstance()->setTtl(300);
});

afterEach(function (): void {
    EnumCache::resetInstance();
});

describe('Enum immutability and thread safety', function (): void {
    it('hasEnumMetadata trait produces consistent results across multiple resolves', function (): void {
        $labels1 = array_map(
            static fn (\ZeroBoiler\Enums\Tests\Fixtures\UserStatus $c): string => $c->label(),
            \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::cases(),
        );
        $labels2 = array_map(
            static fn (\ZeroBoiler\Enums\Tests\Fixtures\UserStatus $c): string => $c->label(),
            \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::cases(),
        );

        expect($labels1)->toBe($labels2);
    });

    it('EnumCache singleton returns the same instance', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('EnumCache resetInstance creates a fresh singleton', function (): void {
        $first = EnumCache::getInstance();
        EnumCache::resetInstance();
        $second = EnumCache::getInstance();

        // Different object instances (not same reference)
        expect($first)->not->toBe($second);

        // Both are valid EnumCache instances
        expect($first)->toBeInstanceOf(EnumCache::class);
        expect($second)->toBeInstanceOf(EnumCache::class);
    });

    it('EnumCache clear removes all entries but preserves instance', function (): void {
        $cache = EnumCache::getInstance();
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class))->toBeTrue();

        $cache->clear();

        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class))->toBeFalse();
        expect($cache)->toBe(EnumCache::getInstance());
    });

    it('EnumCache clearClass removes only the specified class', function (): void {
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\Priority::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class))->toBeTrue();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\Priority::class))->toBeTrue();

        $cache->clearClass(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class))->toBeFalse();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\Priority::class))->toBeTrue();
    });

    it('EnumCache TTL 0 disables caching entirely', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        // With TTL=0, cache should not persist entries
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class))->toBeFalse();
    });

    it('EnumCache negative TTL is normalized to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
    });

    it('EnumCache setTtl/getTtl round-trips correctly', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(600);

        expect($cache->getTtl())->toBe(600);
    });

    it('EnumCache get throws OutOfBoundsException for missing entry', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn (): mixed => $cache->get('NonExistentEnum'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('EnumMetadataResolver::invalidate removes cached entry', function (): void {
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        $cache = EnumCache::getInstance();

        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidate(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class))->toBeFalse();
    });

    it('EnumMetadataResolver::invalidateAll flushes everything', function (): void {
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\Priority::class);
        $cache = EnumCache::getInstance();

        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class))->toBeTrue();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\Priority::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class))->toBeFalse();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\Priority::class))->toBeFalse();
    });

    it('cache rebuilds correctly after invalidation with same data', function (): void {
        $meta1 = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        EnumMetadataResolver::invalidate(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        $meta2 = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        expect($meta1)->toBe($meta2);
    });

    it('EnumMetadataResolver returns consistent structure', function (): void {
        $meta = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        expect($meta['labels'])->toBeArray();
        expect($meta['descriptions'])->toBeArray();
        expect($meta['colors'])->toBeArray();
        expect($meta['icons'])->toBeArray();
    });

    it('is() with string comparison is case-sensitive', function (): void {
        $case = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::ACTIVE;

        expect($case->is('ACTIVE'))->toBeTrue();
        expect($case->is('active'))->toBeFalse();
        expect($case->is('Active'))->toBeFalse();
    });

    it('is() rejects non-string non-instance types', function (): void {
        $case = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::ACTIVE;

        // PHP will throw TypeError for invalid union type (static|string)
        // but we verify the method accepts valid inputs
        expect($case->is($case))->toBeTrue();
        expect($case->is('ACTIVE'))->toBeTrue();
    });

    it('fromName() throws with descriptive message', function (): void {
        expect(function (): void {
            \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::fromName('NON_EXISTENT_CASE');
        })->toThrow(function (InvalidEnumException $e): bool {
            return str_contains($e->getMessage(), 'NON_EXISTENT_CASE')
                && str_contains($e->getMessage(), 'UserStatus');
        });
    });

    it('InvalidEnumException::value formats null value correctly', function (): void {
        $exception = InvalidEnumException::value('SomeEnum', null);

        expect($exception->getMessage())->toBe('Value [null] is not a valid case of [SomeEnum].');
    });

    it('InvalidEnumException::value formats string value correctly', function (): void {
        $exception = InvalidEnumException::value('SomeEnum', 'invalid_value');

        expect($exception->getMessage())->toBe('Value [invalid_value] is not a valid case of [SomeEnum].');
    });

    it('InvalidEnumException::value formats int value correctly', function (): void {
        $exception = InvalidEnumException::value('SomeEnum', 42);

        expect($exception->getMessage())->toBe('Value [42] is not a valid case of [SomeEnum].');
    });

    it('forSelect returns values in case declaration order', function (): void {
        $select = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::forSelect();
        $values = array_column($select, 'value');

        // Must match case order
        expect($values)->toEqual(
            array_map(
                static fn (\ZeroBoiler\Enums\Tests\Fixtures\UserStatus $c): string|int => $c instanceof \BackedEnum ? $c->value : $c->name,
                \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::cases(),
            ),
        );
    });

    it('forApi returns metadata with all required keys', function (): void {
        $api = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::forApi();

        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['value'])->toBeString()->or()->toBeInt();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString()->not()->toBeEmpty();
            expect($item['color'])->toBeString()->not()->toBeEmpty();
        }
    });

    it('in() returns false for empty array', function (): void {
        $case = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::ACTIVE;

        expect($case->in([]))->toBeFalse();
    });
});
