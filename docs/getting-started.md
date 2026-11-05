# Getting Started
This guide complements the README and focuses on main usage.

---

## 1. Install Stateforge Scenario Symfony AI

> This package is intended for test and development use only.

Install the Scenario Symfony AI package in a project that already uses Stateforge Scenario via Composer as a development dependency.
```bash
composer require --dev stateforge/scenario-symfony-ai
```

## 2. Initialize Scenario Symfony AI

Run the config command:

```bash
php bin/console scenario:ai:config
```

This command initializes the Scenario AI installation and drops the `scenario-ai.xml` into project root.

## 3. Configure the AI Provider

Scenario Symfony AI uses a separate XML configuration file named `scenario-ai.xml`.

For example:

```xml
<scenario-ai xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="xsd/scenario-ai.xsd"
    maxQuestions="3"
    projectDoc="mydocs"
    allowProjectInformation="true"
>
    <adapter mode="all" type="openai">
        <var name="apiKey">YOUR_OPENAI_API_KEY</var>
    </adapter>
</scenario-ai>
```

Replace `YOUR_OPENAI_API_KEY` with your own API key.

**Important:** Never commit real API keys or other credentials to a public repository. Keep configuration files containing credentials outside version control.

## AI Code Generation

Scenario Symfony AI provides two ways to assist with creating Scenarios and Parameter Types: interactive code generation and skill generation for AI coding agents.

### Interactive Code Generation

Start the interactive AI code generator:

```bash
php bin/console scenario:ai:make
```

Scenario Symfony AI displays an introduction and asks you to describe the Scenario or Parameter Type you want to generate.

For example:

```text
Please describe the scenario or parameter type.
```

Enter your requirements in natural language.

Scenario Symfony AI analyzes the request and checks whether an existing implementation already satisfies the requirements. If no compatible implementation is available, the AI attempts to generate the corresponding PHP code.

If additional information is required, the AI may ask follow-up questions or request access to existing application classes.

### AI Skill Generation

Generate a skill for AI coding agents:

```bash
php bin/console scenario:ai:skill
```

Scenario Symfony AI generates a `SKILL.md` file containing instructions for AI coding agents.

The generated skill includes framework documentation, available Scenarios and Parameter Types, relevant class definitions, and code generation rules.

AI coding agents can use this information to understand the Stateforge Scenario Symfony framework, identify existing implementations, and generate compatible Scenarios or Parameter Types directly within the project.

Unlike interactive code generation, skill generation does not start an AI conversation or generate PHP code directly. Instead, it provides the necessary context and instructions for external AI coding agents.

---

## Next Steps

- [Configuration](configuration.md)
- [Interactive Conversation](conversation.md)
- [Code Generation Process](generation.md)
- [AI Skill Generation](skill.md)
- [AI Providers](providers.md)