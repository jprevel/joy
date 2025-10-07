# Quick Start: Clean Code Refactoring

## Overview
This guide provides step-by-step instructions for implementing the Clean Code refactoring of the Joy application. Follow these phases sequentially to ensure a smooth transition.

## Prerequisites

### 1. Baseline Metrics Collection
```bash
cd joy-app

# Run static analysis baseline
./vendor/bin/phpstan analyze --level=5 --memory-limit=2G > baseline-phpstan.txt

# Run test coverage baseline
./vendor/bin/phpunit --coverage-html coverage-baseline

# Check code formatting
./vendor/bin/pint --test > baseline-pint.txt

# Count lines of code
cloc app/ > baseline-loc.txt
```

### 2. Create Characterization Tests
Before refactoring, create tests that capture current behavior:

```bash
# Run existing test suite to establish baseline
./vendor/bin/phpunit --testdox > baseline-tests.txt

# Ensure all tests pass
./vendor/bin/phpunit
```

### 3. Setup Performance Monitoring
```bash
# Enable Telescope for performance tracking
php artisan telescope:install
php artisan migrate

# Create performance baseline
php artisan tinker
> \App\Models\ContentItem::factory()->count(100)->create();
> exit
```

## Phase 1: Extract Middleware (Week 1)

### Step 1.1: Create Authentication Middleware
```bash
php artisan make:middleware EnsureAuthenticated
```

**File**: `app/Http/Middleware/EnsureAuthenticated.php`
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\RoleDetectionService;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    public function __construct(
        private RoleDetectionService $roleDetection
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->roleDetection->getCurrentUser();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $request->merge(['authenticated_user' => $user]);

        return $next($request);
    }
}
```

### Step 1.2: Create Client Access Middleware
```bash
php artisan make:middleware ResolveClientAccess
```

**File**: `app/Http/Middleware/ResolveClientAccess.php`
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ClientAccessResolver;
use Symfony\Component\HttpFoundation\Response;

class ResolveClientAccess
{
    public function __construct(
        private ClientAccessResolver $clientResolver
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->get('authenticated_user');
        $clientId = $request->input('client_id') ?? $request->route('client');

        try {
            $client = $this->clientResolver->resolveClient($clientId, $user);
            $request->merge(['resolved_client' => $client]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
```

### Step 1.3: Create ClientAccessResolver Service
```bash
php artisan make:service ClientAccessResolver
```

**File**: `app/Services/ClientAccessResolver.php`
```php
<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClientAccessResolver
{
    public function __construct(
        private RoleDetectionService $roleDetection
    ) {}

    public function resolveClient(?int $clientId, User $user): Client
    {
        if ($clientId) {
            $client = Client::findOrFail($clientId);
            $this->validateAccess($user, $client);
            return $client;
        }

        if ($this->roleDetection->isClient($user)) {
            return $user->client;
        }

        throw new \InvalidArgumentException('client_id parameter required for admin/agency users');
    }

    public function validateAccess(User $user, Client $client): void
    {
        if (!$this->roleDetection->canAccessClient($user, $client)) {
            throw new \RuntimeException('You do not have access to this client');
        }
    }
}
```

### Step 1.4: Register Middleware
**File**: `app/Http/Kernel.php`
```php
protected $middlewareAliases = [
    // ... existing middleware
    'auth.api' => \App\Http\Middleware\EnsureAuthenticated::class,
    'client.access' => \App\Http\Middleware\ResolveClientAccess::class,
];
```

### Step 1.5: Update Routes
**File**: `routes/api.php`
```php
// Apply middleware to route groups
Route::middleware(['auth.api', 'client.access'])->group(function () {
    Route::get('/calendar/month', [CalendarController::class, 'month']);
    Route::get('/calendar/range', [CalendarController::class, 'range']);
    Route::get('/calendar/stats', [CalendarController::class, 'stats']);

    Route::apiResource('content-items', ContentItemController::class);
    Route::put('/content-items/{id}/status', [ContentItemController::class, 'updateStatus']);
});
```

### Step 1.6: Refactor Controllers to Use Middleware
**Before** (CalendarController.php):
```php
public function month(Request $request)
{
    $user = $this->roleDetectionService->getCurrentUser();
    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $clientId = $request->input('client_id');
    if ($clientId) {
        $client = Client::findOrFail($clientId);
        if (!$this->roleDetectionService->canAccessClient($user, $client)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
    } else {
        if (!$this->roleDetectionService->isClient($user)) {
            return response()->json(['error' => 'client_id required'], 422);
        }
        $client = $user->client;
    }

    // ... rest of logic
}
```

**After**:
```php
public function month(Request $request)
{
    $client = $request->get('resolved_client');
    $user = $request->get('authenticated_user');

    // ... rest of logic (70 lines reduced to 2 lines)
}
```

