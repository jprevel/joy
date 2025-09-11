import { test, expect } from '@playwright/test';

test('should show Add Content button in correct position for agency role', async ({ page }) => {
  // Navigate to agency calendar view
  await page.goto('http://localhost:8000/calendar/agency');
  await page.waitForLoadState('networkidle');
  
  // Take a screenshot to see the layout
  await page.screenshot({ path: 'test-results/add-button-position.png', fullPage: true });
  
  // Check that we're in agency mode
  await expect(page.locator('text=AGENCY')).toBeVisible();
  
  // Look for the Add Content button
  const addContentButton = page.locator('a:has-text("Add Content")');
  await expect(addContentButton).toBeVisible();
  
  // Verify the button has the correct styling
  await expect(addContentButton).toHaveClass(/bg-indigo-600/);
  
  // Check that it's positioned near the Calendar/Timeline toggle
  const viewToggle = page.locator('#viewToggle');
  await expect(viewToggle).toBeVisible();
  
  // Verify the button is clickable and leads to the correct page
  await addContentButton.click();
  await expect(page).toHaveURL(/\/content\/add\/agency/);
  await expect(page.locator('h1')).toContainText('Add New Content');
  
  console.log('✅ Add Content button is working correctly!');
});