# Project Management System — Backend API

A full-featured Laravel 11 REST API implementing project management with role-based access control, queued notifications, caching, and comprehensive test coverage.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Architecture Overview](#architecture-overview)
- [Requirements](#requirements)
- [Installation & Setup](#installation--setup)
  - [Option A — Using Laravel Herd (Recommended for Windows/Mac)](#option-a--using-laravel-herd-recommended-for-windowsmac)
  - [Option B — Using php artisan serve (Traditional)](#option-b--using-php-artisan-serve-traditional)
- [API Endpoints Reference](#api-endpoints-reference)
- [Role-Based Access Control](#role-based-access-control)
- [Seeded Test Accounts](#seeded-test-accounts)
- [Running Tests](#running-tests)
- [Postman Collection](#postman-collection)
- [Queue & Notifications](#queue--notifications)
- [Caching](#caching)
- [Activity Logging](#activity-logging)
- [Project Structure](#project-structure)
- [Common Troubleshooting](#common-troubleshooting)

---

## Tech Stack

| Layer          | Technology                          |
|----------------|-------------------------------------|
| Framework      | Laravel 11                          |
| Authentication | Laravel Sanctum (token-based)       |
| Database       | MySQL 8+ (SQLite for testing)       |
| Queue          | Database driver (sync for testing)  |
| Cache          | Database driver (array for testing) |
| Notifications  | Laravel Mail + Database channel     |
| Testing        | PHPUnit 11                          |

---

## Architecture Overview

```
app/
├── Http/
│   ├── Controllers/Api/V1/        # Versioned API controllers
│   │   ├── AuthController.php
│   │   ├── ProjectController.php
│   │   ├── TaskController.php
│   │   └── CommentController.php
│   ├── Middleware/
│   │   ├── ActivityLogger.php     # Logs user_id, endpoint, timestamp
│   │   └── RoleMiddleware.php     # RBAC: role:admin, role:manager
│   └── Requests/                  # Form request validation (per-entity)
│       ├── Auth/
│       ├── Project/
│       ├── Task/
│       └── Comment/
├── Models/                        # Eloquent models with SoftDeletes
├── Services/
│   └── TaskAssignmentService.php  # Business logic for task creation/assignment
├── Traits/
│   ├── ApiResponse.php            # Consistent JSON responses
│   └── CommonQueryScopes.php      # filterByStatus(), searchByTitle()
├── Jobs/
│   └── SendTaskAssignedNotificationJob.php  # Queued notification job
└── Notifications/
    └── TaskAssignedNotification.php         # Mail + DB notification
```

---

## Requirements

- PHP 8.2+
- Composer 2.x
- MySQL 5.7+ (or MariaDB 10.5+)
- [Laravel Herd](https://herd.laravel.com) *(recommended)* or any local PHP environment

---

## Installation & Setup

### Step 1 — Clone the Repository

```bash
git clone https://github.com/yourusername/project-management-system.git
cd project-management-system
```

### Step 2 — Install Dependencies

```bash
composer install
```

### Step 3 — Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_management
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log
```

### Step 4 — Create the Database

Open MySQL and run:

```sql
CREATE DATABASE project_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 5 — Run Migrations & Seed Data

```bash
php artisan migrate
php artisan db:seed
```

This seeds the database with:
- 3 admin users
- 3 manager users
- 5 regular users
- 5 projects
- 10 tasks
- 10 comments

### Step 6 — Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## Running the Application

### Option A — Using Laravel Herd (Recommended for Windows/Mac)

[Laravel Herd](https://herd.laravel.com) is the easiest way to run Laravel locally on Windows or Mac. It bundles PHP 8.3, nginx, and Composer with zero configuration needed.

#### 1. Install Herd

1. Download from **https://herd.laravel.com** (free)
2. Install and open Herd
3. When prompted **"Choose Your Sites Folder"**:
   - Select **"Choose custom folder"**
   - Navigate to the **parent folder** of your project
   - Example: if your project is at `C:\Users\User\Desktop\Lav-App\project-management-system`, select `C:\Users\User\Desktop\Lav-App`
4. Click **Continue**

#### 2. Your app is automatically live at:

```
http://project-management-system.test
```

#### 3. API base URL (use this in Postman):

```
http://project-management-system.test/api/v1
```

#### 4. Start the Queue Worker (separate terminal, needed for email notifications):

```bash
php artisan queue:work
```

> ⚠️ **Do NOT run `php artisan serve` while Herd is running.** Herd already serves your app automatically via nginx. Running both causes port conflicts (`Failed to listen on 127.0.0.1:8000`).

> ⚠️ **After installing Herd**, open a **new** PowerShell window before running any `php` or `composer` commands so the updated PATH takes effect.

---

### Option B — Using php artisan serve (Traditional)

Use this if you are not using Herd and have PHP 8.2+ installed manually.

```bash
# Terminal 1 — Start the development server
php artisan serve
# API available at: http://localhost:8000/api/v1/

# Terminal 2 — Start the queue worker
php artisan queue:work
```

#### API base URL (use this in Postman):

```
http://localhost:8000/api/v1
```

---

## API Endpoints Reference

All endpoints are prefixed with `/api/v1`. All protected endpoints require:

```
Authorization: Bearer {access_token}
Accept: application/json
```

### Authentication

| Method | Endpoint         | Auth Required | Description              |
|--------|------------------|:-------------:|--------------------------|
| POST   | `/auth/register` | ❌            | Register a new user      |
| POST   | `/auth/login`    | ❌            | Login and get token      |
| POST   | `/auth/logout`   | ✅            | Revoke current token     |
| GET    | `/auth/me`       | ✅            | Get current user profile |

### Projects

| Method | Endpoint         | Role Required | Description          |
|--------|------------------|:-------------:|----------------------|
| GET    | `/projects`      | Any Auth      | List all projects    |
| GET    | `/projects/{id}` | Any Auth      | Get a single project |
| POST   | `/projects`      | Admin         | Create a project     |
| PUT    | `/projects/{id}` | Admin         | Update a project     |
| DELETE | `/projects/{id}` | Admin         | Delete a project     |

**Query Parameters (GET /projects):**
- `search` — Filter by title keyword
- `page` — Page number (default: 1)
- `per_page` — Items per page (default: 15)

### Tasks

| Method | Endpoint                       | Role Required            | Description            |
|--------|--------------------------------|:------------------------:|------------------------|
| GET    | `/projects/{project_id}/tasks` | Any Auth                 | List tasks for project |
| GET    | `/tasks/{id}`                  | Any Auth                 | Get a single task      |
| POST   | `/projects/{project_id}/tasks` | Admin / Manager          | Create a task          |
| PUT    | `/tasks/{id}`                  | Manager or Assigned User | Update a task          |
| DELETE | `/tasks/{id}`                  | Admin / Manager          | Delete a task          |

**Query Parameters (GET tasks):**
- `status` — Filter by: `pending` | `in-progress` | `done`
- `search` — Filter by title keyword
- `page`, `per_page`

### Comments

| Method | Endpoint                    | Role Required                   | Description             |
|--------|-----------------------------|---------------------------------|-------------------------|
| GET    | `/tasks/{task_id}/comments` | Any Auth                        | List comments on a task |
| POST   | `/tasks/{task_id}/comments` | Assigned User / Manager / Admin | Add a comment to a task |

---

## Standard Response Format

All endpoints return a consistent JSON envelope:

```json
// Success
{
  "status": "success",
  "message": "Operation successful.",
  "data": { ... }
}

// Error
{
  "status": "error",
  "message": "Validation failed.",
  "data": null,
  "errors": {
    "field": ["error message"]
  }
}
```

### HTTP Status Codes

| Code | Meaning               |
|------|-----------------------|
| 200  | OK                    |
| 201  | Created               |
| 401  | Unauthorized          |
| 403  | Forbidden             |
| 404  | Not Found             |
| 422  | Validation Error      |
| 500  | Internal Server Error |

---

## Role-Based Access Control

| Resource      | Admin | Manager   | User (Assigned)   |
|---------------|:-----:|:---------:|:-----------------:|
| Projects CRUD | ✅    | Read only | Read only         |
| Tasks Create  | ✅    | ✅        | ❌                |
| Tasks Update  | ✅    | ✅        | Status only ✅    |
| Tasks Delete  | ✅    | ✅        | ❌                |
| Comments Add  | ✅    | ✅        | Own tasks only ✅ |
| Comments View | ✅    | ✅        | ✅                |

---

## Seeded Test Accounts

All accounts use the password: **`password`**

| ID | Role    | Email              |
|----|---------|--------------------|
| 1  | Admin   | admin1@pms.local   |
| 2  | Admin   | admin2@pms.local   |
| 3  | Admin   | admin3@pms.local   |
| 4  | Manager | manager1@pms.local |
| 5  | Manager | manager2@pms.local |
| 6  | Manager | manager3@pms.local |
| 7  | User    | user1@pms.local    |
| 8  | User    | user2@pms.local    |
| 9  | User    | user3@pms.local    |
| 10 | User    | user4@pms.local    |
| 11 | User    | user5@pms.local    |

---

## Running Tests

Tests use an **in-memory SQLite database** so they never touch your real MySQL database. Safe to run anytime.

```bash
# Run all tests
php artisan test

# Run only Feature tests
php artisan test --testsuite=Feature

# Run only Unit tests
php artisan test --testsuite=Unit

# Run with verbose output (shows each test name)
php artisan test --verbose

# Run with code coverage (requires Xdebug or PCOV)
php artisan test --coverage --min=85

# Run a specific test file
php artisan test tests/Feature/AuthTest.php
php artisan test tests/Feature/ProjectTest.php
php artisan test tests/Feature/TaskTest.php
php artisan test tests/Feature/CommentTest.php
php artisan test tests/Unit/TaskAssignmentServiceTest.php
```

### Expected Output

```
PASS  Tests\Unit\TaskAssignmentServiceTest       12 tests
PASS  Tests\Feature\AuthTest                     10 tests
PASS  Tests\Feature\ProjectTest                  12 tests
PASS  Tests\Feature\TaskTest                     11 tests
PASS  Tests\Feature\CommentTest                   8 tests

Tests:    43 passed
Duration: ~5.00s
```

---

## Postman Collection

Import `postman/ProjectManagement.postman_collection.json` into Postman.

### Setup Steps

1. Open Postman → click **Import** → drag in the collection file
2. Click the collection name → go to the **Variables** tab
3. Set `base_url` based on your setup:

| Setup          | base_url value                                 |
|----------------|------------------------------------------------|
| Laravel Herd   | `http://project-management-system.test/api/v1` |
| artisan serve  | `http://localhost:8000/api/v1`                 |

4. Click **Save**
5. Open **Auth → Login** → click **Send**
6. The token is **automatically saved** to the `token` variable — all other requests will use it without any manual copy-paste

---

## Queue & Notifications

When a task is created or reassigned, a **queued job** sends an email notification to the assignee.

```bash
# Start the queue worker (keep this running in a separate terminal)
php artisan queue:work

# Monitor queued jobs
php artisan queue:monitor default

# Retry failed jobs
php artisan queue:retry all
```

**Email in development:** With `MAIL_MAILER=log` in `.env`, emails are written to `storage/logs/laravel.log` instead of actually being sent — great for local testing.

---

## Caching

Project listings are cached for **5 minutes** and automatically invalidated whenever a project is created, updated, or deleted.

```bash
php artisan cache:clear    # Clear all cache
php artisan config:clear   # Clear config cache
php artisan route:clear    # Clear route cache
```

---

## Activity Logging

Every API request is automatically logged by the `ActivityLogger` middleware to:
1. The `activity_logs` database table
2. The `storage/logs/laravel.log` daily log file

Logged fields: `user_id`, `method`, `endpoint`, `ip_address`, `user_agent`, `response_status`, `timestamp`

---

## Project Structure

```
project-management-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/     # All API controllers
│   │   ├── Middleware/             # ActivityLogger, RoleMiddleware
│   │   └── Requests/              # Form request validation classes
│   ├── Jobs/                      # SendTaskAssignedNotificationJob
│   ├── Models/                    # User, Project, Task, Comment, ActivityLog
│   ├── Notifications/             # TaskAssignedNotification (mail + db)
│   ├── Providers/                 # AppServiceProvider
│   ├── Services/                  # TaskAssignmentService
│   └── Traits/                    # ApiResponse, CommonQueryScopes
├── bootstrap/
│   ├── app.php                    # Laravel 11 app bootstrap
│   └── providers.php              # Service provider registration
├── config/                        # app, auth, cache, database, mail, queue, etc.
├── database/
│   ├── factories/                 # UserFactory, ProjectFactory, TaskFactory, CommentFactory
│   ├── migrations/                # All 9 schema migrations
│   └── seeders/                   # DatabaseSeeder + 4 individual seeders
├── postman/
│   └── ProjectManagement.postman_collection.json
├── public/
│   └── index.php                  # HTTP front controller
├── routes/
│   └── api.php                    # Versioned API routes (/api/v1/...)
├── storage/                       # Logs, cache, sessions (auto-managed)
├── tests/
│   ├── Feature/                   # AuthTest, ProjectTest, TaskTest, CommentTest
│   └── Unit/                      # TaskAssignmentServiceTest
├── .env.example
├── artisan
├── composer.json
├── phpunit.xml
└── README.md
```

---

## Common Troubleshooting

| Issue | Solution |
|---|---|
| `502 Bad Gateway` on Herd | Run `php artisan config:clear` then restart Herd from the taskbar |
| `Failed to listen on 127.0.0.1:8000` | Herd is already running — use `http://project-management-system.test` instead of `php artisan serve` |
| `Could not open input file: artisan` | You are in the wrong directory — `cd` into the project root first |
| `php -v` shows PHP 7.x after installing Herd | Open a **new** PowerShell window so the updated PATH takes effect |
| `401 Unauthorized` on protected routes | Run **Login** first in Postman to get a fresh token |
| `403 Forbidden` on project/task write routes | Check the role of the logged-in user — admin or manager required |
| Migration errors | Verify DB credentials in `.env` and that the database exists in MySQL |
| Queue notifications not sending | Run `php artisan queue:work` in a separate terminal |
| Tests failing unexpectedly | Run `php artisan config:clear` then retry `php artisan test` |
| Cache stale data | Run `php artisan cache:clear && php artisan config:clear` |
