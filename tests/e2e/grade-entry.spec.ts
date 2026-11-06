import { expect, test, type Page } from '@playwright/test';

async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill('admin');
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard\/admin$/);
}

test.describe('09 grade entry browser flows', () => {
    test('admin can open the grade entry context screen', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/grade-entry');
        await expect(page.getByRole('heading', { name: 'Input nilai', exact: true })).toBeVisible();
        await expect(page.getByText('Pilih konteks yang memiliki penugasan mata pelajaran aktif.', { exact: false })).toBeVisible();
    });
});
