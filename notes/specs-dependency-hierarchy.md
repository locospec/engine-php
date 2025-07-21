# LCS Specs Dependency Hierarchy

## Overview
Analysis of the schema dependencies in `LCS/engine-php/src/Specs` directory.

## Core Schema Dependencies

### 1. Foundation Level
- **`model.json`** - Base model definition
  - Defines core model structure, configuration, database connection
  - No dependencies on other specs

### 2. Model Extension Level
- **`attributes.json`** - Field definitions for models
  - Referenced by: `mutator.json` (line 34: `"$ref": "attributes.json#/definitions/attributes"`)
  - Defines data types, generators, validation rules
  - Depends on: None (standalone definitions)

- **`relationships.json`** - Model relationship definitions
  - Defines `belongs_to`, `has_many`, `has_one` relationships
  - References other models by name
  - Depends on: `model.json` (implicitly through model references)

### 3. Operation Level
- **`filters.json`** - Filter and condition definitions
  - Referenced by: `database-operations/common.json` (line 38: `"$ref": "../filters.json#/definitions/filters"`)
  - Defines condition operators, filter groups
  - Depends on: None (standalone definitions)

### 4. Database Operations Level
- **`database-operations/common.json`** - Shared operation definitions
  - References: `filters.json` for filter definitions
  - Defines scopes, sorts, common patterns
  - Depends on: `filters.json`

- **`database-operations/select.json`** - Select operation specs
  - Depends on: `common.json` (implicitly uses common definitions)

- **`database-operations/insert.json`** - Insert operation specs
  - Depends on: `common.json`

- **`database-operations/update.json`** - Update operation specs
  - Depends on: `common.json`

- **`database-operations/delete.json`** - Delete operation specs
  - Depends on: `common.json`

- **`database-operations/aggregate.json`** - Aggregate operation specs
  - Depends on: `common.json`

### 5. High-Level Spec Level
- **`query.json`** - Query specification
  - References: `model.json` (through model field)
  - Uses attributes, filters, relationships implicitly
  - Depends on: `model.json`, `attributes.json`, `relationships.json`

- **`mutator.json`** - Data mutation specification
  - Explicitly references: `attributes.json` (line 34)
  - References: `model.json` (through model field)
  - Depends on: `model.json`, `attributes.json`

## Dependency Graph

```
model.json (Foundation)
├── attributes.json (Extensions)
├── relationships.json (Extensions)
└── filters.json (Operations)
    └── database-operations/common.json
        ├── database-operations/select.json
        ├── database-operations/insert.json
        ├── database-operations/update.json
        ├── database-operations/delete.json
        └── database-operations/aggregate.json

High-Level Specs:
├── query.json → model.json, attributes.json, relationships.json
└── mutator.json → model.json, attributes.json
```

## Key Relationships

1. **`model.json`** is the foundation - all other specs reference models
2. **`attributes.json`** is explicitly imported by `mutator.json`
3. **`filters.json`** is explicitly imported by `database-operations/common.json`
4. **Database operations** all inherit from `common.json` patterns
5. **Query and Mutator specs** are high-level consumers that combine multiple lower-level specs

## Schema Validation Chain

1. Models must be defined first (`model.json`)
2. Attributes and relationships extend models
3. Filters provide query capabilities
4. Database operations use common patterns and filters
5. Query and Mutator specs orchestrate everything together
