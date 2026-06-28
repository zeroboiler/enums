<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums;

/**
 * Cache for enum metadata to avoid property restrictions in PHP enums.
 *
 * PHP enums cannot have properties, so we use an external cache class.
 * This is a singleton that caches metadata per enum class.
 */
final class EnumCache
{
    private static ?self $instance = null;

    /**
     * @var array<string, array{
     *     labels: array<string, string>,
     *     descriptions: array<string, string>,
     *     colors: array<string, string>,
     *     icons: array<string, string>
     * }>
     */
    private array $cache = [];

    private function __construct()
    {
        // Singleton
    }

    public static function getInstance(): self
    {
        if (! self::$instance instanceof EnumCache) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function has(string $enumClass): bool
    {
        return isset($this->cache[$enumClass]);
    }

    /**
     * @return array{
     *     labels: array<string, string>,
     *     descriptions: array<string, string>,
     *     colors: array<string, string>,
     *     icons: array<string, string>
     * }
     */
    public function get(string $enumClass): array
    {
        return $this->cache[$enumClass];
    }

    /**
     * @param array{
     *     labels: array<string, string>,
     *     descriptions: array<string, string>,
     *     colors: array<string, string>,
     *     icons: array<string, string>
     * } $metadata
     */
    public function set(string $enumClass, array $metadata): void
    {
        $this->cache[$enumClass] = $metadata;
    }

    public function clear(): void
    {
        $this->cache = [];
    }

    public function clearClass(string $enumClass): void
    {
        unset($this->cache[$enumClass]);
    }
}
