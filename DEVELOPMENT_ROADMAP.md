# COOU SIWES Portal Development Roadmap

This roadmap is the execution plan for building the SIWES portal from scratch to production. Phases must be completed in order. A later phase must not begin until the current phase meets its exit criteria, passes tests, and has been reviewed.

## Phase 0: Discovery, Product Scope, And UI Direction

### Objective

Lock the product scope, user journeys, visual direction, and technical decisions before implementation starts.

### Deliverables

- Confirm all workflows from the `UI/` screenshots:
  - landing page
  - admin dashboard
  - student management
  - add student
  - bulk student upload
  - posting-list download
  - ticket management
  - supervisor management
  - supervisor assignment
  - feedback
  - Korapay payment history/details
  - faculty/course management
  - password management
- Define final roles: `super-admin`, `admin`, `supervisor`, `student`.
- Define permission matrix for every module.
- Define database entities and relationships.
- Define UI design system direction:
  - institutional green identity
  - futuristic accents
  - light/dark mode
  - animated login/landing surfaces
  - accessible dashboard/table/form components
- Confirm deployment target, domain, mail/SMS provider, database, object storage, and Korapay account details.

### Exit Criteria

- `DEVELOPMENT_GUIDE.md` is approved.
- Role and permission matrix is approved.
- All core modules are listed with no unresolved blocking questions.
- Korapay is confirmed as the payment gateway.
- Project environment requirements are documented.

### Do Not Proceed Until

The scope, roles, payment provider, and deployment assumptions are confirmed.

## Phase 1: Project Foundation And Tooling

### Objective

Create a clean Laravel foundation with frontend tooling, quality tooling, environment configuration, and baseline CI.

### Deliverables

- Create Laravel 13 application.
- Configure PHP 8.4+, Composer, Node, Vite, Tailwind CSS 4.3.3.
- Configure database, Redis, cache, queues, mail, logs, and filesystem storage.
- Install and configure:
  - Laravel auth foundation
  - Spatie Laravel Permission
  - Laravel Sanctum if API endpoints are needed
  - Pest
  - Laravel Pint
  - Larastan/PHPStan
  - Rector
  - ESLint and Prettier
- Create base folder conventions for Actions, Services, DTOs, Policies, Jobs, Events, Listeners, and View Components.
- Create CI workflow for install, lint, static analysis, and tests.
- Add seeders for baseline roles and permissions.
- Add local development documentation.

### Exit Criteria

- App boots locally.
- Database migrations run cleanly.
- Tailwind/Vite build works.
- CI passes.
- Roles and permissions seed successfully.
- `php artisan test`, Pint, and static analysis run without critical failures.

### Do Not Proceed Until

The application foundation is stable, repeatable, and covered by baseline quality checks.

## Phase 2: Design System, Layout Shells, And Shared Components

### Objective

Build the reusable UI foundation before module screens are implemented.

### Deliverables

- Create theme tokens for colors, spacing, radius, shadows, glow effects, typography, and z-index layers.
- Implement light and dark mode with persisted preference.
- Build responsive app shells:
  - public landing shell
  - auth shell
  - admin shell
  - supervisor shell
  - student shell
- Build shared UI components:
  - buttons
  - inputs/selects/date fields
  - cards
  - tables
  - filters
  - badges
  - alerts
  - toasts
  - modals
  - drawers
  - tabs
  - dropdown menus
  - upload dropzones
  - pagination
  - empty states
  - chart wrappers
- Add controlled animations, glowing focus states, and particle background support for public/auth pages.
- Add accessibility standards for focus states, labels, contrast, keyboard navigation, and reduced motion.

### Exit Criteria

- All shared components render correctly in light and dark mode.
- Components are responsive on mobile, tablet, and desktop.
- No text overlaps or layout breaks in core shells.
- Accessibility smoke checks pass.
- Playwright visual smoke tests exist for public, auth, and dashboard shells.

### Do Not Proceed Until

