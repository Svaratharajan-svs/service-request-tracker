# Technical Decisions

## Service Request Tracker

**Document:** `DECISIONS.md`\
**Project:** Service Request Tracker\
**Status:** Implemented decisions and documented implementation
constraints\
**Backend:** Laravel 13, PHP 8.4\
**Database:** PostgreSQL\
**Authentication:** Laravel Sanctum\
**Frontend:** Next.js (separate application)

------------------------------------------------------------------------

## 1. Purpose

This document records the main technical decisions for the Service
Request Tracker, including the reasoning behind them and the
consequences for implementation and maintenance.

The system allows authenticated employees to create and manage their own
service requests. Each request has a priority and a controlled status
lifecycle. Status changes are recorded in an append-only history and
protected against stale concurrent updates.

## 2. Architecture

### Decision

Use a separate Laravel API backend and Next.js frontend.

-   Laravel owns business rules, validation, authentication,
    persistence, and API responses.
-   Next.js owns the user interface and communicates with Laravel
    through HTTP APIs.
-   PostgreSQL is the persistent relational database.
-   Docker Compose provides a repeatable local environment for the
    application services.

### Rationale

Separating the frontend and backend keeps UI concerns independent from
domain and persistence logic. It also allows the API to be tested
independently and supports replacing or extending the frontend without
rewriting backend rules.

### Consequences

-   Frontend and backend must agree on API contracts and response
    shapes.
-   Browser requests using Sanctum cookie authentication must include
    credentials and follow the configured CSRF flow.
-   Environment-specific API URLs and CORS settings must be configured
    consistently.

## 3. Database and Ownership Model

### Decision

Use PostgreSQL and a `service_requests` table with an explicit
`created_by` foreign key referencing the employee who created the
request.

Core request fields:

  Field           Purpose
  --------------- -----------------------------------------
  `id`            Request identifier
  `created_by`    Owning employee (`users` foreign key)
  `title`         Request title, 3--100 characters
  `description`   Request details, 10--500 characters
  `priority`      `LOW`, `MEDIUM`, or `HIGH`
  `status`        `OPEN`, `IN_PROGRESS`, or `RESOLVED`
  `version`       Integer used for optimistic concurrency
  `created_at`    Creation timestamp
  `updated_at`    Last update timestamp

The `created_by` column is the ownership key. The Eloquent relationship
is named `creator` on `ServiceRequest`, and `serviceRequests` on `User`.

### Rationale

An explicit ownership column makes authorization queries clear and
allows the database to enforce referential integrity.

### Consequences

-   Queries that return employee requests must be scoped through the
    authenticated user's `serviceRequests()` relationship.
-   Do not accept `created_by` from client input. Set it through the
    authenticated user's relationship.
-   Use `creator` when eager-loading the request creator. Do not use the
    old `user` relationship name.
-   Keep history attribution separate from ownership:
    `changed_by_user_id` records who performed a particular status
    change.

## 4. Priority and Status Representation

### Decision

Represent priority and status as PHP backed enums.

**Priority values** - `LOW` - `MEDIUM` - `HIGH`

**Status values** - `OPEN` - `IN_PROGRESS` - `RESOLVED`

A newly created request starts with status `OPEN` and version `1`.

### Rationale

Enums centralize the permitted values and reduce inconsistent string
handling across validation, models, and business logic.

### Consequences

-   Validate incoming status and priority values against their
    respective enums.
-   Serialize enum values as their string values in API resources.
-   The assignment defines the status default as `OPEN`. If the
    assignment does not specify a priority default, do not treat
    `MEDIUM` as a requirement unless it has been explicitly adopted as a
    project decision.

## 5. Status Lifecycle

### Decision

Allow only the following status transitions:

``` text
OPEN
  |
  v
IN_PROGRESS
  |
  v
RESOLVED
```

`RESOLVED` is terminal. A resolved request cannot transition to another
status.

### Rationale

A controlled lifecycle prevents unsupported state changes and makes the
request workflow predictable.

### Consequences

-   Keep transition rules in the `ServiceRequestStatus` enum or a
    dedicated domain service, rather than duplicating them across
    controllers.
-   Reject invalid transitions with a validation response.
-   Do not permit clients to update status through the request-creation
    endpoint.

## 6. Status History

### Decision

Store every successful status change in a separate
`request_status_histories` table. Treat history records as append-only
application data.

A history record includes:

-   `service_request_id`
-   `changed_by_user_id`
-   `from_status`
-   `to_status`
-   `version`
-   `created_at`

