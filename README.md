# Service Request Tracker

A full-stack Service Request Tracker application built with Laravel, PHP, Next.js, and PostgreSQL.

The application allows authenticated employees to create and manage their service requests, track workflow progress, and view a complete status history.

The project focuses on backend reliability, secure resource ownership, database integrity, and concurrency control.

## 1. Project Overview

The Service Request Tracker supports a controlled service request lifecycle:

```text
OPEN
  |
  v
IN_PROGRESS
  |
  v
RESOLVED
```

Employees can:

- Create service requests.
- View their own requests.
- Search and filter requests.
- Track request status.
- View status change history.

The backend ensures that users cannot access or modify requests belonging to other users.

## 2. Technology Stack

### Backend

- PHP
- Laravel
- Laravel Sanctum
- PostgreSQL
- Eloquent ORM

### Frontend

- Next.js
- React
- TypeScript

### Infrastructure and Tools

- Docker
- Docker Compose
- Git
- PHPUnit / Laravel Testing

## 3. Architecture

```text
                 Browser
                    |
                    v
             Next.js Frontend
                    |
                    | HTTP / JSON
                    v
              Laravel API
                    |
                    v
              Sanctum Auth
                    |
                    v
              API Controllers
                    |
                    v
               Eloquent ORM
                    |
                    v
                PostgreSQL
                    |
                    +-------------------+
                    |                   |
                    v                   v
             Service Requests     Status History
```

The frontend communicates with the backend through RESTful API endpoints.

The backend handles authentication, validation, authorization, business rules, transactions, and database operations.

## 4. Key Engineering Features

### Secure Resource Ownership

Each service request belongs to an authenticated user.

The backend uses owner-scoped database queries to prevent unauthorized access.

### Controlled Status Workflow

Only valid status transitions are accepted:

- OPEN → IN_PROGRESS
- IN_PROGRESS → RESOLVED

RESOLVED is a terminal state.

### Status History

The system maintains a separate status history table.

Each successful transition records:

- Previous status.
- New status.
- User who performed the change.
- Version number.
- Timestamp.

### Optimistic Concurrency

Each request contains a version number.

When updating a request, clients provide the expected version.

If another operation has already changed the record, the backend returns HTTP 409 Conflict.

### Transactional Consistency

The status update and corresponding history insertion happen within a single database transaction.

If history insertion fails, the status update is rolled back.

## 5. Project Structure

```text
service-request-tracker/
│
├── backend/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── docs/
│   ├── routes/
│   ├── tests/
│   ├── Dockerfile
│   ├── .dockerignore
│   ├── .env.example
│   └── README.md
│
├── frontend/
│   ├── app/
│   ├── components/
│   ├── hooks/
│   ├── lib/
│   ├── Dockerfile
│   ├── .dockerignore
│   └── ...
│
├── docker-compose.yml
├── README.md
└── .gitignore
```

## 6. Prerequisites

### Required for both approaches

- Git
- Docker Desktop
- Docker Compose

### Required for local development

- PHP (compatible with the Laravel version)
- Composer
- Node.js
- npm

Verify your installations:

```powershell
git --version
docker --version
docker compose version

php -v
composer --version

node --version
npm --version
```

## 7. Getting Started

There are two supported ways to run the application.

Choose one of the following approaches.

---

## Option A: Run the Entire Application Using Docker

In this approach, all three services run inside Docker containers:

- PostgreSQL database
- Laravel backend
- Next.js frontend

You do not need to install PHP, Composer, or Node.js locally to run the application.

### Step 1: Clone the Repository

```powershell
git clone <YOUR_REPOSITORY_URL>

cd service-request-tracker
```

Replace `<YOUR_REPOSITORY_URL>` with your actual repository URL.

### Step 2: Configure the Backend Environment

Create the backend environment file:

```powershell
Copy-Item backend/.env.example backend/.env
```

Install backend dependencies locally if you need to generate the Laravel application key:

```powershell
cd backend

composer install

php artisan key:generate

cd ..
```

Alternatively, generate an application key using a compatible PHP/Composer environment and place it in `backend/.env`.

