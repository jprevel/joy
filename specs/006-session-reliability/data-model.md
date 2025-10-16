# Data Model: Session Reliability & Timeout Handling

**Feature ID**: 006-session-reliability
**Version**: 1.0
**Status**: Approved for Implementation

## Overview

This document describes the data model and session lifecycle for the Session Reliability & Timeout Handling feature. **Importantly, this feature requires NO database schema changes** - it works entirely with existing Laravel session infrastructure and adds client-side monitoring.

## Existing Database Schema

### Sessions Table (Already Exists)

This feature uses Laravel's standard sessions table structure, which is already deployed:

```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,           -- Session ID (unique identifier)
    user_id BIGINT UNSIGNED NULL,          -- Foreign key to users table (nullable for guest sessions)
    ip_address VARCHAR(45) NULL,           -- Client IP address
    user_agent TEXT NULL,                  -- Browser user agent string
    payload LONGTEXT NOT NULL,             -- Serialized session data
    last_activity INTEGER NOT NULL,        -- Unix timestamp of last activity

    INDEX sessions_user_id_index (user_id),
    INDEX sessions_last_activity_index (last_activity)
);
```

**No modifications required** - this table works perfectly for session reliability tracking.

### Related Tables (No Changes)

The following tables are related to authentication but require no changes:

- **users**: User accounts (no changes)
- **password_resets**: Password reset tokens (no changes)
- **magic_links**: Client access tokens (no changes - different auth flow)

## Session Data Model

### Session Lifecycle States

Sessions in this application follow a clear lifecycle:

```
┌─────────────────────────────────────────────────────────────────┐
│                      SESSION LIFECYCLE                           │
└─────────────────────────────────────────────────────────────────┘

    [User Not Authenticated]
             │
             │ POST /login (successful)
             ↓
    ┌────────────────────┐
    │   Session Created  │  ← Session ID generated
    │   user_id: NULL    │  ← CSRF token created
    └────────────────────┘  ← last_activity: NOW
             │
             │ Auth::login($user)
             ↓
    ┌────────────────────┐
    │  Session Active    │  ← user_id: set
    │  CSRF Valid        │  ← CSRF token valid
    └────────────────────┘  ← last_activity updated on each request
             │
             │ (User interacts, session stays alive)
             │ Heartbeat pings extend session
             │
             ├─────────────────────────────────────┐
             │                                     │
             │ No activity for 14 days             │ User clicks logout
             ↓                                     ↓
    ┌────────────────────┐              ┌──────────────────────┐
    │  Session Expired   │              │  Explicit Logout     │
    │  CSRF Invalid      │              │  (POST or GET)       │
    └────────────────────┘              └──────────────────────┘
             │                                     │
             │ session()->invalidate()             │ session()->invalidate()
             │ session()->regenerateToken()        │ session()->regenerateToken()
             ↓                                     ↓
    [User Not Authenticated] ← Row deleted from sessions table
```

### Session State Attributes

Each session has the following attributes (stored in `payload` as serialized data):

| Attribute | Type | Description | Managed By |
|-----------|------|-------------|------------|
| `_token` | string | CSRF token (40 chars) | Laravel |
| `user_id` | integer\|null | Authenticated user ID | Laravel Auth |
| `login_web_*` | string | Auth guard identifiers | Laravel Auth |
| `_previous_url` | string | Last URL visited | Laravel |
| `_flash` | array | Flash messages | Laravel |
| `last_activity` | timestamp | Last request time | Laravel (auto) |

**Important**: We do NOT add custom session data - we work with Laravel's existing session structure.

### Client-Side Session Tracking

The JavaScript session monitor tracks session state client-side:

```javascript
// Client-side session state (NOT stored in database)
{
    sessionStartTime: 1697200000000,      // Unix timestamp (milliseconds)
    serverTimeOffset: -5000,              // Client clock vs server clock (ms)
    sessionLifetime: 1209600,             // Lifetime in seconds (14 days)
    warningThreshold: 300,                // Warn 5 minutes before expiry (seconds)
    heartbeatInterval: 600,               // Heartbeat every 10 minutes (seconds)
    heartbeatEnabled: true,               // Whether heartbeat is active
    lastHeartbeat: 1697200000000,         // Last successful heartbeat (ms)
    isTabVisible: true,                   // Page Visibility API state
    hasWarned: false                      // Whether warning modal shown
}
```

