# Aggregate Patterns Design Document

## Overview

This document explores different aggregation patterns in Locospec and their implications for schema design, query execution, and result visualization.

## Core Problem

The current aggregate design assumes all aggregates work the same way, but they fundamentally don't. Different use cases require different query patterns, return different result structures, and serve different visualization purposes.

## Aggregation Patterns

### Pattern 1: Summary Aggregation (No GROUP BY)

**Use Case**: "Give me totals for these 10 properties"

- Total reserve price across all properties
- Average area of all properties
- Count of properties

**SQL Pattern**:

```sql
SELECT
  SUM(reserve_price) as total_value,
  AVG(area) as avg_area,
  COUNT(*) as property_count
FROM properties
WHERE uuid IN (?, ?, ?, ...) -- 10 property IDs from main query
HAVING SUM(reserve_price) > 1000000  -- Note: Cannot use alias 'total_value' in standard SQL
   AND COUNT(*) >= 5
```

**Result Structure**: Single row with aggregate values

```json
{
  "total_value": 4500000,
  "avg_area": 1200,
  "property_count": 10
}
```

**Model Declaration**:

```json
{
  "aggregates": {
    "portfolio_summary": {
      "calculations": [
        {
          "function": "sum",
          "source": "reserve_price",
          "name": "total_value"
        },
        {
          "function": "avg",
          "source": "area",
          "name": "avg_area"
        },
        {
          "function": "count",
          "name": "property_count"
        }
      ]
    }
}
```

**Visualization**: KPI cards, summary statistics header

### Pattern 2: Per-Record Aggregation (Has-Many Relationships)

**Use Case**: "For each of these 10 properties, tell me how many files it has"

- Each property → file count
- Each property → total bids
- Each property → sum of bid amounts

**SQL Pattern**:

```sql
SELECT
  p.uuid,
  p.property_id,
  COUNT(DISTINCT f.uuid) as file_count,
  COUNT(DISTINCT b.uuid) as bid_count,
  SUM(b.amount) as total_bid_amount
FROM properties p
LEFT JOIN files f ON f.owner_identifier = p.uuid
LEFT JOIN bids b ON b.property_uuid = p.uuid
WHERE p.uuid IN (?, ?, ?, ...)
GROUP BY p.uuid, p.property_id
HAVING COUNT(DISTINCT f.uuid) > 10  -- Must repeat aggregate function
   AND SUM(b.amount) > 500000
```

**Result Structure**: Original records enhanced with aggregate columns

```json
[
  {
    "uuid": "123",
    "property_id": "P001",
    "property_with_metrics": {
      "reserve_price": 300000,
      "file_count": 15,
      "bid_count": 3,
      "total_bid_amount": 450000
    }
  }
  // ... 9 more properties
]
```

**Model Declaration**:

```json
{
  "aggregates": {
    "property_with_metrics": {
      "calculations": [
        {
          "function": "count",
          "source": "files.*",
          "name": "file_count"
        },
        {
          "function": "count",
          "source": "event.bids.*",
          "name": "bid_count"
        },
        {
          "function": "sum",
          "source": "event.bids.amount",
          "name": "total_bid_amount"
        }
      ]
    }
  }
}
```

**Visualization**: Enhanced data tables with aggregate columns

### Pattern 3: Dimensional Aggregation (GROUP BY Belongs-To)

**Use Case**: "Group these 10 properties by bank and show totals"

- By bank → count, total value
- By state → count, average price
- By status → count, total area

**SQL Pattern**:

```sql
SELECT
  b.uuid as bank_uuid,
  b.name as bank_name,
  COUNT(p.uuid) as property_count,
  SUM(p.reserve_price) as total_value
FROM properties p
LEFT JOIN banks b ON p.bank_uuid = b.uuid
WHERE p.uuid IN (?, ?, ?, ...)
GROUP BY b.uuid, b.name
HAVING COUNT(p.uuid) >= 3  -- Only show banks with 3+ properties
   AND SUM(p.reserve_price) > 1000000
```

**Result Structure**: New result set grouped by dimension

```json
[
  {
    "bank_uuid": "bank1",
    "bank_name": "Bank of America",
    "property_count": 4,
    "total_value": 1800000
  },
  {
    "bank_uuid": "bank2",
    "bank_name": "Wells Fargo",
    "property_count": 6,
    "total_value": 2700000
  }
]
```

**Model Declaration**:

```json
{
  "aggregates": {
    "properties_by_bank": {
      "groupBy": ["bank.uuid", "bank.name"],
      "calculations": [
        {
          "function": "count",
          "source": "uuid",
          "name": "property_count"
        },
        {
          "function": "sum",
          "source": "reserve_price",
          "name": "total_value"
        }
      ]
    }
  }
}
```

**Visualization**: Bar charts, pie charts, distribution views

### Pattern 4: Multi-Dimensional Aggregation (Multiple GROUP BYs)

**Use Case**: "Show me property distribution by state AND bank"

- State × Bank matrix
- Status × Asset Type matrix
- Location × Time period matrix

**SQL Pattern**:

```sql
SELECT
  s.uuid as state_uuid,
  s.name as state_name,
  b.uuid as bank_uuid,
  b.name as bank_name,
  COUNT(p.uuid) as property_count,
  SUM(p.reserve_price) as total_value
FROM properties p
LEFT JOIN cities c ON p.city_uuid = c.uuid
LEFT JOIN districts d ON c.district_uuid = d.uuid
LEFT JOIN states s ON d.state_uuid = s.uuid
LEFT JOIN banks b ON p.bank_uuid = b.uuid
WHERE p.uuid IN (?, ?, ?, ...)
GROUP BY s.uuid, s.name, b.uuid, b.name
HAVING COUNT(p.uuid) > 0  -- Exclude empty combinations
   AND SUM(p.reserve_price) > 500000
ORDER BY s.name ASC, SUM(p.reserve_price) DESC  -- Sort by state, then by total value
```

**Result Structure**: Cartesian product of dimensions

```json
[
  {
    "state_uuid": "state-123",
    "state_name": "California",
    "bank_uuid": "bank-456",
    "bank_name": "Bank of America",
    "property_count": 2,
    "total_value": 900000
  },
  {
    "state_uuid": "state-123",
    "state_name": "California",
    "bank_uuid": "bank-789",
    "bank_name": "Wells Fargo",
    "property_count": 3,
    "total_value": 1350000
  },
  {
    "state_uuid": "state-456",
    "state_name": "Texas",
    "bank_uuid": "bank-456",
    "bank_name": "Bank of America",
    "property_count": 2,
    "total_value": 900000
  }
]
```

**Model Declaration**:

```json
{
  "aggregates": {
    "property_matrix": {
      "groupBy": [
        "city.district.state.uuid",
        "city.district.state.name",
        "bank.uuid",
        "bank.name"
      ],
      "calculations": [
        {
          "function": "count",
          "source": "uuid",
          "name": "property_count"
        },
        {
          "function": "sum",
          "source": "reserve_price",
          "name": "total_value"
        }
      ]
    }
  }
}
```

**Visualization**: Heatmaps, pivot tables, cross-tabs
