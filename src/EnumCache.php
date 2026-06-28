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

    /** @var array<string, float> Cache creation timestamps per enum class */
    private array $cacheTimestamps = [];

    /** Cache TTL in seconds (default: 300 = 5 minutes) */
    private int $ttl = 300;

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
        // Auto-expire stale cache entries
        if (isset($this->cache[$enumClass])) {
            $age = microtime(true) - ($this->cacheTimestamps[$enumClass] ?? 0);
            if ($age > $this->ttl) {
                unset($this->cache[$enumClass], $this->cacheTimestamps[$enumClass]);

                return false;
            }

            return true;
        }

        return false;
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
        $this->cacheTimestamps[$enumClass] = microtime(true);
    }

    /**
     * Set the cache TTL (seconds). Useful for testing or long-running processes.
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function clear(): void
    {
        $this->cache = [];
        $this->cacheTimestamps = [];
    }

    public function clearClass(string $enumClass): void
    {
        unset($this->cache[$enumClass]);
    }

    /**
     * Flush the entire cache (alias for clear, semantically explicit for resets).
     */
    public static function flush(): void
    {
        $instance = self::getInstance();
        $instance->cache = [];
        $instance->cacheTimestamps = [];
    }

    /**
     * Reset the singleton instance — primarily for testing.
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
