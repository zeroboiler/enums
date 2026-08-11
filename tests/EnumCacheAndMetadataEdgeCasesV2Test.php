<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Comprehensive edge case tests for EnumCache and metadata resolution.
 *
 * Covers: TTL=0 behavior, resetInstance isolation, multi-enum cache
 * correctness, metadata key type strictness (int vs string), and
 * cache invalidation sequence verification.
 *
 * @see \ZeroBoiler\Enums\EnumCache
 * @see \ZeroBoiler\Enums\Support\EnumMetadataResolver
 */
final class EnumCacheAndMetadataEdgeCasesV2Test extends TestCase
{
    protected function setUp(): void
    {
        EnumCache::flush();
        EnumCache::getInstance()->setTtl(300);
        EnumMetadataResolver::invalidateAll();
    }

    protected function tearDown(): void
    {
        EnumCache::flush();
        EnumCache::getInstance()->setTtl(300);
        EnumMetadataResolver::invalidateAll();
    }

    // -------------------------------------------------------------------
    // TTL=0: Caching disabled
    // -------------------------------------------------------------------

    /**
     * @test TTL=0 means has() always returns false
     */
    public function ttlZeroDisablesCaching(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->clear();

        // Set metadata for an enum
        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // TTL=0 means always stale
        $this->assertFalse($cache->has(UserStatus::class));
    }

    /**
     * @test TTL=0 causes metadata to be rebuilt on every resolve() call
     */
    public function ttlZeroForcesRebuildOnEveryResolve(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        // First resolve — builds metadata
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        $this->assertNotEmpty($meta1['labels']);

        // Second resolve — should rebuild since cache is always stale
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
        $this->assertEquals($meta1, $meta2);
    }

    /**
     * @test Negative TTL is normalized to 0
     */
    public function negativeTtlIsNormalizedToZero(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        $this->assertSame(0, $cache->getTtl());
    }

    // -------------------------------------------------------------------
    // resetInstance: Full singleton teardown
    // -------------------------------------------------------------------

    /**
     * @test resetInstance() creates a fresh singleton with default TTL
     */
    public function resetInstanceCreatesFreshSingleton(): void
    {
        $cache = EnumCache::getInstance();
        $cache->setTtl(999);

        EnumCache::resetInstance();
        $fresh = EnumCache::getInstance();

        // Fresh instance should NOT have TTL=999
        $this->assertNotSame($cache, $fresh);
        $this->assertSame(300, $fresh->getTtl());
    }

