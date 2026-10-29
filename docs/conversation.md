# Interactive Conversations

Scenario Symfony AI supports interactive conversations during code generation.

The AI can request additional information when the initial description is insufficient.

## Follow-up Questions

For example, the AI might need to know:

- Which application class represents a particular entity.
- Which fields are required to create an object.
- Which persistence mechanism the application uses.
- Which behavior the generated Scenario should implement.

The CLI displays the AI's question and waits for your answer.

The conversation continues until a result is generated or the configured generation process cannot continue.

## Access to Existing Classes

Some Scenarios depend on existing application classes.

Scenario Symfony AI can identify relevant class names and request access to their source code.

Before source code is shared, the CLI asks for explicit confirmation:

```text
Can the source code for class "App\Entity\User" be sent to AI?
```

You can approve or decline each request.

Only approved source code is included through this interactive mechanism.

This allows the AI to better understand application-specific classes while keeping the developer in control of source code sharing.

**Source code is not shared through this mechanism without explicit confirmation.**

## Project Documentation

Scenario Symfony AI can use optional project-specific documentation to improve generated results.

Configure the documentation directory using the `projectDoc` attribute:

```xml
<scenario-ai
    maxQuestions="3"
    projectDoc="mydocs"
>
    <!-- Adapter configuration -->
</scenario-ai>
```

Project documentation can describe:

- Application architecture
- Entity relationships
- Coding conventions
- Persistence mechanisms
- Domain-specific requirements
- Common application workflows

This information helps the AI generate code that better matches the application's existing structure.

Project documentation is optional.

If no project documentation is configured, Scenario AI continues to use the Scenario framework documentation and code blueprints.

---

## Next Steps

- [Code Generation Process](generation.md)
- [AI Providers](providers.md)