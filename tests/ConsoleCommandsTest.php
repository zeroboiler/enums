<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Console\Commands\InspectEnumCommand;
use ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Console Commands (unit-level)', function (): void {
    describe('InspectEnumCommand', function (): void {
        it('has correct signature and description', function (): void {
            $command = new InspectEnumCommand;

            expect($command->getName())->toBe('zeroboiler:enum-inspect')
                ->and($command->getDescription())->toBe('Inspect a ZeroBoiler smart enum — show all metadata in a table');
        });

        it('command class is final', function (): void {
            $reflection = new ReflectionClass(InspectEnumCommand::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('has class argument in signature', function (): void {
            $command = new InspectEnumCommand;
            $definition = $command->getDefinition();

            expect($definition->hasArgument('class'))->toBeTrue()
                ->and($definition->getArgument('class')->isRequired())->toBeTrue();
        });

        it('handle method has int return type', function (): void {
            $reflection = new ReflectionMethod(InspectEnumCommand::class, 'handle');

            expect($reflection->getReturnType())->not->toBeNull()
                ->and((string) $reflection->getReturnType())->toBe('int');
        });

        it('safeCall returns null for non-existent method', function (): void {
            $command = new InspectEnumCommand;
            $reflection = new ReflectionMethod(InspectEnumCommand::class, 'safeCall');

            $result = $reflection->invoke($command, new stdClass, 'nonExistentMethod');

            expect($result)->toBeNull();
        });

        it('safeCall returns string result for valid method', function (): void {
            $command = new InspectEnumCommand;
            $reflection = new ReflectionMethod(InspectEnumCommand::class, 'safeCall');

            $result = $reflection->invoke($command, UserStatus::ACTIVE, 'label');

            expect($result)->toBeString()->not->toBeEmpty();
        });

        it('safeCall returns null when method throws', function (): void {
            $command = new InspectEnumCommand;
            $reflection = new ReflectionMethod(InspectEnumCommand::class, 'safeCall');

            // Create an object whose method throws
            $thrower = new class
            {
                public function label(): string
                {
                    throw new RuntimeException('test failure');
                }
            };

            $result = $reflection->invoke($command, $thrower, 'label');

            expect($result)->toBeNull();
        });
    });

    describe('MakeEnumTestCommand', function (): void {
        it('has correct signature and description', function (): void {
            $command = new MakeEnumTestCommand;

            expect($command->getName())->toBe('zeroboiler:enum-test')
                ->and($command->getDescription())->toBe('Generate Pest tests for a ZeroBoiler smart enum');
        });

        it('command class is final', function (): void {
            $reflection = new ReflectionClass(MakeEnumTestCommand::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('has class argument and --dir option in signature', function (): void {
            $command = new MakeEnumTestCommand;
            $definition = $command->getDefinition();

            expect($definition->hasArgument('class'))->toBeTrue()
                ->and($definition->getArgument('class')->isRequired())->toBeTrue()
                ->and($definition->hasOption('dir'))->toBeTrue();
        });

        it('handle method has int return type', function (): void {
            $reflection = new ReflectionMethod(MakeEnumTestCommand::class, 'handle');

            expect($reflection->getReturnType())->not->toBeNull()
                ->and((string) $reflection->getReturnType())->toBe('int');
        });
    });
});