The UI system is reusable enough that module screens can be built consistently without ad hoc styling.

## Phase 3: Authentication, OTP, Roles, And Security Core

### Objective

Implement secure login flows and authorization before protected modules are built.

### Deliverables

- Implement separate login entry points for:
  - admin
  - supervisor
  - student
- Back all login flows with one secure auth system.
- Implement role-aware redirects.
- Implement OTP challenge flow for login and sensitive actions.
- Implement password reset, email verification, password confirmation, and logout from all sessions.
- Implement rate limiting for login, OTP resend, and password reset.
- Implement account statuses: active, inactive, suspended, pending activation.
- Implement policies and middleware for role/permission enforcement.
- Implement audit logging foundation.
- Add session security settings and secure production cookie configuration.

### Exit Criteria

- Each role logs in from its own portal and lands on the correct dashboard.
- Cross-role route access is denied.
- OTP flow works and expires correctly.
- Rate limits are tested.
- Permission middleware and policies are covered by feature tests.
- Audit logs record login, logout, failed login, OTP events, and role changes.

### Do Not Proceed Until

No protected module can be accessed without the correct authenticated role and permission.

## Phase 4: Academic Structure And Admin Configuration

### Objective

Build the academic foundation used by student, supervisor, ticket, payment, and reporting modules.

### Deliverables

- CRUD for faculties.
- CRUD for departments.
- CRUD for courses.
- CRUD for levels, academic years, and sessions.
- Active session configuration.
- Validation for duplicate or invalid academic mappings.
- Soft delete and restore where records are referenced.
- Settings framework for site, academic, upload, OTP, payment, theme, and notification configuration.
- Audit logs for all configuration changes.

### Exit Criteria

- Super admin and permitted admins can manage academic data.
- Invalid deletes are blocked safely.
- Active academic session drives filters and defaults.
- Settings are permission protected and audited.
- Feature tests cover CRUD, validation, permissions, and audit logs.

### Do Not Proceed Until

Academic data and system settings are reliable enough to support student imports and assignments.

## Phase 5: Student Management And Bulk Upload

### Objective

Implement full student administration, including manual creation, search, activation status, and bulk upload.

### Deliverables

- Student list with search, filters, sorting, pagination, and status badges.
- Student create/edit/detail/suspend/reactivate workflows.
- Student profile model and academic mapping.
- CSV/XLSX template downloads.
- Bulk upload with:
  - file validation
  - preview
  - duplicate detection
  - queued processing
  - row-level error report
  - import history
- Auto-generation rules for student ID/matric number where required.
- Export student list and posting-list data.
- Notifications for student creation/import/activation where applicable.

### Exit Criteria

- Manual student CRUD works.
- Bulk import handles valid files, invalid files, duplicates, and partial failures.
- Large imports run through queues.
- Student search/filter performance is acceptable.
- Feature tests cover student CRUD, import validation, permissions, and exports.
- Browser tests cover add student, bulk upload, and student listing.

### Do Not Proceed Until

Student records can be created, imported, searched, exported, secured, and audited without data integrity issues.

## Phase 6: Ticket Activation And Korapay Payments

### Objective

Implement student activation tickets and verified Korapay payments.

### Deliverables

- Ticket generation for single and bulk students.
- Ticket statuses: generated, assigned, paid, used, expired, revoked.
- Hashed ticket codes and secure verification.
- Configurable ticket amount and validity period.
- Korapay checkout initialization.
- Korapay payment verification.
- Korapay webhook handler with signature verification and idempotency.
- Payment history with filters by status, date, student, reference, and academic session.
- Payment details and printable receipt.
- Payment exports.
- Notifications for payment success, failure, ticket activation, and suspicious payment events.

### Exit Criteria

- Ticket generation is permission protected and audited.
- Students can complete activation payment through Korapay.
- Server-side verification controls payment status.
- Duplicate webhooks do not duplicate records or activate twice.
- Failed, abandoned, and expired payment cases are handled.
- Feature tests cover ticket lifecycle, Korapay verification, webhook idempotency, and permissions.
- Browser tests cover payment history and payment details.

