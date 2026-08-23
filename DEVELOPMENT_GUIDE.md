# COOU SIWES Portal Development Guide

## 1. Product Direction

Build a professional Student Industrial Work Experience Scheme management portal for Chukwuemeka Odumegwu Ojukwu University. The current UI screenshots in `UI/` show the required functional scope: admin dashboard, student management, student creation, bulk upload, posting-list downloads, tickets, supervisor management and assignment, feedback, payment history/details, faculty/course management, password change, and a public landing page.

The new implementation should keep the institutional green identity but raise the interface quality substantially: modern responsive dashboards, dark and light modes, smooth transitions, subtle particle backgrounds, glowing focus states, high quality modals, actionable alerts, real-time notifications, analytics cards, configurable tables, and role-specific workflows.

## 2. Target Stack

Use the latest stable platform versions available at project start:

- Backend: Laravel 13.x, PHP 8.4+, MySQL 8 or PostgreSQL 17.
- Frontend: Laravel Blade or Inertia + Vue/React, Tailwind CSS 4.3.3, Vite, Alpine.js only for small Blade interactions.
- Auth and security: Laravel Fortify or Laravel starter kit foundations, Laravel Sanctum for session/API auth where needed, Spatie Laravel Permission 8.x for roles and permissions.
- UI behavior: Livewire 4 or Inertia for reactive admin screens, Chart.js or ApexCharts for analytics, Laravel Echo with Reverb for real-time notifications.
- Jobs and queues: Redis, Laravel Horizon, queued mail/SMS/OTP/exports/imports.
- Imports/exports: Laravel Excel, queued batch imports, CSV validation reports.
- QA: Pest, Laravel Pint, PHPStan/Larastan, Rector, ESLint, Prettier, Playwright for browser flows.
- Deployment: Dockerized Laravel app, Nginx, PHP-FPM, Redis, database backups, CI/CD via GitHub Actions or equivalent.

Primary references checked on July 31, 2026: Laravel 13 release docs, Tailwind CSS official site/npm, Laravel starter kit docs, Spatie Laravel Permission metadata.

## 3. User Roles And Authentication

Implement one user table with role-based access, not separate user models unless there is a hard institutional data requirement. This keeps permissions, auditing, password policy, and OTP enforcement consistent.

Required roles:

- `super-admin`: full system ownership, creates admins, assigns roles, manages permissions, configures modules, views audit logs, overrides blocked workflows.
- `admin`: manages assigned administrative modules based on permissions.
- `supervisor`: manages assigned students, assessments, feedback, reports, and placement progress.
- `student`: activates account, completes profile, manages SIWES details, views posting/payment/ticket status, receives feedback.

Authentication requirements:

- Separate login portals or role tabs for Admin, Supervisor, and Student, all backed by the same secure auth layer.
- OTP verification for sensitive login attempts and critical actions.
- Email verification, password reset, password confirmation, rate limiting, session invalidation, and device/session management.
- Optional passkeys and SSO can be added after the core role flows are stable.
- Login should redirect users to their correct dashboard and deny cross-role route access.

Authorization requirements:

- Use Spatie Permission roles and granular permissions.
- Seed default permissions by module: `students.view`, `students.create`, `students.import`, `students.export`, `tickets.generate`, `supervisors.assign`, `payments.view`, `settings.update`, `admins.manage`, etc.
- Super admin must be able to create custom roles, assign permissions, suspend admins, and inspect permission changes.
- Protect every controller action through policies, gates, middleware, and feature tests.

## 4. Core Modules

### Admin Dashboard

- KPIs: total students, activated students, supervisors, tickets generated/used, payments, pending issues.
- Charts: student distribution by faculty/course/year/gender, ticket usage, supervisor performance, payment trend, activation trend.
- Filters by session, academic year, faculty, department, course, and status.
- Exportable analytics.
- Configurable dashboard widgets per admin role.

### Student Management

- Searchable, paginated, sortable student table.
- Student detail profile with academic, contact, placement, activation, ticket, and supervisor history.
- Add/edit/suspend/reactivate student.
- Bulk upload via CSV/XLSX with template downloads, preview validation, duplicate detection, and import error report.
- Auto-generate student ID and matric number where required by business rules.
- Download student posting list by faculty, department, course, session, and supervisor.

### Supervisor Management

- Add/edit/suspend supervisor.
- Assign and revoke supervisor-to-student relationships.
- Track supervisor capacity, assigned students, submitted feedback, payment status, and performance rating.
- Bulk assignment rules by department/course/year.
- Export supervisor analytics.

