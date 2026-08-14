<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\{
    EmptyDefaultsStatus,
    IntBackedPriority,
    MixedTicketType,
    PureFeatureFlag,
    UserStatus,
};

// ============================================================================
// EmptyDefaultsStatus: auto-generation with zero attributes
// ============================================================================

describe('EmptyDefaultsStatus — auto-generated metadata with no attributes', function (): void {
    test('labels are auto-generated from case names', function (): void {
        expect(EmptyDefaultsStatus::DRAFT->label())->toBe('Draft')
            ->and(EmptyDefaultsStatus::PUBLISHED->label())->toBe('Published')
            ->and(EmptyDefaultsStatus::ARCHIVED->label())->toBe('Archived');
    });

    test('color defaults to secondary for all cases', function (): void {
        foreach (EmptyDefaultsStatus::cases() as $case) {
            expect($case->color())->toBe('secondary');
        }
    });

    test('description is null for all cases', function (): void {
        foreach (EmptyDefaultsStatus::cases() as $case) {
            expect($case->description())->toBeNull();
        }
    });

    test('icon is null for all cases', function (): void {
        foreach (EmptyDefaultsStatus::cases() as $case) {
            expect($case->icon())->toBeNull();
        }
    });

    test('forSelect returns correct structure with auto-generated labels', function (): void {
        $select = EmptyDefaultsStatus::forSelect();

        expect($select)->toHaveCount(3)
            ->and($select[0])->toHaveKeys(['value', 'label'])
            ->and($select[0]['value'])->toBe('draft')
            ->and($select[0]['label'])->toBe('Draft');
    });

    test('forApi returns full structure with nulls', function (): void {
        $api = EmptyDefaultsStatus::forApi();

        expect($api)->toHaveCount(3);
        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon'])
                ->and($item['color'])->toBe('secondary')
                ->and($item['description'])->toBeNull()
                ->and($item['icon'])->toBeNull();
        }
    });

    test('values() returns backed values', function (): void {
        $values = EmptyDefaultsStatus::values();

        expect($values)->toBe(['draft', 'published', 'archived']);
    });

    test('labels() returns auto-generated labels', function (): void {
        $labels = EmptyDefaultsStatus::labels();

        expect($labels)->toBe(['Draft', 'Published', 'Archived']);
    });

    test('tryFromLabel works with auto-generated labels', function (): void {
        expect(EmptyDefaultsStatus::tryFromLabel('Draft'))->toBe(EmptyDefaultsStatus::DRAFT)
            ->and(EmptyDefaultsStatus::tryFromLabel('draft'))->toBeNull() // case-sensitive match via strcasecmp
            ->and(EmptyDefaultsStatus::tryFromLabel('DRAFT'))->toBe(EmptyDefaultsStatus::DRAFT); // case-insensitive
    });

    test('is()/isNot()/in()/notIn() work correctly', function (): void {
        $draft = EmptyDefaultsStatus::DRAFT;

        expect($draft->is(EmptyDefaultsStatus::DRAFT))->toBeTrue()
            ->and($draft->is('DRAFT'))->toBeTrue()
            ->and($draft->is(EmptyDefaultsStatus::PUBLISHED))->toBeFalse()
            ->and($draft->isNot('PUBLISHED'))->toBeTrue()
            ->and($draft->in([EmptyDefaultsStatus::DRAFT, EmptyDefaultsStatus::ARCHIVED]))->toBeTrue()
            ->and($draft->notIn([EmptyDefaultsStatus::PUBLISHED]))->toBeTrue();
    });
});

// ============================================================================
// MixedTicketType: class-level + per-case attribute interaction
// ============================================================================

