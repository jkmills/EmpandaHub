const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/playwright',
    timeout: 30000,
    retries: 0,
    workers: 1,
    reporter: [['list'], ['html', { open: 'never', outputFolder: 'tests/playwright-report' }]],
    use: {
        baseURL: 'http://localhost:8080',
        headless: true,
        screenshot: 'only-on-failure',
        video: 'off',
    },
});
