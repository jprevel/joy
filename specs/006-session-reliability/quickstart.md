# Developer Quickstart: Session Reliability & Timeout Handling

**Feature ID**: 006-session-reliability
**Audience**: Developers testing or extending this feature
**Last Updated**: 2025-10-13

## Quick Overview

This feature prevents "419 Page Expired" errors when users log out after idle periods. It implements 5 layers of protection:

1. Improved CSRF exception handling
2. Alternative GET logout route
3. Client-side session monitoring
4. Heartbeat keep-alive
5. Middleware session validation

**Key Point**: This feature uses existing Laravel session infrastructure - no database changes required.

## Getting Started

### 1. Environment Setup

Add these variables to your `.env`:

```env
# Session Configuration (defaults)
SESSION_DRIVER=database
SESSION_LIFETIME=20160          # 14 days in minutes
SESSION_EXPIRE_ON_CLOSE=false

# Session Monitoring (new)
SESSION_WARNING_MINUTES=5       # Warn 5 minutes before expiration
SESSION_HEARTBEAT_ENABLED=true  # Enable keep-alive pings
SESSION_HEARTBEAT_INTERVAL=600  # Ping every 10 minutes (seconds)
```

### 2. Asset Compilation

Ensure JavaScript assets are compiled:

```bash
# Development
npm run dev

# Production
npm run build
```

### 3. Clear Cache

After making changes, clear Laravel's cache:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Testing Session Expiration

### Method 1: Manual Session Expiration (Recommended)

Use Laravel Tinker to manually expire a session:

```bash
php artisan tinker
```

```php
// Find your current session (look at browser cookie value)
$sessionId = 'your-session-id-here';

// Get the session
use Illuminate\Support\Facades\DB;
$session = DB::table('sessions')->where('id', $sessionId)->first();

// Manually set last_activity to old timestamp (more than 14 days ago)
DB::table('sessions')
    ->where('id', $sessionId)
    ->update(['last_activity' => time() - (20160 * 60 + 100)]); // Just past expiration

// Or just delete the token
session()->forget('_token');
session()->save();
```

**Then**: Try to click logout in the browser. Expected: Redirect to login with message (no 419 error).

### Method 2: Temporarily Reduce Session Lifetime

For faster testing, temporarily reduce the session lifetime:

```env
# In .env (ONLY FOR TESTING)
SESSION_LIFETIME=1  # 1 minute
SESSION_WARNING_MINUTES=0.5  # 30 seconds warning
```

**Remember**: Change this back before committing!

```bash
php artisan config:clear  # Apply changes
```

### Method 3: Browser DevTools (Chrome/Firefox)

1. Open DevTools (F12)
2. Go to Application tab (Chrome) or Storage tab (Firefox)
3. Find Cookies → your domain
4. Find the session cookie (e.g., `joy-session`)
5. Delete the cookie
6. Try to submit a form or click logout

**Expected**: Either GET logout works, or CSRF exception handler redirects gracefully.

## Testing GET Logout

### Manual Test

1. Log in to the application
2. In browser address bar, navigate to:
   ```
   http://localhost/logout
   ```
3. **Expected Result**:
   - You are logged out
   - Redirected to `/login`
   - See message: "You have been logged out successfully"

### cURL Test

```bash
# With valid session cookie
curl -X GET http://localhost/logout \
  -b "joy-session=your-session-id-here" \
  -L  # Follow redirects

# Expected: Redirected to /login
```

### Automated Test

Add to `tests/Feature/AuthenticationTest.php`:

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
```

## Testing Session Warning Modal

### Quick Test with Short Lifetime

1. Set short session lifetime:
   ```env
   SESSION_LIFETIME=2  # 2 minutes
   SESSION_WARNING_MINUTES=1  # Warn after 1 minute
   ```

2. Clear config: `php artisan config:clear`

3. Log in and wait 1 minute

4. **Expected**: Warning modal appears

### Test with Browser Console

Override the timing in browser console:

```javascript
// Open browser DevTools console (F12)

// Force warning to appear immediately
if (window.sessionMonitor) {
    window.sessionMonitor.showExpirationWarning();
}

