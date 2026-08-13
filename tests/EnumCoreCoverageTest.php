<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache singleton lifecycle', function (): void {
    it('returns same instance on multiple calls', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('resetInstance creates a fresh singleton', function (): void {
        $original = EnumCache::getInstance();
        EnumCache::resetInstance();
        $fresh = EnumCache::getInstance();

        expect($fresh)->not->toBe($original);

        // Restore
        EnumCache::resetInstance();
    });
});

describe('EnumCache clearClass', function (): void {
    it('clears metadata for a specific class without affecting others', function (): void {
        $cache = EnumCache::getInstance();

        // Ensure both classes are cached
        UserStatus::ACTIVE->label();
        Priority::LOW->label();

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        // Clear only UserStatus
        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });
});

describe('EnumCache TTL zero disables caching', function (): void {
    it('returns false from has() when TTL is 0', function (): void {
        $cache = EnumCache::getInstance();
        $originalTtl = $cache->getTtl();

        // Populate the cache first
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();

        // Set TTL to 0 — caching disabled
        $cache->setTtl(0);

        expect($cache->has(UserStatus::class))->toBeFalse();

        // Restore
        $cache->setTtl($originalTtl);
        $cache->clearClass(UserStatus::class);
    });

    it('normalizes negative TTL to 0', function (): void {
        $cache = EnumCache::getInstance();
        $originalTtl = $cache->getTtl();

        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);

        $cache->setTtl($originalTtl);
    });
});

describe('EnumCache flush clears all', function (): void {
    it('flush removes all entries via static method', function (): void {
        $cache = EnumCache::getInstance();

        // Populate cache
        UserStatus::ACTIVE->label();
        Priority::LOW->label();

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(Priority::class))->toBeTrue();

        EnumCache::flush();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
    });
});

describe('EnumCache get throws on missing entry', function (): void {
    it('throws OutOfBoundsException when no cached entry exists', function (): void {
        $cache = EnumCache::getInstance();
        $cache->clearClass('NonExistingEnum');

        expect(fn () => $cache->get('NonExistingEnum'))
            ->toThrow(\OutOfBoundsException::class);
    });
});

describe('EnumCache set and round-trip', function (): void {
    it('stores and retrieves metadata correctly', function (): void {
        $cache = EnumCache::getInstance();
        $originalTtl = $cache->getTtl();

        // Disable TTL for this test
        $cache->setTtl(0);
        $cache->clearClass(__CLASS__);

        $meta = [
            'labels' => [1 => 'One', 2 => 'Two'],
            'descriptions' => [1 => 'First'],
            'colors' => [1 => 'success', 2 => 'danger'],
            'icons' => [1 => 'check', 2 => 'x'],
        ];

        $cache->set(__CLASS__, $meta);
        $retrieved = $cache->get(__CLASS__);

        expect($retrieved)->toBe($meta);

        // Cleanup
        $cache->clearClass(__CLASS__);
        $cache->setTtl($originalTtl);
    });
});

