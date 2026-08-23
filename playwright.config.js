import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    fullyParallel: false,
    workers: 1,
    expect: {
        timeout: 5_000,
    },
    use: {
        baseURL: 'http://siwes.test',
        trace: 'on-first-retry',
        navigationTimeout: 10_000,
    },
    projects: [
        {
            name: 'desktop',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'mobile',
            use: { ...devices['Pixel 7'] },
        },
    ],
});