**Storage**: This state is maintained in JavaScript memory only (resets on page load).

## Session Expiration Calculation

### Server-Side Expiration

Laravel calculates session expiration as:

```
expiration_time = last_activity + (session_lifetime * 60)

Where:
- last_activity: Unix timestamp (seconds) of last request
- session_lifetime: Config value in minutes (default: 20160 = 14 days)
```

**Storage**: Managed automatically by Laravel in `sessions.last_activity` column.

### Client-Side Expiration Estimate

JavaScript estimates expiration as:

```javascript
const sessionStartTime = parseInt(document.querySelector('meta[name="session-start"]').content);
const sessionLifetime = parseInt(document.querySelector('meta[name="session-lifetime"]').content);
const estimatedExpiration = sessionStartTime + (sessionLifetime * 1000); // Convert to ms
const timeRemaining = estimatedExpiration - Date.now();
```

**Accuracy**: This is an estimate because:
1. Client clock may differ from server clock (we calculate offset)
2. Heartbeat pings extend server session but client estimate may lag
3. Other browser tabs may make requests, extending session

**Solution**: Client-side estimate is advisory only - server always validates.

## Session Extension Mechanism

### Standard Session Extension (Automatic)

Every HTTP request to the application automatically updates `last_activity`:

```
Request → Laravel → Session Middleware → Update last_activity → Response
```

**No code changes needed** - this is Laravel's default behavior.

### Heartbeat Session Extension (New)

For active users, the heartbeat ping extends session without full page request:

```
Browser (tab visible)
    → JavaScript (every 10 min)
    → GET /api/heartbeat
    → Laravel Session Middleware
    → Update last_activity
    → 204 No Content Response
```

**Implementation**: Lightweight endpoint that simply touches session.

## Data Flow Diagrams

### Login Flow (No Changes)

```
User submits login form
    ↓
POST /login with credentials
    ↓
LoginController validates
    ↓
Auth::login($user) creates session
    ↓
sessions table: Insert/Update row
    ↓
Response with Set-Cookie header
    ↓
Browser stores session cookie
    ↓
User redirected to dashboard
```

### Logout Flow (Enhanced)

```
                    User clicks logout button
                              ↓
                    ┌─────────┴─────────┐
                    │                   │
          JavaScript detects        Browser submits
          session expired           POST form with @csrf
                    │                   │
                    ↓                   ↓
          Use GET /logout      CSRF validation
          (no token needed)            │
                    │            ┌─────┴──────┐
                    │            │            │
                    │        Token Valid  Token Invalid
                    │            │            │
                    │            ↓            ↓
                    │      POST logout   Exception Handler
                    │            │            │
                    └────────────┴────────────┘
                                 ↓
                      LoginController::logout()
                                 ↓
                      Auth::logout() + session()->invalidate()
                                 ↓
                      sessions table: DELETE row
                                 ↓
                      Redirect to /login with message
```

### Session Monitoring Flow (New)

```
Page loads with meta tags
    ↓
<meta name="session-start" content="1697200000">
<meta name="session-lifetime" content="1209600">
    ↓
session-monitor.js initializes
    ↓
Calculates expiration time
    ↓
Sets up interval timers
    ↓
┌─────────────────────────────────────┐
│  Every 1 minute:                    │
│  - Check time remaining             │
│  - If < 5 minutes: Show warning     │
│  - If expired: Block forms          │
└─────────────────────────────────────┘
    ↓
┌─────────────────────────────────────┐
│  Every 10 minutes (if tab visible): │
│  - Send heartbeat ping              │
│  - Extend session                   │
│  - Update client estimate           │
└─────────────────────────────────────┘
```

## Configuration Data Model

### Environment Variables

These environment variables control session behavior:

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `SESSION_DRIVER` | string | `database` | Where to store sessions |
| `SESSION_LIFETIME` | integer | `20160` | Session lifetime in minutes (14 days) |
| `SESSION_EXPIRE_ON_CLOSE` | boolean | `false` | Expire when browser closes |
| `SESSION_WARNING_MINUTES` | integer | `5` | Warn N minutes before expiry |
| `SESSION_HEARTBEAT_ENABLED` | boolean | `true` | Enable heartbeat keep-alive |
| `SESSION_HEARTBEAT_INTERVAL` | integer | `600` | Heartbeat ping every N seconds |

**Storage**: `.env` file (not committed to git, example in `.env.example`)

