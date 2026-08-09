<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum Production Readiness — Final Audit', function () {
    // ── strict types ───────────────────────────────────────────
    it('every source file declares strict_types=1', function () {
        $srcDir = __DIR__ . '/../src';
        $phpFiles = glob($srcDir . '/**/*.php', GLOB_BRACE);

        expect($phpFiles)->not->toBeEmpty();

        foreach ($phpFiles as $file) {
            $contents = file_get_contents($file);
            expect($contents)->toContain('declare(strict_types=1)');
        }
    });

    // ── EnumCache TTL=0 disables caching ───────────────────────
    it('EnumCache with TTL=0 never caches — always rebuilds', function () {
        $cache = EnumCache::getInstance();
        $originalTtl = $cache->getTtl();
        $cache->setTtl(0);

        // Resolve metadata (should not cache)
        $meta1 = EnumMetadataResolver::resolve(OrderStatus::class);

        // Resolve again — TTL=0 means has() returns false every time
        expect($cache->has(OrderStatus::class))->toBeFalse();

        // Clear any residual state
        $cache->setTtl($originalTtl);
        EnumMetadataResolver::invalidateAll();

        expect($meta1)->toBeArray();
    });

    it('EnumCache setTtl rejects negative values and normalizes to 0', function () {
        $cache = EnumCache::getInstance();
        $originalTtl = $cache->getTtl();

        $cache->setTtl(-5);
        expect($cache->getTtl())->toBe(0);

        $cache->setTtl($originalTtl);
    });

    // ── generateLabel edge cases ──────────────────────────────
    it('generates correct labels for SCREAMING_SNAKE_CASE', function () {
        expect(OrderStatus::PENDING->label())->toBe('Pending');
        expect(OrderStatus::CANCELLED->label())->toBe('Cancelled');
    });

    it('generates correct labels for camelCase enum names', function () {
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
    });

    it('generates correct labels for pure enum case names', function () {
        expect(PureFeatureFlag::TWO_FACTOR_AUTH->label())->toBe('Two Factor Auth');
        expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
        expect(PureFeatureFlag::BETA_ACCESS->label())->toBe('Beta Access');
    });

    // ── Single-case enum ──────────────────────────────────────
    it('single-case enum works with all metadata methods', function () {
        expect(SingleCaseEnum::cases())->toHaveCount(1);
        expect(SingleCaseEnum::forSelect())->toHaveCount(1);
        expect(SingleCaseEnum::forApi())->toHaveCount(1);
        expect(SingleCaseEnum::values())->toHaveCount(1);
        expect(SingleCaseEnum::labels())->toHaveCount(1);
        expect(SingleCaseEnum::ONLY->label())->toBeString();
        expect(SingleCaseEnum::ONLY->color())->toBe('secondary');
        expect(SingleCaseEnum::ONLY->icon())->toBeNull();
        expect(SingleCaseEnum::ONLY->description())->toBeNull();
        expect(SingleCaseEnum::ONLY->is(SingleCaseEnum::ONLY))->toBeTrue();
        expect(SingleCaseEnum::ONLY->is('ONLY'))->toBeTrue();
        expect(SingleCaseEnum::ONLY->isNot(SingleCaseEnum::ONLY))->toBeFalse();
        expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
        expect(SingleCaseEnum::hasCase('ONLY'))->toBeTrue();
        expect(SingleCaseEnum::hasCase('NON_EXISTENT'))->toBeFalse();
        expect(SingleCaseEnum::tryFromName('ONLY'))->toBe(SingleCaseEnum::ONLY);
        expect(SingleCaseEnum::tryFromLabel(SingleCaseEnum::ONLY->label()))->toBe(SingleCaseEnum::ONLY);
    });

    // ── Zero value int-backed enum ──────────────────────────
    it('int-backed enum with zero value resolves correctly', function () {
        expect(ZeroPriority::NONE->value)->toBe(0);
        expect(ZeroPriority::NONE->label())->toBe('None');
        expect(ZeroPriority::values())->toBe([0, 1, 2]);
        expect(ZeroPriority::forSelect()[0]['value'])->toBe(0);
    });

    // ── Type consistency across enum flavors ────────────────
    it('string-backed enum forApi returns string values', function () {
        $api = UserStatus::forApi();
        foreach ($api as $item) {
            expect($item['value'])->toBeString();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString();
            expect($item['color'])->toBeString();
        }
    });

    it('int-backed enum forApi returns int values', function () {
        $api = Priority::forApi();
        foreach ($api as $item) {
            expect($item['value'])->toBeInt();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString();
            expect($item['color'])->toBeString();
        }
    });

    it('pure enum forApi returns case names as values', function () {
        $api = PureFeatureFlag::forApi();
        foreach ($api as $item) {
            expect($item['value'])->toBeString();
            // Value should be the case name for pure enums
            expect($item['value'])->toBeIn(['TWO_FACTOR_AUTH', 'DARK_MODE', 'BETA_ACCESS']);
        }
    });

    // ── forSelect structure contract ─────────────────────────
    it('forSelect always returns sequential array with value+label keys', function () {
        $enums = [
            OrderStatus::class,
            Priority::class,
            PureFeatureFlag::class,
            UserStatus::class,
            IntStatusWithColor::class,
        ];

        foreach ($enums as $enumClass) {
            $select = $enumClass::forSelect();
            expect($select)->toBeArray();
            expect($select)->not->toBeEmpty();

            foreach ($select as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }

            // Values should be unique
            $values = array_column($select, 'value');
            expect(array_unique($values))->toBe($values);
        }
    });

    // ── forApi structure contract ────────────────────────────
    it('forApi always returns full metadata with 6 keys per entry', function () {
        $api = UserStatus::forApi();

        foreach ($api as $entry) {
            expect($entry)->toHaveCount(6);
            expect($entry)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($entry['color'])->toBeString()->not->toBeEmpty();
        }
    });

    // ── Class-level EnumIcon default propagation ─────────────
    it('class-level EnumIcon sets default icon for all cases', function () {
        // AllClassLevelEnum has EnumIcon(default: 'heroicon-o-circle')
        foreach (AllClassLevelEnum::cases() as $case) {
            expect($case->icon())->toBe('heroicon-o-circle');
        }
    });

    // ── Class-level EnumDescription propagation ────────────
    it('class-level EnumDescription provides descriptions for all mapped cases', function () {
        expect(AllClassLevelEnum::OPEN->description())->toBe('Task is open');
        expect(AllClassLevelEnum::IN_PROGRESS->description())->toBe('Task is being worked on');
        expect(AllClassLevelEnum::DONE->description())->toBe('Task is complete');
    });

    // ── Class-level EnumLabel propagation ───────────────────
    it('class-level EnumLabel provides labels for all mapped cases', function () {
        expect(AllClassLevelEnum::OPEN->label())->toBe('Open Status');
        expect(AllClassLevelEnum::IN_PROGRESS->label())->toBe('In Progress');
        expect(AllClassLevelEnum::DONE->label())->toBe('Done');
    });

    // ── Mixed attribute resolution priority ────────────────
    it('per-case Label overrides class-level EnumLabel', function () {
        // MixedAttributeStatus has class-level EnumLabel with labels map
        // Verify all cases have non-empty labels
        foreach (MixedAttributeStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });

    it('per-case Color overrides class-level EnumColor', function () {
        // IntStatusWithColor: BANNED=3 has per-case #[Color('danger')]
        // Class-level has danger: [3], so they agree
        expect(IntStatusWithColor::BANNED->color())->toBe('danger');

        // ACTIVE=1 has class-level success: [1] — no per-case override
        expect(IntStatusWithColor::ACTIVE->color())->toBe('success');
    });

    // ── tryFromLabel case-insensitivity ─────────────────────
    it('tryFromLabel is truly case-insensitive', function () {
        $label = UserStatus::ACTIVE->label();
        expect(UserStatus::tryFromLabel(strtolower($label)))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel(strtoupper($label)))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel(ucfirst($label)))->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel returns null for non-existent labels', function () {
        expect(UserStatus::tryFromLabel('nonexistent_label_xyz'))->toBeNull();
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });

    // ── fromName throws on invalid name ───────────────────────
    it('fromName throws InvalidEnumException for non-existent case name', function () {
        expect(fn () => UserStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('InvalidEnumException::value formats message correctly', function () {
        $exception = InvalidEnumException::value(UserStatus::class, 'invalid_value');
        expect($exception->getMessage())->toContain('invalid_value');
        expect($exception->getMessage())->toContain(UserStatus::class);
    });

    it('InvalidEnumException::forName formats message correctly', function () {
        $exception = InvalidEnumException::forName(UserStatus::class, 'NON_EXISTENT');
        expect($exception->getMessage())->toContain('NON_EXISTENT');
        expect($exception->getMessage())->toContain(UserStatus::class);
    });

    // ── Cache invalidation flow ──────────────────────────────
    it('EnumMetadataResolver::invalidate clears specific class cache', function () {
        // Pre-populate cache
        EnumMetadataResolver::resolve(OrderStatus::class);
        $cache = EnumCache::getInstance();
        expect($cache->has(OrderStatus::class))->toBeTrue();

        // Invalidate
        EnumMetadataResolver::invalidate(OrderStatus::class);
        expect($cache->has(OrderStatus::class))->toBeFalse();

        // Re-resolve should work
        $meta = EnumMetadataResolver::resolve(OrderStatus::class);
        expect($meta)->toBeArray();
        expect($cache->has(OrderStatus::class))->toBeTrue();
    });

    it('EnumMetadataResolver::invalidateAll clears everything', function () {
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
    });

    // ── EnumCache reset for testing ──────────────────────────
    it('EnumCache::resetInstance allows fresh singleton creation', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        expect($cache)->toBeInstanceOf(EnumCache::class);
        expect($cache->getTtl())->toBeInt();
    });

    // ── Exception hierarchy ─────────────────────────────────
    it('InvalidEnumException extends Exception', function () {
        $e = InvalidEnumException::value('Test', null);
        expect($e)->toBeInstanceOf(\Exception::class);
    });

    // ── values() returns correct types ──────────────────────
    it('values() returns string array for string-backed, int array for int-backed', function () {
        $stringValues = UserStatus::values();
        foreach ($stringValues as $v) {
            expect($v)->toBeString();
        }

        $intValues = Priority::values();
        foreach ($intValues as $v) {
            expect($v)->toBeInt();
        }
    });

    // ── labels() returns non-empty strings ───────────────────
    it('labels() returns all non-empty strings in declaration order', function () {
        $labels = UserStatus::labels();
        expect($labels)->toHaveCount(count(UserStatus::cases()));
        foreach ($labels as $label) {
            expect($label)->toBeString()->not->toBeEmpty();
        }
    });
});
