import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.BASE_URL ?? 'http://127.0.0.1:8001';
const e2eDatabase = `${process.cwd()}/database/database.e2e.sqlite`;

export default defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/global-setup.ts',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]],
    use: {
        baseURL,
        headless: true,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        actionTimeout: 10_000,
        navigationTimeout: 30_000,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'mobile-chrome',
            use: { ...devices['Pixel 5'] },
        },
    ],
    webServer: process.env.BASE_URL
        ? undefined
        : {
              command: 'php artisan serve --host=127.0.0.1 --port=8001',
              url: `${baseURL}/login`,
              reuseExistingServer: false,
              timeout: 120_000,
              env: {
                  APP_ENV: 'local',
                  APP_URL: baseURL,
                  DB_CONNECTION: 'sqlite',
                  DB_DATABASE: e2eDatabase,
                  SESSION_DRIVER: 'file',
                  CACHE_STORE: 'file',
                  QUEUE_CONNECTION: 'sync',
                  MAIL_MAILER: 'array',
              },
          },
});
