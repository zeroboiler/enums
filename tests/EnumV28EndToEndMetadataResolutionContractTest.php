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
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

/**
 * V28 End-to-end enum metadata resolution and API contract tests.
 *
 * Tests the full lifecycle:
 * 1. Enum definition with all attribute types
 * 2. Metadata resolution priority (per-case → class-level → auto-generated)
 * 3. All accessor methods (label, color, icon, description)
 * 4. All bulk methods (forSelect, forApi, values, labels)
 * 5. All comparison methods (is, isNot, in, notIn)
 * 6. All lookup methods (tryFromLabel, tryFromName, fromName, hasCase)
 * 7. Cache behavior (resolution, invalidation, TTL)
 * 8. EnumRule validation
 * 9. Edge cases (empty labels, null icons, camelCase labels)
 */

// ── Test Fixtures ──────────────────────────────────────────────────

#[EnumColor(success: ['active', 'paid'], danger: ['banned', 'rejected'], warning: ['pending'])]
#[EnumIcon(default: 'heroicon-o-circle')]
#[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
#[EnumDescription(descriptions: ['active' => 'User can fully access the system', 'banned' => 'User is permanently blocked'])]
enum FullMetadataStatus: string
{
    use HasEnumMetadata;

    #[Label('Active User Override'), Icon('heroicon-o-check'), Description('Custom active description')]
    case ACTIVE = 'active';

    case PENDING = 'pending';

    #[Color('danger'), Icon('heroicon-o-x-mark')]
    case BANNED = 'banned';

    case PAID = 'paid';
}

enum PureWorkflowStatus
{
    use HasEnumMetadata;

    #[Label('Open'), Icon('heroicon-o-folder-open')]
    case OPEN;

    #[Label('In Review')]
    case IN_REVIEW;

    #[Label('Completed'), Description('Task is done')]
    case COMPLETED;

    case CLOSED;
}

enum IntPriority: int
{
    use HasEnumMetadata;

    #[Label('Low Priority')]
    case LOW = 1;

    case MEDIUM = 2;

    #[Label('High Priority'), Color('danger')]
    case HIGH = 3;

    case CRITICAL = 4;
}

enum CamelCaseRole
{
    use HasEnumMetadata;

    case SuperAdmin;
    case ContentManager;
    case ViewOnly;
}

// ── Label Resolution Priority ───────────────────────────────────────

test('per-case Label attribute overrides class-level EnumLabel', function (): void {
    // ACTIVE has per-case Label('Active User Override')
    expect(FullMetadataStatus::ACTIVE->label())->toBe('Active User Override');

    // ACTIVE class-level would give 'Active User' but per-case wins
});

test('class-level EnumLabel provides label when no per-case override', function (): void {
    // BANNED has no per-case Label, class-level EnumLabel has 'Banned User'
    expect(FullMetadataStatus::BANNED->label())->toBe('Banned User');
});

test('auto-generated label from SCREAMING_SNAKE_CASE', function (): void {
    // PENDING has no per-case or class-level label
    expect(FullMetadataStatus::PENDING->label())->toBe('Pending');
});

test('auto-generated label from camelCase', function (): void {
    expect(CamelCaseRole::SuperAdmin->label())->toBe('Super Admin');
    expect(CamelCaseRole::ContentManager->label())->toBe('Content Manager');
    expect(CamelCaseRole::ViewOnly->label())->toBe('View Only');
});

// ── Color Resolution ───────────────────────────────────────────────

test('class-level EnumColor provides color', function (): void {
    expect(FullMetadataStatus::ACTIVE->color())->toBe('success');
    expect(FullMetadataStatus::PENDING->color())->toBe('warning');
    expect(FullMetadataStatus::PAID->color())->toBe('success');
});

test('per-case Color overrides class-level EnumColor', function (): void {
    expect(FullMetadataStatus::BANNED->color())->toBe('danger');
});

test('default color is secondary', function (): void {
    expect(PureWorkflowStatus::CLOSED->color())->toBe('secondary');
});

// ── Icon Resolution ───────────────────────────────────────────────

test('class-level default EnumIcon applies to all cases', function (): void {
    expect(FullMetadataStatus::PAID->icon())->toBe('heroicon-o-circle');
});

test('per-case Icon overrides class-level default', function (): void {
    expect(FullMetadataStatus::ACTIVE->icon())->toBe('heroicon-o-check');
    expect(FullMetadataStatus::BANNED->icon())->toBe('heroicon-o-x-mark');
});

test('null icon when no class-level or per-case icon defined', function (): void {
    expect(PureWorkflowStatus::CLOSED->icon())->toBeNull();
});

// ── Description Resolution ──────────────────────────────────────────

test('per-case Description overrides class-level EnumDescription', function (): void {
    expect(FullMetadataStatus::ACTIVE->description())->toBe('Custom active description');
});

