# Feature Specification: Session Reliability & Timeout Handling

**Feature ID**: 006-session-reliability
**Priority**: P0 (Production Blocker)
**Status**: Draft
**Created**: 2025-10-13

## Problem Statement

**Critical Issue**: Users encounter "419 Page Expired" errors when clicking logout after the application sits idle for a couple of hours. This makes the application unreliable for production use.

### Root Cause Analysis

1. **CSRF Token Expiration**: Laravel's CSRF tokens expire with the session
2. **POST Logout Forms**: All logout buttons use POST forms with `@csrf` tokens
3. **Existing Handler Insufficient**: Current CSRF exception handler (bootstrap/app.php:28-34) attempts to invalidate an already-expired session
4. **No Client-Side Detection**: No JavaScript to detect session expiration before form submission
5. **No Fallback Mechanism**: No alternative logout path that works without CSRF

### Current Session Configuration
- Driver: Database
- Lifetime: 20,160 minutes (14 days)
- Issue persists even with long session lifetime

## User Impact

**Severity**: Production Blocker
- Users cannot reliably log out
- Poor user experience with confusing error messages
- Loss of trust in application stability
- Affects all user roles (Admin, Agency, Client)

## Solution Overview: Defense in Depth

Implement **5 layers of protection** to handle session expiration gracefully:

1. **Layer 1: Improved Exception Handling** - Better CSRF error handling
2. **Layer 2: Alternative GET Logout Route** - Logout without CSRF token
3. **Layer 3: Client-Side Session Monitoring** - JavaScript detects expiration
4. **Layer 4: Heartbeat Keep-Alive** - Optional session refresh for active users
5. **Layer 5: Middleware Session Validation** - Pre-emptive session checks

---

## User Stories

### User Story 1: Graceful CSRF Error Handling (P0)

**As a** user who clicks logout after idle time
**I want** to be redirected to login with a friendly message
**So that** I don't see confusing "Page Expired" errors

**Acceptance Criteria:**
- [ ] CSRF token mismatches redirect to login (not error page)
- [ ] User sees message: "Your session has expired. Please log in again."
- [ ] No stack traces or technical errors shown
- [ ] Works for all POST forms (not just logout)
- [ ] Session is properly cleaned up before redirect

**Technical Implementation:**
- Improve `bootstrap/app.php` exception handler
- Catch `TokenMismatchException` more reliably
- Use try-catch for session invalidation
- Add fallback for already-invalidated sessions

---

### User Story 2: Alternative GET Logout Route (P0)

**As a** user with an expired CSRF token
**I want** to still be able to log out using a GET link
**So that** logout works even when session is expired

**Acceptance Criteria:**
- [ ] GET /logout route exists and works without CSRF
- [ ] All logout buttons updated to use GET link with POST form fallback
- [ ] GET logout properly invalidates session
- [ ] GET logout redirects to login with success message
- [ ] Works identically to POST logout (same cleanup)

**Technical Implementation:**
- Add `Route::get('/logout', ...)` in web.php
- Update logout button markup to support both methods
- Use JavaScript to prefer POST, fallback to GET
- Ensure both paths call same logout logic

---

### User Story 3: Client-Side Session Detection (P1)

**As a** user who leaves the app idle for hours
**I want** to be warned before attempting actions with expired session
**So that** I understand my session expired before getting errors

**Acceptance Criteria:**
- [ ] JavaScript tracks session expiration time
- [ ] Warning appears 5 minutes before expiration
- [ ] User can click to refresh session
- [ ] Form submissions blocked if session definitely expired
- [ ] Auto-redirect to login after definite expiration
- [ ] Works without internet connection (client-side only)

**Technical Implementation:**
- Add `resources/js/session-monitor.js`
- Track last activity timestamp
- Calculate expiration based on session lifetime
- Show Bootstrap/Tailwind modal warning
- Intercept form submissions
- Add meta tag with session start time

---

### User Story 4: Heartbeat Keep-Alive (P2)

**As an** active user working in the application
**I want** my session to stay alive while I'm working
**So that** I don't get logged out while using the app

**Acceptance Criteria:**
- [ ] Ping endpoint created (/api/heartbeat)
- [ ] JavaScript pings every 10 minutes if user is active
- [ ] Session lifetime extended by ping
- [ ] No ping if user inactive (tab not visible)
- [ ] Minimal server load (simple endpoint)
- [ ] Configurable via environment variable

**Technical Implementation:**
- Add `routes/api.php` heartbeat route
- Return simple 204 No Content response
- Use Page Visibility API to detect active tab
- Use `navigator.sendBeacon()` for reliability
- Add SESSION_HEARTBEAT_ENABLED env variable

---

### User Story 5: Middleware Session Validation (P2)

**As a** developer
**I want** proactive session validation before critical actions
**So that** users never encounter CSRF errors

