# ATS Migration Plan

## 1. Purpose

This plan describes how the schema in [schema-blueprint.md](schema-blueprint.md) should be introduced in a future migration scope. It does not create or execute migrations.

The order is dependency-driven: referenced parent tables must exist before child foreign keys are created. Closely related pivots and histories follow their owning tables so rollback remains understandable.

## 2. Recommended Migration Creation Order

1. `users`
2. `roles`
3. `permissions`
4. `permission_role`
5. `companies`
6. `role_user`
7. `departments`
8. `job_posts`
9. `candidates`
10. `candidate_profiles`
11. `candidate_resumes`
12. `applications`
13. `application_stage_histories`
14. `interview_schedules`
15. `interview_schedule_user`
16. `interview_feedback`
17. `offers`
18. `email_notifications`
19. `audit_logs`

The Laravel skeleton may already contain migrations for `users`, password reset tokens, sessions, cache, and jobs. In the future migration scope, inspect those files before creating anything. Amend an unshipped initial migration or add a forward-only alteration migration according to project history; do not create a duplicate `users` table.

## 3. Why the Order Matters

- `users` must exist before actor, manager, recruiter, organizer, reviewer, and audit references.
- `roles` and `permissions` must exist before their permission pivot.
- `companies` depends on `users` for creator/updater references, then becomes the tenant parent for most business tables.
- `role_user` follows both authorization definitions and companies because assignments can be tenant-scoped.
- `departments` follows companies and precedes job posts.
- Candidates and their files must exist before applications can select a resume.
- Applications require companies, jobs, candidates, resumes, and users.
- Stage history, interviews, and offers require applications.
- Interview panel assignments require interviews and users; feedback follows the panel table conceptually even though its foreign keys point to interviews and users.
- Notifications follow applications and candidates because either may provide optional context.
- Audit logs are last so every potential actor and tenant parent exists. Auditable targets remain logical references and do not create migration dependencies.

## 4. Suggested Artisan Commands

Run these only in the future migration implementation scope, after reconciling Laravel's existing migrations:

```powershell
php artisan make:migration create_roles_table
php artisan make:migration create_permissions_table
php artisan make:migration create_permission_role_table
php artisan make:migration create_companies_table
php artisan make:migration create_role_user_table
php artisan make:migration create_departments_table
php artisan make:migration create_job_posts_table
php artisan make:migration create_candidates_table
php artisan make:migration create_candidate_profiles_table
php artisan make:migration create_candidate_resumes_table
php artisan make:migration create_applications_table
php artisan make:migration create_application_stage_histories_table
php artisan make:migration create_interview_schedules_table
php artisan make:migration create_interview_schedule_user_table
php artisan make:migration create_interview_feedback_table
php artisan make:migration create_offers_table
php artisan make:migration create_email_notifications_table
php artisan make:migration create_audit_logs_table
```

If the existing `users` migration has already run in a shared or production-like environment, use a clearly named alteration instead of editing migration history:

```powershell
php artisan make:migration add_ats_identity_fields_to_users_table --table=users
```

After implementation, verification commands should include:

```powershell
php artisan migrate:status
php artisan migrate --pretend
php artisan migrate
php artisan schema:dump
```

`schema:dump` is optional and should only be committed when the team deliberately adopts Laravel schema squashing. Never run a production migration without a database backup, deployment review, and tested rollback or forward-fix plan.

## 5. Foreign-Key Dependency Notes

### Delete actions

- Use `CASCADE` for pure dependents: permission-role grants, company role assignments, candidate profiles/resumes, application histories, and interview panel memberships.
- Use `RESTRICT` for business records whose accidental deletion would erase context: companies, departments referenced by active jobs, jobs/candidates referenced by applications, applications referenced by interviews/offers, and reviewers referenced by feedback.
- Use `SET NULL` for attribution and optional context: creators, updaters, managers, recruiters, organizers, notification context, and audit actors.
- Do not rely on soft deletes to trigger foreign-key actions. Soft deletion is an update and must be handled by application rules.

### Tenant consistency

Single-column foreign keys prove that a row exists, but they do not prove that two referenced rows share a `company_id`. Future Services must perform tenant-scoped lookups and write related changes in a transaction. Composite tenant foreign keys can be considered later, but they increase index and migration complexity and should be adopted consistently rather than piecemeal.