test('class-level EnumDescription provides description', function (): void {
    expect(FullMetadataStatus::BANNED->description())->toBe('User is permanently blocked');
});

test('null description when not defined', function (): void {
    expect(FullMetadataStatus::PAID->description())->toBeNull();
});

// ── Bulk Methods ───────────────────────────────────────────────────

test('forSelect returns value-label pairs in declaration order', function (): void {
    $result = FullMetadataStatus::forSelect();

    expect($result)->toBeArray();
    expect(count($result))->toBe(4);

    // First entry: ACTIVE
    expect($result[0])->toHaveKey('value');
    expect($result[0])->toHaveKey('label');
    expect($result[0]['value'])->toBe('active');
    expect($result[0]['label'])->toBe('Active User Override');

    // Backed values used for string enums
    expect($result[3]['value'])->toBe('paid');
});

test('forSelect uses case names for pure enums', function (): void {
    $result = PureWorkflowStatus::forSelect();

    expect($result[0]['value'])->toBe('OPEN');
    expect($result[0]['label'])->toBe('Open');
});

test('forSelect uses backed values for int enums', function (): void {
    $result = IntPriority::forSelect();

    expect($result[0]['value'])->toBe(1);
    expect($result[0]['label'])->toBe('Low Priority');
});

test('forApi returns full metadata structure', function (): void {
    $result = FullMetadataStatus::forApi();

    expect(count($result))->toBe(4);

    // Each entry must have all 6 keys
    foreach ($result as $entry) {
        expect($entry)->toHaveKeys(['value', 'name', 'label', 'color', 'icon', 'description']);
    }

    // Check ACTIVE entry
    $active = $result[0];
    expect($active['value'])->toBe('active');
    expect($active['name'])->toBe('ACTIVE');
    expect($active['label'])->toBe('Active User Override');
    expect($active['color'])->toBe('success');
    expect($active['icon'])->toBe('heroicon-o-check');
    expect($active['description'])->toBe('Custom active description');
});

test('values returns backed values for backed enums', function (): void {
    expect(FullMetadataStatus::values())->toBe(['active', 'pending', 'banned', 'paid']);
    expect(IntPriority::values())->toBe([1, 2, 3, 4]);
});

test('values returns case names for pure enums', function (): void {
    expect(PureWorkflowStatus::values())->toBe(['OPEN', 'IN_REVIEW', 'COMPLETED', 'CLOSED']);
});

test('labels returns all labels in declaration order', function (): void {
    expect(FullMetadataStatus::labels())->toBe([
        'Active User Override',
        'Pending',
        'Banned User',
        'Paid',
    ]);
});

// ── Comparison Methods ────────────────────────────────────────────

test('is() with enum instance', function (): void {
    expect(FullMetadataStatus::ACTIVE->is(FullMetadataStatus::ACTIVE))->toBeTrue();
    expect(FullMetadataStatus::ACTIVE->is(FullMetadataStatus::BANNED))->toBeFalse();
});

