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
use RuntimeException;
use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration as CoreConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use Stateforge\Scenario\SymfonyAi\Console\Command\ScenarioAiMakeCommand;
use Stateforge\Scenario\SymfonyAi\Runtime\Context;
use Stateforge\Scenario\SymfonyAi\Tests\Files\SharedProjectClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use function array_shift;
use function class_exists;
use function count;
use function file_get_contents;
use function preg_replace;
use function str_replace;
use function sys_get_temp_dir;
use function uniqid;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

#[CoversClass(ScenarioAiMakeCommand::class)]
#[UsesClass(Context::class)]
#[Group('command')]
#[Small]
final class ScenarioAiMakeCommandTest extends TestCase
{
    private string $projectDirectory;

    /** @var list<AiRequest> */
    private array $requests = [];

    protected function setUp(): void
    {
        $this->requests = [];
        $this->projectDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'scenario-symfony-ai-make-' . uniqid('', true);
        (new Filesystem())->mkdir($this->projectDirectory);
        $configuration = self::createStub(CoreConfiguration::class);
        $configuration->method('getSuites')->willReturn([
            'main' => new SuiteValue('main', 'Main suite', $this->projectDirectory . DIRECTORY_SEPARATOR . 'scenario' . DIRECTORY_SEPARATOR . 'main'),
        ]);
        $configuration->method('getParameterDirectory')->willReturn($this->projectDirectory . DIRECTORY_SEPARATOR . 'scenario' . DIRECTORY_SEPARATOR . 'parameter');
        $core = new ReflectionClass(Application::class);
        $core->getProperty('rootDir')->setValue(null, $this->projectDirectory);
        $core->getProperty('configuration')->setValue(null, $configuration);
        $ai = new ReflectionClass(Ai::class);
        $ai->getProperty('config')->setValue(null, new Configuration(
            null,
            false,
            3,
            new AdapterConfiguration('local-test', null, null, []),
        ));
        // Never allow Ai::registry() to initialize the real API adapters.
        $ai->getProperty('registry')->setValue(null, new AdapterRegistry());
    }

    protected function tearDown(): void
    {
        $core = new ReflectionClass(Application::class);
        $core->getProperty('rootDir')->setValue(null, null);
        $core->getProperty('configuration')->setValue(null, null);
        $ai = new ReflectionClass(Ai::class);
        $ai->getProperty('config')->setValue(null, null);
        $ai->getProperty('registry')->setValue(null, null);
        (new Filesystem())->remove($this->projectDirectory);
    }

    public function testCommandIsConfigured(): void
    {
        $command = $this->command();

        self::assertSame('scenario:ai:make', $command->getName());
        self::assertSame('Make a scenario or parameter type with AI - should only be used for dev/test', $command->getDescription());
    }

