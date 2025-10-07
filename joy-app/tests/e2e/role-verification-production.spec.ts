import { test, expect } from '@playwright/test';

test.describe('Production Role Verification - Constitutional Requirements ✅', () => {

  test('🔐 Security Boundary - Cross-Role Access Control', async ({ page }) => {
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

    console.log('✅ CONSTITUTIONAL REQUIREMENT: Security boundary verification PASSED');
  });

  test('👤 Agency Role - Access Control Verification', async ({ page }) => {
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

    // CRITICAL: Verify agency cannot access admin areas
    await page.goto('http://localhost:8001/admin');
    await expect(page.url()).not.toContain('/admin');

    console.log('✅ CONSTITUTIONAL REQUIREMENT: Agency role access control PASSED');
  });

  test('🔗 Magic Link Security Pattern', async ({ page }) => {
    await page.context().clearCookies();

    // Test magic link URL patterns and security
    const magicLinkUrls = [
      '/client/invalid_token',
      '/client/test_token_123/calendar',
      '/client/malicious<script>/concept/1',
      '/client/../../etc/passwd',
      '/client/'
    ];

    for (const url of magicLinkUrls) {
      await page.goto(`http://localhost:8001${url}`);

      // Should handle gracefully without crashing
      await expect(page.locator('body')).toBeVisible();

      // Should either show proper error or client content (no system exposure)
      const hasError = await page.locator('text=Invalid access').isVisible();
      const hasUnauthorized = await page.locator('text=401').isVisible();
      const hasNotFound = await page.locator('text=404').isVisible();
      const hasClientContent = await page.locator('[data-testid="client-content"]').isVisible();

      // Should handle securely
      expect(hasError || hasUnauthorized || hasNotFound || hasClientContent).toBeTruthy();
    }

    console.log('✅ CONSTITUTIONAL REQUIREMENT: Magic link security PASSED');
  });

  test('🔒 Session Security and Authentication', async ({ page }) => {
    await page.context().clearCookies();

    // Login as admin
    await page.goto('http://localhost:8001/login');
    await page.fill('#email', 'admin@example.com');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('domcontentloaded');

    // Verify admin access works
    await page.goto('http://localhost:8001/admin');
    await expect(page.locator('h1').filter({ hasText: 'Joy Admin Dashboard' })).toBeVisible();

    // Test logout functionality
    await page.click('text=Logout');
    await expect(page.url()).toContain('/login');

    // CRITICAL: Verify cannot access admin after logout
    await page.goto('http://localhost:8001/admin');
    await expect(page.url()).toContain('/login');

    console.log('✅ CONSTITUTIONAL REQUIREMENT: Session security PASSED');
  });

  test('🏛️ Admin Role - Basic Dashboard Access', async ({ page }) => {
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

    // Verify admin-specific elements exist
    await expect(page.locator('text=Audit Logs')).toBeVisible();
    await expect(page.locator('text=User Management')).toBeVisible();
    await expect(page.locator('text=Client Management')).toBeVisible();

    console.log('✅ CONSTITUTIONAL REQUIREMENT: Admin dashboard access PASSED');
  });

  test('🛡️ Role Isolation - Agency Cannot Access Admin Functions', async ({ page }) => {
    await page.context().clearCookies();

    // Login as agency
    await page.goto('http://localhost:8001/login');
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('domcontentloaded');

    // Test that agency is blocked from ALL admin areas
    const adminUrls = [
      '/admin',
      '/admin/users',
      '/admin/clients',
      '/admin/audit',
      '/admin/trello'
    ];

    for (const url of adminUrls) {
      await page.goto(`http://localhost:8001${url}`);

      // Should be redirected away from admin areas
      await expect(page.url()).not.toContain(url);

      // Should be on agency calendar or login
      const isOnAgencyCalendar = page.url().includes('/calendar/agency');
      const isOnLogin = page.url().includes('/login');
      expect(isOnAgencyCalendar || isOnLogin).toBeTruthy();
    }

    console.log('✅ CONSTITUTIONAL REQUIREMENT: Agency admin isolation PASSED');
  });
});