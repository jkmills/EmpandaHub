const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('Grants', () => {
    test.beforeEach(async ({ page }) => { await login(page); });

    test('grants pipeline grouped by status', async ({ page }) => {
        await page.goto('/grants');
        await expect(page.locator('.page-title')).toContainText('Grant');
        await expect(page.locator('h3, h4').first()).toBeVisible();
    });

    test('funders list loads', async ({ page }) => {
        await page.goto('/grants/funders');
        await expect(page.locator('.page-title')).toContainText('Funder');
    });

    test('grant show page renders with report section', async ({ page }) => {
        await page.goto('/grants');
        const href = await page.locator('a[href*="/grants/"]:not([href*="funders"]):not([href*="create"])').first().getAttribute('href');
        await page.goto(href);
        await expect(page.locator('.page-title')).toBeVisible();
        await expect(page.locator('h3:has-text("Report")')).toBeVisible();
    });

    test('create grant form validates', async ({ page }) => {
        await page.goto('/grants/create');
        await page.evaluate(() => {
            document.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        });
        await page.click('button[type="submit"]');
        await expect(page.locator('.field-error').first()).toBeVisible();
    });

    test('create funder form validates', async ({ page }) => {
        await page.goto('/grants/funders/create');
        await page.evaluate(() => {
            document.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        });
        await page.click('button[type="submit"]');
        await expect(page.locator('.field-error').first()).toBeVisible();
    });

    test('create funder', async ({ page }) => {
        await page.goto('/grants/funders/create');
        await page.fill('input[name="name"]', 'Playwright Foundation');
        await page.fill('input[name="website"]', 'https://playwright.dev');
        await page.click('button[type="submit"]');
        await expect(page.locator('.flash-success')).toBeVisible();
        await expect(page.locator('table')).toContainText('Playwright Foundation');
    });
});
