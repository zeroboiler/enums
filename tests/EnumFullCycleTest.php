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
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * Full-cycle integration tests covering all three enum types,
 * attribute resolution priority, edge cases, and PHPStan compliance.
 */
describe('Enum full-cycle tests', function (): void {

    // -----------------------------------------------------------------
    // 1. Pure enum (no backing type)
    // -----------------------------------------------------------------
    describe('pure enum (RequestState)', function (): void {
        it('has no value property', function (): void {
            $case = RequestState::DRAFT;
            // Pure enums don't have ->value; accessing it would be a compile error.
            // Instead, verify the case exists and has a name.
            expect($case->name)->toBe('DRAFT');
        });

        it('auto-generates labels from case names', function (): void {
            expect(RequestState::DRAFT->label())->toBe('Draft');
            expect(RequestState::SUBMITTED->label())->toBe('Submitted');
            expect(RequestState::APPROVED->label())->toBe('Approved');
            expect(RequestState::REJECTED->label())->toBe('Rejected');
        });

        it('defaults color to secondary', function (): void {
            expect(RequestState::DRAFT->color())->toBe('secondary');
        });

        it('returns null for icon and description', function (): void {
            expect(RequestState::DRAFT->icon())->toBeNull();
            expect(RequestState::DRAFT->description())->toBeNull();
        });

        it('values() returns case names for pure enums', function (): void {
            $values = RequestState::values();
            expect($values)->toBe(['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED']);
        });

        it('forSelect() uses case names as values', function (): void {
            $options = RequestState::forSelect();
            expect($options[0])->toBe(['value' => 'DRAFT', 'label' => 'Draft']);
        });

        it('forApi() returns case names as values', function (): void {
            $api = RequestState::forApi();
            expect($api[0]['value'])->toBe('DRAFT');
            expect($api[0]['name'])->toBe('DRAFT');
            expect($api[0]['label'])->toBe('Draft');
            expect($api[0]['color'])->toBe('secondary');
            expect($api[0]['icon'])->toBeNull();
            expect($api[0]['description'])->toBeNull();
        });

        it('comparison methods work with pure enums', function (): void {
            expect(RequestState::DRAFT->is(RequestState::DRAFT))->toBeTrue();
            expect(RequestState::DRAFT->is('DRAFT'))->toBeTrue();
            expect(RequestState::DRAFT->is('DRAFT'))->toBeTrue();
            expect(RequestState::DRAFT->is(RequestState::SUBMITTED))->toBeFalse();
            expect(RequestState::DRAFT->is('SUBMITTED'))->toBeFalse();
            expect(RequestState::DRAFT->isNot(RequestState::SUBMITTED))->toBeTrue();
            expect(RequestState::DRAFT->in([RequestState::DRAFT, RequestState::SUBMITTED]))->toBeTrue();
            expect(RequestState::DRAFT->in(['DRAFT', 'SUBMITTED']))->toBeTrue();
        });

        it('lookup methods work with pure enums', function (): void {
            expect(RequestState::tryFromName('DRAFT'))->toBe(RequestState::DRAFT);
            expect(RequestState::tryFromName('draft'))->toBeNull(); // case-sensitive
            expect(RequestState::fromName('SUBMITTED'))->toBe(RequestState::SUBMITTED);
            expect(RequestState::hasCase('APPROVED'))->toBeTrue();
            expect(RequestState::hasCase('UNKNOWN'))->toBeFalse();
            expect(RequestState::tryFromLabel('Draft'))->toBe(RequestState::DRAFT);
            expect(RequestState::tryFromLabel('DRAFT'))->toBeNull(); // label ≠ name
        });

        it('fromName() throws InvalidEnumException for unknown case', function (): void {
            expect(fn (): mixed => RequestState::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class, 'Case name [NONEXISTENT] does not exist on enum');
        });

        it('labels() returns all labels in declaration order', function (): void {
            $labels = RequestState::labels();
            expect($labels)->toBe(['Draft', 'Submitted', 'Approved', 'Rejected']);
        });
    });

    // -----------------------------------------------------------------
    // 2. Int-backed enum with zero value
    // -----------------------------------------------------------------
    describe('int-backed enum with zero (ZeroPriority)', function (): void {
        it('zero value works correctly', function (): void {
            expect(ZeroPriority::NONE->value)->toBe(0);
            expect(ZeroPriority::NONE->label())->toBe('None');
        });

        it('values() includes zero', function (): void {
            expect(ZeroPriority::values())->toBe([0, 1, 2]);
        });

        it('forSelect() includes zero as value', function (): void {
            $options = ZeroPriority::forSelect();
            expect($options[0])->toBe(['value' => 0, 'label' => 'None']);
        });

        it('forApi() includes zero as value', function (): void {
            $api = ZeroPriority::forApi();
            expect($api[0]['value'])->toBe(0);
            expect($api[0]['name'])->toBe('NONE');
            expect($api[0]['label'])->toBe('None');
        });

        it('comparison works with zero-valued case', function (): void {
            expect(ZeroPriority::NONE->is(ZeroPriority::NONE))->toBeTrue();
            expect(ZeroPriority::NONE->is('NONE'))->toBeTrue();
            expect(ZeroPriority::NONE->isNot(ZeroPriority::LOW))->toBeTrue();
            expect(ZeroPriority::NONE->in([ZeroPriority::NONE, ZeroPriority::HIGH]))->toBeTrue();
        });

        it('lookup by name works with zero-valued case', function (): void {
            expect(ZeroPriority::tryFromName('NONE'))->toBe(ZeroPriority::NONE);
            expect(ZeroPriority::fromName('NONE'))->toBe(ZeroPriority::NONE);
            expect(ZeroPriority::hasCase('NONE'))->toBeTrue();
        });

        it('lookup by label works with zero-valued case', function (): void {
            expect(ZeroPriority::tryFromLabel('None'))->toBe(ZeroPriority::NONE);
        });
    });

    // -----------------------------------------------------------------
    // 3. Single-case enum
    // -----------------------------------------------------------------
    describe('single-case enum (SingleCaseEnum)', function (): void {
        it('has exactly one case', function (): void {
            expect(SingleCaseEnum::cases())->toHaveCount(1);
        });

        it('label is auto-generated', function (): void {
            expect(SingleCaseEnum::ONLY->label())->toBe('Only');
        });

        it('color defaults to secondary', function (): void {
            expect(SingleCaseEnum::ONLY->color())->toBe('secondary');
        });

        it('forSelect has one entry', function (): void {
            $options = SingleCaseEnum::forSelect();
            expect($options)->toHaveCount(1);
            expect($options[0])->toBe(['value' => 'only', 'label' => 'Only']);
        });

        it('forApi has one entry with full metadata', function (): void {
            $api = SingleCaseEnum::forApi();
            expect($api)->toHaveCount(1);
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('in() with single-element array', function (): void {
            expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
            expect(SingleCaseEnum::ONLY->in([]))->toBeFalse();
        });

        it('labels() has one entry', function (): void {
            expect(SingleCaseEnum::labels())->toBe(['Only']);
        });

        it('values() has one entry', function (): void {
            expect(SingleCaseEnum::values())->toBe(['only']);
        });
    });

    // -----------------------------------------------------------------
    // 4. All class-level attributes (EnumLabel + EnumDescription + EnumIcon)
    // -----------------------------------------------------------------
    describe('all class-level attributes (AllClassLevelEnum)', function (): void {
        it('resolves class-level labels', function (): void {
            expect(AllClassLevelEnum::OPEN->label())->toBe('Open Status');
            expect(AllClassLevelEnum::IN_PROGRESS->label())->toBe('In Progress');
            expect(AllClassLevelEnum::DONE->label())->toBe('Done');
        });

        it('resolves class-level descriptions', function (): void {
            expect(AllClassLevelEnum::OPEN->description())->toBe('Task is open');
            expect(AllClassLevelEnum::IN_PROGRESS->description())->toBe('Task is being worked on');
            expect(AllClassLevelEnum::DONE->description())->toBe('Task is complete');
        });

        it('resolves class-level icon for all cases', function (): void {
            expect(AllClassLevelEnum::OPEN->icon())->toBe('heroicon-o-circle');
            expect(AllClassLevelEnum::IN_PROGRESS->icon())->toBe('heroicon-o-circle');
            expect(AllClassLevelEnum::DONE->icon())->toBe('heroicon-o-circle');
        });

        it('color defaults to secondary when no EnumColor set', function (): void {
            expect(AllClassLevelEnum::OPEN->color())->toBe('secondary');
        });

        it('forApi() returns all class-level metadata', function (): void {
            $api = AllClassLevelEnum::forApi();
            expect($api)->toHaveCount(3);

            $open = $api[0];
            expect($open['label'])->toBe('Open Status');
            expect($open['description'])->toBe('Task is open');
            expect($open['icon'])->toBe('heroicon-o-circle');
            expect($open['color'])->toBe('secondary');
        });
    });

    // -----------------------------------------------------------------
    // 5. TicketStatus (EnumLabel + EnumDescription + EnumIcon combined)
    // -----------------------------------------------------------------
    describe('TicketStatus class-level metadata', function (): void {
        it('uses class-level EnumLabel for labels', function (): void {
            expect(TicketStatus::OPEN->label())->toBe('Open');
            expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
            expect(TicketStatus::CLOSED->label())->toBe('Closed');
        });

        it('uses class-level EnumDescription for descriptions', function (): void {
            expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
            expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
            // IN_PROGRESS has no description in EnumDescription
            expect(TicketStatus::IN_PROGRESS->description())->toBeNull();
        });

        it('uses class-level EnumIcon default icon', function (): void {
            expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');
            expect(TicketStatus::IN_PROGRESS->icon())->toBe('heroicon-o-ticket');
            expect(TicketStatus::CLOSED->icon())->toBe('heroicon-o-ticket');
        });

        it('tryFromLabel works with class-level labels', function (): void {
            expect(TicketStatus::tryFromLabel('Open'))->toBe(TicketStatus::OPEN);
            expect(TicketStatus::tryFromLabel('In Progress'))->toBe(TicketStatus::IN_PROGRESS);
            expect(TicketStatus::tryFromLabel('Closed'))->toBe(TicketStatus::CLOSED);
            expect(TicketStatus::tryFromLabel('Unknown'))->toBeNull();
        });
    });

    // -----------------------------------------------------------------
    // 6. CamelCase enum label generation
    // -----------------------------------------------------------------
    describe('camelCase label generation (CamelCaseRole)', function (): void {
        it('generates title case from camelCase', function (): void {
            expect(CamelCaseRole::isActive->label())->toBe('Is Active');
            expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
            expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
            expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
        });

        it('uses backed values for values() and forSelect()', function (): void {
            expect(CamelCaseRole::values())->toBe(['is_active', 'is_admin', 'is_moderator', 'is_banned']);
            expect(CamelCaseRole::forSelect()[0]['value'])->toBe('is_active');
            expect(CamelCaseRole::forSelect()[0]['label'])->toBe('Is Active');
        });

        it('forApi() uses backed values', function (): void {
            $api = CamelCaseRole::forApi();
            expect($api[0]['value'])->toBe('is_active');
            expect($api[0]['name'])->toBe('isActive');
            expect($api[0]['label'])->toBe('Is Active');
        });

        it('tryFromLabel works with generated labels', function (): void {
            expect(CamelCaseRole::tryFromLabel('Is Active'))->toBe(CamelCaseRole::isActive);
            expect(CamelCaseRole::tryFromLabel('is admin'))->toBe(CamelCaseRole::isAdmin); // case-insensitive
        });

        it('tryFromName works with camelCase names', function (): void {
            expect(CamelCaseRole::tryFromName('isActive'))->toBe(CamelCaseRole::isActive);
            expect(CamelCaseRole::tryFromName('IsAdmin'))->toBeNull(); // case-sensitive
        });
    });

    // -----------------------------------------------------------------
    // 7. Priority (int-backed) full-cycle
    // -----------------------------------------------------------------
    describe('Priority int-backed full-cycle', function (): void {
        it('all cases have correct values', function (): void {
            expect(Priority::LOW->value)->toBe(1);
            expect(Priority::MEDIUM->value)->toBe(2);
            expect(Priority::HIGH->value)->toBe(3);
            expect(Priority::URGENT->value)->toBe(4);
        });

        it('all labels are auto-generated', function (): void {
            expect(Priority::LOW->label())->toBe('Low');
            expect(Priority::MEDIUM->label())->toBe('Medium');
            expect(Priority::HIGH->label())->toBe('High');
            expect(Priority::URGENT->label())->toBe('Urgent');
        });

        it('colors default to secondary', function (): void {
            foreach (Priority::cases() as $case) {
                expect($case->color())->toBe('secondary');
            }
        });

        it('comparison with int-backed values', function (): void {
            expect(Priority::LOW->is(Priority::LOW))->toBeTrue();
            expect(Priority::LOW->is('LOW'))->toBeTrue();
            expect(Priority::LOW->isNot(Priority::HIGH))->toBeTrue();
            expect(Priority::LOW->in([Priority::LOW, Priority::MEDIUM]))->toBeTrue();
            expect(Priority::LOW->in(['LOW', 'MEDIUM']))->toBeTrue();
            expect(Priority::URGENT->in([Priority::LOW, Priority::MEDIUM]))->toBeFalse();
        });

        it('lookup methods', function (): void {
            expect(Priority::tryFromName('LOW'))->toBe(Priority::LOW);
            expect(Priority::fromName('HIGH'))->toBe(Priority::HIGH);
            expect(Priority::hasCase('URGENT'))->toBeTrue();
            expect(Priority::hasCase('CRITICAL'))->toBeFalse();
            expect(Priority::tryFromLabel('Low'))->toBe(Priority::LOW);
            expect(Priority::tryFromLabel('URGENT'))->toBeNull(); // label ≠ name
        });

        it('forApi() full metadata check', function (): void {
            $api = Priority::forApi();
            expect($api)->toHaveCount(4);
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['description'])->toBeNull();
                expect($item['icon'])->toBeNull();
                expect($item['color'])->toBe('secondary');
            }
        });
    });

    // -----------------------------------------------------------------
    // 8. UserStatus per-case override priority
    // -----------------------------------------------------------------
    describe('UserStatus resolution priority', function (): void {
        it('per-case Label overrides class-level and auto-generated', function (): void {
            // ACTIVE has #[Label('Active User')]
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
            // INACTIVE has no label → auto-generated
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
            // PENDING has #[Label('Awaiting Verification')]
            expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
        });

        it('per-case Color overrides class-level EnumColor', function (): void {
            // BANNED has #[Color('danger')] → overrides class-level
            expect(UserStatus::BANNED->color())->toBe('danger');
            // ACTIVE is in class-level success → uses class-level
            expect(UserStatus::ACTIVE->color())->toBe('success');
            // INACTIVE not in any color map → defaults to secondary
            expect(UserStatus::INACTIVE->color())->toBe('secondary');
        });

        it('per-case Icon overrides class-level', function (): void {
            // ACTIVE has #[Icon('heroicon-o-check-circle')]
            expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
            // Others have no icon
            expect(UserStatus::INACTIVE->icon())->toBeNull();
        });

        it('per-case Description overrides class-level', function (): void {
            expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
            expect(UserStatus::BANNED->description())->toBe('User is permanently banned');
            expect(UserStatus::INACTIVE->description())->toBeNull();
        });

        it('forApi() has correct per-case overrides', function (): void {
            $api = UserStatus::forApi();
            $active = $api[0];
            expect($active['label'])->toBe('Active User');
            expect($active['color'])->toBe('success');
            expect($active['icon'])->toBe('heroicon-o-check-circle');
            expect($active['description'])->toBe('User can fully access the system');

            $banned = $api[4];
            expect($banned['label'])->toBe('Banned'); // auto-generated (no per-case Label)
            expect($banned['color'])->toBe('danger'); // per-case override
            expect($banned['description'])->toBe('User is permanently banned');
        });
    });

    // -----------------------------------------------------------------
    // 9. OrderStatus (minimal, no attributes)
    // -----------------------------------------------------------------
    describe('OrderStatus minimal (no attributes)', function (): void {
        it('all metadata is auto-generated or default', function (): void {
            foreach (OrderStatus::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
                expect($case->color())->toBe('secondary');
                expect($case->icon())->toBeNull();
                expect($case->description())->toBeNull();
            }
        });

        it('forSelect() has correct structure', function (): void {
            $options = OrderStatus::forSelect();
            expect($options)->toHaveCount(4);
            foreach ($options as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
            }
        });

        it('values are backed string values', function (): void {
            expect(OrderStatus::values())->toBe(['pending', 'shipped', 'delivered', 'cancelled']);
        });
    });

    // -----------------------------------------------------------------
    // 10. Type safety / PHPStan compliance checks
    // -----------------------------------------------------------------
    describe('type safety (PHPStan level 9)', function (): void {
        it('label() always returns string', function (): void {
            $enums = [
                UserStatus::ACTIVE,
                Priority::LOW,
                RequestState::DRAFT,
                SingleCaseEnum::ONLY,
            ];
            foreach ($enums as $enum) {
                expect($enum->label())->toBeString();
            }
        });

        it('color() always returns string', function (): void {
            $enums = [UserStatus::ACTIVE, Priority::LOW, RequestState::DRAFT];
            foreach ($enums as $enum) {
                expect($enum->color())->toBeString();
            }
        });

        it('icon() returns string or null', function (): void {
            $withIcon = UserStatus::ACTIVE;
            $withoutIcon = UserStatus::INACTIVE;
            expect($withIcon->icon())->toBeString();
            expect($withoutIcon->icon())->toBeNull();
        });

        it('description() returns string or null', function (): void {
            $withDesc = UserStatus::ACTIVE;
            $withoutDesc = UserStatus::INACTIVE;
            expect($withDesc->description())->toBeString();
            expect($withoutDesc->description())->toBeNull();
        });

        it('forSelect() returns array with value and label keys', function (): void {
            $enums = [UserStatus::class, Priority::class, RequestState::class];
            foreach ($enums as $enumClass) {
                $options = $enumClass::forSelect();
                foreach ($options as $option) {
                    expect($option)->toBeArray();
                    expect($option)->toHaveKey('value');
                    expect($option)->toHaveKey('label');
                }
            }
        });

        it('forApi() returns array with all expected keys', function (): void {
            $api = UserStatus::forApi();
            foreach ($api as $item) {
                expect($item)->toBeArray();
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            }
        });

        it('is() and isNot() use strict identity comparison', function (): void {
            $status = UserStatus::ACTIVE;
            // Same instance
            expect($status->is($status))->toBeTrue();
            // Different instance, same case — enums are singletons in PHP
            expect($status->is(UserStatus::ACTIVE))->toBeTrue();
            // Different case
            expect($status->is(UserStatus::BANNED))->toBeFalse();
            // String comparison is case-sensitive
            expect($status->is('ACTIVE'))->toBeTrue();
            expect($status->is('Active'))->toBeFalse();
            expect($status->is('active'))->toBeFalse(); // value ≠ name
        });

        it('values() returns correct types', function (): void {
            // String-backed
            $stringValues = UserStatus::values();
            foreach ($stringValues as $v) {
                expect($v)->toBeString();
            }

            // Int-backed
            $intValues = Priority::values();
            foreach ($intValues as $v) {
                expect($v)->toBeInt();
            }

            // Pure enum (case names)
            $pureValues = RequestState::values();
            foreach ($pureValues as $v) {
                expect($v)->toBeString();
            }
        });
    });
});
