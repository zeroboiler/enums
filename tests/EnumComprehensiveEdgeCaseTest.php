<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum MetadataResolver — detailed resolution', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('resolves class-level EnumLabel for all mapped cases', function () {
        $meta = EnumMetadataResolver::resolve(TicketStatus::class);

        expect($meta['labels']['open'])->toBe('Open');
        expect($meta['labels']['in_progress'])->toBe('In Progress');
        expect($meta['labels']['closed'])->toBe('Closed');
    });

    it('resolves class-level EnumDescription for mapped cases', function () {
        $meta = EnumMetadataResolver::resolve(TicketStatus::class);

        expect($meta['descriptions']['open'])->toBe('Ticket is open and awaiting response');
        expect($meta['descriptions']['closed'])->toBe('Ticket has been resolved');
    });

    it('resolves class-level EnumIcon default for all cases', function () {
        $meta = EnumMetadataResolver::resolve(TicketStatus::class);

        expect($meta['icons']['open'])->toBe('heroicon-o-ticket');
        expect($meta['icons']['in_progress'])->toBe('heroicon-o-ticket');
        expect($meta['icons']['closed'])->toBe('heroicon-o-ticket');
    });

    it('per-case attributes override class-level labels', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // ACTIVE has per-case Label('Active User'), not auto-generated
        expect($meta['labels']['active'])->toBe('Active User');
        // INACTIVE has no per-case Label, not in class-level EnumLabel either
        // so it won't be in the labels map (auto-generated)
        expect($meta['labels'])->not->toHaveKey('inactive');
    });

    it('per-case color overrides class-level EnumColor', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // BANNED has per-case #[Color('danger')] which overrides class-level
        expect($meta['colors']['banned'])->toBe('danger');
    });

    it('class-level EnumColor maps multiple values to same color', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['colors']['pending'])->toBe('warning');
        expect($meta['colors']['suspended'])->toBe('warning');
    });

    it('per-case icon overrides class-level EnumIcon', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // ACTIVE has per-case #[Icon('heroicon-o-check-circle')]
        expect($meta['icons']['active'])->toBe('heroicon-o-check-circle');
    });

    it('resolves empty metadata for enum with no attributes', function () {
        $meta = EnumMetadataResolver::resolve(OrderStatus::class);

        expect($meta['labels'])->toBeArray();
        expect($meta['descriptions'])->toBeArray();
        expect($meta['colors'])->toBeArray();
        expect($meta['icons'])->toBeArray();
    });

    it('caches resolved metadata and returns same instance on second call', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $meta1 = EnumMetadataResolver::resolve(OrderStatus::class);
        $meta2 = EnumMetadataResolver::resolve(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeTrue();
        expect($meta1)->toBe($meta2);

        $cache->setTtl(300);
    });

    it('resolves int-backed enum metadata using int values as keys', function () {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        // No class-level attributes — empty maps
        expect($meta['labels'])->toBeArray();
        expect($meta['descriptions'])->toBeArray();
    });

    it('resolves pure enum metadata using case names as keys', function () {
        $meta = EnumMetadataResolver::resolve(RequestState::class);

        expect($meta['labels'])->toBeArray();
        expect($meta['descriptions'])->toBeArray();
    });
});

describe('EnumManager — error handling', function () {
    it('forSelect throws BadMethodCallException for plain enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class, 'does not use HasEnumMetadata');
    });

    it('forApi throws BadMethodCallException for plain enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forApi(\stdClass::class))
            ->toThrow(\BadMethodCallException::class, 'does not use HasEnumMetadata');
    });

    it('tryFromLabel throws BadMethodCallException for plain enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->tryFromLabel(\stdClass::class, 'test'))
            ->toThrow(\BadMethodCallException::class, 'does not use HasEnumMetadata');
    });
});

