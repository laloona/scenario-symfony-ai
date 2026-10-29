# AI Providers

Scenario Symfony AI separates code generation from provider-specific communication. Built-in adapters support OpenAI and Claude. You can also register a custom adapter without changing the conversation, validation, or file generation workflow.

Configure the selected provider in `scenario-ai.xml` using an `<adapter>` element. Each `<var>` supplies a named configuration value. Only `apiKey` is required for the built-in adapters; omitted optional values use the defaults shown below.

---

## OpenAI

The OpenAI adapter uses the Responses API and processes structured responses.

```xml
<adapter type="openai">
    <var name="apiKey">YOUR_OPENAI_API_KEY</var>
    <var name="apiEndpoint">https://api.openai.com/v1/responses</var>
    <var name="model">gpt-5.4-mini</var>
</adapter>
```

| Variable | Required | Default | Description |
| --- | --- | --- | --- |
| `apiKey` | Yes | — | API key used to authenticate requests. |
| `apiEndpoint` | No | `https://api.openai.com/v1/responses` | Endpoint for the OpenAI Responses API. |
| `model` | No | `gpt-5.4-mini` | Model identifier sent to the API. |

You can omit optional `<var>` elements to use the defaults. Select a model supported by your API account and endpoint.

## Claude

The Claude adapter uses the Anthropic Messages API and processes structured responses.

```xml
<adapter type="claude">
    <var name="apiKey">YOUR_CLAUDE_API_KEY</var>
    <var name="apiEndpoint">https://api.anthropic.com/v1/messages</var>
    <var name="model">claude-opus-5-5</var>
    <var name="version">2026-10-21</var>
    <var name="maxTokens">8192</var>
</adapter>
```

| Variable | Required | Default | Description |
| --- | --- | --- | --- |
| `apiKey` | Yes | — | API key used to authenticate requests. |
| `apiEndpoint` | No | `https://api.anthropic.com/v1/messages` | Endpoint for the Anthropic Messages API. |
| `model` | No | `claude-opus-5-5` | Model identifier sent to the API. |
| `version` | No | `2026-10-21` | Value of the `anthropic-version` request header. |
| `maxTokens` | No | `8192` | Maximum number of output tokens requested. |

The defaults above reflect the adapter's current configuration class. Check that the configured model and API version are accepted by your Anthropic account. `maxTokens` must be a valid integer value.

## Custom Adapters

A custom adapter lets you connect another AI service, a company-hosted model, or a different transport implementation. Scenario AI retains responsibility for instruction generation, conversation management, code validation, and writing generated files.

### 1. Implement the contracts

A custom adapter implements `Stateforge\Scenario\Ai\Contract\AiAdapter`. Its `client()` method receives a `ConfigBuilder`, an `AdapterConfiguration` (including the configured variables), and the prepared `Instruction`. It must return an implementation of `Stateforge\Scenario\Ai\Contract\AiClient`.

The client implements `request(AiRequest $request, array $audits): AiResponse`. The `$audits` argument contains the preceding conversation exchanges, so your client can preserve the conversation history when sending requests to its provider.

For example, place an adapter class in `scenario/Ai/MyProviderAdapter.php` within your project:

```php
<?php declare(strict_types=1);

namespace Stateforge\Ai;

use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;

final class MyProviderAdapter implements AiAdapter
{
    public function client(
        ConfigBuilder $configBuilder,
        AdapterConfiguration $adapterConfiguration,
        Instruction $instruction,
    ): AiClient {
        // Construct your provider-specific AiClient here.
        // Use $adapterConfiguration->vars for configured values,
        // and pass $instruction to the client for prompt construction.
        return new MyProviderClient($adapterConfiguration->vars, $instruction);
    }
}
```

`MyProviderClient` in this example is **your own class**, not part of Scenario AI. Implement it according to the `AiClient` contract:

```php
<?php declare(strict_types=1);

namespace Stateforge\Ai;

use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;

final class MyProviderClient implements AiClient
{
    /** @param array<string, string> $vars */
    public function __construct(
        private readonly array $vars,
        private readonly Instruction $instruction,
    ) {
    }

    /** @param list<\Stateforge\Scenario\Ai\Runtime\Adapter\AiAudit> $audits */
    public function request(AiRequest $request, array $audits): AiResponse
    {
        // TODO: Send instructions, the current request, and previous
        // conversation exchanges to your provider.
        // TODO: Convert its response into an AiResponse.
        throw new \LogicException('Implement provider communication.');
    }
}
```

These examples demonstrate the integration points; the client is deliberately a skeleton and is not usable until provider communication and response mapping are implemented. Refer to the built-in OpenAI and Claude adapters for working implementations.

### 2. Register the adapter

Set `type="custom"`, provide the fully qualified adapter class name, and specify the directory containing that class:

```xml
<adapter
    type="custom"
    class="Stateforge\Ai\MyProviderAdapter"
    path="scenario/Ai"
>
    <var name="apiKey">YOUR_PROVIDER_API_KEY</var>
    <var name="model">your-model</var>
</adapter>
```

- `type="custom"` selects the custom adapter loader.
- `class` identifies the class implementing `AiAdapter`.
- `path` identifies a directory **relative to the project root**; Scenario AI scans it for classes.
- `<var>` entries are passed to your adapter as `$adapterConfiguration->vars`. Their names and interpretation are defined by your implementation.

Both `class` and `path` are required for a custom adapter. Ensure the class can be loaded and implements `AiAdapter`.

### 3. Return compatible responses

Your `AiClient` must return an `AiResponse` compatible with Scenario AI's generation workflow. It should pass the prepared instructions and the relevant conversation history to the provider and map the provider's output into the expected response structure. The existing `ResponseDecoder` and `ResponseBuilder` are available as reference implementations for structured response processing.

Custom adapters do not need to reimplement Scenario AI's validation, AST processing, or file generation.

## Security

Never commit real API keys to version control. Keep credentials in configuration files excluded from the repository or use an appropriate secrets-management mechanism. Review which instructions, project documentation, and approved source files are transmitted to your chosen provider.