    #[DataProvider('generationProvider')]
    public function testGeneratesAndWritesValidatedCodeWithoutApiCall(string $type): void
    {
        $response = $this->generatedResponse($type);
        $this->responses([$response]);
        $tester = new CommandTester($this->command());
        $tester->setInputs(['Generate code']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertCount(1, $this->requests);
        self::assertSame('Generate code' . PHP_EOL, $this->requests[0]->prompt);
        $target = $this->generatedPath($type);
        self::assertSame($response->php, file_get_contents($target . '.ai'));
        self::assertFileDoesNotExist($target);
        self::assertStringContainsString('Welcome to Scenario Symfony AI!', $tester->getDisplay());
        self::assertStringContainsString('configure your project doc', $tester->getDisplay());
        $display = str_replace(['\\', "\r\n"], ['/', "\n"], $tester->getDisplay());
        $expectedPath = str_replace('\\', '/', $target . '.ai');
        self::assertStringContainsString(
            preg_replace('/\s+/', '', $expectedPath) ?? $expectedPath,
            preg_replace('/\s+/', '', $display) ?? $display,
        );
    }

    /** @return array<string, array{string}> */
    public static function generationProvider(): array
    {
        return ['scenario' => ['scenario'], 'parameter' => ['parameter']];
    }

    public function testAsksFollowUpQuestionAndForwardsAnswer(): void
    {
        $this->responses([
            new AiResponse('question', null, GenerationType::Scenario, 'Which state?', []),
            $this->generatedResponse(),
        ], projectDoc: 'docs');
        $tester = new CommandTester($this->command());
        $tester->setInputs(['Generate code', 'Empty database']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertCount(2, $this->requests);
        self::assertSame(2, $this->requests[1]->counter);
        self::assertSame('Empty database' . PHP_EOL . PHP_EOL, $this->requests[1]->prompt);
        self::assertStringContainsString('AI: Which state?', $tester->getDisplay());
        self::assertStringNotContainsString('configure your project doc', $tester->getDisplay());
    }

    #[DataProvider('consentProvider')]
    public function testPromptSourceCodeIsOnlySharedWithConsent(bool $allowed): void
    {
        self::assertTrue(class_exists(SharedProjectClass::class), 'The shared project fixture must be autoloadable.');
        $this->responses([$this->generatedResponse()]);
        $tester = new CommandTester($this->command());
        $tester->setInputs(['Use ' . SharedProjectClass::class . ' and MissingProject\\UnknownClass', $allowed ? 'yes' : 'no']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertCount(1, $this->requests);
        $this->assertSourceSharing($allowed, $this->requests[0]);
    }

    #[DataProvider('consentProvider')]
    public function testRequestedClassSourceCodeRequiresConsent(bool $allowed): void
    {
        self::assertTrue(class_exists(SharedProjectClass::class), 'The shared project fixture must be autoloadable.');
        $this->responses([
            new AiResponse('classes', null, GenerationType::Scenario, null, [SharedProjectClass::class]),
            $this->generatedResponse(),
        ]);
        $tester = new CommandTester($this->command());
        $tester->setInputs(['Generate code', $allowed ? 'yes' : 'no']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertCount(2, $this->requests);
        $this->assertSourceSharing($allowed, $this->requests[1]);
    }

    /** @return array<string, array{bool}> */
    public static function consentProvider(): array
    {
        return ['allowed' => [true], 'declined' => [false]];
    }

    #[DataProvider('incompleteResponseProvider')]
    public function testReportsMissingCodeAtQuestionLimit(?string $php, ?string $type): void
    {
        $this->responses([new AiResponse('id', $php, $type === null ? null : GenerationType::from($type), 'Need details', [])], limit: 1);
        $tester = new CommandTester($this->command());
        $tester->setInputs(['Generate code']);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('No code was generated by AI, please try again.', $tester->getDisplay());
        self::assertDirectoryDoesNotExist($this->projectDirectory . DIRECTORY_SEPARATOR . 'scenario');
    }

    /** @return array<string, array{?string, ?string}> */
    public static function incompleteResponseProvider(): array
    {
        return ['no code' => [null, 'scenario'], 'no type' => ['<?php class Demo {}', null]];
    }

    public function testClientFailurePropagatesWithoutGeneratingFiles(): void
    {
        $client = $this->createMock(AiClient::class);
        $client->expects(self::once())->method('request')->willThrowException(new RuntimeException('Local mock failure'));
        $this->registerClient($client);
        $tester = new CommandTester($this->command());
        $tester->setInputs(['Generate code']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Local mock failure');

        try {
            $tester->execute([]);
        } finally {
            self::assertDirectoryDoesNotExist($this->projectDirectory . DIRECTORY_SEPARATOR . 'scenario');
        }
    }

    /** @param list<AiResponse> $responses */
    private function responses(array $responses, int $limit = 3, ?string $projectDoc = null): void
    {
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, new Configuration(
            $projectDoc,
            false,
            $limit,
            new AdapterConfiguration('local-test', null, null, []),
        ));
        $client = $this->createMock(AiClient::class);
        $client->expects(self::exactly(count($responses)))->method('request')->willReturnCallback(
            function (AiRequest $request) use (&$responses): AiResponse {
                $this->requests[] = $request;
                $response = array_shift($responses);
                self::assertInstanceOf(AiResponse::class, $response);
                return $response;
            },
        );
        $this->registerClient($client);
    }

    private function registerClient(AiClient $client): void
    {
        $adapter = self::createStub(AiAdapter::class);
        $adapter->method('client')->willReturn($client);
        $registry = new AdapterRegistry();
        $registry->register('local-test', $adapter);
        (new ReflectionClass(Ai::class))->getProperty('registry')->setValue(null, $registry);
    }

    private function generatedResponse(string $type = 'scenario'): AiResponse
    {
        $attribute = $type === 'scenario' ? 'AsScenario' : 'AsParameterType';
        $parent = $type === 'scenario' ? 'Scenario' : 'ParameterTypeDefinition';
        $code = '<?php namespace Stateforge\\Suite\\Scenario\\Main; #[\\Stateforge\\Scenario\\Core\\Attribute\\' . $attribute . '] final class Generated extends \\Stateforge\\Scenario\\Symfony\\' . $parent . ' {}';

        return new AiResponse('generated', $code, GenerationType::from($type), null, []);
    }

    private function generatedPath(string $type): string
    {
        return $this->projectDirectory . DIRECTORY_SEPARATOR . 'scenario' . DIRECTORY_SEPARATOR . ($type === 'scenario' ? 'main' : 'parameter') . DIRECTORY_SEPARATOR . 'Generated.php';
    }

    private function assertSourceSharing(bool $allowed, AiRequest $request): void
    {
        if ($allowed) {
            self::assertStringContainsString('Symfony project source marker', $request->prompt);
        } else {
            self::assertStringNotContainsString('Symfony project source marker', $request->prompt);
        }
    }

    private function command(): ScenarioAiMakeCommand
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('test');
        $kernel->method('getProjectDir')->willReturn($this->projectDirectory);

        return new ScenarioAiMakeCommand($kernel, new Filesystem());
    }
}
