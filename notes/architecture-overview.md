# LCS Engine PHP - Architecture Overview

## 1. Introduction

The LCS Engine is a sophisticated PHP-based framework designed for building complex, state-driven applications. Its architecture is centered around a graph-based model, a powerful state machine, and a clear separation of concerns, making it highly modular, extensible, and maintainable.

This document provides a comprehensive overview of the engine's architecture, its core components, and the key concepts that govern its operation.

## 2. Core Engine Components

The engine is composed of several interconnected components, each with a distinct responsibility. The diagram below illustrates the high-level structure of the `src` directory.

```
LCSEngine
├── LCS.php                [Main Engine Facade]
│
├── Graph.php              [Core data structure for state representation]
│   ├── Vertex.php         [Represents a node or state in the graph]
│   └── Edge.php           [Represents a transition between vertices]
│
├── StateMachine/          [Manages state transitions and logic]
│   ├── StateMachine.php   [Orchestrates state transitions]
│   ├── Context.php        [Holds the current context of the state machine]
│   ├── StateInterface.php [Defines the contract for all state types]
│   ├── ChoiceState.php    [A state that branches based on conditions]
│   └── TaskState.php      [A state that executes a specific Task]
│
├── Registry/              [Service container for components and schemas]
│   ├── RegistryManager.php [Manages all registries]
│   ├── ModelRegistry.php   [Registry for data models]
│   ├── TaskRegistry.php    [Registry for Tasks]
│   └── ... (other registries for queries, drivers, etc.)
│
├── Actions/               [Reusable, atomic business logic operations]
│   ├── ActionOrchestrator.php [Executes and coordinates actions]
│   └── Model/             [Core CRUD and custom actions]
│       ├── CreateAction.php
│       ├── ReadListAction.php
│       ├── UpdateAction.php
│       ├── DeleteAction.php
│       └── ...
│
├── Tasks/                 [Complex jobs composed of Actions and other logic]
│   ├── AbstractTask.php   [Base class for all tasks]
│   ├── CreateEntityTask.php
│   ├── ValidateTask.php
│   ├── PreparePayloadTask.php
│   └── ...
│
├── Query/                 [Data querying and filtering logic]
│   └── ...
│
├── Database/              [Handles database interactions]
│   ├── DatabaseOperationsCollection.php [Manages a collection of DB operations]
│   └── ValueResolver.php  [Resolves values from various sources]
│
├── Specs & Schemas/
│   ├── Specifications/    [High-level application/domain specifications]
│   ├── Specs/             [Detailed specifications for validation]
│   ├── Schemas/           [JSON schema definitions for data validation]
│   └── SpecValidator.php  [Validates data against JSON schemas]
│
├── Logger.php             [Handles logging via Monolog]
└── MermaidRenderer.php    [Visualizes the graph as a Mermaid diagram]
```

## 3. Key Architectural Concepts

### 3.1. Graph-Based Model

The engine's core is a graph data structure (`Graph`, `Vertex`, `Edge`). This model is fundamental for representing complex workflows, processes, or state machines. Each `Vertex` represents a distinct state, and each `Edge` represents a valid transition between states, allowing for a flexible and powerful representation of system flow.

### 3.2. State Machine

A dedicated `StateMachine` component manages the logic of transitioning between states in the graph. It consumes a `Context` object that holds the current state and relevant data. The `StateMachine` uses different types of states, such as `TaskState` (to execute business logic) and `ChoiceState` (to handle conditional branching), to drive the application forward. This enforces rules and executes logic deterministically during state changes.

### 3.3. Specification and Schema-Driven Design

The engine relies heavily on specifications (`Specs`, `Specifications`) and JSON schemas (`Schemas`) to define and validate data structures and behavior. `SpecValidator.php` uses `opis/json-schema` to enforce these rules, ensuring data integrity throughout the system. This approach promotes a design-by-contract methodology, making the system more robust and predictable.

### 3.4. Decoupled Business Logic: Actions and Tasks

Business logic is organized into two distinct layers: `Actions` and `Tasks`.

