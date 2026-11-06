import { expect, test, type Page } from '@playwright/test';

async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill('admin');
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard\/admin$/);
}

test.describe('07 subject and predicate configuration browser flows', () => {
    test('admin can open subject master and predicate ranges', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/subjects');
        await expect(page.getByRole('heading', { name: 'Mata pelajaran', exact: true })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Tambah mata pelajaran' })).toBeVisible();

        await page.goto('/admin/predicate-ranges');
        await expect(page.getByRole('heading', { name: 'Rentang predikat', exact: true })).toBeVisible();
        await expect(page.getByText('A', { exact: true })).toBeVisible();
    });

    test('admin can open bulk subject mapping tools', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/academic-classes');
        await page.getByRole('link', { name: 'Mapping beberapa kelas' }).click();
        await expect(page.getByRole('heading', { name: 'Tambah mapel ke beberapa kelas', exact: true })).toBeVisible();

        await page.goto('/admin/academic-classes');
        await page.getByRole('link', { name: 'Bulk assign Guru' }).click();
        await expect(page.getByRole('heading', { name: 'Bulk assign Guru', exact: true })).toBeVisible();
    });
});
