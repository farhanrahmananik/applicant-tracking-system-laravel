# Applicant Tracking System Database Design

## 1. Project Database Overview

This document defines the proposed relational database design for the Laravel 12 Applicant Tracking System (ATS). The target database is MySQL 8+ using InnoDB, `utf8mb4`, strict SQL mode, and the `utf8mb4_unicode_ci` collation unless deployment requirements select a more specific Unicode collation.

The system is designed for HR departments and recruitment agencies operating as separate companies. Company-owned records are tenant-scoped with a required `company_id` wherever practical. Platform identities and authorization definitions remain global, while role assignments can be global or company-specific.

This is a design contract, not an implemented schema. The detailed column proposal is in [schema-blueprint.md](schema-blueprint.md), and the intended implementation sequence is in [migration-plan.md](migration-plan.md).

## 2. Design Goals

- Preserve company data isolation at every tenant-owned boundary.
- Enforce important relationships and uniqueness rules in MySQL, not only in application code.
- Keep workflows extensible by storing status and stage values as strings instead of MySQL `ENUM` columns.
- Retain recruitment history needed for accountability, reporting, and audit review.
- Protect candidate personal data through least-privilege access, controlled retention, and non-public file storage.
- Support Laravel conventions and schema builder types without premature infrastructure abstractions.
- Use stable internal numeric keys and non-sequential public ULIDs where records need public URLs or external references.
- Make destructive deletion exceptional and explicit.

## 3. Naming Conventions

| Item | Convention |
| --- | --- |
| Tables | Plural `snake_case`, for example `job_posts` |
| Primary keys | `id` as `BIGINT UNSIGNED AUTO_INCREMENT` |
| Foreign keys | Singular relation plus `_id`, for example `company_id` |
| Public identifiers | `public_id` as `CHAR(26)` ULID with a unique index |
| Boolean columns | Positive `is_*` or `has_*` names |
| Timestamps | UTC `TIMESTAMP` columns; Laravel `created_at` and `updated_at` conventions |
| Soft deletes | Nullable `deleted_at` via Laravel `softDeletes()` |
| Status values | Lowercase `snake_case` strings such as `interviewing` |
| Currency | ISO 4217 uppercase code in `CHAR(3)` |
| Time zones | IANA identifier such as `Europe/Berlin` |
| Indexes | Descriptive names when Laravel's generated name could be unclear or too long |
| Pivot tables | Singular model names in alphabetical or established domain order, such as `role_user` |

All date-time values are stored in UTC. A record-specific IANA time zone is retained where it is needed to reconstruct local scheduling intent.

## 4. Core Entity Groups

### Identity and access

- `users` stores global authenticated identities.
- `roles` and `permissions` define global authorization vocabulary.
- `permission_role` grants permissions to roles.
- `role_user` assigns roles either at platform scope or within one company.

The planned roles are Super Admin, HR Manager, Recruiter, Interviewer, and Candidate. Authorization rules will later be implemented with Policies and Gates. Database role assignments are inputs to authorization, not a substitute for policy checks.

### Organization

- `companies` represents an HR organization, employer, or recruitment agency tenant.
- `departments` organizes jobs and optional departmental managers inside a company.

### Recruitment content and candidates

- `job_posts` defines vacancies and publication state.
- `candidates` stores a company-owned candidate identity and consent state.
- `candidate_profiles` stores extended, one-to-one profile details.
- `candidate_resumes` stores private file metadata and version history, not file bytes.

### Hiring workflow

- `applications` connects a candidate to a job and stores the current workflow snapshot.
- `application_stage_histories` provides an append-oriented transition history.
- `interview_schedules` defines interview events.
- `interview_schedule_user` assigns one or more interviewers to an event.
- `interview_feedback` stores each interviewer's review.
- `offers` stores versioned offers and their lifecycle.

### Operations and accountability

- `email_notifications` records queued and delivered recruitment email activity.
- `audit_logs` stores immutable security and business-event evidence.

## 5. Relationship Summary

- A company has many departments, job posts, candidates, applications, interviews, offers, email notifications, and audit logs.
- A department belongs to one company and may have many job posts.
- A candidate belongs to one company, may optionally link to a portal user, has one profile, and has many resumes and applications.
- A job post belongs to one company and optionally one department; it has many applications.
- An application belongs to one company, candidate, and job post; it has many stage history entries, interviews, and offer versions.
- An interview belongs to one application and has many interviewers through `interview_schedule_user`.
- Interview feedback belongs to one interview and one reviewer. A reviewer may submit at most one feedback record per interview.
- Roles have many permissions and users through their pivot tables.
- Creator, updater, manager, recruiter, organizer, reviewer, and actor references point to `users` and normally use `ON DELETE SET NULL` to preserve historical records.

Tenant consistency must be verified whenever multiple company-owned foreign keys meet. For example, an application may only connect a candidate and job post belonging to the same company. MySQL cannot express every such cross-row rule with ordinary foreign keys, so future Services must validate these invariants inside database transactions.

## 6. Data Integrity Rules

- Every company-owned aggregate has a `company_id`; queries must always scope by it.
- User email addresses are globally unique. Candidate email addresses are unique within a company.
- A candidate may apply to a given job only once unless a future requirement explicitly introduces application cycles.
- A candidate has at most one profile.
- Resume paths are unique, file checksums are recorded, and only one resume should be marked primary per candidate. The primary-resume invariant is enforced transactionally because MySQL has no portable partial unique index.
- Interview end time must be later than start time.
- An interviewer must be assigned to the interview before submitting feedback.
- Offer versions are unique within an application. At most one offer may be in an accepted state for an application; future Services enforce this transactionally.
- Status transitions must use an allowlist and append a matching stage-history or audit record in the same transaction when required.
- Currency amounts use fixed-precision decimal columns and never floating-point types.
- File content is stored outside MySQL. Database rows hold private storage references and integrity metadata.
- Foreign-key actions default to `RESTRICT` for business aggregates, `CASCADE` for true dependent pivots/details, and `SET NULL` for historical actor references.

