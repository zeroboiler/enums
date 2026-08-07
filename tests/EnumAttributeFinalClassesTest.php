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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

describe('Enum classes are final', function () {
    it('per-case attributes are final', function () {
        expect(Color::class)->toBeFinal();
        expect(Description::class)->toBeFinal();
        expect(Icon::class)->toBeFinal();
        expect(Label::class)->toBeFinal();
    });

    it('class-level attributes are final', function () {
        expect(EnumColor::class)->toBeFinal();
        expect(EnumDescription::class)->toBeFinal();
        expect(EnumIcon::class)->toBeFinal();
        expect(EnumLabel::class)->toBeFinal();
    });

    it('support classes are final', function () {
        expect(EnumCache::class)->toBeFinal();
        expect(EnumManager::class)->toBeFinal();
        expect(EnumMetadataResolver::class)->toBeFinal();
        expect(EnumTestGenerator::class)->toBeFinal();
    });

    it('cast and rule classes are final', function () {
        expect(EnumCast::class)->toBeFinal();
        expect(EnumRule::class)->toBeFinal();
    });

    it('exception class is final', function () {
        expect(InvalidEnumException::class)->toBeFinal();
    });
});

describe('Enum trait has strict types', function () {
    it('HasEnumMetadata file has declare strict_types', function () {
        $reflection = new ReflectionClass(HasEnumMetadata::class);
        $file = $reflection->getFileName();
        $contents = file_get_contents($file);
        expect($contents)->toContain('declare(strict_types=1)');
    });

    it('all source files have declare strict_types', function () {
        $classes = [
            EnumCache::class,
            EnumManager::class,
            EnumMetadataResolver::class,
            EnumRule::class,
            EnumCast::class,
            InvalidEnumException::class,
            Color::class,
            Label::class,
            Icon::class,
            Description::class,
            EnumColor::class,
            EnumLabel::class,
            EnumIcon::class,
            EnumDescription::class,
        ];

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $file = $reflection->getFileName();
            $contents = file_get_contents($file);
            expect($contents)->toContain('declare(strict_types=1)');
        }
    });
});

describe('Enum attribute property types', function () {
    it('Label has readonly string value', function () {
        $attr = new Label('Test');
        expect($attr->value)->toBe('Test');
    });

    it('Color has readonly string value', function () {
        $attr = new Color('success');
        expect($attr->value)->toBe('success');
    });

    it('Icon has readonly string value', function () {
        $attr = new Icon('heroicon-o-check');
        expect($attr->value)->toBe('heroicon-o-check');
    });

    it('Description has readonly string value', function () {
        $attr = new Description('A description');
        expect($attr->value)->toBe('A description');
    });

    it('EnumColor has readonly array properties', function () {
        $attr = new EnumColor(
            success: ['active'],
            danger: ['banned'],
        );
        expect($attr->success)->toBe(['active']);
        expect($attr->danger)->toBe(['banned']);
        expect($attr->warning)->toBe([]);
        expect($attr->info)->toBe([]);
        expect($attr->secondary)->toBe([]);
    });

    it('EnumLabel has readonly nullable properties', function () {
        $classLevel = new EnumLabel(labels: ['a' => 'A']);
        expect($classLevel->labels)->toBe(['a' => 'A']);
        expect($classLevel->label)->toBeNull();

        $caseLevel = new EnumLabel(label: 'Custom');
        expect($caseLevel->label)->toBe('Custom');
        expect($caseLevel->labels)->toBeNull();
    });

    it('EnumIcon has readonly nullable default', function () {
        $attr = new EnumIcon(default: 'heroicon-o-question');
        expect($attr->default)->toBe('heroicon-o-question');

        $empty = new EnumIcon();
        expect($empty->default)->toBeNull();
    });

    it('EnumDescription has readonly nullable properties', function () {
        $classLevel = new EnumDescription(descriptions: ['a' => 'Desc A']);
        expect($classLevel->descriptions)->toBe(['a' => 'Desc A']);
        expect($classLevel->description)->toBeNull();
    });
});