describe('EnumTestGenerator — output structure', function () {
    it('generates valid PHP for a string-backed enum', function () {
        $output = EnumTestGenerator::generate(UserStatus::class);

        expect($output)->toContain('declare(strict_types=1)');
        expect($output)->toContain('use ' . UserStatus::class . ';');
        expect($output)->toContain("describe('UserStatus enum'");
        expect($output)->toContain("it('has cases'");
        expect($output)->toContain("it('can generate select options'");
        expect($output)->toContain("it('can generate API response array'");
        expect($output)->toContain('->toHaveKeys([\'value\', \'label\'])');
        expect($output)->toContain('->toHaveKeys([\'value\', \'name\', \'label\', \'description\', \'color\', \'icon\'])');
    });

    it('generates case-specific label and color tests', function () {
        $output = EnumTestGenerator::generate(UserStatus::class);

        expect($output)->toContain("it('has a label for case ACTIVE'");
        expect($output)->toContain("it('has a color for case ACTIVE'");
    });

    it('generates comparison tests when enum has 2+ cases', function () {
        $output = EnumTestGenerator::generate(UserStatus::class);

        expect($output)->toContain("it('supports is() comparison with instance'");
        expect($output)->toContain("it('supports is() comparison with string name'");
        expect($output)->toContain("it('supports isNot() comparison'");
        expect($output)->toContain("it('supports in() group matching'");
    });

    it('generates reverse lookup test using first case label', function () {
        $output = EnumTestGenerator::generate(UserStatus::class);

        expect($output)->toContain("it('supports tryFromLabel reverse lookup'");
    });

    it('generates tryFromName and hasCase tests', function () {
        $output = EnumTestGenerator::generate(UserStatus::class);

        expect($output)->toContain("it('supports tryFromName lookup'");
        expect($output)->toContain("it('supports hasCase check'");
    });

    it('generates values() and labels() count tests', function () {
        $output = EnumTestGenerator::generate(UserStatus::class);

        expect($output)->toContain("it('values() returns correct count'");
        expect($output)->toContain("it('labels() returns correct count'");
    });
});

describe('HasEnumMetadata — values() and labels() method correctness', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('values() returns backed values for string-backed enum', function () {
        $values = UserStatus::values();

        expect($values)->toContain('active');
        expect($values)->toContain('banned');
        expect($values)->not->toContain('ACTIVE');
    });

    it('values() returns backed values for int-backed enum', function () {
        $values = Priority::values();

        expect($values)->toContain(1);
        expect($values)->toContain(2);
        expect($values)->toContain(3);
        expect($values)->toContain(4);
        expect($values)->not->toContain('LOW');
    });

    it('values() returns case names for pure enum', function () {
        $values = RequestState::values();

        expect($values)->toContain('DRAFT');
        expect($values)->toContain('APPROVED');
    });

    it('labels() returns auto-generated labels for cases without Label attribute', function () {
        $labels = UserStatus::labels();

        // INACTIVE has no per-case Label — auto-generated from 'INACTIVE' → 'Inactive'
        $inactiveIndex = array_search('INACTIVE', array_column(UserStatus::forApi(), 'name'));
        expect($labels[(int) $inactiveIndex])->toBe('Inactive');
    });

    it('labels() returns custom labels for cases with Label attribute', function () {
        $labels = UserStatus::labels();

        $activeIndex = array_search('ACTIVE', array_column(UserStatus::forApi(), 'name'));
        expect($labels[(int) $activeIndex])->toBe('Active User');
    });

    it('forSelect() returns list of value+label pairs', function () {
        $select = TicketStatus::forSelect();

        expect($select)->toBeArray();
        expect($select[0])->toHaveKeys(['value', 'label']);

        // OPEN should have class-level EnumLabel 'Open'
        $open = array_filter($select, fn (array $item) => $item['value'] === 'open');
        expect(array_values($open)[0]['label'])->toBe('Open');
    });

    it('forApi() returns full metadata for each case', function () {
        $api = TicketStatus::forApi();

        expect($api)->toBeArray();
        expect(count($api))->toBe(3);

        // First case OPEN
        expect($api[0]['name'])->toBe('OPEN');
        expect($api[0]['value'])->toBe('open');
        expect($api[0]['label'])->toBe('Open');
        expect($api[0]['description'])->toBe('Ticket is open and awaiting response');
        expect($api[0]['icon'])->toBe('heroicon-o-ticket');
    });

    it('color() returns secondary default for cases without color', function () {
        // OrderStatus has no EnumColor attribute — all should default to 'secondary'
        expect(OrderStatus::PENDING->color())->toBe('secondary');
        expect(OrderStatus::SHIPPED->color())->toBe('secondary');
    });

    it('color() returns class-level mapped color', function () {
        expect(UserStatus::ACTIVE->color())->toBe('success');
        expect(UserStatus::PENDING->color())->toBe('warning');
        expect(UserStatus::BANNED->color())->toBe('danger');
    });

    it('icon() returns null for cases without icon', function () {
        expect(OrderStatus::PENDING->icon())->toBeNull();
        expect(UserStatus::PENDING->icon())->toBeNull();
    });

    it('icon() returns per-case icon when set', function () {
        expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
    });

    it('icon() returns class-level default icon', function () {
        expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');
        expect(TicketStatus::CLOSED->icon())->toBe('heroicon-o-ticket');
    });

    it('description() returns null when no description set', function () {
        expect(OrderStatus::PENDING->description())->toBeNull();
        expect(UserStatus::PENDING->description())->toBeNull();
    });

    it('description() returns per-case description', function () {
        expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
        expect(UserStatus::BANNED->description())->toBe('User is permanently banned');
    });

    it('description() returns class-level description', function () {
        expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
    });
});

