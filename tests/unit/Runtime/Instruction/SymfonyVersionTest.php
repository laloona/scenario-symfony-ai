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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\SymfonyAi\Runtime\Instruction\SymfonyVersion;

#[CoversClass(SymfonyVersion::class)]
#[Group('runtime')]
#[Small]
final class SymfonyVersionTest extends TestCase
{
    #[DataProvider('versions')]
    public function testInstructIncludesTheProvidedSymfonyVersion(string $version, string $expected): void
    {
        self::assertSame($expected, (new SymfonyVersion($version))->instruct());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function versions(): iterable
    {
        yield 'major and minor' => ['7.3', 'Generate code compatible with Symfony 7.3'];
        yield 'patch version' => ['6.4.12', 'Generate code compatible with Symfony 6.4.12'];
        yield 'prerelease' => ['8.0.0-RC1', 'Generate code compatible with Symfony 8.0.0-RC1'];
    }
}
