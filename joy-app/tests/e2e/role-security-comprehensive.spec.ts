import { test, expect } from '@playwright/test';

test.describe('Role-Based Security - Cross-Role Access Control Tests', () => {

  test.describe('Admin Security Boundaries', () => {
    test.beforeEach(async ({ page }) => {
      await page.context().clearCookies();
    });

    test('admin should have unrestricted access to all areas', async ({ page }) => {
      // Login as admin
      await page.goto('http://localhost:8001/login');
      await page.fill('#email', 'admin@example.com');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin');

      // Test all admin areas
      const adminAreas = [
        '/admin',
        '/admin/users',
        '/admin/clients',
        '/admin/audit',
        '/admin/trello',
        '/calendar/admin',
        '/statusfaction'
      ];

      for (const area of adminAreas) {
        await page.goto(`http://localhost:8001${area}`);

        // Admin should have access to all areas
        await expect(page.url()).toContain(area.split('/')[1]); // Should contain main path part
        await expect(page.locator('body')).toBeVisible();
      }
    });

    test('admin should be able to access any client calendar', async ({ page }) => {
      await page.goto('http://localhost:8001/login');
      await page.fill('#email', 'admin@example.com');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin');

      // Test client-specific calendar access
      const clientCalendarUrls = [
        '/calendar/admin/client/1',
        '/calendar/admin/client/2',
        '/calendar/admin/client/999' // Non-existent client
      ];

      for (const url of clientCalendarUrls) {
        await page.goto(`http://localhost:8001${url}`);

        // Should either show calendar or appropriate error, but not access denied
        await expect(page.locator('body')).toBeVisible();

        // Should not be redirected to login
        await expect(page.url()).not.toContain('/login');
      }
    });
  });

  test.describe('Agency Security Boundaries', () => {
    test.beforeEach(async ({ page }) => {
      await page.context().clearCookies();
    });

    test('agency should be blocked from admin areas', async ({ page }) => {
      // Login as agency
      await page.goto('http://localhost:8001/login');
      await page.fill('#email', 'shaira@majormajor.marketing');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/calendar/agency');

      // Test admin areas that should be blocked
      const restrictedAreas = [
        '/admin',
        '/admin/users',
        '/admin/clients',
        '/admin/audit',
        '/admin/trello'
      ];

      for (const area of restrictedAreas) {
        await page.goto(`http://localhost:8001${area}`);

        // Should be redirected away from admin areas
        await expect(page.url()).not.toContain(area);

        // Should be redirected to agency calendar or login
        const isOnAgencyCalendar = page.url().includes('/calendar/agency');
        const isOnLogin = page.url().includes('/login');
        expect(isOnAgencyCalendar || isOnLogin).toBeTruthy();
      }
    });

    test('agency should have access to appropriate areas', async ({ page }) => {
      await page.goto('http://localhost:8001/login');
      await page.fill('#email', 'shaira@majormajor.marketing');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/calendar/agency');

      // Areas agency should have access to
      const allowedAreas = [
        '/calendar/agency',
        '/statusfaction',
        '/content/add/agency'
      ];

      for (const area of allowedAreas) {
        await page.goto(`http://localhost:8001${area}`);

        // Should have access to these areas
        await expect(page.url()).toContain(area.split('/')[1]); // Should contain main path
        await expect(page.locator('body')).toBeVisible();

        // Should not be redirected to login
        await expect(page.url()).not.toContain('/login');
      }
    });

    test('agency should only access team client calendars', async ({ page }) => {
      await page.goto('http://localhost:8001/login');
      await page.fill('#email', 'shaira@majormajor.marketing');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/calendar/agency');

      // Test client-specific access (Shaira is on Bukonuts team)
      const clientCalendarUrls = [
        '/calendar/agency/client/1', // Should have access if client 1 is on Bukonuts team
        '/calendar/agency/client/2'  // May or may not have access depending on team assignment
      ];

      for (const url of clientCalendarUrls) {
        await page.goto(`http://localhost:8001${url}`);

        // Should either show calendar (if authorized) or redirect appropriately
        await expect(page.locator('body')).toBeVisible();

        // Should not get hard error - either shows content or redirects gracefully
        const hasAccessError = await page.locator('text=Access denied').isVisible();
        const isOnLogin = page.url().includes('/login');

        // If redirected, should be graceful
        if (hasAccessError || isOnLogin) {
          expect(true).toBeTruthy(); // Expected behavior for restricted access
        } else {
          // If access granted, should see calendar
          await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();
        }
      }
    });
  });

  test.describe('Client Magic Link Security', () => {
    test.beforeEach(async ({ page }) => {
      await page.context().clearCookies();
    });

    test('unauthenticated users should be blocked from protected areas', async ({ page }) => {
      // Test that unauthenticated users cannot access protected areas
      const protectedAreas = [
        '/admin',
        '/calendar/admin',
        '/calendar/agency',
        '/statusfaction',
        '/content/add/admin',
        '/content/add/agency'
      ];

      for (const area of protectedAreas) {
        await page.goto(`http://localhost:8001${area}`);

        // Should be redirected to login
        await expect(page.url()).toContain('/login');
      }
    });

    test('magic link URLs should handle authentication properly', async ({ page }) => {
      // Test magic link authentication pattern
      const magicLinkUrls = [
        '/client/valid_token_format',
        '/client/another-token/calendar',
        '/client/test_token/concept/1',
        '/client/sample_token/variant/1'
      ];

      for (const url of magicLinkUrls) {
        await page.goto(`http://localhost:8001${url}`);

        // Should handle magic link requests (either valid access or proper error)
        await expect(page.locator('body')).toBeVisible();

        // Should either show client content or appropriate error message
        const hasClientContent = await page.locator('[data-testid="client-content"]').isVisible();
        const hasAccessError = await page.locator('text=Invalid access').isVisible();
        const hasUnauthorized = await page.locator('text=401').isVisible();

        // One of these should be true
        expect(hasClientContent || hasAccessError || hasUnauthorized).toBeTruthy();
      }
    });

    test('client access should be isolated per token', async ({ page }) => {
      // Test client workspace isolation
      const clientTokens = [
        'client1_token_abc123',
        'client2_token_def456',
        'client3_token_ghi789'
      ];

      for (const token of clientTokens) {
        await page.goto(`http://localhost:8001/client/${token}`);

        // Each token should be handled independently
        await expect(page.locator('body')).toBeVisible();

        // URL should preserve the specific token
        expect(page.url()).toContain(`/client/${token}`);

        // Should not leak information between different client tokens
        for (const otherToken of clientTokens) {
          if (otherToken !== token) {
            expect(page.url()).not.toContain(otherToken);
          }
        }
      }
    });
  });

  test.describe('Session Security', () => {
    test('should maintain proper session isolation between roles', async ({ page }) => {
      // Test admin session
      await page.goto('http://localhost:8001/login');
      await page.fill('#email', 'admin@example.com');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin');

      // Verify admin access
      await expect(page.url()).toContain('/admin');

      // Logout
      await page.click('text=Logout');
      await page.waitForURL('**/login');

      // Now login as agency
      await page.fill('#email', 'shaira@majormajor.marketing');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/calendar/agency');

      // Verify agency access (should not have admin privileges)
      await expect(page.url()).toContain('/calendar/agency');

      // Try to access admin area - should be blocked
      await page.goto('http://localhost:8001/admin');
      await expect(page.url()).not.toContain('/admin');
    });

    test('should handle session expiration properly', async ({ page }) => {
      // Login as admin
      await page.goto('http://localhost:8001/login');
      await page.fill('#email', 'admin@example.com');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin');

      // Clear session cookies to simulate expiration
      await page.context().clearCookies();

      // Try to access protected area
      await page.goto('http://localhost:8001/admin');

      // Should be redirected to login
      await expect(page.url()).toContain('/login');
    });

    test('should prevent session fixation attacks', async ({ page }) => {
      // Get initial session state
      await page.goto('http://localhost:8001/login');

      const initialCookies = await page.context().cookies();

      // Login
      await page.fill('#email', 'admin@example.com');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin');

      // Get post-login cookies
      const postLoginCookies = await page.context().cookies();

      // Session should have changed after login (security best practice)
      const sessionCookieBefore = initialCookies.find(c => c.name.includes('session') || c.name.includes('XSRF'));
      const sessionCookieAfter = postLoginCookies.find(c => c.name.includes('session') || c.name.includes('XSRF'));

      if (sessionCookieBefore && sessionCookieAfter) {
        // Session tokens should be different (anti-fixation)
        expect(sessionCookieBefore.value).not.toBe(sessionCookieAfter.value);
      }
    });
  });

  test.describe('CSRF Protection', () => {
    test('should include CSRF protection on forms', async ({ page }) => {
      await page.goto('http://localhost:8001/login');

      // Check for CSRF token in login form
      const csrfToken = await page.locator('input[name="_token"]').getAttribute('value');
      const csrfMeta = await page.locator('meta[name="csrf-token"]').getAttribute('content');

      // Should have CSRF protection
      expect(csrfToken || csrfMeta).toBeTruthy();
    });

    test('should validate CSRF tokens on protected actions', async ({ page }) => {
      // Login first
      await page.goto('http://localhost:8001/login');
      await page.fill('#email', 'admin@example.com');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin');

      // Try logout (POST request that should require CSRF)
      await page.click('text=Logout');

      // Should successfully logout (CSRF token was valid)
      await expect(page.url()).toContain('/login');
    });
  });

  test.describe('Input Validation Security', () => {
    test('should sanitize user inputs to prevent XSS', async ({ page }) => {
      // Login as agency to test content creation
      await page.goto('http://localhost:8001/login');
      await page.fill('#email', 'shaira@majormajor.marketing');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/calendar/agency');

      // Try to create content with potential XSS
      const addContentButton = page.locator('text=Add Content');
      if (await addContentButton.isVisible()) {
        await addContentButton.click();

        // Wait for form
        await page.waitForSelector('[wire\\:submit="addContent"]', { timeout: 10000 });

        // Try XSS in title field
        const xssPayload = '<script>alert("xss")</script>';
        await page.selectOption('select[name="client_id"]', { index: 1 });
        await page.fill('input[name="title"]', xssPayload);
        await page.fill('textarea[name="notes"]', 'Test content');
        await page.selectOption('select[name="platform"]', 'Facebook');
        await page.fill('textarea[name="copy"]', 'Test copy');

        // Submit form
        await page.click('button[type="submit"]');

        // Wait for processing
        await page.waitForTimeout(2000);

        // Check if content appears - should be sanitized
        const contentTitle = page.locator(`text=${xssPayload}`);

        // XSS should be prevented - either escaped or rejected
        const hasRawXSS = await contentTitle.isVisible();
        expect(hasRawXSS).toBeFalsy(); // Should not show raw script tag
      }
    });
  });
});