import { expect, test, type Page } from '@playwright/test';

async function login(page: Page, username: string): Promise<void> {
    await page.context().clearCookies();
    await page.goto('/login');
    await page.getByLabel('Username atau email').fill(username);
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk', exact: true }).click();
    await page.waitForURL(/\/dashboard\/(admin|guru)$/);
}

test('semester change shows one readiness modal without per-student decisions', async ({ page }, testInfo) => {
    await login(page, 'admin');
    await page.goto('/admin/academic-years');
    await page.getByRole('button', { name: 'Mulai semester genap', exact: true }).click();
    const modal = page.getByRole('dialog').filter({ has: page.getByRole('heading', { name: 'Mulai semester genap', exact: true }) });
    await expect(modal.getByRole('heading', { name: 'Mulai semester genap', exact: true })).toBeVisible();
    await expect(modal.getByRole('columnheader', { name: 'Rapor belum lengkap' })).toBeVisible();
    await expect(modal.getByText('Kelas dan anggota tetap;', { exact: false })).toBeVisible();
    await expect(modal.locator('select[name*="decision"]')).toHaveCount(0);
    await page.screenshot({ path: testInfo.outputPath('semester-modal.png'), fullPage: true });
    await modal.getByRole('button', { name: 'Batal', exact: true }).click();
    await expect(modal.getByRole('heading', { name: 'Mulai semester genap', exact: true })).not.toBeVisible();
});

test('homeroom decision and persistent admin preparation lead to a new empty school year', async ({ page }, testInfo) => {
    const device = testInfo.project.name === 'mobile-chrome' ? 'mobile' : 'desktop';
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await login(page, 'period-admin-' + device);
    await page.goto('/admin/academic-years');
    await page.getByRole('link', { name: 'Siapkan tahun baru', exact: true }).click();
    await page.getByRole('button', { name: 'Simpan & lanjutkan', exact: true }).click();
    const preparationURL = page.url();
    await expect(page.getByText('Menunggu keputusan wali kelas', { exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Simpan & periksa ringkasan' }).click();
    await expect(page.getByRole('button', { name: 'Mulai tahun pelajaran baru', exact: true })).toBeDisabled();

    await login(page, 'period-guru-' + device);
    await page.goto('/academic-decisions');
    const sourceClass = await page.getByLabel('Kelas dan tahun pelajaran').inputValue();
    await page.getByLabel('Keputusan Ahmad Peralihan', { exact: true }).selectOption('PROMOTE');
    await page.getByRole('button', { name: 'Simpan draf', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Keputusan kelas berhasil disimpan.' })).toBeVisible();
    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Tetapkan keputusan akhir', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Keputusan akhir kelas sudah ditetapkan.' })).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath('homeroom-decisions.png'), fullPage: true });

    await login(page, 'period-admin-' + device);
    await page.goto(preparationURL);
    await page.getByLabel('Kelas tujuan Ahmad Peralihan', { exact: true }).selectOption({ label: 'Kelas 2 Peralihan' });
    await page.getByRole('checkbox', { name: /Saya sudah meninjau kelas/ }).check();
    await page.getByRole('button', { name: 'Simpan draf', exact: true }).click();
    await page.screenshot({ path: testInfo.outputPath('year-placements.png'), fullPage: true });

    await login(page, 'period-admin-' + device);
    await page.goto(preparationURL);
    await expect(page.getByLabel('Kelas tujuan Ahmad Peralihan', { exact: true })).toHaveValue(/\d+/);
    await page.getByRole('button', { name: 'Simpan & periksa ringkasan' }).click();
    await expect(page.getByText('Semua persyaratan lengkap. Tahun baru siap dimulai.', { exact: true })).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath('year-review.png'), fullPage: true });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
    await page.getByRole('checkbox', { name: /Saya sudah memeriksa ringkasan/ }).check();
    await page.getByRole('button', { name: 'Mulai tahun pelajaran baru', exact: true }).click();
    await expect(page.getByRole('heading', { name: '2027/2028', exact: true })).toBeVisible();
    await expect(page.getByText('Periode baru berhasil dimulai.', { exact: false })).toBeVisible();

    await login(page, 'period-guru-' + device);
    await page.goto('/academic-decisions?class_id=' + sourceClass);
    await expect(page.getByText('Naik ke tingkat 2', { exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Simpan draf', exact: true })).toHaveCount(0);
    await page.goto('/admin/grade-entry');
    await expect(page.getByText('Belum diisi', { exact: true }).first()).toBeVisible();
    expect(errors).toEqual([]);
});
