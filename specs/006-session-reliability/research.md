# Research: Session Reliability & Timeout Handling

**Feature ID**: 006-session-reliability
**Research Date**: 2025-10-13
**Status**: Complete

## Executive Summary

This research document analyzes the current Joy application's session handling, authentication, and CSRF protection mechanisms to understand the root cause of "419 Page Expired" errors and inform the implementation of Session Reliability & Timeout Handling.

## Current Implementation Analysis

### 1. Session Configuration

**Location**: `/Users/jprevel/Documents/joy/joy-app/config/session.php`

**Current Settings**:
- **Driver**: `database` (sessions stored in database table)
- **Lifetime**: `20160` minutes (14 days)
- **Expire on Close**: `false`
- **Cookie Name**: Dynamic based on APP_NAME (e.g., `joy-session`)
- **Same-Site**: `lax`
- **HTTP Only**: `true`
- **Secure**: Environment-dependent
- **Session Table**: `sessions`

**Analysis**:
- Long session lifetime (14 days) is appropriate for the application
- Database driver provides persistence across deployments
- Session configuration is standard Laravel setup
- **Issue**: Despite long lifetime, CSRF tokens still expire causing logout failures

### 2. Current CSRF Exception Handling

**Location**: `/Users/jprevel/Documents/joy/joy-app/bootstrap/app.php` (lines 28-34)

**Current Implementation**:
```php
// Handle CSRF token expiration (419 Page Expired error)
if ($exception instanceof \Illuminate\Session\TokenMismatchException && $request->expectsHtml()) {
    // Clear old session and redirect to login with friendly message
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login')->with('status', 'Your session has expired. Please log in again.');
}
```

**Analysis**:
- ✅ Already attempts to catch `TokenMismatchException`
- ✅ Redirects to login with friendly message
- ⚠️ **Problem**: Attempts to invalidate an already-expired session
- ⚠️ No try-catch wrapper for session operations
- ⚠️ Does not handle case where session is already invalidated

**Root Cause Identified**:
The handler tries to call `session()->invalidate()` on a session that may already be invalid, which can throw additional exceptions or fail silently, leading to the 419 error still appearing.

### 3. Logout Implementation

**Location**: `/Users/jprevel/Documents/joy/joy-app/routes/web.php` (line 15)

**Current Route**:
```php
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');
```

**Analysis**:
- ✅ POST route with CSRF protection
- ✅ Protected by 'auth' middleware
- ❌ **Problem**: No GET alternative for when CSRF token is expired
- ❌ Only one logout path - no fallback mechanism

**Implication**:
When a user's session expires and they click logout:
1. Browser submits POST form with expired CSRF token
2. Laravel's CSRF middleware rejects the request
3. Exception handler tries to clean up session
4. User sees 419 error (current behavior)

### 4. Logout Button Locations

**Found in 7 view files**:

1. `/Users/jprevel/Documents/joy/joy-app/resources/views/livewire/content-calendar.blade.php` (lines 112-122)
2. `/Users/jprevel/Documents/joy/joy-app/resources/views/livewire/statusfaction.blade.php` (lines 127-138)
3. `/Users/jprevel/Documents/joy/joy-app/resources/views/admin/index.blade.php` (lines 20-23)
4. `/Users/jprevel/Documents/joy/joy-app/resources/views/livewire/admin/user-management-page.blade.php` (needs verification)
5. `/Users/jprevel/Documents/joy/joy-app/resources/views/livewire/admin/client-management-page.blade.php` (needs verification)
6. `/Users/jprevel/Documents/joy/joy-app/resources/views/admin/users/index.blade.php` (needs verification)
7. `/Users/jprevel/Documents/joy/joy-app/resources/views/admin/clients/index.blade.php` (needs verification)

**Common Pattern**:
```blade
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="...">
        <svg>...</svg>
        <span>Logout</span>
    </button>
</form>
```

**Analysis**:
- ✅ Consistent pattern across all views
- ✅ Uses named route `logout`
- ❌ All use POST with @csrf token (no fallback)
- ❌ No client-side session checking before submission