describe('MixedTicketType — mixed class-level and per-case attributes', function (): void {
    test('per-case label overrides class-level', function (): void {
        // CRITICAL_BUG has per-case #[Label('Critical Bug')] overriding class-level 'Bug Report'
        expect(MixedTicketType::CRITICAL_BUG->label())->toBe('Critical Bug');
    });

    test('class-level label used when no per-case override', function (): void {
        // SUPPORT uses class-level label 'Support Ticket'
        expect(MixedTicketType::SUPPORT->label())->toBe('Support Ticket');
    });

    test('per-case description overrides class-level', function (): void {
        expect(MixedTicketType::CRITICAL_BUG->description())->toBe('System-breaking bug — immediate fix required');
    });

    test('class-level description used when no per-case override', function (): void {
        expect(MixedTicketType::SUPPORT->description())->toBe('Get help');
    });

    test('partially overridden description uses class-level when available', function (): void {
        // FEATURE has no per-case description, class-level only has keys 1 and 3
        expect(MixedTicketType::FEATURE->description())->toBeNull();
    });

    test('per-case color overrides class-level default', function (): void {
        expect(MixedTicketType::CRITICAL_BUG->color())->toBe('danger')
            ->and(MixedTicketType::FEATURE->color())->toBe('success');
    });

    test('default color for cases without any color attribute', function (): void {
        expect(MixedTicketType::SUPPORT->color())->toBe('secondary')
            ->and(MixedTicketType::DOCS->color())->toBe('secondary');
    });

    test('per-case icon overrides class-level default and per-value map', function (): void {
        expect(MixedTicketType::CRITICAL_BUG->icon())->toBe('heroicon-o-fire');
    });

    test('class-level per-value icon map used for cases without per-case icon', function (): void {
        expect(MixedTicketType::FEATURE->icon())->toBe('heroicon-o-sparkles');
    });

    test('class-level default icon used for cases without any icon', function (): void {
        expect(MixedTicketType::SUPPORT->icon())->toBe('heroicon-o-question-mark-circle')
            ->and(MixedTicketType::DOCS->icon())->toBe('heroicon-o-question-mark-circle');
    });

    test('forApi returns complete and correct metadata', function (): void {
        $api = MixedTicketType::forApi();

        expect($api)->toHaveCount(4);

        // CRITICAL_BUG — all per-case overrides
        $critical = $api[0];
        expect($critical['value'])->toBe(1)
            ->and($critical['name'])->toBe('CRITICAL_BUG')
            ->and($critical['label'])->toBe('Critical Bug')
            ->and($critical['description'])->toBe('System-breaking bug — immediate fix required')
            ->and($critical['color'])->toBe('danger')
            ->and($critical['icon'])->toBe('heroicon-o-fire');

        // FEATURE — per-value icon map
        $feature = $api[1];
        expect($feature['value'])->toBe(2)
            ->and($feature['label'])->toBe('Feature Request')
            ->and($feature['icon'])->toBe('heroicon-o-sparkles');

        // SUPPORT — class-level defaults
        $support = $api[2];
        expect($support['value'])->toBe(3)
            ->and($support['label'])->toBe('Support Ticket')
            ->and($support['description'])->toBe('Get help')
            ->and($support['icon'])->toBe('heroicon-o-question-mark-circle');

        // DOCS — partially overridden (description only)
        $docs = $api[3];
        expect($docs['value'])->toBe(4)
            ->and($docs['label'])->toBe('Documentation Issue')
            ->and($docs['description'])->toBe('Needs documentation update')
            ->and($docs['icon'])->toBe('heroicon-o-question-mark-circle');
    });

    test('values() returns int backed values', function (): void {
        expect(MixedTicketType::values())->toBe([1, 2, 3, 4]);
    });

    test('tryFromName resolves int-backed enum by case name', function (): void {
        expect(MixedTicketType::tryFromName('CRITICAL_BUG'))->toBe(MixedTicketType::CRITICAL_BUG)
            ->and(MixedTicketType::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    test('fromName throws for invalid case name', function (): void {
        expect(fn () => MixedTicketType::fromName('INVALID'))->toThrow(InvalidEnumException::class);
    });

    test('hasCase returns correct boolean', function (): void {
        expect(MixedTicketType::hasCase('FEATURE'))->toBeTrue()
            ->and(MixedTicketType::hasCase('DOES_NOT_EXIST'))->toBeFalse();
    });
});

// ============================================================================
// EnumManager: delegation with valid and invalid classes
// ============================================================================

describe('EnumManager — delegation and error handling', function (): void {
    test('EnumManager is final readonly', function (): void {
        $ref = new ReflectionClass(EnumManager::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    test('EnumManager throws BadMethodCallException for non-enum class', function (): void {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class, 'does not use HasEnumMetadata trait');
    });

    test('EnumManager throws BadMethodCallException for enum without HasEnumMetadata', function (): void {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(\ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum::class))
            ->toThrow(\BadMethodCallException::class, 'does not use HasEnumMetadata trait');
    });

    test('EnumManager forSelect delegates correctly', function (): void {
        $manager = new EnumManager;
        $result = $manager->forSelect(UserStatus::class);

        expect($result)->toBeArray()
            ->and(count($result))->toBeGreaterThan(0)
            ->and($result[0])->toHaveKeys(['value', 'label']);
    });

    test('EnumManager forApi delegates correctly', function (): void {
        $manager = new EnumManager;
        $result = $manager->forApi(UserStatus::class);

        expect($result)->toBeArray()
            ->and($result[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    test('EnumManager tryFromLabel delegates correctly', function (): void {
        $manager = new EnumManager;

        expect($manager->tryFromLabel(UserStatus::class, 'Active User'))->toBe(UserStatus::ACTIVE)
            ->and($manager->tryFromLabel(UserStatus::class, 'non-existent'))->toBeNull();
    });

    test('EnumManager tryFromName delegates correctly', function (): void {
        $manager = new EnumManager;

        expect($manager->tryFromName(UserStatus::class, 'ACTIVE'))->toBe(UserStatus::ACTIVE)
            ->and($manager->tryFromName(UserStatus::class, 'NON_EXISTENT'))->toBeNull();
    });

    test('EnumManager fromName delegates correctly', function (): void {
        $manager = new EnumManager;

        expect($manager->fromName(UserStatus::class, 'BANNED'))->toBe(UserStatus::BANNED);
    });

    test('EnumManager fromName throws for invalid name', function (): void {
        $manager = new EnumManager;

        expect(fn () => $manager->fromName(UserStatus::class, 'GHOST'))
            ->toThrow(InvalidEnumException::class);
    });

    test('EnumManager hasCase delegates correctly', function (): void {
        $manager = new EnumManager;

        expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue()
            ->and($manager->hasCase(UserStatus::class, 'GHOST'))->toBeFalse();
    });

    test('EnumManager values delegates correctly', function (): void {
        $manager = new EnumManager;
        $values = $manager->values(IntBackedPriority::class);

        expect($values)->toBe([1, 2, 3, 4]);
    });

    test('EnumManager labels delegates correctly', function (): void {
        $manager = new EnumManager;
        $labels = $manager->labels(UserStatus::class);

        expect($labels)->toBeArray()
            ->and(count($labels))->toBe(5);
    });
});

// ============================================================================
// Cache invalidation across enum types
// ============================================================================

describe('EnumCache — invalidation and TTL behavior', function (): void {
    test('cache is shared across calls for same class', function (): void {
        EnumCache::resetInstance();

        $cache = EnumCache::getInstance();
        $cache->set('test-class-a', [
            'labels' => ['x' => 'X'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('test-class-a'))->toBeTrue()
            ->and($cache->get('test-class-a')['labels']['x'])->toBe('X');

        // Clean up
        EnumCache::resetInstance();
    });

    test('TTL expiration invalidates entries', function (): void {
        EnumCache::resetInstance();

        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Immediate expiration
        $cache->set('test-class-b', [
            'labels' => ['y' => 'Y'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('test-class-b'))->toBeFalse();

        // Clean up
        EnumCache::resetInstance();
    });

    test('clearClass removes specific class without affecting others', function (): void {
        EnumCache::resetInstance();

        $cache = EnumCache::getInstance();
        $cache->set('test-class-c', [
            'labels' => ['c' => 'C'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('test-class-d', [
            'labels' => ['d' => 'D'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass('test-class-c');

        expect($cache->has('test-class-c'))->toBeFalse()
            ->and($cache->has('test-class-d'))->toBeTrue();

        // Clean up
        EnumCache::resetInstance();
    });

    test('flush removes all entries', function (): void {
        EnumCache::resetInstance();

        $cache = EnumCache::getInstance();
        $cache->set('test-class-e', [
            'labels' => ['e' => 'E'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::flush();

        expect($cache->has('test-class-e'))->toBeFalse();

        // Clean up
        EnumCache::resetInstance();
    });

    test('negative TTL is clamped to 0', function (): void {
        EnumCache::resetInstance();

        $cache = EnumCache::getInstance();
        $cache->setTtl(-10);

        expect($cache->getTtl())->toBe(0);

        // Clean up
        EnumCache::resetInstance();
    });

    test('EnumMetadataResolver::invalidate removes specific class cache', function (): void {
        EnumCache::resetInstance();

        // Resolve UserStatus to populate cache
        $meta1 = \ZeroBoiler\Enums\Support\EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta1)->not->toBeEmpty();

        // Invalidate
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(UserStatus::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Re-resolve should work
        $meta2 = \ZeroBoiler\Enums\Support\EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta2)->not->toBeEmpty();

        // Clean up
        EnumCache::resetInstance();
    });

    test('EnumMetadataResolver::invalidateAll flushes everything', function (): void {
        EnumCache::resetInstance();

        \ZeroBoiler\Enums\Support\EnumMetadataResolver::resolve(UserStatus::class);
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::resolve(EmptyDefaultsStatus::class);

        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse()
            ->and($cache->has(EmptyDefaultsStatus::class))->toBeFalse();

        // Clean up
        EnumCache::resetInstance();
    });
});

// ============================================================================
// Cross-type consistency: int vs string vs pure enums
// ============================================================================

describe('Cross-type consistency — int, string, and pure enums', function (): void {
    test('all enum types return correct case count', function (): void {
        expect(IntBackedPriority::cases())->toHaveCount(4)
            ->and(UserStatus::cases())->toHaveCount(5)
            ->and(PureFeatureFlag::cases())->toHaveCount(3);
    });

    test('all enum types have non-empty labels', function (): void {
        foreach (IntBackedPriority::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
        foreach (UserStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
        foreach (PureFeatureFlag::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });

    test('values() returns correct types per backing type', function (): void {
        // Int-backed: int values
        foreach (IntBackedPriority::values() as $v) {
            expect($v)->toBeInt();
        }

        // String-backed: string values
        foreach (UserStatus::values() as $v) {
            expect($v)->toBeString();
        }

        // Pure: case names as strings
        foreach (PureFeatureFlag::values() as $v) {
            expect($v)->toBeString();
        }
    });

    test('forSelect returns consistent structure across types', function (): void {
        $intSelect = IntBackedPriority::forSelect();
        $strSelect = UserStatus::forSelect();
        $pureSelect = PureFeatureFlag::forSelect();

        foreach ([$intSelect, $strSelect, $pureSelect] as $select) {
            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        }

        // Int-backed values are int, pure values are string
        expect($intSelect[0]['value'])->toBeInt();
        expect($pureSelect[0]['value'])->toBeString();
    });

    test('forApi returns consistent structure across types', function (): void {
        $intApi = IntBackedPriority::forApi();
        $strApi = UserStatus::forApi();
        $pureApi = PureFeatureFlag::forApi();

        foreach ([$intApi, $strApi, $pureApi] as $api) {
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString();
            }
        }
    });
});
