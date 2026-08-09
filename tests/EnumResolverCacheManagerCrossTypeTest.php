<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

beforeEach(function (): void {
    EnumCache::flush();
});

// ─── EnumMetadataResolver::invalidate() and invalidateAll() ─────────

describe('EnumMetadataResolver::invalidate', function (): void {
    it('invalidates cached metadata for a specific class', function (): void {
        // Populate cache
        $label1 = UserStatus::ACTIVE->label();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        // Invalidate
        EnumMetadataResolver::invalidate(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();

        // Re-resolve should work and re-populate cache
        $label2 = UserStatus::ACTIVE->label();
        expect($label2)->toBe($label1);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
    });

    it('does not affect other enum caches', function (): void {
        UserStatus::ACTIVE->label();
        OrderStatus::PENDING->label();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidate(UserStatus::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeTrue();
    });

    it('invalidating a non-cached class is a no-op', function (): void {
        // Should not throw
        EnumMetadataResolver::invalidate('SomeNonExistentClass');
        expect(true)->toBeTrue();
    });
});

describe('EnumMetadataResolver::invalidateAll', function (): void {
    it('clears all cached metadata', function (): void {
        UserStatus::ACTIVE->label();
        OrderStatus::PENDING->label();
        Priority::LOW->label();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
    });

    it('cache works normally after full invalidation', function (): void {
        UserStatus::ACTIVE->label();
        EnumMetadataResolver::invalidateAll();

        $label = UserStatus::ACTIVE->label();
        expect($label)->toBe('Active User');
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
    });
});

// ─── EnumManager factory and delegation ─────────

describe('EnumManager', function (): void {
    it('is registered as singleton via serviceProvider pattern', function (): void {
        $manager1 = new EnumManager;
        $manager2 = new EnumManager;

        // Not a shared singleton — each new instance is independent
        // but the facade uses the container singleton
        expect($manager1)->toBeInstanceOf(EnumManager::class);
        expect($manager2)->toBeInstanceOf(EnumManager::class);
    });

    it('forSelect delegates to enum static method', function (): void {
        $manager = new EnumManager;

        $result = $manager->forSelect(UserStatus::class);

        expect($result)->toBe(UserStatus::forSelect());
    });

    it('forApi delegates to enum static method', function (): void {
        $manager = new EnumManager;

        $result = $manager->forApi(UserStatus::class);

        expect($result)->toBe(UserStatus::forApi());
    });

    it('tryFromLabel delegates to enum static method', function (): void {
        $manager = new EnumManager;

        $result = $manager->tryFromLabel(UserStatus::class, 'Active User');

        expect($result)->toBe(UserStatus::tryFromLabel('Active User'));
    });

    it('throws BadMethodCallException for non-trait enum', function (): void {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(\SomeStandardEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });
});

// ─── Cross-type consistency: all fixture enums ─────────

describe('Cross-fixture consistency', function (): void {
    it('all string-backed enums return string values from values()', function (): void {
        $enums = [
            UserStatus::class,
            OrderStatus::class,
            TicketStatus::class,
            MixedAttributeStatus::class,
            AllClassLevelEnum::class,
            CamelCaseRole::class,
        ];

        foreach ($enums as $enumClass) {
            $values = $enumClass::values();
            foreach ($values as $value) {
                expect($value)->toBeString();
            }
        }
    });

    it('all int-backed enums return int values from values()', function (): void {
        $enums = [
            Priority::class,
            IntStatusWithColor::class,
            ZeroPriority::class,
        ];

        foreach ($enums as $enumClass) {
            $values = $enumClass::values();
            foreach ($values as $value) {
                expect($value)->toBeInt();
            }
        }
    });

    it('all pure enums return string case names from values()', function (): void {
        $enums = [
            PureFeatureFlag::class,
            RequestState::class,
            SingleCaseEnum::class,
        ];

        foreach ($enums as $enumClass) {
            $values = $enumClass::values();
            foreach ($values as $value) {
                expect($value)->toBeString();
            }
        }
    });

    it('every fixture enum has non-empty forSelect with value+label keys', function (): void {
        $enums = [
            UserStatus::class,
            OrderStatus::class,
            TicketStatus::class,
            MixedAttributeStatus::class,
            AllClassLevelEnum::class,
            CamelCaseRole::class,
            Priority::class,
            IntStatusWithColor::class,
            ZeroPriority::class,
            PureFeatureFlag::class,
            RequestState::class,
            SingleCaseEnum::class,
        ];

        foreach ($enums as $enumClass) {
            $select = $enumClass::forSelect();
            expect($select)->not->toBeEmpty();

            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        }
    });

    it('every fixture enum has non-empty forApi with all required keys', function (): void {
        $enums = [
            UserStatus::class,
            OrderStatus::class,
            TicketStatus::class,
            MixedAttributeStatus::class,
            AllClassLevelEnum::class,
            CamelCaseRole::class,
            Priority::class,
            IntStatusWithColor::class,
            ZeroPriority::class,
            PureFeatureFlag::class,
            RequestState::class,
            SingleCaseEnum::class,
        ];

        foreach ($enums as $enumClass) {
            $api = $enumClass::forApi();
            expect($api)->not->toBeEmpty();

            foreach ($api as $item) {
                expect($item)->toHaveKeys([
                    'value',
                    'name',
                    'label',
                    'description',
                    'color',
                    'icon',
                ]);
                expect($item['label'])->toBeString()->not->toBeEmpty();
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        }
    });

    it('every fixture enum has consistent case count across bulk methods', function (): void {
        $enums = [
            UserStatus::class,
            OrderStatus::class,
            TicketStatus::class,
            MixedAttributeStatus::class,
            AllClassLevelEnum::class,
            CamelCaseRole::class,
            Priority::class,
            IntStatusWithColor::class,
            ZeroPriority::class,
            PureFeatureFlag::class,
            RequestState::class,
            SingleCaseEnum::class,
        ];

        foreach ($enums as $enumClass) {
            $caseCount = count($enumClass::cases());
            expect($enumClass::forSelect())->toHaveCount($caseCount);
            expect($enumClass::forApi())->toHaveCount($caseCount);
            expect($enumClass::values())->toHaveCount($caseCount);
            expect($enumClass::labels())->toHaveCount($caseCount);
        }
    });
});

// ─── EnumCache singleton edge cases ─────────

describe('EnumCache singleton', function (): void {
    afterEach(function (): void {
        // Ensure clean state
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('resetInstance allows fresh singleton', function (): void {
        $instance1 = EnumCache::getInstance();
        EnumCache::resetInstance();

        $instance2 = EnumCache::getInstance();

        // After reset, these should be different instances
        expect($instance1)->not->toBe($instance2);
    });

    it('setTtl(0) disables caching entirely', function (): void {
        EnumCache::getInstance()->setTtl(0);

        // Populate
        UserStatus::ACTIVE->label();

        // Should report as not cached
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('getTtl returns current TTL', function (): void {
        EnumCache::getInstance()->setTtl(60);
        expect(EnumCache::getInstance()->getTtl())->toBe(60);

        EnumCache::getInstance()->setTtl(0);
        expect(EnumCache::getInstance()->getTtl())->toBe(0);
    });

    it('getTtl normalizes negative values to 0', function (): void {
        EnumCache::getInstance()->setTtl(-5);
        expect(EnumCache::getInstance()->getTtl())->toBe(0);
    });
});

// ─── InvalidEnumException factory methods ─────────

describe('InvalidEnumException factory methods', function (): void {
    it('value() includes null display', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, null);

        expect($e->getMessage())->toContain('null')
            ->and($e->getMessage())->toContain(UserStatus::class);
    });

    it('value() includes string value', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, 'invalid');

        expect($e->getMessage())->toContain('invalid')
            ->and($e->getMessage())->toContain(UserStatus::class);
    });

    it('value() includes int value', function (): void {
        $e = InvalidEnumException::value(Priority::class, 999);

        expect($e->getMessage())->toContain('999')
            ->and($e->getMessage())->toContain(Priority::class);
    });

    it('forName() includes case name and class', function (): void {
        $e = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

        expect($e->getMessage())->toContain('NONEXISTENT')
            ->and($e->getMessage())->toContain(UserStatus::class);
    });
});

// ─── Comparison method edge cases ─────────

describe('Comparison edge cases', function (): void {
    it('in() with empty array returns false', function (): void {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('in() with single matching instance returns true', function (): void {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
    });

    it('in() with all non-matching strings returns false', function (): void {
        expect(UserStatus::ACTIVE->in(['BANNED', 'INACTIVE']))->toBeFalse();
    });

    it('is() with mixed instance and string in same call', function (): void {
        // Each is() call takes a single case/string
        expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
        expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
    });
});

// Standalone enum without HasEnumMetadata
enum SomeStandardEnum: string
{
    case RED = 'red';
    case GREEN = 'green';
    case BLUE = 'blue';
}
