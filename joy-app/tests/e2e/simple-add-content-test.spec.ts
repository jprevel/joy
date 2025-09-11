import { test, expect } from '@playwright/test';

test.describe('Simple Add Content Test', () => {
  test('should load agency calendar and check for add content functionality', async ({ page }) => {
    // Navigate to agency calendar view
    await page.goto('http://localhost:8000/calendar/agency');
    await page.waitForLoadState('networkidle');
    
    // Take a screenshot to see what's actually on the page
    await page.screenshot({ path: 'test-results/agency-calendar.png', fullPage: true });
    
    // Check if the page loaded properly
    await expect(page.locator('h1')).toContainText('Content Calendar');
    
    // Check if we're in agency mode
    await expect(page.locator('text=AGENCY')).toBeVisible();
    
    // Check permissions display
    const permissionsSection = page.locator('text=Permissions:');
    if (await permissionsSection.isVisible()) {
      console.log('Permissions section found');
      await expect(page.locator('text=✓ Edit')).toBeVisible();
    } else {
      console.log('Permissions section not found');
    }
    
    // Look for any button with "Add" in the text
    const addButtons = page.locator('button, a').filter({ hasText: /add/i });
    const count = await addButtons.count();
    console.log(`Found ${count} buttons with 'add' text`);
    
    for (let i = 0; i < count; i++) {
      const button = addButtons.nth(i);
      const text = await button.textContent();
      console.log(`Button ${i}: ${text}`);
    }
    
    // Try to find the Add Content button by different selectors
    const addContentButton1 = page.locator('a:has-text("Add Content")');
    const addContentButton2 = page.locator('text=Add Content');
    const addContentButton3 = page.locator('[href*="/content/add"]');
    
    console.log(`Button 1 visible: ${await addContentButton1.isVisible()}`);
    console.log(`Button 2 visible: ${await addContentButton2.isVisible()}`);
    console.log(`Button 3 visible: ${await addContentButton3.isVisible()}`);
  });

  test('should test content creation form if accessible', async ({ page }) => {
    // Try to navigate directly to the add content form
    await page.goto('http://localhost:8000/content/add/agency');
    await page.waitForLoadState('networkidle');
    
    // Take a screenshot
    await page.screenshot({ path: 'test-results/add-content-form.png', fullPage: true });
    
    // Check if the form loads
    const title = page.locator('h1');
    if (await title.isVisible()) {
      const titleText = await title.textContent();
      console.log(`Page title: ${titleText}`);
      
      if (titleText?.includes('Add New Content')) {
        console.log('Add content form loaded successfully');
        
        // Test form submission with valid data
        await page.selectOption('#client_id', { index: 1 });
        await page.selectOption('#platform', 'Instagram');
        await page.fill('#title', 'Test Post Created by Playwright');
        await page.fill('#copy', 'This is a test post created by Playwright automation');
        
        // Set future date
        const futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + 2);
        const dateString = futureDate.toISOString().slice(0, 16);
        await page.fill('#scheduled_at', dateString);
        
        // Submit the form
        await page.click('button[type="submit"]');
        
        // Wait for response
        await page.waitForTimeout(3000);
        
        // Check if we got redirected or see success message
        const currentUrl = page.url();
        console.log(`After submission, URL: ${currentUrl}`);
        
        if (currentUrl.includes('/calendar/agency')) {
          console.log('Successfully redirected to calendar - content creation worked!');
        } else {
          console.log('Still on form page - might have validation errors');
          await page.screenshot({ path: 'test-results/after-submission.png', fullPage: true });
        }
      }
    } else {
      console.log('Add content form did not load properly');
    }
  });
});