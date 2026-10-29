<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\SymfonyAi package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\SymfonyAi\Runtime;

use LogicException;
use Stateforge\Scenario\Ai\Contract\AiContext;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\Instruction\BlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\GeneralInstructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Validation\CodeValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\Validator;
use Stateforge\Scenario\Ai\Runtime\Validation\Validators;
use Stateforge\Scenario\Core\Attribute\AsParameterType;
use Stateforge\Scenario\Core\Attribute\AsScenario;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Stateforge\Scenario\Symfony\Scenario;
use Stateforge\Scenario\SymfonyAi\Runtime\Instruction\BaseClassInstruction;
use const DIRECTORY_SEPARATOR;

final class Context implements AiContext
{
    public function instruction(AiRequest $request): Instruction
    {
        $config = Application::config();
        if ($config === null) {
            throw new LogicException('Scenario configuration is not initialized.');
        }

        $aiConfig = Ai::config();
        $path = Application::getRootDir() . DIRECTORY_SEPARATOR .
            'vendor' . DIRECTORY_SEPARATOR .
            'stateforge' . DIRECTORY_SEPARATOR .
            'scenario-symfony' . DIRECTORY_SEPARATOR;

        $instructions = new Instructions();
        $instructions->add(
            new GeneralInstructions(
                $path . 'docs',
                $aiConfig->maxQuestions,
                $request->counter,
                $aiConfig->projectDoc === null
                    ? null
                    : Application::getRootDir() . DIRECTORY_SEPARATOR . $aiConfig->projectDoc,
                Application::getRootDir() . DIRECTORY_SEPARATOR . $config->getParameterDirectory(),
                $aiConfig->allowProjectInformation,
            ),
        );
        $instructions->add(new BaseClassInstruction([ Scenario::class, ParameterTypeDefinition::class ]));
        $instructions->add(
            new BlueprintInstruction(
                new ScenarioBlueprintInstruction(
                    $path . 'blueprint' . DIRECTORY_SEPARATOR . 'scenario.blueprint',
                    $config->getSuites(),
                ),
                new ParameterBlueprintInstruction(
                    $path . 'blueprint' . DIRECTORY_SEPARATOR . 'parameter.blueprint',
                    $config->getParameterDirectory(),
                ),
            ),
        );

        return $instructions;
    }

    public function validator(): Validator
    {
        $validators = new Validators();
        $validators->add(new CodeValidator(
            Scenario::class,
            AsScenario::class,
            ParameterTypeDefinition::class,
            AsParameterType::class,
        ));

        return $validators;
    }
}
