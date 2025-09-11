import { test, expect } from '@playwright/test';

test.describe('Joy Basic Tests', () => {
  test('homepage loads successfully', async ({ page }) => {
    await page.goto('/');
    
    // Check if page loads without errors
    await expect(page).toHaveTitle(/Storytime|Joy|Laravel/);
  });

  test('admin panel is accessible', async ({ page }) => {
    await page.goto('/admin/login');
    
    // Check if login form is present
    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('admin login works with correct credentials', async ({ page }) => {
    await page.goto('/admin/login');
    
    // Fill in login form
    await page.fill('input[type="email"]', 'admin@plotwist.app');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');
    
    // Should redirect to admin dashboard
    await page.waitForURL('**/admin/**');
    
    // Check for admin interface elements
    await expect(page.locator('nav')).toBeVisible();
  });
});