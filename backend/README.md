# Service Request Tracker — Backend

A RESTful backend application built with Laravel, PHP, PostgreSQL, and Laravel Sanctum.

The backend provides authenticated service request management, ownership-based authorization, controlled status transitions, append-only status history, and optimistic concurrency control.

## 1. Technology Stack

| Technology | Purpose |
|---|---|
| PHP | Backend programming language |
| Laravel | Backend application framework |
| PostgreSQL | Relational database |
| Laravel Sanctum | SPA authentication |
| Eloquent ORM | Database interaction |
| Laravel Form Requests | Input validation |
| Laravel API Resources | API response transformation |
| PHPUnit / Laravel Testing | Backend testing |
| Docker Compose | Local PostgreSQL environment |

## 2. Backend Features

### Authentication
- User login and logout.
- Retrieve authenticated user information.
- Laravel Sanctum session-based authentication.
- Protected API routes.

### Service Request Management
- Create service requests.
- List authenticated user's requests.
- View individual requests.
- Filter requests by status.
- Search requests by title and description.
- Paginated API responses.

### Status Workflow

The application supports the following workflow:

```text
OPEN
  |
  v
IN_PROGRESS
  |
  v
RESOLVED
```

Allowed transitions:

| Current Status | Next Status | Allowed |
|---|---|---|
| OPEN | IN_PROGRESS | Yes |
| IN_PROGRESS | RESOLVED | Yes |
| OPEN | RESOLVED | No |
| IN_PROGRESS | OPEN | No |
| RESOLVED | Any status | No |

RESOLVED is a terminal state.

### Status History
- Every successfully created request has an initial OPEN history record.
- Every successful status transition creates a history record.
- History records contain the previous status, new status, version, user, and timestamp.
- History is append-only at the application level.

### Optimistic Concurrency Control

Each service request contains an integer `version` column.

Clients must provide the version they expect when updating a status.

Example:

```json
{
  "status": "IN_PROGRESS",
  "expected_version": 1
}
```

The backend checks the expected version before updating the request.

The database update also uses a conditional comparison:

```sql
UPDATE service_requests
SET status = 'IN_PROGRESS',
    version = 2
WHERE id = 10
  AND version = 1
  AND status = 'OPEN';
```

If another operation has already modified the request, the update fails and the API returns HTTP 409 Conflict.

The status update and history insertion are executed within the same database transaction.

## 3. Architecture

The backend follows Laravel's conventional MVC structure with dedicated validation requests, API resources, enums, and Eloquent models.

```text
HTTP Request
     |
     v
API Routes
     |
     v
Sanctum Authentication
     |
     v
Form Request Validation
     |
     v
API Controller
     |
     v
Eloquent Models
     |
     v
Database Transaction
     |
     +---- Service Request Update
     |
     +---- Status History Insert
     |
     v
API Resource
     |
     v
JSON Response
```

### Main Components

| Component | Responsibility |
|---|---|
| Routes | Define API endpoints |
| Middleware | Authentication and request processing |
| Controllers | Coordinate application operations |
| Form Requests | Validate incoming data |
| Enums | Define allowed service request statuses |
| Models | Define database relationships and casts |
| API Resources | Transform models into JSON responses |
| Migrations | Define database schema |
| Factories and Seeders | Generate test and demo data |
| Feature Tests | Verify application behavior |

## 4. Project Structure

```text
backend/
├── app/
│   ├── Enums/
│   │   └── ServiceRequestStatus.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php
│   │   │       └── ServiceRequestController.php
│   │   │
│   │   ├── Requests/
│   │   │   ├── StoreServiceRequestRequest.php
│   │   │   └── UpdateServiceRequestStatusRequest.php
│   │   │
│   │   └── Resources/
│   │       ├── ServiceRequestResource.php
│   │       └── RequestStatusHistoryResource.php
│   │
│   └── Models/
│       ├── User.php
│       ├── ServiceRequest.php
│       └── RequestStatusHistory.php
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── routes/
│   └── api.php
│
├── tests/
│   ├── Feature/
│   └── Unit/
│
├── docs/
│   ├── ARCHITECTURE.md
│   ├── DATABASE_DESIGN.md
│   ├── API_DOCUMENTATION.md
│   ├── SECURITY.md
│   └── TECHNICAL_DECISIONS.md
│
├── .env.example
├── artisan
├── composer.json
└── README.md
```

## 5. Prerequisites

Install the following:

- PHP version supported by the Laravel version in `composer.json`.
- Composer 2.
- Docker Desktop with Docker Compose.
- PostgreSQL client (optional).
- Git.

Verify your installations:

```powershell
php -v
composer --version
docker --version
docker compose version
```

## 6. Local Installation

### Step 1: Start PostgreSQL

From the project root:

```powershell
docker compose up -d postgres
```

Check the database container:

```powershell
docker compose ps
```

### Step 2: Install PHP dependencies

From the backend directory:

```powershell
cd backend

composer install
```

### Step 3: Configure environment variables

Create your local environment file:

```powershell
Copy-Item .env.example .env
```

Configure the database connection in `.env`:

```env
APP_NAME="Service Request Tracker"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=service_tracker
DB_USERNAME=tracker_user
DB_PASSWORD=local_dev_password
```

