# Applicant Tracking System ERD

## Overview

This entity relationship diagram is derived from [database-design.md](database-design.md), [schema-blueprint.md](schema-blueprint.md), and [migration-plan.md](migration-plan.md). It is a documentation contract for the proposed Laravel 12 and MySQL schema, not a representation of implemented migrations.

```mermaid
erDiagram
    users {
        bigint id PK
        string public_id UK
        string name
        string email UK
        string status
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    roles {
        bigint id PK
        string name
        string slug UK
        boolean is_system
        timestamp created_at
        timestamp updated_at
    }

    permissions {
        bigint id PK
        string name
        string slug UK
        string group
        timestamp created_at
        timestamp updated_at
    }

    permission_role {
        bigint permission_id PK, FK
        bigint role_id PK, FK
        timestamp created_at
    }

    role_user {
        bigint id PK
        bigint user_id FK
        bigint role_id FK
        bigint company_id FK
        string company_scope_key
        bigint assigned_by_id FK
        timestamp created_at
        timestamp updated_at
    }

    companies {
        bigint id PK
        string public_id UK
        string name
        string slug UK
        string type
        string email
        string country_code
        string timezone
        string status
        bigint created_by_id FK
        bigint updated_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    departments {
        bigint id PK
        bigint company_id FK
        string name
        string code
        bigint manager_user_id FK
        string status
        bigint created_by_id FK
        bigint updated_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    job_posts {
        bigint id PK
        string public_id UK
        bigint company_id FK
        bigint department_id FK
        string title
        string slug
        string reference_code
        string employment_type
        string workplace_type
        string location_city
        string status
        timestamp published_at
        timestamp closes_at
        bigint hiring_manager_id FK
        bigint recruiter_id FK
        bigint created_by_id FK
        bigint updated_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    candidates {
        bigint id PK
        string public_id UK
        bigint company_id FK
        bigint user_id FK
        string first_name
        string last_name
        string email
        string phone
        string source
        string status
        date retention_until
        timestamp anonymized_at
        bigint created_by_id FK
        bigint updated_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    candidate_profiles {
        bigint id PK
        bigint candidate_id FK, UK
        string headline
        string city
        string country_code
        string current_job_title
        decimal years_experience
        decimal expected_salary
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    candidate_resumes {
        bigint id PK
        string public_id UK
        bigint candidate_id FK
        string disk
        string path
        string original_filename
        string mime_type
        bigint size_bytes
        string sha256
        bigint version
        boolean is_primary
        bigint uploaded_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    applications {
        bigint id PK
        string public_id UK
        bigint company_id FK
        bigint job_post_id FK
        bigint candidate_id FK
        bigint resume_id FK
        string source
        string status
        string current_stage
        decimal rating
        timestamp applied_at
        timestamp last_stage_changed_at
        bigint created_by_id FK
        bigint updated_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    application_stage_histories {
        bigint id PK
        bigint company_id FK
        bigint application_id FK
        string from_stage
        string to_stage
        string from_status
        string to_status
        bigint changed_by_id FK
        timestamp changed_at
        timestamp created_at
    }

    interview_schedules {
        bigint id PK
        string public_id UK
        bigint company_id FK
        bigint application_id FK
        string title
        string type
        string status
        timestamp starts_at
        timestamp ends_at
        string timezone
        bigint organizer_id FK
        bigint created_by_id FK
        bigint updated_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    interview_schedule_user {
        bigint interview_schedule_id PK, FK
        bigint user_id PK, FK
        string panel_role
        string attendance_status
        timestamp created_at
        timestamp updated_at
    }

    interview_feedback {
        bigint id PK
        bigint company_id FK
        bigint interview_schedule_id FK
        bigint reviewer_id FK
        string recommendation
        decimal score
        string status
        timestamp submitted_at
        timestamp created_at
        timestamp updated_at
    }

    offers {
        bigint id PK
        string public_id UK
        bigint company_id FK
        bigint application_id FK
        bigint version
        string status
        string job_title
        decimal salary_amount
        string salary_currency
        date proposed_start_date
        timestamp expires_at
        timestamp sent_at
        timestamp accepted_at
        timestamp declined_at
        bigint created_by_id FK
        bigint updated_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    email_notifications {
        bigint id PK
        string public_id UK
        bigint company_id FK
        bigint application_id FK
        bigint candidate_id FK
        bigint recipient_user_id FK
        string recipient_email
        string template_key
        string subject
        string status
        string provider_message_id
        bigint attempts
        timestamp scheduled_at
        timestamp sent_at
        timestamp failed_at
        bigint created_by_id FK
        timestamp created_at
        timestamp updated_at
    }

    audit_logs {
        bigint id PK
        bigint company_id FK
        bigint actor_id FK
        string event
        string auditable_type
        bigint auditable_id
        string request_id
        string ip_address
        timestamp created_at
    }

    users ||--o{ role_user : holds
    roles ||--o{ role_user : assigned
    companies o|--o{ role_user : scopes
    users o|--o{ role_user : assigns

    roles ||--o{ permission_role : receives
    permissions ||--o{ permission_role : grants

    users o|--o{ companies : maintains
    companies ||--o{ departments : contains
    users o|--o{ departments : manages
    users o|--o{ departments : maintains

    companies ||--o{ job_posts : owns
    departments o|--o{ job_posts : groups
    users o|--o{ job_posts : manages

    companies ||--o{ candidates : owns
    users o|--o{ candidates : links
    users o|--o{ candidates : maintains
    candidates ||--o| candidate_profiles : has
    candidates ||--o{ candidate_resumes : uploads
    users o|--o{ candidate_resumes : uploads

    companies ||--o{ applications : owns
    job_posts ||--o{ applications : receives
    candidates ||--o{ applications : submits
    candidate_resumes o|--o{ applications : selected
    users o|--o{ applications : maintains

    applications ||--o{ application_stage_histories : records
    companies ||--o{ application_stage_histories : scopes
    users o|--o{ application_stage_histories : changes

    applications ||--o{ interview_schedules : schedules
    companies ||--o{ interview_schedules : owns
    users o|--o{ interview_schedules : organizes
    users o|--o{ interview_schedules : maintains
    interview_schedules ||--o{ interview_schedule_user : includes
    users ||--o{ interview_schedule_user : interviews
    interview_schedules ||--o{ interview_feedback : receives
    companies ||--o{ interview_feedback : owns
    users ||--o{ interview_feedback : reviews

    applications ||--o{ offers : receives
    companies ||--o{ offers : owns
    users o|--o{ offers : maintains

    companies ||--o{ email_notifications : owns
    applications o|--o{ email_notifications : concerns
    candidates o|--o{ email_notifications : receives
    users o|--o{ email_notifications : receives
    users o|--o{ email_notifications : creates

    companies o|--o{ audit_logs : scopes
    users o|--o{ audit_logs : acts
```

