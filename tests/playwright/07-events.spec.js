const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('Events', () => {
    test.beforeEach(async ({ page }) => { await login(page); });

    test('events list loads', async ({ page }) => {
        await page.goto('/events');
        const rows = await page.locator('table tbody tr').count();
        expect(rows).toBeGreaterThanOrEqual(5);
    });

    test('event show page renders with registration section', async ({ page }) => {
        await page.goto('/events');
        const href = await page.locator('table tbody tr:first-child a').first().getAttribute('href');
        await page.goto(href);
        await expect(page.locator('.page-title')).toBeVisible();
    });

    test('check-in view renders', async ({ page }) => {
        await page.goto('/events');
        const href = await page.locator('table tbody tr:first-child a').first().getAttribute('href');
        const id = href.match(/\/events\/(\d+)/)?.[1];
        await page.goto(`/events/${id}/checkin`);
        await expect(page.locator('.page-title')).toBeVisible();
    });

    test('create event form validates', async ({ page }) => {
        await page.goto('/events/create');
        await page.evaluate(() => {
            document.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        });
        await page.click('button[type="submit"]');
        await expect(page.locator('.field-error').first()).toBeVisible();
    });

    test('create event', async ({ page }) => {
        await page.goto('/events/create');
        await page.fill('input[name="title"]', 'Playwright Test Event');
        await page.fill('input[name="event_date"]', '2026-12-01');
        await page.fill('input[name="location"]', 'Test Hall');
        await page.fill('input[name="capacity"]', '50');
        await page.click('button[type="submit"]');
        await expect(page.locator('.flash-success')).toContainText('created');
        await expect(page.locator('.page-title')).toContainText('Playwright Test Event');
    });

    test('register for event', async ({ page }) => {
        await page.goto('/events');
        const href = await page.locator('table tbody tr:first-child a').first().getAttribute('href');
        await page.goto(href);
        const regSelect = page.locator('select[name="contact_id"]');
        if (await regSelect.isVisible()) {
            await regSelect.selectOption({ index: 1 });
            await page.locator('button:has-text("Register")').click();
        }
        await expect(page).not.toHaveURL(/error/);
    });
});
