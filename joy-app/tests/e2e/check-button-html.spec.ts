import { test, expect } from '@playwright/test';

test('check button HTML rendering', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000/calendar/review/2025-09-23');
  await page.waitForLoadState('networkidle');
  
  // Get the HTML of the Add Comment button
  const buttonHTML = await page.locator('button', { hasText: 'Add Comment' }).first().innerHTML();
  console.log('Button HTML:', buttonHTML);
  
  // Get the wire:click attribute value
  const wireClickValue = await page.locator('button', { hasText: 'Add Comment' }).first().getAttribute('wire:click');
  console.log('wire:click value:', wireClickValue);
  
  // Get all attributes of the button
  const buttonElement = page.locator('button', { hasText: 'Add Comment' }).first();
  const allAttributes = await buttonElement.evaluate((el) => {
    const attrs = {};
    for (let i = 0; i < el.attributes.length; i++) {
      const attr = el.attributes[i];
      attrs[attr.name] = attr.value;
    }
    return attrs;
  });
  console.log('All button attributes:', JSON.stringify(allAttributes, null, 2));
  
  // Check if button is actually clickable
  const isVisible = await buttonElement.isVisible();
  const isEnabled = await buttonElement.isEnabled();
  
  console.log('Button visible:', isVisible);
  console.log('Button enabled:', isEnabled);
});