## Relationship Notes

- `role_user` resolves the many-to-many relationship between users and roles. Its nullable `company_id` supports company-scoped assignments, while a null company represents platform scope. `company_scope_key` is the planned generated value used to enforce assignment uniqueness when `company_id` is null.
- `permission_role` resolves the many-to-many relationship between roles and permissions.
- A job post may belong to one department, but it always belongs directly to one company. Department and job company ownership must agree.
- A candidate may optionally link to a global `users` identity for a future candidate portal. Candidate records themselves remain company-owned.
- `candidate_profiles.candidate_id` is unique, producing an optional one-to-one profile. A candidate may have many versioned resumes.
- An application joins one job post and one candidate and may reference the resume selected at application time. All referenced records must belong to the same company.
- `application_stage_histories` is append-oriented and records both stage and broad status transitions.
- Interviewers are modeled through `interview_schedule_user`, not a single `interviewer_id`, because an interview may have a panel. `interview_feedback.reviewer_id` identifies the assigned interviewer providing feedback.
- The source schema uses `email_notifications.created_by_id` for the user who initiated a notification and `sent_at` for delivery time. It does not currently define a separate `sent_by_id`.
- Repeated `created_by_id` and `updated_by_id` columns are summarized as `users` maintaining the associated entity. These actor references are nullable so system operations and user removal do not erase business history.
- `audit_logs.auditable_type` and `auditable_id` form a polymorphic-style logical reference. They intentionally have no foreign key to audited business tables, allowing audit evidence to outlive target deletion.

## Cardinality Summary

| Parent | Child | Cardinality |
| --- | --- | --- |
| Users | Roles | Many-to-many through `role_user` |
| Roles | Permissions | Many-to-many through `permission_role` |
| Companies | Departments | One-to-many |
| Companies | Job posts | One-to-many |
| Departments | Job posts | Optional one-to-many |
| Companies | Candidates | One-to-many |
| Candidates | Candidate profiles | One-to-zero-or-one |
| Candidates | Candidate resumes | One-to-many |
| Job posts | Applications | One-to-many |
| Candidates | Applications | One-to-many |
| Applications | Stage histories | One-to-many |
| Applications | Interview schedules | One-to-many |
| Interview schedules | Users | Many-to-many through `interview_schedule_user` |
| Interview schedules | Interview feedback | One-to-many |
| Applications | Offers | One-to-many version history |
| Companies | Email notifications | One-to-many |
| Companies | Audit logs | One-to-many, with company optional for platform events |

## Tenant Boundary Notes

- Companies are the tenant root. Business data should carry `company_id` where practical, including jobs, candidates, applications, stage histories, interviews, feedback, offers, notifications, and company-level audit records.
- Global identities, roles, and permissions are not owned by one company. `role_user` supplies the company context for tenant-level access.
- Foreign keys establish row existence but do not prove that related rows share a company. Future write workflows must use tenant-scoped queries and transactional consistency checks.
- Public ULIDs provide non-sequential external references, but they do not replace tenant authorization or internal primary keys.
- Candidate identity, feedback, salary, offer, and notification data is confidential. Candidate resumes and offer documents must use private storage disks and authorized, short-lived download responses, never public storage paths.

## Workflow Notes

- Workflow statuses and stages remain string-based columns backed by application allowlists or value objects, not MySQL `ENUM` types.
- `applications.status` stores the broad lifecycle outcome, while `applications.current_stage` stores the active hiring-pipeline position.
- Every application stage transition should update the current snapshot and append an `application_stage_histories` record in the same transaction.
- Interview status, feedback submission, offer versioning, and notification delivery each have independent string-based lifecycles.
- Rules such as one primary resume per candidate, one submitted review per interviewer and interview, and one accepted offer per application require transactional enforcement in future Services.

## Implementation Notes for Future Migration Scope

- This file is documentation only. It does not create tables, constraints, models, or application behavior.
- Future migrations should follow the dependency order in [migration-plan.md](migration-plan.md), declare foreign-key delete actions explicitly, and preserve the indexes and uniqueness rules in [schema-blueprint.md](schema-blueprint.md).
- Laravel Form Requests will handle input validation in a later scope.
- Services will handle complex workflow rules, tenant consistency, transactions, and concurrency-sensitive invariants.
- Policies and Gates will enforce tenant and resource access. Role assignments alone are not sufficient authorization.
- Controllers should remain thin and delegate validation, authorization, and business operations to those layers.
- Migration and rollback verification must eventually run against MySQL, especially for generated columns, check constraints, collations, and nullable unique behavior.
