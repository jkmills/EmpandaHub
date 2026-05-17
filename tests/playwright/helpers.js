async function login(page, email = 'admin@example.com', password = 'password') {
    await page.goto('/auth/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');
}

module.exports = { login };