### Do Not Proceed Until

Activation and payment records are accurate, secure, auditable, and resilient to webhook retries.

## Phase 7: Supervisor Management And Assignment

### Objective

Implement supervisor administration and student-supervisor assignment workflows.

### Deliverables

- Supervisor list with search, filters, sorting, pagination, and performance metrics.
- Supervisor create/edit/detail/suspend/reactivate workflows.
- Assign supervisor to student.
- Revoke supervisor assignment.
- Bulk assignment by faculty, department, course, level, or academic year.
- Capacity tracking and assignment conflict prevention.
- Current assignment list with filters and bulk actions.
- Export supervisor analytics and assignment lists.
- Notifications for assignment and revocation.

### Exit Criteria

- Supervisor CRUD is permission protected.
- Assignment and revocation workflows preserve history.
- A student cannot have conflicting active assignments unless explicitly allowed by configuration.
- Supervisor capacity rules are enforced.
- Feature tests cover CRUD, assignment, revocation, capacity, permissions, and audit logs.
- Browser tests cover add supervisor and assignment workflows.

### Do Not Proceed Until

Supervisor records and assignments are reliable enough for assessment and feedback workflows.

## Phase 8: Supervisor And Student Portals

### Objective

Build complete role-specific dashboards and self-service workflows for supervisors and students.

### Deliverables

- Student dashboard:
  - activation status
  - profile completion
  - payment/ticket status
  - posting details
  - assigned supervisor
  - feedback history
  - notifications
- Student profile update workflow.
- Supervisor dashboard:
  - assigned students
  - pending assessments
  - submitted feedback
  - performance summary
  - notifications
- Supervisor student detail view.
- Role-specific navigation and permissions.

### Exit Criteria

- Students only see their own records.
- Supervisors only see assigned students.
- Dashboards reflect real database state.
- Role-specific notifications work.
- Feature tests cover data isolation and route protection.
- Browser tests cover student and supervisor dashboard flows.

### Do Not Proceed Until

Student and supervisor self-service portals are functional, secure, and role isolated.

## Phase 9: Feedback, Assessment, And Reporting

### Objective

Implement supervisor feedback, student assessment, and reporting workflows.

### Deliverables

- Configurable assessment rubric.
- Supervisor assessment submission.
- Feedback comments and status workflow.
- Student feedback visibility.
- Admin moderation/review where required.
- Attachment support if needed.
- Reporting dashboards for:
  - supervisor performance
  - student completion
  - assessment score distribution
  - payment and activation trends
  - faculty/course distribution
- Exportable reports.

### Exit Criteria

- Assessment forms are configurable by permitted admins.
- Supervisors can submit assessments only for assigned students.
- Students can view only their own feedback.
- Reports match source data.
- Feature tests cover assessment permissions, visibility, reporting, and exports.
- Browser tests cover feedback submission and student feedback view.

### Do Not Proceed Until

Assessment and reporting data is permission-safe and trustworthy.

## Phase 10: Super Admin Control Center

### Objective

Deliver a flexible, configurable super-admin panel for managing admins, roles, permissions, settings, modules, and audit visibility.

### Deliverables

- Admin user management.
- Role builder.
- Permission assignment UI.
- Admin suspension/reactivation.
- Module/feature configuration.
- Theme and branding configuration.
- Payment, OTP, upload, notification, and academic settings.
- Advanced audit log viewer with filters and exports.
- System health widgets for queues, scheduler, failed jobs, and webhook status.

### Exit Criteria

- Super admin can create admins and assign roles/permissions.
- Admin permissions immediately affect access.
- Critical configuration changes require password confirmation and OTP where appropriate.
- All super-admin actions are audited.
- Tests cover role creation, permission assignment, admin suspension, settings, and audit exports.

### Do Not Proceed Until

