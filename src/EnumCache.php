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
 * This is a singleton that caches metadata per enum class with TTL-based
 * expiration to handle long-running processes gracefully.
 *
 * Thread safety: Not thread-safe. In multi-threaded Swoole/Octane environments,
 * each worker process has its own singleton instance.
 *
 * @see \ZeroBoiler\Enums\Support\EnumMetadataResolver For the resolver that uses this cache
 */
final class EnumCache
{
    private static ?self $instance = null;

    /**
     * @var array<string, array{labels: array<int|string, string>, descriptions: array<int|string, string>, colors: array<int|string, string>, icons: array<int|string, string>}>
     */
    private array $cache = [];

    /** @var array<string, float> Cache creation timestamps per enum class */
    private array $cacheTimestamps = [];

    /** Cache TTL in seconds (default: 300 = 5 minutes). 0 disables caching. */
    private int $ttl = 300;

    /**
     * Private constructor — use {@see getInstance()} to obtain the singleton.
     *
     * @internal Singleton accessor. Do not instantiate directly.
     */
    private function __construct() {}

    /**
     * Prevent cloning of the singleton instance.
     *
     * @throws \RuntimeException Always
     */
    private function __clone(): never
    {
        throw new \RuntimeException('EnumCache is a singleton and cannot be cloned.');
    }

    /**
     * Prevent unserialization of the singleton instance.
     *
     * @throws \RuntimeException Always
     */
    public function __wakeup(): never
    {
        throw new \RuntimeException('EnumCache is a singleton and cannot be unserialized.');
    }

    /**
     * Get the singleton cache instance.
     *
     * Thread safety: Each process (PHP-FPM worker, Octane worker) gets its own
     * singleton. There is no cross-process sharing.
     */
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
     *     labels: array<int|string, string>,
     *     descriptions: array<int|string, string>,
     *     colors: array<int|string, string>,
     *     icons: array<int|string, string>
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
     *     labels: array<int|string, string>,
     *     descriptions: array<int|string, string>,
     *     colors: array<int|string, string>,
     *     icons: array<int|string, string>
     * } $metadata
     */
    public function set(string $enumClass, array $metadata): void
    {
        $this->cache[$enumClass] = $metadata;
        $this->cacheTimestamps[$enumClass] = microtime(true);
    }

    /**
     * Set the cache TTL (seconds). Useful for testing or long-running processes.
     *
     * A TTL of 0 or less disables caching entirely — entries are always
     * considered stale. Negative values are normalized to 0.
     *
     * @param  int  $ttl  Cache time-to-live in seconds (0 = disabled)
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = max($ttl, 0);
    }

    /**
     * Get the current cache TTL in seconds.
     *
     * Returns 0 when caching is disabled.
     */
    public function getTtl(): int
    {
        return $this->ttl;
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
     *
     * @param  string  $enumClass  The fully-qualified enum class name
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
     * Reset the singleton instance.
     *
     * Primarily intended for test teardown. Calling this in production
     * code will break caching for the current process.
     *
     * @internal This method is intended for test teardown only.
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
