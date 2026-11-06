import { expect, test, type Page } from '@playwright/test';

async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill('admin');
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard\/admin$/);
}

test.describe('04 academic foundation browser flows', () => {
    test('admin can open period, program, and class management', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/academic-years');
        await expect(page.getByRole('heading', { name: 'Tahun Pelajaran', exact: true })).toBeVisible();

        await page.goto('/admin/programs');
        await expect(page.getByRole('heading', { name: 'Program pendidikan' })).toBeVisible();
        // The directory has one semantic item in the desktop table and one in the mobile card layout.
        await expect(page.getByText('Paket A', { exact: true })).toHaveCount(2);

        await page.goto('/admin/academic-classes');
        await expect(page.getByRole('heading', { name: 'Kelas / Rombel' })).toBeVisible();
    });
});
