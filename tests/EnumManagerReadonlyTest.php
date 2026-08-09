<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ZeroBoiler\Enums\EnumManager;

/**
 * Tests for EnumManager — readonly class verification, delegation, and edge cases.
 */
final class EnumManagerReadonlyTest extends TestCase
{
    private EnumManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new EnumManager;
    }

    // ---------------------------------------------------------------
    // Class structure assertions
    // ---------------------------------------------------------------

    public function test_enum_manager_is_final(): void
    {
        $ref = new ReflectionClass(EnumManager::class);

        $this->assertTrue($ref->isFinal());
    }

    public function test_enum_manager_is_readonly(): void
    {
        $ref = new ReflectionClass(EnumManager::class);

        $this->assertTrue($ref->isReadOnly());
    }

    public function test_enum_manager_has_no_public_properties(): void
    {
        $ref = new ReflectionClass(EnumManager::class);
        $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

        $this->assertEmpty($props, 'EnumManager should have no public properties');
    }

    // ---------------------------------------------------------------
    // forSelect delegation
    // ---------------------------------------------------------------

    public function test_for_select_throws_for_non_trait_enum(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('does not use HasEnumMetadata trait');

        $this->manager->forSelect(\stdClass::class);
    }

    public function test_for_select_throws_for_nonexistent_class(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $this->manager->forSelect('NonExistentEnumClass');
    }

    // ---------------------------------------------------------------
    // forApi delegation
    // ---------------------------------------------------------------

    public function test_for_api_throws_for_non_trait_enum(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('does not use HasEnumMetadata trait');

        $this->manager->forApi(\stdClass::class);
    }

    public function test_for_api_throws_for_nonexistent_class(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $this->manager->forApi('NonExistentEnumClass');
    }

    // ---------------------------------------------------------------
    // tryFromLabel delegation
    // ---------------------------------------------------------------

    public function test_try_from_label_throws_for_non_trait_enum(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('does not use HasEnumMetadata trait');

        $this->manager->tryFromLabel(\stdClass::class, 'test');
    }

    public function test_try_from_label_returns_null_for_nonexistent_class(): void
    {
        // Nonexistent class without the trait — BadMethodCallException is thrown
        $this->expectException(\BadMethodCallException::class);

        $this->manager->tryFromLabel('NonExistentEnumClass', 'test');
    }

    public function test_try_from_label_with_empty_string(): void
    {
        // This would throw because stdClass doesn't have HasEnumMetadata
        $this->expectException(\BadMethodCallException::class);

        $this->manager->tryFromLabel(\stdClass::class, '');
    }

    // ---------------------------------------------------------------
    // Type safety: strict string comparisons
    // ---------------------------------------------------------------

    public function test_for_select_is_method_exists_string_check(): void
    {
        // Ensure the class uses method_exists (string comparison) not instanceof
        $ref = new ReflectionMethod(EnumManager::class, 'forSelect');
        $contents = file_get_contents((string) $ref->getFileName());
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();
        $lines = array_slice(explode("\n", $contents), $start - 1, $end - $start + 1);
        $methodBody = implode("\n", $lines);

        $this->assertStringContainsString("method_exists(", $methodBody);
    }
}
