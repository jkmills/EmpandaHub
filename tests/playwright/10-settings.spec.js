const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('Settings', () => {
    test.beforeEach(async ({ page }) => { await login(page); });

    test('settings page renders org profile and users', async ({ page }) => {
        await page.goto('/settings');
        await expect(page.locator('h3:has-text("Organization Profile")')).toBeVisible();
        await expect(page.locator('h3:has-text("Users")')).toBeVisible();
        const rows = await page.locator('table tbody tr').count();
        expect(rows).toBeGreaterThanOrEqual(3);
    });

    test('brand color picker shows current value', async ({ page }) => {
        await page.goto('/settings');
        const val = await page.locator('input[name="primary_color"]').inputValue();
        expect(val).toMatch(/^#[0-9a-fA-F]{6}$/);
    });

    test('save brand color updates all pages', async ({ page }) => {
        await page.goto('/settings');
        await page.fill('input[name="primary_color"]', '#10b981');
        await page.fill('input[name="name"]', 'Greenfield Community Foundation');
        for (const mod of ['crm', 'membership', 'donors', 'volunteers', 'events', 'grants', 'finance']) {
            const cb = page.locator(`input[name="mod_${mod}"]`);
            if (!(await cb.isChecked())) await cb.check();
        }
        await page.click('button:has-text("Save")');
        await expect(page.locator('.flash-success')).toContainText('saved');

        // Verify brand color applied on next page
        await page.goto('/dashboard');
        const style = await page.locator('html').getAttribute('style');
        expect(style).toContain('#10b981');

        // Reset back to default blue
        await page.goto('/settings');
        await page.fill('input[name="primary_color"]', '#2563eb');
        await page.fill('input[name="name"]', 'Greenfield Community Foundation');
        for (const mod of ['crm', 'membership', 'donors', 'volunteers', 'events', 'grants', 'finance']) {
            const cb = page.locator(`input[name="mod_${mod}"]`);
            if (!(await cb.isChecked())) await cb.check();
        }
        await page.click('button:has-text("Save")');
    });

    test('logo upload saves and displays in sidebar', async ({ page }) => {
        await page.goto('/settings');
        // Minimal valid 1x1 PNG
        const pngBuffer = Buffer.from(
            '89504e470d0a1a0a0000000d4948445200000001000000010802000000' +
            '9001 2e000000094944415478016360f8cfc00000000200017 3e016960000000049454e44ae426082'
                .replace(/\s/g, ''), 'hex'
        );
        await page.locator('input[type="file"][name="logo"]').setInputFiles({
            name: 'logo.png', mimeType: 'image/png', buffer: pngBuffer,
        });
        await page.fill('input[name="name"]', 'Greenfield Community Foundation');
        for (const mod of ['crm', 'membership', 'donors', 'volunteers', 'events', 'grants', 'finance']) {
            const cb = page.locator(`input[name="mod_${mod}"]`);
            if (!(await cb.isChecked())) await cb.check();
        }
        await page.click('button:has-text("Save")');
        await expect(page.locator('.flash-success')).toContainText('saved');
        await expect(page.locator('nav.sidebar img.sidebar-logo')).toBeVisible();
    });

    test('module visibility toggle hides nav item', async ({ page }) => {
        await page.goto('/settings');
        const grantsCb = page.locator('input[name="mod_grants"]');
        if (await grantsCb.isChecked()) await grantsCb.uncheck();
        await page.fill('input[name="name"]', 'Greenfield Community Foundation');
        await page.click('button:has-text("Save")');
        await expect(page.locator('.flash-success')).toContainText('saved');

        // Navigate away and back — Grants nav link should be absent
        await page.goto('/dashboard');
        await expect(page.locator('nav.sidebar a:has-text("Grants")')).not.toBeVisible();

        // Re-enable grants
        await page.goto('/settings');
        await page.locator('input[name="mod_grants"]').check();
        await page.click('button:has-text("Save")');
        await expect(page.locator('.flash-success')).toContainText('saved');
    });

    test('add user and verify appears in table', async ({ page }) => {
        await page.goto('/settings');
        const userForm = page.locator('form').filter({ has: page.locator('button:has-text("Create User")') });
        await userForm.locator('input[name="name"]').fill('Test PW User');
        await userForm.locator('input[name="email"]').fill(`pwtest${Date.now()}@test.com`);
        await userForm.locator('input[name="password"]').fill('Password123!');
        await userForm.locator('select[name="role"]').selectOption('staff');
        await userForm.locator('button:has-text("Create User")').click();
        await expect(page.locator('.flash-success')).toBeVisible();
        await expect(page.locator('table tbody')).toContainText('Test PW User');
    });

    // Staff access test uses its own page context (no admin beforeEach)
    test('staff cannot access settings', async ({ browser }) => {
        const ctx = await browser.newContext();
        const page = await ctx.newPage();
        await page.goto('http://localhost:8080/auth/login');
        await page.fill('input[name="email"]', 'staff@example.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        await page.goto('http://localhost:8080/settings');
        await expect(page.locator('h1')).toContainText('403');
        await ctx.close();
    });
});
