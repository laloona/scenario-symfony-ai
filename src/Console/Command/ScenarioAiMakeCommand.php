<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\SymfonyAi package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\SymfonyAi\Console\Command;

use Stateforge\Scenario\Ai\Console\CliConversation;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Symfony\Console\Command\ScenarioCommand;
use Stateforge\Scenario\Symfony\Console\Output;
use Stateforge\Scenario\SymfonyAi\Runtime\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ScenarioAiMakeCommand extends ScenarioCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scenario:ai:make')
            ->setDescription('Make a scenario or parameter type with AI - should only be used for dev/test')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new Application())->prepare();

        $style = new Output(new SymfonyStyle($input, $output));
        return (new CliConversation('Scenario Symfony AI', 'Scenario Symfony framework'))->talk($style, new Context()) === true
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
