const { test, expect } = require('@playwright/test');
const { login } = require('./helpers');

test.describe('Security', () => {
    test('sensitive paths blocked (403)', async ({ page }) => {
        for (const path of ['/config/config.php', '/core/Auth.php', '/cron/daily.php', '/vendor/autoload.php']) {
            const res = await page.goto(`http://localhost:8080${path}`);
            expect(res.status(), `Expected 403 for ${path}`).toBe(403);
        }
    });

    test('CSRF token required on POST — rejects missing token', async ({ page }) => {
        await login(page);
        // Bypass normal form — raw POST without CSRF token
        const res = await page.request.post('http://localhost:8080/crm', {
            form: { first_name: 'Bad', last_name: 'Actor', email: 'bad@actor.com' }
        });
        // Should get 400 or redirect to login, not 200 success
        expect([302, 400, 403]).toContain(res.status());
    });

    test('readonly user cannot create contacts', async ({ page }) => {
        await page.goto('/auth/login');
        await page.fill('input[name="email"]', 'readonly@example.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.goto('/crm/create');
        // Should get 403
        await expect(page.locator('h1')).toContainText('403');
    });

    test('installer blocked when config exists', async ({ page }) => {
        const res = await page.goto('http://localhost:8080/install/install.php');
        // Already installed — should show block message, not 500
        expect(res.status()).toBe(200);
        await expect(page.locator('body')).toContainText('Already installed');
    });

    test('XSS: contact name is escaped in output', async ({ page }) => {
        await login(page);
        await page.goto('/crm/create');
        await page.fill('input[name="first_name"]', '<script>alert(1)</script>');
        await page.fill('input[name="last_name"]', 'XSSTest');
        await page.click('button[type="submit"]');
        // If XSS was executed, dialog would appear — check it didn't
        let dialogFired = false;
        page.on('dialog', () => { dialogFired = true; });
        await page.waitForTimeout(500);
        expect(dialogFired).toBe(false);
        // The raw <script> should not be in DOM as executed script
        const html = await page.content();
        expect(html).toContain('&lt;script&gt;');
    });
});
