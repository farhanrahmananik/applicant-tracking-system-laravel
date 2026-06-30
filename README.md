# Applicant Tracking System

A portfolio-grade Applicant Tracking System built with Laravel 12 for HR departments and recruitment agencies. The project models a practical recruitment workflow from organization setup and job publishing through candidate evaluation, pipeline movement, interviews, offers, reporting, and audit history.

This repository demonstrates production-style Laravel architecture and engineering practices. It is a local portfolio and demonstration application, not a claim of a hosted production SaaS service.

## Project Highlights

- Role- and permission-based access enforced through Laravel Gates and route middleware
- End-to-end internal recruitment workflows with validated state transitions
- Private resume storage with permission-controlled downloads
- Queued candidate email notifications with safe local logging defaults
- Filtered operational reports and CSV exports
- Security and business audit trails with sensitive-value redaction
- Responsive, Tabler-inspired Bootstrap interface with persistent light and dark themes
- Automated feature coverage for authorization, validation, workflows, exports, notifications, and page rendering

## Implemented Modules

| Module | What it provides |
| --- | --- |
| Authentication and Authorization | Active-user login, logout, intended-route redirects, login throttling, role checks, permission middleware, and Gate-based authorization. |
| Role and Permission Foundation | Seeded role/permission matrix, user-role assignments, Gate registration, and Super Admin bypass. Dedicated role-management CRUD screens are not implemented. |
| Dashboard | Authenticated overview of users, active/inactive accounts, roles, permissions, recent users, and role distribution. |
| Company Management | Searchable company records with create, view, update, status, and soft-delete workflows. |
| Department Management | Company-linked departments with filters, contact details, status management, validation, and soft deletes. |
| Job Posting Management | Company and department assignments, employment/workplace details, compensation ranges, publishing states, filters, and soft deletes. |
| Candidate Management | Internal candidate profiles, contact and professional details, availability/source filters, search, and soft deletes. Candidate self-registration is not implemented. |
| Resume / CV Upload | PDF, DOC, and DOCX uploads to private storage, primary-resume handling, scoped downloads, and permission-controlled deletion. |
| Application Tracking | Candidate-to-job applications, duplicate-active-application protection, status tracking, search, filters, and related-record summaries. |
| Interview Scheduling | Interview scheduling for eligible applications, active internal interviewer validation, schedule updates, cancellation, and application-level visibility. |
| Interview Feedback | Rating, recommendation, strengths, weaknesses, duplicate-submission protection, and eligibility rules for scheduled/completed interviews. |
| Hiring Pipeline | Board-style application pipeline, controlled stage transitions, terminal stages, transition notes, actor attribution, and stage history. |
| Offer Management | Draft-to-resolution offer workflow, compensation and joining details, duplicate active-offer protection, status transitions, and status history. |
| Email Notifications | Queued candidate notifications for applications, interview changes/cancellations, and offer sent/accepted/declined events. |
| Reports | Application, candidate, job posting, interview, pipeline, and offer summaries with validated filters; CSV exports are available for application, job, interview, pipeline, and offer reports. |
| Audit Logs | Filterable activity history, before/after snapshots, actor and entity context, sensitive-value redaction, resume activity, and sanitized CSV export. |
| UI Polish | Responsive SaaS-style admin shell, consistent forms/tables/cards, custom filter selects, mobile navigation, and localStorage-backed light/dark mode. |
| Testing and Quality Review | Regression coverage for protected routes, permissions, validation, workflows, notifications, exports, data integrity, and major admin page render paths. |

## Recruitment Workflow

1. Configure companies and departments.
2. Create and publish job postings.
3. Add candidate profiles and private resumes.
4. Link candidates to jobs through applications.
5. Move applications through validated pipeline stages.
6. Schedule interviews and collect interviewer feedback.
7. Select candidates and manage employment offers.
8. Review reports and audit history throughout the process.

## Role-Based Access

