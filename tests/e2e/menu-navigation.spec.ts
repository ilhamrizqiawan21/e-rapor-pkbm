import { expect, Page, test } from '@playwright/test';

type Role = 'admin' | 'guru' | 'kepsek';

const accounts: Record<Role, { username: string; dashboard: string }> = {
    admin: { username: 'admin', dashboard: '/dashboard/admin' },
    guru: { username: 'guru', dashboard: '/dashboard/guru' },
    kepsek: { username: 'kepsek', dashboard: '/dashboard/kepsek' },
};

async function login(page: Page, role: Role): Promise<void> {
    const account = accounts[role];

    await page.goto('/login');
    await page.getByLabel('Username atau email').fill(account.username);
    await page.getByLabel('Kata sandi').fill('password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(new RegExp(`${account.dashboard}$`));
}
async function visibleMenuLinks(page: Page): Promise<Array<{ label: string; href: string }>> {
    const links = await page.locator('aside nav a, [role="dialog"] nav a').evaluateAll((elements) =>
        elements.map((element) => ({
            label: (element.textContent ?? '').replace(/\s+/g, ' ').trim(),
            href: (element as HTMLAnchorElement).href,
        })),
    );

    return [...new Map(links.map((link) => [link.href, link])).values()];
}

async function assertNavigationPresentation(page: Page, role: Role): Promise<void> {
    const navigation = test.info().project.name === 'mobile-chrome'
        ? page.locator('[role="dialog"] nav')
        : page.locator('#desktop-sidebar-navigation');

    for (const group of ['Data inti', 'Penilaian', 'Rapor & leger', 'Akun']) {
        await expect(navigation).toContainText(group);
    }

    if (role === 'admin') {
        await expect(navigation).toContainText('Pengaturan PKBM');
    }

    for (const label of ['Dashboard', 'Input nilai', 'Kelengkapan rapor', 'Generator leger']) {
        await expect(navigation.getByText(label, { exact: true })).toBeAttached();
    }

    if (role === 'admin') {
        for (const label of ['Data Guru', 'Peserta didik', 'Mata pelajaran', 'Tahun pelajaran', 'Kelas / rombel', 'Backup database']) {
            await expect(navigation.getByText(label, { exact: true })).toBeAttached();
        }
    }

    if (role === 'guru') {
        await expect(navigation.getByText('Histori audit', { exact: true })).toHaveCount(0);
        await expect(navigation.getByText('Backup database', { exact: true })).toHaveCount(0);
    }

    if (role === 'kepsek') {
        await expect(navigation.getByText('Histori audit', { exact: true })).toBeAttached();
        await expect(navigation.getByText('Backup database', { exact: true })).toHaveCount(0);
    }
}

async function assertNavigationToggles(page: Page): Promise<void> {
    const navigation = test.info().project.name === 'mobile-chrome'
        ? page.locator('[role="dialog"] nav')
        : page.locator('#desktop-sidebar-navigation');

    const groupButton = navigation.getByRole('button', { name: 'Data inti' });
    const submenu = navigation.locator('a[title="Peserta didik"]');

    await expect(groupButton).toHaveAttribute('aria-expanded', 'true');
    await groupButton.click();
    await expect(groupButton).toHaveAttribute('aria-expanded', 'false');
    await expect(submenu).toBeHidden();

    await groupButton.click();
    await expect(groupButton).toHaveAttribute('aria-expanded', 'true');
    await expect(submenu).toBeVisible();
}

async function assertAccountMenu(page: Page): Promise<void> {
    if (test.info().project.name === 'mobile-chrome') {
        await page.getByRole('button', { name: 'Tutup menu' }).click();
    }

    await page.locator('header').getByRole('button', { name: /Akun|Admin Demo|Guru Demo|Kepala PKBM/ }).click();
    const accountMenu = page.locator('[role="menu"]');

    await expect(accountMenu).toBeVisible();
    await expect(accountMenu.getByRole('menuitem', { name: 'Profil saya' })).toBeVisible();
    await expect(accountMenu.getByRole('menuitem', { name: 'Ubah password' })).toBeVisible();
    await expect(accountMenu.getByRole('menuitem', { name: 'Keluar' })).toBeVisible();
}

for (const role of Object.keys(accounts) as Role[]) {
    test(`all ${role} sidebar menus open without HTTP errors`, async ({ page }) => {
        await login(page, role);

        if (test.info().project.name === 'mobile-chrome') {
            await page.getByRole('button', { name: 'Buka menu navigasi' }).click();
            await expect(page.locator('[role="dialog"]')).toBeAttached();
            await expect(page.getByRole('button', { name: 'Tutup menu' })).toBeVisible();
        }

        await assertNavigationPresentation(page, role);
        await assertNavigationToggles(page);
        await assertAccountMenu(page);

        const links = await visibleMenuLinks(page);
        expect(links.length, `${role} should expose at least one sidebar link`).toBeGreaterThan(0);

        const failures: string[] = [];

        for (const link of links) {
            const response = await page.goto(link.href);
            const status = response?.status() ?? 0;
            const pathname = new URL(page.url()).pathname;
            const body = (await page.locator('body').textContent()) ?? '';

            if (status >= 400 || pathname === '/login' || /Server Error|Whoops|SQLSTATE/i.test(body)) {
                failures.push(`${link.label || '(tanpa label)'} -> ${new URL(link.href).pathname} [${status}, ${pathname}]`);
            }
        }

        expect(failures, `Menu gagal dibuka untuk role ${role}`).toEqual([]);

        const dimensions = await page.evaluate(() => ({
            viewport: window.innerWidth,
            document: document.documentElement.scrollWidth,
        }));

        expect(dimensions.document, `Horizontal overflow pada role ${role}`).toBeLessThanOrEqual(dimensions.viewport + 1);
    });
}
