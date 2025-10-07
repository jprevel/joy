import { test, expect } from '@playwright/test';

test.describe('Quick Role Verification Tests - Constitutional Requirements', () => {

  test('Admin Role - Basic Access Verification', async ({ page }) => {
    await page.context().clearCookies();

    // Login as admin
    await page.goto('http://localhost:8001/login');
    await page.fill('#email', 'admin@example.com');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('domcontentloaded');

    // Navigate to admin dashboard
    await page.goto('http://localhost:8001/admin');
    await page.waitForLoadState('domcontentloaded');

    // Verify admin dashboard loads
    await expect(page.locator('h1').filter({ hasText: 'Joy Admin Dashboard' })).toBeVisible();

    // Verify admin can access user management
    await page.click('text=Manage Users');
    await expect(page.url()).toContain('/admin/users');
    await expect(page.locator('h1').filter({ hasText: 'User Management' })).toBeVisible();

    // Verify admin can access client management
    await page.goto('http://localhost:8001/admin');
    await page.click('text=Manage Clients');
    await expect(page.url()).toContain('/admin/clients');
    await expect(page.locator('h1').filter({ hasText: 'Client Management' })).toBeVisible();

    console.log('✅ Admin role verification PASSED');
  });

  test('Agency Role - Basic Access Verification', async ({ page }) => {
    await page.context().clearCookies();

    // Login as agency
    await page.goto('http://localhost:8001/login');
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('domcontentloaded');

    // Verify agency calendar access
    await expect(page.url()).toContain('/calendar/agency');

    // Verify agency user info
    await expect(page.locator('text=Shaira Hernandez')).toBeVisible();
    await expect(page.locator('text=Agency')).toBeVisible();

    // Verify agency cannot access admin areas
    await page.goto('http://localhost:8001/admin');
    await expect(page.url()).not.toContain('/admin');

    console.log('✅ Agency role verification PASSED');
  });

  test('Security Boundary - Cross-Role Access Control', async ({ page }) => {
    await page.context().clearCookies();

    // Test unauthenticated access to protected areas
    const protectedUrls = [
      '/admin',
      '/admin/users',
      '/admin/clients',
      '/calendar/admin',
      '/calendar/agency'
    ];

    for (const url of protectedUrls) {
      await page.goto(`http://localhost:8001${url}`);
      // Should be redirected to login
      await expect(page.url()).toContain('/login');
    }

    console.log('✅ Security boundary verification PASSED');
  });

  test('Magic Link Security Pattern', async ({ page }) => {
    await page.context().clearCookies();

    // Test magic link URL patterns
    const magicLinkUrls = [
      '/client/invalid_token',
      '/client/test_token_123/calendar',
      '/client/malicious<script>/concept/1'
    ];

    for (const url of magicLinkUrls) {
      await page.goto(`http://localhost:8001${url}`);

      // Should handle gracefully (either show client content or error)
      await expect(page.locator('body')).toBeVisible();

      // Should not crash or expose sensitive information
      const hasError = await page.locator('text=Invalid access').isVisible();
      const hasUnauthorized = await page.locator('text=401').isVisible();
      const hasContent = await page.locator('[data-testid="client-content"]').isVisible();

      // One of these should be true
      expect(hasError || hasUnauthorized || hasContent).toBeTruthy();
    }

    console.log('✅ Magic link security verification PASSED');
  });

  test('Session Security', async ({ page }) => {
    await page.context().clearCookies();

    // Login as admin
    await page.goto('http://localhost:8001/login');
    await page.fill('#email', 'admin@example.com');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('domcontentloaded');

    // Verify admin access
    await page.goto('http://localhost:8001/admin');
    await expect(page.locator('h1').filter({ hasText: 'Joy Admin Dashboard' })).toBeVisible();

    // Logout
    await page.click('text=Logout');
    await expect(page.url()).toContain('/login');

    // Verify cannot access admin after logout
    await page.goto('http://localhost:8001/admin');
    await expect(page.url()).toContain('/login');

    console.log('✅ Session security verification PASSED');
  });
});