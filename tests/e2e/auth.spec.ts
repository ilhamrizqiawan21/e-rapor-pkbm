import { expect, test, type Page } from '@playwright/test';

type RoleAccount = {
    username: string;
    roleLabel: string;
    dashboardPath: string;
};

const accounts: Record<string, RoleAccount> = {
    admin: { username: 'admin', roleLabel: 'Admin', dashboardPath: '/dashboard/admin' },
    guru: { username: 'guru', roleLabel: 'Guru', dashboardPath: '/dashboard/guru' },
    kepsek: { username: 'kepsek', roleLabel: 'Kepala PKBM', dashboardPath: '/dashboard/kepsek' },
};

async function login(page: Page, account: RoleAccount): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill(account.username);
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(new RegExp(`${account.dashboardPath.replaceAll('/', '\\/')}$`));
}

async function openAccountMenu(page: Page): Promise<void> {
    await page.locator('header').getByRole('button', { name: /Akun|Admin Demo|Guru Demo|Kepala PKBM/ }).click();
    await expect(page.locator('[role="menu"]')).toBeVisible();
}

test.describe('02.05 authentication and authorization browser flows', () => {
    test('guest cannot open the dashboard', async ({ page }) => {
        await page.goto('/');

        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole('heading', { name: 'Masuk ke aplikasi' })).toBeVisible();
    });

    test('guest is redirected from every private entry route', async ({ page }) => {
        for (const path of ['/', '/dashboard/admin', '/dashboard/guru', '/dashboard/kepsek', '/account/password', '/account/profile']) {
            await page.goto(path);
            await expect(page).toHaveURL(/\/login$/);
        }
    });

    for (const [name, account] of Object.entries(accounts)) {
        test(`${name} can log in and sees the correct role`, async ({ page }) => {
            await login(page, account);

            await expect(page.getByText('Fondasi project sudah siap')).toBeVisible();
            await openAccountMenu(page);
            await expect(page.locator('[role="menu"]').getByText(account.roleLabel, { exact: true })).toBeVisible();
        });
    }

    test('invalid credentials show an error and keep the user on login', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Username atau email').fill('admin');
        await page.getByLabel('Kata sandi').fill('wrong-password');
        await page.getByRole('button', { name: 'Masuk' }).click();

        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole('alert').first()).toContainText('Nama pengguna/email atau kata sandi salah.');
        await expect(page.getByLabel('Username atau email')).toHaveValue('admin');
    });

    test('inactive users are rejected', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Username atau email').fill('inactive');
        await page.getByLabel('Kata sandi').fill('password');
        await page.getByRole('button', { name: 'Masuk' }).click();

        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole('alert').first()).toContainText('Nama pengguna/email atau kata sandi salah.');
    });

    test('admin can open the Admin dashboard', async ({ page }) => {
        await login(page, accounts.admin);
        const response = await page.goto('/dashboard/admin');

        expect(response?.status()).toBe(200);
        await expect(page.getByText('Fondasi project sudah siap')).toBeVisible();
    });

    test('guru is forbidden from the Admin dashboard', async ({ page }) => {
        await login(page, accounts.guru);
        const response = await page.goto('/dashboard/admin');

        expect(response?.status()).toBe(403);
    });

    test('Kepala PKBM can open the review role dashboard', async ({ page }) => {
        await login(page, accounts.kepsek);
        const response = await page.goto('/dashboard/kepsek');

        expect(response?.status()).toBe(200);
        await openAccountMenu(page);
        await expect(page.locator('[role="menu"]').getByText('Kepala PKBM Demo', { exact: true })).toBeVisible();
        await expect(page.locator('[role="menu"]').getByText('Kepala PKBM', { exact: true })).toBeVisible();
    });

    test('logout removes the session and protects the dashboard again', async ({ page }) => {
        await login(page, accounts.admin);
        await openAccountMenu(page);
        await page.getByRole('menuitem', { name: 'Keluar' }).click();

        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole('status')).toContainText('Anda telah keluar dari aplikasi.');

        await page.goto('/');
        await expect(page).toHaveURL(/\/login$/);
    });
});
