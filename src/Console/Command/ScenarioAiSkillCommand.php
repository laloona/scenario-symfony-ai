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

use Stateforge\Scenario\Ai\Console\CliSkill;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Symfony\Console\Command\ScenarioCommand;
use Stateforge\Scenario\Symfony\Console\Output;
use Stateforge\Scenario\SymfonyAi\Runtime\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class ScenarioAiSkillCommand extends ScenarioCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scenario:ai:skill')
            ->setDescription('Generate a skill IDE-ready skill for AI - should only be used for dev/test')
        ;
    }

    public function isEnabled(): bool
    {
        try {
            $config = Ai::config();
            return parent::isEnabled()
                && $config->adapter->mode->isSkill() === true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new Application())->prepare();

        $style = new Output(new SymfonyStyle($input, $output));
        return (new CliSkill())->generate($style, new Context()) === true
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
