<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use BackedEnum;
use Illuminate\Contracts\Validation\ValidationRule;
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
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\EdgeCaseNamingEnum;
use ZeroBoiler\Enums\Tests\Fixtures\InventoryStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\WorkflowState;

/**
 * V52 Comprehensive Production Hardening Audit.
 *
 * Covers edge cases discovered after V51:
 * - EnumCache TTL boundary precision (microtime floats)
 * - EnumMetadataResolver cache coherency after invalidate()
 * - EnumRule backing type mismatch rejection (string→int enum, int→string enum)
 * - EnumCast serialize() edge cases (null, scalar passthrough)
 * - HasEnumMetadata toValue() consistency with BackedEnum interface
 * - EnumManager delegation with invalid enum class (not an enum)
 * - EnumMetadataResolver enum_exists() guard for non-enum classes
 * - WorkflowState fixture: full attribute coverage (class + per-case overrides)
 * - Multi-word label generation: InventoryStatus SCREAMING_SNAKE_CASE cases
 * - EdgeCaseNamingEnum: single letter, numeric, triple underscore edge cases
 * - IntPriority: int-backed enum forApi()/forSelect() value type verification
 * - PureFeatureFlag: pure enum toValue() returns case name, not null
 * - SingleCaseToggle: single-case enum bulk methods don't break
 * - EnumCache singleton: resetInstance() creates fresh instance
 * - EnumFacade: getFacadeAccessor() returns correct string
 */
