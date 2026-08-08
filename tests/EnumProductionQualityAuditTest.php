<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum Production Quality Audit', function () {
    describe('Strict types enforcement', function () {
        it('all source files have declare(strict_types=1)', function () {
            $srcDir = dirname(__DIR__, 2).'/src';
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            $violations = [];
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = $file->getContents();
                    if (! str_contains($content, 'declare(strict_types=1)')) {
                        $violations[] = $file->getPathname();
                    }
                }
            }

            expect($violations)->toBeEmpty(
                'All PHP files must declare strict_types=1. Violations: '.implode(', ', $violations)
            );
        });
    });

    describe('Return type declarations', function () {
        it('HasEnumMetadata trait methods all have return types', function () {
            $ref = new ReflectionClass(HasEnumMetadata::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            $violations = [];
            foreach ($methods as $method) {
                if ($method->name === 'generateLabel') {
                    continue; // private method
                }
                $returnType = $method->getReturnType();
                if ($returnType === null) {
                    $violations[] = $method->name;
                }
            }

            expect($violations)->toBeEmpty(
                'All public trait methods must have return types. Missing: '.implode(', ', $violations)
            );
        });
    });

    describe('Typed properties on attribute classes', function () {
        it('all attribute classes use readonly promoted properties', function () {
            $attributeClasses = [
                \ZeroBoiler\Enums\Attributes\Label::class,
                \ZeroBoiler\Enums\Attributes\Color::class,
                \ZeroBoiler\Enums\Attributes\Icon::class,
                \ZeroBoiler\Enums\Attributes\Description::class,
            ];

            foreach ($attributeClasses as $class) {
                $ref = new ReflectionClass($class);
                $props = $ref->getProperties();

                foreach ($props as $prop) {
                    expect($prop->isReadOnly())->toBeTrue("{$class}::\${$prop->name} must be readonly");
                    expect($prop->hasType())->toBeTrue("{$class}::\${$prop->name} must have a type declaration");
                }
            }
        });
    });

    describe('No mixed types in public API', function () {
        it('EnumRule validate method accepts only typed parameters', function () {
            $ref = new ReflectionMethod(EnumRule::class, 'validate');
            $params = $ref->getParameters();

            // attribute: string (typed)
            expect($params[0]->getType()->getName())->toBe('string');

            // value: mixed (acceptable for ValidationRule interface)
            expect($params[1]->getType()->getName())->toBe('mixed');

            // fail: Closure (typed)
            expect($params[2]->getType()->getName())->toBe(Closure::class);
        });
    });

    describe('Docblock completeness', function () {
        it('all public methods on EnumCache have docblocks', function () {
            $ref = new ReflectionClass(EnumCache::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $doc = $method->getDocComment();
                expect($doc)->not->toBeFalse(
                    "EnumCache::{$method->name}() must have a docblock"
                );
            }
        });

        it('EnumMetadataResolver::resolve has complete phpstan type', function () {
            $ref = new ReflectionMethod(EnumMetadataResolver::class, 'resolve');
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse();
            expect($doc)->toContain('@return');
        });
    });

    describe('Cache thread-safety documentation', function () {
        it('EnumCache singleton returns same instance', function () {
            EnumCache::resetInstance();
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();
            expect($a)->toBe($b);
            EnumCache::resetInstance();
        });

        it('EnumCache TTL of zero means no caching', function () {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set('TestEnum', [
                'labels' => ['x' => 'Test'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeFalse();
            EnumCache::resetInstance();
        });
    });

    describe('EnumRule type safety edge cases', function () {
        it('rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(IntStatusWithColor::class);
            $fail = fn (string $_, ?string $_msg = null): mixed => true;
            $result = null;

            // Int-backed enum: string 'abc' should fail
            $rule->validate('status', 'abc', function (string $msg) use (&$result): void {
                $result = $msg;
            });

            expect($result)->not->toBeNull();
        });

        it('accepts int value for int-backed enum', function () {
            $rule = EnumRule::for(IntStatusWithColor::class);
            $result = null;

            $rule->validate('status', 1, function (string $msg) use (&$result): void {
                $result = $msg;
            });

            expect($result)->toBeNull(); // no failure callback triggered
        });

        it('rejects int value for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $result = null;

            $rule->validate('status', 42, function (string $msg) use (&$result): void {
                $result = $msg;
            });

            expect($result)->not->toBeNull();
        });

        it('accepts string value for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $result = null;

            $rule->validate('status', 'active', function (string $msg) use (&$result): void {
                $result = $msg;
            });

            expect($result)->toBeNull();
        });
    });

    describe('Pure enum behavior', function () {
        it('values() returns case names for pure enums', function () {
            $values = PureFeatureFlag::values();

            expect($values)->toBe([
                'TWO_FACTOR_AUTH',
                'DARK_MODE',
            ]);
        });

        it('forSelect() uses case names as values for pure enums', function () {
            $select = PureFeatureFlag::forSelect();

            expect($select[0])->toHaveKey('value');
            expect($select[0]['value'])->toBe('TWO_FACTOR_AUTH');
            expect($select[0])->toHaveKey('label');
        });

        it('forApi() includes null description for pure enums without description', function () {
            $api = PureFeatureFlag::forApi();

            expect($api[0])->toHaveKey('description');
            expect($api[0]['description'])->toBeNull();
        });
    });

    describe('Single-case enum', function () {
        it('forSelect() returns one entry', function () {
            $select = SingleCaseEnum::forSelect();

            expect($select)->toHaveCount(1);
        });

        it('in() works with single element array', function () {
            expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
        });
    });

    describe('CamelCase label generation', function () {
        it('generates title case from camelCase case names', function () {
            expect(CamelCaseRole::isActive->label())->toBe('Is Active');
            expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
            expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
        });
    });

    describe('InvalidEnumException named constructors', function () {
        it('value() includes the value in message', function () {
            $ex = InvalidEnumException::value('UserStatus', 'invalid_value');

            expect($ex->getMessage())->toContain('invalid_value');
            expect($ex->getMessage())->toContain('UserStatus');
        });

        it('forName() includes the name in message', function () {
            $ex = InvalidEnumException::forName('UserStatus', 'NONEXISTENT');

            expect($ex->getMessage())->toContain('NONEXISTENT');
            expect($ex->getMessage())->toContain('UserStatus');
        });

        it('value() handles null gracefully', function () {
            $ex = InvalidEnumException::value('UserStatus', null);

            expect($ex->getMessage())->toContain('null');
        });
    });
});
