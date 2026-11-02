<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\SymfonyAi package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\SymfonyAi\Tests\Unit\Console\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiSkill;
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\SourceCodeRegistry;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration as CoreConfiguration;
use Stateforge\Scenario\SymfonyAi\Console\Command\ScenarioAiSkillCommand;
use Stateforge\Scenario\SymfonyAi\Runtime\Context;
use Stateforge\Scenario\SymfonyAi\Runtime\Instruction\ParameterImplementationInstruction;
use Stateforge\Scenario\SymfonyAi\Runtime\Instruction\SymfonyVersion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use function copy;
use function file_put_contents;
use function sys_get_temp_dir;
use function uniqid;
use const DIRECTORY_SEPARATOR;

#[CoversClass(ScenarioAiSkillCommand::class)]
#[UsesClass(Context::class)]
#[UsesClass(SymfonyVersion::class)]
#[UsesClass(ParameterImplementationInstruction::class)]
#[Group('command')]
#[Small]
final class ScenarioAiSkillCommandTest extends TestCase
{
    private string $projectDirectory;

    private AdapterConfiguration $adapterConfiguration;

    protected function setUp(): void
    {
        $this->projectDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'scenario-symfony-ai-skill-' . uniqid('', true);
        $path = $this->projectDirectory . '/vendor/stateforge/scenario-symfony';
        (new Filesystem())->mkdir([$path . '/docs', $path . '/blueprint']);
        file_put_contents($path . '/docs/framework.md', 'Symfony skill documentation marker');
        file_put_contents($path . '/blueprint/scenario.blueprint', 'Symfony scenario blueprint marker');
        file_put_contents($path . '/blueprint/parameter.blueprint', 'Symfony parameter blueprint marker');
        $configuration = self::createStub(CoreConfiguration::class);
        $configuration->method('getSuites')->willReturn([]);
        $configuration->method('getParameterDirectory')->willReturn('scenario/parameter');
        $core = new ReflectionClass(Application::class);
        $core->getProperty('rootDir')->setValue(null, $this->projectDirectory);
        $core->getProperty('configuration')->setValue(null, $configuration);
        $this->adapterConfiguration = new AdapterConfiguration(AdapterMode::Skill, 'test-skill', null, null, []);
        $ai = new ReflectionClass(Ai::class);
        $ai->getProperty('config')->setValue(null, new Configuration(null, false, 3, $this->adapterConfiguration));
        $ai->getProperty('registry')->setValue(null, new AdapterRegistry());
        (new ReflectionClass(SourceCodeRegistry::class))->getProperty('classes')->setValue(null, []);
    }

    protected function tearDown(): void
    {
        $core = new ReflectionClass(Application::class);
        $core->getProperty('rootDir')->setValue(null, null);
        $core->getProperty('configuration')->setValue(null, null);
        $ai = new ReflectionClass(Ai::class);
        $ai->getProperty('config')->setValue(null, null);
        $ai->getProperty('registry')->setValue(null, null);
        (new ReflectionClass(SourceCodeRegistry::class))->getProperty('classes')->setValue(null, []);
        (new Filesystem())->remove($this->projectDirectory);
    }

    public function testCommandIsConfigured(): void
    {
        $command = $this->command();

        self::assertSame('scenario:ai:skill', $command->getName());
        self::assertSame('Generate a skill IDE-ready skill for AI - should only be used for dev/test', $command->getDescription());
    }