Authorization is configured in [`config/ats_permissions.php`](config/ats_permissions.php), seeded through `RolePermissionSeeder`, registered as Laravel Gates, and enforced by `auth`, `active`, `role`, and `permission` middleware.

| Role | Current access |
| --- | --- |
| Super Admin | Global Gate bypass and full access to every currently registered permission. |
| HR Manager | Organization, recruitment, resume, interview, pipeline, offer, reporting, user-management permission, and audit-log access, including destructive actions where configured. There is no user-management UI yet. |
| Recruiter | Day-to-day recruitment operations: jobs, candidates, resumes, applications, interviews, feedback, pipeline, offers, reports, and dashboard access. Destructive organization/application actions and audit logs are excluded. |
| Interviewer | Dashboard, job-posting visibility, interview visibility, and interview-feedback create/view/update permissions. |
| Candidate | Seeded with `view-own-candidate-profile` only. A candidate-facing portal and candidate registration flow are intentionally not implemented. |

The project currently uses Gates and custom middleware rather than dedicated Laravel Policy classes.

## Technology Stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12 |
| Database | MySQL |
| Server-rendered UI | Blade, Bootstrap 5, Bootstrap Icons |
| Design direction | Custom responsive ATS design system inspired by Tabler admin UI patterns |
| Client behavior | Vanilla JavaScript, Bootstrap JavaScript |
| Asset pipeline | Vite 6, npm |
| Testing | PHPUnit 11 through Laravel's test runner |
| Tooling | Composer, Laravel Pint, Git, GitHub |

> **Frontend dependency note:** jQuery, DataTables, and SweetAlert2 are not current npm dependencies in the checked-in implementation. Existing tables, filters, confirmations, and interactions use Blade, Bootstrap, and vanilla JavaScript, so this README does not present those libraries as active dependencies.

## Architecture

The application follows a conventional service-oriented Laravel structure:

- **Thin controllers** coordinate requests, services, and responses.
- **Form Requests** own authorization checks, normalization, and validation.
- **Services** contain query composition, transactions, status rules, exports, notifications, and audit behavior.
- **Gates and middleware** enforce role/permission access; Super Admin receives a Gate-level bypass.
- **Eloquent relationships** connect users, organizations, jobs, candidates, applications, interviews, feedback, offers, histories, and audit records.
- **Database transactions and row locks** protect important application, pipeline, resume, interview, and offer operations.
- **Queued Laravel Notifications** isolate candidate-facing mail from HTTP workflows.
- **Feature tests** exercise HTTP behavior, authorization, validation, business rules, and major render paths.

### Important Project Paths

| Path | Responsibility |
| --- | --- |
| `app/Http/Controllers` | HTTP orchestration and view/redirect responses |
| `app/Http/Requests` | Validation, normalization, and request authorization |
| `app/Models` | Eloquent entities, relationships, casts, and workflow constants |
| `app/Services` | Business workflows, reporting, exports, notifications, and audit operations |
| `app/Notifications` | Queued candidate email notifications |
| `config/ats_permissions.php` | Permission definitions and role grants |
| `database/migrations` | MySQL schema and relational constraints |
| `database/factories` | Test data factories |
| `database/seeders` | Roles, permissions, and demo user accounts |
| `resources/views` | Authenticated layouts, module pages, reports, and email templates |
| `public/css` and `public/js` | ATS design system and theme behavior |
| `routes/web.php` | Authenticated and permission-protected web routes |
| `tests/Feature` | Module, workflow, security, export, and render regression tests |
| `docs` | Database design, ERD, migration planning, and notification notes |

## Local Setup

### Prerequisites

- PHP 8.2 or later with the extensions required by Laravel and MySQL
- Composer
- Node.js and npm
- MySQL
- Git

### 1. Clone the repository

```bash
git clone https://github.com/farhanrahmananik/applicant-tracking-system-laravel.git
cd applicant-tracking-system-laravel
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Create the environment file

Windows Command Prompt:

```bat
copy .env.example .env
```

PowerShell, macOS, or Linux:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure MySQL

Create a database and update the corresponding values in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ats_laravel
DB_USERNAME=root
DB_PASSWORD=
```

