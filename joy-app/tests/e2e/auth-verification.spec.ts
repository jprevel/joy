import { test, expect } from '@playwright/test';

test.describe('Authentication Verification', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
  });

  test('should verify admin login works', async ({ page }) => {
    await page.goto('http://localhost:8000/login');
    await page.waitForLoadState('domcontentloaded');
    
    // Verify login page loads
    await expect(page.locator('h1')).toContainText('Joy');
    
    // Login as admin
    await page.fill('#email', 'admin@example.com');
    await page.fill('#password', 'password');
    
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]')
    ]);
    
    // Check we're redirected somewhere (not still on login)
    const currentUrl = page.url();
    console.log('Admin login redirect URL:', currentUrl);
    expect(currentUrl).not.toContain('/login');
    
    // Should have logout button (indicates successful login)
    await expect(page.locator('text=Logout')).toBeVisible();
  });

  test('should verify agency user (Shaira) login works', async ({ page }) => {
    await page.goto('http://localhost:8000/login');
    await page.waitForLoadState('domcontentloaded');
    
    // Login as Shaira
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]')
    ]);
    
    // Check we're redirected somewhere (not still on login)
    const currentUrl = page.url();
    console.log('Shaira login redirect URL:', currentUrl);
    expect(currentUrl).not.toContain('/login');
    
    // Should have logout button (indicates successful login)
    await expect(page.locator('text=Logout')).toBeVisible();
  });

  test('should verify client login works', async ({ page }) => {
    await page.goto('http://localhost:8000/login');
    await page.waitForLoadState('domcontentloaded');
    
    // Login as client
    await page.fill('#email', 'client@example.com');
    await page.fill('#password', 'password');
    
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]')
    ]);
    
    // Check we're redirected somewhere (not still on login)
    const currentUrl = page.url();
    console.log('Client login redirect URL:', currentUrl);
    expect(currentUrl).not.toContain('/login');
    
    // For client role, just verify we're authenticated by checking URL contains calendar
    expect(currentUrl).toContain('/calendar');
  });

  test('should show error for invalid credentials', async ({ page }) => {
    await page.goto('http://localhost:8000/login');
    await page.waitForLoadState('domcontentloaded');
    
    // Try invalid credentials
    await page.fill('#email', 'invalid@example.com');
    await page.fill('#password', 'wrongpassword');
    await page.click('button[type="submit"]');
    
    // Should stay on login page
    await expect(page.url()).toContain('/login');
    
    // Should show error message
    await expect(page.locator('.error').or(page.locator('[class*="error"]'))).toBeVisible();
  });
});