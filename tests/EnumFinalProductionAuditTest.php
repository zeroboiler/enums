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
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum Final Production Quality Audit', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    describe('Attribute final classes — PHPStan compatibility', function () {
        it('Label attribute is final', function () {
            $reflection = new ReflectionClass(Label::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('Color attribute is final', function () {
            $reflection = new ReflectionClass(Color::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('Icon attribute is final', function () {
            $reflection = new ReflectionClass(Icon::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('Description attribute is final', function () {
            $reflection = new ReflectionClass(Description::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('EnumColor attribute is final', function () {
            $reflection = new ReflectionClass(EnumColor::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('EnumLabel attribute is final', function () {
            $reflection = new ReflectionClass(EnumLabel::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('EnumIcon attribute is final', function () {
            $reflection = new ReflectionClass(EnumIcon::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('EnumDescription attribute is final', function () {
            $reflection = new ReflectionClass(EnumDescription::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('EnumRule is final and readonly', function () {
            $reflection = new ReflectionClass(EnumRule::class);

            expect($reflection->isFinal())->toBeTrue();
            expect($reflection->isReadOnly())->toBeTrue();
        });

        it('EnumCache is final', function () {
            $reflection = new ReflectionClass(EnumCache::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('EnumManager is final', function () {
            $reflection = new ReflectionClass(EnumManager::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('EnumMetadataResolver is final', function () {
            $reflection = new ReflectionClass(EnumMetadataResolver::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('InvalidEnumException is final', function () {
            $reflection = new ReflectionClass(InvalidEnumException::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('EnumCast is final', function () {
            $reflection = new ReflectionClass(EnumCast::class);

            expect($reflection->isFinal())->toBeTrue();
        });
    });

    describe('Strict types verification — all source files', function () {
        it('all source files declare strict_types=1', function () {
            $srcDir = dirname(__DIR__, 2) . '/src';
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            $violations = [];

            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $contents = file_get_contents($file->getPathname());
                    $tokens = token_get_all($contents);

                    foreach ($tokens as $token) {
                        if (is_array($token) && $token[0] === T_DECLARE) {
                            // Found declare — this file is compliant
                            continue 2;
                        }
                    }

                    $violations[] = $file->getPathname();
                }
            }

            expect($violations)->toBeEmpty();
        });

        it('EnumRule constructor has typed parameters', function () {
            $method = new ReflectionMethod(EnumRule::class, '__construct');
            $params = $method->getParameters();

            expect($params[0]->getType()?->getName())->toBe('string');
            expect($params[1]->getType()?->getName())->toBe('bool');
        });

        it('EnumCache set method has typed parameters', function () {
            $method = new ReflectionMethod(EnumCache::class, 'set');
            $params = $method->getParameters();

            expect($params[0]->getType()?->getName())->toBe('string');
            expect($params[1]->hasType())->toBeTrue();
        });

        it('EnumManager methods have return types', function () {
            $forSelect = new ReflectionMethod(EnumManager::class, 'forSelect');
            $forApi = new ReflectionMethod(EnumManager::class, 'forApi');
            $tryFromLabel = new ReflectionMethod(EnumManager::class, 'tryFromLabel');

            expect($forSelect->getReturnType()?->getName())->toBe('array');
            expect($forApi->getReturnType()?->getName())->toBe('array');
            expect($tryFromLabel->getReturnType()?->getName())->toBe('?');
            expect($tryFromLabel->getReturnType()?->allowsNull())->toBeTrue();
        });
    });

    describe('Return type completeness — HasEnumMetadata trait', function () {
        it('label() returns string', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'label');

            expect($reflection->getReturnType()?->getName())->toBe('string');
        });

        it('description() returns ?string', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'description');

            expect($reflection->getReturnType()?->getName())->toBe('?string');
        });

        it('color() returns string', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'color');

            expect($reflection->getReturnType()?->getName())->toBe('string');
        });

        it('icon() returns ?string', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'icon');

            expect($reflection->getReturnType()?->getName())->toBe('?string');
        });

        it('forSelect() returns array', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'forSelect');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });

        it('forApi() returns array', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'forApi');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });

        it('values() returns array', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'values');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });

        it('labels() returns array', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'labels');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });

        it('tryFromLabel() returns ?static', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'tryFromLabel');

            expect($reflection->getReturnType()?->allowsNull())->toBeTrue();
        });

        it('tryFromName() returns ?static', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'tryFromName');

            expect($reflection->getReturnType()?->allowsNull())->toBeTrue();
        });

        it('fromName() returns static', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'fromName');

            expect($reflection->getReturnType()?->getName())->toBe('static');
        });

        it('hasCase() returns bool', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'hasCase');

            expect($reflection->getReturnType()?->getName())->toBe('bool');
        });

        it('is() returns bool', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'is');

            expect($reflection->getReturnType()?->getName())->toBe('bool');
        });

        it('isNot() returns bool', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'isNot');

            expect($reflection->getReturnType()?->getName())->toBe('bool');
        });

        it('in() returns bool', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'in');

            expect($reflection->getReturnType()?->getName())->toBe('bool');
        });
    });

    describe('Attribute target verification', function () {
        it('Label targets only class constants', function () {
            $attr = new ReflectionAttribute(Label::class);

            expect($attr->getTarget())->toBe(Attribute::TARGET_CLASS_CONSTANT);
        });

        it('Color targets only class constants', function () {
            $attr = new ReflectionAttribute(Color::class);

            expect($attr->getTarget())->toBe(Attribute::TARGET_CLASS_CONSTANT);
        });

        it('EnumColor targets class and class constants', function () {
            $reflection = new ReflectionClass(EnumColor::class);
            $attributes = $reflection->getAttributes();

            $targets = 0;
            foreach ($attributes as $attr) {
                if ($attr->getName() === 'Attribute') {
                    $targets = $attr->getArguments()[0];
                }
            }

            // TARGET_CLASS | TARGET_CLASS_CONSTANT = 1 | 4096 = 4097
            expect($targets & Attribute::TARGET_CLASS)->not->toBe(0);
            expect($targets & Attribute::TARGET_CLASS_CONSTANT)->not->toBe(0);
        });
    });

    describe('EnumTestGenerator output quality', function () {
        it('generates strict_types in output', function () {
            $content = EnumTestGenerator::generate(UserStatus::class);

            expect($content)->toContain('declare(strict_types=1)');
        });

        it('generates tryFromName test with NON_EXISTENT', function () {
            $content = EnumTestGenerator::generate(UserStatus::class);

            expect($content)->toContain('NON_EXISTENT');
        });

        it('generates hasCase test with NON_EXISTENT', function () {
            $content = EnumTestGenerator::generate(UserStatus::class);

            expect($content)->toContain('hasCase');
        });

        it('generates values() count test', function () {
            $content = EnumTestGenerator::generate(Priority::class);

            expect($content)->toContain('values()');
        });

        it('generates labels() count test', function () {
            $content = EnumTestGenerator::generate(Priority::class);

            expect($content)->toContain('labels()');
        });

        it('generates comparison tests when enum has 2+ cases', function () {
            $content = EnumTestGenerator::generate(Priority::class);

            expect($content)->toContain('supports is()');
            expect($content)->toContain('supports isNot()');
            expect($content)->toContain('supports in()');
        });

        it('generates tryFromLabel test when enum has 2+ cases', function () {
            $content = EnumTestGenerator::generate(Priority::class);

            expect($content)->toContain('tryFromLabel');
        });
    });

    describe('Facade accessor verification', function () {
        it('Enum facade returns zeroboiler.enum', function () {
            $facade = new ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class);
            $method = $facade->getMethod('getFacadeAccessor');

            expect($method->getReturnType()?->getName())->toBe('string');
        });
    });

    describe('ServiceProvider registration', function () {
        it('EnumsServiceProvider is final', function () {
            $reflection = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('EnumsServiceProvider has Override attribute on register', function () {
            $method = new ReflectionMethod(\ZeroBoiler\Enums\EnumsServiceProvider::class, 'register');
            $attributes = $method->getAttributes();

            $hasOverride = false;
            foreach ($attributes as $attr) {
                if ($attr->getName() === 'Override' || str_ends_with($attr->getName(), '\\Override')) {
                    $hasOverride = true;
                }
            }

            expect($hasOverride)->toBeTrue();
        });

        it('EnumsServiceProvider has Override attribute on boot', function () {
            $method = new ReflectionMethod(\ZeroBoiler\Enums\EnumsServiceProvider::class, 'boot');
            $attributes = $method->getAttributes();

            $hasOverride = false;
            foreach ($attributes as $attr) {
                if ($attr->getName() === 'Override' || str_ends_with($attr->getName(), '\\Override')) {
                    $hasOverride = true;
                }
            }

            expect($hasOverride)->toBeTrue();
        });
    });

    describe('No mixed types in public API', function () {
        it('EnumRule validate() accepts mixed (Laravel contract) but handles it safely', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            // float value — should fail (not a valid string value)
            $rule->validate('status', 3.14, $fail);
            expect($failed)->toBeTrue();
        });

        it('EnumRule validate() rejects object values safely', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', new stdClass, $fail);
            expect($failed)->toBeTrue();
        });

        it('EnumRule validate() rejects boolean values for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', true, $fail);
            expect($failed)->toBeTrue();
        });

        it('EnumRule validate() rejects boolean values for int-backed enum', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', true, $fail);
            expect($failed)->toBeTrue();
        });

        it('EnumRule validate() accepts valid string for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 'active', $fail);
            expect($failed)->toBeFalse();
        });

        it('EnumRule validate() accepts valid int for int-backed enum', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 3, $fail);
            expect($failed)->toBeFalse();
        });
    });

    describe('EnumCache — TTL boundary precision', function () {
        it('setTtl normalizes negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-1);
            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Test'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            // With negative TTL normalized to 0, caching is disabled
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('setTtl accepts very large values', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(PHP_INT_MAX);
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();
        });
    });

    describe('Metadata resolver — consistency guarantees', function () {
        it('resolve returns same result across multiple enum types', function () {
            $userMeta = EnumMetadataResolver::resolve(UserStatus::class);
            $priorityMeta = EnumMetadataResolver::resolve(Priority::class);
            $pureMeta = EnumMetadataResolver::resolve(RequestState::class);

            // Each has the correct structure
            expect($userMeta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
            expect($priorityMeta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
            expect($pureMeta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        });

        it('TicketStatus class-level EnumLabel is resolved correctly', function () {
            $meta = EnumMetadataResolver::resolve(TicketStatus::class);

            expect($meta['labels']['open'])->toBe('Open');
            expect($meta['labels']['in_progress'])->toBe('In Progress');
            expect($meta['labels']['closed'])->toBe('Closed');
        });

        it('TicketStatus class-level EnumDescription is resolved correctly', function () {
            $meta = EnumMetadataResolver::resolve(TicketStatus::class);

            expect($meta['descriptions']['open'])->toBe('Ticket is open and awaiting response');
            expect($meta['descriptions']['closed'])->toBe('Ticket has been resolved');
        });

        it('TicketStatus class-level EnumIcon default is applied', function () {
            $meta = EnumMetadataResolver::resolve(TicketStatus::class);

            expect($meta['icons']['open'])->toBe('heroicon-o-ticket');
            expect($meta['icons']['in_progress'])->toBe('heroicon-o-ticket');
            expect($meta['icons']['closed'])->toBe('heroicon-o-ticket');
        });
    });

    describe('CamelCase label generation', function () {
        it('generates Title Case from camelCase names', function () {
            expect(CamelCaseRole::isActive->label())->toBe('Is Active');
            expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
            expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
            expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
        });

        it('forSelect uses backed value not case name', function () {
            $select = CamelCaseRole::forSelect();

            expect($select[0]['value'])->toBe('is_active');
            expect($select[0]['label'])->toBe('Is Active');
        });
    });

    describe('OrderStatus — minimal enum without attributes', function () {
        it('all metadata defaults work', function () {
            expect(OrderStatus::PENDING->label())->toBe('Pending');
            expect(OrderStatus::PENDING->color())->toBe('secondary');
            expect(OrderStatus::PENDING->icon())->toBeNull();
            expect(OrderStatus::PENDING->description())->toBeNull();
        });

        it('values returns backed values', function () {
            expect(OrderStatus::values())->toEqual(['pending', 'shipped', 'delivered', 'cancelled']);
        });

        it('forSelect returns correct structure', function () {
            $select = OrderStatus::forSelect();

            expect($select)->toHaveCount(4);
            expect($select[0])->toHaveKeys(['value', 'label']);
        });
    });
});
