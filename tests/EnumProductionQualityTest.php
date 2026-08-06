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
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;

describe('Enum Production Readiness', function () {
    describe('Attribute final classes', function () {
        it('all attribute classes are final', function () {
            $attributes = [
                Color::class,
                Description::class,
                EnumColor::class,
                EnumDescription::class,
                EnumIcon::class,
                EnumLabel::class,
                Icon::class,
                Label::class,
            ];

            foreach ($attributes as $attr) {
                $ref = new ReflectionClass($attr);
                expect($ref->isFinal())->toBeTrue("{$attr} must be final");
            }
        });
    });

    describe('Attribute readonly properties', function () {
        it('Color has readonly value property', function () {
            $ref = new ReflectionProperty(Color::class, 'value');
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('Label has readonly value property', function () {
            $ref = new ReflectionProperty(Label::class, 'value');
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('EnumColor has all readonly properties', function () {
            foreach (['success', 'danger', 'warning', 'info', 'secondary'] as $prop) {
                $ref = new ReflectionProperty(EnumColor::class, $prop);
                expect($ref->isReadOnly())->toBeTrue("EnumColor::\${$prop} must be readonly");
            }
        });

        it('EnumLabel has readonly properties', function () {
            foreach (['labels', 'label'] as $prop) {
                $ref = new ReflectionProperty(EnumLabel::class, $prop);
                expect($ref->isReadOnly())->toBeTrue("EnumLabel::\${$prop} must be readonly");
            }
        });
    });

    describe('Attribute target declarations', function () {
        it('per-case attributes target class constants only', function () {
            $caseAttrs = [Color::class, Description::class, Icon::class, Label::class];

            foreach ($caseAttrs as $attr) {
                $ref = new ReflectionClass($attr);
                $attrs = $ref->getAttributes(Attribute::class);
                expect($attrs)->not->toBeEmpty();

                $instance = $attrs[0]->newInstance();
                expect($instance->flags)->toBe(Attribute::TARGET_CLASS_CONSTANT);
            }
        });

        it('class-level attributes target both class and constants', function () {
            $classAttrs = [EnumColor::class, EnumLabel::class];

            foreach ($classAttrs as $attr) {
                $ref = new ReflectionClass($attr);
                $attrs = $ref->getAttributes(Attribute::class);
                $instance = $attrs[0]->newInstance();
                $expected = Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT;
                expect($instance->flags)->toBe($expected);
            }
        });
    });

    describe('Strict types enforcement', function () {
        it('all source files declare strict types', function () {
            $dir = dirname(__DIR__, 2).'/src';
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                expect($content)->toContain('declare(strict_types=1)');
            }
        });
    });

    describe('Return type declarations', function () {
        it('HasEnumMetadata trait methods have return types', function () {
            $ref = new ReflectionClass(UserStatus::class);
            $traitMethods = ['label', 'description', 'color', 'icon', 'forSelect', 'forApi', 'values', 'labels'];

            foreach ($traitMethods as $method) {
                $methodRef = $ref->getMethod($method);
                $returnType = $methodRef->getReturnType();
                expect($returnType)->not->toBeNull("{$method}() must have a return type");
            }
        });

        it('EnumRule methods have return types', function () {
            $ref = new ReflectionClass(EnumRule::class);

            $method = $ref->getMethod('validate');
            expect($method->getReturnType()?->getName())->toBe('void');

            $method = $ref->getMethod('for');
            expect($method->getReturnType()?->getName())->toBe(EnumRule::class);

            $method = $ref->getMethod('nullable');
            expect($method->getReturnType()?->getName())->toBe(EnumRule::class);
        });
    });

    describe('EnumCache singleton behavior', function () {
        beforeEach(function () {
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::resetInstance();
        });

        it('getInstance returns same instance', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('flush clears all cached metadata', function () {
            $cache = EnumCache::getInstance();
            $cache->set('TestEnum', [
                'labels' => ['test' => 'Test'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeTrue();
            EnumCache::flush();
            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('clearClass clears only specified class', function () {
            $cache = EnumCache::getInstance();
            $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('EnumB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $cache->clearClass('EnumA');
            expect($cache->has('EnumA'))->toBeFalse();
            expect($cache->has('EnumB'))->toBeTrue();
        });

        it('TTL zero disables caching', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set('TestEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('get throws on missing entry', function () {
            $cache = EnumCache::getInstance();

            expect(fn () => $cache->get('NonExistent'))
                ->toThrow(OutOfBoundsException::class);
        });
    });

    describe('Int-backed enum compatibility', function () {
        it('Priority values returns ints', function () {
            $values = Priority::values();
            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('Priority forSelect has int values', function () {
            $select = Priority::forSelect();
            foreach ($select as $option) {
                expect($option['value'])->toBeInt();
                expect($option['label'])->toBeString();
            }
        });

        it('Priority forApi has int values', function () {
            $api = Priority::forApi();
            foreach ($api as $item) {
                expect($item['value'])->toBeInt();
                expect($item['name'])->toBeString();
            }
        });
    });

    describe('String-backed enum compatibility', function () {
        it('UserStatus values returns strings', function () {
            $values = UserStatus::values();
            foreach ($values as $v) {
                expect($v)->toBeString();
            }
        });

        it('UserStatus ACTIVE has custom label', function () {
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
        });

        it('UserStatus ACTIVE has custom icon', function () {
            expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
        });

        it('UserStatus ACTIVE has custom description', function () {
            expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
        });

        it('UserStatus INACTIVE has auto-generated label', function () {
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });
    });

    describe('Class-level metadata', function () {
        it('TicketStatus uses class-level labels', function () {
            expect(TicketStatus::OPEN->label())->toBe('Open');
            expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
            expect(TicketStatus::CLOSED->label())->toBe('Closed');
        });

        it('TicketStatus uses class-level descriptions', function () {
            expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
            expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
        });

        it('TicketStatus uses class-level default icon', function () {
            expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');
            expect(TicketStatus::IN_PROGRESS->icon())->toBe('heroicon-o-ticket');
        });
    });

    describe('Comparison methods strictness', function () {
        it('is() uses strict identity for instances', function () {
            $a = UserStatus::ACTIVE;
            $b = UserStatus::ACTIVE;

            // Enum singletons — same case is always same instance
            expect($a)->toBe($b);
            expect($a->is($b))->toBeTrue();
        });

        it('is() is case-sensitive for string names', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
        });

        it('in() works with mixed instance and string args', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['PENDING', 'BANNED']))->toBeFalse();
        });

        it('in() returns false for empty array', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });
    });

    describe('Reverse lookup edge cases', function () {
        it('tryFromLabel is case-insensitive', function () {
            expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel returns null for non-existent label', function () {
            expect(UserStatus::tryFromLabel('Does Not Exist'))->toBeNull();
        });

        it('fromName throws on non-existent case', function () {
            expect(fn () => UserStatus::fromName('NON_EXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('tryFromName returns null for non-existent case', function () {
            expect(UserStatus::tryFromName('NON_EXISTENT'))->toBeNull();
        });

        it('hasCase returns correct booleans', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('active'))->toBeFalse();
        });
    });

    describe('forApi structure completeness', function () {
        it('returns all expected keys', function () {
            $api = UserStatus::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            }
        });

        it('has correct count matching cases', function () {
            expect(UserStatus::forApi())->toHaveCount(count(UserStatus::cases()));
            expect(Priority::forApi())->toHaveCount(count(Priority::cases()));
        });
    });

    describe('forSelect structure', function () {
        it('returns only value and label', function () {
            $select = UserStatus::forSelect();

            foreach ($select as $option) {
                expect(array_keys($option))->toBe(['value', 'label']);
            }
        });
    });

    describe('InvalidEnumException factories', function () {
        it('value() includes type info', function () {
            $e = InvalidEnumException::value(UserStatus::class, 'invalid');
            expect($e->getMessage())->toContain('invalid');
            expect($e->getMessage())->toContain(UserStatus::class);
        });

        it('forName() includes case name', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN');
            expect($e->getMessage())->toContain('UNKNOWN');
            expect($e->getMessage())->toContain(UserStatus::class);
        });
    });

    describe('EnumRule validation edge cases', function () {
        it('rejects null when not nullable', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = fn (string $msg): string => $msg;
            $failed = false;
            $message = '';

            $rule->validate('status', null, function (string $msg) use (&$failed, &$message): void {
                $failed = true;
                $message = $msg;
            });

            expect($failed)->toBeTrue();
            expect($message)->toBeString();
        });

        it('allows null when nullable', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', null, function (string $msg) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });
    });
});
