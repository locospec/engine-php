# Chapter 3: Hierarchical Taxonomy of User Intents in Intent-Based Data Retrieval

## Abstract

This chapter formalizes a hierarchical taxonomy for user intents in data retrieval, grounded in the language-agnostic JSON specification outlined in the document. By categorizing intents into Projections, Filtering, and Sorting—each with variants based on relationship types (Direct, Associative for low-cardinality BelongsTo/HasOne relations, and Multiplicative for high-cardinality HasMany relations)—we enable a modular algorithm for query plan generation. This taxonomy supports diverse use cases, including tabular displays, faceted navigation with count-based dropdowns, interactive dashboards, and aggregations. We emphasize PhD-level rigor through algorithmic pseudocode, decision heuristics, and proposed experimental protocols for testability and publishability, such as latency benchmarks on synthetic datasets and ablation studies on strategy efficacy.

The taxonomy assumes single-query strategies per category for simplicity, with composite intents handled via state machines (sequenced queries + merge/extract steps). Textual search integrates as a fuzzy filtering variant (e.g., LIKE/full-text operators), reducing redundancy while preserving expressiveness for exploratory faceted interfaces.

## 3.1 Introduction to Intent Categorization

User intents, expressed via JSON payloads, encapsulate requirements for data fetching, filtering, and ordering across models and relationships. Drawing from the schema (e.g., properties with BelongsTo locality, HasMany events.bids), we classify intents by operation type and relation cardinality:

- **Direct**: Primary model only (no relations; efficient single-table operations).
- **Associative**: Low-cardinality relations (BelongsTo/HasOne, e.g., property-to-locality; JOIN-friendly for scalar enrichment without duplication).
- **Multiplicative**: High-cardinality relations (HasMany, e.g., property-to-bids; subquery/aggregate-focused to mitigate row explosion).

This classification informs an algorithm that parses paths (dot notation), consults schema metadata (e.g., cardinality), and selects strategies. For publishability, we propose tests on a mock relational DB (e.g., PostgreSQL with 100K properties, randomized 1:1 vs. 1:N relations), measuring metrics like query latency, result accuracy (no dupes), and faceted recall (e.g., correct dropdown counts).

Pseudocode for Intent Parser:

```
FUNCTION classify_intent(payload_json):
  operations = extract_operations(payload_json)  # e.g., attributes → projections, filters → filtering
  FOR op IN operations:
    path = op.path
    relation_type = schema.get_relation_type(path)  # Direct, Associative, or Multiplicative
    strategy = select_strategy(relation_type, op.depth)  # e.g., JOIN if Associative and depth <= 3
    plans.append(generate_single_query(op, strategy))
  IF multi_op: return sequence_plans_with_merges(plans)  # State machine for composites
```

## 3.2 Projections Taxonomy

Projections define what data to select, supporting table columns (flattened scalars), dashboard widgets (aggregates), and faceted summaries (grouped counts). Strategies prioritize JOINs for Associative (enrichment) and subqueries for Multiplicative (aggregation to avoid multiplicity issues in tabular outputs).

| Category                                        | Description                                                                | Examples from Payload/Schema                        | Execution Implications (Single Strategy)               | Use Cases in Dashboards/Tables/Facets                                     |
| ----------------------------------------------- | -------------------------------------------------------------------------- | --------------------------------------------------- | ------------------------------------------------------ | ------------------------------------------------------------------------- |
| **Direct Projections**                          | Fetch raw or aggregated fields from primary model.                         | `"uuid"`, `"title"`, `"count(*):total_properties"`. | Single-table SELECT with optional aggregates/GROUP BY. | Basic table columns; dashboard totals or footers.                         |
| **Associative Projections (JOIN-Based)**        | Fetch scalar attributes from low-cardinality relations (parents/siblings). | `"locality.name"`, `"bank_branch.bank.name"`.       | Single query with JOINs (INNER/LEFT).                  | Enriched table rows; faceted dropdown labels (e.g., state names).         |
| **Associative Projections (Sequential-Based)**  | Fetch scalar attributes from deep/optimized low-cardinality relations.     | Deep: `"locality.city.district.state.name"`.        | Sequential: Filter mains, batch-fetch via IN.          | Optimized dashboards with transitive chains; avoids bloated JOINs.        |
| **Multiplicative Projections (Subquery-Based)** | Aggregate across high-cardinality relations (children; no raw fetches).    | `"count(events.bids.uuid):bid_count"`.              | Single query with subqueries for aggregates.           | Aggregated child metrics in tables; dashboard widgets (e.g., bid counts). |

### Algorithmic Considerations

For Associative variants, heuristics trigger Sequential if depth > 3 or table sizes exceed thresholds (e.g., >1M rows), testable via synthetic chains (e.g., vary depths 1-5, plot latency curves). Multiplicative restricts to aggregates, enforcing no raw projections to prevent Cartesian products—validated in tests by asserting output structures (e.g., scalar bid_count per property).

