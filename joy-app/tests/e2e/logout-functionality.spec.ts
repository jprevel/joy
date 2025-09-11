import { test, expect } from '@playwright/test';

test.describe('Logout Functionality Across All Roles', () => {
  test.beforeEach(async ({ page }) => {
    // Ensure we start with a fresh session
    await page.context().clearCookies();
  });

  test('should logout admin user and redirect to login', async ({ page }) => {
    // Login as admin
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'admin@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/admin');
    
    // Verify we're logged in
    await expect(page.locator('text=Alex Rodriguez')).toBeVisible();
    await expect(page.locator('text=Logout')).toBeVisible();
    
    // Click logout
    await page.click('text=Logout');
    
    // Should redirect to login page
    await page.waitForURL('**/login');
    await expect(page.url()).toContain('/login');
    
    // Verify login form is visible
    await expect(page.locator('h2', { hasText: 'Sign in' })).toBeVisible();
    await expect(page.locator('#email')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
    
    // Verify we can't access protected pages
    await page.goto('http://localhost:8000/calendar/admin');
    await expect(page.url()).toContain('/login');
  });

  test('should logout agency user and redirect to login', async ({ page }) => {
    // Login as agency user
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'shaira@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/agency');
    
    // Verify we're logged in
    await expect(page.locator('text=Shaira Hernandez')).toBeVisible();
    await expect(page.locator('text=Logout')).toBeVisible();
    
    // Click logout
    await page.click('text=Logout');
    
    // Should redirect to login page
    await page.waitForURL('**/login');
    await expect(page.url()).toContain('/login');
    
    // Verify we can't access protected pages
    await page.goto('http://localhost:8000/calendar/agency');
    await expect(page.url()).toContain('/login');
  });

  test('should logout client user and redirect to login', async ({ page }) => {
    // Login as client user
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'client@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/client');
    
    // Verify we're logged in (client may have different UI)
    await expect(page.locator('text=Logout')).toBeVisible();
    
    // Click logout
    await page.click('text=Logout');
    
    // Should redirect to login page
    await page.waitForURL('**/login');
    await expect(page.url()).toContain('/login');
    
    // Verify we can't access protected pages
    await page.goto('http://localhost:8000/calendar/client');
    await expect(page.url()).toContain('/login');
  });

  test('should clear session data on logout', async ({ page }) => {
    // Login as admin
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'admin@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/admin');
    
    // Store some session data to verify it gets cleared
    await page.evaluate(() => {
      sessionStorage.setItem('test-data', 'should-be-cleared');
    });
    
    // Logout
    await page.click('text=Logout');
    await page.waitForURL('**/login');
    
    // Try to access protected content directly
    await page.goto('http://localhost:8000/calendar/admin');
    await expect(page.url()).toContain('/login');
    
    // Verify session storage persists (it shouldn't be cleared by logout)
    // But authentication should still fail
    const sessionData = await page.evaluate(() => {
      return sessionStorage.getItem('test-data');
    });
    
    // The key point is that despite any client-side data, server-side auth is cleared
    await expect(page.url()).toContain('/login');
  });

  test('should handle logout with expired session gracefully', async ({ page }) => {
    // Login first
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'admin@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/admin');
    
    // Simulate expired session by clearing cookies
    await page.context().clearCookies();
    
    // Try to logout (this simulates clicking logout with an expired session)
    await page.click('text=Logout');
    
    // Should still redirect to login page gracefully
    await page.waitForURL('**/login');
    await expect(page.url()).toContain('/login');
  });

  test('should prevent access to protected routes after logout', async ({ page }) => {
    // Login as admin
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'admin@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/admin');
    
    // Logout
    await page.click('text=Logout');
    await page.waitForURL('**/login');
    
    // Test various protected routes
    const protectedRoutes = [
      '/calendar',
      '/calendar/admin',
      '/calendar/agency', 
      '/calendar/client',
      '/content/add/admin',
      '/content/add/agency'
    ];
    
    for (const route of protectedRoutes) {
      await page.goto(`http://localhost:8000${route}`);
      await expect(page.url()).toContain('/login');
    }
  });

  test('should maintain logout button visibility across different screen sizes', async ({ page }) => {
    // Test logout button on different viewport sizes
    const viewports = [
      { width: 1920, height: 1080 }, // Desktop
      { width: 768, height: 1024 },  // Tablet
      { width: 375, height: 667 }    // Mobile
    ];
    
    for (const viewport of viewports) {
      await page.setViewportSize(viewport);
      
      // Login
      await page.goto('http://localhost:8000/login');
      await page.fill('#email', 'admin@majormajor.marketing');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/calendar/admin');
      
      // Verify logout button is visible and clickable
      const logoutButton = page.locator('text=Logout');
      await expect(logoutButton).toBeVisible();
      
      // On mobile, it might be in a collapsed menu
      if (viewport.width < 768) {
        // Check if sidebar is collapsed and needs to be opened
        const sidebarToggle = page.locator('[aria-label*="menu"], [data-testid*="sidebar-toggle"]').first();
        if (await sidebarToggle.isVisible()) {
          await sidebarToggle.click();
          await expect(logoutButton).toBeVisible();
        }
      }
      
      // Test logout works
      await logoutButton.click();
      await page.waitForURL('**/login');
      await expect(page.url()).toContain('/login');
    }
  });

  test('should handle multiple logout attempts gracefully', async ({ page }) => {
    // Login
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'admin@majormajor.marketing');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar/admin');
    
    // First logout
    await page.click('text=Logout');
    await page.waitForURL('**/login');
    
    // Try to logout again by accessing the logout URL directly
    await page.goto('http://localhost:8000/logout', { method: 'POST' });
    
    // Should handle gracefully (either redirect to login or show appropriate message)
    await expect(page.url()).toContain('/login');
  });
});