For local SPA authentication, configure the relevant Sanctum and session settings:

```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
```

Keep the local frontend and backend hostnames consistent when using cookie-based authentication.

### Step 4: Generate application key

```powershell
php artisan key:generate
```

### Step 5: Run migrations

```powershell
php artisan migrate
```

### Step 6: Seed demo data

```powershell
php artisan db:seed
```

Check `DatabaseSeeder.php` for the actual seeded accounts and credentials.

### Step 7: Start Laravel

```powershell
php artisan serve
```

Backend URL:

```text
http://localhost:8000
```

## 7. API Endpoints

All service request endpoints require authentication.

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/login` | Authenticate user |
| GET | `/api/auth/me` | Get authenticated user |
| POST | `/api/auth/logout` | Log out user |

### Service Requests

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/requests` | Create request |
| GET | `/api/requests` | List own requests |
| GET | `/api/requests/{id}` | View request |
| PATCH | `/api/requests/{id}/status` | Update request status |
| GET | `/api/requests/{id}/history` | View status history |

### Filtering and Search

Example:

```http
GET /api/requests?status=OPEN&search=laptop&page=1&per_page=10
```

Supported parameters:

| Parameter | Description |
|---|---|
| status | Filter by request status |
| search | Search title and description |
| page | Pagination page |
| per_page | Number of records per page |

## 8. API Response Examples

### Create Request

```http
POST /api/requests
```

```json
{
  "title": "Laptop Repair",
  "description": "Laptop is not starting properly."
}
```

Expected HTTP status: `201 Created`.

### Update Request Status

```http
PATCH /api/requests/10/status
```

```json
{
  "status": "IN_PROGRESS",
  "expected_version": 1
}
```

### Version Conflict

HTTP status: `409 Conflict`.

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

### Validation Error

HTTP status: `422 Unprocessable Entity`.

Laravel returns validation errors for invalid request data.

## 9. Authentication

The application uses Laravel Sanctum for session-based SPA authentication.

The frontend should:

1. Request `/sanctum/csrf-cookie`.
2. Authenticate using `/api/auth/login`.
3. Send credentials with subsequent API requests.
4. Use the authenticated session for protected endpoints.
5. Call `/api/auth/logout` when logging out.

For browser-based requests, configure Axios or Fetch to send credentials.

Do not store session credentials or authentication tokens in browser local storage.

## 10. Testing

Run the complete backend test suite:

```powershell
php artisan test
```

Run a specific feature test:

```powershell
php artisan test tests/Feature/ServiceRequest/UpdateServiceRequestStatusTest.php
```

Run code formatting checks:

```powershell
vendor/bin/pint --test
```

Important test scenarios include:

- Authentication and authorization.
- Request creation and listing.
- Ownership protection.
- Status transition validation.
- Status history creation.
- Sequential history versions.
- Optimistic concurrency conflicts.
- Transaction rollback when history insertion fails.

PostgreSQL should also be used for database-specific integration and concurrency verification. SQLite in-memory tests do not reproduce PostgreSQL row-locking behavior.

## 11. Database Design

Main tables:

### users

Stores application users and authentication information.

### service_requests

| Column | Description |
|---|---|
| id | Primary key |
| user_id | Request owner |
| title | Request title |
| description | Request description |
| status | Current workflow status |
| version | Optimistic concurrency version |
| created_at | Creation timestamp |
| updated_at | Last update timestamp |

### request_status_histories

| Column | Description |
|---|---|
| id | Primary key |
| service_request_id | Related service request |
| changed_by_user_id | User who performed the change |
| from_status | Previous status |
| to_status | New status |
| version | Version after the change |
| created_at | History timestamp |

## 12. Security Considerations

- Authentication through Laravel Sanctum.
- Authorization through owner-scoped database queries.
- Server-side validation using Form Requests.
- User IDs are assigned from the authenticated user.
- Clients cannot directly control status history or version fields.
- Foreign keys enforce relational integrity.
- Transactions prevent partial status updates.
- Conditional updates protect against stale writes.
- Production secrets must be stored in environment variables.
- Production debugging must be disabled.

## 13. Production Configuration

Before deployment:

```env
APP_ENV=production
APP_DEBUG=false
```

Additionally:

- Use HTTPS.
- Enable secure cookies.
- Restrict CORS origins.
- Use a production database and credentials.
- Configure database backups.
- Configure logging and monitoring.
- Never commit `.env` files or credentials.

## 14. Useful Artisan Commands

```powershell
# List API routes
php artisan route:list --path=api

# Migration status
php artisan migrate:status

# Run migrations
php artisan migrate

# Clear application caches
php artisan optimize:clear

# Run tests
php artisan test

# Check code formatting
vendor/bin/pint --test
```

## 15. Engineering Focus

The main engineering concerns addressed by this backend are:

1. Secure multi-user resource ownership.
2. Correct workflow state transitions.
3. Consistent audit history.
4. Optimistic concurrency control.
5. Atomic database operations.
6. Testable and maintainable Laravel code.

The implementation prioritizes data integrity, predictable API behavior, and maintainability over a simple CRUD-only approach.