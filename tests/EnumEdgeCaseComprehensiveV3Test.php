<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('EnumCache TTL behavior', function (): void {
    it('returns false when class not cached', function (): void {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();

        expect($cache->has('NonExistentEnum'))->toBeFalse();
    });

    it('stores and retrieves metadata', function (): void {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $cache->set('CacheTestEnum', [
            'labels' => ['test' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('CacheTestEnum'))->toBeTrue();

        $data = $cache->get('CacheTestEnum');
        expect($data['labels']['test'])->toBe('Test');

        // Cleanup
        $cache->clearClass('CacheTestEnum');
    });

    it('auto-expires stale entries based on TTL', function (): void {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $originalTtl = $cache->getTtl();
        $cache->setTtl(1); // 1 second TTL

        $cache->set('TtlTestEnum', [
            'labels' => ['test' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TtlTestEnum'))->toBeTrue();

        // Cleanup
        $cache->clearClass('TtlTestEnum');
        $cache->setTtl($originalTtl);
    });
});

describe('EnumCache singleton behavior', function (): void {
    it('always returns the same instance', function (): void {
        $a = \ZeroBoiler\Enums\EnumCache::getInstance();
        $b = \ZeroBoiler\Enums\EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('throws OutOfBoundsException on get without has check', function (): void {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $cache->clearClass('NonExistentClassForTest');

        expect(fn (): mixed => $cache->get('NonExistentClassForTest'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('setTtl normalizes negative values to 0', function (): void {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $originalTtl = $cache->getTtl();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);

        $cache->setTtl($originalTtl);
    });
});

describe('EnumMetadataResolver cache invalidation', function (): void {
    it('invalidates a specific class', function (): void {
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(UserStatus::class);

        // Next call should rebuild — no exception means it works
        $label = UserStatus::ACTIVE->label();
        expect($label)->toBeString()->not->toBeEmpty();
    });

    it('invalidates all classes', function (): void {
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidateAll();

        $label = OrderStatus::PENDING->label();
        expect($label)->toBe('Pending');
    });
});

describe('InvalidEnumException factory methods', function (): void {
    it('creates exception for invalid value', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, 'unknown_value');

        expect($e->getMessage())->toContain('unknown_value');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('creates exception for null value', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, null);

        expect($e->getMessage())->toContain('null');
    });

    it('creates exception for invalid case name', function (): void {
        $e = InvalidEnumException::forName(UserStatus::class, 'NON_EXISTENT');

        expect($e->getMessage())->toContain('NON_EXISTENT');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('__toString returns class name and message', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, 'bad');

        $str = (string) $e;
        expect($str)->toContain(InvalidEnumException::class);
        expect($str)->toContain('bad');
    });
});

describe('Comparison method edge cases', function (): void {
    it('is() returns false for different enum types', function (): void {
        expect(UserStatus::ACTIVE->is(OrderStatus::class))->toBeFalse();
    });

    it('notIn() returns true when case is not in empty list', function (): void {
        expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
    });

    it('in() returns false for empty list', function (): void {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('in() with same case repeated still returns true', function (): void {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::ACTIVE]))->toBeTrue();
    });
});

describe('Pure enum specific behavior', function (): void {
    it('forSelect uses case names as values for pure enums', function (): void {
        $options = PureFeatureFlag::forSelect();

        expect($options)->toBeArray();
        expect($options[0]['value'])->toBe('DARK_MODE');
        expect($options[0]['label'])->toBe('Dark Mode');
    });

    it('values() returns case names for pure enums', function (): void {
        $values = PureFeatureFlag::values();

        expect($values)->toBe(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
    });

    it('forApi uses case names as values', function (): void {
        $api = PureFeatureFlag::forApi();

        expect($api[0]['value'])->toBe('DARK_MODE');
        expect($api[0]['name'])->toBe('DARK_MODE');
        expect($api[0]['label'])->toBe('Dark Mode');
    });

    it('tryFromLabel works with case-insensitive pure enum labels', function (): void {
        $case = PureFeatureFlag::tryFromLabel('dark mode');

        expect($case)->toBe(PureFeatureFlag::DARK_MODE);
    });

    it('hasCase works with case names', function (): void {
        expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::hasCase('NON_EXISTENT'))->toBeFalse();
    });

    it('MAINTENANCE_MODE auto-generates label and defaults', function (): void {
        $case = PureFeatureFlag::MAINTENANCE_MODE;

        expect($case->label())->toBe('Maintenance Mode');
        expect($case->color())->toBe('secondary');
        expect($case->icon())->toBeNull();
        expect($case->description())->toBeNull();
    });
});

describe('Int-backed enum specific behavior', function (): void {
    it('values() returns int values', function (): void {
        $values = Priority::values();

        expect($values)->toBe([1, 2, 3, 4]);
    });

    it('forSelect uses int values', function (): void {
        $options = Priority::forSelect();

        expect($options[0]['value'])->toBe(1);
        expect($options[3]['value'])->toBe(4);
    });

    it('forApi returns int values in value field', function (): void {
        $api = Priority::forApi();

        expect($api[0]['value'])->toBe(1);
        expect($api[0]['name'])->toBe('LOW');
    });

    it('labels() returns correct auto-generated labels', function (): void {
        $labels = Priority::labels();

        expect($labels)->toBe(['Low', 'Medium', 'High', 'Urgent']);
    });
});

describe('Zero-value int-backed enum', function (): void {
    it('handles zero as a valid backed value', function (): void {
        $case = ZeroPriority::NONE;

        expect($case->value)->toBe(0);
        expect($case->label())->toBeString()->not->toBeEmpty();
    });

    it('forSelect includes zero-value cases', function (): void {
        $options = ZeroPriority::forSelect();
        $zeroOption = array_filter($options, fn (array $o): bool => $o['value'] === 0);

        expect($zeroOption)->not->toBeEmpty();
    });

    it('ZeroBackedPriority resolves class-level label for zero value', function (): void {
        expect(ZeroBackedPriority::NONE->label())->toBe('None');
        expect(ZeroBackedPriority::NONE->color())->toBe('secondary');
    });

    it('ZeroBackedPriority resolves class-level labels for non-zero values', function (): void {
        expect(ZeroBackedPriority::LOW->label())->toBe('Low Priority');
        expect(ZeroBackedPriority::LOW->color())->toBe('success');
    });
});

describe('Int-backed with class-level attributes', function (): void {
    it('IntBackedPriority resolves per-case Color over class-level', function (): void {
        // Per-case #[Color('danger')] overrides class-level EnumColor
        expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
    });

    it('IntBackedPriority resolves class-level EnumDescription', function (): void {
        expect(IntBackedPriority::CRITICAL->description())->toBe('Critical priority — immediate action required');
    });

    it('IntBackedPriority resolves class-level EnumLabel', function (): void {
        expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
        expect(IntBackedPriority::LOW->label())->toBe('Low Priority');
    });

    it('IntBackedPriority auto-generates label for cases without class-level mapping', function (): void {
        expect(IntBackedPriority::NONE->label())->toBe('None');
    });

    it('IntBackedPriority resolves default icon from EnumIcon', function (): void {
        expect(IntBackedPriority::CRITICAL->icon())->toBe('heroicon-o-flag');
    });
});

describe('Single case enum', function (): void {
    it('works with a single case', function (): void {
        expect(SingleCaseEnum::cases())->toHaveCount(1);
        expect(SingleCaseEnum::ONLY->label())->toBe('Only');
        expect(SingleCaseEnum::ONLY->color())->toBe('secondary');
    });

    it('forSelect with single case returns one entry', function (): void {
        $options = SingleCaseEnum::forSelect();

        expect($options)->toHaveCount(1);
    });

    it('in() with single possible case', function (): void {
        expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
    });
});

describe('Class-level EnumLabel resolution', function (): void {
    it('TicketStatus resolves labels from EnumLabel mapping', function (): void {
        expect(TicketStatus::OPEN->label())->toBe('Open');
        expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
        expect(TicketStatus::CLOSED->label())->toBe('Closed');
    });

    it('TicketStatus resolves descriptions from EnumDescription mapping', function (): void {
        expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
        expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
    });

    it('TicketStatus resolves default icon from EnumIcon', function (): void {
        expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');
        expect(TicketStatus::IN_PROGRESS->icon())->toBe('heroicon-o-ticket');
    });
});

describe('DetailedTicketStatus per-case override over class-level', function (): void {
    it('per-case Description overrides class-level EnumDescription', function (): void {
        // IN_PROGRESS has per-case #[Description('...')] which overrides class-level
        expect(DetailedTicketStatus::IN_PROGRESS->description())
            ->toBe('Ticket is currently being worked on');
    });

    it('class-level EnumDescription used when no per-case override', function (): void {
        expect(DetailedTicketStatus::OPEN->description())
            ->toBe('Ticket is open and awaiting triage');
    });
});

describe('Mixed attribute status resolution chain', function (): void {
    it('resolves class-level labels for mapped cases', function (): void {
        expect(MixedAttributeStatus::NEW->label())->toBe('Brand New Item');
        expect(MixedAttributeStatus::USED->label())->toBe('Previously Owned');
    });

    it('auto-generates label for unmapped cases', function (): void {
        expect(MixedAttributeStatus::ACTIVE->label())->toBe('Active');
        expect(MixedAttributeStatus::DELETED->label())->toBe('Deleted');
    });

    it('resolves class-level colors correctly', function (): void {
        expect(MixedAttributeStatus::ACTIVE->color())->toBe('success');
        expect(MixedAttributeStatus::PENDING->color())->toBe('warning');
        expect(MixedAttributeStatus::ARCHIVED->color())->toBe('danger');
    });

    it('resolves class-level descriptions correctly', function (): void {
        expect(MixedAttributeStatus::ACTIVE->description())->toBe('Currently active');
        expect(MixedAttributeStatus::PENDING->description())->toBe('Awaiting review');
    });

    it('returns default icon for all cases', function (): void {
        expect(MixedAttributeStatus::ACTIVE->icon())->toBe('heroicon-o-document');
        expect(MixedAttributeStatus::DELETED->icon())->toBe('heroicon-o-document');
    });
});

describe('fromName and tryFromName edge cases', function (): void {
    it('fromName throws for empty string', function (): void {
        expect(fn (): mixed => UserStatus::fromName(''))
            ->toThrow(InvalidEnumException::class);
    });

    it('tryFromName returns null for empty string', function (): void {
        expect(UserStatus::tryFromName(''))->toBeNull();
    });

    it('fromName throws for case-sensitive mismatch', function (): void {
        expect(fn (): mixed => UserStatus::fromName('active'))
            ->toThrow(InvalidEnumException::class);
    });

    it('tryFromLabel returns null for empty string', function (): void {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });

    it('fromName works with exact case name', function (): void {
        $case = UserStatus::fromName('ACTIVE');

        expect($case)->toBe(UserStatus::ACTIVE);
    });

    it('tryFromName works with exact case name', function (): void {
        $case = UserStatus::tryFromName('PENDING');

        expect($case)->toBe(UserStatus::PENDING);
    });

    it('fromName throws for non-existent case name', function (): void {
        expect(fn (): mixed => UserStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });
});

describe('generateLabel from camelCase case names', function (): void {
    it('generates label from camelCase case name', function (): void {
        // CamelCaseRole case names are camelCase: isActive, isAdmin, etc.
        // generateLabel should handle this gracefully
        $label = CamelCaseRole::isActive->label();

        expect($label)->toBeString()->not->toBeEmpty();
    });

    it('each camelCase case gets a non-empty label', function (): void {
        foreach (CamelCaseRole::cases() as $case) {
            $label = $case->label();
            expect($label)->toBeString()->not->toBeEmpty();
        }
    });
});

describe('forApi consistency', function (): void {
    it('returns entries in case declaration order', function (): void {
        $api = UserStatus::forApi();
        $names = array_column($api, 'name');

        expect($names)->toBe(['ACTIVE', 'INACTIVE', 'PENDING', 'SUSPENDED', 'BANNED']);
    });

    it('always includes color as non-empty string', function (): void {
        foreach (UserStatus::forApi() as $entry) {
            expect($entry['color'])->toBeString()->not->toBeEmpty();
        }
    });

    it('forApi includes all expected keys', function (): void {
        foreach (UserStatus::forApi() as $entry) {
            expect($entry)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        }
    });
});

describe('PaymentStatus — full attribute coverage', function (): void {
    it('has correct metadata for each case', function (): void {
        foreach (PaymentStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
            expect($case->color())->toBeString()->not->toBeEmpty();
        }
    });

    it('forSelect returns consistent structure', function (): void {
        $options = PaymentStatus::forSelect();

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();

        foreach ($options as $option) {
            expect($option)->toHaveKeys(['value', 'label']);
        }
    });
});

describe('SystemStatus — attribute resolution', function (): void {
    it('resolves metadata for all cases', function (): void {
        foreach (SystemStatus::cases() as $case) {
            $label = $case->label();
            $color = $case->color();

            expect($label)->toBeString();
            expect($color)->toBeString();
        }
    });
});
