# Implementation Plan: Session Reliability & Timeout Handling

**Feature ID**: 006-session-reliability
**Priority**: P0 (Production Blocker)
**Estimated Effort**: 8-10 hours
**Status**: Ready for Implementation

## Executive Summary

This implementation plan outlines the step-by-step approach to eliminate "419 Page Expired" errors in the Joy application by implementing a defense-in-depth session management strategy with 5 layers of protection.

## Implementation Strategy Overview

The implementation is divided into 3 phases that can be deployed incrementally:

- **Phase 1 (P0)**: Critical fixes - stop 419 errors immediately (2 hours)
- **Phase 2 (P1)**: Client-side protection - warn users before expiration (3 hours)
- **Phase 3 (P2)**: Advanced features - keep active users logged in (3 hours)

Each phase is independently deployable and adds value without requiring subsequent phases.

---

## Phase 1: Critical Fixes (P0)

**Goal**: Stop "Page Expired" errors immediately
**Estimated Time**: 2 hours
**Deliverable**: Users can always log out successfully

### Task 1.1: Improve CSRF Exception Handler (30 minutes)

**File**: `/Users/jprevel/Documents/joy/joy-app/bootstrap/app.php`

**Current Code** (lines 28-34):
```php
// Handle CSRF token expiration (419 Page Expired error)
if ($exception instanceof \Illuminate\Session\TokenMismatchException && $request->expectsHtml()) {
    // Clear old session and redirect to login with friendly message
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login')->with('status', 'Your session has expired. Please log in again.');
}
```

**Updated Code**:
```php
// Handle CSRF token expiration (419 Page Expired error)
if ($exception instanceof \Illuminate\Session\TokenMismatchException && $request->expectsHtml()) {
    // Safely clear old session with try-catch for already-invalidated sessions
    try {
        if (session()->isStarted()) {
            session()->invalidate();
            session()->regenerateToken();
        }
    } catch (\Exception $e) {
        // Session already invalidated or corrupted - ignore and proceed
        \Log::info('Session invalidation failed during CSRF exception', [
            'exception' => $e->getMessage(),
            'url' => $request->url(),
        ]);
    }

    return redirect()->route('login')->with('status', 'Your session has expired. Please log in again.');
}
```

**Changes**:
1. Add `session()->isStarted()` check
2. Wrap session operations in try-catch
3. Add logging for debugging
4. Always redirect to login regardless of session state

### Task 1.2: Add GET Logout Route (30 minutes)

**File**: `/Users/jprevel/Documents/joy/joy-app/routes/web.php`

**Add After Line 15**:
```php
// Alternative GET logout route (no CSRF required - useful when session expired)
Route::get('/logout', [LoginController::class, 'logout'])
    ->name('logout.get')
    ->middleware('auth');
```

**Note**: The GET route will work because Laravel's CSRF middleware only applies to POST, PUT, PATCH, DELETE methods.

### Task 1.3: Test & Validate (45 minutes)

**Manual Test Checklist**:

1. Test POST Logout (existing behavior)
2. Test GET Logout (navigate to /logout in browser)
3. Test Expired CSRF Token (use Tinker to corrupt session)
4. Test Already-Invalidated Session

**Automated Test** (add to `tests/Feature/AuthenticationTest.php`):
```php
/** @test */
public function get_logout_works_without_csrf_token()
{
    $user = User::factory()->create();
    $this->actingAs($user);
    $response = $this->get('/logout');
    $response->assertRedirect(route('login'));
    $this->assertGuest();
}

/** @test */
public function expired_csrf_redirects_gracefully()
{
    $user = User::factory()->create();
    $this->actingAs($user);
    session()->forget('_token');
    $response = $this->post(route('logout'));
    $response->assertRedirect(route('login'));
}
```

**Run Test Suite**:
```bash
./scripts/test-lock.sh
```

---

## Phase 2: Client-Side Protection (P1)

