const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('Finance', () => {
    test.beforeEach(async ({ page }) => { await login(page); });

    test('finance report renders stat cards', async ({ page }) => {
        await page.goto('/finance');
        await expect(page.locator('.stat-card')).toHaveCount(6);
        await expect(page.locator('.stat-label:has-text("Total Revenue")')).toBeVisible();
        await expect(page.locator('.stat-label:has-text("Donations")')).toBeVisible();
        await expect(page.locator('.stat-label:has-text("Dues")')).toBeVisible();
        await expect(page.locator('.stat-label:has-text("Grant Income")')).toBeVisible();
    });

    test('date range filter works', async ({ page }) => {
        await page.goto('/finance');
        await page.fill('input[name="from"]', '2024-01-01');
        await page.fill('input[name="to"]', '2024-12-31');
        await page.click('button[type="submit"]');
        await expect(page.locator('.stat-value').first()).toBeVisible();
        const total = await page.locator('.stat-value').first().textContent();
        expect(total).toMatch(/\$[\d,]+/);
    });

    test('transaction ledger table visible with data', async ({ page }) => {
        await page.goto('/finance?from=2024-01-01&to=2026-12-31');
        await expect(page.locator('table tbody tr').first()).toBeVisible();
    });

    test('CSV export link triggers download', async ({ page }) => {
        await page.goto('/finance?from=2024-01-01&to=2026-12-31');
        const exportLink = page.locator('a[href*="export"]');
        await expect(exportLink).toBeVisible();
        const [download] = await Promise.all([
            page.waitForEvent('download'),
            exportLink.click(),
        ]);
        expect(download.suggestedFilename()).toMatch(/\.csv$/);
    });
});