### Nullable unique values

MySQL permits multiple null values in a unique index. This is acceptable for optional company codes, job reference codes, and offer document paths. It is not acceptable for global role assignments, so `role_user.company_scope_key` is a stored generated column used by the assignment unique constraint.

### Polymorphic audit targets

`audit_logs.auditable_type` and `auditable_id` intentionally have no foreign key. This lets audit evidence outlive target deletion and avoids coupling the audit table to every business table. Use a stable morph map in a future application scope rather than storing PHP class names directly.

## 6. Migration Implementation Guidelines

- Use Laravel schema builder methods where practical: `id()`, `foreignId()`, `ulid()`, `string()`, `decimal()`, `json()`, `timestamps()`, and `softDeletes()`.
- Specify constrained table names where Laravel inference would be ambiguous, such as `manager_user_id` and `created_by_id`.
- Declare foreign-key delete behavior explicitly; do not accept accidental defaults.
- Add indexes with their owning table migration and name complex composite indexes clearly.
- Use raw SQL only for a requirement the schema builder cannot express cleanly, such as a specific generated-column expression or check constraint. Keep any raw SQL compatible with the supported MySQL version.
- Keep migrations structural. Do not embed role seeding, permission catalogs, or large data transformations in table-creation migrations.
- In later application scopes, use Form Requests for validation, Services for complex transactional workflows, and Policies/Gates for access control. Keep controllers thin.

## 7. Rollback Considerations

- Each `down()` method must reverse only its own `up()` method.
- Drop child tables before parents. Laravel will naturally reverse timestamped migrations, provided the creation order is preserved.
- Drop foreign keys and generated-column-dependent indexes before dropping or altering their columns.
- Treat rollback of a populated production schema as potentially destructive. Prefer a tested forward fix for migrations that have already stored recruitment data.
- Never disable foreign-key checks as a routine rollback strategy. It can conceal incorrect order and leave orphaned rows.
- File rows and physical files have different lifecycles. Rolling back a table does not delete private resume or offer files; any cleanup must be a separate, reviewed operation.
- Audit and stage-history rollback destroys evidence. Back up these tables before any destructive rollback in a non-ephemeral environment.
- Test a complete `migrate:fresh` and rollback cycle against MySQL, not only SQLite, because generated columns, check constraints, index lengths, and collations differ.

Suggested development-only checks after the migrations are eventually implemented:

```powershell
php artisan migrate:fresh
php artisan migrate:rollback --step=1
php artisan migrate
```

Do not use `migrate:fresh` against a shared, staging, or production database.

## 8. Common Mistakes to Avoid

- Creating duplicate Laravel framework tables instead of reviewing existing migrations first.
- Using MySQL `ENUM` for workflow states, making future state changes unnecessarily invasive.
- Omitting `company_id` from tenant-owned tables or performing unscoped lookups by public ID.
- Assuming a foreign key guarantees cross-table company consistency.
- Cascading deletion from companies, jobs, candidates, or applications into legally relevant business history.
- Making actor columns non-null, which prevents retention when a user is removed.
- Using floating-point columns for salary, ratings, or monetary values.
- Storing local schedule times without UTC conversion and the original IANA time zone.
- Storing resume bytes, public file URLs, credentials, tokens, or raw provider payloads in business tables.
- Adding isolated low-cardinality indexes while missing tenant-first composite indexes used by real queries.
- Creating redundant indexes already covered by a unique or composite index's leftmost columns.
- Expecting a unique nullable column to allow only one null in MySQL.
- Updating application stage without writing stage history in the same transaction.
- Allowing multiple primary resumes or accepted offers through race-prone read-then-write code without row locks.
- Logging passwords, tokens, full candidate records, or sensitive unchanged fields in audit JSON.
- Putting validation and workflow logic in controllers instead of future Form Requests and Services.
- Treating role membership alone as authorization instead of applying future Policies/Gates to the resource and tenant.

## 9. Scope Boundary

This plan is documentation-only. No migration command in this document has been run, and no migration, model, controller, request, service, seeder, factory, route, view, test, package, authentication feature, or ERD has been created. `README.md` remains unchanged.
