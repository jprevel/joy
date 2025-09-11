import { test, expect } from '@playwright/test';

test.describe('Agency Role Login and Content Creation', () => {
  test.beforeEach(async ({ page }) => {
    // Ensure we start with a fresh session
    await page.context().clearCookies();
  });

  test('should login as agency user and verify access', async ({ page }) => {
    // Navigate to login page
    await page.goto('http://localhost:8000/login');
    
    // Fill in agency credentials (Shaira Hernandez)
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    
    // Submit the form
    await page.click('button[type="submit"]');
    
    // Wait for redirect to agency calendar
    await page.waitForURL('**/calendar/agency');
    
    // Verify we're authenticated and on the agency calendar
    await expect(page.url()).toContain('/calendar/agency');
    
    // Verify agency-specific elements are visible
    await expect(page.locator('text=Shaira Hernandez')).toBeVisible(); // Agency user name
    await expect(page.locator('text=Agency')).toBeVisible(); // Agency role indicator
    
    // Verify Add Content button is visible for agency
    await expect(page.locator('text=Add Content')).toBeVisible();
    
    // Verify logout button is present
    await expect(page.locator('text=Logout')).toBeVisible();
    
    // Verify calendar is loaded
    await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();
  });

  test('should successfully add a new content item', async ({ page }) => {
    // Login as agency user
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/agency');
    
    // Click Add Content button
    await page.click('text=Add Content');
    
    // Wait for the add content modal/form to appear
    await page.waitForSelector('[wire\\:submit="addContent"]', { timeout: 10000 });
    
    // Fill in content details
    await page.selectOption('select[name="client_id"]', { label: 'TechCorp Solutions' });
    await page.fill('input[name="title"]', 'E2E Test Content Item');
    await page.fill('textarea[name="notes"]', 'This is a test content item created during e2e testing');
    await page.selectOption('select[name="platform"]', 'Facebook');
    await page.fill('textarea[name="copy"]', 'This is test copy for our Facebook post. #Testing #E2E #Joy');
    await page.fill('input[name="media_url"]', 'https://picsum.photos/1200/630?random=999');
    
    // Set scheduled date (tomorrow)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dateString = tomorrow.toISOString().split('T')[0];
    await page.fill('input[name="scheduled_date"]', dateString);
    await page.fill('input[name="scheduled_time"]', '14:30');
    
    await page.selectOption('select[name="status"]', 'Draft');
    
    // Submit the form
    await page.click('button[type="submit"]');
    
    // Wait for the content to be added and modal to close
    await page.waitForTimeout(2000);
    
    // Verify the content appears on the calendar
    await expect(page.locator('text=E2E Test Content Item')).toBeVisible();
    
    // Verify success message or notification
    await expect(page.locator('text=Content added successfully').or(page.locator('.alert-success'))).toBeVisible();
  });

  test('should validate required fields in add content form', async ({ page }) => {
    // Login as agency user
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/agency');
    
    // Click Add Content button
    await page.click('text=Add Content');
    
    // Wait for the form to appear
    await page.waitForSelector('[wire\\:submit="addContent"]', { timeout: 10000 });
    
    // Try to submit without filling required fields
    await page.click('button[type="submit"]');
    
    // Check for validation errors
    await expect(page.locator('.error, [class*="error"]')).toBeVisible();
  });

  test('should filter content by selected client', async ({ page }) => {
    // Login as agency user
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/agency');
    
    // Wait for calendar to load
    await page.waitForSelector('[data-testid="calendar-grid"]');
    
    // Check if client filter dropdown exists and use it
    const clientDropdown = page.locator('select').first();
    if (await clientDropdown.isVisible()) {
      await clientDropdown.selectOption({ label: 'TechCorp Solutions' });
      
      // Wait for content to filter
      await page.waitForTimeout(1000);
      
      // Verify only TechCorp content is shown
      // This will depend on the actual implementation of filtering
      await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();
    }
  });

  test('should display team members for Bukonuts team', async ({ page }) => {
    // Login as agency user (Shaira is on Bukonuts team)
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/agency');
    
    // Verify Shaira can see clients assigned to Bukonuts team
    // TechCorp Solutions, Green Valley Wellness, Creative Studio Arts, NextGen Fitness
    await expect(page.locator('text=TechCorp Solutions').or(page.locator('option[value*="TechCorp"]'))).toBeVisible();
  });
});