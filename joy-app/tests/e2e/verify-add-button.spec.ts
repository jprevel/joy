import { test, expect } from '@playwright/test';

test.describe('Add Content Button Verification', () => {
  test('Add Content button should be visible for agency role', async ({ page }) => {
    await page.goto('http://localhost:8000/calendar/agency');
    await page.waitForLoadState('networkidle');
    
    // Check that we're in agency mode
    await expect(page.locator('text=AGENCY')).toBeVisible();
    
    // Check that the Add Content button is visible
    const addContentButton = page.locator('a:has-text("Add Content")');
    await expect(addContentButton).toBeVisible();
    
    // Verify it has the correct link
    await expect(addContentButton).toHaveAttribute('href', /\/content\/add\/agency/);
    
    console.log('✅ Add Content button is visible for agency role');
  });

  test('Add Content button should be visible for admin role', async ({ page }) => {
    await page.goto('http://localhost:8000/calendar/admin');
    await page.waitForLoadState('networkidle');
    
    // Check that we're in admin mode
    await expect(page.locator('text=ADMIN')).toBeVisible();
    
    // Check that the Add Content button is visible
    const addContentButton = page.locator('a:has-text("Add Content")');
    await expect(addContentButton).toBeVisible();
    
    // Verify it has the correct link
    await expect(addContentButton).toHaveAttribute('href', /\/content\/add\/admin/);
    
    console.log('✅ Add Content button is visible for admin role');
  });

  test('Add Content button should NOT be visible for client role', async ({ page }) => {
    await page.goto('http://localhost:8000/calendar/client');
    await page.waitForLoadState('networkidle');
    
    // Check that we're in client mode
    await expect(page.locator('text=CLIENT')).toBeVisible();
    
    // Check that the Add Content button is NOT visible
    const addContentButton = page.locator('a:has-text("Add Content")');
    await expect(addContentButton).not.toBeVisible();
    
    console.log('✅ Add Content button is correctly hidden for client role');
  });

  test('Permission display should show correct permissions for each role', async ({ page }) => {
    // Test agency permissions
    await page.goto('http://localhost:8000/calendar/agency');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('text=✓ Edit')).toBeVisible();
    
    // Test admin permissions  
    await page.goto('http://localhost:8000/calendar/admin');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('text=✓ Edit')).toBeVisible();
    await expect(page.locator('text=✓ System')).toBeVisible();
    
    // Test client permissions
    await page.goto('http://localhost:8000/calendar/client');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('text=✓ Edit')).not.toBeVisible();
    await expect(page.locator('text=✓ View')).toBeVisible();
    
    console.log('✅ All role permissions are displayed correctly');
  });
});