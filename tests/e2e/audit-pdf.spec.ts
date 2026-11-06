import { expect, test, type Page } from '@playwright/test';

async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill('admin');
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard\/admin$/);
}

test.describe('11 workflow audit browser flows', () => {
    test('admin can open immutable audit history with filters', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/audit-logs');
        await expect(page.getByRole('heading', { name: 'Histori audit', exact: true })).toBeVisible();
        await expect(page.getByLabel('Pengguna')).toBeVisible();
        await expect(page.getByLabel('Aksi')).toBeVisible();

        const loginAction = test.info().project.name === 'mobile-chrome'
            ? page.locator('[data-responsive-card]').getByText('Login', { exact: true }).first()
            : page.getByRole('table').getByText('Login', { exact: true }).first();

        await expect(loginAction).toBeVisible();
    });
});
