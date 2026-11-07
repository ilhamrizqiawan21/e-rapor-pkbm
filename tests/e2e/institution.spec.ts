import { expect, test, type Page } from '@playwright/test';

async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill('admin');
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard\/admin$/);
}

test.describe('03 institution administration browser flows', () => {
    test('admin can open identity, signatory, and format settings', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/institutions');
        await expect(page.getByRole('heading', { name: 'Identitas PKBM' })).toBeVisible();
        await expect(page.getByText('PKBM Contoh')).toBeVisible();

        await page.goto('/admin/signatory-settings');
        await expect(page.getByRole('heading', { name: 'Penandatangan rapor' })).toBeVisible();
        await expect(page.getByRole('option', { name: /Kepala PKBM Demo/ })).toBeAttached();

        await page.goto('/admin/settings/format');
        await expect(page.getByRole('heading', { name: 'Format rapor' })).toBeVisible();
        await expect(page.getByLabel('Format nama Peserta Didik')).toBeVisible();
    });

    test('admin can open the identity preview from the institution detail', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/admin/institutions');
        await page.getByRole('link', { name: 'Lihat detail' }).click();
        await page.getByRole('link', { name: 'Pratinjau rapor' }).click();

        await expect(page).toHaveURL(/\/admin\/institutions\/\d+\/preview$/);
        await expect(page.getByRole('heading', { name: 'Pratinjau identitas rapor' })).toBeVisible();
        await expect(page.getByText('PKBM Contoh')).toBeVisible();
        await expect(page.getByText('Area isi nilai, KKM, predikat, deskripsi, sikap, dan absensi')).toBeVisible();
    });
});
