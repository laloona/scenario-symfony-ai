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
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\SymfonyAi\Console\Command\ScenarioAiConfigCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use function copy;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function sys_get_temp_dir;
use function uniqid;
use const DIRECTORY_SEPARATOR;

#[CoversClass(ScenarioAiConfigCommand::class)]
#[Group('command')]
#[Small]
final class ScenarioAiConfigCommandTest extends TestCase
{
    private string $projectDirectory;

    protected function setUp(): void
    {
        $this->projectDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'scenario-symfony-ai-' . uniqid('', true);
        (new Filesystem())->mkdir($this->projectDirectory);
        (new ReflectionClass(Application::class))->getProperty('rootDir')->setValue(null, $this->projectDirectory);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
    }

    protected function tearDown(): void
    {
        (new ReflectionClass(Application::class))->getProperty('rootDir')->setValue(null, null);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
        (new Filesystem())->remove($this->projectDirectory);
    }

    public function testCommandIsConfigured(): void
    {
        $command = $this->command();

        self::assertSame('scenario:ai:config', $command->getName());
        self::assertSame('Add the AI config for Scenario AI - should only be used for dev/test', $command->getDescription());
    }

    public function testReportsMissingBlueprint(): void
    {
        $tester = new CommandTester($this->command());

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Config file generation failed.', $tester->getDisplay());
        self::assertFileDoesNotExist($this->target());
    }

    public function testCreatesAiConfigurationWithoutChangingCoreConfiguration(): void
    {
        $this->writeBlueprint();
        $coreConfig = $this->projectDirectory . DIRECTORY_SEPARATOR . 'scenario.dist.xml';
        file_put_contents($coreConfig, 'Core configuration');
        $tester = new CommandTester($this->command());

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Config file generated, please modify to your needs.', $tester->getDisplay());
        self::assertSame('<scenario-ai><adapter type="test" /></scenario-ai>', file_get_contents($this->target()));
        self::assertSame('Core configuration', file_get_contents($coreConfig));
    }

    public function testPreservesExistingAiConfiguration(): void
    {
        $this->writeBlueprint();
        file_put_contents($this->target(), 'Existing configuration');
        $tester = new CommandTester($this->command());

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Config file already exists.', $tester->getDisplay());
        self::assertSame('Existing configuration', file_get_contents($this->target()));
    }

    public function testReportsFailureWhenCopyDoesNotCreateTarget(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))->method('exists')->willReturnOnConsecutiveCalls(true, false, false);
        $filesystem->expects(self::once())->method('copy')->with($this->blueprint(), $this->target());
        $tester = new CommandTester($this->command($filesystem));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Config file generation failed.', $tester->getDisplay());
    }

    private function command(?Filesystem $filesystem = null): ScenarioAiConfigCommand
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->projectDirectory);
        $kernel->method('getEnvironment')->willReturn('test');

        return new ScenarioAiConfigCommand($kernel, $filesystem ?? new Filesystem());
    }

    private function writeBlueprint(): void
    {
        (new Filesystem())->mkdir(dirname($this->blueprint()));
        file_put_contents($this->blueprint(), '<scenario-ai><adapter type="test" /></scenario-ai>');
    }

    private function blueprint(): string
    {
        return $this->projectDirectory . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'stateforge' . DIRECTORY_SEPARATOR . 'scenario-ai' . DIRECTORY_SEPARATOR . 'blueprint' . DIRECTORY_SEPARATOR . 'config.blueprint';
    }

    private function target(): string
    {
        return $this->projectDirectory . DIRECTORY_SEPARATOR . 'scenario-ai.dist.xml';
    }
    #[DataProvider('adapterModes')]
    public function testIsDisabledWhenAiConfigurationIsValid(AdapterMode $mode): void
    {
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, new Configuration(null, false, 3, new AdapterConfiguration($mode, 'test', null, null, [])));
        self::assertFalse($this->command()->isEnabled());
    }

    /** @return iterable<string, array{AdapterMode}> */
    public static function adapterModes(): iterable
    {
        foreach (AdapterMode::cases() as $mode) {
            yield $mode->value => [$mode];
        }
    }

    #[DataProvider('configurationFailures')]
    public function testIsEnabledForConfigurationFailureOnlyInAllowedInstalledProject(string $failure, string $environment, bool $installed, bool $expected): void
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
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->projectDirectory);
        $kernel->method('getEnvironment')->willReturn($environment);
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($environment === 'prod' ? self::never() : self::once())->method('exists')
            ->with($this->projectDirectory . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'scenario.yaml')
            ->willReturn($installed);

        self::assertSame($expected, (new ScenarioAiConfigCommand($kernel, $filesystem))->isEnabled());
    }

    /** @return iterable<string, array{string, string, bool, bool}> */
    public static function configurationFailures(): iterable
    {
        foreach (['missing schema', 'missing configuration', 'invalid configuration'] as $failure) {
            yield $failure . ' in dev' => [$failure, 'dev', true, true];
            yield $failure . ' in test' => [$failure, 'test', true, true];
            yield $failure . ' in production' => [$failure, 'prod', true, false];
            yield $failure . ' without installation' => [$failure, 'test', false, false];
        }
    }

}
