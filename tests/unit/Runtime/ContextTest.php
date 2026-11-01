<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\SymfonyAi package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\SymfonyAi\Tests\Unit\Runtime;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\Exception\AttributeValidatorException;
use Stateforge\Scenario\Ai\Runtime\Exception\ClassDoesNotExistException;
use Stateforge\Scenario\Ai\Runtime\Exception\InheritanceValidatorException;
use Stateforge\Scenario\Ai\Runtime\Validation\Validators;
use Stateforge\Scenario\Core\Attribute\AsScenario;
use Stateforge\Scenario\Core\Contract\ScenarioInterface;
use Stateforge\Scenario\Core\ParameterType;
use Stateforge\Scenario\Core\ParameterTypeDefinition as CoreParameterTypeDefinition;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration as CoreConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use Stateforge\Scenario\Core\Runtime\Metadata\Parameter\ParameterTypeRegistry;
use Stateforge\Scenario\Core\Runtime\ScenarioDefinition;
use Stateforge\Scenario\Core\Runtime\ScenarioRegistry;
use Stateforge\Scenario\Core\Scenario as CoreScenario;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Stateforge\Scenario\Symfony\Scenario;
use Stateforge\Scenario\SymfonyAi\Runtime\Context;
use Stateforge\Scenario\SymfonyAi\Runtime\Instruction\ParameterImplementationInstruction;
use Stateforge\Scenario\SymfonyAi\Runtime\Instruction\SymfonyVersion;
use Symfony\Component\Filesystem\Filesystem;
use function file_get_contents;
use function file_put_contents;
use function str_replace;
use function sys_get_temp_dir;
use function uniqid;
use const DIRECTORY_SEPARATOR;

#[CoversClass(Context::class)]
#[UsesClass(SymfonyVersion::class)]
#[UsesClass(ParameterImplementationInstruction::class)]
#[Group('runtime')]
#[Small]
final class ContextTest extends TestCase
{
    private string $projectDirectory;

    protected function setUp(): void
    {
        $this->projectDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'scenario-symfony-ai-context-' . uniqid('', true);
        $package = $this->projectDirectory . '/vendor/stateforge/scenario-symfony';
        (new Filesystem())->mkdir([$package . '/docs', $package . '/blueprint', $this->projectDirectory . '/project-docs']);
        file_put_contents($package . '/docs/framework.md', 'Symfony framework documentation marker');
        file_put_contents($package . '/blueprint/scenario.blueprint', 'Symfony scenario blueprint marker');
        file_put_contents($package . '/blueprint/parameter.blueprint', 'Symfony parameter blueprint marker');
        file_put_contents($this->projectDirectory . '/project-docs/project.md', 'Project documentation marker');
        $core = new ReflectionClass(Application::class);
        $core->getProperty('rootDir')->setValue(null, $this->projectDirectory);
        $core->getProperty('configuration')->setValue(null, null);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
        ScenarioRegistry::getInstance()->clear();
        (new ReflectionClass(ParameterTypeRegistry::class))->getProperty('instance')->setValue(null, null);
    }

    protected function tearDown(): void
    {
        $core = new ReflectionClass(Application::class);
        $core->getProperty('rootDir')->setValue(null, null);
        $core->getProperty('configuration')->setValue(null, null);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
        ScenarioRegistry::getInstance()->clear();
        (new ReflectionClass(ParameterTypeRegistry::class))->getProperty('instance')->setValue(null, null);
        (new Filesystem())->remove($this->projectDirectory);
    }

    public function testInstructionRequiresInitializedCoreConfiguration(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Scenario configuration is not initialized.');

        (new Context())->instruction(new AiRequest(null, 'Generate', 1));
    }

