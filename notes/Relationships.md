### has_one

think of this like user has one phone, phone and user are models

user has id
phone has id, and user_id

"foreignKey",
"localKey"

-   `foreignKey` is name of current model and seperated by `_` and joined with `primaryKey` of current model
-   `localKey` is primary key of current model

foreignKey is something which is present in relatedModel, localKey is present in currentModel

```sql
SELECT * FROM phones WHERE user_id = <value of id> of user LIMIT 1

SELECT * FROM <related_model> WHERE <foreignKey> = <value of localKey in current model> LIMIT 1
```

### belongs_to

think of this like comment belongs to a post, comment and post are two models

post has id
comment has post_id

"foreignKey",
"ownerKey"

-   `foreignKey` is name of related model and separated by `_` and joined with `primaryKey` of related model
-   `ownerKey` is primary key of parent model

```sql
SELECT * FROM posts WHERE id = <value of post_id> of user LIMIT 1

SELECT * FROM <related_model> WHERE <ownerKey> = <value of foreignKey in current model>
```

### has_many

think of this like post has many comments, comment and post are two models

post has id
comment has id, post_id

"foreignKey",
"ownerKey"

-   `foreignKey` is name of current model and seperated by `_` and joined with `primaryKey` current model
-   `localKey` is primary key of current model

```sql
SELECT * FROM comments WHERE post_id = <value of id in current model>

SELECT * FROM <related_model> WHERE <foreignKey> = <value of localKey in current model>
```
