# Joy Application E2E Test Suite

This directory contains a comprehensive end-to-end testing suite for the Joy content calendar management application.

## Overview

The test suite covers all major functionality including:
- User authentication and role-based access control
- Content creation and management workflows
- Magic link client access (when implemented)
- Logout functionality and session management
- Responsive design and navigation
- Security and error handling

## Test Files

### Core Test Suites

1. **`admin-login.spec.ts`** - Admin role authentication and verification
   - Admin login functionality
   - Admin-specific feature access
   - Role-based redirects
   - Error handling for invalid credentials

2. **`agency-login-and-content.spec.ts`** - Agency user workflow
   - Agency user authentication
   - Content creation through UI
   - Form validation
   - Team-based client filtering

3. **`magic-link-client-workflow.spec.ts`** - Client access via magic links
   - Magic link validation and security
   - Client permission system simulation
   - Content approval workflow concepts
   - Invalid token handling

4. **`logout-functionality.spec.ts`** - Logout across all user roles
   - Session termination for all user types
   - Session cleanup verification
   - Protected route access after logout
   - Responsive logout button visibility

5. **`comprehensive-test-suite.spec.ts`** - Full application workflow
   - Complete authentication flows
   - Content management workflows
   - Role-based access control
   - UI/UX testing across devices
   - Security and performance testing

### Support Files

- **`setup/global-setup.ts`** - Global test setup and database seeding
- **`utils/test-helpers.ts`** - Reusable test utilities and helper functions

## Prerequisites

1. **Laravel Application Running**: Ensure the Joy application is running on `http://localhost:8000`
2. **Database Seeded**: The tests expect seeded data including:
   - Admin user: `admin@majormajor.marketing`
   - Agency user: `shaira@majormajor.marketing` 
   - Clients distributed across Bukonuts and Kalamansi teams
   - Sample content items

3. **Playwright Installed**: 
   ```bash
   npm install @playwright/test
   npx playwright install
   ```

## Running Tests

### Run All Tests
```bash
npx playwright test
```

### Run Specific Test File
```bash
npx playwright test tests/e2e/admin-login.spec.ts
```

### Run Tests in Headed Mode (with Browser UI)
```bash
npx playwright test --headed
```

### Run Tests in Debug Mode
```bash
npx playwright test --debug
```

### Run Tests for Specific Browser
```bash
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit
```

### Generate Test Report
```bash
npx playwright show-report
```

## Test Data Requirements

The tests expect the following seeded data:

### Users
- **Admin**: `admin@majormajor.marketing` (password: `password`)
- **Agency**: `shaira@majormajor.marketing` (password: `password`)
- **Client**: `client@majormajor.marketing` (password: `password`) - optional

### Teams
- **Bukonuts** - with assigned clients
- **Kalamansi** - with assigned clients

### Clients
- **TechCorp Solutions** (Bukonuts team)
- **Green Valley Wellness** (Bukonuts team)
- **Creative Studio Arts** (Bukonuts team)
- **NextGen Fitness** (Bukonuts team)
- **Urban Kitchen Co** (Kalamansi team)
- **Bright Future Education** (Kalamansi team)
- **Pacific Real Estate Group** (Kalamansi team)
- **Coastal Coffee Roasters** (Kalamansi team)

### Content Items
- Multiple content items across different platforms (Facebook, Instagram, LinkedIn, Blog)
- Various statuses (Draft, In Review, Approved, Scheduled)
- Different campaigns and scheduling dates

## Test Configuration

The Playwright configuration (`playwright.config.ts`) includes:
- Global setup for database seeding
- Multiple browser testing (Chrome, Firefox, Safari)
- Mobile device testing
- Video recording on failures
- Screenshot capture
- Custom timeouts and retry logic

## Test Utilities

The `TestHelpers` class provides reusable methods for:
- User authentication (admin, agency, client)
- Content creation through UI
- Calendar interaction
- Form validation testing
- Session management
- Responsive design testing

## CI/CD Integration

Tests are configured for CI environments with:
- Reduced parallelism for stability
- Increased retry attempts
- Optimized worker configuration
- Comprehensive reporting

## Troubleshooting

### Common Issues

1. **Tests failing due to timing**: Increase timeouts in `playwright.config.ts`
2. **Database not seeded**: Run `php artisan db:seed` before tests
3. **Server not running**: Ensure Laravel server is running on port 8000
4. **Permission errors**: Check file permissions for test directories

### Debugging Tips

1. Use `--headed` flag to see browser actions
2. Use `--debug` flag to step through tests
3. Check browser console for JavaScript errors
4. Verify network requests in browser dev tools
5. Use `page.pause()` in tests for manual debugging

## Best Practices

1. **Test Isolation**: Each test clears sessions and starts fresh
2. **Data Independence**: Tests don't rely on specific seeded data order
3. **Error Handling**: Tests verify both success and failure scenarios
4. **Responsive Design**: Tests verify functionality across device sizes
5. **Security**: Tests verify proper authentication and authorization

## Future Enhancements

- Add tests for magic link email generation
- Implement client commenting and approval workflow tests
- Add Trello integration testing
- Include accessibility testing
- Add performance benchmarking
- Implement visual regression testing