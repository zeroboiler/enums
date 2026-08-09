<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('EnumManager delegation edge cases', function (): void {
    it('throws BadMethodCallException for non-enum class', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        expect(fn (): mixed => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class, 'does not use HasEnumMetadata trait');
    });

    it('throws BadMethodCallException for non-enum class in forApi', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        expect(fn (): mixed => $manager->forApi(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException for non-enum class in tryFromLabel', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        expect(fn (): mixed => $manager->tryFromLabel(\stdClass::class, 'test'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('forSelect returns consistent structure across calls', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        $first = $manager->forSelect(UserStatus::class);
        $second = $manager->forSelect(UserStatus::class);

        expect($first)->toBe($second);
    });

    it('forApi includes all expected keys for each case', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $api = $manager->forApi(UserStatus::class);

        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['color'])->toBeString();
            expect($item['label'])->toBeString();
        }
    });

    it('tryFromLabel returns null for non-existent label', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        expect($manager->tryFromLabel(UserStatus::class, 'totally-nonexistent-label-xyz'))
            ->toBeNull();
    });
});

describe('EnumMetadataResolver invalidation and rebuild', function (): void {
    it('invalidating a class forces rebuild on next access', function (): void {
        EnumMetadataResolver::invalidate(UserStatus::class);
        EnumCache::getInstance()->clearClass(UserStatus::class);

        // Access after invalidation should still work (rebuilt from scratch)
        $label = UserStatus::ACTIVE->label();

        expect($label)->toBe('Active User');
    });

    it('invalidateAll clears everything', function (): void {
        EnumMetadataResolver::invalidateAll();

        // Should still work after full invalidation
        expect(UserStatus::ACTIVE->label())->toBe('Active User');
        expect(Priority::LOW->label())->toBe('Low');
    });

    it('cross-enum invalidation does not affect other enums', function (): void {
        // Warm both caches
        UserStatus::ACTIVE->label();
        Priority::LOW->label();

        // Invalidate only UserStatus
        EnumMetadataResolver::invalidate(UserStatus::class);

        // Priority should still return correct result from cache
        expect(Priority::LOW->label())->toBe('Low');

        // UserStatus should rebuild correctly
        expect(UserStatus::ACTIVE->label())->toBe('Active User');
    });
});

describe('EnumCache TTL behavior', function (): void {
    it('disabling TTL means cache entries are always stale', function (): void {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(0);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Cached Label'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // With TTL=0, has() should return false (always stale)
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Reset TTL for other tests
        $cache->setTtl(300);
    });

    it('negative TTL is normalized to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);

        $cache->setTtl(300);
    });

    it('setTtl accepts zero and positive values', function (): void {
        $cache = EnumCache::getInstance();

        $cache->setTtl(0);
        expect($cache->getTtl())->toBe(0);

        $cache->setTtl(60);
        expect($cache->getTtl())->toBe(60);

        $cache->setTtl(300);
    });
});

describe('EnumRule with int-backed enums', function (): void {
    it('validates correct int value', function (): void {
        $rule = EnumRule::for(ZeroPriority::class);
        $fail = fn (): never => throw new \Exception('Validation failed');

        // Should not throw
        $rule->validate('priority', 0, $fail);
        $rule->validate('priority', 1, $fail);
        $rule->validate('priority', 2, $fail);

        expect(true)->toBeTrue();
    });

    it('rejects string value for int-backed enum', function (): void {
        $rule = EnumRule::for(ZeroPriority::class);
        $fail = fn (): never => throw new \Exception('Validation failed');

        // String '1' should fail for int-backed enum
        expect(fn () => $rule->validate('priority', '1', $fail))
            ->toThrow(\Exception::class, 'Validation failed');
    });

    it('rejects out-of-range int value', function (): void {
        $rule = EnumRule::for(ZeroPriority::class);
        $fail = fn (): never => throw new \Exception('Validation failed');

        expect(fn () => $rule->validate('priority', 99, $fail))
            ->toThrow(\Exception::class, 'Validation failed');
    });

    it('nullable variant allows null', function (): void {
        $rule = EnumRule::for(ZeroPriority::class)->nullable();
        $fail = fn (): never => throw new \Exception('Validation failed');

        // Should not throw for null
        $rule->validate('priority', null, $fail);

        expect(true)->toBeTrue();
    });

    it('non-nullable variant rejects null', function (): void {
        $rule = EnumRule::for(ZeroPriority::class);
        $fail = fn (): never => throw new \Exception('Validation failed');

        expect(fn () => $rule->validate('priority', null, $fail))
            ->toThrow(\Exception::class, 'Validation failed');
    });
});

