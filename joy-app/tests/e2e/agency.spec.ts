import { test, expect } from '@playwright/test';

test.describe('Agency Role - Calendar and Content Management', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();

    // Login as agency user
    await page.goto('http://localhost:8000/login');
    await page.waitForLoadState('domcontentloaded');
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/agency', { timeout: 10000 });
  });

  test('should access agency calendar', async ({ page }) => {
    await expect(page.url()).toContain('/calendar/agency');
    await expect(page.locator('text=Agency')).toBeVisible();
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();
    await expect(page.locator('text=Add Content')).toBeVisible();
  });

  test('should see team-specific clients only', async ({ page }) => {
    const addContentButton = page.locator('text=Add Content');
    if (await addContentButton.isVisible()) {
      await addContentButton.click();
      await page.waitForSelector('select[name="client_id"]', { timeout: 10000 });

      const clientDropdown = page.locator('select[name="client_id"]');
      await expect(clientDropdown).toBeVisible();

      const dropdownOptions = await clientDropdown.locator('option').allTextContents();
      expect(dropdownOptions.length).toBeGreaterThan(1); // More than just "Select Client"
    }
  });

  test('should create content for team clients', async ({ page }) => {
    const addContentButton = page.locator('text=Add Content');
    if (await addContentButton.isVisible()) {
      await addContentButton.click();
      await page.waitForSelector('[wire\\:submit="addContent"]', { timeout: 10000 });

      // Fill in content details
      await page.selectOption('select[name="client_id"]', { index: 1 });
      await page.fill('input[name="title"]', 'Agency E2E Test Content');
      await page.fill('textarea[name="notes"]', 'Test content created by agency user');
      await page.selectOption('select[name="platform"]', 'Facebook');
      await page.fill('textarea[name="copy"]', 'Test copy for agency-created content');
      await page.fill('input[name="media_url"]', 'https://picsum.photos/1200/630');

      // Set scheduled date
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      const dateString = tomorrow.toISOString().split('T')[0];
      await page.fill('input[name="scheduled_date"]', dateString);
      await page.fill('input[name="scheduled_time"]', '15:30');
      await page.selectOption('select[name="status"]', 'Draft');

      // Submit form
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);

      // Verify content appears
      await expect(page.locator('text=Agency E2E Test Content')).toBeVisible();
    }
  });

  test('should NOT have admin privileges', async ({ page }) => {
    await page.goto('http://localhost:8000/admin');

    // Should be redirected away from admin
    await expect(page.url()).not.toContain('/admin');
    await expect(page.url()).toContain('/calendar/agency');
  });

  test('should not see admin navigation elements', async ({ page }) => {
    await expect(page.locator('text=Agency')).toBeVisible();
    await expect(page.locator('text=Add Content')).toBeVisible();

    // Should NOT see admin elements
    await expect(page.locator('text=User Management')).not.toBeVisible();
    await expect(page.locator('text=Admin Dashboard')).not.toBeVisible();
  });

  test('should access statusfaction for weekly updates', async ({ page }) => {
    await page.goto('http://localhost:8000/statusfaction');

    await expect(page.url()).toContain('/statusfaction');
    await expect(page.locator('h1, h2, h3')).toContainText(/Status|Weekly|Update/);
  });

  test('should filter content by team clients', async ({ page }) => {
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();

    const clientFilter = page.locator('select').first();
    if (await clientFilter.isVisible()) {
      const options = await clientFilter.locator('option').allTextContents();
      expect(options.length).toBeGreaterThan(0);
    }
  });

  test('should maintain session and handle logout', async ({ page }) => {
    // Verify session persistence
    await page.reload();
    await expect(page.url()).toContain('/calendar/agency');

    // Test logout
    await page.click('text=Logout');
    await page.waitForURL('**/login');
    await expect(page.url()).toContain('/login');

    // Verify cannot access after logout
    await page.goto('http://localhost:8000/calendar/agency');
    await expect(page.url()).toContain('/login');
  });
});
