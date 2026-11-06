import { expect, test, type Page } from '@playwright/test';

async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill('admin');
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard\/admin$/);
}

test.describe('08 reusable description bank browser flows', () => {
    test('admin can open the description bank', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/description-snippets');
        await expect(page.getByRole('heading', { name: 'Bank deskripsi', exact: true })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Tambah snippet' })).toBeVisible();
    });

    test('admin can open the description picker used by grade input', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/description-snippets/picker');
        await expect(page.getByRole('heading', { name: 'Pilih deskripsi capaian', exact: true })).toBeVisible();
        await expect(page.getByText('Menampilkan milik Anda dan yang dibagikan', { exact: true })).toBeVisible();
    });
});
