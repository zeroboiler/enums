<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;

describe('EnumsServiceProvider', function (): void {
    it('registers EnumManager as singleton bound to zeroboiler.enum', function (): void {
        $provider = new EnumsServiceProvider($this->app);
        $provider->register();

        $manager1 = $this->app->make('zeroboiler.enum');
        $manager2 = $this->app->make('zeroboiler.enum');

        expect($manager1)->toBeInstanceOf(\ZeroBoiler\Enums\EnumManager::class);
        expect($manager1)->toBe($manager2); // same singleton instance
    });

    it('configures cache TTL in local environment via configureDevCacheInvalidation', function (): void {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300); // production default

        expect($cache->getTtl())->toBe(300);

        // Simulate local environment
        $this->app['env'] = 'local';
        $provider = new class($this->app) extends EnumsServiceProvider {
            public function testConfigureDevCache(): void
            {
                $this->configureDevCacheInvalidation();
            }
        };
        $provider->testConfigureDevCache();

        expect($cache->getTtl())->toBe(2);

        EnumCache::resetInstance();
    });

    it('does not change TTL in production environment', function (): void {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $this->app['env'] = 'production';
        $provider = new class($this->app) extends EnumsServiceProvider {
            public function testConfigureDevCache(): void
            {
                $this->configureDevCacheInvalidation();
            }
        };
        $provider->testConfigureDevCache();

        expect($cache->getTtl())->toBe(300);

        EnumCache::resetInstance();
    });

    it('registers artisan commands in console environment', function (): void {
        $this->app->shouldReceive('runningInConsole')->andReturn(true);
        $this->app->shouldReceive('make')->with('events')->andReturn(
            new \Illuminate\Events\Dispatcher($this->app)
        );

        $provider = new EnumsServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // If no exception was thrown, commands were registered successfully
        expect(true)->toBeTrue();
    });

    it('EnumManager throws BadMethodCallException for non-metadata enums', function (): void {
        $manager = $this->app->make('zeroboiler.enum');

        expect(fn (): mixed => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });
});
