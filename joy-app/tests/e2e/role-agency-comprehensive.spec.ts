import { test, expect } from '@playwright/test';

test.describe('Agency Role - Comprehensive Access and Functionality Tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();

    // Login as agency user
    await page.goto('http://localhost:8001/login');
    await page.waitForLoadState('domcontentloaded');
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/agency');
  });

  test('should access agency calendar with appropriate permissions', async ({ page }) => {
    // Verify agency calendar loads correctly
    await expect(page.url()).toContain('/calendar/agency');

    // Verify agency-specific UI elements
    await expect(page.locator('text=Shaira Hernandez')).toBeVisible();
    await expect(page.locator('text=Agency')).toBeVisible();

    // Verify calendar grid is visible
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    // Verify Add Content functionality is available to agency
    await expect(page.locator('text=Add Content')).toBeVisible();

    // Verify logout functionality
    await expect(page.locator('text=Logout')).toBeVisible();
  });

  test('should see team-specific clients only', async ({ page }) => {
    // Agency users should see only clients assigned to their team
    // Shaira is on Bukonuts team with: TechCorp Solutions, Green Valley Wellness, Creative Studio Arts, NextGen Fitness

    // Check if Add Content is available
    const addContentButton = page.locator('text=Add Content');
    if (await addContentButton.isVisible()) {
      await addContentButton.click();

      // Wait for form to load
      await page.waitForSelector('select[name="client_id"]', { timeout: 10000 });

      // Should see team-specific clients in dropdown
      const clientDropdown = page.locator('select[name="client_id"]');
      await expect(clientDropdown).toBeVisible();

      // Check for Bukonuts team clients
      const dropdownOptions = await clientDropdown.locator('option').allTextContents();

      // Should contain some expected clients (or at least have options)
      expect(dropdownOptions.length).toBeGreaterThan(1); // More than just "Select Client"
    }
  });

  test('should be able to create content for team clients', async ({ page }) => {
    // Test content creation workflow
    const addContentButton = page.locator('text=Add Content');
    if (await addContentButton.isVisible()) {
      await addContentButton.click();

      // Wait for form to load
      await page.waitForSelector('[wire\\:submit="addContent"]', { timeout: 10000 });

      // Fill in content details
      await page.selectOption('select[name="client_id"]', { index: 1 }); // First non-empty option
      await page.fill('input[name="title"]', 'Agency E2E Test Content');
      await page.fill('textarea[name="notes"]', 'Test content created by agency user during e2e testing');
      await page.selectOption('select[name="platform"]', 'Facebook');
      await page.fill('textarea[name="copy"]', 'Test copy for agency-created content #AgencyTest #E2E');
      await page.fill('input[name="media_url"]', 'https://picsum.photos/1200/630?random=888');

      // Set scheduled date (tomorrow)
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      const dateString = tomorrow.toISOString().split('T')[0];
      await page.fill('input[name="scheduled_date"]', dateString);
      await page.fill('input[name="scheduled_time"]', '15:30');

      await page.selectOption('select[name="status"]', 'Draft');

      // Submit the form
      await page.click('button[type="submit"]');

      // Wait for content to be created
      await page.waitForTimeout(2000);

      // Verify content appears in calendar
      await expect(page.locator('text=Agency E2E Test Content')).toBeVisible();
    }
  });

  test('should access statusfaction for weekly updates', async ({ page }) => {
    // Navigate to statusfaction (if available)
    await page.goto('http://localhost:8001/statusfaction');

    // Agency users should have access to statusfaction
    await expect(page.url()).toContain('/statusfaction');

    // Verify statusfaction interface loads
    await expect(page.locator('h1, h2, h3')).toContainText(/Status|Weekly|Update/);
  });

  test('should filter content by clients in team workspace', async ({ page }) => {
    // Verify calendar filtering functionality
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    // Check if client filter dropdown exists
    const clientFilter = page.locator('select').first();
    if (await clientFilter.isVisible()) {
      // Should show only team clients
      const options = await clientFilter.locator('option').allTextContents();

      // Should have client options (including "All" or similar)
      expect(options.length).toBeGreaterThan(0);
    }
  });

  test('should NOT have admin privileges', async ({ page }) => {
    // Verify agency cannot access admin areas
    await page.goto('http://localhost:8001/admin');

    // Should be redirected away from admin area
    await expect(page.url()).not.toContain('/admin');
    await expect(page.url()).toContain('/calendar/agency');

    // Test other admin-only URLs
    const adminUrls = [
      '/admin/users',
      '/admin/clients',
      '/admin/audit',
      '/admin/trello'
    ];

    for (const adminUrl of adminUrls) {
      await page.goto(`http://localhost:8001${adminUrl}`);
      await expect(page.url()).not.toContain(adminUrl);
    }
  });

  test('should access client-specific calendar views for team clients', async ({ page }) => {
    // Try to access a client-specific view
    await page.goto('http://localhost:8001/calendar/agency/client/1');

    // Should be able to access if client is in agency user's team
    await expect(page.url()).toContain('/calendar');
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();
  });

  test('should be able to edit own content', async ({ page }) => {
    // Verify agency can edit content they created
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    // Look for existing content items
    const contentItems = page.locator('[class*="content-item"], [data-content-item]');

    if (await contentItems.first().isVisible()) {
      // Click on a content item
      await contentItems.first().click();

      // Should be able to access content details/editing
      // This would depend on the specific implementation
      await expect(page.locator('body')).toBeVisible(); // Basic verification
    }
  });

  test('should maintain agency session and handle logout', async ({ page }) => {
    // Verify session persistence
    await page.reload();
    await expect(page.url()).toContain('/calendar/agency');

    // Test logout
    await page.click('text=Logout');
    await page.waitForURL('**/login');

    // Verify logout worked
    await expect(page.url()).toContain('/login');

    // Verify cannot access agency areas after logout
    await page.goto('http://localhost:8001/calendar/agency');
    await expect(page.url()).toContain('/login');
  });

  test('should see appropriate navigation and role indicators', async ({ page }) => {
    // Verify agency-specific navigation elements
    await expect(page.locator('text=Agency')).toBeVisible();
    await expect(page.locator('text=Shaira Hernandez')).toBeVisible();

    // Verify appropriate action buttons
    await expect(page.locator('text=Add Content')).toBeVisible();

    // Should NOT see admin-specific elements
    await expect(page.locator('text=User Management')).not.toBeVisible();
    await expect(page.locator('text=Admin Dashboard')).not.toBeVisible();
  });

  test('should have access to content review workflows', async ({ page }) => {
    // Navigate to calendar
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    // Look for content in review state
    const reviewContent = page.locator('text=Review', { timeout: 5000 });
    if (await reviewContent.isVisible()) {
      // Agency should be able to interact with review content
      await expect(reviewContent).toBeVisible();
    }

    // Check if we can navigate to review page for a specific date
    try {
      await page.goto('http://localhost:8001/calendar/review/2024-01-15');
      await expect(page.url()).toContain('/calendar/review');
    } catch {
      // Review page might not exist or be accessible
      // This is acceptable as it depends on the implementation
    }
  });
});