// Or manually trigger by setting expiration time to now
if (window.sessionMonitor) {
    window.sessionMonitor.sessionStartTime = Date.now() - (14 * 24 * 60 * 60 * 1000); // 14 days ago
    window.sessionMonitor.checkSessionStatus();
}
```

### What to Look For

When warning appears, verify:
- [ ] Modal shows clear message
- [ ] "Refresh Session" button works (extends session)
- [ ] "Logout Now" button logs out
- [ ] Modal cannot be dismissed by clicking outside
- [ ] Warning only shows once per session

## Testing Heartbeat Keep-Alive

### Enable Verbose Logging

Add console logging to `session-monitor.js` (temporarily):

```javascript
function sendHeartbeat() {
    console.log('[Heartbeat] Sending ping at:', new Date());

    fetch('/api/heartbeat', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('[Heartbeat] Response status:', response.status);
        if (response.status === 204) {
            console.log('[Heartbeat] Session extended successfully');
        }
    });
}
```

### Manual Test

1. Log in to the application
2. Open browser DevTools Console (F12)
3. Keep tab visible and active
4. Every 10 minutes, you should see:
   ```
   [Heartbeat] Sending ping at: <timestamp>
   [Heartbeat] Response status: 204
   [Heartbeat] Session extended successfully
   ```

5. Switch to another tab for 10+ minutes
6. **Expected**: No heartbeat pings (tab not visible)

### Database Verification

Check that `last_activity` is updated:

```bash
php artisan tinker
```

```php
// Watch last_activity timestamp
use Illuminate\Support\Facades\DB;

// Your session ID (from browser cookie)
$sessionId = 'your-session-id-here';

// Check last activity before heartbeat
$before = DB::table('sessions')->where('id', $sessionId)->value('last_activity');
echo "Before: " . date('Y-m-d H:i:s', $before) . "\n";

// Wait for heartbeat to fire (10 minutes, or reduce interval for testing)

// Check last activity after heartbeat
$after = DB::table('sessions')->where('id', $sessionId)->value('last_activity');
echo "After: " . date('Y-m-d H:i:s', $after) . "\n";

// Should be updated
echo "Updated: " . ($after > $before ? 'YES' : 'NO') . "\n";
```

### Test Page Visibility API

Test that heartbeat respects tab visibility:

```javascript
// In browser DevTools console

// Check current visibility state
console.log('Tab visible:', !document.hidden);

// Listen for visibility changes
document.addEventListener('visibilitychange', () => {
    console.log('Visibility changed:', !document.hidden);
});

// Switch tabs and check console
// Expected: Heartbeat only fires when tab is visible
```

## Testing CSRF Exception Handling

### Simulate Expired CSRF Token

```bash
php artisan tinker
```

```php
// Corrupt current session token
session()->put('_token', 'invalid-token');
session()->save();

// Or remove token entirely
session()->forget('_token');
session()->save();
```

**Then**: Try to submit a form (e.g., click logout).

**Expected**:
- No "419 Page Expired" error
- Redirected to `/login`
- Message: "Your session has expired. Please log in again."

### Automated Test

```php
/** @test */
public function expired_csrf_token_redirects_to_login_gracefully()
{
    $user = User::factory()->create();
    $this->actingAs($user);

    // Simulate expired CSRF token
    session()->forget('_token');

    // Try to logout with POST (would normally fail with 419)
    $response = $this->post(route('logout'));

    // Should redirect to login with message, not throw 419
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
}
```

## Testing Middleware Session Validation

The `ValidateSession` middleware pre-emptively checks session validity.

### Test Valid Session

```bash
# As authenticated user, make request
curl http://localhost/calendar/admin \
  -b "joy-session=valid-session-id" \
  -L

# Expected: 200 OK, page loads normally
```

### Test Expired Session

```bash
php artisan tinker
```

```php
// Expire a session
use Illuminate\Support\Facades\DB;
$sessionId = 'your-session-id-here';

DB::table('sessions')
    ->where('id', $sessionId)
    ->update(['last_activity' => time() - (20160 * 60 + 100)]);
```

```bash
# Try to access protected route
curl http://localhost/calendar/admin \
  -b "joy-session=expired-session-id" \
  -L