## 3.3 Filtering Taxonomy

Filtering applies constraints (exact/fuzzy, pre/post-aggregation), enabling faceted dropdowns (e.g., "City: Mumbai (42)" via grouped counts) and dashboard refinements. Textual search embeds as LIKE/full-text, with Associative using JOINed indexes and Multiplicative EXISTS for child matches.

| Category                                      | Description                                                                      | Examples from Payload/Schema                                                                                                  | Execution Implications (Single Strategy)       | Use Cases in Dashboards/Tables/Facets                            |
| --------------------------------------------- | -------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------- | ---------------------------------------------------------------- |
| **Direct Filtering**                          | Apply exact/fuzzy constraints on primary model attributes (incl. text search).   | `{"path": "status", "op": "=", "value": "active"}`, `{"path": "title", "op": "like", "value": "luxury%"}`.                    | Single-table WHERE; index/full-text optimized. | Basic table filters; quick dashboard toggles or search bars.     |
| **Associative Filtering (JOIN-Based)**        | Apply constraints on low-cardinality relations (incl. text search on parents).   | `{"path": "locality.city.name", "op": "=", "value": "Mumbai"}`, `{"path": "bank.name", "op": "like", "value": "State%"}`.     | Single query with JOINs in WHERE.              | Faceted dropdowns from associations; refined enriched views.     |
| **Multiplicative Filtering (Subquery-Based)** | Apply constraints on high-cardinality relations (incl. text search on children). | `{"path": "events.bids.amount", "op": ">", "value": 1000}`, `{"path": "events.description", "op": "like", "value": "high%"}`. | Single query with EXISTS/IN subquery in WHERE. | Advanced child-based faceting; dashboard filters avoiding dupes. |

### Algorithmic Considerations

Post-aggregation (HAVING) integrates for faceted counts (e.g., filter bid_count > 5). Tests: Simulate 50K intents with mixed textual/exact ops, measure selectivity (e.g., index hit rates) and precision in faceted outputs (e.g., correct counts post-filter).

## 3.4 Sorting Taxonomy

Sorting orders results for ranked tables/dashboards, often composed with projections (e.g., sort by aggregated bid_count). Strategies ensure tie-breaking and integration with faceted views (e.g., sorted dropdown options).

| Category                                    | Description                                                              | Examples from Payload/Schema                         | Execution Implications (Single Strategy)         | Use Cases in Dashboards/Tables/Facets                              |
| ------------------------------------------- | ------------------------------------------------------------------------ | ---------------------------------------------------- | ------------------------------------------------ | ------------------------------------------------------------------ |
| **Direct Sorting**                          | Prioritize by primary model attributes/aggregates.                       | `{"path": "reserve_price", "dir": "desc"}`.          | ORDER BY on main table; single query.            | Sorted basic tables; ranked simple dashboards.                     |
| **Associative Sorting (JOIN-Based)**        | Prioritize by low-cardinality relation attributes/aggregates.            | `{"path": "locality.name", "dir": "asc"}`.           | ORDER BY with JOINs; single query.               | Sorted enriched tables; relational-ranked faceted views.           |
| **Multiplicative Sorting (Subquery-Based)** | Prioritize by high-cardinality relation aggregates (e.g., child counts). | `{"path": "events.bids.uuid:count", "dir": "desc"}`. | ORDER BY with subquery aggregates; single query. | Ranked tables by child metrics; aggregated dashboard leaderboards. |

### Algorithmic Considerations

For stability, add secondary keys (e.g., uuid asc). Experiments: On 20K-row datasets, ablate JOIN vs. subquery, quantify sort time deltas in dashboard simulations (e.g., ranked faceted lists).

## 3.5 Experimental Protocols for Validation

To ensure publishability, we outline tests:

- **Synthetic Dataset Generation**: 100K properties with randomized relations (e.g., Associative depth 1-5, Multiplicative fanout 1-100); inject keywords for textual filtering.
- **Unit Tests**: Per category, 200 intents; assert strategy match (e.g., no JOINs in Direct) and output fidelity (e.g., no dupes).
- **Benchmarks**: Latency/throughput on composites (e.g., faceted dashboard: Associative Filtering + Multiplicative Projection); vary DB sizes, plot curves.
- **Ablation Studies**: Remove Sequential variants, measure overhead in deep chains; evaluate faceted accuracy (recall/precision on dropdown counts).
- **User Simulation**: Mock dashboard interactions (e.g., filter + sort + search), time end-to-end responses for real-world viability.

This taxonomy lays the groundwork for Chapter 4: Query Plan Generation Algorithm, enabling efficient, testable data access layers.
