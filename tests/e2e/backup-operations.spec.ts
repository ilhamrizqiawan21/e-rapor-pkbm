import { expect, test, type Page } from '@playwright/test';

async function login(page: Page, username: string, dashboardPath: string): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill(username);
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(new RegExp(dashboardPath.replaceAll('/', '\\/') + '$'));
}

test.describe('15 backup and operational audit browser flows', () => {
    test('admin can open the private backup and restore operations page', async ({ page }) => {
        await login(page, 'admin', '/dashboard/admin');
        await page.goto('/admin/backups');

        await expect(page.getByRole('heading', { name: 'Cadangan dan pemulihan data', exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Buat cadangan data', exact: true })).toBeVisible();
        await expect(page.getByLabel('File cadangan')).toBeVisible();
        await expect(page.getByLabel('Konfirmasi')).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Arsip cadangan', exact: true })).toBeVisible();
    });

    test('guru cannot open backup operations', async ({ page }) => {
        await login(page, 'guru', '/dashboard/guru');
        const response = await page.goto('/admin/backups');

        expect(response?.status()).toBe(403);
    });
});