# Expected: Redirected to /login
```

### Automated Test

```php
/** @test */
public function middleware_blocks_expired_sessions()
{
    $user = User::factory()->create();
    $this->actingAs($user);

    // Manually expire session
    session()->put('last_activity', time() - (config('session.lifetime') * 60 + 100));
    session()->save();

    // Try to access protected route
    $response = $this->get(route('calendar'));

    // Should redirect to login
    $response->assertRedirect(route('login'));
    $this->assertGuest();
}
```

## Common Testing Scenarios

### Scenario 1: User Returns After Long Absence

**Setup**:
1. User logs in
2. User closes laptop (browser tab still open)
3. User returns 15 days later

**Expected**:
- Session expired (14 day lifetime)
- Clicking logout uses GET route (no CSRF needed)
- User redirected to login with message

**Test**:
```php
$user = User::factory()->create();
$this->actingAs($user);

// Simulate 15 days passing
session()->put('last_activity', time() - (15 * 24 * 60 * 60));

// GET logout should work
$response = $this->get('/logout');
$response->assertRedirect(route('login'));
```

### Scenario 2: Active User Stays Logged In

**Setup**:
1. User logs in
2. User actively works in application (tab visible)
3. User works for > 14 days

**Expected**:
- Heartbeat keeps session alive
- User never sees expiration warning
- User can work indefinitely

**Test**: Manual only (requires time passage)

### Scenario 3: User in Multiple Tabs

**Setup**:
1. User logs in
2. User opens calendar in Tab 1
3. User opens statusfaction in Tab 2
4. User switches between tabs

**Expected**:
- Each tab sends heartbeat when visible
- Session stays alive as long as one tab active
- Warning appears in all tabs if session expires

**Test**: Manual only (open multiple tabs)

### Scenario 4: User Disables JavaScript

**Setup**:
1. User disables JavaScript in browser
2. User tries to use application

**Expected**:
- Session monitoring does not work (no JavaScript)
- Logout still works (uses POST form with @csrf)
- No JavaScript errors
- Graceful degradation

**Test**:
1. Disable JavaScript in DevTools
2. Navigate application
3. Click logout
4. **Expected**: Logout works normally

## Debugging Tools

### Browser Console Logging

The session monitor logs activity to console:

```javascript
// In session-monitor.js (already implemented)
console.log('[SessionMonitor] Initialized with lifetime:', sessionLifetime);
console.log('[SessionMonitor] Warning threshold:', warningThreshold);
console.log('[SessionMonitor] Session expires at:', new Date(expirationTime));
```

Enable verbose logging by setting:

```javascript
window.sessionMonitorDebug = true;
```

### Laravel Logging

Add logging to exception handler for debugging:

```php
// In bootstrap/app.php
if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
    \Log::info('CSRF Token Mismatch', [
        'url' => $request->url(),
        'method' => $request->method(),
        'session_id' => session()->getId(),
        'has_token' => session()->has('_token'),
        'user_id' => auth()->id(),
    ]);
}
```

Check logs:

```bash
tail -f storage/logs/laravel.log | grep "CSRF Token"
```

### Session Table Inspection

Monitor session table in real-time:

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\DB;

// Watch all sessions
DB::table('sessions')->get()->each(function ($session) {
    echo "Session: {$session->id}\n";
    echo "User: {$session->user_id}\n";
    echo "Last Activity: " . date('Y-m-d H:i:s', $session->last_activity) . "\n";
    echo "Expires: " . date('Y-m-d H:i:s', $session->last_activity + (config('session.lifetime') * 60)) . "\n";
    echo "---\n";
});
```

## Configuration Reference

### Session Lifetime Presets

For different testing scenarios:

```env
# Ultra-short (for testing warning modal)
SESSION_LIFETIME=2
SESSION_WARNING_MINUTES=1

# Short (for testing expiration)
SESSION_LIFETIME=5
SESSION_WARNING_MINUTES=2

# Standard (production)
SESSION_LIFETIME=20160  # 14 days
SESSION_WARNING_MINUTES=5

# Long (for development)
SESSION_LIFETIME=43200  # 30 days
SESSION_WARNING_MINUTES=10
```

### Heartbeat Presets