Administrative control is flexible, secure, auditable, and does not require code changes for normal configuration.

## Phase 11: Notifications, Realtime, And User Experience Polish

### Objective

Finish real-time behavior, user feedback, animation polish, and responsive quality.

### Deliverables

- Laravel database notifications.
- Real-time push notifications with Laravel Reverb/Echo.
- Notification center with unread counts, filters, and mark-as-read.
- Toast system for success, warning, error, info, and security alerts.
- Refined modal, drawer, table, upload, and chart interactions.
- Futuristic visual polish:
  - subtle particles
  - glow states
  - animated counters
  - smooth page transitions
  - skeleton loaders
  - accessible reduced-motion fallbacks
- Cross-device responsive refinements.

### Exit Criteria

- Notifications are delivered to the correct users only.
- Real-time updates work for key events.
- UI remains usable with reduced motion.
- No layout overlap on mobile, tablet, or desktop.
- Playwright visual and interaction smoke tests pass.

### Do Not Proceed Until

The portal feels consistent, modern, responsive, and stable across the primary workflows.

## Phase 12: QA, Security Hardening, And Performance

### Objective

Stabilize the product before production deployment.

### Deliverables

- Full Pest test suite.
- Full browser smoke suite.
- Static analysis cleanup.
- Formatting cleanup.
- Security review:
  - auth
  - OTP
  - permissions
  - file uploads
  - Korapay webhooks
  - signed downloads
  - rate limits
  - session/cookie settings
- Performance review:
  - slow queries
  - indexes
  - pagination
  - queues
  - cache
  - asset bundle size
- Backup and restore test.
- Production runbook.
- Rollback plan.

### Exit Criteria

- CI passes fully.
- No known critical or high security issues remain.
- Core pages meet agreed performance targets.
- Backup restore has been tested.
- Deployment runbook is complete.
- Stakeholder UAT issues are resolved or explicitly deferred.

### Do Not Proceed Until

The system is ready for production with documented operational procedures.

## Phase 13: Deployment And Launch

### Objective

Deploy the SIWES portal to production and verify all critical workflows.

### Deliverables

- Production server or managed platform provisioned.
- Environment variables configured securely.
- Database migrated.
- Assets built.
- Queues, scheduler, Horizon, logs, and Reverb configured.
- Storage and backups configured.
- SSL/HTTPS enabled.
- Korapay live keys and webhook URL configured.
- Smoke test checklist executed:
  - public landing page
  - admin login + OTP
  - supervisor login + OTP
  - student login + OTP
  - student list
  - bulk upload dry run
  - ticket generation
  - Korapay payment verification
  - supervisor assignment
  - feedback submission
  - notifications
  - exports
- Monitoring and alerting enabled.

### Exit Criteria

- Production smoke tests pass.
- Queue workers and scheduler are running.
- Webhooks are reachable and verified.
- Backups are running.
- Monitoring is active.
- Rollback procedure is ready.

### Do Not Proceed Until

Production is verified by the technical team and approved for use.

## Phase 14: Post-Launch Support And Iteration

### Objective

Monitor the launched system, fix launch issues, and plan future improvements without destabilizing production.

### Deliverables

- Launch support window.
- Daily review of logs, failed jobs, payment webhooks, and user issues.
- Bug triage board.
- Hotfix process.
- User feedback collection.
- Deferred feature list.
- Post-launch report.

### Exit Criteria

- No unresolved launch-blocking defects remain.
- Payment, activation, supervisor assignment, and login flows are stable.
- Support documentation is updated.
- Next development cycle is prioritized.

### Do Not Proceed Until

The live system is stable and the team has agreed on the next improvement cycle.

## Mandatory Phase Gate Rule

Every phase must close with:

- Code review.
- Passing automated tests relevant to that phase.
- Updated documentation.
- Updated seeders/factories where data changed.
- No unresolved critical or high severity defects.
- Approval to proceed to the next phase.

If a phase fails any exit criterion, development stays in that phase until the issue is corrected.

