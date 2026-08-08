<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum cache isolation and resolver edge cases', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    describe('Cache isolation between enum classes', function () {
        it('resolving one enum does not pollute another enum cache', function () {
            // Resolve UserStatus — fills cache for UserStatus only
            $userMeta = EnumMetadataResolver::resolve(UserStatus::class);
            expect($userMeta)->toBeArray();
            expect($userMeta)->toHaveKey('labels');

            // Resolve Priority — fills cache for Priority only
            $priorityMeta = EnumMetadataResolver::resolve(Priority::class);
            expect($priorityMeta)->toBeArray();
            expect($priorityMeta)->toHaveKey('labels');

            // UserStatus cache should still be intact and independent
            $cache = EnumCache::getInstance();
            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has(Priority::class))->toBeTrue();

            // Invalidate only UserStatus — Priority should remain
            EnumMetadataResolver::invalidate(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeTrue();

            // Resolve UserStatus again — should rebuild from scratch
            $rebuiltMeta = EnumMetadataResolver::resolve(UserStatus::class);
            expect($rebuiltMeta['labels']['active'])->toBe('Active User');
        });

        it('cache entries store the correct metadata shape per enum', function () {
            EnumMetadataResolver::resolve(MixedAttributeStatus::class);

            $cache = EnumCache::getInstance();
            $meta = $cache->get(MixedAttributeStatus::class);

            // Verify the metadata shape has all four keys
            expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);

            // Class-level EnumLabel should be resolved
            expect($meta['labels']['new'])->toBe('Brand New Item');
            expect($meta['labels']['used'])->toBe('Previously Owned');

            // Class-level EnumColor should be resolved
            expect($meta['colors']['active'])->toBe('success');
            expect($meta['colors']['archived'])->toBe('danger');

            // Class-level EnumIcon default should be set for all cases
            expect($meta['icons'])->not->toBeEmpty();

            // Class-level EnumDescription should be resolved
            expect($meta['descriptions']['active'])->toBe('Currently active');
        });

        it('pure enum and backed enum caches coexist without interference', function () {
            // Pure enum
            EnumMetadataResolver::resolve(RequestState::class);
            // String-backed enum
            EnumMetadataResolver::resolve(UserStatus::class);
            // Int-backed enum
            EnumMetadataResolver::resolve(Priority::class);

            $cache = EnumCache::getInstance();

            // All three should be cached
            expect($cache->has(RequestState::class))->toBeTrue();
            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has(Priority::class))->toBeTrue();

            // Verify values are distinct per enum type
            $pureMeta = $cache->get(RequestState::class);
            $stringMeta = $cache->get(UserStatus::class);
            $intMeta = $cache->get(Priority::class);

            // Pure enum labels should be auto-generated from case names
            expect($pureMeta['labels']['DRAFT'])->toBe('Draft');

            // String-backed enum should have custom label
            expect($stringMeta['labels']['active'])->toBe('Active User');

            // Int-backed enum should have auto-generated label
            expect($intMeta['labels'][1])->toBe('Low');
        });

        it('invalidateAll clears all caches simultaneously', function () {
            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(Priority::class);
            EnumMetadataResolver::resolve(RequestState::class);
            EnumMetadataResolver::resolve(MixedAttributeStatus::class);

            $cache = EnumCache::getInstance();
            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has(Priority::class))->toBeTrue();
            expect($cache->has(RequestState::class))->toBeTrue();
            expect($cache->has(MixedAttributeStatus::class))->toBeTrue();

            EnumMetadataResolver::invalidateAll();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeFalse();
            expect($cache->has(RequestState::class))->toBeFalse();
            expect($cache->has(MixedAttributeStatus::class))->toBeFalse();
        });
    });

    describe('Int-backed enum metadata resolution', function () {
        it('IntStatusWithColor resolves per-case Color override over EnumColor', function () {
            // BANNED=3 has #[Color('danger')] per-case override
            expect(IntStatusWithColor::BANNED->color())->toBe('danger');

            // ACTIVE=1 gets 'success' from class-level EnumColor
            expect(IntStatusWithColor::ACTIVE->color())->toBe('success');

            // PENDING=2 gets 'warning' from class-level EnumColor
            expect(IntStatusWithColor::PENDING->color())->toBe('warning');

            // DRAFT=4 gets 'success' from class-level EnumColor
            expect(IntStatusWithColor::DRAFT->color())->toBe('success');
        });

        it('IntStatusWithColor forSelect returns int values', function () {
            $select = IntStatusWithColor::forSelect();
            expect($select)->toHaveCount(4);

            foreach ($select as $option) {
                expect(is_int($option['value']))->toBeTrue();
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('IntStatusWithColor forApi returns int values with all metadata keys', function () {
            $api = IntStatusWithColor::forApi();
            expect($api)->toHaveCount(4);

            foreach ($api as $item) {
                expect(is_int($item['value']))->toBeTrue();
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            }
        });

        it('IntStatusWithColor values returns all int values', function () {
            $values = IntStatusWithColor::values();
            expect($values)->toEqual([1, 2, 3, 4]);
        });
    });

    describe('Pure enum metadata resolution', function () {
        it('PureFeatureFlag resolves per-case Icon attribute', function () {
            expect(PureFeatureFlag::TWO_FACTOR_AUTH->icon())->toBe('heroicon-o-shield-check');
            expect(PureFeatureFlag::DARK_MODE->icon())->toBeNull();
            expect(PureFeatureFlag::BETA_ACCESS->icon())->toBeNull();
        });

        it('PureFeatureFlag uses case names as values', function () {
            $values = PureFeatureFlag::values();
            expect($values)->toEqual(['TWO_FACTOR_AUTH', 'DARK_MODE', 'BETA_ACCESS']);
        });

        it('PureFeatureFlag forSelect uses case names', function () {
            $select = PureFeatureFlag::forSelect();
            expect($select)->toHaveCount(3);
            expect($select[0]['value'])->toBe('TWO_FACTOR_AUTH');
            expect($select[0]['label'])->toBe('Two Factor Auth');
        });

        it('PureFeatureFlag comparison methods work with case names', function () {
            expect(PureFeatureFlag::TWO_FACTOR_AUTH->is('TWO_FACTOR_AUTH'))->toBeTrue();
            expect(PureFeatureFlag::TWO_FACTOR_AUTH->is('two_factor_auth'))->toBeFalse();
            expect(PureFeatureFlag::DARK_MODE->isNot(PureFeatureFlag::BETA_ACCESS))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->in(['TWO_FACTOR_AUTH', 'DARK_MODE']))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->in(['BETA_ACCESS']))->toBeFalse();
        });

        it('PureFeatureFlag lookup methods work with case names', function () {
            expect(PureFeatureFlag::tryFromName('DARK_MODE'))->toBe(PureFeatureFlag::DARK_MODE);
            expect(PureFeatureFlag::tryFromName('NONEXISTENT'))->toBeNull();
            expect(PureFeatureFlag::fromName('BETA_ACCESS'))->toBe(PureFeatureFlag::BETA_ACCESS);
            expect(fn () => PureFeatureFlag::fromName('INVALID'))->toThrow(InvalidEnumException::class);
            expect(PureFeatureFlag::hasCase('TWO_FACTOR_AUTH'))->toBeTrue();
            expect(PureFeatureFlag::hasCase('NONEXISTENT'))->toBeFalse();
        });

        it('PureFeatureFlag tryFromLabel resolves auto-generated labels', function () {
            expect(PureFeatureFlag::tryFromLabel('Two Factor Auth'))->toBe(PureFeatureFlag::TWO_FACTOR_AUTH);
            expect(PureFeatureFlag::tryFromLabel('two factor auth'))->toBe(PureFeatureFlag::TWO_FACTOR_AUTH);
            expect(PureFeatureFlag::tryFromLabel('Dark Mode'))->toBe(PureFeatureFlag::DARK_MODE);
        });
    });

    describe('MixedAttributeStatus full resolution chain', function () {
        it('class-level EnumLabel provides labels for multiple cases', function () {
            expect(MixedAttributeStatus::NEW->label())->toBe('Brand New Item');
            expect(MixedAttributeStatus::USED->label())->toBe('Previously Owned');
        });

        it('class-level EnumColor provides colors for multiple cases', function () {
            expect(MixedAttributeStatus::ACTIVE->color())->toBe('success');
            expect(MixedAttributeStatus::PENDING->color())->toBe('warning');
            expect(MixedAttributeStatus::USED->color())->toBe('warning');
            expect(MixedAttributeStatus::ARCHIVED->color())->toBe('danger');
        });

        it('class-level EnumDescription provides descriptions', function () {
            expect(MixedAttributeStatus::ACTIVE->description())->toBe('Currently active');
            expect(MixedAttributeStatus::PENDING->description())->toBe('Awaiting review');
        });

        it('class-level EnumIcon provides default icon for all cases', function () {
            expect(MixedAttributeStatus::ACTIVE->icon())->toBe('heroicon-o-document');
            expect(MixedAttributeStatus::DELETED->icon())->toBe('heroicon-o-document');
        });

        it('cases without class-level metadata fall back to auto-generated', function () {
            // DELETED has no class-level label — auto-generated: "Deleted"
            expect(MixedAttributeStatus::DELETED->label())->toBe('Deleted');
            // DELETED has no class-level color — default: 'secondary'
            expect(MixedAttributeStatus::DELETED->color())->toBe('secondary');
            // DELETED has no class-level description — null
            expect(MixedAttributeStatus::DELETED->description())->toBeNull();
        });

        it('forApi returns all cases with complete metadata', function () {
            $api = MixedAttributeStatus::forApi();
            expect($api)->toHaveCount(6); // ACTIVE, NEW, PENDING, USED, ARCHIVED, DELETED

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['label'])->toBeString()->not->toBeEmpty();
                expect($item['color'])->toBeString();
            }
        });
    });

    describe('InvalidEnumException named constructors', function () {
        it('value() factory formats null values correctly', function () {
            $e = InvalidEnumException::value(UserStatus::class, null);
            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain('UserStatus');
        });

        it('value() factory formats string values correctly', function () {
            $e = InvalidEnumException::value(UserStatus::class, 'nonexistent');
            expect($e->getMessage())->toContain('nonexistent');
            expect($e->getMessage())->toContain('UserStatus');
        });

        it('value() factory formats int values correctly', function () {
            $e = InvalidEnumException::value(Priority::class, 99);
            expect($e->getMessage())->toContain('99');
            expect($e->getMessage())->toContain('Priority');
        });

        it('forName() factory includes both class and name in message', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN_CASE');
            expect($e->getMessage())->toContain('UserStatus');
            expect($e->getMessage())->toContain('UNKNOWN_CASE');
            expect($e->getMessage())->toContain('does not exist');
        });

        it('exceptions are final and extend Exception', function () {
            $ref = new ReflectionClass(InvalidEnumException::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->getParentClass()->getName())->toBe('Exception');
        });
    });

    describe('EnumCache TTL edge cases', function () {
        it('negative TTL is normalized to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);
            expect($cache->getTtl())->toBe(0);
        });

        it('setTtl and getTtl are consistent', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(42);
            expect($cache->getTtl())->toBe(42);

            $cache->setTtl(0);
            expect($cache->getTtl())->toBe(0);

            $cache->setTtl(3600);
            expect($cache->getTtl())->toBe(3600);
        });

        it('cache is per-process — reset creates a clean instance', function () {
            $cache1 = EnumCache::getInstance();
            $cache1->setTtl(300);
            $cache1->set(UserStatus::class, [
                'labels' => ['active' => 'Cached User'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache1->has(UserStatus::class))->toBeTrue();
            expect($cache1->getTtl())->toBe(300);

            // Reset
            EnumCache::resetInstance();

            $cache2 = EnumCache::getInstance();
            expect($cache2->has(UserStatus::class))->toBeFalse();
            expect($cache2->getTtl())->toBe(300); // TTL default is 300 in new instance
        });
    });

    describe('EnumMetadataResolver::resolve returns correct shape', function () {
        it('resolved metadata has all required keys with correct types', function () {
            $meta = EnumMetadataResolver::resolve(TicketStatus::class);

            // Top-level keys
            expect(array_keys($meta))->toEqual(['labels', 'descriptions', 'colors', 'icons']);

            // labels is array<string, string>
            foreach ($meta['labels'] as $key => $value) {
                expect(is_string($key))->toBeTrue('Label key must be string');
                expect(is_string($value))->toBeTrue('Label value must be string');
            }

            // descriptions is array<string, string>
            foreach ($meta['descriptions'] as $key => $value) {
                expect(is_string($key))->toBeTrue('Description key must be string');
                expect(is_string($value))->toBeTrue('Description value must be string');
            }

            // colors is array<string, string>
            foreach ($meta['colors'] as $key => $value) {
                expect(is_string($key))->toBeTrue('Color key must be string');
                expect(is_string($value))->toBeTrue('Color value must be string');
            }

            // icons is array<string, string>
            foreach ($meta['icons'] as $key => $value) {
                expect(is_string($key))->toBeTrue('Icon key must be string');
                expect($value === null || is_string($value))->toBeTrue('Icon value must be string or null');
            }
        });

        it('TicketStatus class-level EnumLabel resolves correctly', function () {
            expect(TicketStatus::OPEN->label())->toBe('Open');
            expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
            expect(TicketStatus::CLOSED->label())->toBe('Closed');
        });

        it('TicketStatus class-level EnumDescription resolves correctly', function () {
            expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
            expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
        });
    });

    describe('EnumRule — pure enum validation edge cases', function () {
        it('accepts valid case name for pure enum', function () {
            $rule = EnumRule::for(RequestState::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('state', 'DRAFT', $fail);
            expect($failed)->toBeFalse();
        });

        it('rejects invalid case name for pure enum', function () {
            $rule = EnumRule::for(RequestState::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('state', 'NONEXISTENT', $fail);
            expect($failed)->toBeTrue();
        });

        it('rejects non-string value for pure enum', function () {
            $rule = EnumRule::for(RequestState::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('state', 123, $fail);
            expect($failed)->toBeTrue();
        });

        it('rejects int value for string-backed enum', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 1, $fail);
            expect($failed)->toBeTrue();
        });

        it('accepts valid string value for string-backed enum', function () {
            $rule = EnumRule::for(OrderStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 'pending', $fail);
            expect($failed)->toBeFalse();
        });

        it('nullable pure enum allows null', function () {
            $rule = EnumRule::for(RequestState::class)->nullable();
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('state', null, $fail);
            expect($failed)->toBeFalse();
        });
    });

    describe('OrderStatus — minimal enum with no attributes', function () {
        it('all metadata is auto-generated or default', function () {
            expect(OrderStatus::PENDING->label())->toBe('Pending');
            expect(OrderStatus::SHIPPED->label())->toBe('Shipped');
            expect(OrderStatus::DELIVERED->label())->toBe('Delivered');
            expect(OrderStatus::CANCELLED->label())->toBe('Cancelled');

            // No color defined — default is 'secondary'
            expect(OrderStatus::PENDING->color())->toBe('secondary');
            expect(OrderStatus::SHIPPED->color())->toBe('secondary');

            // No description — null
            expect(OrderStatus::PENDING->description())->toBeNull();

            // No icon — null
            expect(OrderStatus::PENDING->icon())->toBeNull();
        });

        it('forSelect returns all cases with backed values', function () {
            $select = OrderStatus::forSelect();
            expect($select)->toHaveCount(4);

            $values = array_column($select, 'value');
            expect($values)->toEqual(['pending', 'shipped', 'delivered', 'cancelled']);
        });

        it('tryFromLabel resolves auto-generated labels case-insensitively', function () {
            expect(OrderStatus::tryFromLabel('Pending'))->toBe(OrderStatus::PENDING);
            expect(OrderStatus::tryFromLabel('SHIPPED'))->toBe(OrderStatus::SHIPPED);
            expect(OrderStatus::tryFromLabel('cancelled'))->toBe(OrderStatus::CANCELLED);
        });
    });
});