```env
# Aggressive (testing)
SESSION_HEARTBEAT_ENABLED=true
SESSION_HEARTBEAT_INTERVAL=60  # 1 minute

# Standard (production)
SESSION_HEARTBEAT_ENABLED=true
SESSION_HEARTBEAT_INTERVAL=600  # 10 minutes

# Conservative (reduce server load)
SESSION_HEARTBEAT_ENABLED=true
SESSION_HEARTBEAT_INTERVAL=1800  # 30 minutes

# Disabled (rely on manual activity only)
SESSION_HEARTBEAT_ENABLED=false
```

## Troubleshooting

### Problem: 419 Error Still Appears

**Possible Causes**:
1. Exception handler not updated
2. Cache not cleared
3. JavaScript not compiled
4. Wrong environment

**Solutions**:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
npm run build
```

### Problem: Warning Modal Never Appears

**Possible Causes**:
1. JavaScript not loaded
2. Session lifetime too long
3. Meta tags missing
4. Console errors

**Solutions**:
1. Check browser console for errors
2. Verify meta tags exist in page source
3. Check `window.sessionMonitor` is defined
4. Reduce session lifetime for testing

### Problem: Heartbeat Not Firing

**Possible Causes**:
1. Heartbeat disabled in config
2. Tab not visible
3. JavaScript error
4. Route not defined

**Solutions**:
1. Check `SESSION_HEARTBEAT_ENABLED=true`
2. Ensure browser tab is active
3. Check browser console for errors
4. Verify route exists: `php artisan route:list | grep heartbeat`

### Problem: GET Logout Not Working

**Possible Causes**:
1. Route not defined
2. Middleware blocking
3. Cache issue

**Solutions**:
```bash
# Check route exists
php artisan route:list | grep logout

# Clear cache
php artisan route:clear
php artisan config:clear

# Verify in browser
curl -X GET http://localhost/logout -L
```

## Running the Full Test Suite

Per project requirements, the test suite is locked at 42 test files.

**Before testing**:
```bash
# Run the test lock script
./scripts/test-lock.sh
```

**Expected**: All tests pass (23 incomplete tests are allowed).

**If tests fail**: Fix them before proceeding - the test suite must be green.

## Next Steps

After testing this feature:

1. **Verify all layers work**:
   - [ ] CSRF exception handler catches errors
   - [ ] GET logout works without CSRF
   - [ ] Session warning appears before expiration
   - [ ] Heartbeat extends active sessions
   - [ ] Middleware validates sessions

2. **Performance check**:
   - [ ] Heartbeat endpoint is fast (< 50ms)
   - [ ] JavaScript overhead is minimal
   - [ ] No memory leaks in long-running sessions

3. **User experience check**:
   - [ ] Warning modal is clear and helpful
   - [ ] Logout always works (never 419 error)
   - [ ] Works on mobile browsers
   - [ ] Works with JavaScript disabled (degrades gracefully)

## Quick Reference

### Key Files

| File | Purpose |
|------|---------|
| `bootstrap/app.php` | Exception handling |
| `routes/web.php` | Logout routes |
| `routes/api.php` | Heartbeat endpoint |
| `app/Http/Middleware/ValidateSession.php` | Session validation |
| `resources/js/session-monitor.js` | Client-side monitoring |
| `resources/views/components/logout-button.blade.php` | Reusable logout |
| `config/session.php` | Session configuration |

### Key Commands

```bash
# Clear caches
php artisan config:clear && php artisan cache:clear && php artisan view:clear

# Rebuild assets
npm run build

# Test logout
curl -X GET http://localhost/logout -L

# Watch logs
tail -f storage/logs/laravel.log

# Inspect sessions
php artisan tinker
> DB::table('sessions')->get();
```

### Key Routes

- `POST /logout` - Standard logout (with CSRF)
- `GET /logout` - Alternative logout (no CSRF)
- `GET /api/heartbeat` - Session extension ping
- `GET /login` - Login page (redirect destination)

## Support

For questions or issues:
1. Check this quickstart guide
2. Review `research.md` for architecture details
3. Review `spec.md` for feature requirements
4. Check browser console for JavaScript errors
5. Check Laravel logs for server errors

---

**Happy Testing!** 🚀

This feature should make the Joy application significantly more reliable and user-friendly.