The initial creation event records `from_status = null`,
`to_status = OPEN`, and `version = 1`.

### Rationale

A separate history table provides an audit trail and makes it possible
to reconstruct the sequence of status changes.

### Consequences

-   Create the request and its initial history record in the same
    database transaction.
-   Create each later history record in the same transaction as its
    status update.
-   Do not update or delete historical events as part of normal request
    operations.
-   Keep `changed_by_user_id`; it is not the request ownership column.
-   Return history ordered by version.

## 7. Optimistic Concurrency Control

### Decision

Use an integer `version` column and require clients to submit
`expected_version` when changing a request's status.

For a successful update:

1.  Load the request within the authenticated employee's ownership
    scope.
2.  Compare the stored version with `expected_version`.
3.  Validate the requested status transition.
4.  Update only when the stored version and current status still match.
5.  Increment the version by one.
6.  Insert the corresponding history record in the same transaction.

If the version does not match, return HTTP `409 Conflict` with an error
code such as `VERSION_CONFLICT` and the current version.

### Rationale

Optimistic concurrency prevents a client with stale data from silently
overwriting a newer update.

### Consequences

-   The frontend must use the latest version returned by the API.
-   On HTTP 409, the frontend should explain that the request changed
    elsewhere and reload the authoritative request state.
-   Do not automatically retry the old update with the stale version.
-   The conditional update should include the request ID, expected
    version, and expected current status.

## 8. Transactions and Atomicity

### Decision

Use database transactions for operations that modify a request and its
history together.

### Rationale

The request state and its audit history must not become inconsistent.
Either both writes succeed or neither write is committed.

### Consequences

-   Wrap request creation plus initial history insertion in one
    transaction.
-   Wrap status update plus history insertion in one transaction.
-   Allow exceptions during history insertion to roll back the
    associated request update.
-   PostgreSQL row locking through `lockForUpdate()` is used when
    retrieving a request for a status update. The conditional version
    update remains an important concurrency safeguard.

## 9. Authentication and Authorization

### Decision

Use Laravel Sanctum for API authentication. Protect request endpoints
with the `auth:sanctum` middleware.

Use cookie-based Sanctum authentication for the first-party browser
frontend, with stateful API middleware, CSRF protection, and
credentialed CORS configured for the frontend origin.

### Authorization rules

-   Employees can list only their own requests.
-   Employees can view only their own requests.
-   Employees can update the status only of their own requests.
-   Employees can view history only for their own requests.
-   The server derives the owner from the authenticated user.

### Rationale

Authentication establishes the user's identity; ownership-scoped queries
enforce which records that identity may access. This reduces the risk of
insecure direct object reference (IDOR) vulnerabilities.

### Consequences

-   Apply ownership scoping in the database query, not only in the
    frontend.
-   Return not-found behavior for records outside the user's scope.
-   Never rely on a client-supplied owner ID.
-   For browser authentication, request the CSRF cookie before login and
    send cookies/credentials on subsequent requests.
-   Keep frontend origin, Sanctum stateful domains, and CORS
    configuration aligned.

## 10. Validation and API Contracts

### Decision

Use dedicated Laravel Form Request classes for write operations and
Laravel validation for list filters.

-   `StoreServiceRequestRequest` validates title, description, and
    priority.
-   `UpdateServiceRequestStatusRequest` validates status and
    `expected_version`.
-   The server controls `created_by`, initial status, and initial
    version.

### Rationale

Dedicated request classes keep input validation separate from controller
orchestration and make the accepted API contract explicit.

### Consequences

-   Reject invalid enum values and out-of-range text lengths.
-   Do not mass-assign untrusted ownership or version fields from the
    request.
-   Use API resources to control which fields are exposed to clients.
-   Keep API error responses consistent and meaningful.

## 11. Listing, Filtering, Searching, and Pagination

### Decision

The request-list endpoint supports:

-   `status` filter
-   `priority` filter
-   `search` across title and description
-   `per_page` pagination, bounded from 1 to 100

All list queries are scoped to the authenticated employee.

Use PostgreSQL `ILIKE` for case-insensitive matching and `LIKE` for
database drivers that do not support `ILIKE`.

### Rationale

Server-side filtering and pagination avoid transferring unnecessary
records and ensure filtering is applied within the user's authorization
scope.

### Consequences

-   Validate filter values before applying them.
-   Allow filters to be combined.
-   Keep pagination limits bounded.
-   Add or adjust indexes based on measured query patterns and
    production data volume.

Example:

``` http
GET /api/requests?status=IN_PROGRESS&priority=HIGH&search=laptop&per_page=10
```

