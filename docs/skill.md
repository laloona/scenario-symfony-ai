# AI Skill Generation

Stateforge Symfony Scenario AI provides an `scenario:ai:skill` command to generate a reusable skill for coding agents working with the Scenario framework.

## Generate a Skill

From a project with Stateforge Scenario Symfony AI installed and registered, run:

```bash
php bin/consolse scenario:ai:skill
```

The command generates skill content rather than a Scenario or Parameter Type PHP implementation.

## Purpose

The generated skill gives coding agents a framework-specific reference for subsequent development requests. It is designed to help an agent:

- distinguish Scenario requests from Parameter Type requests;
- examine available implementations before generating new code;
- reuse existing classes when they fully satisfy the requested behavior;
- follow the documented attributes, method signatures, and framework APIs;
- use the relevant Scenario or Parameter Type blueprint when a new implementation is necessary;
- avoid inventing project-specific classes or methods when essential information is missing.

The skill contains framework documentation and class definitions. Its content should be reviewed for correctness and suitability before sharing it with an agent.

## How It Differs from `scenario:ai:make`

| Command | Purpose |
| --- | --- |
| `php bin/consolse scenario:ai:make` | Starts an interactive request to create or reuse a particular Scenario or Parameter Type. |
| `php bin/consolse scenario:ai:skill` | Generates reusable guidance for a coding agent to apply across future requests. |

Generating a skill does not itself create, review, or activate a PHP implementation.

## Using the Result

1. Run `scenario:ai:skill` in the intended Scenario project.
2. Test the agent with both a request requiring a new implementation and a request that an existing implementation already satisfies.

## Security and Review

Treat generated skill content as information that may be shared with the coding agent or its AI provider. A generated skill guides agent behavior but does not guarantee that every subsequent implementation is correct; review and test resulting code.

## Next Steps

- [AI Providers](providers.md)
