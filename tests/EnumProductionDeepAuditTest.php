<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

// ---------------------------------------------------------------------------
// Runtime fixtures (no autoloader needed — test files only)
// ---------------------------------------------------------------------------

#[EnumColor(success: ['active', 'verified'], danger: ['banned', 'suspended'], warning: ['pending'])]
#[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
#[EnumDescription(descriptions: ['active' => 'Fully active user', 'banned' => 'Permanently banned'])]
#[EnumIcon(default: 'heroicon-o-user')]
enum RuntimeUserStatus: string
{
    use HasEnumMetadata;

    #[Label('Active User Override')]
    #[Description('Fully active — override')]
    #[Color('success')]
    #[Icon('heroicon-o-check')]
    case ACTIVE = 'active';

    #[Color('danger')]
    case BANNED = 'banned';

    case PENDING = 'pending';

    #[Icon('heroicon-o-shield-check')]
    case VERIFIED = 'verified';

    case SUSPENDED = 'suspended';
}

// Pure enum fixture
#[EnumColor(success: ['on'], danger: ['off'])]
#[EnumIcon(default: 'heroicon-o-bolt')]
enum RuntimeFeatureFlag
{
    use HasEnumMetadata;

    #[Color('success')]
    #[Icon('heroicon-o-check-circle')]
    case ON;

    #[Color('danger')]
    case OFF;
}

// Int-backed fixture
#[EnumColor(info: [0, 1], warning: [2], danger: [3])]
enum RuntimePriority: int
{
    use HasEnumMetadata;

    case LOW = 0;
    case MEDIUM = 1;
    case HIGH = 2;
    case CRITICAL = 3;
}

describe('Enum Metadata Resolution Priority', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('per-case label overrides class-level label', function () {
        // Class-level: active => 'Active User'
        // Per-case: ACTIVE => 'Active User Override'
        expect(RuntimeUserStatus::ACTIVE->label())->toBe('Active User Override');
    });

    it('class-level label is used when per-case override is absent', function () {
        // Class-level: banned => 'Banned User'
        expect(RuntimeUserStatus::BANNED->label())->toBe('Banned User');
    });

    it('auto-generates label when no class-level or per-case label exists', function () {
        // PENDING has no label at any level
        expect(RuntimeUserStatus::PENDING->label())->toBe('Pending');
    });

    it('per-case color overrides class-level color', function () {
        // Class-level: success includes 'active'
        // Per-case: ACTIVE => 'success' (same, but explicit)
        expect(RuntimeUserStatus::ACTIVE->color())->toBe('success');
    });

    it('class-level color is used for non-overridden cases', function () {
        // Class-level: danger includes 'banned'
        expect(RuntimeUserStatus::BANNED->color())->toBe('danger');
        expect(RuntimeUserStatus::SUSPENDED->color())->toBe('danger');
    });

    it('class-level color warning group works', function () {
        expect(RuntimeUserStatus::PENDING->color())->toBe('warning');
    });

    it('per-case description overrides class-level description', function () {
        expect(RuntimeUserStatus::ACTIVE->description())->toBe('Fully active — override');
    });

    it('class-level description is used when no per-case override', function () {
        expect(RuntimeUserStatus::BANNED->description())->toBe('Permanently banned');
    });

    it('returns null description when none defined at any level', function () {
        expect(RuntimeUserStatus::PENDING->description())->toBeNull();
    });

    it('per-case icon overrides class-level default icon', function () {
        expect(RuntimeUserStatus::ACTIVE->icon())->toBe('heroicon-o-check');
        expect(RuntimeUserStatus::VERIFIED->icon())->toBe('heroicon-o-shield-check');
    });

    it('class-level default icon is used when no per-case override', function () {
        expect(RuntimeUserStatus::BANNED->icon())->toBe('heroicon-o-user');
        expect(RuntimeUserStatus::PENDING->icon())->toBe('heroicon-o-user');
    });

    it('pure enum uses case name as lookup key', function () {
        expect(RuntimeFeatureFlag::ON->label())->toBe('On');
        expect(RuntimeFeatureFlag::OFF->label())->toBe('Off');
    });

    it('int-backed enum uses backed value as lookup key', function () {
        // Class-level EnumColor maps int values to colors
        expect(RuntimePriority::LOW->color())->toBe('info');
        expect(RuntimePriority::HIGH->color())->toBe('warning');
        expect(RuntimePriority::CRITICAL->color())->toBe('danger');
    });

    it('int-backed enum auto-generates label from case name', function () {
        expect(RuntimePriority::LOW->label())->toBe('Low');
        expect(RuntimePriority::CRITICAL->label())->toBe('Critical');
    });
});

