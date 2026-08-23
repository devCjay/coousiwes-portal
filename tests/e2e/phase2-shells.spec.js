import { expect, test } from '@playwright/test';
import { readFileSync, readdirSync } from 'node:fs';
import { join } from 'node:path';

const publicBuild = join(process.cwd(), 'public', 'build', 'assets');
const snapshots = join(process.cwd(), 'tests', 'e2e', 'snapshots');
const appScript = readFileSync(
    join(publicBuild, readdirSync(publicBuild).find((file) => /^app-.*\.js$/.test(file))),
    'utf8',
);
const snapshotFor = {
    '/': 'home.html',
    '/login/admin': 'login-admin.html',
    '/login/supervisor': 'login-supervisor.html',
    '/login/student': 'login-student.html',
    '/admin/dashboard': 'admin-dashboard.html',
    '/admin/students': 'admin-students.html',
    '/admin/supervisors': 'admin-supervisors.html',
    '/admin/tickets': 'admin-tickets.html',
    '/admin/assessments/rubric': 'admin-assessment-rubric.html',
    '/admin/reports': 'admin-reports.html',
    '/admin/control': 'admin-control-center.html',
    '/admin/control/audit-logs': 'admin-audit-logs.html',
    '/notifications': 'notifications.html',
    '/supervisor/dashboard': 'supervisor-dashboard.html',
    '/supervisor/students': 'supervisor-students.html',
    '/supervisor/assessments': 'supervisor-assessments.html',
    '/student/dashboard': 'student-dashboard.html',
    '/student/payments': 'student-payments.html',
    '/student/feedback': 'student-feedback.html',
};

test.beforeEach(async ({ page }) => {
    await page.route('**/*', async (route) => {
        const request = route.request();
        const resourceType = request.resourceType();

        if (resourceType === 'document') {
            const url = new URL(request.url());
            const snapshot = snapshotFor[url.pathname];

            await route.fulfill({
                status: snapshot ? 200 : 404,
                contentType: 'text/html',
                body: snapshot ? readFileSync(join(snapshots, snapshot), 'utf8') : 'Not found',
            });

            return;
        }

        if (request.url().includes('/build/assets/app-') && request.url().endsWith('.js')) {
            await route.fulfill({
                contentType: 'application/javascript',
                body: appScript,
            });

            return;
        }

        if (['font', 'stylesheet'].includes(resourceType)) {
            await route.fulfill({
                contentType: resourceType === 'stylesheet' ? 'text/css' : 'font/woff2',
                body: '',
            });

            return;
        }

        await route.continue();
    });
});

test('landing page exposes role entry points and theme toggle', async ({ page }) => {
    await page.addInitScript(() => localStorage.setItem('siwes-theme', 'light'));
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'COOU SIWES Management Portal' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Student' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Supervisor' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Admin' })).toBeVisible();

    await page.getByRole('button', { name: 'Toggle theme' }).click();
    await expect(page.locator('html')).toHaveClass(/dark/);
});

test('role login shell switches between login portals', async ({ page }) => {
    await page.goto('/login/admin', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Admin Login' })).toBeVisible();

    await page.getByRole('link', { name: 'Supervisor' }).click();
    await expect(page.getByRole('heading', { name: 'Supervisor Login' })).toBeVisible();

    await page.getByRole('link', { name: 'Student' }).click();
    await expect(page.getByRole('heading', { name: 'Student Login' })).toBeVisible();
});

test('admin dashboard supports toast, modal, and table preview', async ({ page }) => {
    const errors = [];

    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Admin Dashboard' })).toBeVisible();
    await expect(page.getByText('Student List')).toBeVisible();

    await expect(page.getByRole('link', { name: 'Notifications' })).toBeVisible();
    await page.evaluate(() => window.SiwesToast({ title: 'Notification delivered', message: 'Toast channel is active.', tone: 'info' }));
    await expect(page.getByText('Notification delivered')).toBeVisible();

    await page.getByLabel('Search').fill('Joshua');
    await expect(page.getByText('Joshua C. Ananti')).toBeVisible();
    await expect(page.getByText('Mary Dennis Abang')).toBeHidden();

    await page.getByRole('button', { name: 'Add' }).click();
    await expect(page.locator('#student-modal')).toHaveJSProperty('open', true);
    await expect(page.getByText('Add Student').last()).toBeVisible();
    expect(errors).toEqual([]);
});

test('mobile app shell can open the sidebar navigation', async ({ page, isMobile }) => {
    test.skip(!isMobile, 'mobile-only shell check');

    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await expect(page.getByRole('heading', { name: 'Student Dashboard' })).toBeVisible();

    await page.getByRole('button', { name: 'Toggle navigation' }).click();
    await expect(page.getByRole('link', { name: /Profile/ })).toBeVisible();
});

test('student and supervisor dashboards render database-backed portal workflows', async ({ page }) => {
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Student Dashboard' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Update Profile' })).toBeVisible();
    await expect(page.getByText('Student activation')).toBeVisible();
    await expect(page.getByText('Dr Ada Supervisor')).toBeVisible();

    await page.goto('/supervisor/dashboard', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Supervisor Dashboard' })).toBeVisible();
    await expect(page.getByText('2026/CSC/001')).toBeVisible();
    await expect(page.getByText('Supervisor assignment')).toBeVisible();
});