### Faculty, Department, And Course Management

- CRUD for faculties, departments, courses, levels, sessions, and academic years.
- Soft delete with restore where records are referenced.
- Validation to prevent duplicate course/faculty mappings.
- Configurable active academic session.

### Ticket And Activation

- Generate activation tickets singly or in bulk.
- Ticket statuses: generated, assigned, paid, used, expired, revoked.
- Ticket verification logs with IP/device metadata.
- Configurable ticket amount, validity window, and payment provider.

### Payments

- Integrate Korapay first for Nigerian card/bank/transfer/USSD payments, with provider abstraction for future gateways.
- Store payment reference, provider response, amount, currency, status, student, ticket, and verified timestamp.
- Verify payments server-side using provider API and webhook signatures.
- Payment history filters by status, date, student, reference, academic session.
- Export CSV/XLSX and printable receipts.

### Feedback And Assessment

- Supervisor assessment forms configured by admin.
- Student feedback history and admin moderation.
- Rubric scoring, comments, attachments, approval status, and audit trail.
- Notifications to students/admins when feedback is submitted or returned.

### Notifications

- Database notifications for all users.
- Real-time push notifications through Laravel Reverb/Echo.
- Email and optional SMS for OTP, activation, payment, assignment, and security events.
- In-app notification center with unread counts, filters, and mark-as-read controls.

### Settings And Configuration

- Admin-configurable site name, logo, theme, academic session, payment amount, OTP TTL, upload limits, notification channels, and maintenance banners.
- Feature flags for modules that may not launch immediately.
- Every setting change must be audited.

## 5. UI/UX Standard

Use the screenshots as workflow references, not as final visual quality.

Design principles:

- Preserve university green as the primary identity color, balanced with graphite, white, electric cyan, emerald, and amber accents.
- Provide first-class light and dark themes with CSS variables and persisted user preference.
- Use a responsive app shell: collapsible sidebar on desktop, bottom or drawer navigation on mobile.
- Build dense operational pages for admin work: filters, tables, bulk actions, status badges, and quick actions should be easy to scan.
- Use futuristic polish in controlled areas: animated login background, particle field on landing/auth pages, glowing active nav/focus states, micro-interactions on cards/buttons.
- Avoid letting animation reduce readability or performance. Respect `prefers-reduced-motion`.
- Use reusable components for cards, tables, filters, modals, alerts, drawers, empty states, upload dropzones, charts, and confirmation dialogs.

Screen requirements:

- Landing page with clear SIWES identity, public role entry points, feature preview, and institutional trust signals.
- Auth screens for admin, supervisor, and student with OTP step and clear error states.
- Role dashboards with only the widgets relevant to that role.
- Data tables with column visibility, sorting, filtering, pagination, export, and saved views.
- Modal and drawer forms with validation summaries and inline field errors.
- Toasts and alerts for success, warning, error, info, and security-sensitive events.

Accessibility:

- WCAG AA contrast in both themes.
- Keyboard navigation for menus, modals, tables, and forms.
- Visible focus rings.
- Semantic headings, labels, and ARIA where needed.
- No text overlap on mobile or desktop.

## 6. Suggested Data Model

Core tables:

- `users`: name, email, phone, password, status, last_login_at, otp_enabled, metadata.
- `profiles`: user_id, avatar, address, gender, date_of_birth, nationality.
- `students`: user_id, matric_no, faculty_id, department_id, academic_level_id, academic_session_id, activation_status.
- `supervisors`: user_id, staff_no, organization, bank details if required, status, capacity.
- `faculties`, `departments`, `courses`, `academic_years`, `sessions`.
- `supervisor_student`: supervisor_id, student_id, assigned_by, assigned_at, revoked_at.
- `tickets`: code_hash, student_id, amount, status, expires_at, used_at.
- `payments`: student_id, ticket_id, provider, reference, amount, currency, status, payload.
- `assessments`: supervisor_id, student_id, score, status, submitted_at.
- `assessment_items`, `feedback`, `attachments`.
- `settings`, `audit_logs`, `notifications`, `otp_challenges`, `imports`, `exports`.

Model rules:

- Use UUID/ULID public identifiers where appropriate; keep numeric IDs internal.
- Use soft deletes for administrative records.
- Add database constraints and indexes for searchable columns and unique identifiers.
- Store sensitive ticket/OTP values hashed, never as plaintext.

## 7. Architecture And Coding Standards

Backend structure:

- Keep controllers thin.
- Use Form Requests for validation.
- Use Actions or Services for business operations: ticket generation, student import, payment verification, supervisor assignment.
- Use Policies for model authorization.
- Use Events and Listeners for notifications and audit logging.
- Use Jobs for imports, exports, mail, SMS, analytics recomputation, and webhook retries.
- Use API Resources for JSON endpoints.

Frontend structure:

- Create a component library under `resources/js/components` or Blade components under `resources/views/components`.
- Centralize layout shells by role.
- Keep charts and tables reusable.
- Use Tailwind theme variables instead of hardcoded color repetition.
- Keep animation utilities documented and limited.

Quality standards:

- Laravel Pint must pass.
- Larastan/PHPStan level should be raised progressively and enforced in CI.
- Pest tests for auth, permissions, imports, payments, tickets, assignments, and settings.
- Playwright tests for login, OTP, role redirects, bulk upload, payment history, supervisor assignment, and dark mode.
- Seeders and factories must support realistic development data.

## 8. Security Requirements

- Enforce HTTPS in production.
- Secure cookies, strict session settings, CSRF protection, and SameSite cookies.
- Rate limit login, OTP resend, payment verification, imports, and exports.
- OTP expiry, retry limits, replay protection, and audit logs.
- Password policy with breach checks if available.
- Audit every admin action: create/update/delete, role change, permission change, payment verification, ticket generation, supervisor assignment.
- Validate and sanitize file uploads; scan type/extension/content; store outside public paths unless explicitly public.
- Use signed URLs for sensitive downloads.
- Verify webhook signatures and make handlers idempotent.
- Use least-privilege database and queue credentials.
- Keep secrets in environment variables, never in git.

## 9. Development Phases

### Phase 1: Foundation

- Create Laravel 13 project.
- Configure database, Redis, mail, queues, logging, storage, and Vite.
- Install Tailwind CSS 4, auth foundation, Spatie Permission, Pest, Pint, Larastan.
- Build base layout, theme tokens, auth pages, role redirects, seed roles/permissions.

### Phase 2: Admin Core

- Admin dashboard, student CRUD, faculty/course/session CRUD.
- Bulk upload templates and queued import.
- Audit logging and notification center.
- Feature tests for admin permissions.

### Phase 3: Student And Supervisor Workflows

- Student activation, profile completion, ticket status, posting details.
- Supervisor dashboard, assigned students, feedback/assessment submission.
- Assignment/revocation workflows and analytics.

### Phase 4: Payments And Notifications

- Korapay integration, payment verification, webhooks, receipts.
- Real-time database notifications.
- OTP enforcement for sensitive flows.

### Phase 5: Configurable Super Admin Panel

- Role and permission builder.
- Module configuration.
- Site/theme/payment/upload/notification settings.
- Advanced audit log filters and exports.

### Phase 6: QA, Hardening, And Deployment

- Complete automated test suite.
- Browser QA across desktop and mobile.
- Security review, rate-limit review, and backup restore test.
- Production deployment with CI/CD, queues, scheduler, Horizon, and monitoring.

## 10. Deployment Standard

Production infrastructure:

- Nginx + PHP-FPM container or managed Laravel host.
- MySQL/PostgreSQL managed database.
- Redis for cache, queues, sessions, broadcasting.
- Object storage for uploads and exports.
- Queue workers managed by Supervisor/systemd or container orchestration.
- Laravel scheduler running every minute.
- Horizon dashboard protected by super-admin middleware.

Deployment checklist:

- `composer install --no-dev --optimize-autoloader`
- `npm ci && npm run build`
- `php artisan migrate --force`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `php artisan event:cache`
- restart queue workers
- run smoke tests for login, OTP, dashboard, imports, payments, and notifications

Operational requirements:

- Daily database backups and periodic restore verification.
- Centralized logs and alerting.
- Error monitoring.
- Uptime checks.
- Webhook retry monitoring.
- Documented rollback process.

## 11. Acceptance Criteria

The project is not complete until:

- Admin, supervisor, and student auth flows work independently with correct redirects and route protection.
- Super admin can create admins, define roles, assign privileges, and audit those changes.
- Every UI screenshot workflow has a modern replacement screen.
- Light mode, dark mode, alerts, modals, push notifications, animations, and responsive layouts are implemented.
- OTP, rate limiting, audit logs, secure uploads, payment verification, and permission checks are tested.
- CI passes formatting, static analysis, unit/feature tests, and browser smoke tests.
- Production deployment includes workers, scheduler, backups, monitoring, and documented rollback.