Adjust credentials for your local MySQL installation. The project also reads `APP_TIMEZONE` from `.env`.

### 5. Migrate and seed

For a clean local/demo database:

```bash
php artisan migrate:fresh --seed
```

> `migrate:fresh` drops all existing tables before rebuilding the database. Do not run it against data you need to preserve.

The seeders create the permission matrix, five roles, and five demo users. They do **not** create sample companies, candidates, applications, interviews, or offers; those records can be created through the application.

### 6. Build or serve frontend assets

For active development:

```bash
npm run dev
```

For a production asset build:

```bash
npm run build
```

On Windows, the explicit executable form is also available:

```bat
npm.cmd run build
```

### 7. Start Laravel

```bash
php artisan serve
```

Open `http://127.0.0.1:8000` unless Artisan reports a different port.

### 8. Process queued notifications

Notifications use the database queue and the `notifications` queue name:

```bash
php artisan queue:work --queue=notifications,default
```

The default local mailer writes messages to `storage/logs/laravel.log`; it does not send external email.

### Resume Storage

Candidate resumes are stored on Laravel's private `local` disk under `storage/app/private` and are served only through permission-protected download routes. `php artisan storage:link` is **not required** for resume uploads in this project.

## Demo Accounts

`php artisan migrate:fresh --seed` creates the following accounts. Every seeded account uses the password `password`.

| Role | Email | Current demo use |
| --- | --- | --- |
| Super Admin | `superadmin@ats.test` | Full application review |
| HR Manager | `hr@ats.test` | HR operations and audit access |
| Recruiter | `recruiter@ats.test` | Recruitment workflow review |
| Interviewer | `interviewer@ats.test` | Interview and feedback review |
| Candidate | `candidate@ats.test` | Seeded role only; no candidate portal is implemented |

These credentials are for local demonstration only and must not be used in a deployed environment.

## Testing and Quality

Run the complete Laravel test suite:

```bash
php artisan test
```

Format changed PHP files:

```bash
vendor/bin/pint --dirty
```

Windows:

```bat
vendor\bin\pint --dirty
```

Latest verified project status at the end of the Testing / Quality Review scope:

- `php artisan migrate:fresh --seed`: passed
- `php artisan test`: **150 tests, 886 assertions, all passing**
- `npm.cmd run build`: passed

Coverage includes authentication, throttling, inactive users, permissions, CRUD workflows, soft deletes, private resume handling, pipeline and offer transitions, interview rules, queued notifications, report/audit exports, filtering, and protected admin page rendering.

## Reports and Audit Exports

- Recruitment reports support validated filters across company, department, job, candidate, application, interview, pipeline, and offer data.
- CSV downloads are generated as streamed responses with spreadsheet-formula injection protection.
- Audit exports contain timestamps, actors, actions, entity references, and sanitized summaries.
- Stored audit snapshots redact configured sensitive values such as credentials and private file details.

## Screenshots

Screenshots are not currently committed to this repository. This section is intentionally left without image links rather than referencing files that do not exist.

Recommended future captures include:

- Dashboard in dark and light mode
- Candidate and application detail pages
- Hiring pipeline board
- Interview feedback and offer history
- Reports and audit-log views

## Scope Notes

- The system is an internal ATS administration demo; candidate registration and a candidate self-service portal are not implemented.
- No hosted demo URL is currently documented.
- Production deployment configuration is outside this repository's current scope.
- Resume content is private and is not linked from public storage.

## Additional Documentation

- [Database design](docs/database/database-design.md)
- [Schema blueprint](docs/database/schema-blueprint.md)
- [Migration plan](docs/database/migration-plan.md)
- [Entity relationship diagram](docs/database/erd.md)
- [Email notification notes](docs/email-notifications.md)
