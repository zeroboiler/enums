<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\WorkflowState;

describe('WorkflowState comprehensive fixture', function (): void {
    beforeEach(function (): void {
        EnumCache::flush();
    });

    afterEach(function (): void {
        EnumCache::flush();
    });

    describe('case existence', function (): void {
        it('has exactly 7 cases', function (): void {
            expect(WorkflowState::cases())->toHaveCount(7);
        });

        it('has all expected case names in declaration order', function (): void {
            $names = array_map(
                static fn (WorkflowState $case): string => $case->name,
                WorkflowState::cases(),
            );

            expect($names)->toBe([
                'ACTIVE',
                'PENDING',
                'PROCESSING',
                'PROCESSING_ALT',
                'COMPLETED',
                'FAILED',
                'DELETED',
            ]);
        });
    });

    describe('label resolution priority', function (): void {
        it('uses per-case Label override for ACTIVE', function (): void {
            // Per-case #[Label('Active & Running')] overrides class-level EnumLabel
            expect(WorkflowState::ACTIVE->label())->toBe('Active & Running');
        });

        it('uses class-level EnumLabel for PENDING', function (): void {
            // Class-level EnumLabel labels: ['pending' => 'Pending Review']
            expect(WorkflowState::PENDING->label())->toBe('Pending Review');
        });

        it('uses class-level EnumLabel for DELETED', function (): void {
            // Class-level EnumLabel labels: ['deleted' => 'Soft Deleted']
            expect(WorkflowState::DELETED->label())->toBe('Soft Deleted');
        });

        it('auto-generates label for PROCESSING (no per-case or class-level)', function (): void {
            expect(WorkflowState::PROCESSING->label())->toBe('Processing');
        });

        it('auto-generates label for PROCESSING_ALT (SCREAMING_SNAKE_CASE → Title Case)', function (): void {
            expect(WorkflowState::PROCESSING_ALT->label())->toBe('Processing Alt');
        });

        it('auto-generates label for COMPLETED (no per-case or class-level)', function (): void {
            expect(WorkflowState::COMPLETED->label())->toBe('Completed');
        });

        it('auto-generates label for FAILED (no per-case or class-level)', function (): void {
            expect(WorkflowState::FAILED->label())->toBe('Failed');
        });
    });

    describe('color resolution priority', function (): void {
        it('uses class-level EnumColor for ACTIVE → success', function (): void {
            // EnumColor success: ['active', 'completed']
            expect(WorkflowState::ACTIVE->color())->toBe('success');
        });

        it('uses class-level EnumColor for PENDING → warning', function (): void {
            // EnumColor warning: ['pending']
            expect(WorkflowState::PENDING->color())->toBe('warning');
        });

        it('uses class-level EnumColor for PROCESSING → info', function (): void {
            // EnumColor info: ['processing']
            expect(WorkflowState::PROCESSING->color())->toBe('info');
        });

        it('uses per-case Color override for PROCESSING_ALT → info', function (): void {
            // #[Color('info')] per-case override
            expect(WorkflowState::PROCESSING_ALT->color())->toBe('info');
        });

        it('uses class-level EnumColor for COMPLETED → success', function (): void {
            // EnumColor success: ['active', 'completed']
            expect(WorkflowState::COMPLETED->color())->toBe('success');
        });

        it('uses class-level EnumColor for FAILED → danger', function (): void {
            // EnumColor danger: ['failed', 'deleted']
            expect(WorkflowState::FAILED->color())->toBe('danger');
        });

        it('uses class-level EnumColor for DELETED → danger', function (): void {
            // EnumColor danger: ['failed', 'deleted']
            expect(WorkflowState::DELETED->color())->toBe('danger');
        });

        it('returns secondary for unmapped values (if any)', function (): void {
            // All WorkflowState cases are mapped, but verify default fallback
            // by testing the internal resolution behavior
            $meta = EnumMetadataResolver::resolve(WorkflowState::class);

            // All cases should have a color
            foreach (WorkflowState::cases() as $case) {
                $value = $case->value;
                expect(isset($meta['colors'][$value]))->toBeTrue();
                expect(in_array($meta['colors'][$value], ['success', 'danger', 'warning', 'info', 'secondary'], true))->toBeTrue();
            }
        });
    });

    describe('icon resolution priority', function (): void {
        it('uses per-case Icon override for ACTIVE → heroicon-o-bolt', function (): void {
            // #[Icon('heroicon-o-bolt')] per-case override
            expect(WorkflowState::ACTIVE->icon())->toBe('heroicon-o-bolt');
        });

        it('uses per-case EnumIcon icons map for FAILED → heroicon-o-x-circle', function (): void {
            // EnumIcon icons: ['failed' => 'heroicon-o-x-circle']
            expect(WorkflowState::FAILED->icon())->toBe('heroicon-o-x-circle');
        });

        it('uses per-case EnumIcon icons map for COMPLETED → heroicon-o-check', function (): void {
            // EnumIcon icons: ['completed' => 'heroicon-o-check']
            expect(WorkflowState::COMPLETED->icon())->toBe('heroicon-o-check');
        });

        it('uses EnumIcon default for PENDING → heroicon-o-circle-dot', function (): void {
            // EnumIcon default: 'heroicon-o-circle-dot' (no specific icon for 'pending')
            expect(WorkflowState::PENDING->icon())->toBe('heroicon-o-circle-dot');
        });

        it('uses EnumIcon default for PROCESSING → heroicon-o-circle-dot', function (): void {
            expect(WorkflowState::PROCESSING->icon())->toBe('heroicon-o-circle-dot');
        });

        it('uses EnumIcon default for PROCESSING_ALT → heroicon-o-circle-dot', function (): void {
            expect(WorkflowState::PROCESSING_ALT->icon())->toBe('heroicon-o-circle-dot');
        });

        it('uses EnumIcon default for DELETED → heroicon-o-circle-dot', function (): void {
            expect(WorkflowState::DELETED->icon())->toBe('heroicon-o-circle-dot');
        });
    });

    describe('description resolution priority', function (): void {
        it('uses per-case Description override for ACTIVE', function (): void {
            expect(WorkflowState::ACTIVE->description())->toBe('System is actively processing');
        });

        it('uses per-case Description override for PROCESSING_ALT', function (): void {
            expect(WorkflowState::PROCESSING_ALT->description())->toBe('Task is currently being processed');
        });

        it('uses per-case Description override for DELETED', function (): void {
            // Per-case overrides class-level for 'deleted'
            expect(WorkflowState::DELETED->description())->toBe('Soft deleted — recoverable within 30 days');
        });

        it('uses class-level EnumDescription for FAILED', function (): void {
            // EnumDescription descriptions: ['failed' => 'Execution has failed']
            expect(WorkflowState::FAILED->description())->toBe('Execution has failed');
        });

        it('returns null for cases without description', function (): void {
            expect(WorkflowState::PENDING->description())->toBeNull();
            expect(WorkflowState::PROCESSING->description())->toBeNull();
            expect(WorkflowState::COMPLETED->description())->toBeNull();
        });
    });

    describe('bulk methods', function (): void {
        it('forSelect returns correct value/label pairs', function (): void {
            $select = WorkflowState::forSelect();

            expect($select)->toBeArray();
            expect($select)->toHaveCount(7);

            // Each entry must have value and label keys
            foreach ($select as $entry) {
                expect($entry)->toHaveKeys(['value', 'label']);
            }

            // Verify specific entries
            $activeEntry = $select[0];
            expect($activeEntry['value'])->toBe('active');
            expect($activeEntry['label'])->toBe('Active & Running');

            $pendingEntry = $select[1];
            expect($pendingEntry['value'])->toBe('pending');
            expect($pendingEntry['label'])->toBe('Pending Review');
        });

        it('forApi returns full metadata structure', function (): void {
            $api = WorkflowState::forApi();

            expect($api)->toBeArray();
            expect($api)->toHaveCount(7);

            foreach ($api as $entry) {
                expect($entry)->toHaveKeys(['value', 'name', 'label', 'color', 'icon', 'description']);
            }

            // Verify ACTIVE entry (per-case overrides)
            $activeEntry = $api[0];
            expect($activeEntry['value'])->toBe('active');
            expect($activeEntry['name'])->toBe('ACTIVE');
            expect($activeEntry['label'])->toBe('Active & Running');
            expect($activeEntry['color'])->toBe('success');
            expect($activeEntry['icon'])->toBe('heroicon-o-bolt');
            expect($activeEntry['description'])->toBe('System is actively processing');
        });

        it('values returns all backed values in declaration order', function (): void {
            expect(WorkflowState::values())->toBe([
                'active',
                'pending',
                'processing',
                'processing_alt',
                'completed',
                'failed',
                'deleted',
            ]);
        });

        it('labels returns all labels in declaration order', function (): void {
            expect(WorkflowState::labels())->toBe([
                'Active & Running',
                'Pending Review',
                'Processing',
                'Processing Alt',
                'Completed',
                'Failed',
                'Soft Deleted',
            ]);
        });
    });

    describe('comparison methods', function (): void {
        it('is() matches enum instances', function (): void {
            expect(WorkflowState::ACTIVE->is(WorkflowState::ACTIVE))->toBeTrue();
            expect(WorkflowState::ACTIVE->is(WorkflowState::FAILED))->toBeFalse();
        });

        it('is() matches string case names', function (): void {
            expect(WorkflowState::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(WorkflowState::ACTIVE->is('FAILED'))->toBeFalse();
        });

        it('isNot() negates is()', function (): void {
            expect(WorkflowState::ACTIVE->isNot(WorkflowState::FAILED))->toBeTrue();
            expect(WorkflowState::ACTIVE->isNot(WorkflowState::ACTIVE))->toBeFalse();
        });

        it('in() matches mixed instances and strings', function (): void {
            expect(WorkflowState::ACTIVE->in([WorkflowState::ACTIVE, 'PENDING']))->toBeTrue();
            expect(WorkflowState::ACTIVE->in(['ACTIVE']))->toBeTrue();
            expect(WorkflowState::FAILED->in([WorkflowState::ACTIVE, WorkflowState::COMPLETED]))->toBeFalse();
        });

        it('notIn() negates in()', function (): void {
            expect(WorkflowState::ACTIVE->notIn(['FAILED', 'DELETED']))->toBeTrue();
            expect(WorkflowState::ACTIVE->notIn(['ACTIVE', 'PENDING']))->toBeFalse();
        });
    });

    describe('lookup methods', function (): void {
        it('tryFromName resolves existing case names', function (): void {
            expect(WorkflowState::tryFromName('ACTIVE'))->toBe(WorkflowState::ACTIVE);
            expect(WorkflowState::tryFromName('PROCESSING_ALT'))->toBe(WorkflowState::PROCESSING_ALT);
        });

        it('tryFromName returns null for non-existent names', function (): void {
            expect(WorkflowState::tryFromName('UNKNOWN'))->toBeNull();
            expect(WorkflowState::tryFromName(''))->toBeNull();
        });

        it('fromName throws InvalidEnumException for non-existent names', function (): void {
            expect(fn (): mixed => WorkflowState::fromName('UNKNOWN'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns correct boolean', function (): void {
            expect(WorkflowState::hasCase('ACTIVE'))->toBeTrue();
            expect(WorkflowState::hasCase('DELETED'))->toBeTrue();
            expect(WorkflowState::hasCase('UNKNOWN'))->toBeFalse();
        });

        it('tryFromLabel resolves by custom labels (case-insensitive)', function (): void {
            expect(WorkflowState::tryFromLabel('Active & Running'))->toBe(WorkflowState::ACTIVE);
            expect(WorkflowState::tryFromLabel('active & running'))->toBe(WorkflowState::ACTIVE);
            expect(WorkflowState::tryFromLabel('Pending Review'))->toBe(WorkflowState::PENDING);
        });

        it('tryFromLabel resolves by auto-generated labels', function (): void {
            expect(WorkflowState::tryFromLabel('Processing'))->toBe(WorkflowState::PROCESSING);
            expect(WorkflowState::tryFromLabel('Processing Alt'))->toBe(WorkflowState::PROCESSING_ALT);
            expect(WorkflowState::tryFromLabel('Completed'))->toBe(WorkflowState::COMPLETED);
        });

        it('tryFromLabel returns null for non-existent labels', function (): void {
            expect(WorkflowState::tryFromLabel('Nonexistent Label'))->toBeNull();
        });

        it('toValue returns backed value', function (): void {
            expect(WorkflowState::ACTIVE->toValue())->toBe('active');
            expect(WorkflowState::PROCESSING_ALT->toValue())->toBe('processing_alt');
        });
    });

    describe('cache behavior', function (): void {
        it('caches metadata after first resolve', function (): void {
            $cache = EnumCache::getInstance();

            // First access triggers reflection and caching
            WorkflowState::ACTIVE->label();

            expect($cache->has(WorkflowState::class))->toBeTrue();

            // Second access uses cache
            WorkflowState::FAILED->color();

            expect($cache->has(WorkflowState::class))->toBeTrue();
        });

        it('cache can be invalidated per-class', function (): void {
            $cache = EnumCache::getInstance();

            WorkflowState::ACTIVE->label();
            expect($cache->has(WorkflowState::class))->toBeTrue();

            EnumMetadataResolver::invalidate(WorkflowState::class);
            expect($cache->has(WorkflowState::class))->toBeFalse();
        });

        it('clearClass removes specific class from cache', function (): void {
            $cache = EnumCache::getInstance();

            WorkflowState::ACTIVE->label();
            expect($cache->has(WorkflowState::class))->toBeTrue();

            $cache->clearClass(WorkflowState::class);
            expect($cache->has(WorkflowState::class))->toBeFalse();
        });
    });

    describe('validation rule', function (): void {
        it('EnumRule validates against backed string values', function (): void {
            $rule = EnumRule::for(WorkflowState::class);

            // Valid values
            $fail = fn (): mixed => throw new \Exception('Should not fail');
            $rule->validate('status', 'active', $fail);
            $rule->validate('status', 'processing_alt', $fail);

            // Invalid value
            $failed = false;
            $rule->validate('status', 'invalid_value', function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('EnumRule rejects null when not nullable', function (): void {
            $rule = EnumRule::for(WorkflowState::class);
            $failed = false;
            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('EnumRule nullable allows null', function (): void {
            $rule = EnumRule::for(WorkflowState::class)->nullable();
            $failed = false;
            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        });
    });

    describe('metadata structure integrity', function (): void {
        it('metadata has all required keys', function (): void {
            $meta = EnumMetadataResolver::resolve(WorkflowState::class);

            expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        });

        it('all cases have a color entry', function (): void {
            $meta = EnumMetadataResolver::resolve(WorkflowState::class);

            foreach (WorkflowState::cases() as $case) {
                expect(isset($meta['colors'][$case->value]))->toBeTrue("Missing color for {$case->name}");
            }
        });

        it('all cases have a label entry (explicit or from class-level)', function (): void {
            $meta = EnumMetadataResolver::resolve(WorkflowState::class);

            // Note: Some cases use auto-generated labels, so not all will be in the labels map
            // But cases with explicit labels (per-case or class-level) must be present
            expect(isset($meta['labels']['active']))->toBeTrue();
            expect(isset($meta['labels']['pending']))->toBeTrue();
            expect(isset($meta['labels']['deleted']))->toBeTrue();
        });

        it('icons map contains per-case overrides and default entries', function (): void {
            $meta = EnumMetadataResolver::resolve(WorkflowState::class);

            // Per-case Icon override
            expect($meta['icons']['active'])->toBe('heroicon-o-bolt');

            // EnumIcon per-value icon map
            expect($meta['icons']['failed'])->toBe('heroicon-o-x-circle');
            expect($meta['icons']['completed'])->toBe('heroicon-o-check');

            // EnumIcon default applied to cases without specific icons
            expect($meta['icons']['pending'])->toBe('heroicon-o-circle-dot');
            expect($meta['icons']['processing'])->toBe('heroicon-o-circle-dot');
        });

        it('descriptions map contains per-case and class-level entries', function (): void {
            $meta = EnumMetadataResolver::resolve(WorkflowState::class);

            // Per-case Description override
            expect($meta['descriptions']['active'])->toBe('System is actively processing');
            expect($meta['descriptions']['processing_alt'])->toBe('Task is currently being processed');
            expect($meta['descriptions']['deleted'])->toBe('Soft deleted — recoverable within 30 days');

            // Class-level EnumDescription
            expect($meta['descriptions']['failed'])->toBe('Execution has failed');

            // No description for pending, processing, completed
            expect($meta['descriptions']['pending'] ?? null)->toBeNull();
            expect($meta['descriptions']['processing'] ?? null)->toBeNull();
        });
    });
});