### Step 1.7: Test Middleware
```bash
# Create middleware tests
php artisan make:test Middleware/EnsureAuthenticatedTest --unit
php artisan make:test Middleware/ResolveClientAccessTest --unit

# Run tests
./vendor/bin/phpunit --filter=Middleware
```

### Step 1.8: Remove Duplicate Code
Search and remove the duplicate authentication/authorization code from all controllers:
- CalendarController
- ContentItemController
- CommentController
- TrelloController
- MagicLinkController

### Step 1.9: Verify Phase 1
```bash
# All tests should pass
./vendor/bin/phpunit

# Static analysis should improve
./vendor/bin/phpstan analyze --level=6

# Code should be formatted correctly
./vendor/bin/pint

# Manual testing
php artisan serve
# Test API endpoints manually or with Postman
```

**Expected Outcomes**:
- ✅ 170+ lines of duplicate code removed
- ✅ All tests passing
- ✅ No authentication/authorization logic in controllers
- ✅ Middleware handles cross-cutting concerns

---

## Phase 2: Refactor Controllers (Week 2)

### Step 2.1: Create CalendarService
```bash
php artisan make:service CalendarService
```

**File**: `app/Services/CalendarService.php`
```php
<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ContentItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    public function getMonthView(Client $client, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $contentItems = $this->fetchContentItems($client, $startDate, $endDate);

        return [
            'month' => $startDate->format('Y-m'),
            'content_items' => $contentItems,
            'grouped_by_date' => $this->groupByDate($contentItems),
        ];
    }

    public function getRangeView(Client $client, Carbon $start, Carbon $end): array
    {
        $contentItems = $this->fetchContentItems($client, $start, $end);

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'content_items' => $contentItems,
        ];
    }

    private function fetchContentItems(Client $client, Carbon $start, Carbon $end): Collection
    {
        return ContentItem::where('client_id', $client->id)
            ->whereBetween('scheduled_date', [$start, $end])
            ->with(['comments', 'trelloCard'])
            ->orderBy('scheduled_date')
            ->get();
    }

    private function groupByDate(Collection $items): array
    {
        return $items->groupBy(fn($item) => $item->scheduled_date->toDateString())
            ->toArray();
    }
}
```

### Step 2.2: Create CalendarStatisticsService
```bash
php artisan make:service CalendarStatisticsService
```

### Step 2.3: Create API Resources
```bash
php artisan make:resource ContentItemResource
php artisan make:resource CalendarResource
php artisan make:resource ClientResource
```

