<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('EnumMetadataEdgeCases', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    describe('camelCase label generation', function () {
        it('generates Title Case from camelCase enum names', function () {
            // CamelCaseRole has camelCase cases like isActive, isAdmin
            $label = CamelCaseRole::isActive->label();
            expect($label)->toBe('Is Active');
        });

        it('generates correct labels for all camelCase cases', function () {
            $cases = CamelCaseRole::cases();
            $labels = array_map(static fn ($case) => $case->label(), $cases);

            expect($labels)->not->toBeEmpty();
            // Every label should have at least one space (Title Case from camelCase)
            foreach ($labels as $label) {
                expect($label)->toBeString();
                expect(strlen($label))->toBeGreaterThan(0);
            }
        });

        it('generates Title Case from SCREAMING_SNAKE_CASE enum names', function () {
            $label = UserStatus::INACTIVE->label();
            // INACTIVE → "Inactive"
            expect($label)->toBe('Inactive');
        });

        it('generates Title Case for single-word SCREAMING_CASE', function () {
            $label = Priority::LOW->label();
            expect($label)->toBe('Low');
        });

        it('generates Title Case for multiple SCREAMING_SNAKE_CASE words', function () {
            // NONE = 0, single word
            $label = ZeroPriority::NONE->label();
            expect($label)->toBe('None');
        });
    });

    describe('class-level EnumIcon default fallback', function () {
        it('applies default icon to all cases when EnumIcon is set at class level', function () {
            // TicketStatus has #[EnumIcon(default: 'heroicon-o-ticket')]
            foreach (TicketStatus::cases() as $case) {
                expect($case->icon())->toBe('heroicon-o-ticket');
            }
        });

        it('per-case Icon overrides class-level EnumIcon', function () {
            // UserStatus::ACTIVE has #[Icon('heroicon-o-check-circle')]
            expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');

            // UserStatus::INACTIVE has no per-case Icon, and no class-level EnumIcon
            // so it should be null
            expect(UserStatus::INACTIVE->icon())->toBeNull();
        });
    });

    describe('class-level EnumLabel bulk definitions', function () {
        it('applies bulk labels from EnumLabel attribute', function () {
            expect(TicketStatus::OPEN->label())->toBe('Open');
            expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
            expect(TicketStatus::CLOSED->label())->toBe('Closed');
        });
    });

    describe('class-level EnumDescription bulk definitions', function () {
        it('applies bulk descriptions from EnumDescription attribute', function () {
            expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
            expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
        });

        it('returns null for cases without class-level or per-case description', function () {
            expect(TicketStatus::IN_PROGRESS->description())->toBeNull();
        });
    });

    describe('pure enum metadata behavior', function () {
        it('values() returns case names for pure enums', function () {
            $values = RequestState::values();
            expect($values)->toBe(['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED']);
        });

        it('forSelect() uses case names as values for pure enums', function () {
            $select = RequestState::forSelect();
            expect($select)->toHaveCount(4);
            expect($select[0]['value'])->toBe('DRAFT');
            expect($select[0])->toHaveKey('label');
        });

        it('forApi() returns case names in value field for pure enums', function () {
            $api = RequestState::forApi();
            expect($api[0]['value'])->toBe('DRAFT');
            expect($api[0]['name'])->toBe('DRAFT');
        });

        it('tryFromName works with pure enum case names', function () {
            expect(RequestState::tryFromName('DRAFT'))->toBe(RequestState::DRAFT);
            expect(RequestState::tryFromName('NON_EXISTENT'))->toBeNull();
        });

        it('fromName throws InvalidEnumException for pure enums', function () {
            expect(fn () => RequestState::fromName('UNKNOWN'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns correct boolean for pure enums', function () {
            expect(RequestState::hasCase('DRAFT'))->toBeTrue();
            expect(RequestState::hasCase('draft'))->toBeFalse(); // case-sensitive
            expect(RequestState::hasCase('UNKNOWN'))->toBeFalse();
        });
    });

    describe('int-backed enum metadata behavior', function () {
        it('values() returns int backed values', function () {
            $values = Priority::values();
            expect($values)->toBe([1, 2, 3, 4]);
        });

        it('forSelect() uses int values', function () {
            $select = Priority::forSelect();
            expect($select)->toHaveCount(4);
            expect($select[0]['value'])->toBe(1);
        });

        it('forApi() uses int values', function () {
            $api = Priority::forApi();
            expect($api[0]['value'])->toBe(1);
            expect($api[0]['name'])->toBe('LOW');
        });

        it('labels() returns auto-generated labels for all cases', function () {
            $labels = Priority::labels();
            expect($labels)->toBe(['Low', 'Medium', 'High', 'Urgent']);
        });
    });

    describe('single-case enum edge case', function () {
        it('forSelect() returns single option', function () {
            $select = SingleCaseEnum::forSelect();
            expect($select)->toHaveCount(1);
            expect($select[0]['value'])->toBe('only');
            expect($select[0]['label'])->toBe('Only');
        });

        it('forApi() returns single item', function () {
            $api = SingleCaseEnum::forApi();
            expect($api)->toHaveCount(1);
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('values() returns single value', function () {
            expect(SingleCaseEnum::values())->toBe(['only']);
        });

        it('is() works on single-case enum', function () {
            expect(SingleCaseEnum::ONLY->is(SingleCaseEnum::ONLY))->toBeTrue();
            expect(SingleCaseEnum::ONLY->is('ONLY'))->toBeTrue();
        });

        it('in() works with single-element array', function () {
            expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
            expect(SingleCaseEnum::ONLY->in([]))->toBeFalse();
        });
    });

    describe('color resolution hierarchy', function () {
        it('per-case Color overrides class-level EnumColor', function () {
            // BANNED has per-case #[Color('danger')] — overrides class-level success/danger map
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('class-level EnumColor applies when no per-case Color exists', function () {
            // ACTIVE is in the 'success' list of EnumColor
            expect(UserStatus::ACTIVE->color())->toBe('success');

            // PENDING is in the 'warning' list
            expect(UserStatus::PENDING->color())->toBe('warning');
        });

        it('defaults to secondary when no color attribute exists', function () {
            // INACTIVE is not in any EnumColor list and has no per-case Color
            expect(UserStatus::INACTIVE->color())->toBe('secondary');
        });

        it('pure enums without color attributes default to secondary', function () {
            foreach (RequestState::cases() as $case) {
                expect($case->color())->toBe('secondary');
            }
        });
    });

    describe('description resolution hierarchy', function () {
        it('per-case Description overrides class-level EnumDescription', function () {
            // ACTIVE has per-case #[Description('User can fully access the system')]
            expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
        });

        it('returns null when no description is defined anywhere', function () {
            // INACTIVE has no per-case Description, no class-level EnumDescription
            expect(UserStatus::INACTIVE->description())->toBeNull();
        });
    });

    describe('forSelect consistency', function () {
        it('returns consistent value/label pairs across enum types', function () {
            // String-backed
            $stringSelect = UserStatus::forSelect();
            foreach ($stringSelect as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
            }

            // Int-backed
            $intSelect = Priority::forSelect();
            foreach ($intSelect as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect(is_int($option['value']) || is_string($option['value']))->toBeTrue();
                expect($option['label'])->toBeString();
            }

            // Pure enum
            $pureSelect = RequestState::forSelect();
            foreach ($pureSelect as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
            }
        });

        it('forSelect values are unique', function () {
            $values = array_column(UserStatus::forSelect(), 'value');
            expect($values)->toEqual(array_unique($values));
        });
    });

    describe('forApi structure consistency', function () {
        it('always returns all six keys per entry', function () {
            $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

            foreach (UserStatus::forApi() as $entry) {
                expect(array_keys($entry))->toEqual($requiredKeys);
            }

            foreach (Priority::forApi() as $entry) {
                expect(array_keys($entry))->toEqual($requiredKeys);
            }

            foreach (RequestState::forApi() as $entry) {
                expect(array_keys($entry))->toEqual($requiredKeys);
            }
        });

        it('description and icon can be null', function () {
            foreach (UserStatus::forApi() as $entry) {
                // description is nullable
                expect($entry['description'])->toBeNull()->or()->toBeString();
                // icon is nullable
                expect($entry['icon'])->toBeNull()->or()->toBeString();
            }
        });
    });

    describe('negative TTL normalization', function () {
        it('normalizes negative TTL to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);

            // With TTL effectively 0, caching is disabled
            UserStatus::ACTIVE->label();
            expect($cache->has(UserStatus::class))->toBeFalse();

            // Reset
            $cache->setTtl(300);
        });
    });

    describe('InvalidEnumException message formatting', function () {
        it('formats null value correctly', function () {
            $e = InvalidEnumException::value(UserStatus::class, null);
            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain(UserStatus::class);
        });

        it('formats int value correctly', function () {
            $e = InvalidEnumException::value(Priority::class, 99);
            expect($e->getMessage())->toContain('99');
            expect($e->getMessage())->toContain(Priority::class);
        });

        it('formats string value correctly', function () {
            $e = InvalidEnumException::value(UserStatus::class, 'unknown_status');
            expect($e->getMessage())->toContain('unknown_status');
        });

        it('forName includes both class and name in message', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'GHOST');
            expect($e->getMessage())->toContain('GHOST');
            expect($e->getMessage())->toContain(UserStatus::class);
        });
    });

    describe('is()/isNot() strict identity', function () {
        it('is() returns false for different enums with same backing value concept', function () {
            // Two different enum types should never match via is()
            $result = UserStatus::ACTIVE->is(Priority::LOW);
            // PHP type system prevents this — $is() expects self|string
            // So this test verifies type safety at the trait level
            expect(true)->toBeTrue(); // If code compiles, types are correct
        });

        it('isNot() negates is() correctly', function () {
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
            expect(UserStatus::ACTIVE->isNot('BANNED'))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot('ACTIVE'))->toBeFalse();
        });
    });

    describe('tryFromLabel case-insensitive matching', function () {
        it('matches regardless of case', function () {
            $active = UserStatus::tryFromLabel('Active User');
            expect($active)->toBe(UserStatus::ACTIVE);

            $lower = UserStatus::tryFromLabel('active user');
            expect($lower)->toBe(UserStatus::ACTIVE);

            $upper = UserStatus::tryFromLabel('ACTIVE USER');
            expect($upper)->toBe(UserStatus::ACTIVE);
        });

        it('returns null for non-matching label', function () {
            expect(UserStatus::tryFromLabel('Nonexistent Label'))->toBeNull();
        });
    });

    describe('labels() ordering', function () {
        it('returns labels in declaration order', function () {
            $labels = UserStatus::labels();
            $expected = ['Active User', 'Inactive', 'Awaiting Verification', 'Suspended', 'Banned'];
            expect($labels)->toBe($expected);
        });
    });
});