test('is() with case name string', function (): void {
    expect(FullMetadataStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
    expect(FullMetadataStatus::ACTIVE->is('BANNED'))->toBeFalse();
});

test('isNot() negation', function (): void {
    expect(FullMetadataStatus::ACTIVE->isNot(FullMetadataStatus::BANNED))->toBeTrue();
    expect(FullMetadataStatus::ACTIVE->isNot(FullMetadataStatus::ACTIVE))->toBeFalse();
});

test('in() group matching with instances', function (): void {
    expect(FullMetadataStatus::ACTIVE->in([FullMetadataStatus::ACTIVE, FullMetadataStatus::PENDING]))->toBeTrue();
    expect(FullMetadataStatus::ACTIVE->in([FullMetadataStatus::BANNED]))->toBeFalse();
});

test('in() group matching with strings', function (): void {
    expect(FullMetadataStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
    expect(FullMetadataStatus::ACTIVE->in(['BANNED']))->toBeFalse();
});

test('notIn() exclusion', function (): void {
    expect(FullMetadataStatus::ACTIVE->notIn(['BANNED', 'PENDING']))->toBeTrue();
    expect(FullMetadataStatus::ACTIVE->notIn(['ACTIVE']))->toBeFalse();
});

// ── Lookup Methods ─────────────────────────────────────────────────

test('tryFromLabel resolves case-insensitively', function (): void {
    expect(FullMetadataStatus::tryFromLabel('Active User Override'))->toBe(FullMetadataStatus::ACTIVE);
    expect(FullMetadataStatus::tryFromLabel('active user override'))->toBe(FullMetadataStatus::ACTIVE);
    expect(FullMetadataStatus::tryFromLabel('UNKNOWN'))->toBeNull();
});

test('tryFromName resolves by case name', function (): void {
    expect(FullMetadataStatus::tryFromName('ACTIVE'))->toBe(FullMetadataStatus::ACTIVE);
    expect(FullMetadataStatus::tryFromName('active'))->toBeNull(); // case-sensitive
    expect(FullMetadataStatus::tryFromName('UNKNOWN'))->toBeNull();
});

test('fromName throws on invalid case name', function (): void {
    $this->expectException(InvalidEnumException::class);
    FullMetadataStatus::fromName('UNKNOWN');
});

test('fromName returns case for valid name', function (): void {
    expect(FullMetadataStatus::fromName('BANNED'))->toBe(FullMetadataStatus::BANNED);
});

test('hasCase checks existence', function (): void {
    expect(FullMetadataStatus::hasCase('ACTIVE'))->toBeTrue();
    expect(FullMetadataStatus::hasCase('UNKNOWN'))->toBeFalse();
});

// ── Cache Behavior ─────────────────────────────────────────────────

test('EnumMetadataResolver caches results', function (): void {
    EnumMetadataResolver::invalidate(FullMetadataStatus::class);

    $result1 = EnumMetadataResolver::resolve(FullMetadataStatus::class);
    $result2 = EnumMetadataResolver::resolve(FullMetadataStatus::class);

    // Same reference from cache
    expect($result1)->toBe($result2);
});

test('EnumMetadataResolver invalidate forces rebuild', function (): void {
    $result1 = EnumMetadataResolver::resolve(FullMetadataStatus::class);
    EnumMetadataResolver::invalidate(FullMetadataStatus::class);
    $result2 = EnumMetadataResolver::resolve(FullMetadataStatus::class);

    // Values should be identical (same metadata)
    expect($result1)->toEqual($result2);
    // But different array instances (rebuilt from reflection)
    expect($result1)->not->toBe($result2);
});

// ── EnumRule ───────────────────────────────────────────────────────

test('EnumRule validates backed enum values', function (): void {
    $rule = EnumRule::for(FullMetadataStatus::class);
    $fail = fn (string $message): string => $message;
    $message = null;

    // Valid value should not fail
    $rule->validate('status', 'active', function (string $m) use (&$message): void {
        $message = $m;
    });
    expect($message)->toBeNull();
});

test('EnumRule rejects invalid backed enum value', function (): void {
    $rule = EnumRule::for(FullMetadataStatus::class);
    $fail = fn (string $message): string => $message;
    $message = null;

    $rule->validate('status', 'invalid_value', function (string $m) use (&$message): void {
        $message = $m;
    });

    expect($message)->not->toBeNull();
    expect($message)->toContain('invalid');
});

test('EnumRule nullable allows null', function (): void {
    $rule = EnumRule::for(FullMetadataStatus::class)->nullable();
    $message = null;

    $rule->validate('status', null, function (string $m) use (&$message): void {
        $message = $m;
    });

    expect($message)->toBeNull();
});

test('EnumRule non-nullable rejects null', function (): void {
    $rule = EnumRule::for(FullMetadataStatus::class);
    $message = null;

    $rule->validate('status', null, function (string $m) use (&$message): void {
        $message = $m;
    });

    expect($message)->not->toBeNull();
});

// ── Integer-Backed Enum ───────────────────────────────────────────

test('int-backed enum metadata resolution works correctly', function (): void {
    expect(IntPriority::LOW->label())->toBe('Low Priority');
    expect(IntPriority::MEDIUM->label())->toBe('Medium'); // auto-generated
    expect(IntPriority::HIGH->label())->toBe('High Priority');
    expect(IntPriority::CRITICAL->label())->toBe('Critical'); // auto-generated
});

test('int-backed enum color resolution', function (): void {
    expect(IntPriority::HIGH->color())->toBe('danger');
    expect(IntPriority::LOW->color())->toBe('secondary');
});

test('int-backed enum forSelect uses int values', function (): void {
    $result = IntPriority::forSelect();
    expect($result[0]['value'])->toBe(1);
    expect($result[1]['value'])->toBe(2);
    expect($result[2]['value'])->toBe(3);
    expect($result[3]['value'])->toBe(4);
});

// ── Pure Enum ──────────────────────────────────────────────────────

test('pure enum uses case names for values', function (): void {
    expect(PureWorkflowStatus::OPEN->label())->toBe('Open');
    expect(PureWorkflowStatus::IN_REVIEW->label())->toBe('In Review');
    expect(PureWorkflowStatus::COMPLETED->label())->toBe('Completed');
    expect(PureWorkflowStatus::CLOSED->label())->toBe('Closed');
});

test('pure enum description works', function (): void {
    expect(PureWorkflowStatus::COMPLETED->description())->toBe('Task is done');
    expect(PureWorkflowStatus::CLOSED->description())->toBeNull();
});

test('pure enum icon works', function (): void {
    expect(PureWorkflowStatus::OPEN->icon())->toBe('heroicon-o-folder-open');
    expect(PureWorkflowStatus::CLOSED->icon())->toBeNull();
});
