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
        await expect(page.locator('[data-autosave-status]')).toHaveAttribute('aria-label', 'Tersimpan otomatis');
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

    test('admin saves a changed score automatically', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/grade-entry');
        const score = page.locator('[data-score-input]').first();
        const status = page.locator('[data-grade-status]').first();
        const predicate = page.locator('[data-predicate-badge]').first();
        const autosave = page.locator('[data-autosave-status]');
        const saveRequest = page.waitForResponse((response) =>
            response.url().includes('/admin/grade-entry/all')
            && response.request().method() === 'POST'
            && response.status() === 200,
        );

        await score.fill('88');
        await expect(status).toHaveText('Terisi');
        await expect(predicate).toHaveClass(/bg-emerald-50/);
        await saveRequest;
        await expect(autosave).toHaveAttribute('aria-label', 'Tersimpan otomatis');
        await expect(autosave.locator('[data-autosave-icon]')).not.toHaveClass(/animate-spin/);
    });

    test('admin can reset a score and see its status change without refresh', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/grade-entry');
        const score = page.locator('[data-score-input]').first();
        const status = page.locator('[data-grade-status]').first();
        const reset = page.locator('[data-grade-reset]').first();
        const saveRequest = page.waitForResponse((response) =>
            response.url().includes('/admin/grade-entry/all')
            && response.request().method() === 'POST'
            && response.status() === 200,
        );

        await reset.click();
        await expect(score).toHaveValue('');
        await expect(status).toHaveText('Belum diisi');
        await saveRequest;
    });
});
