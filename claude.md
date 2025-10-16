# Joy - MajorMajor suite of applications

This directory contains the project documentation for Joy, a suite of client and internal facing tools for MajorMajor Digital Marketing.

## Documentation Structure

- this app is written with spec-driven-development. We are using Spec Kit framework from GitHub. The documentation files live in the /specs folder.

## Project Overview

Joy is a web application built with the TALL stack (Tailwind, Alpine.js, Laravel, Livewire) + Filament for managing client content calendars. The system provides:

- Monthly calendar and timeline views for content management
- Magic link sharing for client access without login
- Client review, commenting, and approval workflows
- Integration with Trello for comment synchronization
- Multi-platform content variants (Facebook, Instagram, LinkedIn, Blog)

## Key Features

- **Role-based access**: Admin, Agency Team, and Client roles with appropriate permissions
- **Workspace isolation**: Each client gets their own workspace
- **Magic link authentication**: Secure, time-limited access for clients
- **Real-time synchronization**: Comments sync to Trello cards automatically
- **Audit trail**: Complete activity logging for compliance and tracking
- **Responsive design**: Works across desktop and mobile devices

## Technical Stack

- **Frontend**: Tailwind CSS, Alpine.js, Livewire
- **Backend**: Laravel with Filament admin panel
- **Database**: MySQL/PostgreSQL (Laravel compatible)
- **Integrations**: Trello API, Slack (via Trello)
- **Deployment**: Web-based responsive application

## Architecture & Design Decisions

### Frontend Architecture: Livewire (Not REST API)

**IMPORTANT**: This application uses **Livewire full-stack components**, not a REST API architecture.

#### Why Livewire?
- **Server-side rendering** with reactive components
- **No separate frontend/backend split**
- **Simpler architecture** - no API layer needed
- **Better for server-rendered applications**
- **Automatic CSRF protection**
- **Direct service layer access**

#### What This Means:
- ❌ **DO NOT** create REST API controllers
- ❌ **DO NOT** create API routes in `routes/api.php`
- ❌ **DO NOT** create Form Request classes for API validation
- ❌ **DO NOT** create middleware for API authentication/authorization
- ✅ **DO** create Livewire components in `app/Livewire/`
- ✅ **DO** inject services into Livewire constructors
- ✅ **DO** use Blade templates with Livewire directives
- ✅ **DO** handle authorization in Livewire methods using services

#### Historical Context:
In 2025-10-15, we removed an entire unused REST API layer (~5,300 lines) that was built but never wired up to routes. The application had evolved to use Livewire, but the API code was left behind. See commits `e1a1fbd` and `4aec636` for details.

---

### Service Layer Pattern

**Use Services, Not Middleware, in Livewire**

Livewire components should inject services into their constructors and use them directly:

```php
class ContentCalendar extends Component
{
    public function __construct(
        private ContentItemService $contentItemService,
        private RoleDetectionService $roleDetectionService
    ) {}

    public function mount()
    {
        // Use services directly
        $user = $this->roleDetectionService->getCurrentUser();
        if (!$this->roleDetectionService->isAgency($user)) {
            abort(403);
        }
    }
}
```

**Available Services**:
- `RoleDetectionService` - Role checking and permissions
- `ContentItemService` - Content CRUD operations
- `MagicLinkService` - Magic link validation
- `AuditService` - Activity logging
- `SlackNotificationService` - Slack notifications
- `TrelloService` - Trello integration
- And more in `app/Services/`

---

### Middleware Usage

**Registered Middleware** (in `bootstrap/app.php`):
- `validate.magic.link` → `ValidateMagicLink` - Validate magic link tokens
- `admin.auth` → `AdminAuth` - Admin-only routes
- `auth.api` → `EnsureAuthenticated` - API authentication (minimal use)
- `client.access` → `ResolveClientAccess` - Resolve client from context

**When to Use Middleware**:
- ✅ Route-level concerns (authentication, rate limiting)
- ✅ Global request/response modifications
- ✅ Cross-cutting concerns applied to many routes

**When NOT to Use Middleware**:
- ❌ Business logic (use services)
- ❌ Authorization checks in Livewire (use services directly)
- ❌ Role detection (use `RoleDetectionService`)
- ❌ CORS/API concerns (we don't have a REST API)

---

### Authorization Patterns

**In Livewire Components**:
```php
// ✅ Good - Direct service usage
public function mount()
{
    $user = $this->roleDetectionService->getCurrentUser();

    if (!$this->roleDetectionService->isAdmin($user)) {
        return redirect()->route('dashboard')
            ->with('error', 'Admin access required');
    }
}

// ✅ Good - Policy usage
public function deleteContent(ContentItem $item)
{
    $this->authorize('delete', $item);
    $this->contentItemService->delete($item);
}

// ❌ Bad - Creating middleware for Livewire
// Don't do this - middleware is for routes, not components
```

**In Routes**:
```php
// ✅ Good - Middleware for route protection
Route::middleware('admin.auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

// ✅ Good - Livewire route with auth
Route::middleware('auth')->group(function () {
    Route::get('/calendar', ContentCalendar::class);
});
```

---

### File Organization

**Controllers** (`app/Http/Controllers/`):
- `AdminController.php` - Admin panel routes
- `ClientController.php` - Client-specific routes (magic link handling)
- `LoginController.php` - Authentication
- `Admin/TrelloIntegrationController.php` - Admin Trello management
- `Admin/AuditLogController.php` - Admin audit logs

**Livewire Components** (`app/Livewire/`):
- Main application logic lives here
- Full-stack components with views
- Inject services for business logic
- Handle user interactions

**Services** (`app/Services/`):
- Business logic
- External API integrations
- Reusable functionality
- Injected into Livewire and Controllers

---

### When to Create What

| Scenario | Create |
|----------|--------|
| New user-facing feature | Livewire component + Blade view |
| Business logic | Service class |
| Admin panel page | Livewire component in `Admin/` folder |
| Background job | Queue job in `app/Jobs/` |
| External API | Service with contract interface |
| Route protection | Use existing middleware |
| Authorization | Policy or service method |
| Data transformation | Service method |

---

## Development Constitution
**MANDATORY TDD**: All features must follow strict Test-Driven Development.

#### Rules:

1. **NEW TEST FILES by permission only**
   - Do not create new `*Test.php` files unless you ask expressed permission with a reason why.

2. **ALL TESTS MUST PASS** - Zero tolerance for failing tests
   - All existing tests must pass (excluding incomplete tests)
   - When tests fail, assume that the test is correct and the implementation is the issue. Do not change tests just to make them pass.