describe('EnumRule with pure enums', function (): void {
    it('validates correct case name', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $fail = fn (): never => throw new \Exception('Validation failed');

        $rule->validate('feature', 'TWO_FACTOR_AUTH', $fail);
        $rule->validate('feature', 'DARK_MODE', $fail);
        $rule->validate('feature', 'BETA_ACCESS', $fail);

        expect(true)->toBeTrue();
    });

    it('rejects invalid case name for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $fail = fn (): never => throw new \Exception('Validation failed');

        expect(fn () => $rule->validate('feature', 'NONEXISTENT', $fail))
            ->toThrow(\Exception::class, 'Validation failed');
    });

    it('rejects non-string value for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $fail = fn (): never => throw new \Exception('Validation failed');

        expect(fn () => $rule->validate('feature', 123, $fail))
            ->toThrow(\Exception::class, 'Validation failed');
    });
});

describe('ZeroPriority zero-value edge cases', function (): void {
    it('label auto-generates for zero-value case', function (): void {
        expect(ZeroPriority::NONE->label())->toBe('None');
    });

    it('values() includes zero', function (): void {
        $values = ZeroPriority::values();

        expect($values)->toContain(0);
        expect($values)->toBe([0, 1, 2]);
    });

    it('forSelect uses zero as value', function (): void {
        $options = ZeroPriority::forSelect();

        expect($options[0]['value'])->toBe(0);
        expect($options[0]['label'])->toBe('None');
    });
});

describe('TicketStatus class-level attributes', function (): void {
    it('resolves labels from EnumLabel', function (): void {
        expect(TicketStatus::OPEN->label())->toBe('Open');
        expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
        expect(TicketStatus::CLOSED->label())->toBe('Closed');
    });

    it('resolves descriptions from EnumDescription', function (): void {
        expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
        expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
    });

    it('resolves default icon from EnumIcon', function (): void {
        expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');
        expect(TicketStatus::IN_PROGRESS->icon())->toBe('heroicon-o-ticket');
        expect(TicketStatus::CLOSED->icon())->toBe('heroicon-o-ticket');
    });

    it('hasCase returns correct results', function (): void {
        expect(TicketStatus::hasCase('OPEN'))->toBeTrue();
        expect(TicketStatus::hasCase('IN_PROGRESS'))->toBeTrue();
        expect(TicketStatus::hasCase('NONEXISTENT'))->toBeFalse();
    });

    it('fromName returns correct case', function (): void {
        expect(TicketStatus::fromName('OPEN')->value)->toBe('open');
    });

    it('fromName throws for invalid name', function (): void {
        expect(fn () => TicketStatus::fromName('INVALID'))
            ->toThrow(InvalidEnumException::class);
    });
});

describe('HasEnumMetadata trait completeness', function (): void {
    it('is() works with both enum instances and strings', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->is(UserStatus::ACTIVE))->toBeTrue();
        expect($status->is('ACTIVE'))->toBeTrue();
        expect($status->is(UserStatus::BANNED))->toBeFalse();
        expect($status->is('BANNED'))->toBeFalse();
    });

    it('isNot() is the negation of is()', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->isNot(UserStatus::ACTIVE))->toBeFalse();
        expect($status->isNot('ACTIVE'))->toBeFalse();
        expect($status->isNot(UserStatus::BANNED))->toBeTrue();
        expect($status->isNot('BANNED'))->toBeTrue();
    });

    it('in() works with mixed instances and strings', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
        expect($status->in(['ACTIVE', 'PENDING']))->toBeTrue();
        expect($status->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        expect($status->in([UserStatus::BANNED]))->toBeFalse();
        expect($status->in(['BANNED']))->toBeFalse();
    });

    it('in() returns false for empty array', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->in([]))->toBeFalse();
    });
});
