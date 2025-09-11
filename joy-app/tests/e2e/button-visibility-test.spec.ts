import { test, expect } from '@playwright/test';

test('simple button visibility test', async ({ page }) => {
  // Navigate to agency calendar view
  await page.goto('http://localhost:8000/calendar/agency');
  await page.waitForLoadState('networkidle');
  
  // Take a screenshot
  await page.screenshot({ path: 'test-results/button-test.png', fullPage: true });
  
  // Simply check if the Add Content button exists and is visible
  const addContentButton = page.locator('a:has-text("Add Content")');
  const isVisible = await addContentButton.isVisible();
  
  console.log(`Add Content button visible: ${isVisible}`);
  
  if (isVisible) {
    console.log('✅ SUCCESS: Add Content button is visible!');
    
    // Test that it works
    await addContentButton.click();
    await page.waitForTimeout(2000);
    
    const currentUrl = page.url();
    console.log(`After click, URL: ${currentUrl}`);
    
    if (currentUrl.includes('/content/add/agency')) {
      console.log('✅ SUCCESS: Button navigation works!');
    }
  } else {
    console.log('❌ FAILED: Add Content button is not visible');
    
    // Debug: Show what buttons we can find
    const allButtons = page.locator('button, a');
    const count = await allButtons.count();
    console.log(`Total buttons/links found: ${count}`);
    
    for (let i = 0; i < Math.min(count, 10); i++) {
      const button = allButtons.nth(i);
      const text = await button.textContent();
      console.log(`Button ${i}: "${text}"`);
    }
  }
  
  // Check if we have the permission system working
  const editPermission = page.locator('text=✓ Edit');
  const hasEditPermission = await editPermission.isVisible();
  console.log(`Has edit permission: ${hasEditPermission}`);
});