    #[DataProvider('generationResults')]
    public function testGeneratesSkillWithSymfonyInstructionsWithoutApiCall(?string $path, int $expectedStatus, string $expectedMessage): void
    {
        $skill = $this->createMock(AiSkill::class);
        $skill->expects(self::once())->method('generate')->willReturn($path);
        $adapter = $this->createMock(AiAdapter::class);
        $adapter->expects(self::never())->method('client');
        $adapter->expects(self::once())->method('skill')
            ->with(self::identicalTo($this->adapterConfiguration), self::isInstanceOf(Instruction::class))
            ->willReturnCallback(function (AdapterConfiguration $configuration, Instruction $instruction) use ($skill): AiSkill {
                $text = $instruction->instruct($configuration->mode);
                foreach (['Introduction', 'Framework Attribute Rules', 'Available attributes:', 'Documentation', 'Base class definitions:', 'Blueprint Rules', 'Scenario Rules', 'Scenario Blueprint:', 'ParameterType Rules', 'Parameter Type Blueprint:', 'ParameterType Implementation Rules', 'Final Validation'] as $heading) {
                    self::assertStringContainsString($heading, $text);
                }
                self::assertStringNotContainsString('Conversation rules:', $text);
                return $skill;
            });
        $registry = new AdapterRegistry();
        $registry->register('test-skill', $adapter);
        (new ReflectionClass(Ai::class))->getProperty('registry')->setValue(null, $registry);
        $tester = new CommandTester($this->command());

        self::assertSame($expectedStatus, $tester->execute([]));
        self::assertStringContainsString($expectedMessage, $tester->getDisplay());
    }

    /** @return iterable<string, array{?string, int, string}> */
    public static function generationResults(): iterable
    {
        yield 'success' => ['SKILL.md', Command::SUCCESS, 'Skill was generated successfully: SKILL.md'];
        yield 'failure' => [null, Command::FAILURE, 'Skill generation failed.'];
    }

    private function command(): ScenarioAiSkillCommand
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->projectDirectory);
        $kernel->method('getEnvironment')->willReturn('test');
        return new ScenarioAiSkillCommand($kernel, new Filesystem());
    }
    #[DataProvider('enabledConfigurations')]
    public function testIsEnabledHonorsAdapterModeEnvironmentAndInstallation(AdapterMode $mode, string $environment, bool $installed, bool $expected): void
    {
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(
            null,
            new Configuration(null, false, 3, new AdapterConfiguration($mode, 'test', null, null, [])),
        );
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->projectDirectory);
        $kernel->method('getEnvironment')->willReturn($environment);
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($environment === 'prod' ? self::never() : self::once())
            ->method('exists')
            ->with($this->projectDirectory . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'scenario.yaml')
            ->willReturn($installed);

        self::assertSame($expected, (new ScenarioAiSkillCommand($kernel, $filesystem))->isEnabled());
    }

    /** @return iterable<string, array{AdapterMode, string, bool, bool}> */
    public static function enabledConfigurations(): iterable
    {
        foreach (AdapterMode::cases() as $mode) {
            yield $mode->value . ' in dev' => [$mode, 'dev', true, $mode !== AdapterMode::Cli];
            yield $mode->value . ' in test' => [$mode, 'test', true, $mode !== AdapterMode::Cli];
            yield $mode->value . ' in production' => [$mode, 'prod', true, false];
            yield $mode->value . ' without installation' => [$mode, 'test', false, false];
        }
    }

    #[DataProvider('configurationFailures')]
    public function testIsDisabledWhenAiConfigurationCannotBeLoaded(string $failure): void
    {
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
        if ($failure !== 'missing schema') {
            $schemaDirectory = $this->projectDirectory . '/vendor/stateforge/scenario-ai/xsd';
            (new Filesystem())->mkdir($schemaDirectory);
            copy(__DIR__ . '/../../../../vendor/stateforge/scenario-ai/xsd/scenario-ai.xsd', $schemaDirectory . '/scenario-ai.xsd');
        }
        if ($failure === 'invalid configuration') {
            file_put_contents($this->projectDirectory . '/scenario-ai.xml', '<scenario-ai>');
        }
        self::assertFalse($this->command()->isEnabled());
    }

    /** @return iterable<string, array{string}> */
    public static function configurationFailures(): iterable
    {
        yield 'missing schema' => ['missing schema'];
        yield 'missing configuration' => ['missing configuration'];
        yield 'invalid configuration' => ['invalid configuration'];
    }

}