**Acceptance Criteria:**
- [ ] Middleware checks session validity before POST processing
- [ ] Returns JSON error for AJAX requests
- [ ] Redirects to login for regular requests
- [ ] Applied to web middleware group
- [ ] Does not impact API routes (different auth)

**Technical Implementation:**
- Create `app/Http/Middleware/ValidateSession.php`
- Check session has valid token
- Check session not expired
- Add to web middleware group
- Skip for login/register routes

---

## Technical Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                      Client Browser                          │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ session-monitor.js                                    │  │
│  │ - Track expiration time                              │  │
│  │ - Show warnings                                       │  │
│  │ - Intercept form submissions                         │  │
│  │ - Send heartbeat pings                               │  │
│  └──────────────────────────────────────────────────────┘  │
│                           ↓                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Logout Button Component                              │  │
│  │ - Prefers POST with CSRF                             │  │
│  │ - Falls back to GET link                             │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         │ HTTP Request
                         ↓
┌─────────────────────────────────────────────────────────────┐
│                      Laravel Backend                         │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ bootstrap/app.php                                     │  │
│  │ - Exception Handler                                   │  │
│  │ - Catch TokenMismatchException                       │  │
│  │ - Redirect to login with message                     │  │
│  └──────────────────────────────────────────────────────┘  │
│                           ↓                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ ValidateSession Middleware                           │  │
│  │ - Check session validity                             │  │
│  │ - Pre-emptive expiration detection                   │  │
│  └──────────────────────────────────────────────────────┘  │
│                           ↓                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Logout Routes                                         │  │
│  │ - POST /logout (with CSRF)                           │  │
│  │ - GET /logout (no CSRF required)                     │  │
│  └──────────────────────────────────────────────────────┘  │
│                           ↓                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Heartbeat Endpoint                                    │  │
│  │ - GET /api/heartbeat                                  │  │
│  │ - Extends session lifetime                           │  │
│  │ - Returns 204 No Content                             │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### File Changes Required

#### New Files
1. `app/Http/Middleware/ValidateSession.php` - Session validation middleware
2. `resources/js/session-monitor.js` - Client-side session monitoring
3. `resources/views/components/logout-button.blade.php` - Reusable logout component

#### Modified Files
1. `bootstrap/app.php` - Improve CSRF exception handling
2. `routes/web.php` - Add GET /logout route
3. `routes/api.php` - Add /api/heartbeat route
4. `resources/js/app.js` - Import session monitor
5. `resources/views/layouts/app.blade.php` - Add session meta tags
6. All view files with logout buttons (6 files) - Use new component

---

## Implementation Strategy

### Phase 1: Critical Fixes (P0) - Day 1
**Goal**: Stop "Page Expired" errors immediately

1. **Improve CSRF Exception Handler** (30 min)
   - Update bootstrap/app.php
   - Add try-catch for session operations
   - Better error messages

2. **Add GET Logout Route** (45 min)
   - Create route in web.php
   - Test manually
   - Update one logout button as proof of concept

3. **Test & Validate** (45 min)
   - Manually test expired session scenarios
   - Verify no more 419 errors
   - Test both POST and GET logout paths

**Deliverable**: Users can always log out successfully

---

### Phase 2: Client-Side Protection (P1) - Day 2
**Goal**: Warn users before expiration

1. **Create Session Monitor JS** (2 hours)
   - Implement session-monitor.js
   - Add expiration tracking
   - Add form submission interceptor
   - Add warning modal

2. **Add Meta Tags & Import** (30 min)
   - Add session config to layout
   - Import JS module
   - Test in multiple browsers

3. **Update All Logout Buttons** (1 hour)
   - Create Blade component
   - Replace all 6 logout button instances
   - Test across all pages

**Deliverable**: Users warned before session expires

---

### Phase 3: Advanced Features (P2) - Day 3
**Goal**: Keep active users' sessions alive

1. **Create Heartbeat Endpoint** (1 hour)
   - Add API route
   - Add simple controller method
   - Test performance

2. **Add Heartbeat Client Logic** (1 hour)
   - Extend session-monitor.js
   - Use Page Visibility API
   - Test in active/inactive tabs

3. **Create Session Middleware** (1 hour)
   - Create ValidateSession.php
   - Add to middleware stack
   - Test pre-emptive validation

**Deliverable**: Active users never time out

---

## Testing Strategy

### Manual Test Scenarios

1. **Expired CSRF Token Test**
   - Log in
   - Wait for session expiration OR manually expire session
   - Click logout
   - **Expected**: Redirect to login with friendly message (not 419 error)

2. **GET Logout Test**
   - Log in
   - Navigate directly to /logout in browser
   - **Expected**: Successfully logged out, redirect to login

3. **Session Warning Test**
   - Log in
   - Wait until 5 minutes before expiration
   - **Expected**: See warning modal, can click to refresh

