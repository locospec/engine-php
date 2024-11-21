```php
// Single insert
$operation = InsertOperation::single('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Bulk insert
$operation = InsertOperation::bulk('users', [
    [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ],
    [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com'
    ]
]);
```