**Goal**: Warn users before session expires
**Estimated Time**: 3 hours
**Deliverable**: Users see warning before expiration

### Task 2.1: Create Session Monitor JavaScript (90 minutes)

**File**: `/Users/jprevel/Documents/joy/joy-app/resources/js/session-monitor.js` (NEW)

Create a JavaScript module that:
- Reads session metadata from meta tags
- Calculates expiration time
- Shows warning modal 5 minutes before expiration
- Intercepts form submissions if session expired
- Implements heartbeat functionality

**Key features**:
- Respects Page Visibility API (only active when tab visible)
- Handles server time offset (clock drift)
- Provides "Stay Logged In" and "Logout" options

### Task 2.2: Update JavaScript Entry Point (15 minutes)

**File**: `/Users/jprevel/Documents/joy/joy-app/resources/js/app.js`

```javascript
import './bootstrap';
import './session-monitor';
```

**Rebuild assets**:
```bash
npm run build
```

### Task 2.3: Add Session Meta Tags (30 minutes)

Create component: `/Users/jprevel/Documents/joy/joy-app/resources/views/components/session-meta.blade.php`

```blade
<meta name="session-start" content="{{ time() }}">
<meta name="session-lifetime" content="{{ config('session.lifetime') * 60 }}">
<meta name="session-warning" content="{{ config('session.warning_minutes', 5) * 60 }}">
<meta name="heartbeat-enabled" content="{{ config('session.heartbeat_enabled', true) ? '1' : '0' }}">
<meta name="heartbeat-interval" content="{{ config('session.heartbeat_interval', 600) }}">
```

Add `<x-session-meta />` to all authenticated views.

### Task 2.4: Create Logout Button Component (30 minutes)

**File**: `/Users/jprevel/Documents/joy/joy-app/resources/views/components/logout-button.blade.php` (NEW)

Create reusable logout button component that handles both sidebar and inline usage.

### Task 2.5: Replace All Logout Buttons (30 minutes)

Update 7+ view files to use `<x-logout-button />` component.

### Task 2.6: Test Phase 2 (15 minutes)

- Test session warning modal
- Test logout button component
- Verify JavaScript loads without errors

---

## Phase 3: Advanced Features (P2)

**Goal**: Keep active users' sessions alive
**Estimated Time**: 3 hours
**Deliverable**: Active users never time out

### Task 3.1: Create API Routes File (15 minutes)