4. **Heartbeat Test**
   - Log in
   - Keep tab visible and interact with page
   - Wait past normal expiration time
   - **Expected**: Session stays alive

5. **Inactive Tab Test**
   - Log in
   - Switch to different tab for extended period
   - Return to app
   - **Expected**: Session expired (no heartbeat when inactive)

### Automated Tests

Add to `tests/Feature/AuthenticationTest.php`:

```php
/** @test */
public function expired_csrf_token_redirects_to_login_on_logout()
{
    $user = User::factory()->create();

    $this->actingAs($user);

    // Simulate expired session
    session()->forget('_token');

    $response = $this->post(route('logout'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', 'Your session has expired. Please log in again.');
}

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
public function heartbeat_extends_session_lifetime()
{
    $user = User::factory()->create();

    $this->actingAs($user);

    $initialTime = session()->get('last_activity');

    sleep(2);

    $response = $this->get('/api/heartbeat');

    $response->assertStatus(204);
    $this->assertGreaterThan($initialTime, session()->get('last_activity'));
}
```

---

## Configuration

### Environment Variables

Add to `.env.example`:

```env
# Session Configuration
SESSION_LIFETIME=20160  # 14 days in minutes
SESSION_EXPIRE_ON_CLOSE=false

# Session Monitoring
SESSION_WARNING_MINUTES=5  # Warn 5 minutes before expiration
SESSION_HEARTBEAT_ENABLED=true
SESSION_HEARTBEAT_INTERVAL=600  # 10 minutes in seconds
```

---

## Success Metrics

### Before Implementation
- ❌ Users encounter 419 errors when clicking logout after idle
- ❌ No warning before session expiration
- ❌ Active users get logged out unexpectedly
- ❌ Production app feels unreliable

### After Implementation
- ✅ Zero "Page Expired" errors in logout flow
- ✅ Users see friendly expiration warnings
- ✅ Active users stay logged in
- ✅ Graceful degradation if JavaScript disabled
- ✅ Production-ready session management

---

## Risk Assessment

### Risks & Mitigation

**Risk 1: GET Logout Security Concern**
- **Concern**: GET requests cached by browsers/proxies
- **Mitigation**:
  - Add `Cache-Control: no-store` header
  - Use signed URLs with expiring tokens
  - Make it truly stateless (check auth, log out, redirect)

**Risk 2: Heartbeat Performance Impact**
- **Concern**: Many clients pinging server every 10 minutes
- **Mitigation**:
  - Lightweight endpoint (no DB queries)
  - Only ping when tab visible
  - Configurable interval
  - Can disable via env variable

**Risk 3: Client-Side Clock Drift**
- **Concern**: User's system clock incorrect
- **Mitigation**:
  - Use server timestamp in meta tag
  - Calculate time difference client vs server
  - Warning is advisory (server still validates)

---

## Dependencies

### Laravel Packages (Already Installed)
- Laravel 11.x with session support
- Built-in CSRF protection
- Built-in exception handling

### No Additional Dependencies Required
- Pure JavaScript (no jQuery/frameworks needed)
- Native browser APIs (Page Visibility, Beacon)
- Standard Laravel components

---

## Documentation Updates

### For Developers
- Update CLAUDE.md with session handling conventions
- Document logout component usage
- Explain session monitoring architecture

### For Users
- Add to user guide: "Your session will warn before expiring"
- Explain why heartbeat keeps session alive

---

## Rollout Plan

### Staging Deployment (Day 1-2)
1. Deploy Phase 1 fixes to staging
2. Manual testing by QA/users
3. Monitor logs for any issues
4. Validate no 419 errors

### Production Deployment (Day 3)
1. Deploy all three phases together
2. Monitor error logs closely
3. Check session-related metrics
4. Be ready to rollback if issues

### Post-Deployment
1. Monitor for 48 hours
2. Gather user feedback
3. Adjust timeouts if needed
4. Document any edge cases

---

## Future Enhancements (Out of Scope)

1. **Redis Session Driver**: For better performance at scale
2. **OAuth/SSO Integration**: Alternative auth methods
3. **Remember Me Functionality**: Long-lived sessions
4. **Session Activity Dashboard**: Admin view of active sessions
5. **Force Logout**: Admin can terminate user sessions

---

## Approval & Sign-Off

**Created By**: Claude (AI Assistant)
**Reviewed By**: _Pending_
**Approved By**: _Pending_
**Date**: 2025-10-13

---

## Appendix

### Related Issues
- Issue: "Page Expired" errors on logout after idle
- Priority: P0 (Production Blocker)

### References
- Laravel Session Documentation: https://laravel.com/docs/11.x/session
- Laravel CSRF Protection: https://laravel.com/docs/11.x/csrf
- MDN Page Visibility API: https://developer.mozilla.org/en-US/docs/Web/API/Page_Visibility_API
- MDN Beacon API: https://developer.mozilla.org/en-US/docs/Web/API/Beacon_API