### 5. JavaScript Asset Structure

**Build System**: Vite
**Config**: `/Users/jprevel/Documents/joy/joy-app/vite.config.js`

**Entry Points**:
- `resources/css/app.css`
- `resources/js/app.js`

**Current JavaScript Structure**:
```
resources/js/
├── app.js          (imports bootstrap.js)
└── bootstrap.js    (sets up Axios with CSRF header)
```

**bootstrap.js Analysis**:
```javascript
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
```

**Analysis**:
- ✅ Axios configured globally
- ✅ AJAX requests marked with X-Requested-With header
- ❌ No session monitoring JavaScript
- ❌ No CSRF token refresh mechanism
- ❌ No client-side expiration warnings

**Implication**:
Easy to add new JavaScript modules - just import in `app.js` and Vite will bundle them.

### 6. Middleware Stack

**Location**: `/Users/jprevel/Documents/joy/joy-app/bootstrap/app.php` (lines 13-19)

**Custom Middleware Aliases**:
```php
'validate.magic.link' => \App\Http\Middleware\ValidateMagicLink::class,
'admin.auth' => \App\Http\Middleware\AdminAuth::class,
'auth.api' => \App\Http\Middleware\EnsureAuthenticated::class,
'client.access' => \App\Http\Middleware\ResolveClientAccess::class,
```

**Existing Middleware** (found in `/Users/jprevel/Documents/joy/joy-app/app/Http/Middleware/`):
- AdminAuth.php
- ApiCors.php
- ApiRateLimit.php
- CorsHandler.php
- EnhancedAuth.php
- EnsureAuthenticated.php
- MagicLinkAuth.php
- MagicLinkLegacy.php
- MagicLinkRateLimit.php
- RequireRole.php
- ResolveClientAccess.php
- RoleAccess.php
- RoleAuthorization.php
- RoleDetection.php
- ValidateMagicLink.php

**Analysis**:
- ✅ Laravel 11 structure with middleware aliases
- ✅ Multiple custom middleware already implemented
- ❌ No session validation middleware
- ❌ No pre-emptive CSRF checking

**Implication**:
Adding new middleware (`ValidateSession.php`) will follow existing patterns.

### 7. Route Structure Analysis

**Web Routes** (`/Users/jprevel/Documents/joy/joy-app/routes/web.php`):

**Route Groups**:
1. **Guest routes** (lines 11-14): Login forms
2. **Protected routes** (lines 33-48): Calendar, content, statusfaction (requires 'auth')
3. **Magic link routes** (lines 55-60): Client access with tokens
4. **Admin routes** (lines 63-96): Admin panel with 'admin.auth' middleware

**API Routes**:
- ❌ **Not found** - No `api.php` file exists
- Need to create API routes file for heartbeat endpoint

**Analysis**:
- ✅ Clear separation of authenticated vs guest routes
- ✅ Uses middleware groups effectively
- ❌ No API routes file for heartbeat endpoint
- ❌ No GET logout route

### 8. Authentication System

**Auth Middleware**: Laravel's built-in 'auth' middleware
**Custom Auth**: Multiple custom middleware for different auth flows

**Current Auth Flows**:
1. **Standard Auth**: Email/password login for agency and admin users
2. **Magic Links**: Token-based access for client users
3. **Admin Auth**: Additional layer for admin-only routes

**Session Storage**:
- Uses database sessions table
- Session data includes: user_id, payload, last_activity, ip_address, user_agent

**Analysis**:
- ✅ Multiple auth flows working correctly
- ✅ Session persistence via database
- ❌ No client-side session lifetime awareness
- ❌ No automatic session refresh for active users

## Identified Gaps and Problems

### Critical Issues (P0)

1. **CSRF Handler Not Robust**
   - Attempts to invalidate already-invalid session
   - No try-catch for session operations
   - Can still surface 419 errors to users