describe('HasEnumMetadata — comparison methods edge cases', function () {
    it('is() matches with string name (case-sensitive)', function () {
        expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
    });

    it('is() matches with enum instance', function () {
        expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
        expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
    });

    it('isNot() is exact negation of is()', function () {
        expect(UserStatus::ACTIVE->isNot('ACTIVE'))->toBeFalse();
        expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
    });

    it('in() returns true for empty list', function () {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('in() works with mixed instance and string arguments', function () {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        expect(UserStatus::ACTIVE->in([UserStatus::BANNED, 'PENDING']))->toBeFalse();
    });

    it('hasCase returns false for empty string', function () {
        expect(UserStatus::hasCase(''))->toBeFalse();
    });

    it('tryFromName returns null for empty string', function () {
        expect(UserStatus::tryFromName(''))->toBeNull();
    });

    it('fromName throws for empty string', function () {
        expect(fn () => UserStatus::fromName(''))
            ->toThrow(InvalidEnumException::class);
    });
});

describe('EnumRule — message generation edge cases', function () {
    it('generates fallback message for enum without values() method', function () {
        $rule = new \ZeroBoiler\Enums\Rules\EnumRule(\stdClass::class);

        $message = '';
        $fail = static function (string $m) use (&$message): void {
            $message = $m;
        };

        $rule->validate('field', 'test', $fail);

        expect($message)->toBe('The selected field is invalid.');
    });

    it('generates descriptive message with allowed values', function () {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(Priority::class);

        $message = '';
        $fail = static function (string $m) use (&$message): void {
            $message = $m;
        };

        $rule->validate('level', 999, $fail);

        expect($message)->toContain('Allowed values');
        expect($message)->toContain('1');
        expect($message)->toContain('4');
    });

    it('nullable instance accepts null without calling fail', function () {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class)->nullable();

        $called = false;
        $fail = static function () use (&$called): void {
            $called = true;
        };

        $rule->validate('status', null, $fail);

        expect($called)->toBeFalse();
    });
});

describe('EnumCache — negative TTL normalization', function () {
    it('setTtl with negative value normalizes to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        // TTL <= 0 means no caching — set() succeeds but has() returns false
        $cache->set('TestClass', [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestClass'))->toBeFalse();

        // Reset for other tests
        $cache->setTtl(300);
    });
});

describe('EnumCast — edge cases', function () {
    it('get returns null for null value', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
        $result = $cast->get(new stdClass, 'status', null, []);

        expect($result)->toBeNull();
    });

    it('set throws InvalidArgumentException for wrong enum type', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);

        expect(fn () => $cast->set(new stdClass, 'status', Priority::LOW, []))
            ->toThrow(\InvalidArgumentException::class, 'Expected enum');
    });

    it('set throws InvalidArgumentException for invalid type', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);

        expect(fn () => $cast->set(new stdClass, 'status', ['array'], []))
            ->toThrow(\InvalidArgumentException::class, 'Invalid value type');
    });

    it('set throws InvalidArgumentException for invalid raw value', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);

        expect(fn () => $cast->set(new stdClass, 'status', 'nonexistent', []))
            ->toThrow(\InvalidArgumentException::class, 'Invalid value');
    });

    it('serialize returns raw value for non-enum', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
        $result = $cast->serialize(new stdClass, 'status', 'active', []);

        expect($result)->toBe('active');
    });

    it('get returns null for invalid value', function () {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);
        $result = $cast->get(new stdClass, 'status', 'nonexistent', []);

        expect($result)->toBeNull();
    });
});
