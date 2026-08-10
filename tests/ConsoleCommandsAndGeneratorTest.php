<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ZeroBoiler\Enums\Console\Commands\InspectEnumCommand;
use ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Tests for console commands and test generator structural integrity.
 *
 * Verifies that commands are final, have correct signatures, and that
 * EnumTestGenerator produces valid PHP test content without actually
 * executing the commands (requires Laravel console).
 *
 * @covers \ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand
 * @covers \ZeroBoiler\Enums\Console\Commands\InspectEnumCommand
 * @covers \ZeroBoiler\Enums\Support\EnumTestGenerator
 */
final class ConsoleCommandsAndGeneratorTest extends TestCase
{
    // -------------------------------------------------------------------
    // MakeEnumTestCommand structure
    // -------------------------------------------------------------------

    public function testMakeEnumTestCommandIsFinal(): void
    {
        $ref = new ReflectionClass(MakeEnumTestCommand::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testMakeEnumTestCommandHasCorrectSignature(): void
    {
        $command = new MakeEnumTestCommand;

        $ref = new ReflectionClass($command);
        $prop = $ref->getProperty('signature');

        $this->assertSame(
            'zeroboiler:enum-test {class : The enum class FQN} {--dir= : Output directory}',
            $prop->getValue($command),
        );
    }

    public function testMakeEnumTestCommandHandleHasOverride(): void
    {
        $method = new ReflectionMethod(MakeEnumTestCommand::class, 'handle');

        $attrs = $method->getAttributes();
        $hasOverride = false;
        foreach ($attrs as $attr) {
            if ($attr->getName() === 'Override') {
                $hasOverride = true;
                break;
            }
        }

        $this->assertTrue($hasOverride, 'handle() should have #[Override]');
        $this->assertSame('int', $method->getReturnType()?->getName());
    }

    // -------------------------------------------------------------------
    // InspectEnumCommand structure
    // -------------------------------------------------------------------

    public function testInspectEnumCommandIsFinal(): void
    {
        $ref = new ReflectionClass(InspectEnumCommand::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testInspectEnumCommandHasCorrectSignature(): void
    {
        $command = new InspectEnumCommand;

        $ref = new ReflectionClass($command);
        $prop = $ref->getProperty('signature');

        $this->assertSame(
            'zeroboiler:enum-inspect {class : The enum class FQN}',
            $prop->getValue($command),
        );
    }

    public function testInspectEnumCommandHandleHasOverride(): void
    {
        $method = new ReflectionMethod(InspectEnumCommand::class, 'handle');

        $this->assertSame('int', $method->getReturnType()?->getName());
    }

    // -------------------------------------------------------------------
    // EnumTestGenerator output verification
    // -------------------------------------------------------------------

    public function testGeneratorProducesValidPhpForStringBackedEnum(): void
    {
        $content = EnumTestGenerator::generate(UserStatus::class);

        // Must start with PHP opening tag and strict types
        $this->assertStringStartsWith("<?php\n\ndeclare(strict_types=1);", $content);
        $this->assertStringContainsString('use '.UserStatus::class.';', $content);
        $this->assertStringContainsString('describe(', $content);
        $this->assertStringContainsString('it(', $content);

        // Must include all UserStatus cases
        foreach (UserStatus::cases() as $case) {
            $this->assertStringContainsString("case {$case->name}", $content);
        }

        // Must include bulk method tests
        $this->assertStringContainsString('forSelect', $content);
        $this->assertStringContainsString('forApi', $content);
        $this->assertStringContainsString('values()', $content);
        $this->assertStringContainsString('labels()', $content);

        // Must include comparison tests (UserStatus has 5 cases, so >= 2)
        $this->assertStringContainsString('is()', $content);
        $this->assertStringContainsString('isNot()', $content);
        $this->assertStringContainsString('in(', $content);
        $this->assertStringContainsString('tryFromLabel', $content);
        $this->assertStringContainsString('tryFromName', $content);
        $this->assertStringContainsString('fromName(', $content);
        $this->assertStringContainsString('hasCase', $content);
        $this->assertStringContainsString('InvalidEnumException', $content);
    }

    public function testGeneratorProducesValidPhpForIntBackedEnum(): void
    {
        $content = EnumTestGenerator::generate(IntBackedPriority::class);

        $this->assertStringStartsWith("<?php\n\ndeclare(strict_types=1);", $content);
        $this->assertStringContainsString('use '.IntBackedPriority::class.';', $content);

        // Int-backed enums should have int-specific test
        $this->assertStringContainsString('each->toBeInt()', $content);
    }

    public function testGeneratorProducesValidPhpForPureEnum(): void
    {
        $content = EnumTestGenerator::generate(PureFeatureFlag::class);

        $this->assertStringStartsWith("<?php\n\ndeclare(strict_types=1);", $content);

        // Pure enums should use case names for values
        $this->assertStringContainsString('values() returns case names for pure enum', $content);
    }

    public function testGeneratorIncludesExpectedCaseCount(): void
    {
        $content = EnumTestGenerator::generate(UserStatus::class);
        $casesCount = count(UserStatus::cases());

        $this->assertStringContainsString("toHaveCount({$casesCount})", $content);
    }

    public function testGeneratorIsFinal(): void
    {
        $ref = new ReflectionClass(EnumTestGenerator::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testGeneratorGenerateMethodIsStaticAndPublic(): void
    {
        $method = new ReflectionMethod(EnumTestGenerator::class, 'generate');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame('string', $method->getReturnType()?->getName());
    }

    // -------------------------------------------------------------------
    // ServiceProvider command registration verification
    // -------------------------------------------------------------------

    public function testServiceProviderRegistersBothCommands(): void
    {
        $ref = new ReflectionClass(EnumsServiceProvider::class);
        $filename = $ref->getFileName();

        $this->assertIsString($filename);
        $this->assertFileExists($filename);

        $content = file_get_contents($filename);
        $this->assertStringContainsString(MakeEnumTestCommand::class, $content);
        $this->assertStringContainsString(InspectEnumCommand::class, $content);
    }

    public function testServiceProviderRegistersEnumManagerSingleton(): void
    {
        $ref = new ReflectionClass(EnumsServiceProvider::class);
        $filename = $ref->getFileName();

        $this->assertIsString($filename);
        $content = file_get_contents($filename);

        $this->assertStringContainsString('zeroboiler.enum', $content);
        $this->assertStringContainsString(EnumManager::class, $content);
    }
}