2. **No Alternative Logout Path**
   - Only POST route with CSRF token
   - No fallback for expired sessions
   - Users cannot log out reliably

### High Priority Issues (P1)

3. **No Client-Side Session Monitoring**
   - JavaScript has no awareness of session expiration
   - No warnings before session expires
   - Forms submitted with expired tokens fail

4. **No Session Meta Tags**
   - Views don't include session expiration time
   - Client-side code cannot calculate time remaining
   - No server timestamp for clock drift detection

### Medium Priority Issues (P2)

5. **No Heartbeat Mechanism**
   - Active users' sessions still expire after 14 days of inactivity
   - No way to extend session for actively working users
   - No API routes file exists for heartbeat endpoint

6. **No Proactive Session Validation**
   - Middleware doesn't pre-emptively check session validity
   - CSRF errors only caught after form submission
   - No early warning system

7. **Logout Buttons Not DRY**
   - Duplicated logout form markup in 7+ files
   - Difficult to update all instances
   - No reusable Blade component

## Architecture Insights

### Laravel 11 Structure

The application uses Laravel 11's simplified structure:
- No `app/Http/Kernel.php` - middleware registered in `bootstrap/app.php`
- Exception handling in `bootstrap/app.php` using closures
- Middleware aliases defined via `$middleware->alias()`

### Vite Build System

- Modern asset pipeline with hot module replacement
- Easy to add new JavaScript modules
- Tailwind CSS with JIT compilation
- Alpine.js included via CDN (in views)

### Livewire Components

Several pages use Livewire:
- ContentCalendar
- ContentReview
- Statusfaction
- AddContent
- Admin user/client management pages

**Implication for Session Handling**:
Livewire AJAX requests automatically include CSRF tokens in headers, but will still fail if session expires. Session monitoring JavaScript must handle both regular forms and Livewire requests.

## Technical Constraints

### Must Maintain

1. **Test Suite Lock**: No new test files (42 files locked)
2. **Existing Routes**: Cannot break existing routes
3. **Middleware Compatibility**: Must work with existing middleware stack
4. **Livewire Compatibility**: Session handling must not break Livewire
5. **Magic Link Auth**: Must not interfere with client magic link access

### Browser Compatibility

- Must work without JavaScript (graceful degradation)
- Must handle browser clock drift
- Must work with tab visibility changes
- Must respect browser session storage limits

### Performance Constraints

- Heartbeat endpoint must be lightweight (no DB queries if possible)
- Session monitoring JavaScript must be minimal overhead
- Cannot significantly increase page load time

## Recommended Implementation Approach

Based on this research, the recommended implementation strategy is:

### Phase 1: Critical Fixes (P0) - 2 hours

1. **Improve CSRF exception handler** in `bootstrap/app.php`
   - Add try-catch for session operations
   - Handle already-invalidated sessions gracefully
   - Ensure friendly error message always shown

2. **Add GET logout route** in `routes/web.php`
   - Create `Route::get('/logout', ...)`
   - Skip CSRF validation for GET logout
   - Implement same logout logic as POST route

3. **Update one logout button** as proof-of-concept
   - Test dual-method logout (POST with GET fallback)
   - Verify both paths work

### Phase 2: Client-Side Protection (P1) - 3 hours

4. **Create session-monitor.js**
   - Track session expiration client-side
   - Show warning 5 minutes before expiration
   - Intercept form submissions if session expired
   - Include meta tag reading for server time

5. **Update all views**
   - Add session meta tags to layout
   - Import session-monitor.js in app.js
   - Create logout button Blade component
   - Replace all logout button instances

### Phase 3: Advanced Features (P2) - 3 hours

6. **Create API routes file and heartbeat endpoint**
   - Add `routes/api.php` if not exists
   - Create lightweight `/api/heartbeat` route
   - Return 204 No Content

7. **Add heartbeat to session-monitor.js**
   - Use Page Visibility API
   - Ping every 10 minutes when tab visible
   - Use navigator.sendBeacon() for reliability

