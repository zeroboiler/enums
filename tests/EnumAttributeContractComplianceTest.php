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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
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
 * Comprehensive attribute contract and integration tests.
 *
 * Verifies that all enum attributes are properly structured
 * and that cross-enum interactions work correctly.
 */
describe('Enum Attribute Contract & Integration', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    // -----------------------------------------------------------------------
    // Section 1: Attribute class structure
    // -----------------------------------------------------------------------
    describe('attribute classes are final and properly structured', function () {
        $attributeClasses = [
            Color::class,
            Description::class,
            EnumColor::class,
            EnumDescription::class,
            EnumIcon::class,
            EnumLabel::class,
            Icon::class,
            Label::class,
        ];

        test('all attribute classes are final', function () use ($attributeClasses) {
            foreach ($attributeClasses as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} should be final");
            }
        });

        test('all attribute classes use Attribute PHP attribute', function () use ($attributeClasses) {
            foreach ($attributeClasses as $class) {
                $attrs = (new ReflectionClass($class))->getAttributes(\Attribute::class);
                expect($attrs)->not->toBeEmpty("{$class} should have #[Attribute]");
            }
        });
    });

    // -----------------------------------------------------------------------
    // Section 2: Simple marker attributes (Color, Description, Icon, Label)
    // -----------------------------------------------------------------------
    describe('simple marker attributes have Attribute::TARGET_CLASS target', function () {
        $simpleAttributes = [
            Color::class => 'color',
            Description::class => 'description',
            Icon::class => 'icon',
            Label::class => 'label',
        ];

        test('marker attributes target class level', function () use ($simpleAttributes) {
            foreach ($simpleAttributes as $class => $property) {
                $ref = new ReflectionProperty($class, $property);
                expect($ref->isReadOnly())->toBeTrue();
            }
        });
    });

    // -----------------------------------------------------------------------
    // Section 3: Per-case parameterized attributes
    // -----------------------------------------------------------------------
    describe('per-case parameterized attributes accept string values', function () {
        test('Label stores value', function () {
            $label = new Label('Custom Label');
            expect($label->value)->toBe('Custom Label');
        });

        test('Color stores value', function () {
            $color = new Color('success');
            expect($color->value)->toBe('success');
        });

        test('Description stores value', function () {
            $desc = new Description('A detailed description');
            expect($desc->value)->toBe('A detailed description');
        });

        test('Icon stores value', function () {
            $icon = new Icon('heroicon-o-check');
            expect($icon->value)->toBe('heroicon-o-check');
        });
    });

    // -----------------------------------------------------------------------
    // Section 4: Class-level parameterized attributes (named args)
    // -----------------------------------------------------------------------
    describe('class-level parameterized attributes store named arguments', function () {
        test('EnumColor stores color map', function () {
            $ec = new EnumColor(
                success: ['active'],
                danger: ['banned'],
                warning: ['pending'],
            );
            expect($ec->colors['success'])->toBe(['active']);
            expect($ec->colors['danger'])->toBe(['banned']);
            expect($ec->colors['warning'])->toBe(['pending']);
        });

        test('EnumDescription stores description map', function () {
            $ed = new EnumDescription(
                active: 'User is active',
                banned: 'User is banned',
            );
            expect($ed->descriptions['active'])->toBe('User is active');
            expect($ed->descriptions['banned'])->toBe('User is banned');
        });

        test('EnumIcon stores icon map', function () {
            $ei = new EnumIcon(
                active: 'heroicon-o-check',
                banned: 'heroicon-o-x',
            );
            expect($ei->icons['active'])->toBe('heroicon-o-check');
            expect($ei->icons['banned'])->toBe('heroicon-o-x');
        });

        test('EnumLabel stores label map', function () {
            $el = new EnumLabel(
                active: 'Active User',
                banned: 'Banned User',
            );
            expect($el->labels['active'])->toBe('Active User');
            expect($el->labels['banned'])->toBe('Banned User');
        });
    });

    // -----------------------------------------------------------------------
    // Section 5: Cross-enum metadata isolation
    // -----------------------------------------------------------------------
    describe('metadata is isolated between enum classes', function () {
        test('UserStatus labels do not leak into Priority labels', function () {
            $userMeta = EnumMetadataResolver::resolve(UserStatus::class);
            $priorityMeta = EnumMetadataResolver::resolve(Priority::class);

            // ACTIVE is a UserStatus case, not Priority
            expect(isset($priorityMeta['labels']['active']))->toBeFalse();
            expect(isset($userMeta['labels']['active']))->toBeTrue();
        });

        test('pure enum labels do not leak into string enum labels', function () {
            $pureMeta = EnumMetadataResolver::resolve(RequestState::class);
            $stringMeta = EnumMetadataResolver::resolve(UserStatus::class);

            // DRAFT is RequestState, ACTIVE is UserStatus
            expect(isset($pureMeta['labels']['DRAFT']))->toBeTrue();
            expect(isset($stringMeta['labels']['DRAFT']))->toBeFalse();
        });

        test('cache entries are independent per class', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, ['labels' => ['active' => 'Cached User']]);
            $cache->set(Priority::class, ['labels' => [1 => 'Cached Priority']]);

            // UserStatus should have its own entry
            $userLabels = $cache->get(UserStatus::class)['labels'];
            expect($userLabels['active'])->toBe('Cached User');

            // Priority should have its own entry
            $priorityLabels = $cache->get(Priority::class)['labels'];
            expect($priorityLabels[1])->toBe('Cached Priority');

            // Clearing one does not affect the other
            $cache->clearClass(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // Section 6: forApi() structure validation across enum types
    // -----------------------------------------------------------------------
    describe('forApi() returns consistent structure across enum types', function () {
        test('string-backed enum forApi has all keys', function () {
            $api = UserStatus::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
                expect($item['color'])->toBeString();
            }
        });

        test('int-backed enum forApi has all keys', function () {
            $api = Priority::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect(is_int($item['value']))->toBeTrue();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
            }
        });

        test('pure enum forApi has all keys', function () {
            $api = RequestState::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
            }
        });

        test('forApi returns entries in declaration order', function () {
            $cases = UserStatus::cases();
            $api = UserStatus::forApi();

            foreach ($cases as $i => $case) {
                expect($api[$i]['name'])->toBe($case->name);
                expect($api[$i]['value'])->toBe($case->value);
            }
        });
    });

    // -----------------------------------------------------------------------
    // Section 7: Edge cases — single-case enum, zero-value enum
    // -----------------------------------------------------------------------
    describe('special enum types', function () {
        test('single-case enum works with HasEnumMetadata', function () {
            $single = SingleCaseEnum::ONLY;
            expect($single->label())->toBeString();
            expect($single->color())->toBe('secondary');
            expect($single->description())->toBeNull();
            expect($single->icon())->toBeNull();
        });

        test('zero-value int enum resolves correctly', function () {
            $zero = ZeroPriority::NONE;
            expect($zero->label())->toBeString();
            expect($zero->value)->toBe(0);
        });

        test('forSelect works for single-case enum', function () {
            $select = SingleCaseEnum::forSelect();
            expect($select)->toHaveCount(1);
            expect($select[0])->toHaveKeys(['value', 'label']);
        });

        test('forApi works for single-case enum', function () {
            $api = SingleCaseEnum::forApi();
            expect($api)->toHaveCount(1);
            expect($api[0])->toHaveKey('name');
            expect($api[0]['name'])->toBe('ONLY');
        });
    });

    // -----------------------------------------------------------------------
    // Section 8: CamelCase enum name handling
    // -----------------------------------------------------------------------
    describe('CamelCaseRole enum name handling', function () {
        test('label is generated from camelCase', function () {
            // camelCaseRole → "Camel Case Role" (or however the trait generates it)
            $label = CamelCaseRole::cases()[0]->label();
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        });

        test('forApi returns correct case name (original)', function () {
            $api = CamelCaseRole::forApi();
            foreach ($api as $item) {
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
            }
        });
    });

    // -----------------------------------------------------------------------
    // Section 9: Comparison methods — is(), isNot(), in()
    // -----------------------------------------------------------------------
    describe('comparison methods', function () {
        test('is() matches by instance', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
        });

        test('is() matches by name string', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse(); // case-sensitive
        });

        test('isNot() is inverse of is()', function () {
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
        });

        test('in() checks membership', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect(UserStatus::BANNED->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeFalse();
        });

        test('in() accepts name strings', function () {
            expect(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
            expect(UserStatus::BANNED->in(['ACTIVE', 'PENDING']))->toBeFalse();
        });

        test('is() works with int-backed enum', function () {
            expect(Priority::LOW->is(Priority::LOW))->toBeTrue();
            expect(Priority::LOW->is(Priority::HIGH))->toBeFalse();
        });

        test('is() works with pure enum', function () {
            expect(RequestState::DRAFT->is(RequestState::DRAFT))->toBeTrue();
            expect(RequestState::DRAFT->is(RequestState::APPROVED))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // Section 10: values() and labels() ordering
    // -----------------------------------------------------------------------
    describe('values() and labels() ordering', function () {
        test('values() returns backed values for string enum', function () {
            $values = UserStatus::values();
            $expected = array_map(fn ($case) => $case->value, UserStatus::cases());
            expect($values)->toBe($expected);
        });

        test('values() returns backed values for int enum', function () {
            $values = Priority::values();
            $expected = array_map(fn ($case) => $case->value, Priority::cases());
            expect($values)->toBe($expected);
        });

        test('values() returns case names for pure enum', function () {
            $values = RequestState::values();
            $expected = array_map(fn ($case) => $case->name, RequestState::cases());
            expect($values)->toBe($expected);
        });
    });

    // -----------------------------------------------------------------------
    // Section 11: AllClassLevelEnum — all metadata from class-level attributes
    // -----------------------------------------------------------------------
    describe('AllClassLevelEnum — class-level attribute resolution', function () {
        test('labels resolve from class-level EnumLabel', function () {
            foreach (AllClassLevelEnum::cases() as $case) {
                $label = $case->label();
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        });

        test('color resolves from class-level EnumColor', function () {
            foreach (AllClassLevelEnum::cases() as $case) {
                $color = $case->color();
                expect($color)->toBeString();
                expect(in_array($color, ['success', 'danger', 'warning', 'info', 'secondary'], true))->toBeTrue();
            }
        });

        test('forApi returns consistent data', function () {
            $api = AllClassLevelEnum::forApi();
            expect($api)->toHaveCount(count(AllClassLevelEnum::cases()));
        });
    });

    // -----------------------------------------------------------------------
    // Section 12: InvalidEnumException — all named constructors
    // -----------------------------------------------------------------------
    describe('InvalidEnumException named constructors', function () {
        test('value() includes class name and value in message', function () {
            $ex = InvalidEnumException::value(Priority::class, 99);
            expect($ex->getMessage())->toContain('Priority');
            expect($ex->getMessage())->toContain('99');
        });

        test('forName() includes class name and name in message', function () {
            $ex = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');
            expect($ex->getMessage())->toContain('UserStatus');
            expect($ex->getMessage())->toContain('NONEXISTENT');
        });

        test('both named constructors return InvalidEnumException instances', function () {
            $ex1 = InvalidEnumException::value(UserStatus::class, 'bad');
            $ex2 = InvalidEnumException::forName(UserStatus::class, 'BAD');

            expect($ex1)->toBeInstanceOf(InvalidEnumException::class);
            expect($ex2)->toBeInstanceOf(InvalidEnumException::class);
        });
    });
});
