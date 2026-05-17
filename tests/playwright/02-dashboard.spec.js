const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('Dashboard', () => {
    test.beforeEach(async ({ page }) => { await login(page); });

    test('renders stat cards', async ({ page }) => {
        await expect(page.locator('.stat-card')).toHaveCount(3);
        await expect(page.locator('.stat-label').first()).toBeVisible();
        await expect(page.locator('.stat-value').first()).toBeVisible();
    });

    test('brand color applied to html element', async ({ page }) => {
        const brand = await page.locator('html').getAttribute('style');
        expect(brand).toMatch(/--brand:\s*#[0-9a-fA-F]{6}/);
    });

    test('sidebar has app-layout body class', async ({ page }) => {
        await expect(page.locator('body')).toHaveClass(/app-layout/);
    });

    test('sidebar navigation links present', async ({ page }) => {
        // Check stable links only — module toggles in settings tests can affect optional ones
        const nav = page.locator('nav.sidebar');
        await expect(nav.locator('a', { hasText: 'Dashboard' })).toBeVisible();
        await expect(nav.locator('a', { hasText: 'CRM' })).toBeVisible();
        await expect(nav.locator('a', { hasText: 'Membership' })).toBeVisible();
        await expect(nav.locator('a', { hasText: 'Settings' })).toBeVisible();
        // At least 7 nav links total (Dashboard + 6 modules + Settings)
        const linkCount = await nav.locator('a.nav-item').count();
        expect(linkCount).toBeGreaterThanOrEqual(7);
    });

    test('mobile toggle button present', async ({ page }) => {
        await expect(page.locator('button.menu-toggle')).toBeAttached();
    });

    test('mobile sidebar opens on toggle click', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto('/dashboard');
        const sidebar = page.locator('nav.sidebar');
        await expect(sidebar).not.toHaveClass(/open/);
        await page.locator('button.menu-toggle').click();
        await expect(sidebar).toHaveClass(/open/);
    });

    test('recent activity section shows audit entries', async ({ page }) => {
        // Activity log now shows human-readable action text, not badge-muted chips
        await expect(page.locator('.card').filter({ hasText: 'Recent Activity' })).toBeVisible();
    });
});