## 7. Status Workflow Overview

Statuses are `VARCHAR` values backed by PHP constants or value objects in a future scope. They are indexed where they drive queues, filters, or reports.

### Job post

`draft` -> `published` -> `paused` or `closed` -> `archived`

A closed job may be reopened only through an explicit service operation. Publication and closure timestamps must match their states.

### Candidate

Typical values: `active`, `inactive`, `blocked`, `anonymized`.

This describes the candidate record, not an individual application outcome.

### Application

The broad status uses values such as `active`, `rejected`, `withdrawn`, `hired`, and `archived`. The current pipeline position is stored separately in `current_stage`, with initial suggested stages:

`applied` -> `screening` -> `shortlisted` -> `interview` -> `offer` -> `hired`

Terminal outcomes such as `rejected` and `withdrawn` are application statuses. Every pipeline change creates an `application_stage_histories` row.

### Interview

Typical values: `scheduled`, `rescheduled`, `completed`, `cancelled`, `no_show`.

### Offer

Typical values: `draft`, `pending_approval`, `approved`, `sent`, `accepted`, `declined`, `expired`, `withdrawn`.

### Email notification

Typical values: `pending`, `queued`, `sending`, `sent`, `failed`, `cancelled`.

## 8. Audit and Ownership Strategy

- Tenant-owned records carry `company_id` even when it can be inferred through another relation. This supports mandatory scoping, efficient filtering, and audit review.
- Mutable business records include nullable `created_by_id` and `updated_by_id` where a user can initiate changes. System-created records may leave these fields null.
- Actor foreign keys use `SET NULL` so deleting or anonymizing a user does not erase business history.
- `audit_logs` is append-only. Normal application code must not update or soft-delete audit rows.
- Audit entries record company, actor, event, target type/id, before/after values, request identifier, IP address, and user agent where available.
- Sensitive values such as passwords, tokens, resume contents, and unnecessary candidate details must never be copied into audit JSON.
- Future complex write operations belong in Services so the aggregate update and its audit/history records share one database transaction.

## 9. Soft Delete Strategy

Soft deletes are proposed for users, companies, departments, job posts, candidates, candidate profiles, candidate resumes, applications, interview schedules, and offers. These records may be referenced by reports, workflow history, or legal evidence.

Soft deletes are intentionally not proposed for:

- Authorization pivots, which represent current assignments and can be removed directly.
- Application stage history and audit logs, which are append-oriented records.
- Interview feedback, which should be retained and access-controlled; correction should be audited rather than silently removed.
- Email notification delivery records, which require retention and purge rules rather than user-facing deletion semantics.

Soft deletion is not anonymization. Privacy erasure should be a dedicated, audited process that removes or irreversibly replaces personal fields and files after checking legal retention obligations. Company deletion should normally suspend access first and use an asynchronous retention workflow instead of cascading through recruitment history.

## 10. Indexing Strategy

- Index every foreign key used for joins or tenant scoping.
- Lead common operational indexes with `company_id`, followed by status/date fields used in filters.
- Add unique constraints for business identity, including company slug, company-scoped candidate email, job slug, application candidate/job pair, and offer version.
- Index searchable names, emails, job titles, stages, statuses, scheduled times, and delivery states.
- Use composite indexes that match real query order, for example `(company_id, status, applied_at)`.
- Avoid indexing `TEXT`, `LONGTEXT`, and unrestricted JSON columns by default. Add full-text or generated-column indexes only after query evidence justifies them.
- Review indexes with `EXPLAIN` against production-like data before adding overlapping variants.
- Preserve selectivity: low-cardinality status columns generally belong after `company_id`, not as isolated indexes.

## 11. Security and Privacy Considerations

- Treat candidate identity, contact details, resumes, salary expectations, interview feedback, and offers as confidential personal data.
- Store resume and offer files on private storage disks. Serve them through authorized, short-lived download responses; never persist public URLs.
- Encrypt especially sensitive fields at the application layer when required by the threat model. Any encrypted field that must be searched needs a separate normalized keyed hash or another deliberate lookup design.
- Restrict candidate data by company and role through future Policies/Gates. Interviewers should receive only the data needed for assigned interviews.
- Validate uploads by MIME type, extension, size, and content scanning before making them available.
- Do not store raw mail-provider payloads, credentials, access tokens, password reset tokens, or secrets in business tables or audit logs.
- Define retention periods for rejected applications, resumes, notification content, IP addresses, and audit metadata. Retention must account for applicable privacy and employment law.
- Record consent source and timestamp where consent is the processing basis. Track erasure/anonymization without retaining the erased values.
- Use TLS for database connections in production, encrypted backups, least-privilege database credentials, and tested backup restoration.

## 12. Intentionally Excluded From This Scope

This scope creates documentation only. It does not create or modify migrations, models, controllers, Form Requests, Services, Policies, Gates, seeders, factories, routes, views, tests, packages, dashboards, authentication, or business modules. It also does not generate an ERD or modify `README.md`.

Future HTTP validation should use Laravel Form Requests. Controllers should remain thin, complex business workflows should be implemented in Services with transactions, and access control should use Policies/Gates. Those implementation decisions are documented here only as boundaries for later scopes.