**File**: `/Users/jprevel/Documents/joy/joy-app/routes/api.php` (NEW)

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/heartbeat', function () {
        return response()->noContent();
    })->name('api.heartbeat');
});
```

Register in `bootstrap/app.php`:
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

### Task 3.2: Implement Heartbeat (Already Done in Phase 2)

Heartbeat functionality already included in session-monitor.js.

### Task 3.3: Create ValidateSession Middleware (60 minutes)

**File**: `/Users/jprevel/Documents/joy/joy-app/app/Http/Middleware/ValidateSession.php` (NEW)

Create middleware that:
- Checks session validity before request processing
- Validates CSRF token exists
- Checks last_activity timestamp
- Redirects to login if expired

### Task 3.4: Register Middleware (15 minutes)

Add to `bootstrap/app.php`:
```php
'validate.session' => \App\Http\Middleware\ValidateSession::class,
```

Apply to authenticated routes in `routes/web.php`.

### Task 3.5: Add Environment Variables (10 minutes)

Update `.env.example`:
```env
SESSION_WARNING_MINUTES=5
SESSION_HEARTBEAT_ENABLED=true
SESSION_HEARTBEAT_INTERVAL=600
```

### Task 3.6: Test Phase 3 (45 minutes)

- Test heartbeat endpoint
- Test middleware session validation
- Test Page Visibility API
- Run automated tests

---

## Post-Implementation Tasks

### Update Configuration Files

Add session monitoring config to `config/session.php`:
```php
'warning_minutes' => env('SESSION_WARNING_MINUTES', 5),
'heartbeat_enabled' => env('SESSION_HEARTBEAT_ENABLED', true),
'heartbeat_interval' => env('SESSION_HEARTBEAT_INTERVAL', 600),
```

### Update CLAUDE.md

Add session handling guidelines for developers.

### Run Full Test Suite

```bash
./scripts/test-lock.sh
```

Verify:
- All tests pass
- Test count still 42 files
- 23 incomplete tests allowed

---

## Deployment Strategy

### Staging Deployment (Day 1-2)

```bash
git pull origin 006-session-reliability
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:clear
php artisan cache:clear
php artisan optimize
```

**Smoke Tests**:
- Log in as different user roles
- Click logout (verify no 419 errors)
- Test GET /logout
- Wait for session warning
- Check browser console

### Production Deployment (Day 3)

```bash
php artisan down
git pull origin 006-session-reliability
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:clear
php artisan cache:clear
php artisan optimize
php artisan up
sudo systemctl restart php-fpm
```

**Post-Deployment Monitoring**:
- Monitor logs for 48 hours
- Check 419 error rate (should be zero)
- Monitor heartbeat endpoint performance
- Gather user feedback

### Rollback Plan

```bash
php artisan down
git checkout main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:clear && php artisan cache:clear
php artisan up
```

**Rollback is safe**: No database migrations, no data modifications.

---

## Success Criteria

### Before Implementation
- L Users encounter 419 errors
- L No session expiration warnings
- L Active users get logged out
- L Production feels unreliable

### After Implementation
-  Zero "Page Expired" errors
-  Users see expiration warnings
-  Active users stay logged in
-  Graceful JavaScript degradation
-  Production-ready session management

### Metrics

| Metric | Before | Target |
|--------|--------|--------|
| 419 errors/day | 10-20 | 0 |
| Timeout complaints | 5-10/week | 0 |
| Logout success rate | 95% | 100% |
| Session duration | < 14 days | Unlimited |

---

## Risk Mitigation

### Risk 1: GET Logout Security
**Mitigation**: Simple redirect, requires session cookie, no data exposure

### Risk 2: Heartbeat Performance
**Mitigation**: Lightweight endpoint, Page Visibility API, configurable

### Risk 3: JavaScript Disabled
**Mitigation**: Graceful degradation, POST logout still works

### Risk 4: Browser Clock Drift
**Mitigation**: Server time offset calculation, server-authoritative validation

---

## File Inventory

**New Files**:
1. `app/Http/Middleware/ValidateSession.php`
2. `resources/js/session-monitor.js`
3. `resources/views/components/logout-button.blade.php`
4. `resources/views/components/session-meta.blade.php`
5. `routes/api.php`

**Modified Files**:
1. `bootstrap/app.php` - CSRF exception handling, middleware registration
2. `routes/web.php` - GET logout route, middleware application
3. `resources/js/app.js` - Import session monitor
4. `config/session.php` - Config documentation
5. 7+ view files - Use components

---

## Commands Reference

```bash
# Development
php artisan config:clear
php artisan cache:clear
npm run dev

# Testing
./scripts/test-lock.sh
php artisan test --filter=AuthenticationTest

# Production
npm run build
php artisan optimize
```

---

## Approval & Sign-Off

**Implementation Plan Prepared By**: Claude (AI Assistant)
**Ready for Approval**:  Yes
**Estimated Timeline**: 8-10 hours
**Risk Level**: Low (no database changes, backward compatible)

**Next Steps**:
1. Review and approve this plan
2. Create branch: `006-session-reliability`
3. Implement Phase 1 (P0)
4. Test and validate
5. Implement Phase 2 (P1)
6. Test and validate
7. Implement Phase 3 (P2)
8. Deploy to staging
9. Deploy to production

**Questions?** See `quickstart.md` for testing guidance or `research.md` for details.

---

**End of Implementation Plan**
