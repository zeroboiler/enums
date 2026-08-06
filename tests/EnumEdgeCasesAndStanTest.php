<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum Edge Cases & PHPStan L9 Compliance', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    describe('HasEnumMetadata — string-backed enum', function () {
        it('returns backed value from forSelect (not case name)', function () {
            $select = UserStatus::forSelect();

            expect($select[0]['value'])->toBe('active');
            expect($select[0]['value'])->not->toBe('ACTIVE');
        });

        it('returns case name from forApi', function () {
            $api = UserStatus::forApi();

            expect($api[0]['name'])->toBe('ACTIVE');
            expect($api[0]['value'])->toBe('active');
        });

        it('resolves label with per-case override taking precedence over class-level', function () {
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
            expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
            expect(UserStatus::INACTIVE->label())->toBe('Inactive'); // auto-generated
        });

        it('resolves color with per-case override over class-level', function () {
            expect(UserStatus::ACTIVE->color())->toBe('success');
            expect(UserStatus::BANNED->color())->toBe('danger');
            expect(UserStatus::SUSPENDED->color())->toBe('warning');
        });

        it('defaults to secondary for unset colors', function () {
            expect(UserStatus::INACTIVE->color())->toBe('secondary');
        });

        it('returns null for unset icons and descriptions', function () {
            expect(UserStatus::INACTIVE->icon())->toBeNull();
            expect(UserStatus::INACTIVE->description())->toBeNull();
        });

        it('tryFromLabel is case-insensitive', function () {
            expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel returns null for non-existent labels', function () {
            expect(UserStatus::tryFromLabel('nonexistent'))->toBeNull();
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });

        it('forSelect has unique values', function () {
            $values = array_column(UserStatus::forSelect(), 'value');
            expect($values)->toEqual(array_unique($values));
        });

        it('values() returns backed values for backed enums', function () {
            $values = UserStatus::values();
            expect($values)->toContain('active');
            expect($values)->toContain('banned');
            expect(in_array('ACTIVE', $values, true))->toBeFalse();
        });

        it('labels() returns all labels in case declaration order', function () {
            $labels = UserStatus::labels();
            expect(count($labels))->toBe(5);
            expect($labels[0])->toBe('Active User');
        });
    });

    describe('HasEnumMetadata — int-backed enum', function () {
        it('returns int values from forSelect', function () {
            $select = Priority::forSelect();

            expect($select[0]['value'])->toBe(1);
            expect(is_int($select[0]['value']))->toBeTrue();
        });

        it('returns int values from values()', function () {
            $values = Priority::values();
            expect($values)->toEqual([1, 2, 3, 4]);
        });

        it('returns int values from forApi', function () {
            $api = Priority::forApi();
            expect($api[0]['value'])->toBe(1);
            expect($api[0]['name'])->toBe('LOW');
        });

        it('auto-generates labels from case names', function () {
            expect(Priority::LOW->label())->toBe('Low');
            expect(Priority::URGENT->label())->toBe('Urgent');
        });
    });

    describe('HasEnumMetadata — int-backed enum with zero value', function () {
        it('handles zero value correctly in values()', function () {
            $values = ZeroPriority::values();
            expect($values)->toContain(0);
            expect($values)->toEqual([0, 1, 2]);
        });

        it('handles zero value in forSelect()', function () {
            $select = ZeroPriority::forSelect();
            expect($select[0]['value'])->toBe(0);
        });

        it('auto-generates label for NONE case', function () {
            expect(ZeroPriority::NONE->label())->toBe('None');
        });
    });

    describe('HasEnumMetadata — pure enum', function () {
        it('returns case names as values for pure enums', function () {
            $values = RequestState::values();
            expect($values)->toEqual(['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED']);
        });

        it('returns case names in forSelect for pure enums', function () {
            $select = RequestState::forSelect();
            expect($select[0]['value'])->toBe('DRAFT');
            expect(is_string($select[0]['value']))->toBeTrue();
        });

        it('returns case names in forApi for pure enums', function () {
            $api = RequestState::forApi();
            expect($api[0]['value'])->toBe('DRAFT');
            expect($api[0]['name'])->toBe('DRAFT');
        });

        it('auto-generates labels for pure enums', function () {
            expect(RequestState::DRAFT->label())->toBe('Draft');
            expect(RequestState::SUBMITTED->label())->toBe('Submitted');
        });

        it('tryFromLabel works for pure enums', function () {
            expect(RequestState::tryFromLabel('Draft'))->toBe(RequestState::DRAFT);
            expect(RequestState::tryFromLabel('draft'))->toBe(RequestState::DRAFT);
        });
    });

    describe('tryFromName / fromName / hasCase', function () {
        it('resolves by case name for string-backed enum', function () {
            expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromName('active'))->toBeNull();
        });

        it('resolves by case name for int-backed enum', function () {
            expect(Priority::tryFromName('HIGH'))->toBe(Priority::HIGH);
        });

        it('resolves by case name for pure enum', function () {
            expect(RequestState::tryFromName('DRAFT'))->toBe(RequestState::DRAFT);
        });

        it('fromName throws on invalid name', function () {
            expect(fn () => UserStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('fromName exception contains class and name info', function () {
            try {
                UserStatus::fromName('NONEXISTENT');
                expect(true)->toBeFalse('Should have thrown');
            } catch (InvalidEnumException $e) {
                expect($e->getMessage())->toContain('NONEXISTENT');
                expect($e->getMessage())->toContain(UserStatus::class);
            }
        });

        it('hasCase returns correct booleans', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
            expect(UserStatus::hasCase('active'))->toBeFalse(); // case-sensitive
        });
    });

    describe('EnumCache', function () {
        it('singleton returns same instance', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('flush resets cache', function () {
            $cache = EnumCache::getInstance();
            $cache->set('TestEnum', [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeTrue();

            $cache->clear();

            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('clearClass removes specific class only', function () {
            $cache = EnumCache::getInstance();
            $cache->set('EnumA', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set('EnumB', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->clearClass('EnumA');

            expect($cache->has('EnumA'))->toBeFalse();
            expect($cache->has('EnumB'))->toBeTrue();
        });

        it('TTL=0 means no caching', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set('TestEnum', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('resetInstance allows creating fresh singleton', function () {
            $a = EnumCache::getInstance();
            EnumCache::resetInstance();
            $b = EnumCache::getInstance();

            expect($a)->not->toBe($b);
        });
    });

    describe('InvalidEnumException', function () {
        it('value() includes actual value in message', function () {
            $e = InvalidEnumException::value(UserStatus::class, 'invalid');
            expect($e->getMessage())->toContain('invalid');
            expect($e->getMessage())->toContain(UserStatus::class);
            expect($e->getMessage())->toContain('not a valid case');
        });

        it('value() handles null input', function () {
            $e = InvalidEnumException::value(Priority::class, null);
            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain(Priority::class);
        });

        it('value() handles int input', function () {
            $e = InvalidEnumException::value(Priority::class, 99);
            expect($e->getMessage())->toContain('99');
            expect($e->getMessage())->toContain(Priority::class);
        });

        it('forName() formats with class and name', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN');
            expect($e->getMessage())->toContain('UNKNOWN');
            expect($e->getMessage())->toContain(UserStatus::class);
        });
    });

    describe('EnumManager', function () {
        it('forSelect delegates to enum trait', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $result = $manager->forSelect(UserStatus::class);

            expect($result)->toBeArray();
            expect($result[0])->toHaveKeys(['value', 'label']);
        });

        it('forApi delegates to enum trait', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $result = $manager->forApi(UserStatus::class);

            expect($result)->toBeArray();
            expect($result[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('tryFromLabel delegates to enum trait', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $result = $manager->tryFromLabel(UserStatus::class, 'Active User');

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('throws BadMethodCallException for enum without trait', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;

            // Use a plain PHP enum without HasEnumMetadata
            enum PlainEnum: string { case A = 'a'; }

            expect(fn () => $manager->forSelect(PlainEnum::class))
                ->toThrow(\BadMethodCallException::class);
        });
    });

    describe('Attribute consistency', function () {
        it('all per-case attributes are final', function () {
            $attrs = [
                \ZeroBoiler\Enums\Attributes\Color::class,
                \ZeroBoiler\Enums\Attributes\Description::class,
                \ZeroBoiler\Enums\Attributes\Icon::class,
                \ZeroBoiler\Enums\Attributes\Label::class,
            ];

            foreach ($attrs as $attr) {
                $ref = new \ReflectionClass($attr);
                expect($ref->isFinal())->toBeTrue("{$attr} should be final");
            }
        });

        it('all class-level attributes are final', function () {
            $attrs = [
                \ZeroBoiler\Enums\Attributes\EnumColor::class,
                \ZeroBoiler\Enums\Attributes\EnumDescription::class,
                \ZeroBoiler\Enums\Attributes\EnumIcon::class,
                \ZeroBoiler\Enums\Attributes\EnumLabel::class,
            ];

            foreach ($attrs as $attr) {
                $ref = new \ReflectionClass($attr);
                expect($ref->isFinal())->toBeTrue("{$attr} should be final");
            }
        });

        it('EnumColor has readonly promoted properties', function () {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\Attributes\EnumColor::class);
            $props = $ref->getProperties();

            foreach ($props as $prop) {
                expect($prop->isReadOnly())->toBeTrue(
                    "EnumColor::\${$prop->name} should be readonly"
                );
            }
        });

        it('EnumLabel has readonly promoted properties', function () {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\Attributes\EnumLabel::class);
            $props = $ref->getProperties();

            foreach ($props as $prop) {
                expect($prop->isReadOnly())->toBeTrue(
                    "EnumLabel::\${$prop->name} should be readonly"
                );
            }
        });

        it('EnumDescription has readonly promoted properties', function () {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\Attributes\EnumDescription::class);
            $props = $ref->getProperties();

            foreach ($props as $prop) {
                expect($prop->isReadOnly())->toBeTrue(
                    "EnumDescription::\${$prop->name} should be readonly"
                );
            }
        });

        it('EnumIcon has readonly promoted properties', function () {
            $ref = new \ReflectionClass(\ZeroBoiler\Enums\Attributes\EnumIcon::class);
            $props = $ref->getProperties();

            foreach ($props as $prop) {
                expect($prop->isReadOnly())->toBeTrue(
                    "EnumIcon::\${$prop->name} should be readonly"
                );
            }
        });
    });
});