**File**: `app/Http/Resources/ContentItemResource.php`
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_date' => $this->scheduled_date->toIso8601String(),
            'status' => $this->status,
            'platform' => $this->platform,
            'image_url' => $this->image_url,
            'comments_count' => $this->comments_count ?? $this->comments->count(),
            'client' => new ClientResource($this->whenLoaded('client')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

### Step 2.4: Refactor CalendarController
**File**: `app/Http/Controllers/CalendarController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Services\CalendarService;
use App\Services\CalendarStatisticsService;
use App\Http\Resources\ContentItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CalendarController extends Controller
{
    public function __construct(
        private CalendarService $calendarService,
        private CalendarStatisticsService $statsService
    ) {}

    public function month(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $client = $request->get('resolved_client');
        $data = $this->calendarService->getMonthView(
            $client,
            $validated['year'],
            $validated['month']
        );

        $data['content_items'] = ContentItemResource::collection($data['content_items']);

        return response()->json($data);
    }

    public function range(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $client = $request->get('resolved_client');
        $data = $this->calendarService->getRangeView(
            $client,
            \Carbon\Carbon::parse($validated['start_date']),
            \Carbon\Carbon::parse($validated['end_date'])
        );

        $data['content_items'] = ContentItemResource::collection($data['content_items']);

        return response()->json($data);
    }

    public function stats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $client = $request->get('resolved_client');
        $stats = $this->statsService->getMonthStatistics(
            $client,
            $validated['year'],
            $validated['month']
        );

        return response()->json($stats);
    }
}
```

### Step 2.5: Test Refactored Controllers
```bash
# Run integration tests
./vendor/bin/phpunit --filter=CalendarController

# Check line count reduction
wc -l app/Http/Controllers/CalendarController.php
# Should be ~60 lines vs original 486 lines
```

---

## Phase 3: Complete Service Layer (Week 3)

### Step 3.1: Create Form Requests
```bash
php artisan make:request StoreContentItemRequest
php artisan make:request UpdateContentItemRequest
php artisan make:request UpdateStatusRequest
```

### Step 3.2: Complete ContentItemService
Add missing methods to ContentItemService:
- `update()`
- `delete()`
- `changeStatus()`
- `bulkUpdateStatus()`

### Step 3.3: Create ContentItemStatusManager
```bash
php artisan make:service ContentItemStatusManager
```

### Step 3.4: Refactor ContentItemController
Replace direct model access with service calls.

---

## Phase 4: Trello Service Refactoring (Week 4)

### Step 4.1: Split TrelloService
Create three focused services:
```bash
php artisan make:service TrelloApiClient
php artisan make:service TrelloCardService
php artisan make:service TrelloWebhookService
php artisan make:service TrelloSyncService
```

### Step 4.2: Extract HTTP Client Logic
Move HTTP communication to TrelloApiClient.

### Step 4.3: Update Dependencies
Update controllers and services to use new focused services.

---

## Phase 5: Final Cleanup (Week 5)

### Step 5.1: Naming Improvements
```bash
# Search for generic variable names
grep -r "\$data" app/
grep -r "\$result" app/
grep -r "\$info" app/

# Rename to specific, descriptive names
```

### Step 5.2: Replace Magic Numbers
```php
// Before
return response()->json(['error' => 'Forbidden'], 403);

// After
use Symfony\Component\HttpFoundation\Response;
return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
```

### Step 5.3: Static Analysis to Level 8
```bash
./vendor/bin/phpstan analyze --level=8
# Fix all issues reported
```

### Step 5.4: Final Test Run
```bash
# Unit tests
./vendor/bin/phpunit --testsuite=Unit

# Feature tests
./vendor/bin/phpunit --testsuite=Feature

# Integration tests
./vendor/bin/phpunit --testsuite=Integration

# E2E tests
npx playwright test

# Coverage report
./vendor/bin/phpunit --coverage-html coverage-final
```

---

## Validation & Metrics

### Code Quality Metrics Comparison
```bash
# Compare before/after
diff baseline-phpstan.txt final-phpstan.txt
diff baseline-loc.txt final-loc.txt

# Generate metrics report
./vendor/bin/phpmetrics --report-html=metrics-final app/
```

### Performance Validation
```bash
# Run performance tests
php artisan tinker
> Benchmark::dd(fn() => app(CalendarService::class)->getMonthView($client, 2025, 10));
```

### Expected Improvements
| Metric | Before | After | Status |
|--------|--------|-------|--------|
| Avg Function Length | 28 lines | <20 lines | ✅ |
| Max Function Length | 73 lines | <30 lines | ✅ |
| Code Duplication | 170+ blocks | <10 blocks | ✅ |
| Test Coverage | 65% | 80%+ | ✅ |
| PHPStan Level | 5 | 8 | ✅ |
| Controller LOC (avg) | 220 lines | <100 lines | ✅ |

---

## Rollback Plan

If issues arise, rollback by phase:

### Rollback Phase 1 (Middleware)
```bash
git revert <phase-1-commit-hash>
# Re-add authentication code to controllers temporarily
```

### Rollback Phase 2 (Controller Refactoring)
```bash
git revert <phase-2-commit-hash>
# Restore original controller implementations
```

### Emergency Rollback (Full)
```bash
git reset --hard <pre-refactoring-commit>
# WARNING: This discards all refactoring work
```

---

## Success Criteria Checklist

### Code Quality ✅
- [ ] All functions <20 lines (95%+ compliance)
- [ ] No code duplication >3 lines
- [ ] PHPStan level 8 passing
- [ ] Laravel Pint formatting passing
- [ ] 80%+ test coverage

### Functionality ✅
- [ ] All existing tests passing
- [ ] No regression bugs found
- [ ] Performance benchmarks maintained
- [ ] API contracts respected

### Documentation ✅
- [ ] Service classes documented
- [ ] API resources documented
- [ ] Middleware usage documented
- [ ] Migration guide for team

---

## Team Training

### Knowledge Transfer Session (2 hours)
1. **Overview of Changes** (30 min)
   - Show before/after code examples
   - Explain Clean Code principles applied

2. **New Patterns** (45 min)
   - Middleware usage
   - Service layer architecture
   - API Resources
   - Form Requests

3. **Development Workflow** (30 min)
   - How to add new endpoints
   - How to add business logic
   - Testing strategy

4. **Q&A** (15 min)

### Reference Materials
- Clean Code by Uncle Bob Martin (book)
- Laravel Best Practices documentation
- Internal refactoring documentation
- Code review checklist

---

## Next Steps After Completion

1. **Monitor Production**
   - Watch error rates
   - Track performance metrics
   - Gather user feedback

2. **Continuous Improvement**
   - Schedule regular code reviews
   - Implement automated quality gates
   - Plan next refactoring phase

3. **Documentation Updates**
   - Update onboarding materials
   - Create architecture decision records
   - Document design patterns used