describe('EnumMetadataResolver invalidate', function (): void {
    it('invalidates a specific class and forces rebuild', function (): void {
        // Populate cache
        $label1 = UserStatus::ACTIVE->label();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        // Invalidate
        EnumMetadataResolver::invalidate(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();

        // Re-resolve should rebuild cache
        $label2 = UserStatus::ACTIVE->label();
        expect($label2)->toBe($label1);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
    });

    it('invalidateAll clears everything', function (): void {
        UserStatus::ACTIVE->label();
        Priority::LOW->label();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
    });
});

describe('InvalidEnumException named constructors', function (): void {
    it('creates value exception with null value', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, null);

        expect($e->getMessage())->toContain('null');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('creates value exception with int value', function (): void {
        $e = InvalidEnumException::value(Priority::class, 99);

        expect($e->getMessage())->toContain('99');
        expect($e->getMessage())->toContain(Priority::class);
    });

    it('creates value exception with string value', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, 'nonexistent');

        expect($e->getMessage())->toContain('nonexistent');
    });

    it('creates forName exception', function (): void {
        $e = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

        expect($e->getMessage())->toContain('NONEXISTENT');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('__toString returns class and message', function (): void {
        $e = InvalidEnumException::value('TestEnum', 'bad');

        $str = (string) $e;

        expect($str)->toBe(InvalidEnumException::class.': Value [bad] is not a valid case of [TestEnum].');
    });
});

describe('Int-backed enum with class-level attributes (SystemStatus)', function (): void {
    it('resolves labels from class-level EnumLabel', function (): void {
        expect(SystemStatus::ENABLED->label())->toBe('Enabled');
        expect(SystemStatus::DISABLED->label())->toBe('Disabled');
        expect(SystemStatus::MAINTENANCE->label())->toBe('Maintenance');
    });

    it('resolves colors from class-level EnumColor', function (): void {
        expect(SystemStatus::ENABLED->color())->toBe('success');
        expect(SystemStatus::DISABLED->color())->toBe('danger');
        expect(SystemStatus::MAINTENANCE->color())->toBe('warning');
    });

    it('resolves icons from class-level EnumIcon with per-value overrides', function (): void {
        expect(SystemStatus::ENABLED->icon())->toBe('heroicon-o-check');
        expect(SystemStatus::DISABLED->icon())->toBe('heroicon-o-x-mark');
    });

    it('falls back to default icon for cases without specific icon', function (): void {
        expect(SystemStatus::MAINTENANCE->icon())->toBe('heroicon-o-cog-6-tooth');
    });

    it('resolves descriptions from class-level EnumDescription', function (): void {
        expect(SystemStatus::ENABLED->description())->toBe('System is fully operational');
        expect(SystemStatus::DISABLED->description())->toBe('System is offline');
        expect(SystemStatus::MAINTENANCE->description())->toBe('Undergoing scheduled maintenance');
    });

    it('forSelect uses int values', function (): void {
        $options = SystemStatus::forSelect();

        expect($options)->toHaveCount(3);
        expect($options[0]['value'])->toBe(0);
        expect($options[1]['value'])->toBe(1);
        expect($options[0]['label'])->toBe('Disabled');
    });

    it('forApi returns int values with full metadata', function (): void {
        $api = SystemStatus::forApi();

        expect($api)->toHaveCount(3);
        expect($api[1])->toHaveKey('value');
        expect($api[1])->toHaveKey('icon');
        expect($api[1])->toHaveKey('description');
    });
});

describe('Pure enum metadata resolution (PureFeatureFlag)', function (): void {
    it('uses case names as metadata keys for pure enums', function (): void {
        expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
        expect(PureFeatureFlag::BETA_FEATURES->label())->toBe('Beta Features');
    });

    it('auto-generates label for cases without attributes', function (): void {
        expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
    });

    it('forSelect uses case names as values', function (): void {
        $options = PureFeatureFlag::forSelect();

        expect($options[0]['value'])->toBe('DARK_MODE');
        expect($options[0]['label'])->toBe('Dark Mode');
    });

    it('values() returns case names for pure enum', function (): void {
        $values = PureFeatureFlag::values();

        expect($values)->toBe(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
    });

    it('tryFromName works with case names', function (): void {
        expect(PureFeatureFlag::tryFromName('DARK_MODE'))->toBe(PureFeatureFlag::DARK_MODE);
        expect(PureFeatureFlag::tryFromName('nonexistent'))->toBeNull();
    });

    it('hasCase checks existence correctly', function (): void {
        expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::hasCase('INVALID'))->toBeFalse();
    });

    it('fromName throws for invalid case name', function (): void {
        expect(fn () => PureFeatureFlag::fromName('INVALID'))
            ->toThrow(InvalidEnumException::class);
    });

    it('is() and isNot() work with case names', function (): void {
        expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->is(PureFeatureFlag::DARK_MODE))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->isNot('BETA_FEATURES'))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->isNot(PureFeatureFlag::DARK_MODE))->toBeFalse();
    });

    it('in() and notIn() work with mixed types', function (): void {
        expect(PureFeatureFlag::DARK_MODE->in([PureFeatureFlag::DARK_MODE, 'BETA_FEATURES']))->toBeTrue();
        expect(PureFeatureFlag::DARK_MODE->in(['BETA_FEATURES']))->toBeFalse();
        expect(PureFeatureFlag::DARK_MODE->notIn(['BETA_FEATURES', 'MAINTENANCE_MODE']))->toBeTrue();
    });
});

describe('HasEnumMetadata trait edge cases', function (): void {
    it('tryFromLabel is case-insensitive', function (): void {
        expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
    });

    it('labels() preserves declaration order', function (): void {
        $labels = UserStatus::labels();

        expect($labels)->toHaveCount(5);
        expect($labels[0])->toBe('Active User');
        expect($labels[4])->toBe('Banned');
    });

    it('values() preserves declaration order', function (): void {
        $values = UserStatus::values();

        expect($values)->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);
    });
});
