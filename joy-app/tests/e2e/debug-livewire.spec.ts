import { test, expect } from '@playwright/test';

test('debug livewire initialization', async ({ page }) => {
  // Listen for console messages
  page.on('console', (msg) => {
    console.log(`Console ${msg.type()}: ${msg.text()}`);
  });

  // Listen for page errors
  page.on('pageerror', (error) => {
    console.log(`Page error: ${error.message}`);
  });

  // Navigate to the page
  await page.goto('http://127.0.0.1:8000/calendar/review/2025-09-23');
  
  // Wait for the page to load
  await page.waitForLoadState('networkidle');
  
  // Check if Livewire is loaded
  const livewireLoaded = await page.evaluate(() => {
    return typeof window.Livewire !== 'undefined';
  });
  
  console.log(`Livewire loaded: ${livewireLoaded}`);
  
  // Check if there are any wire: attributes
  const wireElements = await page.locator('[wire\\:click]').count();
  console.log(`Elements with wire:click: ${wireElements}`);
  
  // Check if there are any wire:model attributes
  const wireModels = await page.locator('[wire\\:model]').count();
  console.log(`Elements with wire:model: ${wireModels}`);
  
  // Take a screenshot
  await page.screenshot({ path: 'livewire-debug.png', fullPage: true });
  
  // Wait a bit to capture any delayed errors
  await page.waitForTimeout(3000);
});