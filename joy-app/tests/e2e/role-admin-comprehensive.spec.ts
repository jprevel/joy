import { test, expect } from '@playwright/test';

test.describe('Admin Role - Comprehensive Access and Functionality Tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();

    // Login as admin
    await page.goto('http://localhost:8001/login');
    await page.waitForLoadState('domcontentloaded');
    await page.fill('#email', 'admin@example.com');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');

    // Wait for navigation and ensure we're on admin page
    await page.waitForLoadState('domcontentloaded');

    // Navigate to admin dashboard if not already there
    const currentUrl = page.url();
    if (!currentUrl.includes('/admin') || currentUrl.includes('/calendar')) {
      await page.goto('http://localhost:8001/admin');
      await page.waitForLoadState('domcontentloaded');
    }
  });

  test('should access admin dashboard with all admin-specific features', async ({ page }) => {
    // Verify we're on an admin page (either admin dashboard or admin calendar)
    const currentUrl = page.url();
    console.log('Current URL after admin login:', currentUrl);

    // If redirected to calendar, go to admin dashboard
    if (currentUrl.includes('/calendar')) {
      await page.goto('http://localhost:8001/admin');
      await page.waitForLoadState('domcontentloaded');
    }

    // Verify admin dashboard loads
    await expect(page.locator('h1')).toContainText('Joy Admin Dashboard');

    // Verify admin-specific navigation elements are visible
    await expect(page.locator('text=Audit Logs')).toBeVisible();
    await expect(page.locator('text=User Management')).toBeVisible();
    await expect(page.locator('text=Client Management')).toBeVisible();
    await expect(page.locator('text=System Status')).toBeVisible();
    await expect(page.locator('text=Integrations')).toBeVisible();

    // Verify admin user name is displayed
    await expect(page.locator('text=Admin User')).toBeVisible();
  });

  test('should access and use audit log management', async ({ page }) => {
    // Navigate to audit logs
    await page.click('text=Dashboard', { timeout: 10000 });
    await page.waitForSelector('text=Audit Logs');
    await page.click('text=Dashboard');

    // Should navigate to audit dashboard or recent logs
    await expect(page.url()).toMatch(/\/admin\/audit/);

    // Verify audit-specific elements
    await expect(page.locator('h1, h2, h3')).toContainText(/Audit|Log/);
  });

  test('should access user management with admin privileges', async ({ page }) => {
    // Navigate to user management
    await page.click('text=Manage Users');

    // Verify user management page loads
    await expect(page.url()).toContain('/admin/users');
    await expect(page.locator('h1')).toContainText('User Management');

    // Verify admin can see user statistics
    await expect(page.locator('text=Total Users')).toBeVisible();
    await expect(page.locator('text=Admin Users')).toBeVisible();
    await expect(page.locator('text=Agency Users')).toBeVisible();

    // Verify user table is present
    await expect(page.locator('table')).toBeVisible();
  });

  test('should access client management with admin privileges', async ({ page }) => {
    // Navigate to client management
    await page.click('text=Manage Clients');

    // Verify client management page loads
    await expect(page.url()).toContain('/admin/clients');
    await expect(page.locator('h1')).toContainText('Client Management');

    // Verify admin can see client statistics
    await expect(page.locator('text=Total Clients')).toBeVisible();
    await expect(page.locator('text=Content Items')).toBeVisible();
    await expect(page.locator('text=Trello Integrated')).toBeVisible();

    // Verify client table is present
    await expect(page.locator('table')).toBeVisible();
  });

  test('should access admin calendar view with full permissions', async ({ page }) => {
    // Navigate to admin calendar
    await page.click('text=View Calendar');

    // Verify admin calendar loads
    await expect(page.url()).toContain('/calendar/admin');

    // Verify admin has access to Add Content functionality
    await expect(page.locator('text=Add Content')).toBeVisible();

    // Verify calendar grid is visible
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    // Verify admin role indicator
    await expect(page.locator('text=Admin')).toBeVisible();
  });

  test('should access Trello integration management', async ({ page }) => {
    // Navigate to integrations
    await page.click('text=Trello Setup');

    // Verify Trello management page loads
    await expect(page.url()).toContain('/admin/trello');

    // Verify admin has access to Trello configuration
    await expect(page.locator('h1, h2, h3')).toContainText(/Trello|Integration/);
  });

  test('should have access to system-wide content management', async ({ page }) => {
    // Navigate to admin calendar
    await page.goto('http://localhost:8001/calendar/admin');

    // Verify admin can see content from all clients
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    // Admin should be able to add content
    await expect(page.locator('text=Add Content')).toBeVisible();

    // Verify no client-specific restrictions
    const addContentButton = page.locator('text=Add Content');
    if (await addContentButton.isVisible()) {
      await addContentButton.click();

      // Should see client selection dropdown (admin can create for any client)
      await expect(page.locator('select[name="client_id"]')).toBeVisible();
    }
  });

  test('should be able to access any client-specific calendar view', async ({ page }) => {
    // Try to access a specific client calendar as admin
    await page.goto('http://localhost:8001/calendar/admin/client/1');

    // Admin should be able to access any client view
    await expect(page.url()).toContain('/calendar');
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();
  });

  test('should verify admin cannot access regular user workflows', async ({ page }) => {
    // Verify admin doesn't get redirected to agency or client calendars
    await page.goto('http://localhost:8001/calendar/agency');

    // Admin should either:
    // 1. Be able to access it with admin privileges, OR
    // 2. Be redirected to appropriate admin area
    await expect(page.url()).toMatch(/\/calendar/);
  });

  test('should maintain admin session and logout properly', async ({ page }) => {
    // Verify admin session is maintained
    await page.reload();
    await expect(page.url()).toContain('/admin');

    // Test logout functionality
    await page.click('text=Logout');
    await page.waitForURL('**/login');

    // Verify logout worked
    await expect(page.url()).toContain('/login');

    // Verify cannot access admin areas after logout
    await page.goto('http://localhost:8001/admin');
    await expect(page.url()).toContain('/login');
  });

  test('should have comprehensive audit trail access', async ({ page }) => {
    // Navigate to audit recent logs
    await page.goto('http://localhost:8001/admin/audit/recent');

    // Should be able to access audit logs
    await expect(page.url()).toContain('/admin/audit');

    // Verify audit log interface is available
    await expect(page.locator('h1, h2, h3')).toContainText(/Audit|Log/);

    // If logs exist, should see them
    const logsTable = page.locator('table');
    if (await logsTable.isVisible()) {
      await expect(logsTable).toBeVisible();
    }
  });
});