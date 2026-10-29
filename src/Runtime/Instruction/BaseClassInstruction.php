<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\SymfonyAi package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\SymfonyAi\Runtime\Instruction;

use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;
use const PHP_EOL;

final class BaseClassInstruction implements Instruction
{
    /**
     * @param list<class-string> $classes
     */
    public function __construct(
        private readonly array $classes,
    ) {
    }

    public function instruct(): string
    {
        return 'Base class definitions: ' . PHP_EOL . PHP_EOL .
            (new SourceCodeReader())->read($this->classes);
    }
}