-   **Actions**: These are atomic, reusable, and framework-agnostic units of work. They typically perform a single, well-defined operation, such as CRUD operations on a model (`CreateAction`, `UpdateAction`).
-   **Tasks**: These represent more complex, high-level processes that orchestrate multiple `Actions` and other logic. For example, `CreateEntityTask` might coordinate validation, authorization, data preparation, and the final `CreateAction`.

This separation allows for clear, maintainable, and testable code.

### 3.5. Centralized Service Registry

The `Registry` component, managed by the `RegistryManager`, acts as a service container. It provides a central place to register and access different parts of the engine, such as `Tasks`, data models (`ModelRegistry`), database drivers, and more. This promotes loose coupling and makes components easily swappable.

### 3.6. Advanced Data Handling

-   **Collections**: The extensive use of `illuminate/collections` provides a fluent, expressive, and powerful API for data manipulation, reducing boilerplate and improving code readability.
-   **Querying**: The `Query` directory, combined with the `mtdowling/jmespath.php` dependency, provides a sophisticated system for querying and extracting data from complex JSON-like data structures.

### 3.7. Visualization and Debugging

The `MermaidRenderer.php` is a unique and powerful feature that dynamically generates [Mermaid.js](https://mermaid-js.github.io/mermaid/#/) diagrams from the graph structure. This provides an invaluable tool for visualizing, understanding, and debugging the system's state and flow.

## 4. Detailed Component Breakdown

### 4.1. `Actions`

-   **Location**: `src/Actions`
-   **Purpose**: Contains the atomic, reusable business logic units.
-   **Key Classes**:
    -   `ActionOrchestrator.php`: Executes actions and manages their lifecycle.
    -   `Model/`: This subdirectory contains the core actions, many of which are related to models, such as `CreateAction`, `ReadListAction`, `UpdateAction`, and `DeleteAction`.

### 4.2. `Tasks`

-   **Location**: `src/Tasks`
-   **Purpose**: Defines complex, multi-step processes that orchestrate `Actions`.
-   **Key Classes**:
    -   `AbstractTask.php`: The base class that all concrete tasks extend.
    -   `CreateEntityTask.php`: A high-level task for creating a new entity, likely involving validation, authorization, and action execution.
    -   `ValidateTask.php`: A task dedicated to performing validation logic.
    -   `TaskFactory.php`: Responsible for creating task instances.

### 4.3. `Database`

-   **Location**: `src/Database`
-   **Purpose**: Abstracts and manages all database interactions.
-   **Key Classes**:
    -   `DatabaseOperationsCollection.php`: Manages and executes a series of database operations.
    -   `ValueResolver.php`: A utility for resolving values from different contexts, crucial for dynamic queries.
    -   `JMESPathCustomRuntime.php`: Extends the JMESPath query language with custom functions.

### 4.4. `Registry`

-   **Location**: `src/Registry`
-   **Purpose**: Acts as a service container for the engine's components.
-   **Key Classes**:
    -   `RegistryManager.php`: The central manager for all specialized registries.
    -   `ModelRegistry.php`: Manages the registration and retrieval of data models.
    -   `TaskRegistry.php`: Manages the registration and retrieval of tasks.

### 4.5. `StateMachine`

-   **Location**: `src/StateMachine`
-   **Purpose**: Manages and executes state transitions within the graph.
-   **Key Classes**:
    -   `StateMachine.php`: The core state machine executor.
    -   `Context.php`: A data object that holds the state and payload as it moves through the machine.
    -   `StateInterface.php`: The contract for all state classes.
    -   `TaskState.php`: A state that executes a registered `Task`.
    -   `ChoiceState.php`: A state that directs the flow based on conditional logic.

## 5. Dependencies and Their Roles

-   **`doctrine/inflector`**: Used for string manipulation, likely for converting between different naming conventions (e.g., class names to table names).
-   **`illuminate/collections`**: The primary tool for all data and array manipulation, providing a fluent and powerful API.
-   **`monolog/monolog`**: Powers the `Logger.php` for robust, channel-based logging.
-   **`mtdowling/jmespath.php`**: The engine for querying JSON data, used heavily within the `Query` and `Database` components.
-   **`opis/json-schema`**: The engine for JSON Schema validation, used by `SpecValidator.php` to ensure data integrity.
