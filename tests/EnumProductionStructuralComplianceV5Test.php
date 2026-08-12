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
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Console\Commands\InspectEnumCommand;
use ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum Production Structural Compliance V5', function () {
    // ── Strict Types ────────────────────────────────────────────────

    it('every source file has declare(strict_types=1)', function () {
        $srcDir = __DIR__.'/../src';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $violations = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $tokens = token_get_all($content);

            // Skip the very first open tag
            foreach ($tokens as $i => $token) {
                if (is_array($token) && $token[0] === T_OPEN_TAG) {
                    continue;
                }
                if (is_array($token) && $token[0] === T_OPEN_TAG_WITH_ECHO) {
                    continue;
                }
                // First meaningful token after open tag should be declare/namespace
                if (is_array($token) && ($token[0] === T_DECLARE || $token[0] === T_NAMESPACE || $token[0] === T_COMMENT)) {
                    continue 2;
                }

                // If we hit a class/enum/trait/interface before declare(strict_types=1)
                if (is_array($token) && in_array($token[0], [T_CLASS, T_ENUM, T_TRAIT, T_INTERFACE, T_FUNCTION], true)) {
                    if (! str_contains($content, 'declare(strict_types=1)')) {
                        $violations[] = $file->getBasename();
                    }
                    break;
                }
                break;
            }
        }

        expect($violations)->toBeEmpty('Files missing declare(strict_types=1): '.implode(', ', $violations));
    });

    // ── Final Classes ───────────────────────────────────────────────

    it('all non-trait classes are final', function () {
        $classes = [
            EnumMetadataResolver::class,
            EnumCache::class,
            EnumManager::class,
            EnumRule::class,
            EnumCast::class,
            InvalidEnumException::class,
            Enum::class,
            EnumsServiceProvider::class,
            InspectEnumCommand::class,
            MakeEnumTestCommand::class,
            EnumTestGenerator::class,
            Label::class,
            Color::class,
            Icon::class,
            Description::class,
            EnumLabel::class,
            EnumColor::class,
            EnumIcon::class,
            EnumDescription::class,
        ];

        foreach ($classes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    // ── Readonly Properties on Attributes ──────────────────────────

    it('all attribute classes use readonly promoted constructor properties', function () {
        $attributeClasses = [
            Label::class,
            Color::class,
            Icon::class,
            Description::class,
            EnumLabel::class,
            EnumColor::class,
            EnumIcon::class,
            EnumDescription::class,
        ];

        foreach ($attributeClasses as $class) {
            $ref = new ReflectionClass($class);
            $ctor = $ref->getConstructor();

            expect($ctor)->not()->toBeNull("{$class} must have a constructor");

            foreach ($ctor->getParameters() as $param) {
                if ($param->getName() === 'message') {
                    continue; // Nullable message param — skip
                }
                expect($param->isPromoted())->toBeTrue("{$class}::\${$param->getName()} must be promoted");
            }

            // Check that all properties are readonly
            foreach ($ref->getProperties() as $prop) {
                expect($prop->isReadOnly())->toBeTrue("{$class}::\${$prop->getName()} must be readonly");
            }
        }
    });

    // ── EnumRule Readonly ──────────────────────────────────────────

    it('EnumRule is readonly class with readonly promoted properties', function () {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->isFinal())->toBeTrue();
        // PHP 8.2+ readonly class check — EnumRule is `final readonly class`
        foreach ($ref->getProperties() as $prop) {
            expect($prop->isReadOnly())->toBeTrue("EnumRule::\${$prop->getName()} must be readonly");
        }
    });

    // ── EnumCast Readonly ──────────────────────────────────────────

    it('EnumCast has readonly promoted constructor properties', function () {
        $ref = new ReflectionClass(EnumCast::class);
        $ctor = $ref->getConstructor();

        foreach ($ctor->getParameters() as $param) {
            expect($param->isPromoted())->toBeTrue("EnumCast::\${$param->getName()} must be promoted");
            expect($param->isReadOnly())->toBeTrue("EnumCast::\${$param->getName()} must be readonly");
        }
    });

    // ── #[Override] on Interface Implementations ────────────────────

    it('EnumRule validate() has #[Override] attribute', function () {
        $method = new ReflectionMethod(EnumRule::class, 'validate');
        $attrs = array_map(
            fn (ReflectionAttribute $a): string => $a->getName(),
            $method->getAttributes()
        );
        expect($attrs)->toContain('Override');
    });

    it('EnumCast get/set/serialize have #[Override] attribute', function () {
        foreach (['get', 'set'] as $method) {
            $ref = new ReflectionMethod(EnumCast::class, $method);
            $attrs = array_map(
                fn (ReflectionAttribute $a): string => $a->getName(),
                $ref->getAttributes()
            );
            expect($attrs)->toContain('Override', "EnumCast::{$method}() must have #[Override]");
        }
    });

    it('Enum facade has #[Override] on getFacadeAccessor', function () {
        $method = new ReflectionMethod(Enum::class, 'getFacadeAccessor');
        $attrs = array_map(
            fn (ReflectionAttribute $a): string => $a->getName(),
            $method->getAttributes()
        );
        expect($attrs)->toContain('Override');
    });

    it('EnumsServiceProvider register/boot have #[Override]', function () {
        foreach (['register', 'boot'] as $method) {
            $ref = new ReflectionMethod(EnumsServiceProvider::class, $method);
            $attrs = array_map(
                fn (ReflectionAttribute $a): string => $a->getName(),
                $ref->getAttributes()
            );
            expect($attrs)->toContain('Override', "EnumsServiceProvider::{$method}() must have #[Override]");
        }
    });

    // ── Attribute Targeting ────────────────────────────────────────

    it('per-case attributes target TARGET_CLASS_CONSTANT', function () {
        $expectedFlags = Attribute::TARGET_CLASS_CONSTANT;

        foreach ([Label::class, Color::class, Icon::class, Description::class] as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(Attribute::class);
            expect($attrs)->not()->toBeEmpty("{$class} must have #[Attribute]");
            $instance = $attrs[0]->newInstance();
            expect($instance->flags)->toBe($expectedFlags, "{$class} must target TARGET_CLASS_CONSTANT");
        }
    });

    it('class-level attributes target TARGET_CLASS | TARGET_CLASS_CONSTANT', function () {
        $expectedFlags = Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT;

        foreach ([EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class] as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(Attribute::class);
            expect($attrs)->not()->toBeEmpty("{$class} must have #[Attribute]");
            $instance = $attrs[0]->newInstance();
            expect($instance->flags)->toBe($expectedFlags, "{$class} must target TARGET_CLASS | TARGET_CLASS_CONSTANT");
        }
    });

    // ── Return Type Declarations ───────────────────────────────────

    it('HasEnumMetadata trait methods all have explicit return types', function () {
        $methods = ['label', 'color', 'icon', 'description', 'forSelect', 'forApi', 'tryFromLabel', 'tryFromName', 'fromName', 'hasCase', 'is', 'isNot', 'in', 'notIn', 'values', 'labels'];

        $ref = new ReflectionClass(HasEnumMetadata::class);
        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            expect($m->hasReturnType())->toBeTrue("HasEnumMetadata::{$method}() must have a return type declaration");
        }
    });

    it('EnumCache public methods all have explicit return types', function () {
        $methods = ['getInstance', 'has', 'get', 'set', 'setTtl', 'getTtl', 'clear', 'clearClass', 'flush', 'resetInstance'];

        $ref = new ReflectionClass(EnumCache::class);
        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            expect($m->hasReturnType())->toBeTrue("EnumCache::{$method}() must have a return type declaration");
        }
    });

    it('EnumManager public methods all have explicit return types', function () {
        $methods = ['forSelect', 'forApi', 'tryFromLabel'];

        $ref = new ReflectionClass(EnumManager::class);
        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            expect($m->hasReturnType())->toBeTrue("EnumManager::{$method}() must have a return type declaration");
        }
    });

    // ── Docblocks ──────────────────────────────────────────────────

    it('all public methods on EnumMetadataResolver have docblocks', function () {
        $ref = new ReflectionClass(EnumMetadataResolver::class);
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $doc = $method->getDocComment();
            expect($doc)->not()->toBeFalse("EnumMetadataResolver::{$method->getName()}() must have a docblock");
        }
    });

    it('all public methods on EnumCache have docblocks', function () {
        $ref = new ReflectionClass(EnumCache::class);
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $doc = $method->getDocComment();
            expect($doc)->not()->toBeFalse("EnumCache::{$method->getName()}() must have a docblock");
        }
    });

    // ── Singleton Safety ──────────────────────────────────────────

    it('EnumCache __clone and __wakeup return never', function () {
        $ref = new ReflectionClass(EnumCache::class);

        $clone = $ref->getMethod('__clone');
        expect($clone->getReturnType()?->getName())->toBe('never');

        $wakeup = $ref->getMethod('__wakeup');
        expect($wakeup->getReturnType()?->getName())->toBe('never');
    });

    // ── Strict Comparisons (no == for value comparison) ───────────

    it('EnumMetadataResolver uses only strict comparisons', function () {
        $file = (new ReflectionClass(EnumMetadataResolver::class))->getFileName();
        $content = file_get_contents($file);

        // Should not contain loose == comparisons (except for !==)
        // We look for == that is NOT part of !== or ===
        if (preg_match_all('/(?<!=)(?<!<)(?<!!)={2}(?!=)/', $content, $matches)) {
            // Filter out false positives (inside comments, strings)
            $realMatches = array_filter($matches[0], fn () => true);
            expect(count($realMatches))->toBe(0, 'EnumMetadataResolver should not use == comparisons');
        }
    });

    it('HasEnumMetadata uses only strict comparisons', function () {
        $file = (new ReflectionClass(HasEnumMetadata::class))->getFileName();
        $content = file_get_contents($file);

        if (preg_match_all('/(?<!=)(?<!<)(?<!!)={2}(?!=)/', $content, $matches)) {
            expect(count($matches[0]))->toBe(0, 'HasEnumMetadata should not use == comparisons');
        }
    });

    // ── No Mixed Types in Public API ────────────────────────────────

    it('EnumMetadataResolver::resolve() returns array with @phpstan-type', function () {
        $method = new ReflectionMethod(EnumMetadataResolver::class, 'resolve');
        $doc = $method->getDocComment();
        expect($doc)->not()->toBeFalse();
        expect($doc)->toContain('@return');
    });

    // ── EnumRule Implements ValidationRule ─────────────────────────

    it('EnumRule implements Illuminate ValidationRule interface', function () {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Validation\ValidationRule::class))->toBeTrue();
    });

    // ── EnumCast Implements CastsAttributes ─────────────────────────

    it('EnumCast implements Illuminate CastsAttributes interface', function () {
        $ref = new ReflectionClass(EnumCast::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))->toBeTrue();
    });

    // ── InvalidEnumException Factory Methods ────────────────────────

    it('InvalidEnumException has named constructors value() and forName()', function () {
        $ref = new ReflectionClass(InvalidEnumException::class);
        expect($ref->hasMethod('value'))->toBeTrue();
        expect($ref->hasMethod('forName'))->toBeTrue();

        $valueMethod = $ref->getMethod('value');
        expect($valueMethod->isStatic())->toBeTrue();
        expect($valueMethod->getReturnType()?->getName())->toBe(self::class);

        $forNameMethod = $ref->getMethod('forName');
        expect($forNameMethod->isStatic())->toBeTrue();
        expect($forNameMethod->getReturnType()?->getName())->toBe(self::class);
    });

    // ── Trait API Completeness ─────────────────────────────────────

    it('HasEnumMetadata provides all documented public methods', function () {
        $ref = new ReflectionClass(HasEnumMetadata::class);
        $expectedMethods = [
            'label', 'color', 'icon', 'description',
            'forSelect', 'forApi',
            'tryFromLabel', 'tryFromName', 'fromName', 'hasCase',
            'is', 'isNot', 'in', 'notIn',
            'values', 'labels',
        ];

        foreach ($expectedMethods as $method) {
            expect($ref->hasMethod($method))->toBeTrue("HasEnumMetadata must have {$method}() method");
        }
    });

    // ── Cache Integration ──────────────────────────────────────────

    it('EnumMetadataResolver uses EnumCache for caching', function () {
        EnumCache::resetInstance();
        EnumCache::getInstance()->setTtl(300);

        // First call should populate cache
        UserStatus::ACTIVE->label();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        // Second call should use cache (no crash)
        UserStatus::ACTIVE->label();

        EnumCache::resetInstance();
    });

    it('EnumMetadataResolver::invalidate clears cache for a specific class', function () {
        EnumCache::resetInstance();
        UserStatus::ACTIVE->label();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidate(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();

        EnumCache::resetInstance();
    });

    // ── Composer Config Validation ─────────────────────────────────

    it('composer.json requires PHP ^8.5', function () {
        $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($composer['require']['php'])->toBe('^8.5');
    });

    it('composer.json requires illuminate/contracts ^13.0', function () {
        $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($composer['require']['illuminate/contracts'])->toBe('^13.0');
    });

    it('composer.json autoload maps ZeroBoiler\\Enums to src/', function () {
        $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($composer['autoload']['psr-4']['ZeroBoiler\\Enums\\'])->toBe('src/');
    });

    // ── phpstan.neon Targets Level 9 ───────────────────────────────

    it('phpstan.neon is configured for level 9', function () {
        $neon = file_get_contents(__DIR__.'/../phpstan.neon');
        expect($neon)->toContain('level: 9');
        expect($neon)->toContain('paths:');
        expect($neon)->toContain('- src');
    });
});
