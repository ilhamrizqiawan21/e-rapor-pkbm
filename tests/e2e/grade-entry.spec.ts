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
        await expect(page.getByText('Materi / capaian pembelajaran', { exact: true })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Impor dari Excel' })).toBeVisible();
    });

    test('admin can open the context-locked Excel import screen', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/grade-entry');
        const importLink = page.getByRole('link', { name: 'Impor dari Excel' });
        await importLink.click();
        await expect(page).toHaveURL(/\/admin\/grade-entry\/import(?:\?.*)?$/);
        await expect(page.getByRole('heading', { name: 'Impor nilai dari Excel', exact: true })).toBeVisible();
        await expect(page.getByText('Konteks file terkunci', { exact: true })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Unduh template resmi' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Validasi dan preview' })).toBeVisible();
    });
});
