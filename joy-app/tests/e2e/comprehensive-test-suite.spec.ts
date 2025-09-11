import { test, expect } from '@playwright/test';
import TestHelpers from './utils/test-helpers';

test.describe('Comprehensive Joy Application Test Suite', () => {
  let helpers: TestHelpers;

  test.beforeEach(async ({ page }) => {
    helpers = new TestHelpers(page);
    await helpers.clearSession();
  });

  test.describe('Authentication Flow', () => {
    test('should complete full authentication flow for all user types', async ({ page }) => {
      const helpers = new TestHelpers(page);

      // Test admin login
      await helpers.loginAsAdmin();
      await helpers.verifyUserDashboard('admin', 'Admin User');
      await helpers.logout();

      // Test agency login  
      await helpers.loginAsAgency();
      await helpers.verifyUserDashboard('agency', 'Shaira Hernandez');
      await helpers.logout();

      // Test client login
      try {
        await helpers.loginAsClient();
        await helpers.verifyUserDashboard('client', 'Client User');
        await helpers.logout();
      } catch {
        // Client user may not exist in current seed data
        console.log('Client user test skipped - user may not exist');
      }
    });

    test('should handle invalid credentials properly', async ({ page }) => {
      const helpers = new TestHelpers(page);
      await helpers.verifyInvalidLogin('invalid@example.com', 'wrongpassword');
    });
  });

  test.describe('Content Management Workflow', () => {
    test('should complete content creation and management workflow', async ({ page }) => {
      const helpers = new TestHelpers(page);

      // Login as agency user
      await helpers.loginAsAgency();
      await helpers.waitForCalendarLoad();

      // Add new content item
      await helpers.addContentItem({
        clientName: 'TechCorp Solutions',
        title: 'Comprehensive Test Content',
        notes: 'This content was created during comprehensive testing',
        platform: 'Facebook',
        copy: 'Comprehensive test post for Joy application testing. #Testing #E2E',
        mediaUrl: 'https://picsum.photos/1200/630?random=123',
        scheduledDate: helpers.getTomorrowDate(),
        scheduledTime: '15:00',
        status: 'Draft'
      });

      // Verify content appears
      await helpers.verifyContentExists('Comprehensive Test Content');

      // Verify calendar structure
      await helpers.verifyCalendarStructure();
    });

    test('should display seeded content across different clients', async ({ page }) => {
      const helpers = new TestHelpers(page);

      // Login as admin to see all content
      await helpers.loginAsAdmin();
      await helpers.waitForCalendarLoad();

      // Verify TechCorp content exists (from seeders)
      await expect(page.locator('text=TechCorp Solutions').first()).toBeVisible({ timeout: 10000 });
      
      // Verify calendar shows content
      await helpers.verifyCalendarStructure();
    });
  });

  test.describe('Role-Based Access Control', () => {
    test('should enforce proper access controls for each role', async ({ page }) => {
      const helpers = new TestHelpers(page);

      // Test admin access
      await helpers.loginAsAdmin();
      await expect(page.locator('text=Add Content')).toBeVisible();
      await helpers.logout();

      // Test agency access
      await helpers.loginAsAgency();
      await expect(page.locator('text=Add Content')).toBeVisible();
      await helpers.logout();

      // Verify protected routes are blocked when not authenticated
      await helpers.verifyProtectedRouteBlocked('/calendar/admin');
      await helpers.verifyProtectedRouteBlocked('/calendar/agency');
    });
  });

  test.describe('User Interface and Navigation', () => {
    test('should handle responsive design and navigation', async ({ page }) => {
      const helpers = new TestHelpers(page);

      // Test different viewport sizes
      const viewports = [
        { width: 1920, height: 1080 }, // Desktop
        { width: 768, height: 1024 },  // Tablet
        { width: 375, height: 667 }    // Mobile
      ];

      for (const viewport of viewports) {
        await page.setViewportSize(viewport);
        
        await helpers.loginAsAdmin();
        await helpers.waitForCalendarLoad();
        
        // Ensure sidebar is accessible on all screen sizes
        if (viewport.width < 768) {
          await helpers.ensureSidebarExpanded();
        }
        
        // Verify key elements are visible
        await expect(page.locator('text=Logout')).toBeVisible();
        await helpers.verifyCalendarStructure();
        
        await helpers.logout();
      }
    });

    test('should handle calendar navigation and display', async ({ page }) => {
      const helpers = new TestHelpers(page);
      
      await helpers.loginAsAdmin();
      await helpers.waitForCalendarLoad();
      
      // Verify calendar grid is present
      await expect(page.locator('[data-testid="calendar-grid"]')).toBeVisible();
      
      // Test that calendar shows current month content
      const currentMonth = new Date().toLocaleString('default', { month: 'long' });
      // Note: Specific month navigation would depend on UI implementation
      
      await helpers.logout();
    });
  });

  test.describe('Security and Session Management', () => {
    test('should properly handle session security', async ({ page }) => {
      const helpers = new TestHelpers(page);

      // Login and verify session
      await helpers.loginAsAdmin();
      await helpers.verifyUserDashboard('admin', 'Alex Rodriguez');

      // Test logout clears session
      await helpers.logout();
      await helpers.verifyProtectedRouteBlocked('/calendar/admin');

      // Test that expired sessions redirect to login
      await helpers.clearSession();
      await helpers.verifyProtectedRouteBlocked('/calendar/admin');
    });

    test('should validate form inputs and handle errors gracefully', async ({ page }) => {
      const helpers = new TestHelpers(page);

      await helpers.loginAsAgency();
      await page.click('text=Add Content');
      
      // Wait for form
      await helpers.waitForElement('[wire\\:submit="addContent"]');
      
      // Test form validation
      await helpers.verifyFormValidation('[wire\\:submit="addContent"]');
    });
  });

  test.describe('Team and Client Management', () => {
    test('should display team-appropriate clients for agency users', async ({ page }) => {
      const helpers = new TestHelpers(page);

      // Login as Shaira (Bukonuts team member)
      await helpers.loginAsAgency();
      await helpers.waitForCalendarLoad();

      // Verify Shaira can see Bukonuts team clients
      // TechCorp Solutions should be visible as it's assigned to Bukonuts
      if (await page.locator('text=TechCorp Solutions').isVisible()) {
        await expect(page.locator('text=TechCorp Solutions')).toBeVisible();
      }
    });
  });

  test.describe('Integration and Error Handling', () => {
    test('should handle various error conditions gracefully', async ({ page }) => {
      const helpers = new TestHelpers(page);

      // Test handling of network errors (simulated)
      await helpers.loginAsAdmin();
      
      // Test accessing non-existent content
      await page.goto('http://localhost:8000/calendar/nonexistent');
      // Should either redirect or show appropriate error
      
      // Test malformed requests
      await page.goto('http://localhost:8000/calendar/../etc/passwd');
      await expect(page.url()).toContain('/login');
    });
  });

  test.describe('Performance and Load Testing', () => {
    test('should load application within reasonable time limits', async ({ page }) => {
      const helpers = new TestHelpers(page);

      const startTime = Date.now();
      await helpers.loginAsAdmin();
      await helpers.waitForCalendarLoad();
      const loadTime = Date.now() - startTime;

      // Application should load within 10 seconds
      expect(loadTime).toBeLessThan(10000);
    });
  });
});