import { expect, test, type Page } from '@playwright/test';

async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill('admin');
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard\/admin$/);
}

test.describe('10 report card completeness browser flows', () => {
    test('admin can open the report card completeness index', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/report-cards');
        await expect(page.getByRole('heading', { name: 'Daftar rapor peserta didik', exact: true })).toBeVisible();
        const emptyState = test.info().project.name === 'mobile-chrome'
            ? page.locator('p', { hasText: 'Belum ada rapor pada institusi ini.' })
            : page.getByRole('cell', { name: 'Belum ada rapor pada institusi ini.' });

        await expect(emptyState).toBeVisible();
    });
});