Ensure the backend environment contains an `APP_KEY`.

The Docker Compose backend service should load `backend/.env` using `env_file`, while its Docker-specific environment settings override the local database host.

For example, the backend service should include:

```yaml
backend:
  env_file:
    - ./backend/.env
  environment:
    DB_CONNECTION: pgsql
    DB_HOST: postgres
    DB_PORT: 5432
    DB_DATABASE: service_tracker
    DB_USERNAME: tracker_user
    DB_PASSWORD: local_dev_password
```

The database hostname inside Docker must be `postgres`, not `127.0.0.1`.

### Step 3: Configure the Frontend

Ensure the frontend Dockerfile provides the public API URL at build time.

For example:

```dockerfile
ARG NEXT_PUBLIC_API_URL=http://localhost:8000
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL
```

The Docker Compose frontend build configuration should pass:

```yaml
frontend:
  build:
    context: ./frontend
    dockerfile: Dockerfile
    args:
      NEXT_PUBLIC_API_URL: http://localhost:8000
```

The browser accesses Laravel through `http://localhost:8000`.

If server-side Next.js code calls Laravel directly from inside the frontend container, use a separate internal API URL such as `http://backend:8000`.

### Step 4: Build and Start All Services

From the project root:

```powershell
docker compose up -d --build
```

This builds and starts:

- PostgreSQL
- Laravel backend
- Next.js frontend

### Step 5: Check Container Status

```powershell
docker compose ps
```

View logs:

```powershell
docker compose logs -f
```

View individual service logs:

```powershell
docker compose logs -f postgres
docker compose logs -f backend
docker compose logs -f frontend
```

### Step 6: Run Database Migrations

If your backend container does not automatically run migrations:

```powershell
docker compose exec backend php artisan migrate --force
```

Seed demo data if required:

```powershell
docker compose exec backend php artisan db:seed --force
```

### Step 7: Access the Application

| Service | URL |
|---|---|
| Next.js Frontend | http://localhost:3000 |
| Laravel Backend | http://localhost:8000 |
| PostgreSQL | localhost:5432 |

### Useful Docker Commands

Stop all services:

```powershell
docker compose stop
```

Start stopped services:

```powershell
docker compose start
```

Stop and remove containers:

```powershell
docker compose down
```

Rebuild after changing Dockerfiles or dependencies:

```powershell
docker compose up -d --build
```

Remove containers and database volume:

```powershell
docker compose down -v
```

**Warning:** `docker compose down -v` deletes the PostgreSQL volume and its stored database data.

---

## Option B: Local Development (Only PostgreSQL Runs in Docker)

This approach is recommended when actively developing the Laravel backend and Next.js frontend.

Only PostgreSQL runs inside Docker.

Laravel and Next.js run directly on your local machine.

```text
                 Browser
                    |
                    v
          Next.js (localhost:3000)
                    |
                    | HTTP / JSON
                    v
          Laravel (localhost:8000)
                    |
                    v
          PostgreSQL (Docker:5432)
```

### Step 1: Clone the Repository

```powershell
git clone <YOUR_REPOSITORY_URL>

cd service-request-tracker
```

### Step 2: Start Only PostgreSQL

From the project root:

```powershell
docker compose up -d postgres
```

Check the database container:

```powershell
docker compose ps
```

The PostgreSQL database should be available at:

```text
Host: localhost
Port: 5432
Database: service_tracker
Username: tracker_user
Password: local_dev_password
```

### Step 3: Configure and Install Backend Dependencies

Open a PowerShell terminal:

```powershell
cd backend
```

Install PHP dependencies:

```powershell
composer install
```

Create the environment file:

```powershell
Copy-Item .env.example .env
```

Generate the Laravel application key:

```powershell
php artisan key:generate
```

Configure the database connection in `backend/.env`:

```env
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

Configure Sanctum and session settings for the frontend origin:

```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000

