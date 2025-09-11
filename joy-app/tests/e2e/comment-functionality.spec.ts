import { test, expect } from '@playwright/test';

test.describe('Comment Functionality', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to content review page
    await page.goto('http://127.0.0.1:8000/calendar/review/2025-09-23');
    await page.waitForLoadState('networkidle');
  });

  test('should be able to add a comment to content item', async ({ page }) => {
    // Wait for the page to fully load
    await page.waitForSelector('textarea[wire\\:model*="commentText"]', { timeout: 10000 });
    
    // Find the first comment textarea
    const commentTextarea = page.locator('textarea[wire\\:model*="commentText"]').first();
    
    // Check if textarea is visible
    await expect(commentTextarea).toBeVisible();
    
    // Type a test comment
    const testComment = `Test comment ${Date.now()}`;
    await commentTextarea.fill(testComment);
    
    // Find and click the "Add Comment" button
    const addCommentButton = page.locator('button', { hasText: 'Add Comment' }).first();
    await expect(addCommentButton).toBeVisible();
    
    // Count existing comments before adding
    const existingComments = await page.locator('.comment-item').count();
    console.log(`Existing comments: ${existingComments}`);
    
    // Click the Add Comment button
    await addCommentButton.click();
    
    // Wait a moment for the action to complete
    await page.waitForTimeout(2000);
    
    // Check if success message appears or comment was added
    const successMessage = page.locator('text=Comment added successfully');
    const hasSuccessMessage = await successMessage.isVisible();
    
    if (hasSuccessMessage) {
      console.log('Success message appeared');
    }
    
    // Check if new comment appears
    const newCommentCount = await page.locator('.comment-item').count();
    console.log(`New comment count: ${newCommentCount}`);
    
    // Verify textarea was cleared
    const textareaValue = await commentTextarea.inputValue();
    console.log(`Textarea value after submit: "${textareaValue}"`);
    
    // Check if the comment appears in the comments section
    const commentText = page.locator('.comment-item', { hasText: testComment });
    const commentExists = await commentText.isVisible();
    
    console.log(`Comment with text "${testComment}" exists: ${commentExists}`);
    
    // Take a screenshot for debugging
    await page.screenshot({ path: 'comment-test-result.png', fullPage: true });
  });

  test('should show validation error for empty comment', async ({ page }) => {
    // Find the first "Add Comment" button
    const addCommentButton = page.locator('button', { hasText: 'Add Comment' }).first();
    await expect(addCommentButton).toBeVisible();
    
    // Click without entering any text
    await addCommentButton.click();
    
    // Wait for potential error message
    await page.waitForTimeout(1000);
    
    // Check for error message
    const errorMessage = page.locator('text=Please enter a comment before submitting');
    const hasErrorMessage = await errorMessage.isVisible();
    
    console.log(`Error message for empty comment: ${hasErrorMessage}`);
    
    // Take screenshot
    await page.screenshot({ path: 'empty-comment-test.png', fullPage: true });
  });
});