describe('Enum Cache Lifecycle', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('caches metadata after first resolve', function () {
        $cache = EnumCache::getInstance();

        expect($cache->has(RuntimeUserStatus::class))->toBeFalse();

        EnumMetadataResolver::resolve(RuntimeUserStatus::class);

        expect($cache->has(RuntimeUserStatus::class))->toBeTrue();
    });

    it('clear removes specific class cache', function () {
        EnumMetadataResolver::resolve(RuntimeUserStatus::class);
        EnumMetadataResolver::resolve(RuntimePriority::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(RuntimeUserStatus::class))->toBeTrue();
        expect($cache->has(RuntimePriority::class))->toBeTrue();

        $cache->clearClass(RuntimeUserStatus::class);

        expect($cache->has(RuntimeUserStatus::class))->toBeFalse();
        expect($cache->has(RuntimePriority::class))->toBeTrue();
    });

    it('clear removes all cached entries', function () {
        EnumMetadataResolver::resolve(RuntimeUserStatus::class);
        EnumMetadataResolver::resolve(RuntimePriority::class);

        $cache = EnumCache::getInstance();
        $cache->clear();

        expect($cache->has(RuntimeUserStatus::class))->toBeFalse();
        expect($cache->has(RuntimePriority::class))->toBeFalse();
    });

    it('flush static method delegates to singleton clear', function () {
        EnumMetadataResolver::resolve(RuntimeUserStatus::class);

        EnumCache::flush();

        $cache = EnumCache::getInstance();
        expect($cache->has(RuntimeUserStatus::class))->toBeFalse();
    });

    it('TTL of 0 disables caching', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        EnumMetadataResolver::resolve(RuntimeUserStatus::class);

        expect($cache->has(RuntimeUserStatus::class))->toBeFalse();
    });

    it('negative TTL is normalized to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        EnumMetadataResolver::resolve(RuntimeUserStatus::class);

        expect($cache->has(RuntimeUserStatus::class))->toBeFalse();
    });

    it('stale cache entry is auto-expired', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);

        EnumMetadataResolver::resolve(RuntimeUserStatus::class);
        expect($cache->has(RuntimeUserStatus::class))->toBeTrue();

        // Wait for TTL to expire
        sleep(2);

        expect($cache->has(RuntimeUserStatus::class))->toBeFalse();
    });

    it('get throws OutOfBoundsException for non-existent class', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))
            ->toThrow(OutOfBoundsException::class);
    });

    it('resetInstance creates fresh singleton', function () {
        $first = EnumCache::getInstance();
        EnumCache::resetInstance();
        $second = EnumCache::getInstance();

        // Both should be functional but are different instances
        expect($first)->not->toBe($second);
    });

    it('cached metadata shape contains all expected keys', function () {
        $cache = EnumCache::getInstance();
        EnumMetadataResolver::resolve(RuntimeUserStatus::class);
        $meta = $cache->get(RuntimeUserStatus::class);

        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        expect($meta['labels'])->toBeArray();
        expect($meta['descriptions'])->toBeArray();
        expect($meta['colors'])->toBeArray();
        expect($meta['icons'])->toBeArray();
    });
});

