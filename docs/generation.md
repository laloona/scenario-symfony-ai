# Code Generation Process
Scenario Symfony AI combines natural language processing with the structure and conventions of the Scenario framework.

The generation process consists of several steps.

---

## 1. Build Instructions

Scenario Symfony AI prepares instructions for the selected AI provider.

These instructions include the relevant Scenario framework documentation, code blueprints, and configuration information.

Optional project documentation can provide additional context.

## 2. Process the Request

The AI analyzes the natural language request and determines which type of code should be generated.

The supported generation types include:

- Scenario
- Parameter Type

## 3. Request Additional Information

If the request is incomplete or ambiguous, the AI may ask follow-up questions.

It may also request access to existing application classes.

Source code is only shared after explicit confirmation.

## 4. Generate PHP Code

The AI generates PHP code based on the available instructions and project context.

The generated implementation may include:

- Namespace declarations
- Class definitions
- Required imports
- Framework attributes
- Method implementations
- TODO comments for missing application-specific details

## 5. Validate the Result

Scenario Symfony AI validates the generated result before accepting it.

If validation fails, the generation process can request a corrected response.

Validation helps ensure that generated code follows the expected structure and conventions of the Scenario framework.

## 6. Determine the Destination

The generated PHP code is analyzed using an Abstract Syntax Tree (AST).

Scenario Symfony AI uses the generated namespace and class name together with the Scenario configuration to determine the destination directory.

## 7. Save the Generated Code

Once the result has been accepted, the generated PHP code is saved to a file.

The file receives an `.ai` suffix to prevent immediate activation.

## 8. Review and Activate

Review the generated implementation carefully.

When you are satisfied with the result, remove the `.ai` suffix to activate the file.

## Generated Files

Scenario Symfony AI uses the configured Scenario suites and Parameter Type directory to determine where generated files belong.

For example, the following Scenario configuration:

```xml
<suites>
    <suite name="main" description="Main Scenario Suite">
        <directory>scenario/main</directory>
    </suite>
</suites>
```

defines the directory for the `main` Scenario suite.

A generated Scenario might be saved as:

```text
scenario/main/CreateThreeUsersInFreshDatabase.php.ai
```

After reviewing the implementation, rename the file:

```text
scenario/main/CreateThreeUsersInFreshDatabase.php
```

The same principle applies to generated Parameter Types.

For example:

```text
scenario/parameter/BirthDateType.php.ai
```

After review:

```text
scenario/parameter/BirthDateType.php
```

**Generated files must be reviewed before activation.**

## Validation

Scenario Symfony AI includes a validation system for generated code.

The validation process is designed to check generated implementations against the requirements of the Scenario framework.

Validation is independent of the AI provider.

This allows the same validation process to be used with different AI adapters.

If validation fails, Scenario AI can request a corrected response.

However, validation does not guarantee that generated code is logically correct, secure, or suitable for production.

Developers remain responsible for reviewing and testing generated implementations.

---

## Next Steps

- [AI Providers](providers.md)