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
 * Not `readonly` because it holds mutable cache entries, TTL configuration,
 * and static singleton state. Compare with {@see EnumManager} which IS
 * `final readonly` because it is entirely stateless.
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
     * Public visibility is required for PHP magic methods.
     * The method always throws to enforce singleton semantics.
     *
     * @throws \RuntimeException Always
     */
    public function __clone(): never
    {
        throw new \RuntimeException('EnumCache is a singleton and cannot be cloned.');
    }

    /**
     * Get a human-readable debug output for var_dump/print_r.
     *
     * Hides internal cache state and shows only TTL and class count.
     * Used by var_dump() and debuggers for cleaner output.
     *
     * @return array{ttl: int, cachedClasses: int, timestampCount: int}
     */
    public function __debugInfo(): array
    {
        return [
            'ttl' => $this->ttl,
            'cachedClasses' => count($this->cache),
            'timestampCount' => count($this->cacheTimestamps),
        ];
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
     * Prevent serialization via serialize().
     *
     * PHP 8.1+ uses __serialize()/__unserialize() instead of __sleep()/__wakeup()
     * when they are defined. Since this is a singleton, serialization must be blocked.
     *
     * @throws \RuntimeException Always
     */
    public function __serialize(): never
    {
        throw new \RuntimeException('EnumCache is a singleton and cannot be serialized.');
    }

    /**
     * Prevent unserialization via unserialize().
     *
     * The `$data` parameter is required by the PHP serialization protocol
     * but intentionally unused — this method always throws.
     *
     * @param  array<string, mixed>  $data  Serialized data (ignored)
     *
     * @throws \RuntimeException Always
     */
    public function __unserialize(array $data): never
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
     * When TTL is 0 or less, caching is disabled and this
     * method always returns false.
     *
     * @param  string  $enumClass  The fully-qualified enum class name
     * @return bool True if valid cached metadata exists, false otherwise
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
     * Store cached metadata for an enum class and record the creation timestamp.
     *
     * Overwrites any existing entry for the same class.
     * The TTL check in {@see has()} uses the stored timestamp
     * to determine staleness.
     *
     * @param  array{
     *     labels: array<int|string, string>,
     *     descriptions: array<int|string, string>,
     *     colors: array<int|string, string>,
     *     icons: array<int|string, string>
     * } $metadata  The resolved metadata to cache
     * @return void
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
     * considered stale. Negative values are normalized to 0 via `max()`.
     *
     * @param  int  $ttl  Cache time-to-live in seconds (0 = disabled, negative values clamped to 0)
     * @return void
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
     * Clear all cached metadata entries and their timestamps.
     *
     * @return void
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
     * @return void
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
     * Destroys the cached singleton so the next call to {@see getInstance()}
     * creates a fresh instance with an empty cache. This also clears
     * any custom TTL setting back to the default (300 seconds).
     *
     * **WARNING:** Calling this in production code will break caching
     * for the current process and degrade performance. All enum metadata
     * will need to be re-resolved via reflection on the next access.
     *
     * @internal This method is intended for **test teardown only** (e.g. in `tearDown()` or `afterEach()`).
     *           Never call this in production code or middleware.
     * @see EnumCache::clear() For production-safe single-class cache clearing
     * @see EnumCache::flush() For production-safe full cache clearing
     * @see EnumCache::clearClass() For production-safe per-class cache clearing
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
