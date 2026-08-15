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
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('V15 — Service Provider, Cache Lifecycle, Test Generator Contract', function () {
    // ── Section 1: EnumCache TTL Boundary Behavior ───────────────────────────

    it('EnumCache with TTL=0 disables caching — has() always returns false', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Cached'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();
        EnumCache::resetInstance();
    });

    it('EnumCache with negative TTL normalizes to 0 and disables caching', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Cached'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ]);

        expect($cache->getTtl())->toBe(0);
        expect($cache->has(UserStatus::class))->toBeFalse();
        EnumCache::resetInstance();
    });

    it('EnumCache with TTL=1 expires entries within 1 second', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Cached'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();

        // Sleep just past TTL
        usleep(1_100_000); // 1.1 seconds

        expect($cache->has(UserStatus::class))->toBeFalse();
        EnumCache::resetInstance();
    });

    it('EnumCache clearClass removes specific entry only', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'User'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(IntPriority::class, [
            'labels' => [1 => 'Priority'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(IntPriority::class))->toBeTrue();

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(IntPriority::class))->toBeTrue();
        EnumCache::resetInstance();
    });

    // ── Section 2: EnumMetadataResolver with non-enum class ─────────────────

    it('EnumMetadataResolver throws LogicException for non-enum class', function () {
        EnumMetadataResolver::invalidateAll();
        expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))
            ->toThrow(\LogicException::class);
    });

    it('EnumMetadataResolver invalidate removes specific class cache', function () {
        EnumMetadataResolver::invalidateAll();
        // First resolve caches it
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta1['labels'])->not->toBeEmpty();

        // Invalidate
        EnumMetadataResolver::invalidate(UserStatus::class);

        // Re-resolve should still work
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta2)->toBe($meta1);
    });

    // ── Section 3: EnumManager delegates correctly ─────────────────────────

    it('EnumManager forSelect returns correct structure for string-backed enum', function () {
        $manager = new EnumManager;
        $result = $manager->forSelect(UserStatus::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        foreach ($result as $item) {
            expect($item)->toHaveKeys(['value', 'label']);
            expect($item['value'])->toBeString();
            expect($item['label'])->toBeString()->not->toBeEmpty();
        }
    });

    it('EnumManager forApi returns full metadata structure', function () {
        $manager = new EnumManager;
        $result = $manager->forApi(UserStatus::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        foreach ($result as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['color'])->toBeString();
        }
    });

    it('EnumManager values returns correct type for int-backed enum', function () {
        $manager = new EnumManager;
        $values = $manager->values(IntPriority::class);

        expect($values)->not->toBeEmpty();
        foreach ($values as $v) {
            expect($v)->toBeInt();
        }
    });

    it('EnumManager values returns string case names for pure enum', function () {
        $manager = new EnumManager;
        $values = $manager->values(PureFeatureFlag::class);

        expect($values)->not->toBeEmpty();
        foreach ($values as $v) {
            expect($v)->toBeString();
        }
    });

    it('EnumManager labels returns non-empty strings', function () {
        $manager = new EnumManager;
        $labels = $manager->labels(UserStatus::class);

        expect($labels)->not->toBeEmpty();
        expect(count($labels))->toBe(count(UserStatus::cases()));
        foreach ($labels as $label) {
            expect($label)->toBeString()->not->toBeEmpty();
        }
    });

    it('EnumManager throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        // PlainTestEnum is a raw enum without HasEnumMetadata
        expect(fn () => $manager->forSelect(\ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('EnumManager hasCase returns correct booleans', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
        expect($manager->hasCase(UserStatus::class, 'NONEXISTENT'))->toBeFalse();
    });

    it('EnumManager fromName returns correct case or throws', function () {
        $manager = new EnumManager;

        $case = $manager->fromName(UserStatus::class, 'ACTIVE');
        expect($case)->toBe(UserStatus::ACTIVE);

        expect(fn () => $manager->fromName(UserStatus::class, 'NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('EnumManager tryFromName returns null for missing case', function () {
        $manager = new EnumManager;

        expect($manager->tryFromName(UserStatus::class, 'ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect($manager->tryFromName(UserStatus::class, 'NONEXISTENT'))->toBeNull();
    });

    it('EnumManager tryFromLabel resolves case-insensitively', function () {
        $manager = new EnumManager;

        $label = UserStatus::ACTIVE->label();
        expect($manager->tryFromLabel(UserStatus::class, $label))->toBe(UserStatus::ACTIVE);
        expect($manager->tryFromLabel(UserStatus::class, strtolower($label)))->toBe(UserStatus::ACTIVE);
        expect($manager->tryFromLabel(UserStatus::class, 'nonexistent-label'))->toBeNull();
    });

    // ── Section 4: EnumTestGenerator Output Structure ───────────────────────

    it('EnumTestGenerator generates valid PHP for string-backed enum', function () {
        $code = EnumTestGenerator::generate(UserStatus::class);

        expect($code)->toBeString()->not->toBeEmpty();
        expect($code)->toContain('declare(strict_types=1)');
        expect($code)->toContain('use '.UserStatus::class);
        expect($code)->toContain('InvalidEnumException');
        expect($code)->toContain("describe('UserStatus enum'");
        expect($code)->toContain('forSelect');
        expect($code)->toContain('forApi');
        expect($code)->toContain('tryFromName');
        expect($code)->toContain('fromName');
        expect($code)->toContain('hasCase');
        expect($code)->toContain('values()');
        expect($code)->toContain('labels()');
        expect($code)->toContain('->toBeString()');
    });

    it('EnumTestGenerator generates comparison tests for enums with 2+ cases', function () {
        $code = EnumTestGenerator::generate(UserStatus::class);

        expect($code)->toContain('supports is() comparison');
        expect($code)->toContain('supports isNot() comparison');
        expect($code)->toContain('supports in() group matching');
        expect($code)->toContain('supports notIn() group exclusion');
        expect($code)->toContain('tryFromLabel reverse lookup');
    });

    it('EnumTestGenerator generates correct backing type test for int enum', function () {
        $code = EnumTestGenerator::generate(IntPriority::class);

        expect($code)->toContain('values() returns int backed values');
        expect($code)->toContain('->toBeInt()');
    });

    it('EnumTestGenerator generates pure enum case name test', function () {
        $code = EnumTestGenerator::generate(PureFeatureFlag::class);

        expect($code)->toContain('values() returns case names for pure enum');
    });

    it('EnumTestGenerator generates per-case tests for each case', function () {
        $code = EnumTestGenerator::generate(SingleCaseEnum::class);

        foreach (SingleCaseEnum::cases() as $case) {
            expect($code)->toContain("case {$case->name}");
        }
    });

    it('EnumTestGenerator does NOT generate comparison tests for single-case enums', function () {
        $code = EnumTestGenerator::generate(SingleCaseEnum::class);

        // SingleCaseEnum has only 1 case, so comparison section is skipped
        expect($code)->not->toContain('supports is() comparison');
    });

    it('EnumTestGenerator generates fromName case-sensitivity test', function () {
        $code = EnumTestGenerator::generate(UserStatus::class);

        expect($code)->toContain('fromName() rejects case-insensitive name lookup');
        expect($code)->toContain('strtolower');
    });

    // ── Section 5: EnumRule edge cases ──────────────────────────────────────

    it('EnumRule nullable allows null values', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $fail = fn () => true;
        $passed = false;

        $rule->validate('status', null, function (string $_, string $message = null) use (&$passed): void {
            $passed = true;
        });

        expect($passed)->toBeFalse(); // nullable rule should NOT call fail for null
    });

    it('EnumRule rejects non-null invalid values even when nullable', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $failed = false;

        $rule->validate('status', 'invalid_value', function (string $_, string $message = null) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('EnumRule validates int-backed enum with strict type checking', function () {
        $rule = EnumRule::for(IntPriority::class);
        $failed = false;

        // Int-backed enum rejects string value
        $rule->validate('priority', 'high', function (string $_, string $message = null) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('EnumRule for() and nullable() return new instances', function () {
        $rule1 = EnumRule::for(UserStatus::class);
        $rule2 = $rule1->nullable();

        expect($rule1)->not->toBe($rule2);
    });

    // ── Section 6: EnumCast serialization contract ──────────────────────────

    it('EnumCast serialize returns backed value for enum instance', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', UserStatus::ACTIVE, []);

        expect($result)->toBe('active');
    });

    it('EnumCast serialize returns raw string for string values', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', 'active', []);

        expect($result)->toBe('active');
    });

    it('EnumCast serialize returns raw int for int values', function () {
        $cast = new EnumCast(IntPriority::class);
        $result = $cast->serialize(new \stdClass, 'priority', 1, []);

        expect($result)->toBe(1);
    });

    it('EnumCast serialize returns null for null value', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', null, []);

        expect($result)->toBeNull();
    });

    // ── Section 7: CamelCase enum label generation ──────────────────────────

    it('camelCase enum case generates correct Title Case label', function () {
        // CamelCaseRole has camelCase cases
        foreach (CamelCaseRole::cases() as $case) {
            $label = $case->label();
            expect($label)->toBeString()->not->toBeEmpty();
            // First char should be uppercase
            expect(ctype_upper($label[0]))->toBeTrue();
        }
    });

    // ── Section 8: InvalidEnumException named constructors ────────────────

    it('InvalidEnumException::value includes value in message', function () {
        $exception = InvalidEnumException::value(UserStatus::class, 'bad_value');
        expect($exception->getMessage())->toContain('bad_value');
        expect($exception->getMessage())->toContain(UserStatus::class);
    });

    it('InvalidEnumException::value handles null value', function () {
        $exception = InvalidEnumException::value(UserStatus::class, null);
        expect($exception->getMessage())->toContain('null');
    });

    it('InvalidEnumException::forName includes name in message', function () {
        $exception = InvalidEnumException::forName(UserStatus::class, 'BAD_CASE');
        expect($exception->getMessage())->toContain('BAD_CASE');
        expect($exception->getMessage())->toContain(UserStatus::class);
    });

    it('InvalidEnumException __toString includes class name', function () {
        $exception = InvalidEnumException::forName(UserStatus::class, 'BAD');
        $str = (string) $exception;

        expect($str)->toBe(InvalidEnumException::class.': '.$exception->getMessage());
    });

    // ── Section 9: EnumCache flush and reset ─────────────────────────────────

    it('EnumCache flush clears all entries via static method', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::flush();

        // After flush, need new instance check (flush clears via singleton)
        $cache2 = EnumCache::getInstance();
        expect($cache2->has(UserStatus::class))->toBeFalse();
        EnumCache::resetInstance();
    });

    it('EnumCache resetInstance destroys singleton', function () {
        EnumCache::resetInstance();
        $cache1 = EnumCache::getInstance();
        $cache1->setTtl(42);

        EnumCache::resetInstance();

        $cache2 = EnumCache::getInstance();
        // New instance should have default TTL (300), not 42
        expect($cache2->getTtl())->toBe(300);
        EnumCache::resetInstance();
    });
});
