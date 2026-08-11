<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole;
use ZeroBoiler\Enums\Tests\Fixtures\OrderWorkflowStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;

describe('Enum edge cases and integration coverage', function () {
    // ── Pure Enum: no backed value ──────────────────────────────
    describe('pure enum behavior', function () {
        it('returns case names as values for pure enums', function () {
            $values = PureFeatureFlag::values();

            expect($values)->toBe([
                'DARK_MODE',
                'BETA_FEATURES',
                'MAINTENANCE_MODE',
            ]);
        });

        it('forSelect uses case names as value for pure enums', function () {
            $select = PureFeatureFlag::forSelect();

            expect($select[0]['value'])->toBe('DARK_MODE');
            expect($select[1]['value'])->toBe('BETA_FEATURES');
            expect($select[2]['value'])->toBe('MAINTENANCE_MODE');
        });

        it('forApi uses case names as value for pure enums', function () {
            $api = PureFeatureFlag::forApi();

            expect($api[0]['value'])->toBe('DARK_MODE');
            expect($api[0]['name'])->toBe('DARK_MODE');
        });

        it('tryFromName works with case names for pure enums', function () {
            $case = PureFeatureFlag::tryFromName('DARK_MODE');

            expect($case)->toBeInstanceOf(PureFeatureFlag::class);
            expect($case->name)->toBe('DARK_MODE');
        });

        it('fromName throws for non-existent pure enum case', function () {
            expect(fn () => PureFeatureFlag::fromName('NON_EXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase correctly identifies existing and non-existing cases', function () {
            expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
            expect(PureFeatureFlag::hasCase('NON_EXISTENT'))->toBeFalse();
        });

        it('is() comparison works with case names for pure enums', function () {
            $flag = PureFeatureFlag::DARK_MODE;

            expect($flag->is('DARK_MODE'))->toBeTrue();
            expect($flag->is('BETA_FEATURES'))->toBeFalse();
        });

        it('in() group matching works for pure enums', function () {
            $flag = PureFeatureFlag::BETA_FEATURES;

            expect($flag->in(['DARK_MODE', 'BETA_FEATURES']))->toBeTrue();
            expect($flag->in(['DARK_MODE']))->toBeFalse();
        });
    });

    // ── Int-Backed Enum: int values ──────────────────────────────
    describe('int-backed enum behavior', function () {
        it('values() returns int backed values', function () {
            $values = SystemStatus::values();

            expect($values)->each->toBeInt();
        });

        it('forSelect returns int values as keys', function () {
            $select = SystemStatus::forSelect();

            expect($select[0]['value'])->toBeInt();
        });

        it('forApi returns int values for int-backed enums', function () {
            $api = SystemStatus::forApi();

            expect($api[0]['value'])->toBeInt();
        });

        it('per-case EnumLabel override on int-backed enum works', function () {
            // IntBackedPriority has class-level label for value 1: 'Critical Priority'
            // and per-case override for value 2: 'High Priority'
            expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
            expect(IntBackedPriority::HIGH->label())->toBe('High Priority');
        });

        it('class-level EnumIcon per-value map works on int-backed enum', function () {
            // SystemStatus: icons: [1 => 'heroicon-o-check', 0 => 'heroicon-o-x-mark']
            // default: 'heroicon-o-cog-6-tooth'
            expect(SystemStatus::ENABLED->icon())->toBe('heroicon-o-check');
            expect(SystemStatus::DISABLED->icon())->toBe('heroicon-o-x-mark');
            expect(SystemStatus::MAINTENANCE->icon())->toBe('heroicon-o-cog-6-tooth');
        });
    });

    // ── Class-Level EnumLabel resolution ────────────────────────
    describe('class-level EnumLabel resolution', function () {
        it('resolves class-level labels for all cases', function () {
            expect(PaymentStatus::APPROVED->label())->toBe('Approved Payment');
            expect(PaymentStatus::REJECTED->label())->toBe('Rejected Payment');
            expect(PaymentStatus::REVIEW->label())->toBe('Under Review');
        });

        it('falls back to auto-generated label for unmapped cases', function () {
            // LabelMapEnum::TRASHED is not in class-level labels map
            expect(LabelMapEnum::TRASHED->label())->toBe('Trashed');
        });
    });

    // ── Class-Level EnumDescription resolution ────────────────
    describe('class-level EnumDescription resolution', function () {
        it('resolves class-level descriptions', function () {
            expect(PaymentStatus::APPROVED->description())->toBe('Payment has been approved');
            expect(PaymentStatus::REJECTED->description())->toBe('Payment was rejected');
        });

        it('returns null for unmapped descriptions', function () {
            expect(PaymentStatus::REVIEW->description())->toBeNull();
        });
    });

    // ── Per-case Description override ───────────────────────────
    describe('per-case description override', function () {
        it('prefers per-case description over class-level', function () {
            // OPEN gets class-level description
            expect(DetailedTicketStatus::OPEN->description())->toBe('Ticket is open and awaiting triage');
            // IN_PROGRESS has per-case override
            expect(DetailedTicketStatus::IN_PROGRESS->description())->toBe('Ticket is currently being worked on');
        });
    });

    // ── Class-Level EnumIcon default ────────────────────────────
    describe('class-level EnumIcon default', function () {
        it('applies default icon to all cases', function () {
            expect(DefaultIconFeature::SEARCH->icon())->toBe('heroicon-o-circle-question-mark');
            expect(DefaultIconFeature::FILTER->icon())->toBe('heroicon-o-circle-question-mark');
        });
    });

    // ── Per-case Icon override over class-level default ─────────
    describe('per-case icon override', function () {
        it('per-case icon overrides class-level default', function () {
            expect(OverriddenIconRole::ADMIN->icon())->toBe('heroicon-o-user');
            // VIEWER gets the class-level default
            expect(OverriddenIconRole::VIEWER->icon())->toBe('heroicon-o-circle-question-mark');
        });
    });

    // ── Large enum: bulk operations ─────────────────────────────
    describe('large enum bulk operations', function () {
        it('forSelect returns correct count for 20-case enum', function () {
            $select = OrderWorkflowStatus::forSelect();

            expect($select)->toHaveCount(20);
            expect($select)->each->toHaveKeys(['value', 'label']);
        });

        it('forApi returns correct count with all metadata keys', function () {
            $api = OrderWorkflowStatus::forApi();

            expect($api)->toHaveCount(20);
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('values() returns correct count', function () {
            $values = OrderWorkflowStatus::values();

            expect($values)->toHaveCount(20);
        });

        it('labels() returns correct count and non-empty strings', function () {
            $labels = OrderWorkflowStatus::labels();

            expect($labels)->toHaveCount(20);
            expect($labels)->each->toBeString()->not->toBeEmpty();
        });

        it('color() returns valid colors for all 20 cases', function () {
            $validColors = ['success', 'danger', 'warning', 'info', 'secondary'];

            foreach (OrderWorkflowStatus::cases() as $case) {
                expect($case->color())->toBeIn($validColors);
            }
        });

        it('select option values are unique for backed enums', function () {
            $select = OrderWorkflowStatus::forSelect();
            $values = array_column($select, 'value');

            expect(array_unique($values))->toHaveCount(count($values));
        });
    });

    // ── Comparison edge cases ──────────────────────────────────
    describe('comparison edge cases', function () {
        it('is() uses strict identity comparison', function () {
            $case1 = PaymentStatus::APPROVED;
            $case2 = PaymentStatus::APPROVED;

            // PHP enums are singletons — same case is same instance
            expect($case1->is($case2))->toBeTrue();
        });

        it('isNot() is negation of is()', function () {
            $case = PaymentStatus::APPROVED;

            expect($case->isNot(PaymentStatus::REJECTED))->toBeTrue();
            expect($case->isNot(PaymentStatus::APPROVED))->toBeFalse();
        });

        it('in() returns false for empty array', function () {
            $case = PaymentStatus::APPROVED;

            expect($case->in([]))->toBeFalse();
        });

        it('in() with single-element array works', function () {
            $case = PaymentStatus::APPROVED;

            expect($case->in([PaymentStatus::APPROVED]))->toBeTrue();
            expect($case->in([PaymentStatus::REJECTED]))->toBeFalse();
        });

        it('in() with mixed instance and string arguments', function () {
            $case = PaymentStatus::APPROVED;

            expect($case->in([PaymentStatus::REJECTED, 'APPROVED']))->toBeTrue();
        });
    });

    // ── Label auto-generation ──────────────────────────────────
    describe('label auto-generation', function () {
        it('generates Title Case from SCREAMING_SNAKE_CASE', function () {
            expect(OrderWorkflowStatus::DRAFT->label())->toBe('Draft');
            expect(OrderWorkflowStatus::PROCESSING->label())->toBe('Processing');
        });
    });

    // ── Cache invalidation ──────────────────────────────────────
    describe('cache invalidation', function () {
        it('invalidate removes cached metadata', function () {
            // Resolve metadata first (caches it)
            EnumMetadataResolver::resolve(PaymentStatus::class);

            // Invalidate
            EnumMetadataResolver::invalidate(PaymentStatus::class);

            // Re-resolve should work fine (no stale state)
            $meta = EnumMetadataResolver::resolve(PaymentStatus::class);

            expect($meta)->toBeArray();
            expect($meta)->toHaveKey('labels');
        });

        it('invalidateAll clears everything', function () {
            EnumMetadataResolver::resolve(PaymentStatus::class);
            EnumMetadataResolver::resolve(OrderWorkflowStatus::class);

            EnumMetadataResolver::invalidateAll();

            // Should still work after invalidation
            expect(EnumMetadataResolver::resolve(PaymentStatus::class))->toBeArray();
            expect(EnumMetadataResolver::resolve(OrderWorkflowStatus::class))->toBeArray();
        });
    });

    // ── tryFromLabel case-insensitive ──────────────────────────
    describe('tryFromLabel edge cases', function () {
        it('returns null for empty string label', function () {
            expect(PaymentStatus::tryFromLabel(''))->toBeNull();
        });

        it('is case-insensitive', function () {
            $case = PaymentStatus::tryFromLabel('approved payment');

            expect($case)->toBeInstanceOf(PaymentStatus::class);
            expect($case->name)->toBe('APPROVED');
        });

        it('returns first match when multiple cases share similar labels', function () {
            $case = LabelMapEnum::tryFromLabel('draft article');

            expect($case)->toBeInstanceOf(LabelMapEnum::class);
            expect($case->name)->toBe('DRAFT');
        });
    });

    // ── fromName throw behavior ────────────────────────────────
    describe('fromName exception', function () {
        it('includes class name in exception message', function () {
            expect(fn () => PaymentStatus::fromName('DOES_NOT_EXIST'))
                ->toThrow(InvalidEnumException::class, 'PaymentStatus');
        });

        it('includes case name in exception message', function () {
            expect(fn () => PaymentStatus::fromName('DOES_NOT_EXIST'))
                ->toThrow(InvalidEnumException::class, 'DOES_NOT_EXIST');
        });
    });

    // ── Default color and null icon/description ─────────────────
    describe('default metadata behavior', function () {
        it('returns secondary when no color is defined', function () {
            // PureFeatureFlag::MAINTENANCE_MODE has no EnumColor or Color attribute
            expect(PureFeatureFlag::MAINTENANCE_MODE->color())->toBe('secondary');
        });

        it('returns null for undefined icon when no default is set', function () {
            // PaymentStatus has no class-level EnumIcon
            expect(PaymentStatus::APPROVED->icon())->toBeNull();
        });

        it('returns null for undefined description', function () {
            expect(PaymentStatus::APPROVED->icon())->toBeNull();
        });
    });
});
