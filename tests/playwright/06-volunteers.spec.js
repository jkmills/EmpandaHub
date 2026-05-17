const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('Volunteers', () => {
    test.beforeEach(async ({ page }) => { await login(page); });

    test('volunteer roster loads', async ({ page }) => {
        await page.goto('/volunteers');
        const rows = await page.locator('table tbody tr').count();
        expect(rows).toBeGreaterThanOrEqual(5);
    });

    test('volunteer show page has log hours and hours log sections', async ({ page }) => {
        await page.goto('/volunteers');
        const href = await page.locator('table tbody tr:first-child a').first().getAttribute('href');
        await page.goto(href);
        await expect(page.locator('h3:has-text("Log Hours")')).toBeVisible();
        await expect(page.locator('h3:has-text("Hours Log")')).toBeVisible();
    });

    test('log hours on volunteer', async ({ page }) => {
        await page.goto('/volunteers');
        const href = await page.locator('table tbody tr:first-child a').first().getAttribute('href');
        await page.goto(href);
        await page.fill('input[name="hours"]', '3.5');
        await page.fill('input[name="activity_date"]', '2026-05-01');
        await page.fill('input[name="description"]', 'Playwright test hours');
        await page.click('button:has-text("Log Hours")');
        await expect(page.locator('.flash-success')).toBeVisible();
    });

    test('shifts page loads', async ({ page }) => {
        await page.goto('/volunteers/shifts');
        await expect(page.locator('.page-title')).toContainText('Shift');
    });

    test('create shift form validates', async ({ page }) => {
        await page.goto('/volunteers/shifts/create');
        await page.evaluate(() => {
            document.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        });
        await page.click('button[type="submit"]');
        await expect(page.locator('.field-error').first()).toBeVisible();
    });

    test('create volunteer validates required fields', async ({ page }) => {
        await page.goto('/volunteers/create');
        await page.evaluate(() => {
            document.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        });
        await page.click('button[type="submit"]');
        await expect(page.locator('.field-error').first()).toBeVisible();
    });
});
