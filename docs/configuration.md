# Configuration
This document explains the shared Scenario Symfony AI configuration file used by Scenario Symfony AI and all framework adapters.

The configuration defines how Scenario Symfony AI uses project data and which AI is used.

---

## Configure Scenario Symfony AI

Scenario AI uses two configuration files:

- `scenario.xml` – Defines the Scenario framework configuration, including suites, directories, and extensions. (see [stateforge/scenario-core](https://github.com/laloona/scenario-core))
- `scenario-ai.xml` – Defines the AI adapter and AI-specific settings.

Generate the `scenario-ai.xml` configuration file:

```bash
php vendor/bin/scenario ai-config
```
This command creates the Scenario Symfony AI configuration file in the project root directory.
Edit the generated file to configure your preferred AI provider and project-specific settings.

### `maxQuestions`

Controls the configured limit for AI follow-up questions.

```xml
maxQuestions="3"
```

If the value is missing, invalid, or negative, the default value is `3`.

### `projectDoc`

Specifies the path to optional project-specific documentation.

```xml
projectDoc="mydocs"
```

The path is resolved relative to the project root directory.

Project documentation helps the AI understand application-specific conventions, architecture, and requirements.

Only configure documentation that is appropriate to share with the selected AI provider.

## `allowProjectInformation`

Controls whether Scenario Symfony AI is allowed to include project-specific information in the context sent to the selected AI provider.

```xml
allowProjectInformation="true"
```

Set this attribute to `false` to disable the use of project-specific information. Use `true` only when you are comfortable sharing the relevant project context with the configured provider.

This setting is separate from `projectDoc`, which specifies the location of optional project documentation. Review what information your project supplies before enabling it.

> **Note:** The default value when this attribute is omitted is not specified here; check the application configuration implementation before relying on a default. This setting should not be assumed to replace the separate confirmation process for sharing individual source files.

## `adapter`

Defines the AI provider used for code generation. You can use `claude` or `openai` or implement use a `custom` adapter.

```xml
<adapter type="openai">
    <var name="apiKey">YOUR_OPENAI_API_KEY</var>
</adapter>
```

The `type` attribute selects the adapter.

Nested `var` elements provide adapter-specific configuration values.

Each variable uses a `name` attribute and a text value.

---

## Next Steps

- [Interactive Conversation](conversation.md)
- [Code Generation Process](generation.md)
- [AI Providers](providers.md)