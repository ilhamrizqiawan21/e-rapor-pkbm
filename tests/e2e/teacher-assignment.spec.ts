import { expect, test, type Page } from '@playwright/test';

async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill('admin');
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard\/admin$/);
}

test.describe('05 teacher and assignment browser flows', () => {
    test('admin can open teacher management and class assignment entry points', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/teachers');
        await expect(page.getByRole('heading', { name: 'Guru', exact: true })).toBeVisible();
        await expect(page.getByText('Guru Demo', { exact: true })).toBeVisible();

        await page.goto('/admin/academic-classes');
        await expect(page.getByRole('heading', { name: 'Kelas / Rombel', exact: true })).toBeVisible();
    });
});
