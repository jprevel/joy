import { test, expect } from '@playwright/test';

test.describe('Admin Role - Dashboard and Functionality', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();

    // Login as admin
    await page.goto('http://localhost:8000/login');
    await page.waitForLoadState('domcontentloaded');
    await page.fill('#email', 'admin@example.com');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('domcontentloaded');
  });

  test('should access admin dashboard', async ({ page }) => {
    const currentUrl = page.url();

    // Navigate to admin dashboard if not already there
    if (!currentUrl.includes('/admin') || currentUrl.includes('/calendar')) {
      await page.goto('http://localhost:8000/admin');
      await page.waitForLoadState('domcontentloaded');
    }

    // Verify admin dashboard loads
    await expect(page.locator('h1')).toContainText('Joy Admin Dashboard');

    // Verify admin-specific navigation elements
    await expect(page.locator('text=Audit Logs')).toBeVisible();
    await expect(page.locator('text=User Management')).toBeVisible();
    await expect(page.locator('text=Client Management')).toBeVisible();
  });

  test('should access user management', async ({ page }) => {
    await page.goto('http://localhost:8000/admin');
    await page.click('text=Manage Users');

    await expect(page.url()).toContain('/admin/users');
    await expect(page.locator('h1')).toContainText('User Management');

    // Verify user statistics
    await expect(page.locator('text=Total Users')).toBeVisible();
    await expect(page.locator('text=Admin Users')).toBeVisible();
    await expect(page.locator('text=Agency Users')).toBeVisible();
    await expect(page.locator('table')).toBeVisible();
  });

  test('should access client management', async ({ page }) => {
    await page.goto('http://localhost:8000/admin');
    await page.click('text=Manage Clients');

    await expect(page.url()).toContain('/admin/clients');
    await expect(page.locator('h1')).toContainText('Client Management');

    // Verify client statistics
    await expect(page.locator('text=Total Clients')).toBeVisible();
    await expect(page.locator('text=Content Items')).toBeVisible();
    await expect(page.locator('table')).toBeVisible();
  });

  test('should access audit logs', async ({ page }) => {
    await page.goto('http://localhost:8000/admin/audit/recent');

    await expect(page.url()).toContain('/admin/audit');
    await expect(page.locator('h1, h2, h3')).toContainText(/Audit|Log/);
  });

  test('should access admin calendar with full permissions', async ({ page }) => {
    await page.goto('http://localhost:8000/calendar/admin');

    // Verify admin calendar loads
    await expect(page.url()).toContain('/calendar/admin');
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    // Verify Add Content functionality
    await expect(page.locator('text=Add Content')).toBeVisible();
  });

  test('should be able to add content for any client', async ({ page }) => {
    await page.goto('http://localhost:8000/calendar/admin');

    const addContentButton = page.locator('text=Add Content');
    if (await addContentButton.isVisible()) {
      await addContentButton.click();

      // Admin can create for any client
      await expect(page.locator('select[name="client_id"]')).toBeVisible();
    }
  });

  test('should access Trello integration management', async ({ page }) => {
    await page.goto('http://localhost:8000/admin');
    await page.click('text=Trello Setup');

    await expect(page.url()).toContain('/admin/trello');
    await expect(page.locator('h1, h2, h3')).toContainText(/Trello|Integration/);
  });

  test('should not be able to access admin areas after logout', async ({ page }) => {
    await page.goto('http://localhost:8000/admin');

    // Logout
    await page.click('text=Logout');
    await page.waitForURL('**/login');

    // Try to access admin area
    await page.goto('http://localhost:8000/admin');
    await expect(page.url()).toContain('/login');
  });
});
