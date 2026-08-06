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
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Check if cached metadata exists for an enum class.
     *
     * Automatically expires stale entries based on TTL.
     */
    public function has(string $enumClass): bool
    {
        // TTL <= 0 means no caching — entries are always stale
        if ($this->ttl <= 0) {
            return false;
        }

        // Auto-expire stale cache entries
        if (isset($this->cache[$enumClass])) {
            $age = microtime(true) - ($this->cacheTimestamps[$enumClass] ?? 0);
            if ($age >= $this->ttl) {
                unset($this->cache[$enumClass], $this->cacheTimestamps[$enumClass]);

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Get cached metadata for an enum class.
     *
     * Call {@see has()} first to check if the entry exists, or use
     * {@see EnumMetadataResolver::resolve()} which handles caching transparently.
     *
     * @return array{
     *     labels: array<string, string>,
     *     descriptions: array<string, string>,
     *     colors: array<string, string>,
     *     icons: array<string, string>
     * }
     *
     * @throws \OutOfBoundsException If no cached entry exists for the given enum class
     */
    public function get(string $enumClass): array
    {
        if (! isset($this->cache[$enumClass])) {
            throw new \OutOfBoundsException("No cached metadata for [{$enumClass}]. Call has() first.");
        }

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

    /**
     * Clear all cached metadata entries.
     */
    public function clear(): void
    {
        $this->cache = [];
        $this->cacheTimestamps = [];
    }

    /**
     * Clear cached metadata for a specific enum class.
     */
    public function clearClass(string $enumClass): void
    {
        unset($this->cache[$enumClass], $this->cacheTimestamps[$enumClass]);
    }

    /**
     * Flush the entire cache (alias for clear, semantically explicit for resets).
     *
     * Convenience static accessor that delegates to the singleton's clear().
     */
    public static function flush(): void
    {
        self::getInstance()->clear();
    }

    /**
     * Reset the singleton instance — primarily for testing.
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
