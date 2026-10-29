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

use Stateforge\Scenario\Symfony\Console\Command\ScenarioCommand;
use Stateforge\Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use const DIRECTORY_SEPARATOR;

final class ScenarioAiConfigCommand extends ScenarioCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scenario:ai:config')
            ->setDescription('Add the AI config for Scenario AI - should only be used for dev/test')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new Output(new SymfonyStyle($input, $output));

        $file = $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR .
            'vendor' . DIRECTORY_SEPARATOR .
            'stateforge' . DIRECTORY_SEPARATOR .
            'scenario-ai' . DIRECTORY_SEPARATOR .
            'blueprint' . DIRECTORY_SEPARATOR .
            'config.blueprint';

        if ($this->getFilesystem()->exists($file) === false) {
            $style->error('Config file generation failed.');
            return Command::FAILURE;
        }

        $configFile = $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR . 'scenario-ai.dist.xml';
        if ($this->getFilesystem()->exists($configFile) === true) {
            $style->error('Config file already exists.');
            return Command::FAILURE;
        }

        $this->getFilesystem()->copy($file, $configFile);

        if ($this->getFilesystem()->exists($configFile) === false) {
            $style->error('Config file generation failed.');
            return Command::FAILURE;
        }

        $style->success('Config file generated, please modify to your needs.');
        return Command::SUCCESS;
    }
}
