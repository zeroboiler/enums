<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Cross-Method Consistency Test — verifies that bulk methods, accessors,
 * and lookup methods return consistent data for every enum case.
 *
 * For each enum fixture, this test validates:
 * - forSelect() values match values()
 * - forSelect() labels match labels()
 * - forApi() contains all expected keys and consistent values
 * - forApi()[i]['label'] === label() for each case
 * - forApi()[i]['color'] === color() for each case
 * - tryFromLabel() is the inverse of label() (roundtrip)
 * - tryFromName() is the inverse of name (roundtrip)
 * - values() are unique (no duplicates)
 * - fromName() throws for non-existent names
 * - hasCase() is consistent with tryFromName()
 *
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata
 */
use ReflectionEnum;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Cross-Method Consistency', function () {
    /**
     * All fixture enums to test against.
     *
     * @var list<class-string<\UnitEnum>>
     */
    $fixtures = [
        UserStatus::class,
        TicketStatus::class,
        Priority::class,
        IntStatusWithColor::class,
        PureFeatureFlag::class,
        CamelCaseRole::class,
        SingleCaseEnum::class,
        ZeroPriority::class,
        MixedAttributeStatus::class,
        LabelMapEnum::class,
    ];

    it('forSelect() values match values() for every fixture', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            $selectValues = array_column($enumClass::forSelect(), 'value');
            $rawValues = $enumClass::values();

            expect($selectValues)->toBe($rawValues, "Mismatch for {$enumClass}");
        }
    });

    it('forSelect() labels match labels() for every fixture', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            $selectLabels = array_column($enumClass::forSelect(), 'label');
            $rawLabels = $enumClass::labels();

            expect($selectLabels)->toBe($rawLabels, "Label mismatch for {$enumClass}");
        }
    });

    it('forSelect() and values() have the same count as cases()', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            $caseCount = count($enumClass::cases());
            $selectCount = count($enumClass::forSelect());
            $valuesCount = count($enumClass::values());
            $labelsCount = count($enumClass::labels());

            expect($selectCount)->toBe($caseCount, "forSelect count mismatch for {$enumClass}");
            expect($valuesCount)->toBe($caseCount, "values count mismatch for {$enumClass}");
            expect($labelsCount)->toBe($caseCount, "labels count mismatch for {$enumClass}");
        }
    });

    it('forApi() returns consistent data with individual accessors', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            $api = $enumClass::forApi();
            $cases = $enumClass::cases();

            expect(count($api))->toBe(count($cases), "API count mismatch for {$enumClass}");

            foreach ($cases as $index => $case) {
                $apiItem = $api[$index];

                // Verify required keys exist
                expect($apiItem)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);

                // Verify consistency with individual accessor calls
                expect($apiItem['label'])->toBe($case->label(), "Label mismatch at {$enumClass}::{$case->name}");
                expect($apiItem['color'])->toBe($case->color(), "Color mismatch at {$enumClass}::{$case->name}");
                expect($apiItem['icon'])->toBe($case->icon(), "Icon mismatch at {$enumClass}::{$case->name}");
                expect($apiItem['description'])->toBe($case->description(), "Description mismatch at {$enumClass}::{$case->name}");
                expect($apiItem['name'])->toBe($case->name, "Name mismatch at {$enumClass}::{$case->name}");

                // Value consistency
                $reflection = new ReflectionEnum($enumClass);
                $isBacked = $reflection->isBacked();
                if ($isBacked) {
                    expect($apiItem['value'])->toBe($case->value, "Value mismatch at {$enumClass}::{$case->name}");
                } else {
                    expect($apiItem['value'])->toBe($case->name, "Pure enum value should be name at {$enumClass}::{$case->name}");
                }
            }
        }
    });

    it('tryFromLabel() is the inverse of label() for every case', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                $label = $case->label();
                $resolved = $enumClass::tryFromLabel($label);

                expect($resolved)->not->toBeNull("tryFromLabel returned null for '{$label}' in {$enumClass}::{$case->name}");
                expect($resolved->name)->toBe($case->name, "tryFromLabel resolved wrong case for {$enumClass}::{$case->name}");
            }
        }
    });

    it('tryFromLabel() is case-insensitive', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                $label = $case->label();
                $lowerLabel = strtolower($label);
                $upperLabel = strtoupper($label);
                $resolvedLower = $enumClass::tryFromLabel($lowerLabel);
                $resolvedUpper = $enumClass::tryFromLabel($upperLabel);

                expect($resolvedLower)->not->toBeNull("Lowercase label '{$lowerLabel}' not found in {$enumClass}");
                expect($resolvedLower->name)->toBe($case->name);
                expect($resolvedUpper)->not->toBeNull("Uppercase label '{$upperLabel}' not found in {$enumClass}");
                expect($resolvedUpper->name)->toBe($case->name);
            }
        }
    });

    it('tryFromName() is the inverse of case name for every case', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                $resolved = $enumClass::tryFromName($case->name);

                expect($resolved)->not->toBeNull("tryFromName returned null for '{$case->name}' in {$enumClass}");
                expect($resolved->name)->toBe($case->name);
            }
        }
    });

    it('tryFromName() is case-sensitive (lowercase returns null)', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                if (strtolower($case->name) === $case->name) {
                    continue; // Skip cases that are already lowercase
                }

                $resolved = $enumClass::tryFromName(strtolower($case->name));
                expect($resolved)->toBeNull("tryFromName should be case-sensitive for {$enumClass}::{$case->name}");
            }
        }
    });

    it('fromName() returns correct case and throws for invalid names', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                $resolved = $enumClass::fromName($case->name);
                expect($resolved->name)->toBe($case->name);
            }

            // Verify throw on invalid
            expect(fn () => $enumClass::fromName('NON_EXISTENT_CASE_' . uniqid()))
                ->toThrow(InvalidEnumException::class);
        }
    });

    it('hasCase() is consistent with tryFromName()', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                expect($enumClass::hasCase($case->name))->toBeTrue("hasCase should be true for {$enumClass}::{$case->name}");
            }

            expect($enumClass::hasCase('NON_EXISTENT_CASE'))->toBeFalse();
        }
    });

    it('values() contains no duplicates', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            $values = $enumClass::values();
            $unique = array_unique($values);

            expect(count($unique))->toBe(count($values), "Duplicate values found in {$enumClass}::values()");
        }
    });

    it('forSelect() values are unique', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            $select = $enumClass::forSelect();
            $values = array_column($select, 'value');
            $unique = array_unique($unique);

            expect(count(array_unique($values)))->toBe(count($values), "Duplicate values found in {$enumClass}::forSelect()");
        }
    });

    it('labels() are all non-empty strings', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            foreach ($enumClass::labels() as $index => $label) {
                expect($label)->toBeString("Label at index {$index} is not a string in {$enumClass}");
                expect($label)->not->toBeEmpty("Label at index {$index} is empty in {$enumClass}");
            }
        }
    });

    it('color() always returns a non-empty string', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                $color = $case->color();
                expect($color)->toBeString("color() returned non-string in {$enumClass}::{$case->name}");
                expect($color)->not->toBeEmpty("color() returned empty string in {$enumClass}::{$case->name}");
            }
        }
    });

    it('comparison methods are internally consistent (is ↔ isNot, in ↔ notIn)', function () use ($fixtures) {
        foreach ($fixtures as $enumClass) {
            $cases = $enumClass::cases();
            if (count($cases) < 2) {
                continue;
            }

            $first = $cases[0];
            $second = $cases[1];

            // is/isNot consistency
            expect($first->is($first))->toBeTrue();
            expect($first->isNot($first))->toBeFalse();
            expect($first->is($second))->toBeFalse();
            expect($first->isNot($second))->toBeTrue();

            // String comparison
            expect($first->is($first->name))->toBeTrue();
            expect($first->isNot($first->name))->toBeFalse();

            // in/notIn consistency
            expect($first->in([$first, $second]))->toBeTrue();
            expect($first->notIn([$first, $second]))->toBeFalse();
            expect($first->notIn([$second]))->toBeTrue();
            expect($first->in([$second]))->toBeFalse();

            // Empty array
            expect($first->in([]))->toBeFalse();
            expect($first->notIn([]))->toBeTrue();
        }
    });

    it('zero-backed enum value works correctly in all methods', function () {
        $case = ZeroPriority::ZERO;
        $reflection = new ReflectionEnum(ZeroPriority::class);
        expect($reflection->isBacked())->toBeTrue();
        expect($case->value)->toBe(0);

        // Zero should appear in values
        expect(ZeroPriority::values())->toContain(0);

        // Zero should appear in forSelect
        $select = ZeroPriority::forSelect();
        $zeroEntry = array_filter($select, fn (array $entry): bool => $entry['value'] === 0);
        expect(count($zeroEntry))->toBe(1);

        // Label should be non-empty
        expect($case->label())->toBeString()->not->toBeEmpty();

        // Lookup by value should work
        expect(ZeroPriority::tryFrom(0))->toBe(ZeroPriority::ZERO);
    });

    it('single-case enum works correctly in all methods', function () {
        $cases = SingleCaseEnum::cases();
        expect(count($cases))->toBe(1);

        $case = $cases[0];
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->color())->toBeString()->not->toBeEmpty();

        expect(SingleCaseEnum::forSelect())->toHaveCount(1);
        expect(SingleCaseEnum::values())->toHaveCount(1);
        expect(SingleCaseEnum::labels())->toHaveCount(1);

        // in/notIn with only one case
        expect($case->in([$case]))->toBeTrue();
        expect($case->notIn([$case]))->toBeFalse();
    });
});
