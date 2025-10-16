# Service Contracts: Session Reliability & Timeout Handling

**Feature ID**: 006-session-reliability
**Status**: No Contracts Required

## Overview

This feature **does not require new service contracts or interfaces**. Here's why:

## Rationale: No Contracts Needed

### 1. Uses Existing Laravel APIs

This feature works entirely through existing Laravel session and middleware APIs:

- `session()` facade - Laravel's standard session API
- `Auth` facade - Laravel's authentication API
- Middleware pipeline - Laravel's request/response cycle
- Exception handling - Laravel's exception handler

**No custom services are needed** because Laravel already provides all the primitives.

### 2. Stateless JavaScript Module

The client-side session monitoring is implemented as a pure JavaScript module:

```javascript
// session-monitor.js exports functions, not classes
export function initSessionMonitor() { ... }
export function showWarning() { ... }
export function sendHeartbeat() { ... }
```

**No contracts needed** - it's a simple functional module with no dependencies to inject.

### 3. Middleware Is Self-Contained

The `ValidateSession` middleware implements Laravel's middleware contract:

```php
class ValidateSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // Implementation
    }
}
```

**Contract already exists** - Laravel's middleware contract is sufficient.

### 4. No Service Layer Required

This feature doesn't introduce business logic that needs abstraction:

- CSRF handling: Exception handler (closure)
- Session validation: Middleware (standard interface)
- Heartbeat: Simple route closure
- Logout: Existing controller method

**No services to contract** - all logic is procedural or uses framework contracts.

## What This Feature Does Add

### New Middleware

**File**: `app/Http/Middleware/ValidateSession.php`

**Interface**: Implements Laravel's implicit middleware contract
```php
handle(Request $request, Closure $next): Response
```

**Purpose**: Pre-emptive session validation before request processing.

**No contract needed** - Laravel's middleware pipeline handles this.

### New JavaScript Module

**File**: `resources/js/session-monitor.js`

**Exports**:
```javascript
// No class, no interface - just functions
export function initSessionMonitor(config) { ... }
export function checkSessionStatus() { ... }
export function showExpirationWarning() { ... }
export function sendHeartbeat() { ... }
```

**No contract needed** - module is self-contained with no dependencies.

### New Blade Component

**File**: `resources/views/components/logout-button.blade.php`

**Interface**: Blade component contract (Laravel standard)
```php
// Automatic based on file location
<x-logout-button />
```

**No custom contract** - uses Laravel's component system.

### New API Endpoint

**Route**: `GET /api/heartbeat`

**Handler**: Simple closure or controller method
```php
Route::get('/api/heartbeat', function () {
    return response()->noContent();
})->middleware('auth');
```

**No service needed** - endpoint does nothing but touch session (Laravel auto-updates `last_activity`).

## Why Contracts Would Be Over-Engineering

### Example: "SessionMonitorService" (NOT NEEDED)

❌ **Bad Approach**:
```php
interface SessionMonitorServiceContract
{
    public function isExpired(): bool;
    public function getTimeRemaining(): int;
    public function extendSession(): void;
}

class SessionMonitorService implements SessionMonitorServiceContract
{
    public function isExpired(): bool
    {
        return session()->get('last_activity') < time() - config('session.lifetime');
    }
    // ... more methods
}
```

✅ **Better Approach** (what we do):
```php
// In middleware, use Laravel's session API directly
if (session()->has('_token') && session()->get('last_activity') > $threshold) {
    // Session is valid
}
```

**Reason**: Adding a service layer adds complexity without benefit. Laravel's session API is the abstraction.

### Example: "HeartbeatService" (NOT NEEDED)

❌ **Bad Approach**:
```php
interface HeartbeatServiceContract
{
    public function ping(): void;
    public function isEnabled(): bool;
}
```

✅ **Better Approach** (what we do):
```php
// Simple route that does nothing - Laravel handles session update
Route::get('/api/heartbeat', fn() => response()->noContent());
```

**Reason**: The endpoint doesn't need logic - Laravel's session middleware automatically updates `last_activity` on every request.

## Framework Contracts We Do Use

While we don't add new contracts, we do implement these existing framework contracts:

### 1. Middleware Contract

```php
// Implicit contract - all middleware must have this signature
public function handle(Request $request, Closure $next): Response
```

**Implementation**: `ValidateSession` middleware

### 2. Exception Handler Contract

```php
// In bootstrap/app.php
$exceptions->respond(function ($response, $exception, $request) { ... });
```

**Implementation**: CSRF exception handling closure

### 3. Blade Component Contract

```php
// Automatic based on file location
<x-logout-button />
```

**Implementation**: `logout-button.blade.php` component

## Testing Without Contracts

Since we don't have service contracts to mock, testing uses Laravel's built-in testing APIs:

```php
// Test session expiration
$this->actingAs($user);
session()->forget('_token'); // Simulate expired token
$response = $this->post(route('logout'));
$response->assertRedirect(route('login'));

// Test heartbeat
$this->actingAs($user);
$response = $this->get('/api/heartbeat');
$response->assertStatus(204);

// Test middleware
$this->actingAs($user);
session()->put('last_activity', time() - 10000); // Old activity
$response = $this->post('/some-route');
$response->assertRedirect(route('login'));
```

**No mocking needed** - we test against real session and auth systems (which Laravel provides factories for).

## When Would We Need Contracts?

Contracts would be appropriate if:

1. **Multiple Implementations**: If we needed to swap session storage (but Laravel already provides this via drivers)
2. **Complex Business Logic**: If session validation had business rules (but it's just timestamp checks)
3. **External Integration**: If we integrated with a third-party session service (but we use Laravel's)
4. **Team Coordination**: If multiple teams built separate implementations (but this is a single feature)

**None of these apply** - this is a focused feature using existing framework abstractions.

## Conclusion

**This feature requires zero new service contracts** because:

1. ✅ Uses existing Laravel session/auth APIs
2. ✅ Middleware implements framework contract
3. ✅ JavaScript is a simple functional module
4. ✅ No complex business logic to abstract
5. ✅ No external services to integrate

**Result**: The `contracts/` directory exists to document this decision, not to hold contract files.

Adding contracts would increase complexity without providing testability, flexibility, or maintainability benefits. Laravel's existing abstractions are sufficient.
