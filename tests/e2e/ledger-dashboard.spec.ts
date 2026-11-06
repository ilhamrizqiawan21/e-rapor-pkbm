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

        await expect(page.getByRole('heading', { name: 'Generator leger nilai', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Belum ada periode leger', exact: true })).toBeVisible();
    });

    test('role dashboards render their monitoring surfaces', async ({ page }) => {
        await login(page, 'admin', '/dashboard/admin');
        await expect(page.getByRole('heading', { name: 'Operasional PKBM dalam satu layar', exact: true })).toBeVisible();
        await expect(page.getByText('Progres nilai per kelas', { exact: true })).toBeVisible();

        await page.locator('header').getByRole('button', { name: /Akun|Admin Demo|Guru Demo|Kepala PKBM/ }).click();
        await page.getByRole('menuitem', { name: 'Keluar' }).click();
        await login(page, 'guru', '/dashboard/guru');
        await expect(page.getByRole('heading', { name: 'Ruang kerja penilaian', exact: true })).toBeVisible();
        await expect(page.getByText('Progres input nilai per mapel', { exact: true })).toBeVisible();

        await page.locator('header').getByRole('button', { name: /Akun|Admin Demo|Guru Demo|Kepala PKBM/ }).click();
        await page.getByRole('menuitem', { name: 'Keluar' }).click();
        await login(page, 'kepsek', '/dashboard/kepsek');
        await expect(page.getByRole('heading', { name: 'Pusat review dan persetujuan rapor', exact: true })).toBeVisible();
        await expect(page.getByText('Antrean review', { exact: true })).toBeVisible();
    });
});