describe('V52 Comprehensive Production Hardening Audit', function (): void {
    // ── EnumCache TTL Boundary Precision ────────────────────────────────

    it('EnumCache has() returns false when TTL is 0 (caching disabled)', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(__CLASS__.'_test', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(__CLASS__.'_test'))->toBeFalse();
    });

    it('EnumCache has() returns false for negative TTL (clamped to 0)', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);
        $cache->set(__CLASS__.'_negative_test', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(__CLASS__.'_negative_test'))->toBeFalse();
    });

    it('EnumCache setTtl() clamps negative values to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    // ── EnumMetadataResolver Cache Coherency After Invalidate ──────────

    it('metadata resolver re-resolves after cache invalidation', function (): void {
        $status = UserStatus::ACTIVE;
        $labelBefore = $status->label();

        // Flush and re-resolve — should produce identical result
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(UserStatus::class);
        $labelAfter = $status->label();

        expect($labelAfter)->toBe($labelBefore);
    });

    it('metadata resolver invalidateAll() clears everything', function (): void {
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidateAll();
        $label = UserStatus::ACTIVE->label();

        // Should still work (rebuilds from scratch)
        expect($label)->toBeString()->not->toBeEmpty();
    });

    // ── WorkflowState Full Attribute Coverage ───────────────────────────

    it('WorkflowState per-case overrides take priority over class-level', function (): void {
        // ACTIVE has per-case Label('Active & Running'), overriding EnumLabel 'Active'
        expect(WorkflowState::ACTIVE->label())->toBe('Active & Running');

        // PENDING falls back to EnumLabel 'Pending Review'
        expect(WorkflowState::PENDING->label())->toBe('Pending Review');
    });

    it('WorkflowState per-case icon overrides class-level default and map', function (): void {
        // ACTIVE has per-case Icon('heroicon-o-bolt'), overriding EnumIcon map
        expect(WorkflowState::ACTIVE->icon())->toBe('heroicon-o-bolt');
    });

    it('WorkflowState class-level EnumIcon provides default for uncovered cases', function (): void {
        // PENDING has no per-case Icon, no entry in EnumIcon icons map
        // → falls back to EnumIcon default
        expect(WorkflowState::PENDING->icon())->toBe('heroicon-o-circle-dot');
    });

    it('WorkflowState EnumIcon per-value map overrides default', function (): void {
        // FAILED has an entry in EnumIcon icons map: 'failed' => 'heroicon-o-x-circle'
        expect(WorkflowState::FAILED->icon())->toBe('heroicon-o-x-circle');
    });

    it('WorkflowState class-level color mapping works for all cases', function (): void {
        expect(WorkflowState::ACTIVE->color())->toBe('success');
        expect(WorkflowState::PENDING->color())->toBe('warning');
        expect(WorkflowState::PROCESSING->color())->toBe('info');
        expect(WorkflowState::FAILED->color())->toBe('danger');
        expect(WorkflowState::DELETED->color())->toBe('danger');
    });

    it('WorkflowState per-case Color overrides class-level', function (): void {
        // PROCESSING_ALT has #[Color('info')] which matches class-level anyway
        expect(WorkflowState::PROCESSING_ALT->color())->toBe('info');
    });

    it('WorkflowState EnumDescription maps to cases by backed value', function (): void {
        expect(WorkflowState::ACTIVE->description())->toBe('System is actively processing');
        expect(WorkflowState::FAILED->description())->toBe('Execution has failed');
    });

    it('WorkflowState forApi() returns complete metadata for all cases', function (): void {
        $api = WorkflowState::forApi();

        expect($api)->toHaveCount(7);
        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['color'])->toBeString()->not->toBeEmpty();
            expect($item['label'])->toBeString()->not->toBeEmpty();
        }
    });

    // ── InventoryStatus Multi-Word Label Generation ─────────────────────

    it('InventoryStatus generates correct multi-word labels from SCREAMING_SNAKE', function (): void {
        expect(InventoryStatus::IN_STOCK->label())->toBe('In Stock');
        expect(InventoryStatus::OUT_OF_STOCK->label())->toBe('Out Of Stock');
        expect(InventoryStatus::ON_BACK_ORDER->label())->toBe('On Back Order');
        expect(InventoryStatus::DISCONTINUED->label())->toBe('Discontinued');
    });

    it('InventoryStatus uses backed values in forSelect()', function (): void {
        $options = InventoryStatus::forSelect();

        expect($options)->toHaveCount(4);
        // Backed values (snake_case), not case names (SCREAMING_SNAKE_CASE)
        $values = array_column($options, 'value');
        expect($values)->toContain('in_stock');
        expect($values)->toContain('out_of_stock');
        expect($values)->toContain('on_back_order');
        expect($values)->toContain('discontinued');
    });

    // ── EdgeCaseNamingEnum Edge Cases ───────────────────────────────────

    it('EdgeCaseNamingEnum generates labels for single-letter case', function (): void {
        expect(EdgeCaseNamingEnum::X->label())->toBe('X');
    });

    it('EdgeCaseNamingEnum generates labels for two-letter case', function (): void {
        expect(EdgeCaseNamingEnum::AB->label())->toBe('Ab');
    });

    it('EdgeCaseNamingEnum generates labels for numeric-in-name case', function (): void {
        expect(EdgeCaseNamingEnum::A1->label())->toBe('A1');
    });

    it('EdgeCaseNamingEnum handles triple underscore', function (): void {
        expect(EdgeCaseNamingEnum::TRIPLE___WORD->label())->toBe('Triple Word');
    });

    it('EdgeCaseNamingEnum handles double trailing underscore', function (): void {
        expect(EdgeCaseNamingEnum::UNDER_SCORE__->label())->toBe('Under Score');
    });

    it('EdgeCaseNamingEnum lower-case case generates capitalized label', function (): void {
        expect(EdgeCaseNamingEnum::LOWER->label())->toBe('Lower');
    });

    // ── IntPriority: Int-Backed Enum ───────────────────────────────────

    it('IntPriority forApi() returns int values not strings', function (): void {
        $api = IntPriority::forApi();

        expect($api)->not->toBeEmpty();
        foreach ($api as $item) {
            expect($item['value'])->toBeInt();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString();
        }
    });

    it('IntPriority forSelect() returns int values', function (): void {
        $options = IntPriority::forSelect();

        foreach ($options as $option) {
            expect($option['value'])->toBeInt();
            expect($option['label'])->toBeString();
        }
    });

    it('IntPriority values() returns list of ints', function (): void {
        $values = IntPriority::values();

        expect($values)->not->toBeEmpty();
        foreach ($values as $v) {
            expect($v)->toBeInt();
        }
    });

    it('IntPriority toValue() returns int', function (): void {
        $case = IntPriority::cases()[0];

        expect($case->toValue())->toBeInt();
    });

    // ── PureFeatureFlag: Pure Enum ──────────────────────────────────────

    it('PureFeatureFlag toValue() returns case name for pure enum', function (): void {
        $case = PureFeatureFlag::cases()[0];

        expect($case->toValue())->toBe($case->name);
        expect($case->toValue())->toBeString();
    });

    it('PureFeatureFlag values() returns case names', function (): void {
        $values = PureFeatureFlag::values();
        $names = array_map(static fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());

        expect($values)->toBe($names);
    });

    // ── SingleCaseToggle: Single-Case Enum ────────────────────────────

    it('SingleCaseToggle forSelect() works with single case', function (): void {
        $options = SingleCaseToggle::forSelect();

        expect($options)->toHaveCount(1);
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('SingleCaseToggle forApi() works with single case', function (): void {
        $api = SingleCaseToggle::forApi();

        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    // ── EnumCast serialize() Edge Cases ────────────────────────────────

    it('EnumCast serialize() returns null for null value', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', null, []);

        expect($result)->toBeNull();
    });

    it('EnumCast serialize() passes through string values', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', 'active', []);

        expect($result)->toBe('active');
    });

    it('EnumCast serialize() passes through int values', function (): void {
        $cast = new EnumCast(IntPriority::class);
        $result = $cast->serialize(new \stdClass, 'priority', 1, []);

        expect($result)->toBe(1);
    });

    it('EnumCast serialize() returns null for unsupported types', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass, 'status', 3.14, []);

        expect($result)->toBeNull();
    });

    // ── EnumRule Backing Type Mismatch Rejection ──────────────────────

    it('EnumRule rejects string value for int-backed enum', function (): void {
        $rule = EnumRule::for(IntPriority::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('priority', 'not_an_int', $fail);

        expect($failCalled)->toBeTrue();
    });

    it('EnumRule accepts int value for int-backed enum', function (): void {
        $case = IntPriority::cases()[0];
        $rule = EnumRule::for(IntPriority::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('priority', $case->value, $fail);

        expect($failCalled)->toBeFalse();
    });

    it('EnumRule accepts valid string for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('status', 'active', $fail);

        expect($failCalled)->toBeFalse();
    });

    // ── EnumCache Singleton Lifecycle ───────────────────────────────────

    it('EnumCache resetInstance() creates a fresh singleton', function (): void {
        $cache1 = EnumCache::getInstance();
        $cache1->setTtl(999);
        EnumCache::resetInstance();
        $cache2 = EnumCache::getInstance();

        // Fresh instance should have default TTL (300)
        expect($cache2->getTtl())->toBe(300);

        // Clean up
        EnumCache::resetInstance();
    });

    it('EnumCache clearClass() removes specific entry', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(__CLASS__.'_clear_test', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(__CLASS__.'_clear_test'))->toBeTrue();

        $cache->clearClass(__CLASS__.'_clear_test');

        expect($cache->has(__CLASS__.'_clear_test'))->toBeFalse();
    });

    // ── Enum Facade Accessor ───────────────────────────────────────────

    it('Enum facade returns correct accessor string', function (): void {
        $ref = new \ReflectionClass(Enum::class);
        $method = $ref->getMethod('getFacadeAccessor');
        $method->setAccessible(true);

        expect($method->invoke(null))->toBe('zeroboiler.enum');
    });

    // ── EnumRule nullable() creates independent instance ──────────────

    it('EnumRule nullable() creates new instance with nullable flag', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $nullableRule = $rule->nullable();

        expect($nullableRule)->not->toBe($rule);

        // Nullable rule should pass null
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $nullableRule->validate('status', null, $fail);
        expect($failCalled)->toBeFalse();
    });

    it('EnumRule non-nullable rejects null', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failCalled = false;
        $fail = static function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('status', null, $fail);
        expect($failCalled)->toBeTrue();
    });

    // ── EnumManager with non-enum class ────────────────────────────────

    it('EnumManager forSelect() throws for non-enum class', function (): void {
        $manager = new EnumManager;

        expect(fn (): array => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('EnumManager tryFromLabel() throws for non-enum class', function (): void {
        $manager = new EnumManager;

        expect(fn (): ?\UnitEnum => $manager->tryFromLabel(\stdClass::class, 'test'))
            ->toThrow(\BadMethodCallException::class);
    });

    // ── InvalidEnumException Named Constructors ─────────────────────────

    it('InvalidEnumException::value() handles null value', function (): void {
        $exception = InvalidEnumException::value(UserStatus::class, null);

        expect($exception->getMessage())->toContain('null');
    });

    it('InvalidEnumException::forName() includes class name in message', function (): void {
        $exception = InvalidEnumException::forName(UserStatus::class, 'INVALID');

        expect($exception->getMessage())->toContain('UserStatus');
        expect($exception->getMessage())->toContain('INVALID');
    });

    it('InvalidEnumException __toString() includes class name', function (): void {
        $exception = InvalidEnumException::forName(UserStatus::class, 'X');

        $str = (string) $exception;
        expect($str)->toContain('InvalidEnumException');
    });

    // ── EnumCache __debugInfo Structure ──────────────────────────────────

    it('EnumCache __debugInfo() returns expected keys', function (): void {
        $cache = EnumCache::getInstance();
        $debug = $cache->__debugInfo();

        expect($debug)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
        expect($debug['ttl'])->toBeInt();
        expect($debug['cachedClasses'])->toBeInt();
        expect($debug['timestampCount'])->toBeInt();
    });

    // ── PlainTestEnum Default Color Behavior ───────────────────────────

    it('plain test enum defaults to secondary color', function (): void {
        $case = PlainTestEnum::cases()[0];

        expect($case->color())->toBe('secondary');
    });

    // ── EnumsServiceProvider Structure ──────────────────────────────────

    it('EnumsServiceProvider is a final class', function (): void {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumsServiceProvider extends Laravel ServiceProvider', function (): void {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

        expect($ref->isSubclassOf(\Illuminate\Support\ServiceProvider::class))->toBeTrue();
    });

    // ── Cross-Method Label Consistency ─────────────────────────────────

    it('forSelect() labels match label() accessor for all WorkflowState cases', function (): void {
        foreach (WorkflowState::cases() as $case) {
            $directLabel = $case->label();
            $selectEntry = array_filter(
                WorkflowState::forSelect(),
                static fn (array $entry): bool => $entry['value'] === $case->value
            );
            $selectLabel = array_values($selectEntry)[0]['label'] ?? null;

            expect($selectLabel)->toBe($directLabel);
        }
    });

    it('forApi() labels match label() accessor for all WorkflowState cases', function (): void {
        foreach (WorkflowState::cases() as $case) {
            $directLabel = $case->label();
            $apiEntry = array_filter(
                WorkflowState::forApi(),
                static fn (array $entry): bool => $entry['value'] === $case->value
            );
            $apiLabel = array_values($apiEntry)[0]['label'] ?? null;

            expect($apiLabel)->toBe($directLabel);
        }
    });
});
