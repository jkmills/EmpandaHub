const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('Membership', () => {
    test.beforeEach(async ({ page }) => { await login(page); });

    test('membership list loads', async ({ page }) => {
        await page.goto('/membership');
        await expect(page.locator('.page-title')).toContainText('Membership');
        const rows = await page.locator('table tbody tr').count();
        expect(rows).toBeGreaterThan(0);
    });

    test('tiers list shows 3 seeded tiers', async ({ page }) => {
        await page.goto('/membership/tiers');
        const rows = await page.locator('table tbody tr').count();
        expect(rows).toBe(3);
        await expect(page.locator('table tbody')).toContainText('Lifetime');
    });

    test('create tier form validates name required', async ({ page }) => {
        await page.goto('/membership/tiers/create');
        // Bypass HTML5 required validation to test server-side
        await page.evaluate(() => {
            document.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        });
        await page.click('button[type="submit"]');
        await expect(page.locator('.field-error')).toContainText('required');
    });

    test('membership show page loads with dues section', async ({ page }) => {
        await page.goto('/membership');
        const href = await page.locator('table tbody tr:first-child a').first().getAttribute('href');
        await page.goto(href);
        await expect(page.locator('.page-title')).toBeVisible();
        await expect(page.locator('h3:has-text("Dues")')).toBeVisible();
        await expect(page.locator('button:has-text("Record")')).toBeVisible();
    });

    test('add dues payment', async ({ page }) => {
        await page.goto('/membership');
        const href = await page.locator('table tbody tr:first-child a').first().getAttribute('href');
        await page.goto(href);
        await page.fill('input[name="amount"]', '75.00');
        await page.fill('input[name="paid_on"]', '2026-05-01');
        await page.selectOption('select[name="method"]', 'Check');
        await page.click('button:has-text("Record Payment")');
        await expect(page.locator('.flash-success')).toBeVisible();
    });

    test('expiring memberships report', async ({ page }) => {
        await page.goto('/membership/expiring');
        await expect(page.locator('.page-title')).toContainText('Expiring');
    });

    test('overdue memberships report', async ({ page }) => {
        await page.goto('/membership/overdue');
        await expect(page.locator('.page-title')).toContainText('Overdue');
    });
});
