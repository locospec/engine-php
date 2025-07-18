# Aggregate Calculation Algorithm

## Overview

This document describes the algorithm for calculating aggregates with relationship support in Locospec. The implementation allows aggregating data across related models using SQL JOINs and GROUP BY clauses.

## Key Concepts

1. **Aggregate Attributes**: Model attributes marked with `aggregateKey: true` that define aggregation rules
2. **Relationship Paths**: Dot-notation paths like `city.district.state.name` that traverse model relationships
3. **Table Aliasing**: Using `tableName_columnName` pattern to avoid conflicts in JOIN queries

## Algorithm Phases

### Phase 1: Path Analysis and Extraction

```
Input:
  aggregateAttribute: {
    aggregateGroupBy: ["city.district.state.name", "bank.name"],
    aggregates: [
      {function: "count", name: "property_count"},
      {function: "sum", source: "reserve_price", name: "total_value"},
      {function: "avg", source: "bank.total_assets", name: "avg_bank_assets"}
    ]
  }

Process:
1. Extract paths from GROUP BY fields:
   - "city.district.state.name" → path: "city.district.state"
   - "bank.name" → path: "bank"

2. Extract paths from aggregate sources:
   - "reserve_price" → no path (main table)
   - "bank.total_assets" → path: "bank"

3. Build unique path set: ["city.district.state", "bank"]

4. Optimize paths (remove subpaths):
   - If we have both "city" and "city.district", keep only "city.district"
```

### Phase 2: Build JOINs with Deduplication

```
Initialize:
  relationshipsJoined = {}  // Cache of joined relationships
  joins = []                // Array of JOIN clauses
  pathToTableMap = {}       // Maps paths to table names

For each unique path:
  Process path segments incrementally:
    
  Example for "city.district.state":
    Step 1: Process "city"
      - currentPath = "city"
      - relationship = properties.relationships.belongs_to.city
      - JOIN cities ON properties.city_uuid = cities.uuid
      - Cache: relationshipsJoined["city"] = {model: City, table: "cities"}
      
    Step 2: Process "district" 
      - currentPath = "city.district"
      - relationship = city.relationships.belongs_to.district
      - JOIN districts ON cities.district_uuid = districts.uuid
      - Cache: relationshipsJoined["city.district"] = {model: District, table: "districts"}
      
    Step 3: Process "state"
      - currentPath = "city.district.state"
      - relationship = district.relationships.belongs_to.state
      - JOIN states ON districts.state_uuid = states.uuid
      - Cache: relationshipsJoined["city.district.state"] = {model: State, table: "states"}
```

### Phase 3: Resolve Field Paths to Table.Column

```
Function resolveFieldPath(path):
  Examples:
    "city.district.state.name" → {table: "states", column: "name", alias: "states_name"}
    "bank.name" → {table: "banks", column: "name", alias: "banks_name"}
    "reserve_price" → {table: "properties", column: "reserve_price", alias: "reserve_price"}
    
  Algorithm:
    1. Split path by dots
    2. If single element, use main table
    3. Otherwise, get table from relationshipsJoined cache
    4. Build alias as tableName_columnName
```

### Phase 4: Build SQL Query

```sql
-- Template
SELECT 
  [GROUP BY fields with aliases],
  [Aggregate functions]
FROM [main_table]
[JOIN clauses]
WHERE [main_table].[primary_key] IN (source_ids)
GROUP BY [GROUP BY fields]

-- Example Result
SELECT 
  states.name as states_name,
  banks.name as banks_name,
  COUNT(*) as property_count,
  SUM(properties.reserve_price) as total_value,
  AVG(banks.total_assets) as avg_bank_assets
FROM properties
LEFT JOIN cities ON properties.city_uuid = cities.uuid
LEFT JOIN districts ON cities.district_uuid = districts.uuid
LEFT JOIN states ON districts.state_uuid = states.uuid
LEFT JOIN banks ON properties.bank_uuid = banks.uuid
WHERE properties.uuid IN (?, ?, ?, ...)
GROUP BY states.name, banks.name
```

### Phase 5: Execute and Map Results

```
1. Execute the aggregate query
2. Results come back with aliased columns:
   [
     {
       "states_name": "California",
       "banks_name": "Bank of America", 
       "property_count": 45,
       "total_value": 15000000,
       "avg_bank_assets": 5000000000
     }
   ]

3. Map results to the aggregate attribute name in main query results
```

## Implementation Classes

### AggregateCalculator

```php
class AggregateCalculator {
    private Model $model;
    private DatabaseOperationsCollection $dbOps;
    private Logger $logger;
    
    // Path tracking
    private array $relationshipsJoined = [];
    private array $pathToTableMap = [];
    
    public function calculate(array $dbOpResult): array
    private function extractPaths(Attribute $attribute): array
    private function buildJoinsForPaths(array $paths): array
    private function resolveFieldPath(string $path): array
    private function buildAggregateSelect(Attribute $attribute): array
    private function buildAggregateOperation(Attribute $attr, array $sourceIds): array
}
```

## Special Cases

### 1. Has-Many Relationships
- May produce duplicate rows
- Consider using subqueries or careful GROUP BY

### 2. Multiple Aggregates on Same Attribute
- Group by attribute to minimize queries
- Execute single query per attribute

### 3. Null Handling
- LEFT JOINs preserve records without relationships
- Aggregate functions handle NULLs appropriately (COUNT ignores, SUM treats as 0)

### 4. Complex Expressions
- Future enhancement: support for CASE statements
- Future enhancement: support for calculated fields

## Example Aggregates

### 1. Properties by Location and Bank
```json
{
  "aggregateGroupBy": ["city.district.state.name", "bank.name"],
  "aggregates": [
    {"function": "count", "name": "property_count"},
    {"function": "sum", "source": "reserve_price", "name": "total_value"}
  ]
}
```

### 2. Branch Performance Metrics
```json
{
  "aggregateGroupBy": ["bank_branch.uuid", "bank_branch.name", "bank_branch.city.name"],
  "aggregates": [
    {"function": "count", "name": "properties_managed"},
    {"function": "sum", "source": "reserve_price", "name": "portfolio_value"},
    {"function": "avg", "source": "reserve_price", "name": "avg_property_value"}
  ]
}
```

### 3. Properties with Document Counts
```json
{
  "aggregateGroupBy": ["uuid", "property_id"],
  "aggregates": [
    {"function": "count", "source": "files.uuid", "name": "document_count"}
  ]
}
```

## Performance Considerations

1. **Index Usage**: Ensure foreign keys are indexed
2. **Query Optimization**: Database will optimize JOIN order
3. **Result Set Size**: GROUP BY can reduce result set significantly
4. **Memory Usage**: Large aggregations may require pagination

## Error Handling

1. **Invalid Paths**: Validate all relationship paths exist
2. **Circular References**: Detect and prevent infinite loops
3. **Missing Relationships**: Handle gracefully with clear error messages
4. **SQL Injection**: Use parameterized queries for all dynamic values