test('student management page covers listing, add form, and bulk upload', async ({ page }) => {
    await page.goto('/admin/students', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Student Management' })).toBeVisible();
    await expect(page.getByText('Ada Okoye')).toBeVisible();
    await expect(page.getByText('Bulk Upload')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Create Student' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Preview Import' })).toBeVisible();

    await page.getByLabel('Search').fill('Ngozi');
    await expect(page.getByText('Ngozi Eze')).toBeVisible();
    await expect(page.getByText('Ada Okoye')).toBeHidden();
});

test('ticket and Korapay payment pages render key workflows', async ({ page }) => {
    await page.goto('/admin/tickets', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Ticket And Payment Control' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Generate Ticket' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Korapay' })).toBeVisible();

    await page.goto('/student/payments', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Activation Payments' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Pay With Korapay' })).toBeVisible();
    await expect(page.getByText('SIWES-SNAPSHOT')).toBeVisible();
});

test('supervisor management and assignment pages render key workflows', async ({ page }) => {
    await page.goto('/admin/supervisors', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Supervisor Management' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Create Supervisor' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Assign Student' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Bulk Assign' })).toBeVisible();

    await page.getByLabel('Search').fill('Ada');
    await expect(page.getByText('Dr Ada Supervisor').first()).toBeVisible();

    await page.goto('/supervisor/students', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Assigned Students' })).toBeVisible();
    await expect(page.getByText('2026/CSC/001')).toBeVisible();
});

test('assessment, feedback, and reporting pages render phase nine workflows', async ({ page }) => {
    await page.goto('/admin/assessments/rubric', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Assessment Rubric' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Create Item' })).toBeVisible();
    await expect(page.getByText('Technical Skill')).toBeVisible();

    await page.goto('/supervisor/assessments', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Assessments' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit Assessment' })).toBeVisible();
    await expect(page.getByText('Strong workplace conduct')).toBeVisible();

    await page.goto('/student/feedback', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Supervisor Feedback' })).toBeVisible();
    await expect(page.getByText('Strong workplace conduct')).toBeVisible();

    await page.goto('/admin/reports', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Reports' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Export CSV' })).toBeVisible();
    await expect(page.getByText('Supervisor Performance')).toBeVisible();
});

test('super admin control center renders roles, admins, health, and audit explorer', async ({ page }) => {
    await page.goto('/admin/control', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Super Admin Control' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Create Admin' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Create Role' })).toBeVisible();
    await expect(page.getByText('SIWES-HEALTH')).toBeVisible();

    await page.getByLabel('Live Search').first().fill('Finance');
    await expect(page.getByRole('cell', { name: 'finance-admin' })).toBeVisible();

    await page.goto('/admin/control/audit-logs', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Audit Logs' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Export' })).toBeVisible();
    await expect(page.getByText('roles.updated')).toBeVisible();
});

test('notification center supports unread alerts and live filtering', async ({ page }) => {
    await page.goto('/notifications', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: 'Notification Center' })).toBeVisible();
    await expect(page.getByText('Supervisor feedback submitted')).toBeVisible();
    await expect(page.getByText('Payment verified')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Mark All Read' })).toBeVisible();

    await page.getByLabel('Live Search').fill('Korapay');
    await expect(page.getByText('Payment verified')).toBeVisible();
    await expect(page.getByText('Supervisor feedback submitted')).toBeHidden();
});
