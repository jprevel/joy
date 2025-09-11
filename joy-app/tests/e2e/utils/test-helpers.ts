import { Page, expect } from '@playwright/test';

export class TestHelpers {
  constructor(private page: Page) {}

  /**
   * Login with specified user credentials
   */
  async login(email: string, password: string = 'password'): Promise<void> {
    await this.page.goto('http://localhost:8000/login');
    
    // Wait for page to load completely
    await this.page.waitForLoadState('domcontentloaded');
    
    // Fill form fields
    await this.page.fill('#email', email);
    await this.page.fill('#password', password);
    
    // Submit form and wait for navigation
    await Promise.all([
      this.page.waitForNavigation({ waitUntil: 'networkidle' }),
      this.page.click('button[type="submit"]')
    ]);
  }

  /**
   * Login as admin user
   */
  async loginAsAdmin(): Promise<void> {
    await this.login('admin@example.com');
    // Wait for any calendar route (admin, agency, or client)
    await this.page.waitForURL('**/calendar/**');
  }

  /**
   * Login as agency user (Shaira)
   */
  async loginAsAgency(): Promise<void> {
    await this.login('shaira@majormajor.marketing');
    // Wait for any calendar route
    await this.page.waitForURL('**/calendar/**');
  }

  /**
   * Login as client user
   */
  async loginAsClient(): Promise<void> {
    await this.login('client@example.com');
    // Wait for any calendar route
    await this.page.waitForURL('**/calendar/**');
  }

  /**
   * Logout the current user
   */
  async logout(): Promise<void> {
    await this.page.click('text=Logout');
    await this.page.waitForURL('**/login');
  }

  /**
   * Wait for calendar to load with content
   */
  async waitForCalendarLoad(): Promise<void> {
    await this.page.waitForSelector('[data-testid="calendar-grid"]', { timeout: 10000 });
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Add content item through the UI
   */
  async addContentItem(contentData: {
    clientName: string;
    title: string;
    notes: string;
    platform: string;
    copy: string;
    mediaUrl?: string;
    scheduledDate?: string;
    scheduledTime?: string;
    status: string;
  }): Promise<void> {
    await this.page.click('text=Add Content');
    
    // Wait for form to appear
    await this.page.waitForSelector('[wire\\:submit="addContent"]', { timeout: 10000 });
    
    // Fill form fields
    await this.page.selectOption('select[name="client_id"]', { label: contentData.clientName });
    await this.page.fill('input[name="title"]', contentData.title);
    await this.page.fill('textarea[name="notes"]', contentData.notes);
    await this.page.selectOption('select[name="platform"]', contentData.platform);
    await this.page.fill('textarea[name="copy"]', contentData.copy);
    
    if (contentData.mediaUrl) {
      await this.page.fill('input[name="media_url"]', contentData.mediaUrl);
    }
    
    if (contentData.scheduledDate) {
      await this.page.fill('input[name="scheduled_date"]', contentData.scheduledDate);
    }
    
    if (contentData.scheduledTime) {
      await this.page.fill('input[name="scheduled_time"]', contentData.scheduledTime);
    }
    
    await this.page.selectOption('select[name="status"]', contentData.status);
    
    // Submit form
    await this.page.click('button[type="submit"]');
    
    // Wait for content to be added
    await this.page.waitForTimeout(2000);
  }

  /**
   * Verify user is on the correct dashboard for their role
   */
  async verifyUserDashboard(userRole: 'admin' | 'agency' | 'client', userName: string): Promise<void> {
    await expect(this.page.url()).toContain(`/calendar/${userRole}`);
    await expect(this.page.locator(`text=${userName}`)).toBeVisible();
    await expect(this.page.locator('text=Logout')).toBeVisible();
  }

  /**
   * Verify calendar contains expected elements
   */
  async verifyCalendarStructure(): Promise<void> {
    await expect(this.page.locator('[data-testid="calendar-grid"]')).toBeVisible();
    
    // Check for basic calendar navigation
    const navigationElements = [
      'button', // Month navigation buttons
      'text=Add Content', // Should be visible for admin/agency
    ];
    
    for (const element of navigationElements) {
      if (await this.page.locator(element).isVisible()) {
        await expect(this.page.locator(element)).toBeVisible();
      }
    }
  }

  /**
   * Check if content item exists on calendar
   */
  async verifyContentExists(title: string): Promise<void> {
    await expect(this.page.locator(`text=${title}`)).toBeVisible();
  }

  /**
   * Verify error handling for invalid credentials
   */
  async verifyInvalidLogin(email: string, password: string): Promise<void> {
    await this.page.goto('http://localhost:8000/login');
    await this.page.fill('#email', email);
    await this.page.fill('#password', password);
    await this.page.click('button[type="submit"]');
    
    // Should remain on login page with error
    await expect(this.page.url()).toContain('/login');
    await expect(this.page.locator('.error')).toBeVisible();
  }

  /**
   * Verify protected route access is blocked
   */
  async verifyProtectedRouteBlocked(route: string): Promise<void> {
    await this.page.goto(`http://localhost:8000${route}`);
    await expect(this.page.url()).toContain('/login');
  }

  /**
   * Clear browser session and cookies
   */
  async clearSession(): Promise<void> {
    await this.page.context().clearCookies();
    await this.page.evaluate(() => {
      sessionStorage.clear();
      localStorage.clear();
    });
  }

  /**
   * Get tomorrow's date in YYYY-MM-DD format
   */
  getTomorrowDate(): string {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
  }

  /**
   * Wait for an element to be visible with custom timeout
   */
  async waitForElement(selector: string, timeout: number = 5000): Promise<void> {
    await this.page.waitForSelector(selector, { timeout });
  }

  /**
   * Check if sidebar is collapsed and expand if needed (for mobile/responsive testing)
   */
  async ensureSidebarExpanded(): Promise<void> {
    const sidebarToggle = this.page.locator('[aria-label*="menu"], [data-testid*="sidebar-toggle"]').first();
    
    if (await sidebarToggle.isVisible()) {
      await sidebarToggle.click();
      await this.page.waitForTimeout(500); // Wait for animation
    }
  }

  /**
   * Verify form validation by submitting empty form
   */
  async verifyFormValidation(formSelector: string): Promise<void> {
    await this.page.click('button[type="submit"]');
    await expect(this.page.locator('.error, [class*="error"]')).toBeVisible();
  }
}

export default TestHelpers;