    #[DataProvider('projectInformationProvider')]
    public function testBuildsSymfonyInstructionsAndHonorsProjectConfiguration(?string $projectDoc, bool $allowed): void
    {
        $core = self::createStub(CoreConfiguration::class);
        $core->method('getSuites')->willReturn(['main' => new SuiteValue('main', 'Main Symfony suite', 'scenario/main')]);
        $core->method('getParameterDirectory')->willReturn('scenario/parameter');
        (new ReflectionClass(Application::class))->getProperty('configuration')->setValue(null, $core);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, new Configuration(
            $projectDoc,
            $allowed,
            7,
            new AdapterConfiguration(AdapterMode::All, 'not-used', null, null, []),
        ));
        $scenario = self::createStub(Scenario::class);
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            $scenario::class,
            new AsScenario('listed', 'Registered project scenario marker'),
            [],
        ));

        $text = (new Context())->instruction(new AiRequest(null, 'Generate', 2))->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Symfony framework documentation marker', $text);
        self::assertStringContainsString('Symfony scenario blueprint marker', $text);
        self::assertStringContainsString('Symfony parameter blueprint marker', $text);
        self::assertMatchesRegularExpression('/Symfony parameter blueprint marker[\s\S]*ParameterType Implementation Rules[\s\S]*Final Validation/', $text);
        self::assertStringContainsString('Implement complete validation rules in constraints().', $text);
        self::assertStringContainsString('name: main, description: Main Symfony suite, directory: scenario/main, namespace: Scenario\\Main', $text);
        self::assertStringContainsString('directory: scenario/parameter, namespace: Scenario\\Parameter', $text);
        self::assertStringContainsString('Maximum clarification questions: 7', $text);
        self::assertStringContainsString('Clarification questions already asked: 2', $text);
        self::assertStringContainsString('Base class definitions:', $text);
        foreach ([
            Scenario::class,
            ParameterTypeDefinition::class,
            CoreScenario::class,
            ScenarioInterface::class,
            ParameterType::class,
            CoreParameterTypeDefinition::class,
        ] as $class) {
            $path = (new ReflectionClass($class))->getFileName();
            self::assertIsString($path);
            $source = file_get_contents($path);
            self::assertIsString($source);
            self::assertStringContainsString($source, $text, 'Missing class definition: ' . $class);
        }
        self::assertStringContainsString('namespace Stateforge\\Scenario\\Symfony;', $text);
        self::assertStringContainsString('abstract class Scenario extends', $text);
        self::assertStringContainsString('abstract class ParameterTypeDefinition extends', $text);
        if ($projectDoc === null) {
            self::assertStringNotContainsString('Project documentation marker', $text);
        } else {
            self::assertStringContainsString('Project documentation marker', $text);
        }
        if ($allowed) {
            self::assertStringContainsString('Registered project scenario marker', $text);
        } else {
            self::assertStringNotContainsString('Registered project scenario marker', $text);
        }
    }

    /** @return array<string, array{?string, bool}> */
    public static function projectInformationProvider(): array
    {
        return [
            'neither' => [null, false],
            'documentation only' => ['project-docs', false],
            'project information only' => [null, true],
            'both' => ['project-docs', true],
        ];
    }

    #[DataProvider('generationProvider')]
    public function testValidatorAcceptsSymfonyClassesWithRequiredAttribute(string $type, string $parent, string $attribute): void
    {
        $code = '<?php #[\\' . $attribute . '] class Generated extends \\' . $parent . ' {}';
        $validator = (new Context())->validator();
        self::assertInstanceOf(Validators::class, $validator);

        $validator->validate(new AiResponse(null, $code, GenerationType::from($type), null, [], null));
    }

    #[DataProvider('generationProvider')]
    public function testValidatorRejectsMissingRequiredAttribute(string $type, string $parent, string $attribute): void
    {
        $code = '<?php class Generated extends \\' . $parent . ' {}';
        $this->expectException(AttributeValidatorException::class);
        $this->expectExceptionMessage('Missing required attribute "' . $attribute . '".');

        (new Context())->validator()->validate(new AiResponse(null, $code, GenerationType::from($type), null, [], null));
    }

    #[DataProvider('generationProvider')]
    public function testValidatorRejectsCoreBaseClassInsteadOfSymfonyAdapter(string $type, string $parent, string $attribute): void
    {
        $coreParent = str_replace('\\Symfony\\', '\\Core\\', $parent);
        $code = '<?php #[\\' . $attribute . '] class Generated extends \\' . $coreParent . ' {}';
        $this->expectException(InheritanceValidatorException::class);

        (new Context())->validator()->validate(new AiResponse(null, $code, GenerationType::from($type), null, [], null));
    }

    public function testValidatorRejectsMissingClassesBeforeCheckingGeneratedCode(): void
    {
        $response = new AiResponse('id', '<?php class {', GenerationType::Scenario, null, ['Missing\RequestedClass'], null);
        $this->expectException(ClassDoesNotExistException::class);
        $this->expectExceptionMessage('class "Missing\RequestedClass" does not exist.');

        (new Context())->validator()->validate($response);
    }

    public function testValidatorRejectsMissingMatchWithoutGeneratedCode(): void
    {
        $response = new AiResponse('id', null, GenerationType::Scenario, null, [], 'Missing\MatchingClass');
        $this->expectException(ClassDoesNotExistException::class);
        $this->expectExceptionMessage('class "Missing\MatchingClass" does not exist.');

        (new Context())->validator()->validate($response);
    }

    public function testValidatorAcceptsExistingClassReferencesWithoutGeneratedCode(): void
    {
        $response = new AiResponse('id', null, GenerationType::Scenario, null, [self::class], self::class);

        (new Context())->validator()->validate($response);

        self::assertSame([self::class], $response->classes);
        self::assertSame(self::class, $response->match);
    }

    /** @return array<string, array{string, string, string}> */
    public static function generationProvider(): array
    {
        return [
            'scenario' => ['scenario', 'Stateforge\\Scenario\\Symfony\\Scenario', 'Stateforge\\Scenario\\Core\\Attribute\\AsScenario'],
            'parameter' => ['parameter', 'Stateforge\\Scenario\\Symfony\\ParameterTypeDefinition', 'Stateforge\\Scenario\\Core\\Attribute\\AsParameterType'],
        ];
    }
}
