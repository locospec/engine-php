// Array of conditions

```json
"filters": [
    {"attribute": "status", "op": "is", "value": "active"},
    {"attribute": "age", "op": "greater_than", "value": 18}
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
