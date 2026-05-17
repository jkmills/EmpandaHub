const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('Donors', () => {
    test.beforeEach(async ({ page }) => { await login(page); });

    test('donations list loads with seeded data', async ({ page }) => {
        await page.goto('/donors');
        const rows = await page.locator('table tbody tr').count();
        expect(rows).toBeGreaterThanOrEqual(5);
    });

    test('campaign list and progress bar', async ({ page }) => {
        await page.goto('/donors/campaigns');
        await expect(page.locator('.page-title')).toContainText('Campaign');
    });

    test('create donation form validates', async ({ page }) => {
        await page.goto('/donors/create');
        // Bypass HTML5 required to reach server-side validation
        await page.evaluate(() => {
            document.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        });
        await page.click('button[type="submit"]');
        await expect(page.locator('.field-error').first()).toBeVisible();
    });

    test('create donation', async ({ page }) => {
        await page.goto('/donors/create');
        await page.selectOption('select[name="contact_id"]', { index: 1 });
        await page.fill('input[name="amount"]', '100.00');
        await page.fill('input[name="donated_on"]', '2026-05-01');
        await page.click('button[type="submit"]');
        await expect(page.locator('.flash-success')).toContainText('recorded');
    });

    test('donation show page renders', async ({ page }) => {
        await page.goto('/donors');
        const href = await page.locator('table tbody tr:first-child a').first().getAttribute('href');
        await page.goto(href);
        await expect(page.locator('.page-title')).toBeVisible();
    });

    test('LYBUNT report', async ({ page }) => {
        await page.goto('/donors/lybunt');
        await expect(page.locator('.page-title')).toContainText('LYBUNT');
    });

    test('SYBUNT report', async ({ page }) => {
        await page.goto('/donors/sybunt');
        await expect(page.locator('.page-title')).toContainText('SYBUNT');
    });

    test('batch receipt page', async ({ page }) => {
        await page.goto('/donors/batch-receipt');
        await expect(page.locator('.page-title')).toContainText('Receipt');
    });
});
