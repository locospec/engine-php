// Array of conditions

```json
"filters": [
    {"attribute": "status", "op": "eq", "value": "active"},
    {"attribute": "age", "op": "gt", "value": 18}
]
```

// Filter group

```json
"filters": {
    "op": "and",
    "conditions": [...]
}
```

// Simple key-value

```json
"filters": {
    "status": "active",
    "age": 18
}
```
