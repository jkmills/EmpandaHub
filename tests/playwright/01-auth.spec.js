const { test, expect } = require('@playwright/test');

test.describe('Auth', () => {
    test('login page renders correctly', async ({ page }) => {
        await page.goto('/auth/login');
        await expect(page).toHaveTitle(/Login/);
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
        // Single body tag check
        const bodyCount = await page.locator('body').count();
        expect(bodyCount).toBe(1);
        // Auth layout class
        await expect(page.locator('body')).toHaveClass(/auth-layout/);
    });

    test('login rejects bad credentials', async ({ page }) => {
        await page.goto('/auth/login');
        await page.fill('input[name="email"]', 'wrong@example.com');
        await page.fill('input[name="password"]', 'wrongpass');
        await page.click('button[type="submit"]');
        await expect(page.locator('.flash-error')).toContainText('Invalid');
        await expect(page).toHaveURL(/login/);
    });

    test('login succeeds and redirects to dashboard', async ({ page }) => {
        await page.goto('/auth/login');
        await page.fill('input[name="email"]', 'admin@example.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        await expect(page.locator('.page-title')).toBeVisible();
    });

    test('logout works', async ({ page }) => {
        const { login } = require('./helpers');
        await login(page);
        await page.click('a[href*="logout"]');
        await page.waitForURL('**/login');
        await expect(page.locator('input[name="email"]')).toBeVisible();
    });

    test('unauthenticated access redirects to login', async ({ page }) => {
        await page.goto('/crm');
        await expect(page).toHaveURL(/login/);
    });
});