### Runtime Configuration

Configuration is read from `config/session.php` and passed to views via meta tags:

```blade
<meta name="session-start" content="{{ time() }}">
<meta name="session-lifetime" content="{{ config('session.lifetime') * 60 }}">
<meta name="session-warning" content="{{ config('session.warning_minutes', 5) * 60 }}">
<meta name="heartbeat-enabled" content="{{ config('session.heartbeat_enabled', true) ? '1' : '0' }}">
<meta name="heartbeat-interval" content="{{ config('session.heartbeat_interval', 600) }}">
```

## Session Security Model

### CSRF Token Management

**Current Behavior**:
- CSRF token stored in session (`_token` key)
- Token included in forms via `@csrf` directive
- Token validated by `VerifyCsrfToken` middleware

**Enhanced Behavior**:
- JavaScript checks session validity before form submission
- GET logout route bypasses CSRF (but validates auth)
- Exception handler gracefully handles token mismatches

### Session Hijacking Prevention

Existing Laravel protections (no changes):
- HTTP-only cookies (JavaScript cannot access)
- Secure cookies in production (HTTPS only)
- Same-site cookies (CSRF protection)
- Session ID regeneration on auth state change

### Heartbeat Security

**Concern**: Heartbeat pings extend session - could attacker abuse this?

**Mitigations**:
1. Heartbeat requires valid session cookie (cannot be forged)
2. Heartbeat validates auth state (must be logged in)
3. Heartbeat only extends existing session (doesn't create new)
4. Rate limiting can be added if needed (existing `ApiRateLimit` middleware)

## Data Retention and Cleanup

### Session Garbage Collection

Laravel automatically cleans up expired sessions:

```php
// In config/session.php
'lottery' => [2, 100],  // 2% chance per request
```

**Behavior**:
- On 2% of requests, Laravel runs garbage collection
- Deletes rows from `sessions` table where `last_activity` is old
- Threshold: `last_activity < (current_time - session_lifetime)`

**No changes needed** - this continues to work automatically.

### Manual Cleanup (Optional)

Admins can manually clean up sessions if needed:

```bash
php artisan session:table  # Show session stats
php artisan cache:clear    # Clear all sessions (use with caution)
```

**Note**: This feature doesn't add new cleanup commands.

## Migration Strategy

### Database Migrations

**Required**: NONE ✅

**Rationale**: This feature uses existing Laravel session infrastructure. The `sessions` table already exists and has the correct schema.

### Data Migration

**Required**: NONE ✅

**Rationale**: No schema changes = no data migration needed. Existing sessions continue to work without modification.

### Backward Compatibility

**Impact**: 100% backward compatible ✅

**Guarantees**:
- Existing sessions continue to work
- Logout still works if JavaScript disabled (uses POST form)
- GET logout is optional enhancement
- Session lifetime unchanged
- CSRF protection still active

## Testing Data Requirements

### Test Scenarios Requiring Data

1. **Session Expiration Test**
   - Need: User account with valid session
   - Action: Manually expire session in database
   - Expected: Logout redirects to login (not 419 error)

2. **Heartbeat Test**
   - Need: User account with active session
   - Action: Send heartbeat ping
   - Expected: `last_activity` timestamp updates

3. **Warning Modal Test**
   - Need: Session near expiration (or mock time)
   - Expected: JavaScript shows warning modal

### Test Data Setup

```php
// In tests, can manipulate session directly:
session()->put('_token', 'test-token');
session()->put('last_activity', time() - 3600); // 1 hour ago
session()->save();

// Or use factory for users:
$user = User::factory()->create();
$this->actingAs($user);
```

## Summary

**Key Points**:

1. ✅ **No database changes required** - uses existing `sessions` table
2. ✅ **No new models** - works with Laravel's session API
3. ✅ **Client-side tracking** - JavaScript monitors expiration (in memory only)
4. ✅ **Configuration via .env** - runtime behavior configurable
5. ✅ **Backward compatible** - existing sessions work unchanged
6. ✅ **Security maintained** - all existing protections remain active

**Data Storage Locations**:
- **Server-side**: `sessions` table (existing)
- **Client-side**: JavaScript variables (not persisted)
- **Configuration**: `.env` file and `config/session.php`

This feature is entirely non-destructive and works within Laravel's existing session management framework. No database migrations, no schema changes, no data loss risk.