    /**
     * @test resetInstance() clears all cached metadata
     */
    public function resetInstanceClearsAllMetadata(): void
    {
        $cache = EnumCache::getInstance();
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ]);

        EnumCache::resetInstance();
        $fresh = EnumCache::getInstance();

        $this->assertFalse($fresh->has(UserStatus::class));
    }

    // -------------------------------------------------------------------
    // Multi-enum cache isolation
    // -------------------------------------------------------------------

    /**
     * @test Multiple enums cache independently
     */
    public function multipleEnumsCacheIndependently(): void
    {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);
        EnumMetadataResolver::resolve(PureFeatureFlag::class);

        $cache = EnumCache::getInstance();

        $this->assertTrue($cache->has(UserStatus::class));
        $this->assertTrue($cache->has(IntBackedPriority::class));
        $this->assertTrue($cache->has(PureFeatureFlag::class));
    }

    /**
     * @test Invalidating one enum doesn't affect others
     */
    public function invalidatingOneEnumDoesNotAffectOthers(): void
    {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        EnumMetadataResolver::invalidate(UserStatus::class);

        $cache = EnumCache::getInstance();

        $this->assertFalse($cache->has(UserStatus::class));
        $this->assertTrue($cache->has(IntBackedPriority::class));
    }

    /**
     * @test invalidateAll() clears everything
     */
    public function invalidateAllClearsEverything(): void
    {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();

        $this->assertFalse($cache->has(UserStatus::class));
        $this->assertFalse($cache->has(IntBackedPriority::class));
    }

    // -------------------------------------------------------------------
    // Metadata key type strictness (int vs string keys)
    // -------------------------------------------------------------------

    /**
     * @test Int-backed enum uses int keys in metadata
     */
    public function intBackedEnumUsesIntKeys(): void
    {
        $meta = EnumMetadataResolver::resolve(IntBackedPriority::class);

        // Labels should have int keys
        $this->assertArrayHasKey(1, $meta['labels']);
        $this->assertArrayHasKey(3, $meta['labels']);
        $this->assertSame('Critical Priority', $meta['labels'][1]);

        // Colors should have int keys
        $this->assertArrayHasKey(1, $meta['colors']);
        $this->assertSame('danger', $meta['colors'][1]);
    }

    /**
     * @test String-backed enum uses string keys in metadata
     */
    public function stringBackedEnumUsesStringKeys(): void
    {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        $this->assertArrayHasKey('active', $meta['labels']);
        $this->assertArrayHasKey('banned', $meta['colors']);
    }

    /**
     * @test Pure enum uses case name string keys in metadata
     */
    public function pureEnumUsesCaseNameStringKeys(): void
    {
        $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        $this->assertArrayHasKey('DARK_MODE', $meta['labels']);
        $this->assertArrayHasKey('BETA_FEATURES', $meta['colors']);
    }

    // -------------------------------------------------------------------
    // forApi/forSelect alignment
    // -------------------------------------------------------------------

    /**
     * @test forSelect returns correct value-type alignment for int-backed enums
     */
    public function forSelectReturnsIntValuesForIntBackedEnum(): void
    {
        $select = IntBackedPriority::forSelect();

        foreach ($select as $option) {
            $this->assertIsInt($option['value']);
            $this->assertIsString($option['label']);
        }
    }

    /**
     * @test forSelect returns string values for string-backed enums
     */
    public function forSelectReturnsStringValuesForStringBackedEnum(): void
    {
        $select = UserStatus::forSelect();

        foreach ($select as $option) {
            $this->assertIsString($option['value']);
            $this->assertIsString($option['label']);
        }
    }

    /**
     * @test forSelect returns case names for pure enums
     */
    public function forSelectReturnsCaseNamesForPureEnum(): void
    {
        $select = PureFeatureFlag::forSelect();

        foreach ($select as $option) {
            $this->assertIsString($option['value']);
            $this->assertIsString($option['label']);
        }

        // Pure enum values should be case names
        $values = array_column($select, 'value');
        $this->assertContains('DARK_MODE', $values);
        $this->assertContains('BETA_FEATURES', $values);
    }

    /**
     * @test forApi returns consistent types across all enum types
     */
    public function forApiReturnsConsistentStructureAcrossEnumTypes(): void
    {
        $userStatusApi = UserStatus::forApi();
        $intPriorityApi = IntBackedPriority::forApi();
        $pureFlagApi = PureFeatureFlag::forApi();

        foreach ([$userStatusApi, $intPriorityApi, $pureFlagApi] as $apiData) {
            foreach ($apiData as $case) {
                $this->assertArrayHasKey('value', $case);
                $this->assertArrayHasKey('name', $case);
                $this->assertArrayHasKey('label', $case);
                $this->assertArrayHasKey('description', $case);
                $this->assertArrayHasKey('color', $case);
                $this->assertArrayHasKey('icon', $case);
            }
        }
    }

    /**
     * @test forApi values match forSelect values for each enum type
     */
    public function forApiValuesMatchForSelectValues(): void
    {
        $enums = [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class];

        foreach ($enums as $enumClass) {
            $apiValues = array_column($enumClass::forApi(), 'value');
            $selectValues = array_column($enumClass::forSelect(), 'value');

            $this->assertEquals(
                $selectValues,
                $apiValues,
                "forApi() and forSelect() values diverge for {$enumClass}"
            );
        }
    }

    // -------------------------------------------------------------------
    // Exception factory methods
    // -------------------------------------------------------------------

    /**
     * @test InvalidEnumException::value() with null
     */
    public function invalidEnumExceptionFormatsNullValue(): void
    {
        $exception = \ZeroBoiler\Enums\Exceptions\InvalidEnumException::value(
            'App\\Enums\\Foo',
            null,
        );

        $this->assertStringContainsString('null', $exception->getMessage());
        $this->assertStringContainsString('App\\Enums\\Foo', $exception->getMessage());
    }

    /**
     * @test InvalidEnumException::value() with int
     */
    public function invalidEnumExceptionFormatsIntValue(): void
    {
        $exception = \ZeroBoiler\Enums\Exceptions\InvalidEnumException::value(
            'App\\Enums\\Priority',
            999,
        );

        $this->assertStringContainsString('999', $exception->getMessage());
    }

    /**
     * @test InvalidEnumException::forName() formats message correctly
     */
    public function invalidEnumExceptionFormatsName(): void
    {
        $exception = \ZeroBoiler\Enums\Exceptions\InvalidEnumException::forName(
            'App\\Enums\\Status',
            'NONEXISTENT',
        );

        $this->assertStringContainsString('NONEXISTENT', $exception->getMessage());
        $this->assertStringContainsString('App\\Enums\\Status', $exception->getMessage());
    }

    // -------------------------------------------------------------------
    // EnumCache::get() throws on missing entry
    // -------------------------------------------------------------------

    /**
     * @test get() throws OutOfBoundsException when no cached entry exists
     */
    public function cacheGetThrowsOnMissingEntry(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();

        $this->expectException(\OutOfBoundsException::class);
        $cache->get(UserStatus::class);
    }

    /**
     * @test set() stores metadata that get() can retrieve
     */
    public function cacheSetGetRoundtrip(): void
    {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(300);

        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ];

        $cache->set(UserStatus::class, $metadata);
        $this->assertTrue($cache->has(UserStatus::class));
        $this->assertSame($metadata, $cache->get(UserStatus::class));
    }
}
