import { test, expect } from '@playwright/test';

test.describe('Joy Calendar Tests', () => {
  test('calendar page loads and displays content', async ({ page }) => {
    await page.goto('/calendar');
    
    // Check for main title (should show first client's name)
    await expect(page.locator('h1')).toContainText('Content Calendar');
    
    // Check for view toggle buttons
    await expect(page.locator('button', { hasText: 'Calendar' })).toBeVisible();
    await expect(page.locator('button', { hasText: 'Timeline' })).toBeVisible();
    
    // Check calendar navigation
    await expect(page.locator('button', { hasText: 'Previous' })).toBeVisible();
    await expect(page.locator('button', { hasText: 'Next' })).toBeVisible();
    await expect(page.locator('button', { hasText: 'Today' })).toBeVisible();
    
    // Check calendar grid headers
    await expect(page.locator('text=Sun')).toBeVisible();
    await expect(page.locator('text=Mon')).toBeVisible();
    await expect(page.locator('text=Tue')).toBeVisible();
    
    // Wait a moment for content to load
    await page.waitForTimeout(2000);
    
    // Switch to timeline view
    await page.click('button:has-text("Timeline")');
    
    // Check timeline view elements
    await expect(page.locator('h2', { hasText: 'Content Timeline' })).toBeVisible();
    
    // Take a screenshot for verification
    await page.screenshot({ path: 'test-results/calendar-screenshot.png', fullPage: true });
    
    console.log('Calendar page loaded successfully with content!');
  });

  test('calendar view switching works', async ({ page }) => {
    await page.goto('/calendar');
    
    // Start in calendar view
    await expect(page.locator('button:has-text("Calendar")').nth(0)).toHaveClass(/bg-white/);
    
    // Switch to timeline
    await page.click('button:has-text("Timeline")');
    await expect(page.locator('button:has-text("Timeline")').nth(0)).toHaveClass(/bg-white/);
    
    // Switch back to calendar
    await page.click('button:has-text("Calendar")');
    await expect(page.locator('button:has-text("Calendar")').nth(0)).toHaveClass(/bg-white/);
  });
});