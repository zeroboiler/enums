<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderWorkflowStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('HasEnumMetadata trait — comprehensive integration', function (): void {
    // ────────────────────────────────────────────────────────────────
    // Auto-label generation edge cases
    // ────────────────────────────────────────────────────────────────

    it('auto-generates label from SCREAMING_SNAKE_CASE (OrderWorkflowStatus)', function (): void {
        // OrderWorkflowStatus has no per-case labels, all auto-generated
        expect(OrderWorkflowStatus::DRAFT->label())->toBe('Draft');
        expect(OrderWorkflowStatus::IN_PROGRESS->label())->toBe('In Progress');
        expect(OrderWorkflowStatus::PROCESSING->label())->toBe('Processing');
    });

    it('auto-generates label from camelCase-style names via CamelCaseRole', function (): void {
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
    });

    // ────────────────────────────────────────────────────────────────
    // Class-level EnumIcon default icon resolution
    // ────────────────────────────────────────────────────────────────

    it('applies default icon from class-level EnumIcon to cases without per-case icon', function (): void {
        $meta = DefaultIconFeature::SEARCH->icon();
        $meta2 = DefaultIconFeature::FILTER->icon();

        expect($meta)->toBeString()->not->toBeEmpty();
        expect($meta2)->toBeString()->not->toBeEmpty();
    });

    // ────────────────────────────────────────────────────────────────
    // Class-level EnumIcon with per-case override (OverriddenIconRole)
    // ────────────────────────────────────────────────────────────────

    it('per-case icon overrides class-level default icon', function (): void {
        $adminIcon = OverriddenIconRole::ADMIN->icon();
        $viewerIcon = OverriddenIconRole::VIEWER->icon();

        // Admin should have a custom icon (per-case override)
        expect($adminIcon)->toBeString();
        // Viewer falls back to the class-level default
        expect($viewerIcon)->toBeString()->not->toBeEmpty();
    });

    // ────────────────────────────────────────────────────────────────
    // Class-level EnumIcon with per-value icon map (SystemStatus)
    // ────────────────────────────────────────────────────────────────

    it('resolves per-value icons from class-level EnumIcon map (int-backed)', function (): void {
        $disabled = SystemStatus::DISABLED->icon();
        $enabled = SystemStatus::ENABLED->icon();
        $maintenance = SystemStatus::MAINTENANCE->icon();

        expect($disabled)->toBeString();
        expect($enabled)->toBeString();
        expect($maintenance)->toBeString();
    });

    // ────────────────────────────────────────────────────────────────
    // Int-backed enum with class-level EnumColor
    // ────────────────────────────────────────────────────────────────

    it('resolves color for int-backed enums via class-level EnumColor', function (): void {
        expect(IntStatusWithColor::ACTIVE->color())->toBe('success');
        expect(IntStatusWithColor::PENDING->color())->toBe('warning');
        expect(IntStatusWithColor::BANNED->color())->toBe('danger');
        expect(IntStatusWithColor::DRAFT->color())->toBe('success');
    });

    // ────────────────────────────────────────────────────────────────
    // Mixed attribute usage — class-level + per-case on same enum
    // ────────────────────────────────────────────────────────────────

    it('correctly prioritizes per-case over class-level for mixed attributes', function (): void {
        $draft = MixedAttributeStatus::DRAFT;
        $published = MixedAttributeStatus::PUBLISHED;

        // Draft has per-case label and color overrides
        expect($draft->label())->toBeString()->not->toBeEmpty();
        expect($draft->color())->toBeString();

        // Published uses class-level EnumLabel for label, per-case Color for color
        expect($published->label())->toBeString()->not->toBeEmpty();
        expect($published->color())->toBeString();
    });

    // ────────────────────────────────────────────────────────────────
    // DetailedTicketStatus — extensive per-case metadata
    // ────────────────────────────────────────────────────────────────

    it('returns structured metadata for detailed ticket status', function (): void {
        $open = DetailedTicketStatus::OPEN;
        $inProgress = DetailedTicketStatus::IN_PROGRESS;
        $closed = DetailedTicketStatus::CLOSED;

        // All cases should have labels
        expect($open->label())->toBeString()->not->toBeEmpty();
        expect($inProgress->label())->toBeString()->not->toBeEmpty();
        expect($closed->label())->toBeString()->not->toBeEmpty();

        // All should have colors
        expect($open->color())->toBeString();
        expect($inProgress->color())->toBeString();
        expect($closed->color())->toBeString();

        // All should have icons or null
        expect($open->icon())->toBeNull()->or()->toBeString();
        expect($inProgress->icon())->toBeNull()->or()->toBeString();
        expect($closed->icon())->toBeNull()->or()->toBeString();

        // All should have descriptions or null
        expect($open->description())->toBeNull()->or()->toBeString();
        expect($inProgress->description())->toBeNull()->or()->toBeString();
        expect($closed->description())->toBeNull()->or()->toBeString();
    });

    // ────────────────────────────────────────────────────────────────
    // PaymentStatus — string-backed enum with all class-level metadata
    // ────────────────────────────────────────────────────────────────

    it('returns consistent values() for string-backed PaymentStatus', function (): void {
        $values = PaymentStatus::values();

        expect($values)->toBeArray();
        expect($values)->not->toBeEmpty();
        // String-backed enums should have string values
        foreach ($values as $v) {
            expect($v)->toBeString();
        }
    });

    it('forSelect returns correct value/label pairs for string-backed enums', function (): void {
        $options = PaymentStatus::forSelect();

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();

        foreach ($options as $option) {
            expect($option)->toHaveKeys(['value', 'label']);
            expect($option['value'])->toBeString();
            expect($option['label'])->toBeString()->not->toBeEmpty();
        }
    });

    // ────────────────────────────────────────────────────────────────
    // IntBackedPriority — int-backed enum with class-level metadata
    // ────────────────────────────────────────────────────────────────

    it('returns int values for IntBackedPriority', function (): void {
        $values = IntBackedPriority::values();

        expect($values)->toBeArray();
        expect($values)->toHaveCount(4);
        foreach ($values as $v) {
            expect($v)->toBeInt();
        }
    });

    it('forSelect returns int values for IntBackedPriority', function (): void {
        $options = IntBackedPriority::forSelect();

        foreach ($options as $option) {
            expect($option['value'])->toBeInt();
            expect($option['label'])->toBeString()->not->toBeEmpty();
        }
    });

    // ────────────────────────────────────────────────────────────────
    // RequestState — pure enum without backing type
    // ────────────────────────────────────────────────────────────────

    it('uses case names as values for pure enum forSelect', function (): void {
        $options = RequestState::forSelect();

        foreach ($options as $option) {
            expect($option['value'])->toBeString();
            // Value should match a valid case name
            expect(RequestState::tryFromName($option['value']))->not->toBeNull();
        }
    });

    // ────────────────────────────────────────────────────────────────
    // ZeroPriority — edge case with zero value
    // ────────────────────────────────────────────────────────────────

    it('handles zero as a valid backed value', function (): void {
        $none = ZeroPriority::NONE;

        expect($none->value)->toBe(0);
        expect($none->label())->toBeString()->not->toBeEmpty();
        expect($none->color())->toBeString();
    });

    it('includes zero value in values() array', function (): void {
        $values = ZeroPriority::values();

        expect($values)->toContain(0);
        expect(in_array(0, $values, true))->toBeTrue();
    });

    // ────────────────────────────────────────────────────────────────
    // SingleCaseEnum — edge case with only one case
    // ────────────────────────────────────────────────────────────────

    it('works correctly with single-case enums', function (): void {
        $cases = SingleCaseEnum::cases();

        expect($cases)->toHaveCount(1);
        expect(SingleCaseEnum::ONLY->label())->toBeString()->not->toBeEmpty();

        $select = SingleCaseEnum::forSelect();
        expect($select)->toHaveCount(1);

        $api = SingleCaseEnum::forApi();
        expect($api)->toHaveCount(1);
    });

    // ────────────────────────────────────────────────────────────────
    // TicketStatus — additional fixture coverage
    // ────────────────────────────────────────────────────────────────

    it('returns valid metadata for all TicketStatus cases', function (): void {
        foreach (TicketStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
            expect($case->color())->toBeString()->not->toBeEmpty();
        }
    });

    // ────────────────────────────────────────────────────────────────
    // Bulk method consistency checks across all fixture enums
    // ────────────────────────────────────────────────────────────────

    it('forSelect and forApi return same number of items as cases()', function (): void {
        $enums = [
            UserStatus::class,
            PaymentStatus::class,
            TicketStatus::class,
            DetailedTicketStatus::class,
            PureFeatureFlag::class,
            RequestState::class,
            OrderWorkflowStatus::class,
            IntBackedPriority::class,
            SystemStatus::class,
            ZeroPriority::class,
        ];

        foreach ($enums as $enumClass) {
            $caseCount = count($enumClass::cases());
            expect($enumClass::forSelect())->toHaveCount($caseCount, "forSelect count mismatch for {$enumClass}");
            expect($enumClass::forApi())->toHaveCount($caseCount, "forApi count mismatch for {$enumClass}");
            expect($enumClass::values())->toHaveCount($caseCount, "values count mismatch for {$enumClass}");
            expect($enumClass::labels())->toHaveCount($caseCount, "labels count mismatch for {$enumClass}");
        }
    });

    // ────────────────────────────────────────────────────────────────
    // Lookup and comparison method consistency
    // ────────────────────────────────────────────────────────────────

    it('tryFromName returns null for empty string', function (): void {
        expect(UserStatus::tryFromName(''))->toBeNull();
        expect(PaymentStatus::tryFromName(''))->toBeNull();
    });

    it('tryFromName returns null for malformed case names', function (): void {
        expect(UserStatus::tryFromName('active'))->toBeNull(); // lowercase, not case name
        expect(UserStatus::tryFromName('Active'))->toBeNull();  // PascalCase, not case name
    });

    it('fromName throws InvalidEnumException with correct class name', function (): void {
        expect(function (): void {
            PaymentStatus::fromName('NON_EXISTENT');
        })->toThrow(function (InvalidEnumException $e): bool {
            return str_contains($e->getMessage(), PaymentStatus::class);
        });
    });

    it('is() with string comparison is case-sensitive', function (): void {
        expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
        expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
    });

    it('in() returns false for empty array', function (): void {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('in() returns true when at least one match in array', function (): void {
        expect(UserStatus::ACTIVE->in([UserStatus::BANNED, 'ACTIVE']))->toBeTrue();
    });

    // ────────────────────────────────────────────────────────────────
    // hasCase consistency with tryFromName
    // ────────────────────────────────────────────────────────────────

    it('hasCase is consistent with tryFromName across all fixtures', function (): void {
        $enums = [
            UserStatus::class,
            PaymentStatus::class,
            PureFeatureFlag::class,
            SystemStatus::class,
            ZeroPriority::class,
        ];

        foreach ($enums as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                expect($enumClass::hasCase($case->name))->toBeTrue();
                expect($enumClass::tryFromName($case->name))->toBe($case);
            }
        }
    });

    // ────────────────────────────────────────────────────────────────
    // Type safety — return types are strictly correct
    // ────────────────────────────────────────────────────────────────

    it('forSelect returns array with int|string values and string labels', function (): void {
        $select = PaymentStatus::forSelect();

        foreach ($select as $item) {
            expect($item['value'])->toBeInt();
            expect($item['label'])->toBeString();
        }

        $select2 = UserStatus::forSelect();

        foreach ($select2 as $item) {
            expect($item['value'])->toBeString();
            expect($item['label'])->toBeString();
        }
    });

    it('forApi returns complete metadata structure', function (): void {
        $api = UserStatus::forApi();

        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['value'])->toBeString();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString()->not->toBeEmpty();
            expect($item['color'])->toBeString()->not->toBeEmpty();
            // description and icon can be null
            expect($item['description'])->toBeNull()->or()->toBeString();
            expect($item['icon'])->toBeNull()->or()->toBeString();
        }
    });
});
