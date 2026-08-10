<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Large enum with many cases — tests metadata resolution performance
 * and bulk operations (forSelect, forApi, values, labels) at scale.
 *
 * 20 cases with class-level EnumColor covering all color categories.
 * No per-case attributes — relies entirely on class-level defaults.
 */
#[EnumColor(
    success: ['active', 'completed', 'verified', 'approved', 'delivered', 'paid'],
    danger: ['failed', 'cancelled', 'rejected', 'expired', 'suspended', 'blocked'],
    warning: ['pending', 'processing', 'review', 'held', 'deferred'],
    secondary: ['draft', 'archived', 'unknown'],
)]
enum OrderWorkflowStatus: string
{
    use HasEnumMetadata;

    // Active states
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case REVIEW = 'review';
    case HELD = 'held';
    case DEFERRED = 'deferred';

    // Success states
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case VERIFIED = 'verified';
    case APPROVED = 'approved';
    case DELIVERED = 'delivered';
    case PAID = 'paid';

    // Failure states
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
    case BLOCKED = 'blocked';

    // Terminal states
    case ARCHIVED = 'archived';
    case UNKNOWN = 'unknown';
}
