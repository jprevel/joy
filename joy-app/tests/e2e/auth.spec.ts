import { test, expect } from '@playwright/test';

test.describe('Authentication - Login and Logout', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
  });

  test.describe('Login', () => {
    test('should login as admin', async ({ page }) => {
      await page.goto('http://localhost:8000/login');
      await page.waitForLoadState('domcontentloaded');

      await expect(page.locator('h1')).toContainText('Joy');

      await page.fill('#email', 'admin@example.com');
      await page.fill('#password', 'password');

      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle' }),
        page.click('button[type="submit"]')
      ]);

      const currentUrl = page.url();
      expect(currentUrl).not.toContain('/login');
      await expect(page.locator('text=Logout')).toBeVisible();
    });

    test('should login as agency user', async ({ page }) => {
      await page.goto('http://localhost:8000/login');
      await page.waitForLoadState('domcontentloaded');

      await page.fill('#email', 'shaira@majormajor.marketing');
      await page.fill('#password', 'password');

      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle' }),
        page.click('button[type="submit"]')
      ]);

      await expect(page.url()).toContain('/calendar');
      await expect(page.locator('text=Logout')).toBeVisible();
    });

    test('should reject invalid credentials', async ({ page }) => {
      await page.goto('http://localhost:8000/login');
      await page.waitForLoadState('domcontentloaded');

      await page.fill('#email', 'invalid@example.com');
      await page.fill('#password', 'wrongpassword');
      await page.click('button[type="submit"]');

      // Should stay on login page or show error
      await page.waitForTimeout(1000);
      const currentUrl = page.url();
      expect(currentUrl).toContain('/login');
    });
  });

  test.describe('Logout', () => {
    test('should logout admin user and redirect to login', async ({ page }) => {
      // Login as admin
      await page.goto('http://localhost:8000/login');
      await page.fill('#email', 'admin@majormajor.marketing');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');

      // Verify we're logged in
      await expect(page.locator('text=Logout')).toBeVisible();

      // Click logout
      await page.click('text=Logout');

      // Should redirect to login page
      await page.waitForURL('**/login', { timeout: 5000 });
      await expect(page.url()).toContain('/login');
      await expect(page.locator('#email')).toBeVisible();
    });

    test('should logout agency user and redirect to login', async ({ page }) => {
      // Login as agency
      await page.goto('http://localhost:8000/login');
      await page.fill('#email', 'shaira@majormajor.marketing');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');

      // Verify we're logged in
      await expect(page.locator('text=Logout')).toBeVisible();

      // Click logout
      await page.click('text=Logout');

      // Should redirect to login page
      await page.waitForURL('**/login', { timeout: 5000 });
      await expect(page.url()).toContain('/login');
    });
  });
});
