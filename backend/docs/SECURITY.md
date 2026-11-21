# Security

This document describes the security controls used in the API and how each one protects the application.

## 1. Sanctum Authentication

All protected endpoints require authentication through Laravel Sanctum.

- Users log in through `POST /api/auth/login` and receive a token.
- The client sends the token in the `Authorization: Bearer <token>` header on every protected request.
- Protected routes are wrapped in the `auth:sanctum` middleware, so unauthenticated requests are rejected with HTTP 401 before reaching any controller.
- `POST /api/auth/logout` revokes the current token so it can no longer be used.

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::apiResource('requests', ServiceRequestController::class);
});
```

## 2. Password Hashing

Passwords are never stored in plain text.

- Passwords are hashed with Laravel's `Hash` facade (bcrypt or Argon2, depending on configuration).
- The `password` column is hidden from serialization through the model's `$hidden` property, so it never appears in API responses.
- Login compares the submitted password against the stored hash with `Hash::check()`.

```php
protected $hidden = ['password', 'remember_token'];

protected function casts(): array
{
    return ['password' => 'hashed'];
}
```

## 3. Ownership-Based Data Access

A user can only read or modify their own service requests.

- Queries are always scoped through the authenticated user's relationship, never through a bare ID lookup.
- `GET /api/requests` returns only the requests that belong to the current user.
- A request that belongs to another user is treated as if it does not exist.

```php
$requests = $request->user()->serviceRequests()->latest()->get();
```

## 4. Input Validation

All input is validated before it reaches the controller logic.

- Each write endpoint uses a dedicated Form Request class.
- Validation rules check type, length, and allowed values (for example, `status` must be one of the permitted status values).
- Invalid input is rejected with HTTP 422 and a list of field errors.
- Controllers use `$request->validated()` so only validated fields are used.

```php
public function rules(): array
{
    return [
        'status' => ['required', 'string', Rule::in(['OPEN', 'IN_PROGRESS', 'COMPLETED'])],
        'expected_version' => ['required', 'integer', 'min:1'],
    ];
}
```

## 5. Mass-Assignment Protection

Models define exactly which attributes can be set in bulk.

- Each model declares an explicit `$fillable` list.
- Sensitive fields such as `user_id`, `version`, and `id` are not fillable, so a client cannot set them by adding extra JSON fields.
- Ownership (`user_id`) is always taken from the authenticated user, never from the request body.

```php
class ServiceRequest extends Model
{
    protected $fillable = ['title', 'description'];
}
```

```php
$serviceRequest = $request->user()->serviceRequests()->create($request->validated());
```

## 6. CSRF Protection for Stateful Authentication

CSRF protection depends on how the client authenticates.

- **Token-based clients (mobile apps, external services):** Bearer tokens are not sent automatically by the browser, so CSRF attacks do not apply.
- **Browser-based SPA clients using cookie sessions:** Sanctum's stateful mode is used. The client first calls `GET /sanctum/csrf-cookie` to obtain the CSRF cookie, then sends the `X-XSRF-TOKEN` header on state-changing requests. Laravel verifies it through the CSRF middleware.
- Only trusted frontend domains are listed in `SANCTUM_STATEFUL_DOMAINS`, and CORS is restricted to those origins.

## 7. Database Foreign-Key Constraints

Relationships are enforced by the database, not only by application code.

- `service_requests.user_id` references `users.id`.
- `request_status_histories.service_request_id` references `service_requests.id`.
- `request_status_histories.changed_by_user_id` references `users.id`.
- Foreign keys prevent orphaned records and invalid references, even if a bug or a direct database write bypasses the application layer.

```php
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
```

## 8. Optimistic Concurrency Protection

Status updates use a `version` column to prevent lost updates when two operations modify the same request.

- The client sends `expected_version` with every status update.
- The update only succeeds if the stored version matches `expected_version`.
- On success, the version is incremented, and the status change plus its history record are written in one database transaction.
- On mismatch, the API returns HTTP 409 with the code `VERSION_CONFLICT` and the current version.

```php
$updated = ServiceRequest::where('id', $serviceRequest->id)
    ->where('version', $expectedVersion)
    ->update([
        'status'  => $newStatus,
        'version' => $expectedVersion + 1,
    ]);

if ($updated === 0) {
    // Return 409 VERSION_CONFLICT
}
```

## 9. Safe Exception Handling

Errors are handled so that internal details are never exposed to clients.

- `APP_DEBUG` is set to `false` in production, so stack traces and queries are never returned.
- Exceptions are converted to consistent JSON error responses with a generic message.
- Unexpected errors return HTTP 500 with a safe message, while the full details are written to the server logs.
- Database errors, file paths, and SQL are never included in API responses.
- Multi-step writes run inside a transaction that rolls back on failure, so partial updates are never saved.

## 10. Environment-Variable-Based Secrets

Secrets are kept out of the source code.

- Database credentials, `APP_KEY`, and other secrets are stored in the `.env` file.
- The `.env` file is listed in `.gitignore` and is never committed.
- A `.env.example` file documents the required variables with placeholder values only.
- Code reads configuration through `config()` files, which read from `env()`.
- Production secrets are set on the server or in the deployment platform, and can be rotated without code changes.

## Preventing IDOR (Insecure Direct Object Reference)

IDOR happens when an application uses an ID from the request to load a record without checking that the current user is allowed to access it. For example, if a user changes `/api/requests/10` to `/api/requests/11` and receives someone else's data, the application has an IDOR vulnerability.

### Vulnerable Pattern

```php
// Unsafe: any authenticated user can load any request by ID
$serviceRequest = ServiceRequest::findOrFail($id);
```

### Safe Pattern

All lookups go through the authenticated user's relationship, so the ownership check is part of the query itself:

```php
$serviceRequest = $user->serviceRequests()
    ->findOrFail($id);
```

This generates a query equivalent to:

```sql
SELECT * FROM service_requests
WHERE user_id = :current_user_id
  AND id = :id
LIMIT 1;
```

### Result

| Scenario | Outcome |
|----------|---------|
| User requests their own record | Record is returned |
| User requests another user's record | `findOrFail` throws `ModelNotFoundException`, returned as HTTP 404 |
| User requests a record that does not exist | HTTP 404 (same response) |

Returning 404 in both cases means an attacker cannot tell whether a record ID exists but belongs to someone else.

### Where This Applies

The same scoped lookup is used on every endpoint that takes a request ID:

- `GET /api/requests/{id}`
- `PATCH /api/requests/{id}/status`
- `GET /api/requests/{id}/history`

The history endpoint loads the parent request through the user's relationship first, and only then reads its history records:

```php
$serviceRequest = $request->user()->serviceRequests()->findOrFail($id);
$history = $serviceRequest->statusHistories()->latest()->get();
```