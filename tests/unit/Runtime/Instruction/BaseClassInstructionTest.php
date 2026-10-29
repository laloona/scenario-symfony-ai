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
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\Exception\ClassFileDoesNotExistException;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Stateforge\Scenario\Symfony\Scenario;
use Stateforge\Scenario\SymfonyAi\Runtime\Instruction\BaseClassInstruction;
use stdClass;
use function file_get_contents;
use const PHP_EOL;

#[CoversClass(BaseClassInstruction::class)]
#[Group('runtime')]
#[Small]
final class BaseClassInstructionTest extends TestCase
{
    public function testIncludesHeadingAndFullClassDefinitionsInGivenOrder(): void
    {
        $expected = 'Base class definitions: ' . PHP_EOL . PHP_EOL . PHP_EOL;
        foreach ([Scenario::class, ParameterTypeDefinition::class] as $class) {
            $file = (new ReflectionClass($class))->getFileName();
            self::assertIsString($file);
            $source = file_get_contents($file);
            self::assertIsString($source);
            $expected .= $source . PHP_EOL;
        }

        self::assertSame($expected, (new BaseClassInstruction([Scenario::class, ParameterTypeDefinition::class]))->instruct());
    }

    public function testEmptyClassListReturnsHeadingAndLineBreaks(): void
    {
        self::assertSame('Base class definitions: ' . PHP_EOL . PHP_EOL . PHP_EOL, (new BaseClassInstruction([]))->instruct());
    }

    public function testPropagatesMissingSourceFileException(): void
    {
        $this->expectException(ClassFileDoesNotExistException::class);
        $this->expectExceptionMessage('class file for class "stdClass" does not exist.');

        (new BaseClassInstruction([stdClass::class]))->instruct();
    }
}
