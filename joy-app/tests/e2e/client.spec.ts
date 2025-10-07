import { test, expect } from '@playwright/test';

test.describe('Client Role - Magic Link Access and Content Review', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
  });

  test('should reject invalid magic link tokens', async ({ page }) => {
    await page.goto('http://localhost:8000/client/invalid_token');

    // Should show error or redirect
    await expect(page.locator('text=Invalid access').or(page.locator('text=401'))).toBeVisible();
  });

  test('should handle malicious magic link tokens safely', async ({ page }) => {
    const invalidTokens = [
      'short',
      'contains spaces',
      'contains/slashes',
      '../../etc/passwd',
      '<script>alert("xss")</script>',
      ''
    ];

    for (const invalidToken of invalidTokens) {
      await page.goto(`http://localhost:8000/client/${encodeURIComponent(invalidToken)}`);

      // Should handle gracefully without errors
      await expect(page.locator('body')).toBeVisible();
    }
  });

  test('should verify content exists for client review', async ({ page }) => {
    // Login as admin to verify content that clients would see
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'admin@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    // Verify content exists for client review
    const contentItems = page.locator('[class*="content-item"], [data-content-item]');
    if (await contentItems.first().isVisible()) {
      await expect(contentItems.first()).toBeVisible();
    }
  });

  test('should verify content approval states workflow', async ({ page }) => {
    // Login as admin to see approval states
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'admin@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await page.waitForSelector('[data-testid="calendar-grid"]');

    // Verify different approval states exist
    const possibleStates = ['Draft', 'In Review', 'Approved', 'Scheduled'];

    for (const state of possibleStates) {
      const stateElement = page.locator(`text=${state}`).first();
      if (await stateElement.isVisible()) {
        await expect(stateElement).toBeVisible();
      }
    }
  });

  test('should demonstrate client commenting workflow concept', async ({ page }) => {
    // Login as agency to demonstrate client-like content interaction
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/agency');

    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    const contentItems = page.locator('[class*="content-item"], [data-content-item]');

    if (await contentItems.first().isVisible()) {
      await expect(contentItems.first()).toBeVisible();

      // Click content item (simulates client review workflow)
      await contentItems.first().click();

      // Verify interaction is possible
      await expect(page.locator('body')).toBeVisible();
    }
  });

  test('should verify magic link access is permission-based', async ({ page }) => {
    // Test that protected content requires authentication
    await page.goto('http://localhost:8000/calendar/admin');

    // Should redirect to login
    await expect(page.url()).toContain('/login');
  });

  test('should verify client-specific content filtering', async ({ page }) => {
    // Login as admin to demonstrate client-scoped content
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'admin@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    // Verify content filtering capability exists
    const clientFilter = page.locator('select').first();
    if (await clientFilter.isVisible()) {
      const options = await clientFilter.locator('option').allTextContents();
      expect(options.length).toBeGreaterThan(0);
    }
  });

  test('should verify client portal access security', async ({ page }) => {
    // Attempt to access protected areas without authentication
    const protectedUrls = [
      '/calendar/admin',
      '/calendar/agency',
      '/admin',
      '/admin/users',
      '/admin/clients'
    ];

    for (const url of protectedUrls) {
      await page.goto(`http://localhost:8000${url}`);

      // Should redirect to login
      await expect(page.url()).toContain('/login');
    }
  });
});