describe('Enum Comparison Edge Cases', function () {
    it('is() with same instance returns true', function () {
        $case = RuntimeUserStatus::ACTIVE;
        expect($case->is($case))->toBeTrue();
    });

    it('is() with different instance returns false', function () {
        expect(RuntimeUserStatus::ACTIVE->is(RuntimeUserStatus::BANNED))->toBeFalse();
    });

    it('is() with string name returns true for matching name', function () {
        expect(RuntimeUserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
    });

    it('is() with string name returns false for non-matching name', function () {
        expect(RuntimeUserStatus::ACTIVE->is('BANNED'))->toBeFalse();
    });

    it('isNot() is exact negation of is()', function () {
        $case = RuntimeUserStatus::ACTIVE;

        // Same instance
        expect($case->isNot($case))->toBeFalse();
        expect($case->isNot('ACTIVE'))->toBeFalse();

        // Different instance
        expect($case->isNot(RuntimeUserStatus::BANNED))->toBeTrue();
        expect($case->isNot('BANNED'))->toBeTrue();
    });

    it('in() matches at least one case in array', function () {
        expect(RuntimeUserStatus::ACTIVE->in([RuntimeUserStatus::BANNED, RuntimeUserStatus::ACTIVE]))->toBeTrue();
    });

    it('in() returns false when no matches', function () {
        expect(RuntimeUserStatus::ACTIVE->in([RuntimeUserStatus::BANNED, RuntimeUserStatus::PENDING]))->toBeFalse();
    });

    it('in() works with string names', function () {
        expect(RuntimeUserStatus::ACTIVE->in(['BANNED', 'ACTIVE']))->toBeTrue();
        expect(RuntimeUserStatus::ACTIVE->in(['BANNED', 'PENDING']))->toBeFalse();
    });

    it('in() with empty array returns false', function () {
        expect(RuntimeUserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('hasCase returns true for existing case', function () {
        expect(RuntimeUserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(RuntimeUserStatus::hasCase('BANNED'))->toBeTrue();
    });

    it('hasCase returns false for non-existing case', function () {
        expect(RuntimeUserStatus::hasCase('NON_EXISTENT'))->toBeFalse();
        expect(RuntimeUserStatus::hasCase(''))->toBeFalse();
    });
});

describe('Enum Lookup Methods', function () {
    it('tryFromName returns case for valid name', function () {
        expect(RuntimeUserStatus::tryFromName('ACTIVE'))->toBe(RuntimeUserStatus::ACTIVE);
    });

    it('tryFromName returns null for invalid name', function () {
        expect(RuntimeUserStatus::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('fromName throws InvalidEnumException for invalid name', function () {
        expect(fn () => RuntimeUserStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class, 'NON_EXISTENT');
    });

    it('fromName exception includes class name', function () {
        try {
            RuntimeUserStatus::fromName('INVALID');
            expect(true)->toBeFalse('Should have thrown');
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain(RuntimeUserStatus::class);
            expect($e->getMessage())->toContain('INVALID');
        }
    });

    it('tryFromLabel is case-insensitive', function () {
        $label = RuntimeUserStatus::ACTIVE->label();

        expect(RuntimeUserStatus::tryFromLabel($label))->toBe(RuntimeUserStatus::ACTIVE);
        expect(RuntimeUserStatus::tryFromLabel(strtolower($label)))->toBe(RuntimeUserStatus::ACTIVE);
        expect(RuntimeUserStatus::tryFromLabel(strtoupper($label)))->toBe(RuntimeUserStatus::ACTIVE);
    });

    it('tryFromLabel returns null for non-matching label', function () {
        expect(RuntimeUserStatus::tryFromLabel('Non Existent Label'))->toBeNull();
    });
});

describe('Enum Bulk Methods', function () {
    it('forSelect returns correct structure', function () {
        $options = RuntimeUserStatus::forSelect();

        expect($options)->toBeArray();
        expect($options)->toHaveCount(5);

        foreach ($options as $option) {
            expect($option)->toHaveKeys(['value', 'label']);
            expect($option['value'])->toBeString();
            expect($option['label'])->toBeString();
        }
    });

    it('forSelect values match backed values', function () {
        $options = RuntimeUserStatus::forSelect();
        $values = array_column($options, 'value');

        expect($values)->toContain('active');
        expect($values)->toContain('banned');
        expect($values)->toContain('pending');
        expect($values)->toContain('verified');
        expect($values)->toContain('suspended');
    });

    it('forSelect values are unique', function () {
        $options = RuntimeUserStatus::forSelect();
        $values = array_column($options, 'value');

        expect($values)->toEqual(array_unique($values));
    });

    it('forApi returns correct structure', function () {
        $api = RuntimeUserStatus::forApi();

        expect($api)->toBeArray();
        expect($api)->toHaveCount(5);

        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['value'])->toBeString();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString();
            expect($item['color'])->toBeString();
        }
    });

    it('values() returns all backed values', function () {
        $values = RuntimeUserStatus::values();

        expect($values)->toEqual(['active', 'banned', 'pending', 'verified', 'suspended']);
    });

    it('values() for pure enums returns case names', function () {
        $values = RuntimeFeatureFlag::values();

        expect($values)->toEqual(['ON', 'OFF']);
    });

    it('values() for int-backed enums returns int values', function () {
        $values = RuntimePriority::values();

        expect($values)->toEqual([0, 1, 2, 3]);
    });

    it('labels() returns labels for all cases', function () {
        $labels = RuntimeUserStatus::labels();

        expect($labels)->toBeArray();
        expect($labels)->toHaveCount(5);

        foreach ($labels as $label) {
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        }
    });

    it('forApi includes description and icon as nullable', function () {
        $api = RuntimeUserStatus::forApi();

        // ACTIVE has per-case description and icon
        $active = $api[0];
        expect($active['description'])->not->toBeNull();
        expect($active['icon'])->not->toBeNull();

        // PENDING has no description/icon
        $pending = array_first($api, fn (array $item): bool => $item['name'] === 'PENDING');
        expect($pending['description'])->toBeNull();
    });
});

describe('Enum Exception Factory Methods', function () {
    it('InvalidEnumException::value formats null value', function () {
        $e = InvalidEnumException::value('TestEnum', null);

        expect($e->getMessage())->toContain('null');
        expect($e->getMessage())->toContain('TestEnum');
    });

    it('InvalidEnumException::value formats string value', function () {
        $e = InvalidEnumException::value('TestEnum', 'invalid_value');

        expect($e->getMessage())->toContain('invalid_value');
        expect($e->getMessage())->toContain('TestEnum');
    });

    it('InvalidEnumException::value formats int value', function () {
        $e = InvalidEnumException::value('TestEnum', 42);

        expect($e->getMessage())->toContain('42');
        expect($e->getMessage())->toContain('TestEnum');
    });

    it('InvalidEnumException::forName includes class and name', function () {
        $e = InvalidEnumException::forName('TestEnum', 'BAD_CASE');

        expect($e->getMessage())->toContain('TestEnum');
        expect($e->getMessage())->toContain('BAD_CASE');
    });
});

describe('Enum Cache Set/Get Roundtrip', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('set and get returns same metadata', function () {
        $cache = EnumCache::getInstance();
        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => ['active' => 'Active user'],
            'colors' => ['active' => 'success'],
            'icons' => ['active' => 'check'],
        ];

        $cache->set('TestEnum', $metadata);

        expect($cache->get('TestEnum'))->toBe($metadata);
    });

    it('set overwrites previous cache', function () {
        $cache = EnumCache::getInstance();

        $cache->set('TestEnum', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $newMeta = [
            'labels' => ['x' => 'X'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];

        $cache->set('TestEnum', $newMeta);

        expect($cache->get('TestEnum'))->toBe($newMeta);
    });
});
