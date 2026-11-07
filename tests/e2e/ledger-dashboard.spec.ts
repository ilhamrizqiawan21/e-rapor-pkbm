import { expect, test, type Page } from '@playwright/test';

async function login(page: Page, username: string, dashboardPath: string): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill(username);
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(new RegExp(dashboardPath.replaceAll('/', '\\/') + '$'));
}

test.describe('13-14 leger and dashboard browser flows', () => {
    test('admin can open the ledger generator and empty period state', async ({ page }) => {
        await login(page, 'admin', '/dashboard/admin');
        await page.goto('/admin/ledger');

        await expect(page.getByRole('heading', { name: 'Leger nilai', exact: true })).toBeVisible();
        await expect(page.getByText('2026/2027', { exact: true }).first()).toBeVisible();

        if (test.info().project.name === 'chromium') {
            const widths = await page.locator('table').first().evaluate((table) => ({
                table: table.getBoundingClientRect().width,
                container: table.parentElement?.getBoundingClientRect().width ?? 0,
            }));

            expect(widths.table).toBeGreaterThanOrEqual(widths.container - 1);
        }

        const dimensions = await page.evaluate(() => ({
            viewport: window.innerWidth,
            document: document.documentElement.scrollWidth,
        }));

        expect(dimensions.document).toBeLessThanOrEqual(dimensions.viewport + 1);
    });

    test('role dashboards render their monitoring surfaces', async ({ page }) => {
        await login(page, 'admin', '/dashboard/admin');
        await expect(page.getByRole('heading', { name: 'Operasional PKBM dalam satu layar', exact: true })).toBeVisible();
        await expect(page.getByText('Progres nilai per kelas', { exact: true })).toBeVisible();

        await page.locator('header').getByRole('button', { name: 'Buka menu akun' }).click();
        await page.getByRole('menuitem', { name: 'Keluar' }).click();
        await login(page, 'guru', '/dashboard/guru');
        await expect(page.getByRole('heading', { name: 'Ruang kerja penilaian', exact: true })).toBeVisible();
        await expect(page.getByText('Progres input nilai per mapel', { exact: true })).toBeVisible();

        await page.locator('header').getByRole('button', { name: 'Buka menu akun' }).click();
        await page.getByRole('menuitem', { name: 'Keluar' }).click();
        await login(page, 'kepsek', '/dashboard/kepsek');
        await expect(page.getByRole('heading', { name: 'Pantau rapor peserta didik', exact: true })).toBeVisible();
        await expect(page.getByText('Rapor yang perlu dipantau', { exact: true })).toBeVisible();
    });
});
