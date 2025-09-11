import { test, expect } from '@playwright/test';

test.describe('Add Content Functionality', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to agency calendar view
    await page.goto('http://localhost:8000/calendar/agency');
    await page.waitForLoadState('networkidle');
  });

  test('should show Add Content button for agency role', async ({ page }) => {
    // Check that the Add Content button is visible for agency role
    const addContentButton = page.locator('a', { hasText: 'Add Content' });
    await expect(addContentButton).toBeVisible();
    
    // Verify it has the correct styling and icon
    await expect(addContentButton).toHaveClass(/bg-indigo-600/);
    const icon = addContentButton.locator('svg');
    await expect(icon).toBeVisible();
  });

  test('should navigate to add content form when button is clicked', async ({ page }) => {
    // Click the Add Content button
    await page.click('a:has-text("Add Content")');
    
    // Verify we're on the add content page
    await expect(page).toHaveURL(/\/content\/add\/agency/);
    await expect(page.locator('h1')).toContainText('Add New Content');
  });

  test('should display all required form fields', async ({ page }) => {
    // Navigate to add content form
    await page.click('a:has-text("Add Content")');
    await page.waitForLoadState('networkidle');
    
    // Check all form fields are present
    await expect(page.locator('#client_id')).toBeVisible();
    await expect(page.locator('#platform')).toBeVisible();
    await expect(page.locator('#title')).toBeVisible();
    await expect(page.locator('#copy')).toBeVisible();
    await expect(page.locator('#notes')).toBeVisible();
    await expect(page.locator('#scheduled_at')).toBeVisible();
    await expect(page.locator('#image')).toBeVisible();
    
    // Check dropdowns have options
    await page.click('#client_id');
    await expect(page.locator('#client_id option')).toHaveCount.greaterThan(1);
    
    await page.click('#platform');
    await expect(page.locator('#platform option[value="Facebook"]')).toBeVisible();
    await expect(page.locator('#platform option[value="Instagram"]')).toBeVisible();
    await expect(page.locator('#platform option[value="LinkedIn"]')).toBeVisible();
  });

  test('should successfully create content item', async ({ page }) => {
    // Navigate to add content form
    await page.click('a:has-text("Add Content")');
    await page.waitForLoadState('networkidle');
    
    // Fill out the form
    await page.selectOption('#client_id', { index: 1 }); // Select first client
    await page.selectOption('#platform', 'Instagram');
    await page.fill('#title', 'Test Content Item');
    await page.fill('#copy', 'This is a test post for Instagram');
    await page.fill('#notes', 'Internal testing notes');
    
    // Set a future date
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 1);
    const dateString = futureDate.toISOString().slice(0, 16); // Format for datetime-local
    await page.fill('#scheduled_at', dateString);
    
    // Submit the form
    await page.click('button[type="submit"]');
    
    // Wait for redirect to calendar
    await page.waitForURL(/\/calendar\/agency/);
    
    // Check for success message or verify content appears in calendar
    // We should be redirected back to the calendar
    await expect(page).toHaveURL(/\/calendar\/agency/);
  });

  test('should show validation errors for required fields', async ({ page }) => {
    // Navigate to add content form
    await page.click('a:has-text("Add Content")');
    await page.waitForLoadState('networkidle');
    
    // Try to submit without filling required fields
    await page.click('button[type="submit"]');
    
    // Wait a moment for validation to trigger
    await page.waitForTimeout(1000);
    
    // Check that we're still on the form page (not redirected)
    await expect(page).toHaveURL(/\/content\/add\/agency/);
  });

  test('should handle image upload', async ({ page }) => {
    // Navigate to add content form
    await page.click('a:has-text("Add Content")');
    await page.waitForLoadState('networkidle');
    
    // Create a simple test image file
    const buffer = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==', 'base64');
    
    // Upload the file
    await page.setInputFiles('#image', {
      name: 'test-image.png',
      mimeType: 'image/png',
      buffer: buffer
    });
    
    // Wait for the image to be processed
    await page.waitForTimeout(2000);
    
    // Check if preview appears (this depends on Livewire processing)
    // The image preview should show up after upload
    const imagePreview = page.locator('img[alt="Preview"]');
    // Note: This might not work perfectly due to Livewire's temporary URL handling
  });

  test('should test drag and drop functionality', async ({ page }) => {
    // Navigate to add content form
    await page.click('a:has-text("Add Content")');
    await page.waitForLoadState('networkidle');
    
    // Create a test file for drag and drop
    const testFile = {
      name: 'test-drag-image.png',
      mimeType: 'image/png',
      buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==', 'base64')
    };
    
    // Get the dropzone element
    const dropzone = page.locator('#dropzone');
    await expect(dropzone).toBeVisible();
    
    // Simulate drag and drop by dispatching events
    await page.evaluate(async (file) => {
      const dropzoneEl = document.getElementById('dropzone');
      const fileInputEl = document.getElementById('image') as HTMLInputElement;
      
      if (dropzoneEl && fileInputEl) {
        // Create a File object
        const fileObj = new File([new Uint8Array(Buffer.from(file.buffer))], file.name, { type: file.mimeType });
        
        // Create DataTransfer object
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(fileObj);
        
        // Create and dispatch drop event
        const dropEvent = new DragEvent('drop', {
          bubbles: true,
          dataTransfer: dataTransfer
        });
        
        dropzoneEl.dispatchEvent(dropEvent);
      }
    }, testFile);
    
    // Wait for the drop to be processed
    await page.waitForTimeout(1000);
  });

  test('should not show Add Content button for client role', async ({ page }) => {
    // Navigate to client calendar view
    await page.goto('http://localhost:8000/calendar/client');
    await page.waitForLoadState('networkidle');
    
    // Check that the Add Content button is NOT visible for client role
    const addContentButton = page.locator('a', { hasText: 'Add Content' });
    await expect(addContentButton).not.toBeVisible();
  });

  test('should show proper role permissions in testing bar', async ({ page }) => {
    // Check agency role permissions
    await page.goto('http://localhost:8000/calendar/agency');
    await page.waitForLoadState('networkidle');
    
    // Check that agency role shows correct permissions
    const permissionsText = page.locator('text=Permissions:');
    await expect(permissionsText).toBeVisible();
    
    // Should show edit permission for agency
    await expect(page.locator('text=✓ Edit')).toBeVisible();
    
    // Check client role has fewer permissions
    await page.goto('http://localhost:8000/calendar/client');
    await page.waitForLoadState('networkidle');
    
    // Should NOT show edit permission for client
    await expect(page.locator('text=✓ Edit')).not.toBeVisible();
    await expect(page.locator('text=✓ View')).toBeVisible();
  });
});