## 12. API Endpoint Responsibilities

  ------------------------------------------------------------------------------
  Method                  Endpoint                       Responsibility
  ----------------------- ------------------------------ -----------------------
  `POST`                  `/api/auth/login`              Authenticate employee

  `GET`                   `/api/auth/me`                 Return authenticated
                                                         employee

  `POST`                  `/api/auth/logout`             End authenticated
                                                         session

  `POST`                  `/api/requests`                Create a request

  `GET`                   `/api/requests`                List the employee's
                                                         requests

  `GET`                   `/api/requests/{id}`           View an owned request

  `PATCH`                 `/api/requests/{id}/status`    Perform a
                                                         version-checked status
                                                         transition

  `GET`                   `/api/requests/{id}/history`   View status history for
                                                         an owned request
  ------------------------------------------------------------------------------

## 13. Testing Strategy

### Decision

Use Laravel feature tests to verify behavior through HTTP endpoints and
the database.

The test suite should cover:

-   Authentication requirements.
-   Request creation and validation.
-   Ownership isolation between employees.
-   Listing, status filtering, priority filtering, searching, and
    pagination.
-   Request details and history access.
-   Allowed and disallowed status transitions.
-   Sequential history versions.
-   Stale-version conflict responses.
-   Transaction rollback when history insertion fails.

### Rationale

Feature tests verify the integrated behavior of routes, middleware,
validation, controllers, Eloquent relationships, and database
operations.

### Consequences

-   Use factories to create test users and requests.
-   Use `RefreshDatabase` to isolate test data.
-   Run the suite against PostgreSQL when verifying PostgreSQL-specific
    behavior and locking.
-   SQLite tests are useful for fast feedback, but SQLite does not
    provide PostgreSQL-equivalent row-locking semantics. Do not treat
    SQLite-only tests as proof of PostgreSQL concurrency behavior.

## 14. Local Development and Docker

### Decision

Use Docker Compose to run PostgreSQL and, when desired, the backend and
frontend services.

The backend container connects to the Compose database service using the
service hostname (for example, `postgres`), not `localhost`.

### Rationale

A consistent containerized environment reduces differences between
developer machines and simplifies application startup.

### Consequences

-   Keep local and container environment variables distinct and correct
    for each execution mode.
-   `env_file` injects variables into a container; it does not
    automatically copy the host `.env` file into the container
    filesystem.
-   Rebuild the affected service after changing build-time frontend
    configuration or Docker build inputs.
-   Do not commit real credentials or production secrets.

## 15. Security Considerations

-   Enforce authentication on protected endpoints.
-   Scope every request lookup to the authenticated employee.
-   Do not accept ownership, status, or version fields from
    create-request input.
-   Validate all user-controlled filter and update parameters.
-   Use CSRF protection for cookie-authenticated browser requests.
-   Keep secrets out of source control.
-   Avoid exposing internal exception details in API responses.
-   Keep history writes coupled transactionally to the state changes
    they describe.

## 16. Known Constraints and Follow-up Decisions

The following items should be reviewed if the application grows beyond
the assignment scope:

1.  **Priority default:** The request specification defines the allowed
    priority values but may not define a default. Confirm the required
    behavior before enforcing a database or application default.
2.  **Concurrency verification:** Run concurrency-focused tests against
    PostgreSQL, not only SQLite.
3.  **Search performance:** For larger datasets, evaluate PostgreSQL
    indexes or full-text search based on actual query plans.
4.  **Authorization policy layer:** If authorization rules expand,
    consider moving them into Laravel policies while retaining
    ownership-scoped queries.
5.  **Audit immutability:** If stronger audit guarantees are required,
    consider database-level restrictions or a dedicated audit mechanism
    in addition to application conventions.

------------------------------------------------------------------------

## Decision Summary

  Area                     Decision
  ------------------------ ------------------------------------------------------------
  Application structure    Separate Laravel API and Next.js frontend
  Database                 PostgreSQL
  Authentication           Laravel Sanctum
  Ownership                `created_by` foreign key
  Creator relationship     `creator`
  Priority                 Backed enum: `LOW`, `MEDIUM`, `HIGH`
  Status                   Backed enum with controlled lifecycle
  Initial status/version   `OPEN` / `1`
  History                  Append-only status history
  Concurrency              Optimistic version checking
  Conflict response        HTTP `409`
  Atomic writes            Database transactions
  List endpoint            Status, priority, search, pagination
  Testing                  Laravel feature tests, PostgreSQL concurrency verification
  Local environment        Docker Compose