8. **Create ValidateSession middleware**
   - Pre-emptive session validation
   - Apply to web middleware group
   - Skip for login/register routes

## Dependencies and Requirements

### Required New Files

1. `app/Http/Middleware/ValidateSession.php` - Session validation middleware
2. `resources/js/session-monitor.js` - Client-side monitoring
3. `resources/views/components/logout-button.blade.php` - Reusable component
4. `routes/api.php` - API routes file (if not exists)

### Modified Files

1. `bootstrap/app.php` - Improve CSRF exception handling
2. `routes/web.php` - Add GET /logout route
3. `resources/js/app.js` - Import session monitor
4. `resources/views/layouts/app.blade.php` - Add session meta tags (if layout exists)
5. All views with logout buttons (7+ files) - Use new component

### Environment Variables

Add to `.env.example`:
```env
SESSION_WARNING_MINUTES=5
SESSION_HEARTBEAT_ENABLED=true
SESSION_HEARTBEAT_INTERVAL=600
```

## Security Considerations

### GET Logout Route Security

**Concern**: GET requests can be cached or leaked via referrer headers

**Mitigations**:
1. Add `Cache-Control: no-store, no-cache` headers
2. Consider using signed URLs with expiration (optional)
3. Make truly stateless - check auth, log out immediately, redirect
4. Document that GET logout should not be linked externally

### Session Hijacking Prevention

Current protections:
- HTTP-only cookies ✅
- Same-site cookies (lax) ✅
- Secure cookies in production ✅
- Session regeneration on auth ✅

New considerations:
- Heartbeat pings extend session - ensure auth is still verified
- Client-side expiration tracking - doesn't reduce security (server validates)

## Testing Strategy

### Manual Test Scenarios

1. **Expired session logout**
   - Log in, manually expire session, click logout
   - Expected: Redirect to login with message (not 419)

2. **GET logout**
   - Navigate to `/logout` in browser address bar
   - Expected: Successfully logged out

3. **Session warning**
   - Log in, wait for warning (or manually test)
   - Expected: Modal appears 5 minutes before expiration

4. **Heartbeat keeps alive**
   - Log in, stay active, session should not expire
   - Expected: Session extended automatically

### Automated Test Requirements

Based on Test Suite Lock constraint:
- ❌ Cannot create new test file
- ✅ Can add tests to existing `tests/Feature/AuthenticationTest.php`
- Must ensure all existing tests still pass

## Open Questions

1. **Layout File**: Does a shared layout exist for adding session meta tags, or does each view have its own `<head>`?
   - **Answer**: Views include their own `<head>` sections - need to add meta tags to each view or create layout component

2. **API Routes**: Should we create a new `api.php` file or add heartbeat to `web.php`?
   - **Recommendation**: Create `api.php` for proper REST endpoint

3. **Livewire Wire:Navigate**: Do any views use Livewire's wire:navigate? Does this affect session handling?
   - **Answer**: Not evident in reviewed files - standard navigation

4. **Session Table**: Is the sessions table already migrated? What's the schema?
   - **Assumption**: Yes, Laravel's standard sessions table exists (driver is 'database')

## References

- Laravel 11 Session Documentation: https://laravel.com/docs/11.x/session
- Laravel 11 CSRF Protection: https://laravel.com/docs/11.x/csrf
- Laravel 11 Middleware: https://laravel.com/docs/11.x/middleware
- Vite Laravel Plugin: https://laravel.com/docs/11.x/vite

## Conclusion

The research confirms that the "419 Page Expired" error is caused by:
1. CSRF tokens expiring with sessions
2. Logout buttons using POST forms that require valid CSRF tokens
3. Insufficient exception handling for already-invalidated sessions
4. No alternative logout mechanism

The proposed solution (defense in depth with 5 layers) directly addresses these root causes and is feasible within the existing architecture. The implementation can be done incrementally, with P0 fixes providing immediate relief and P1/P2 features adding robustness.

**Research Status**: ✅ Complete - Ready for implementation planning
