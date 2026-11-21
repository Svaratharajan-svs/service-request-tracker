## Database Relationship Diagram

```text
+---------------------------+
| users                     |
+---------------------------+
| id (PK)                   |
| name                      |
| email                     |
| password                  |
+---------------------------+
            |
            | one user has many requests
            |
            v
+---------------------------+
| service_requests          |
+---------------------------+
| id (PK)                   |
| user_id (FK)              |
| title                     |
| description               |
| status                    |
| version                   |
+---------------------------+
            |
            | one request has many history records
            |
            v
+-----------------------------+
| request_status_histories    |
+-----------------------------+
| id (PK)                     |
| service_request_id (FK)     |
| changed_by_user_id (FK)     |
| from_status                 |
| to_status                   |
| version                     |
| created_at                  |
+-----------------------------+
```

### Relationships

| From | To | Type | Foreign Key |
|------|----|------|-------------|
| `users` | `service_requests` | One to many | `service_requests.user_id` |
| `service_requests` | `request_status_histories` | One to many | `request_status_histories.service_request_id` |
| `users` | `request_status_histories` | One to many | `request_status_histories.changed_by_user_id` |

### Tables

#### users

| Column | Key | Description |
|--------|-----|-------------|
| `id` | PK | Unique user ID |
| `name` | | User name |
| `email` | | User email |
| `password` | | Hashed password |

#### service_requests

| Column | Key | Description |
|--------|-----|-------------|
| `id` | PK | Unique request ID |
| `user_id` | FK | Owner of the request |
| `title` | | Request title |
| `description` | | Request details |
| `status` | | Current status |
| `version` | | Version number for the request |

#### request_status_histories

| Column | Key | Description |
|--------|-----|-------------|
| `id` | PK | Unique history record ID |
| `service_request_id` | FK | Related service request |
| `changed_by_user_id` | FK | User who changed the status |
| `from_status` | | Previous status |
| `to_status` | | New status |
| `version` | | Request version at the time of change |
| `created_at` | | When the change happened |