# Aggregate Types Analysis

## Overview
Aggregations can happen at different levels and serve different visualization purposes. We need to distinguish between these types to implement them correctly.

## Types of Aggregations

### 1. Model-Level Aggregation (Summary Statistics)
**Purpose**: Get summary statistics for the entire result set
**Example**: "What's the total value of all properties in the result?"

```json
{
  "type": "model_summary",
  "aggregates": [
    {"function": "sum", "source": "reserve_price", "name": "total_value"},
    {"function": "avg", "source": "reserve_price", "name": "avg_value"},
    {"function": "count", "name": "total_count"}
  ]
}
```

**SQL Pattern**:
```sql
SELECT 
  SUM(reserve_price) as total_value,
  AVG(reserve_price) as avg_value,
  COUNT(*) as total_count
FROM properties 
WHERE uuid IN (?, ?, ?) -- from main query results
```

**Result**: Single row with aggregated values

### 2. Per-Record Has-Many Aggregation
**Purpose**: For each record, aggregate its has-many relationships
**Example**: "For each property, how many files/documents does it have?"

```json
{
  "type": "per_record_has_many",
  "relationship": "files",
  "aggregates": [
    {"function": "count", "name": "file_count"},
    {"function": "sum", "source": "size", "name": "total_size"}
  ]
}
```

**SQL Pattern** (Option 1 - Subquery):
```sql
SELECT 
  p.*,
  (SELECT COUNT(*) FROM files WHERE owner_identifier = p.uuid) as file_count,
  (SELECT SUM(size) FROM files WHERE owner_identifier = p.uuid) as total_size
FROM properties p
WHERE p.uuid IN (?, ?, ?)
```

**SQL Pattern** (Option 2 - LEFT JOIN with GROUP BY):
```sql
SELECT 
  p.uuid,
  p.property_id,
  COUNT(f.uuid) as file_count,
  SUM(f.size) as total_size
FROM properties p
LEFT JOIN files f ON f.owner_identifier = p.uuid
WHERE p.uuid IN (?, ?, ?)
GROUP BY p.uuid, p.property_id
```

**Result**: One row per property with its aggregates

### 3. Grouped Aggregation (By Belongs-To Dimension)
**Purpose**: Group records by a belongs-to relationship and aggregate
**Example**: "Group properties by bank and show count/total value per bank"

```json
{
  "type": "grouped_by_dimension",
  "groupBy": ["bank.uuid", "bank.name"],
  "aggregates": [
    {"function": "count", "name": "property_count"},
    {"function": "sum", "source": "reserve_price", "name": "total_value"}
  ]
}
```

**SQL Pattern**:
```sql
SELECT 
  b.uuid as bank_uuid,
  b.name as bank_name,
  COUNT(p.uuid) as property_count,
  SUM(p.reserve_price) as total_value
FROM properties p
LEFT JOIN banks b ON p.bank_uuid = b.uuid
WHERE p.uuid IN (?, ?, ?)
GROUP BY b.uuid, b.name
```

**Result**: One row per bank with aggregated property data

### 4. Multi-Dimensional Grouping
**Purpose**: Group by multiple dimensions (multiple belongs-to paths)
**Example**: "Group properties by state AND bank"

```json
{
  "type": "multi_dimensional",
  "groupBy": ["city.district.state.name", "bank.name"],
  "aggregates": [
    {"function": "count", "name": "property_count"},
    {"function": "sum", "source": "reserve_price", "name": "total_value"}
  ]
}
```

**SQL Pattern**:
```sql
SELECT 
  states.name as state_name,
  banks.name as bank_name,
  COUNT(p.uuid) as property_count,
  SUM(p.reserve_price) as total_value
FROM properties p
LEFT JOIN cities ON p.city_uuid = cities.uuid
LEFT JOIN districts ON cities.district_uuid = districts.uuid
LEFT JOIN states ON districts.state_uuid = states.uuid
LEFT JOIN banks ON p.bank_uuid = banks.uuid
WHERE p.uuid IN (?, ?, ?)
GROUP BY states.name, banks.name
```

**Result**: One row per state-bank combination

### 5. Related Model Aggregation
**Purpose**: Aggregate data from related models
**Example**: "For properties grouped by bank, also show the bank's total assets"

```json
{
  "type": "related_model_aggregate",
  "groupBy": ["bank.uuid", "bank.name"],
  "aggregates": [
    {"function": "count", "name": "property_count"},
    {"function": "sum", "source": "reserve_price", "name": "property_value"},
    {"function": "max", "source": "bank.total_assets", "name": "bank_assets"}
  ]
}
```

**SQL Pattern**:
```sql
SELECT 
  b.uuid as bank_uuid,
  b.name as bank_name,
  COUNT(p.uuid) as property_count,
  SUM(p.reserve_price) as property_value,
  MAX(b.total_assets) as bank_assets
FROM properties p
LEFT JOIN banks b ON p.bank_uuid = b.uuid
WHERE p.uuid IN (?, ?, ?)
GROUP BY b.uuid, b.name, b.total_assets
```

## Visualization Use Cases

### 1. Summary Cards/KPIs
- Type: Model-Level Aggregation
- Shows: Total count, sum, average
- Example: Dashboard header showing total portfolio value

### 2. Data Table with Counts
- Type: Per-Record Has-Many Aggregation  
- Shows: Each record with related counts
- Example: Properties table with document count column

### 3. Bar Charts/Pie Charts
- Type: Grouped Aggregation
- Shows: Distribution by category
- Example: Properties by bank, by status, by location

### 4. Heatmaps/Pivot Tables
- Type: Multi-Dimensional Grouping
- Shows: Two-dimensional analysis
- Example: Properties by state vs bank

### 5. Comparative Analysis
- Type: Related Model Aggregation
- Shows: Comparison across dimensions
- Example: Bank performance metrics

## Implementation Considerations

### 1. Query Performance
- Model-level: Single query, fast
- Per-record: N+1 query problem, consider batch loading
- Grouped: Single query with GROUP BY
- Multi-dimensional: Complex JOINs, may be slow

### 2. Result Structure
Different types need different result structures:
- Model-level: `{aggregates: {total: 100, avg: 50}}`
- Per-record: Add to each record: `{uuid: "...", file_count: 5}`
- Grouped: New result set: `[{bank_name: "...", count: 10}]`

### 3. Schema Design
We might need different schema approaches:

```json
// Option 1: Type-based
{
  "aggregateKey": true,
  "aggregateType": "grouped", // "model_summary", "per_record", "grouped"
  "groupBy": ["bank.name"],
  "aggregates": [...]
}

// Option 2: Purpose-based
{
  "aggregateKey": true,
  "purpose": "summary", // "summary", "dimension", "per_record"
  "config": {...}
}
```

## Revised Schema Proposal

```json
{
  "properties_summary": {
    "type": "object",
    "aggregateKey": true,
    "aggregateType": "model_summary",
    "aggregates": [
      {"function": "count", "name": "total_count"},
      {"function": "sum", "source": "reserve_price", "name": "total_value"}
    ]
  },
  
  "properties_with_file_counts": {
    "type": "object",
    "aggregateKey": true,
    "aggregateType": "per_record",
    "relationship": "files",
    "aggregates": [
      {"function": "count", "name": "file_count"}
    ]
  },
  
  "properties_by_bank": {
    "type": "object",
    "aggregateKey": true,
    "aggregateType": "grouped",
    "groupBy": ["bank.uuid", "bank.name"],
    "aggregates": [
      {"function": "count", "name": "property_count"},
      {"function": "sum", "source": "reserve_price", "name": "total_value"}
    ]
  }
}
```