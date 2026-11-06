import { expect, Page, test } from '@playwright/test';

async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill('admin');
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard\/admin$/);
}

test.describe('UI regressions', () => {
    test('keeps the production navigation focused on PKBM work', async ({ page }) => {
        await loginAsAdmin(page);

        const navigation = test.info().project.name === 'mobile-chrome'
            ? page.locator('[role="dialog"] nav')
            : page.locator('#desktop-sidebar-navigation');

        if (test.info().project.name === 'mobile-chrome') {
            await page.getByRole('button', { name: 'Buka menu navigasi' }).click();
        }

        await expect(navigation.getByText('Komponen UI', { exact: true })).toHaveCount(0);
        await expect(navigation).toContainText('Data inti');
    });

    test('traps mobile drawer focus and restores the trigger', async ({ page }) => {
        test.skip(test.info().project.name !== 'mobile-chrome', 'Focus trap hanya relevan pada drawer mobile.');
        await loginAsAdmin(page);

        const trigger = page.getByRole('button', { name: 'Buka menu navigasi' });
        await trigger.click();

        const dialog = page.getByRole('dialog');
        const closeButton = page.getByRole('button', { name: 'Tutup menu' });
        await expect(closeButton).toBeFocused();

        for (let index = 0; index < 20; index += 1) {
            await page.keyboard.press('Tab');
            await expect(dialog.locator(':focus')).toHaveCount(1);
        }

        await closeButton.click();
        await expect(trigger).toBeFocused();
    });

    test('preserves sidebar scroll position when switching menus', async ({ page }) => {
        await loginAsAdmin(page);

        const isMobile = test.info().project.name === 'mobile-chrome';
        const navigation = isMobile
            ? page.locator('[role="dialog"] nav')
            : page.locator('#desktop-sidebar-navigation');

        if (isMobile) {
            await page.getByRole('button', { name: 'Buka menu navigasi' }).click();
        }

        const expectedScrollTop = await navigation.evaluate((element) => {
            const maxScrollTop = element.scrollHeight - element.clientHeight;
            const scrollTop = Math.min(240, maxScrollTop);

            element.scrollTop = scrollTop;
            element.dispatchEvent(new Event('scroll', { bubbles: true }));

            return scrollTop;
        });

        const academicYearsLink = navigation.getByRole('link', { name: 'Tahun pelajaran', exact: true });

        await Promise.all([
            page.waitForURL(/\/admin\/academic-years$/),
            academicYearsLink.evaluate((element) => (element as HTMLAnchorElement).click()),
        ]);

        if (isMobile) {
            await page.getByRole('button', { name: 'Buka menu navigasi' }).click();
        }

        await expect.poll(() => navigation.evaluate((element) => element.scrollTop)).toBe(expectedScrollTop);
    });

    test('exposes form errors and hints to assistive technology', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/components-showcase');

        const invalidSelect = page.getByLabel('Status Kelulusan');
        const selectErrorId = await invalidSelect.getAttribute('aria-describedby');
        await expect(invalidSelect).toHaveAttribute('aria-invalid', 'true');
        expect(selectErrorId).toBeTruthy();
        await expect(page.locator(`#${selectErrorId}`)).toHaveAttribute('role', 'alert');

        const textarea = page.getByLabel('Catatan Perkembangan Akademik');
        await expect(textarea).toHaveAttribute('aria-describedby', /textarea-academic_notes-hint/);
    });

    test('uses a mobile-first empty state on operational dashboards', async ({ page }) => {
        await loginAsAdmin(page);

        if (test.info().project.name === 'mobile-chrome') {
            await expect(page.locator('table:visible')).toHaveCount(0);
            await expect(page.getByRole('link', { name: 'Kelola periode' }).first()).toBeVisible();
            await expect(page.getByText('Siapkan kelas / rombel')).toBeVisible();
        }

        await expect(page.getByText('Langkah berikutnya: Aktifkan periode')).toBeVisible();
        await expect(page.getByText('0/4 selesai')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Kelola periode' }).first()).toBeVisible();
    });
});
