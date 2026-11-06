import { expect, test, type Page } from '@playwright/test';

async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill('admin');
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard\/admin$/);
}

test.describe('06 student and placement browser flows', () => {
    test('admin can open student master and import workflow', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/students');
        await expect(page.getByRole('heading', { name: 'Peserta didik', exact: true })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Import data' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Direktori peserta didik', exact: true })).toBeVisible();
        await expect(page.getByLabel('Cari peserta didik')).toBeVisible();

        await page.goto('/admin/students/import');
        await expect(page.getByRole('heading', { name: 'Import peserta didik', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Upload file peserta didik', exact: true })).toBeVisible();
        await expect(page.getByText('Upload & validasi', { exact: true })).toBeVisible();
        await expect(page.getByLabel('File XLSX atau CSV')).toBeVisible();
    });
});
