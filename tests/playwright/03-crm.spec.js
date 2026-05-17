const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('CRM', () => {
    test.beforeEach(async ({ page }) => { await login(page); });

    test('contact list loads with seeded data', async ({ page }) => {
        await page.goto('/crm');
        const rows = await page.locator('table tbody tr').count();
        expect(rows).toBeGreaterThan(10);
    });

    test('search filters contacts', async ({ page }) => {
        await page.goto('/crm');
        await page.fill('input[name="q"]', 'Alice');
        await page.press('input[name="q"]', 'Enter');
        await expect(page.locator('table tbody tr')).toHaveCount(1);
        await expect(page.locator('table tbody')).toContainText('Alice');
    });

    test('create contact form validates required fields', async ({ page }) => {
        await page.goto('/crm/create');
        // Bypass HTML5 required to reach server-side validation
        await page.evaluate(() => {
            document.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        });
        await page.click('button[type="submit"]');
        await expect(page.locator('.field-error').first()).toBeVisible();
        await expect(page.locator('.field-error').first()).toContainText('required');
    });

    test('create and view new contact', async ({ page }) => {
        await page.goto('/crm/create');
        await page.fill('input[name="first_name"]', 'Playwright');
        await page.fill('input[name="last_name"]', 'Test');
        await page.fill('input[name="email"]', 'playwright@test.com');
        await page.fill('input[name="phone"]', '555-0001');
        await page.fill('input[name="city"]', 'Testville');
        await page.fill('input[name="state"]', 'TX');
        await page.click('button[type="submit"]');
        await expect(page.locator('.flash-success')).toContainText('created');
        await expect(page.locator('.page-title')).toContainText('Playwright Test');
    });

    test('add note to contact', async ({ page }) => {
        await page.goto('/crm');
        const href = await page.locator('table tbody tr:first-child a').first().getAttribute('href');
        await page.goto(href);
        await page.fill('textarea[name="body"]', 'Playwright note test');
        await page.click('button:has-text("Add Note")');
        await expect(page.locator('.flash-success')).toContainText('Note added');
    });

    test('edit contact', async ({ page }) => {
        await page.goto('/crm');
        const href = await page.locator('table tbody tr:first-child a').first().getAttribute('href');
        const id = href.match(/\/crm\/(\d+)/)?.[1];
        await page.goto(`/crm/${id}/edit`);
        await expect(page.locator('input[name="first_name"]')).toHaveValue(/.+/);
        await page.fill('input[name="city"]', 'EditedCity');
        await page.click('button[type="submit"]');
        await expect(page.locator('.flash-success')).toContainText('updated');
    });

    test('csv export downloads', async ({ page }) => {
        await page.goto('/crm');
        const exportLink = page.locator('a[href*="export"]');
        await expect(exportLink).toBeVisible();
        const [download] = await Promise.all([
            page.waitForEvent('download'),
            exportLink.click(),
        ]);
        expect(download.suggestedFilename()).toMatch(/\.csv$/);
    });

    test('duplicates page loads', async ({ page }) => {
        await page.goto('/crm/duplicates');
        await expect(page.locator('.page-title')).toContainText('Duplicate');
    });

    test('import page renders file upload', async ({ page }) => {
        await page.goto('/crm/import');
        await expect(page.locator('input[type="file"]')).toBeVisible();
    });
});
