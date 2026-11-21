# API Documentation

## Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/auth/login` | Login |
| GET | `/api/auth/me` | Current user |
| POST | `/api/auth/logout` | Logout |
| POST | `/api/requests` | Create request |
| GET | `/api/requests` | List own requests |
| GET | `/api/requests/{id}` | View request |
| PATCH | `/api/requests/{id}/status` | Update status |
| GET | `/api/requests/{id}/history` | View history |

## Update Request Status

Updates the status of a request. The endpoint uses optimistic locking: the client sends the version it last saw, and the update is rejected if the request has changed since then.

**Endpoint**

```text
PATCH /api/requests/{id}/status
```

### Request Body

```json
{
  "status": "IN_PROGRESS",
  "expected_version": 1
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `status` | string | Yes | The new status for the request |
| `expected_version` | integer | Yes | The version of the request the client last read |

### Successful Response

**HTTP 200**

```json
{
  "success": true,
  "message": "Request status updated successfully.",
  "data": {
    "id": 10,
    "status": "IN_PROGRESS",
    "version": 2
  }
}
```

| Field | Description |
|-------|-------------|
| `data.id` | ID of the updated request |
| `data.status` | The new status |
| `data.version` | The new version, incremented after the update |

### Conflict Response

**HTTP 409**

Returned when `expected_version` does not match the current version of the request, meaning another operation modified it first.

```json
{
  "success": false,
  "message": "This request was modified by another operation.",
  "error": {
    "code": "VERSION_CONFLICT",
    "current_version": 2
  }
}
```

| Field | Description |
|-------|-------------|
| `error.code` | Always `VERSION_CONFLICT` for this error |
| `error.current_version` | The current version stored on the server |

### Handling a Conflict

1. Read the request again with `GET /api/requests/{id}`.
2. Check whether the status change is still needed.
3. Retry the update using the latest version as `expected_version`.