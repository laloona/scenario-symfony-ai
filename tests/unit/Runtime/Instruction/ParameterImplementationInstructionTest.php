<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\SymfonyAi package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\SymfonyAi\Tests\Unit\Runtime\Instruction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\SymfonyAi\Runtime\Instruction\ParameterImplementationInstruction;

#[CoversClass(ParameterImplementationInstruction::class)]
#[Group('runtime')]
#[Small]
final class ParameterImplementationInstructionTest extends TestCase
{
    public function testUsesInheritedCastAndRequiresSymfonyConstraints(): void
    {
        $text = (new ParameterImplementationInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('The Symfony ParameterTypeDefinition base class already provides a final cast() method.', $text);
        self::assertStringContainsString('This method converts values using valueType() and validates them using constraints().', $text);
        self::assertStringContainsString('Never override or reimplement cast().', $text);
        self::assertStringContainsString('Implement complete validation rules in constraints().', $text);
        self::assertStringContainsString('The constraints() method MUST return an array of Symfony Constraint instances.', $text);
        self::assertStringNotContainsString('Never override or reimplement constraints().', $text);
        self::assertStringNotContainsString('Laravel', $text);
    }

    public function testRequiresCompatibleValueTypeInstances(): void
    {
        $text = (new ParameterImplementationInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('The valueType() method MUST return an instance of the appropriate ValueType class.', $text);
        self::assertStringContainsString('NEVER return a class name string from valueType().', $text);
        self::assertStringContainsString('Pass the required constructor arguments when creating ValueType instances.', $text);
        self::assertStringContainsString('The selected ValueType MUST be consistent with the values returned by the inherited cast() method.', $text);
    }

    public function testRequestsMissingInformationInsteadOfGeneratingIncompleteCode(): void
    {
        $text = (new ParameterImplementationInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Invalid or unsupported input values MUST be rejected through the validation rules.', $text);
        self::assertStringContainsString('Never replace required validation or conversion logic with placeholder comments.', $text);
        self::assertStringContainsString('If implementation requires unavailable project-specific dependencies, request the necessary information.', $text);
        self::assertStringContainsString('If meaningful implementation is impossible, request clarification instead of generating unrelated or incomplete code.', $text);
    }
}
