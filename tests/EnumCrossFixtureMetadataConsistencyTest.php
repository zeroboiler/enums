<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\EdgeCaseNamingEnum;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\WorkflowState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\OrderWorkflowStatus;
use ZeroBoiler\Enums\Tests\Fixtures\InventoryStatus;
use ZeroBoiler\Enums\Tests\Fixtures\NumericStatusCode;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;
use ZeroBoiler\Enums\Tests\Fixtures\SingletonMode;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\MixedTicketType;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;

/**
 * Cross-fixture metadata consistency and integration tests.
 *
 * Verifies that metadata resolution produces consistent, structurally valid
 * results across ALL fixture enums. Tests the invariant that every resolved
 * metadata array has exactly 4 keys (labels, descriptions, colors, icons)
 * and that forSelect/forApi produce arrays of the correct length and shape.
 *
 * @see \ZeroBoiler\Enums\Support\EnumMetadataResolver
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata
 */
describe('Cross-fixture metadata consistency', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    /**
     * Every fixture enum that uses HasEnumMetadata must produce valid metadata.
     */
    it('all fixture enums produce metadata with exactly 4 keys', function (): void {
        $fixtures = [
            UserStatus::class,
            IntBackedPriority::class,
            PureFeatureFlag::class,
            OrderStatus::class,
            Priority::class,
            AllClassLevelEnum::class,
            CamelCaseRole::class,
            EdgeCaseNamingEnum::class,
            MixedAttributeStatus::class,
            PaymentStatus::class,
            SystemStatus::class,
            DefaultIconFeature::class,
            OverriddenIconRole::class,
            ZeroPriority::class,
            ZeroBackedPriority::class,
            SingleCaseToggle::class,
            LabelMapEnum::class,
            WorkflowState::class,
            TicketStatus::class,
            IntStatusWithColor::class,
            OrderWorkflowStatus::class,
            InventoryStatus::class,
            NumericStatusCode::class,
            DetailedTicketStatus::class,
            EmptyDefaultsStatus::class,
            SingletonMode::class,
            IntPriority::class,
            MixedTicketType::class,
            PlainTestEnum::class,
            RequestState::class,
            PureSystemState::class,
            CamelCasePriority::class,
        ];

        foreach ($fixtures as $fixtureClass) {
            $metadata = EnumMetadataResolver::resolve($fixtureClass);

n            expect(array_keys($metadata))->toEqual(
                ['labels', 'descriptions', 'colors', 'icons'],
                "{$fixtureClass} metadata must have exactly 4 keys: labels, descriptions, colors, icons"
            );
        }
    });

    /**
     * forSelect() output must have exactly as many entries as cases.
     */
    it('forSelect() count matches cases() count for all fixtures', function (): void {
        $fixtures = [
            UserStatus::class,
            IntBackedPriority::class,
            PureFeatureFlag::class,
            OrderStatus::class,
            Priority::class,
            AllClassLevelEnum::class,
            CamelCaseRole::class,
            EdgeCaseNamingEnum::class,
            MixedAttributeStatus::class,
            PaymentStatus::class,
            SystemStatus::class,
            DefaultIconFeature::class,
            OverriddenIconRole::class,
            ZeroPriority::class,
            ZeroBackedPriority::class,
            SingleCaseToggle::class,
            LabelMapEnum::class,
            WorkflowState::class,
            TicketStatus::class,
            IntStatusWithColor::class,
            OrderWorkflowStatus::class,
            InventoryStatus::class,
            NumericStatusCode::class,
            DetailedTicketStatus::class,
            EmptyDefaultsStatus::class,
            SingletonMode::class,
            IntPriority::class,
            MixedTicketType::class,
            PlainTestEnum::class,
            RequestState::class,
            PureSystemState::class,
            CamelCasePriority::class,
        ];

        foreach ($fixtures as $fixtureClass) {
            $select = $fixtureClass::forSelect();
            $caseCount = count($fixtureClass::cases());

n            expect($select)->toHaveCount(
                $caseCount,
                "{$fixtureClass}::forSelect() must have {$caseCount} entries"
            );
        }
    });

    /**
     * forApi() output must have 6 keys per entry and match cases count.
     */
    it('forApi() shape is consistent across all fixtures', function (): void {
        $expectedKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

        $fixtures = [
            UserStatus::class,
            IntBackedPriority::class,
            PureFeatureFlag::class,
            OrderStatus::class,
            Priority::class,
            AllClassLevelEnum::class,
            ZeroPriority::class,
            SingleCaseToggle::class,
            DefaultIconFeature::class,
            OverriddenIconRole::class,
            LabelMapEnum::class,
            CamelCaseRole::class,
            EdgeCaseNamingEnum::class,
        ];

        foreach ($fixtures as $fixtureClass) {
            $api = $fixtureClass::forApi();
            $caseCount = count($fixtureClass::cases());

            expect($api)->toHaveCount($caseCount);

            foreach ($api as $i => $entry) {
                expect($entry)->toHaveKeys($expectedKeys,
                    "{$fixtureClass}::forApi()[{$i}] must have keys: ".implode(', ', $expectedKeys)
                );
                // color must never be null or empty
                expect($entry['color'])->toBeString();
                expect($entry['color'])->not->toBeEmpty();
                // label must never be null or empty
                expect($entry['label'])->toBeString();
                expect($entry['label'])->not->toBeEmpty();
            }
        }
    });

    /**
     * All labels must be non-empty strings for every fixture.
     */
    it('all labels are non-empty strings across all fixtures', function (): void {
        $fixtures = [
            UserStatus::class,
            IntBackedPriority::class,
            PureFeatureFlag::class,
            OrderStatus::class,
            Priority::class,
            AllClassLevelEnum::class,
            CamelCaseRole::class,
            EdgeCaseNamingEnum::class,
            MixedAttributeStatus::class,
            PaymentStatus::class,
            SystemStatus::class,
            DefaultIconFeature::class,
            OverriddenIconRole::class,
            ZeroPriority::class,
            ZeroBackedPriority::class,
            SingleCaseToggle::class,
            LabelMapEnum::class,
            WorkflowState::class,
            TicketStatus::class,
            IntStatusWithColor::class,
            OrderWorkflowStatus::class,
            InventoryStatus::class,
            NumericStatusCode::class,
            DetailedTicketStatus::class,
            EmptyDefaultsStatus::class,
            SingletonMode::class,
            IntPriority::class,
            MixedTicketType::class,
            PlainTestEnum::class,
            RequestState::class,
            PureSystemState::class,
            CamelCasePriority::class,
        ];

        foreach ($fixtures as $fixtureClass) {
            foreach ($fixtureClass::cases() as $case) {
                $label = $case->label();
                expect($label)->toBeString()->not->toBeEmpty(
                    "{$fixtureClass}::{$case->name}->label() must be non-empty"
                );
            }
        }
    });

    /**
     * values() returns the correct type for each enum backing.
     */
    it('values() returns correct types for string-backed, int-backed, and pure enums', function (): void {
        // String-backed: all values are strings
        foreach (UserStatus::values() as $v) {
            expect($v)->toBeString();
        }

        // Int-backed: all values are ints
        foreach (Priority::values() as $v) {
            expect($v)->toBeInt();
        }
        foreach (IntBackedPriority::values() as $v) {
            expect($v)->toBeInt();
        }

        // Pure: values are case name strings
        $pureValues = PureFeatureFlag::values();
        $pureNames = array_map(static fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());
        expect($pureValues)->toBe($pureNames);
    });

    /**
     * forSelect values match values() for every fixture.
     */
    it('forSelect values match values() output', function (): void {
        $fixtures = [
            UserStatus::class,
            IntBackedPriority::class,
            PureFeatureFlag::class,
            Priority::class,
            ZeroPriority::class,
            CamelCaseRole::class,
        ];

        foreach ($fixtures as $fixtureClass) {
            $selectValues = array_column($fixtureClass::forSelect(), 'value');
            $rawValues = $fixtureClass::values();

            expect($selectValues)->toEqual($rawValues,
                "{$fixtureClass} forSelect values must match values() output"
            );
        }
    });

    /**
     * EnumMetadataResolver rejects non-enum classes.
     */
    it('EnumMetadataResolver throws LogicException for non-enum class', function (): void {
        expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))
            ->toThrow(\LogicException::class);
    });

    /**
     * Cached metadata is byte-identical on second resolve call.
     */
    it('resolve() returns identical metadata on consecutive calls', function (): void {
        EnumCache::getInstance()->setTtl(300);

        $first = EnumMetadataResolver::resolve(UserStatus::class);
        $second = EnumMetadataResolver::resolve(UserStatus::class);

        expect($first)->toBe($second); // strict identity (same array reference from cache)
    });

    /**
     * Zero-backed enums work correctly with all metadata methods.
     */
    it('zero-valued backed enums have correct metadata', function (): void {
        expect(ZeroPriority::NONE->toValue())->toBe(0);
        expect(ZeroPriority::NONE->label())->toBeString()->not->toBeEmpty();
        expect(ZeroPriority::NONE->color())->toBeString();
        expect(ZeroBackedPriority::ZERO->toValue())->toBe(0);
        expect(ZeroBackedPriority::ZERO->label())->toBeString()->not->toBeEmpty();
    });

    /**
     * Single-case enums work correctly.
     */
    it('single-case enums produce valid metadata', function (): void {
        expect(SingleCaseToggle::cases())->toHaveCount(1);
        expect(SingleCaseToggle::forSelect())->toHaveCount(1);
        expect(SingleCaseToggle::forApi())->toHaveCount(1);
        expect(SingleCaseToggle::ON->label())->toBeString()->not->toBeEmpty();
        expect(SingleCaseToggle::values())->toHaveCount(1);
        expect(SingleCaseToggle::labels())->toHaveCount(1);
    });

    /**
     * toValue() is consistent with forSelect values across all enum types.
     */
    it('toValue() is consistent with forSelect values', function (): void {
        $fixtures = [
            UserStatus::class,
            IntBackedPriority::class,
            PureFeatureFlag::class,
            ZeroPriority::class,
            CamelCaseRole::class,
        ];

        foreach ($fixtures as $fixtureClass) {
            $cases = $fixtureClass::cases();
            $select = $fixtureClass::forSelect();

            foreach ($cases as $i => $case) {
                expect($case->toValue())->toBe($select[$i]['value'],
                    "{$fixtureClass}::{$case->name}->toValue() must match forSelect value"
                );
            }
        }
    });
});
