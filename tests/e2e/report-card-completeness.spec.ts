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
        await expect(page.getByRole('heading', { name: 'Kelengkapan & cetak rapor', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Antrean kelengkapan rapor', exact: true })).toBeVisible();
        await expect(page.getByText('Perlu dilengkapi', { exact: true })).toBeVisible();
        await expect(page.getByText('Siap dicetak', { exact: true }).first()).toBeVisible();
        await expect(page.getByRole('link', { name: /Lengkapi|Cetak rapor|Lihat rincian/ }).first()).toBeVisible();

        const dimensions = await page.evaluate(() => ({
            viewport: window.innerWidth,
            document: document.documentElement.scrollWidth,
        }));

        expect(dimensions.document).toBeLessThanOrEqual(dimensions.viewport + 1);
    });
});