SESSION_DRIVER=database
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
```

Ensure the Laravel CORS configuration allows:

```text
http://localhost:3000
```

and enables credentials for Sanctum cookie authentication.

### Step 4: Run Backend Migrations

```powershell
php artisan migrate
```

Seed demo data if required:

```powershell
php artisan db:seed
```

### Step 5: Start the Laravel Backend

```powershell
php artisan serve
```

Backend API:

```text
http://localhost:8000
```

Keep this terminal running.

### Step 6: Install Frontend Dependencies

Open a second PowerShell terminal:

```powershell
cd frontend
```

Install dependencies:

```powershell
npm install
```

Configure the frontend environment file according to the variables used by the application.

For example, if the API client uses `NEXT_PUBLIC_API_URL`:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```

### Step 7: Start the Next.js Frontend

```powershell
npm run dev
```

Frontend:

```text
http://localhost:3000
```

Keep this terminal running.

### Step 8: Verify the Application

The expected services are:

| Service | Runs On | Execution |
|---|---|---|
| PostgreSQL | localhost:5432 | Docker |
| Laravel API | localhost:8000 | Local PHP |
| Next.js | localhost:3000 | Local Node.js |

You can now modify Laravel and Next.js code locally and use their development servers without rebuilding Docker images.

---

## 8. Development Mode Comparison

| Feature | Full Docker | Local Development |
|---|---|---|
| PostgreSQL | Docker | Docker |
| Laravel | Docker | Local PHP |
| Next.js | Docker | Local Node.js |
| PHP installation required | No | Yes |
| Node.js installation required | No | Yes |
| Hot reload | Depends on Docker setup | Yes |
| Rebuild after code changes | Usually | No |
| Best for | Reproducible environment | Active development |

**Recommended workflow:** Use Option B while developing and debugging. Use Option A to verify that the complete application can build and run using Docker.

## 9. API Overview

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/login` | Login |
| GET | `/api/auth/me` | Current user |
| POST | `/api/auth/logout` | Logout |
| POST | `/api/requests` | Create request |
| GET | `/api/requests` | List requests |
| GET | `/api/requests/{id}` | View request |
| PATCH | `/api/requests/{id}/status` | Update status |
| GET | `/api/requests/{id}/history` | View history |

Protected endpoints require authentication.

See `backend/README.md` for setup details and API examples.

## 10. Concurrency Handling

The application uses optimistic concurrency control.

Example status update:

```json
{
  "status": "IN_PROGRESS",
  "expected_version": 1
}
```

The database update checks both the expected version and current status.

If the expected version is outdated, the backend responds with:

```text
HTTP 409 Conflict
```

The client should reload the latest request state instead of automatically retrying with stale data.

## 11. Testing

Backend tests:

```powershell
cd backend

php artisan test
```

Formatting check:

```powershell
vendor/bin/pint --test
```

The backend test suite covers authentication, ownership, validation, status transitions, history, concurrency conflicts, and transactional rollback.

Use PostgreSQL for database-specific integration and concurrency tests.

## 12. Documentation

Additional backend documentation:

```text
backend/
├── README.md
└── docs/
    ├── ARCHITECTURE.md
    ├── DATABASE_DESIGN.md
    ├── API_DOCUMENTATION.md
    ├── SECURITY.md
    └── TECHNICAL_DECISIONS.md
```

## 13. Environment Variables and Security

Never commit:

- `.env` files.
- Database passwords.
- Application secrets.
- Production credentials.
- Private keys.

Commit `.env.example` with placeholder or local-development configuration only.

Production deployments should use secure environment variables, HTTPS, restricted CORS origins, secure cookies, and `APP_DEBUG=false`.

## 14. Design Priorities

The project emphasizes:

- Secure multi-user access.
- Maintainable Laravel architecture.
- Clear API contracts.
- Database consistency.
- Reliable status history.
- Concurrency protection.
- Automated backend testing.

## 15. Future Improvements

Potential enhancements include:

- Role-based access control.
- Request assignment to support staff.
- Notifications and email alerts.
- Queue-based background processing.
- Audit logging.
- API rate limiting.
- Observability and performance monitoring.
- CI/CD automation.

---

**Service Request Tracker** — A full-stack application demonstrating secure API development, transactional database operations, and reliable workflow management.