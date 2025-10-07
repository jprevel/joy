# Tasks: Clean Code Refactoring

**Branch**: `001-clean-code-refactoring`
**Input**: Design documents from `/specs/001-clean-code-refactoring/`
**Prerequisites**: ✅ plan.md, ✅ research.md, ✅ data-model.md, ✅ contracts/, ✅ quickstart.md

**Tech Stack**: PHP 8.2+, Laravel 12.x, PHPUnit 11.5+, Larastan (PHPStan)
**Project Structure**: Laravel monolith at `joy-app/`

## Task Execution Rules

1. **TDD Required**: Write tests first, verify they fail, then implement
2. **[P] = Parallel**: Tasks marked [P] can run simultaneously (different files)
3. **Dependencies**: Complete prerequisite tasks before dependent tasks
4. **Commit Strategy**: Commit after each task completion
5. **Test Verification**: Run `./vendor/bin/phpunit` after each implementation task

---

## Phase 1: Setup & Baseline (CRITICAL)

### T001: Establish Code Quality Baseline
**Priority**: CRITICAL | **Effort**: S

**Description**: Capture current codebase metrics before refactoring

**Commands**:
```bash
cd joy-app

# Static analysis baseline
./vendor/bin/phpstan analyze --level=5 --memory-limit=2G > ../specs/001-clean-code-refactoring/baseline-phpstan.txt

# Test coverage baseline
./vendor/bin/phpunit --coverage-html ../specs/001-clean-code-refactoring/coverage-baseline

# Code style baseline
./vendor/bin/pint --test > ../specs/001-clean-code-refactoring/baseline-pint.txt

# Lines of code baseline
cloc app/ > ../specs/001-clean-code-refactoring/baseline-loc.txt
```

**Acceptance Criteria**:
- [ ] baseline-phpstan.txt created
- [ ] coverage-baseline/ directory created with HTML report
- [ ] baseline-pint.txt created
- [ ] baseline-loc.txt created
- [ ] All baselines committed to git

**Dependencies**: None

---

### T002: Run Existing Test Suite Baseline
**Priority**: CRITICAL | **Effort**: S

**Description**: Verify all existing tests pass before refactoring

**Commands**:
```bash
cd joy-app
./vendor/bin/phpunit --testdox > ../specs/001-clean-code-refactoring/baseline-tests.txt
./vendor/bin/phpunit
```

**Acceptance Criteria**:
- [ ] All existing tests pass
- [ ] baseline-tests.txt created
- [ ] Test count recorded (Unit, Feature, Integration, E2E)

**Dependencies**: None

---

## Phase 2: Middleware Extraction Tests (CRITICAL - TDD Phase)

⚠️ **CRITICAL**: All T003-T005 tests MUST be written and MUST FAIL before implementing T006-T008

### T003: [P] Create EnsureAuthenticated Middleware Test
**Priority**: CRITICAL | **Effort**: S

**Description**: Write failing test for EnsureAuthenticated middleware

**Files to Create**:
- `joy-app/tests/Unit/Middleware/EnsureAuthenticatedTest.php`

**Test Cases**:
```php
- test_allows_authenticated_user_to_pass()
- test_blocks_unauthenticated_user_with_401()
- test_merges_authenticated_user_into_request()
```

**Acceptance Criteria**:
- [ ] Test file created with 3 test methods
- [ ] Tests FAIL (middleware doesn't exist yet)
- [ ] Uses PHPUnit assertions
- [ ] Mocks RoleDetectionService

**Dependencies**: T002

---

### T004: [P] Create ResolveClientAccess Middleware Test
**Priority**: CRITICAL | **Effort**: S

**Description**: Write failing test for ResolveClientAccess middleware

**Files to Create**:
- `joy-app/tests/Unit/Middleware/ResolveClientAccessTest.php`

**Test Cases**:
```php
- test_resolves_client_from_client_id_parameter()
- test_resolves_client_from_authenticated_client_user()
- test_validates_user_has_access_to_client()
- test_returns_403_when_access_denied()
- test_returns_422_when_admin_missing_client_id()
```

**Acceptance Criteria**:
- [ ] Test file created with 5 test methods
- [ ] Tests FAIL (middleware doesn't exist yet)
- [ ] Uses database factory for Client/User

**Dependencies**: T002

---

### T005: [P] Create ClientAccessResolver Service Test
**Priority**: CRITICAL | **Effort**: M

**Description**: Write failing unit tests for ClientAccessResolver service

**Files to Create**:
- `joy-app/tests/Unit/Services/ClientAccessResolverTest.php`

**Test Cases**:
```php
- test_resolve_client_with_client_id_for_admin()
- test_resolve_client_from_user_for_client_role()
- test_throws_exception_when_admin_missing_client_id()
- test_validate_access_passes_for_authorized_user()
- test_validate_access_throws_for_unauthorized_user()
- test_validate_access_passes_for_client_own_data()
```

**Acceptance Criteria**:
- [ ] Test file created with 6+ test methods
- [ ] Tests FAIL (service doesn't exist yet)
- [ ] Tests cover all business rules from data-model.md
- [ ] Uses database factories for test data

**Dependencies**: T002

---

## Phase 3: Middleware Implementation (CRITICAL)

⚠️ **Run T003-T005 tests - verify they FAIL before proceeding**

### T006: Implement EnsureAuthenticated Middleware
**Priority**: CRITICAL | **Effort**: S

**Description**: Create middleware to handle authentication check

**Files to Create**:
- `joy-app/app/Http/Middleware/EnsureAuthenticated.php`

**Implementation**:
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\RoleDetectionService;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    public function __construct(private RoleDetectionService $roleDetection) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->roleDetection->getCurrentUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        $request->merge(['authenticated_user' => $user]);
        return $next($request);
    }
}
```

**Acceptance Criteria**:
- [ ] Middleware file created
- [ ] Uses RoleDetectionService
- [ ] Returns 401 for unauthenticated requests
- [ ] Merges user into request
- [ ] T003 tests now PASS

**Dependencies**: T003 (tests written and failing)

---

### T007: Implement ResolveClientAccess Middleware
**Priority**: CRITICAL | **Effort**: S

**Description**: Create middleware to resolve and validate client access

**Files to Create**:
- `joy-app/app/Http/Middleware/ResolveClientAccess.php`

**Implementation Reference**: See quickstart.md Step 1.2

**Acceptance Criteria**:
- [ ] Middleware file created
- [ ] Resolves client from client_id parameter or user
- [ ] Returns 403 for unauthorized access
- [ ] Returns 422 for admin without client_id
- [ ] Merges resolved_client into request
- [ ] T004 tests now PASS

**Dependencies**: T004 (tests written and failing), T008 (needs ClientAccessResolver)

---

### T008: Implement ClientAccessResolver Service
**Priority**: CRITICAL | **Effort**: M

**Description**: Create service to encapsulate client access resolution logic

**Files to Create**:
- `joy-app/app/Services/ClientAccessResolver.php`

**Implementation Reference**: See quickstart.md Step 1.3

**Methods**:
- `resolveClient(?int $clientId, User $user): Client`
- `validateAccess(User $user, Client $client): void`

**Acceptance Criteria**:
- [ ] Service file created
- [ ] resolveClient() handles both client_id and user.client scenarios
- [ ] validateAccess() checks canAccessClient() permission
- [ ] Throws appropriate exceptions
- [ ] T005 tests now PASS

**Dependencies**: T005 (tests written and failing)

---

### T009: Register Middleware in Kernel
**Priority**: CRITICAL | **Effort**: S

**Description**: Register new middleware aliases in HTTP Kernel

**Files to Modify**:
- `joy-app/app/Http/Kernel.php`

**Changes**:
```php
protected $middlewareAliases = [
    // ... existing middleware
    'auth.api' => \App\Http\Middleware\EnsureAuthenticated::class,
    'client.access' => \App\Http\Middleware\ResolveClientAccess::class,
];
```

**Acceptance Criteria**:
- [ ] Both middleware registered with aliases
- [ ] Middleware accessible via route definitions
- [ ] No syntax errors

**Dependencies**: T006, T007

---

### T010: Update API Routes to Use Middleware
**Priority**: CRITICAL | **Effort**: S

**Description**: Apply middleware to API route groups

**Files to Modify**:
- `joy-app/routes/api.php`

**Changes**:
```php
Route::middleware(['auth.api', 'client.access'])->group(function () {
    // Calendar routes
    Route::get('/calendar/month', [CalendarController::class, 'month']);
    Route::get('/calendar/range', [CalendarController::class, 'range']);
    Route::get('/calendar/stats', [CalendarController::class, 'stats']);

    // Content Item routes
    Route::apiResource('content-items', ContentItemController::class);
    Route::put('/content-items/{id}/status', [ContentItemController::class, 'updateStatus']);

    // Comment routes
    Route::apiResource('comments', CommentController::class);
});
```

**Acceptance Criteria**:
- [ ] Middleware applied to all protected routes
- [ ] Route definitions clean and organized
- [ ] No duplicate middleware applications

**Dependencies**: T009

---

### T011: Refactor CalendarController to Use Middleware
**Priority**: CRITICAL | **Effort**: M

**Description**: Remove duplicate auth/client resolution code from CalendarController

**Files to Modify**:
- `joy-app/app/Http/Controllers/CalendarController.php`

**Changes**:
- Remove all `getCurrentUser()` calls
- Remove all `canAccessClient()` checks
- Use `$request->get('authenticated_user')` and `$request->get('resolved_client')`
- Reduce each method from ~70 lines to ~20 lines

**Before/After**: See quickstart.md Step 1.6

**Acceptance Criteria**:
- [ ] All auth logic removed (expect ~70 lines removed)
- [ ] Uses middleware-injected user and client
- [ ] All existing CalendarController tests still pass
- [ ] Methods are <30 lines each

**Dependencies**: T010

---

### T012: [P] Refactor ContentItemController to Use Middleware
**Priority**: CRITICAL | **Effort**: M

**Description**: Remove duplicate auth/client resolution from ContentItemController

**Files to Modify**:
- `joy-app/app/Http/Controllers/ContentItemController.php`

**Acceptance Criteria**:
- [ ] Auth logic removed
- [ ] Uses middleware-injected user and client
- [ ] All existing tests pass
- [ ] ~50 lines removed

**Dependencies**: T010

---

### T013: [P] Refactor CommentController to Use Middleware
**Priority**: CRITICAL | **Effort**: S

**Description**: Remove duplicate auth logic from CommentController

**Files to Modify**:
- `joy-app/app/Http/Controllers/CommentController.php`

**Acceptance Criteria**:
- [ ] Auth logic removed
- [ ] Uses middleware-injected user
- [ ] Tests pass

**Dependencies**: T010

---

### T014: [P] Refactor TrelloController to Use Middleware
**Priority**: CRITICAL | **Effort**: S

**Description**: Remove duplicate auth logic from TrelloController

**Files to Modify**:
- `joy-app/app/Http/Controllers/TrelloController.php`

**Acceptance Criteria**:
- [ ] Auth logic removed
- [ ] Uses middleware-injected user and client
- [ ] Tests pass

**Dependencies**: T010

---

### T015: [P] Refactor MagicLinkController to Use Middleware
**Priority**: CRITICAL | **Effort**: S

**Description**: Remove duplicate auth logic from MagicLinkController

**Files to Modify**:
- `joy-app/app/Http/Controllers/MagicLinkController.php`

**Acceptance Criteria**:
- [ ] Auth logic removed (if applicable - magic links may have different auth)
- [ ] Tests pass

**Dependencies**: T010

---

### T016: Verify Middleware Refactoring Complete
**Priority**: CRITICAL | **Effort**: S

**Description**: Run full test suite and verify middleware refactoring

**Commands**:
```bash
cd joy-app
./vendor/bin/phpunit
./vendor/bin/phpstan analyze --level=6
grep -r "getCurrentUser()" app/Http/Controllers/
```

**Acceptance Criteria**:
- [ ] All tests pass
- [ ] No getCurrentUser() calls in controllers (except admin auth)
- [ ] No canAccessClient() calls in controllers
- [ ] PHPStan level 6 passes
- [ ] ~170 lines of duplicate code eliminated

**Dependencies**: T011, T012, T013, T014, T015

---

## Phase 4: Service Layer Tests (HIGH Priority - TDD Phase)

⚠️ **CRITICAL**: T017-T021 tests MUST FAIL before implementing T022-T026

### T017: [P] Create CalendarService Unit Tests
**Priority**: HIGH | **Effort**: M

**Description**: Write failing tests for CalendarService

**Files to Create**:
- `joy-app/tests/Unit/Services/CalendarServiceTest.php`

**Test Cases**:
```php
- test_get_month_view_returns_content_items_for_month()
- test_get_month_view_groups_items_by_date()
- test_get_range_view_returns_items_in_date_range()
- test_fetch_content_items_eager_loads_relationships()
```

**Acceptance Criteria**:
- [ ] Test file created with 4+ test methods
- [ ] Tests FAIL (service doesn't exist)
- [ ] Uses database factories
- [ ] Tests date range logic

**Dependencies**: T016

---

### T018: [P] Create CalendarStatisticsService Unit Tests
**Priority**: HIGH | **Effort**: M

**Description**: Write failing tests for CalendarStatisticsService

**Files to Create**:
- `joy-app/tests/Unit/Services/CalendarStatisticsServiceTest.php`

**Test Cases**:
```php
- test_get_month_statistics_calculates_completion_rate()
- test_get_completion_rate_handles_no_content()
- test_get_busiest_day_returns_day_with_most_items()
- test_get_platform_distribution_aggregates_correctly()
```

**Acceptance Criteria**:
- [ ] Test file created with 4+ test methods
- [ ] Tests FAIL (service doesn't exist)
- [ ] Tests calculation logic

**Dependencies**: T016

---

### T019: [P] Create ContentItemResource Test
**Priority**: HIGH | **Effort**: S

**Description**: Write test for ContentItemResource transformation

**Files to Create**:
- `joy-app/tests/Unit/Resources/ContentItemResourceTest.php`

**Test Cases**:
```php
- test_resource_transforms_content_item_correctly()
- test_resource_includes_client_when_loaded()
- test_resource_formats_dates_as_iso8601()
```

**Acceptance Criteria**:
- [ ] Test file created
- [ ] Tests FAIL (resource doesn't exist)
- [ ] Validates JSON structure matches contract

**Dependencies**: T016

---

### T020: [P] Create CalendarResource Test
**Priority**: HIGH | **Effort**: S

**Description**: Write test for CalendarResource transformation

**Files to Create**:
- `joy-app/tests/Unit/Resources/CalendarResourceTest.php`

**Acceptance Criteria**:
- [ ] Test file created
- [ ] Tests FAIL (resource doesn't exist)
- [ ] Validates calendar response format

**Dependencies**: T016

---

### T021: [P] Create ClientResource Test
**Priority**: HIGH | **Effort**: S

**Description**: Write test for ClientResource transformation

**Files to Create**:
- `joy-app/tests/Unit/Resources/ClientResourceTest.php`

**Acceptance Criteria**:
- [ ] Test file created
- [ ] Tests FAIL (resource doesn't exist)
- [ ] Tests conditional fields based on user role

**Dependencies**: T016

---

## Phase 5: Service Layer Implementation (HIGH Priority)

⚠️ **Run T017-T021 tests - verify they FAIL before proceeding**

### T022: Implement CalendarService
**Priority**: HIGH | **Effort**: M

**Description**: Create CalendarService to handle calendar business logic

**Files to Create**:
- `joy-app/app/Services/CalendarService.php`

**Implementation Reference**: See quickstart.md Step 2.1

**Methods**:
- `getMonthView(Client $client, int $year, int $month): array`
- `getRangeView(Client $client, Carbon $start, Carbon $end): array`
- `fetchContentItems(Client $client, Carbon $start, Carbon $end): Collection`
- `groupByDate(Collection $items): array`

**Acceptance Criteria**:
- [ ] Service file created
- [ ] All 4 methods implemented
- [ ] Uses Eloquent for data fetching
- [ ] Eager loads relationships
- [ ] T017 tests now PASS

**Dependencies**: T017 (tests written and failing)

---

### T023: Implement CalendarStatisticsService
**Priority**: HIGH | **Effort**: M

**Description**: Create CalendarStatisticsService for metrics calculation

**Files to Create**:
- `joy-app/app/Services/CalendarStatisticsService.php`

**Methods**:
- `getMonthStatistics(Client $client, int $year, int $month): array`
- `getCompletionRate(Client $client, DateRange $range): float`
- `getBusiestDay(Client $client, DateRange $range): Carbon`
- `getPlatformDistribution(Client $client, DateRange $range): array`

**Acceptance Criteria**:
- [ ] Service file created
- [ ] All statistical calculations working
- [ ] T018 tests now PASS

**Dependencies**: T018 (tests written and failing)

---

### T024: [P] Implement ContentItemResource
**Priority**: HIGH | **Effort**: S

**Description**: Create API Resource for consistent ContentItem responses

**Files to Create**:
- `joy-app/app/Http/Resources/ContentItemResource.php`

**Implementation Reference**: See quickstart.md Step 2.3

**Acceptance Criteria**:
- [ ] Resource file created
- [ ] Transforms all ContentItem fields
- [ ] Uses ISO8601 date formatting
- [ ] Conditionally loads relationships
- [ ] T019 tests now PASS

**Dependencies**: T019 (tests written and failing)

---

### T025: [P] Implement CalendarResource
**Priority**: HIGH | **Effort**: S

**Description**: Create API Resource for calendar responses

**Files to Create**:
- `joy-app/app/Http/Resources/CalendarResource.php`

**Acceptance Criteria**:
- [ ] Resource file created
- [ ] Formats calendar data per contract
- [ ] Uses ContentItemResource for items
- [ ] T020 tests now PASS

**Dependencies**: T020, T024

---

### T026: [P] Implement ClientResource
**Priority**: HIGH | **Effort**: S

**Description**: Create API Resource for Client responses

**Files to Create**:
- `joy-app/app/Http/Resources/ClientResource.php`

**Acceptance Criteria**:
- [ ] Resource file created
- [ ] Conditionally shows admin-only fields
- [ ] T021 tests now PASS

**Dependencies**: T021

---

### T027: Refactor CalendarController to Use Services
**Priority**: HIGH | **Effort**: M

**Description**: Refactor CalendarController to use CalendarService and resources

**Files to Modify**:
- `joy-app/app/Http/Controllers/CalendarController.php`

**Implementation Reference**: See quickstart.md Step 2.4

**Changes**:
- Inject CalendarService and CalendarStatisticsService
- Move business logic to services
- Use API Resources for responses
- Reduce from ~486 lines to ~60 lines

**Acceptance Criteria**:
- [ ] Constructor injection of services
- [ ] Business logic moved to services
- [ ] Responses use API Resources
- [ ] Controller is <80 lines total
- [ ] All existing tests pass

**Dependencies**: T022, T023, T024, T025

---

### T028: Create CalendarController Integration Tests
**Priority**: HIGH | **Effort**: M

**Description**: Create integration tests for refactored CalendarController

**Files to Create**:
- `joy-app/tests/Feature/CalendarControllerTest.php`

**Test Cases**:
```php
- test_month_endpoint_returns_calendar_data()
- test_month_endpoint_validates_year_and_month()
- test_range_endpoint_returns_date_range_data()
- test_stats_endpoint_returns_statistics()
- test_middleware_enforces_authentication()
- test_middleware_resolves_client_access()
```

**Acceptance Criteria**:
- [ ] Integration test file created
- [ ] Tests full request/response cycle
- [ ] Tests middleware integration
- [ ] All tests pass

**Dependencies**: T027

---

### T029: Refactor ContentItemController to Use Services
**Priority**: HIGH | **Effort**: M

**Description**: Refactor ContentItemController to use services and resources

**Files to Modify**:
- `joy-app/app/Http/Controllers/ContentItemController.php`

**Changes**:
- Use ContentItemResource for responses
- Move validation to Form Requests (to be created in next phase)
- Reduce from ~308 lines to ~80 lines

**Acceptance Criteria**:
- [ ] Uses ContentItemResource
- [ ] Response formatting consistent
- [ ] Controller is <100 lines
- [ ] All tests pass

**Dependencies**: T024

---

## Phase 6: Form Requests & Status Management (HIGH Priority)

### T030: [P] Create StoreContentItemRequest
**Priority**: HIGH | **Effort**: S

**Description**: Create Form Request for content item creation validation

**Files to Create**:
- `joy-app/app/Http/Requests/StoreContentItemRequest.php`

**Validation Rules**:
```php
'title' => 'required|string|max:255',
'description' => 'nullable|string',
'scheduled_date' => 'required|date',
'platform' => 'required|in:facebook,instagram,linkedin,blog',
'client_id' => 'required|exists:clients,id',
```

**Acceptance Criteria**:
- [ ] Form Request created
- [ ] Validation rules match contract
- [ ] Authorization logic included
- [ ] Custom error messages provided

**Dependencies**: T029

---

### T031: [P] Create UpdateContentItemRequest
**Priority**: HIGH | **Effort**: S

**Description**: Create Form Request for content item updates

**Files to Create**:
- `joy-app/app/Http/Requests/UpdateContentItemRequest.php`

**Acceptance Criteria**:
- [ ] Form Request created
- [ ] All fields optional (sometimes rules)
- [ ] Authorization logic included

**Dependencies**: T029

---

### T032: [P] Create UpdateStatusRequest
**Priority**: HIGH | **Effort**: M

**Description**: Create Form Request for status change validation

**Files to Create**:
- `joy-app/app/Http/Requests/UpdateStatusRequest.php`

**Validation Rules**:
```php
'status' => 'required|in:draft,pending_review,approved,rejected,published',
'reason' => 'required_if:status,rejected|string|max:500',
```

**Custom Validation**: Check valid status transition using ContentItemStatusManager

**Acceptance Criteria**:
- [ ] Form Request created
- [ ] Status enum validated
- [ ] Reason required for rejection
- [ ] Custom validation for transitions

**Dependencies**: T029

---

### T033: Create ContentItemStatusManager Test
**Priority**: HIGH | **Effort**: M

**Description**: Write failing tests for status transition management

**Files to Create**:
- `joy-app/tests/Unit/Services/ContentItemStatusManagerTest.php`

**Test Cases**:
```php
- test_can_transition_from_draft_to_pending_review()
- test_cannot_transition_from_draft_to_published()
- test_only_admin_can_approve()
- test_client_cannot_reject()
- test_get_available_statuses_respects_role()
```

**Acceptance Criteria**:
- [ ] Test file created with 8+ test cases
- [ ] Tests all transition rules from data-model.md
- [ ] Tests FAIL (service doesn't exist)

**Dependencies**: T029

---

### T034: Implement ContentItemStatusManager
**Priority**: HIGH | **Effort**: M

**Description**: Create service to manage status transitions

**Files to Create**:
- `joy-app/app/Services/ContentItemStatusManager.php`

**Methods**:
- `canTransition(ContentItem $item, string $newStatus, User $user): bool`
- `validateTransition(ContentItem $item, string $newStatus): void`
- `executeTransition(ContentItem $item, string $newStatus, User $user): ContentItem`
- `getAvailableStatuses(ContentItem $item, User $user): array`

**Business Rules**: See data-model.md status transitions

**Acceptance Criteria**:
- [ ] Service file created
- [ ] All transition rules enforced
- [ ] Role-based permissions checked
- [ ] T033 tests now PASS

**Dependencies**: T033 (tests written and failing)

---

### T035: Enhance ContentItemService with CRUD Methods
**Priority**: HIGH | **Effort**: M

**Description**: Add missing CRUD methods to ContentItemService

**Files to Modify**:
- `joy-app/app/Services/ContentItemService.php`

**Methods to Add**:
```php
public function create(Client $client, array $data): ContentItem
public function update(ContentItem $item, array $data): ContentItem
public function delete(ContentItem $item): bool
public function changeStatus(ContentItem $item, string $newStatus, User $user): ContentItem
public function bulkUpdateStatus(array $itemIds, string $status, User $user): Collection
```

**Acceptance Criteria**:
- [ ] All 5 methods added
- [ ] Uses ContentItemStatusManager for status changes
- [ ] Uses AuditService for logging
- [ ] Existing tests still pass

**Dependencies**: T034

---

### T036: Create ContentItemRepository Test
**Priority**: HIGH | **Effort**: M

**Description**: Write tests for repository pattern implementation

**Files to Create**:
- `joy-app/tests/Unit/Repositories/ContentItemRepositoryTest.php`

**Test Cases**:
```php
- test_find_by_date_range()
- test_get_statistics()
- test_eager_loads_relationships()
```

**Acceptance Criteria**:
- [ ] Test file created
- [ ] Tests FAIL (repository doesn't exist)

**Dependencies**: T035

---

### T037: Implement ContentItemRepository
**Priority**: HIGH | **Effort**: M

**Description**: Create repository for complex ContentItem queries

**Files to Create**:
- `joy-app/app/Repositories/ContentItemRepository.php`
- `joy-app/app/Repositories/Contracts/ContentItemRepositoryInterface.php`

**Methods**:
- `findByDateRange(Client $client, DateRange $range): Collection`
- `getStatistics(Client $client, DateRange $range): array`

**Acceptance Criteria**:
- [ ] Repository and interface created
- [ ] Registered in RepositoryServiceProvider
- [ ] Encapsulates complex queries
- [ ] T036 tests now PASS

**Dependencies**: T036 (tests written and failing)

---

### T038: Update Controllers to Use Enhanced Services
**Priority**: HIGH | **Effort**: M

**Description**: Update controllers to use completed service layer

**Files to Modify**:
- `joy-app/app/Http/Controllers/ContentItemController.php`
- `joy-app/app/Http/Controllers/CalendarController.php`

**Changes**:
- Replace direct model access with service calls
- Use Form Requests for validation
- Consistent error handling

**Acceptance Criteria**:
- [ ] All controllers use services
- [ ] No direct Eloquent queries in controllers
- [ ] Form Requests used for validation
- [ ] All tests pass

**Dependencies**: T030, T031, T032, T035, T037

---

## Phase 7: Trello Service Refactoring (MEDIUM Priority)

### T039: [P] Create TrelloApiClient Test
**Priority**: MEDIUM | **Effort**: M

**Description**: Write tests for HTTP client abstraction

**Files to Create**:
- `joy-app/tests/Unit/Services/TrelloApiClientTest.php`

**Acceptance Criteria**:
- [ ] Test file created
- [ ] Tests HTTP communication
- [ ] Tests FAIL

**Dependencies**: T038

---

### T040: [P] Create TrelloCardService Test
**Priority**: MEDIUM | **Effort**: M

**Description**: Write tests for Trello card operations

**Files to Create**:
- `joy-app/tests/Unit/Services/TrelloCardServiceTest.php`

**Acceptance Criteria**:
- [ ] Test file created
- [ ] Tests card CRUD operations
- [ ] Tests FAIL

**Dependencies**: T038

---

### T041: [P] Create TrelloWebhookService Test
**Priority**: MEDIUM | **Effort**: M

**Description**: Write tests for webhook management

**Files to Create**:
- `joy-app/tests/Unit/Services/TrelloWebhookServiceTest.php`

**Acceptance Criteria**:
- [ ] Test file created
- [ ] Tests webhook registration/validation
- [ ] Tests FAIL

**Dependencies**: T038

---

### T042: [P] Create TrelloSyncService Test
**Priority**: MEDIUM | **Effort**: M

**Description**: Write tests for comment synchronization

**Files to Create**:
- `joy-app/tests/Unit/Services/TrelloSyncServiceTest.php`

**Acceptance Criteria**:
- [ ] Test file created
- [ ] Tests bidirectional sync
- [ ] Tests FAIL

**Dependencies**: T038

---

### T043: Implement TrelloApiClient
**Priority**: MEDIUM | **Effort**: M

**Description**: Extract HTTP communication to focused service

**Files to Create**:
- `joy-app/app/Services/TrelloApiClient.php`

**Methods**:
- `testConnection(): array`
- `createCard(array $data): array`
- `addComment(string $cardId, string $text): array`
- `registerWebhook(string $callbackUrl): array`

**Acceptance Criteria**:
- [ ] Service created
- [ ] Handles HTTP errors
- [ ] T039 tests now PASS

**Dependencies**: T039

---

### T044: Implement TrelloCardService
**Priority**: MEDIUM | **Effort**: M

**Description**: Service for Trello card management

**Files to Create**:
- `joy-app/app/Services/TrelloCardService.php`

**Acceptance Criteria**:
- [ ] Service created
- [ ] Uses TrelloApiClient
- [ ] T040 tests now PASS

**Dependencies**: T040, T043

---

### T045: Implement TrelloWebhookService
**Priority**: MEDIUM | **Effort**: M

**Description**: Service for webhook operations

**Files to Create**:
- `joy-app/app/Services/TrelloWebhookService.php`

**Acceptance Criteria**:
- [ ] Service created
- [ ] Uses TrelloApiClient
- [ ] T041 tests now PASS

**Dependencies**: T041, T043

---

### T046: Implement TrelloSyncService
**Priority**: MEDIUM | **Effort**: M

**Description**: Service for comment synchronization

**Files to Create**:
- `joy-app/app/Services/TrelloSyncService.php`

**Acceptance Criteria**:
- [ ] Service created
- [ ] Handles sync errors gracefully
- [ ] T042 tests now PASS

**Dependencies**: T042, T044

---

### T047: Refactor Existing TrelloService to Use New Services
**Priority**: MEDIUM | **Effort**: L

**Description**: Update TrelloService to delegate to focused services

**Files to Modify**:
- `joy-app/app/Services/TrelloService.php`

**Changes**:
- Replace direct HTTP calls with TrelloApiClient
- Delegate card operations to TrelloCardService
- Use TrelloWebhookService and TrelloSyncService
- Reduce from 335 lines to ~100 lines (orchestration only)

**Acceptance Criteria**:
- [ ] TrelloService is now a facade/orchestrator
- [ ] All logic delegated to focused services
- [ ] Existing tests still pass
- [ ] Service is <150 lines

**Dependencies**: T044, T045, T046

---

### T048: Update TrelloController to Use Refactored Services
**Priority**: MEDIUM | **Effort**: S

**Description**: Update controller to use new service structure

**Files to Modify**:
- `joy-app/app/Http/Controllers/TrelloController.php`

**Acceptance Criteria**:
- [ ] Uses focused services
- [ ] Cleaner dependency injection
- [ ] All tests pass

**Dependencies**: T047

---

## Phase 8: Final Cleanup (LOW Priority)

### T049: [P] Rename Generic Variables - Controllers
**Priority**: LOW | **Effort**: M

**Description**: Replace generic variable names with descriptive names in controllers

**Files to Modify**: All controllers

**Changes**:
- `$data` → `$validatedInput`, `$contentItemData`, `$calendarData`
- `$result` → `$connectionTestResult`, `$syncOutcome`
- `$info` → specific descriptive names

**Commands**:
```bash
grep -r "\$data" joy-app/app/Http/Controllers/
# Manual refactor each occurrence
```

**Acceptance Criteria**:
- [ ] No generic `$data` variables in controllers
- [ ] No `$result` variables without context
- [ ] All tests still pass

**Dependencies**: T048

---

### T050: [P] Replace Magic Numbers with Response Constants
**Priority**: LOW | **Effort**: S

**Description**: Use Symfony Response constants for HTTP status codes

**Files to Modify**: All controllers and middleware

**Changes**:
```php
// Before: return response()->json(['error' => 'Forbidden'], 403);
// After:
use Symfony\Component\HttpFoundation\Response;
return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
```

**Acceptance Criteria**:
- [ ] No numeric HTTP status codes
- [ ] All use Response::HTTP_* constants
- [ ] Tests still pass

**Dependencies**: T048

---

### T051: [P] Fix Misleading Method Names
**Priority**: LOW | **Effort**: S

**Description**: Rename methods that don't match their return types

**Files to Modify**:
- `joy-app/app/Services/RoleDetectionService.php`

**Changes**:
- Rename `getCurrentUserRole()` to `getCurrentUser()` (returns User, not role)
- Add new `getCurrentUserRole()` that returns string

**Acceptance Criteria**:
- [ ] Method names match return types
- [ ] All callers updated
- [ ] Tests pass

**Dependencies**: T048

---

### T052: Achieve PHPStan Level 8
**Priority**: LOW | **Effort**: L

**Description**: Fix all static analysis issues to reach level 8

**Commands**:
```bash
cd joy-app
./vendor/bin/phpstan analyze --level=6
# Fix issues
./vendor/bin/phpstan analyze --level=7
# Fix issues
./vendor/bin/phpstan analyze --level=8
```

**Acceptance Criteria**:
- [ ] PHPStan level 8 passes with 0 errors
- [ ] Type hints added where missing
- [ ] DocBlocks complete and accurate

**Dependencies**: T049, T050, T051

---

### T053: Run Laravel Pint for Code Formatting
**Priority**: LOW | **Effort**: S

**Description**: Auto-format all code to Laravel standards

**Commands**:
```bash
cd joy-app
./vendor/bin/pint
```

**Acceptance Criteria**:
- [ ] All code formatted consistently
- [ ] Pint reports 0 style violations
- [ ] Git diff shows only formatting changes

**Dependencies**: T052

---

### T054: Generate Final Coverage Report
**Priority**: LOW | **Effort**: S

**Description**: Generate final test coverage report and verify 80%+ target

**Commands**:
```bash
cd joy-app
./vendor/bin/phpunit --coverage-html ../specs/001-clean-code-refactoring/coverage-final
./vendor/bin/phpunit --coverage-text
```

**Acceptance Criteria**:
- [ ] Coverage report generated
- [ ] Overall coverage ≥80%
- [ ] Refactored code coverage ≥85%
- [ ] Report compared with baseline

**Dependencies**: T053

---

### T055: Compare Before/After Metrics
**Priority**: LOW | **Effort**: S

**Description**: Generate comparison report of code quality improvements

**Commands**:
```bash
cd joy-app

# Current metrics
./vendor/bin/phpstan analyze --level=8 > ../specs/001-clean-code-refactoring/final-phpstan.txt
cloc app/ > ../specs/001-clean-code-refactoring/final-loc.txt

# Compare
diff ../specs/001-clean-code-refactoring/baseline-phpstan.txt ../specs/001-clean-code-refactoring/final-phpstan.txt
diff ../specs/001-clean-code-refactoring/baseline-loc.txt ../specs/001-clean-code-refactoring/final-loc.txt
```

**Acceptance Criteria**:
- [ ] Final metrics captured
- [ ] Comparison document created
- [ ] All target metrics achieved:
  - [ ] Avg function length <10 lines
  - [ ] Code duplication <10 blocks
  - [ ] Test coverage ≥80%
  - [ ] PHPStan level 8
  - [ ] Controller LOC <100 lines avg

**Dependencies**: T054

---

## Dependencies Summary

```
Setup Phase:
T001, T002 → Independent (run in parallel)

Middleware Phase (CRITICAL):
T003, T004, T005 → Independent tests [P]
T006 → T003
T007 → T004, T008
T008 → T005
T009 → T006, T007
T010 → T009
T011 → T010
T012, T013, T014, T015 → T010 [P]
T016 → T011, T012, T013, T014, T015

Service Layer Phase (HIGH):
T017, T018, T019, T020, T021 → T016 [P]
T022 → T017
T023 → T018
T024, T025, T026 → T019, T020, T021 [P]
T027 → T022, T023, T024, T025
T028 → T027
T029 → T024

Form Requests Phase (HIGH):
T030, T031, T032 → T029 [P]
T033 → T029
T034 → T033
T035 → T034
T036 → T035
T037 → T036
T038 → T030, T031, T032, T035, T037

Trello Phase (MEDIUM):
T039, T040, T041, T042 → T038 [P]
T043 → T039
T044 → T040, T043
T045 → T041, T043
T046 → T042, T044
T047 → T044, T045, T046
T048 → T047

Cleanup Phase (LOW):
T049, T050, T051 → T048 [P]
T052 → T049, T050, T051
T053 → T052
T054 → T053
T055 → T054
```

## Parallel Execution Examples

### Example 1: Run All Middleware Tests in Parallel
```bash
# After T002 complete, launch T003-T005 together:
# Each in separate terminal or using background jobs

# Terminal 1:
./vendor/bin/phpunit --filter=EnsureAuthenticatedTest

# Terminal 2:
./vendor/bin/phpunit --filter=ResolveClientAccessTest

# Terminal 3:
./vendor/bin/phpunit --filter=ClientAccessResolverTest
```

### Example 2: Refactor Multiple Controllers in Parallel
```bash
# After T010 complete, refactor controllers T012-T015 in parallel:
# These are independent files with no shared dependencies

# Can be done by multiple developers simultaneously or sequentially
```

### Example 3: Create All API Resources in Parallel
```bash
# After T019-T021 tests written, implement T024-T026 together:
# Each resource is an independent file

# Terminal 1: Implement ContentItemResource
# Terminal 2: Implement CalendarResource
# Terminal 3: Implement ClientResource
```

## Validation Checklist

Before marking refactoring complete, verify:

- [ ] All 55 tasks completed
- [ ] All tests passing (Unit, Feature, Integration, E2E)
- [ ] Code quality metrics achieved:
  - [ ] Avg function length <10 lines
  - [ ] Code duplication eliminated (170+ → <10 occurrences)
  - [ ] Test coverage ≥80%
  - [ ] PHPStan level 8 passes
  - [ ] Controllers avg <100 lines
- [ ] Performance maintained (±5% response time)
- [ ] API contracts validated
- [ ] Documentation updated
- [ ] Team trained on new patterns

## Notes

1. **Test-First Mandatory**: Never implement before writing failing tests
2. **Commit Frequently**: After each task completion
3. **Run Tests Often**: After every implementation task
4. **Code Review**: Each phase should be reviewed before proceeding
5. **Performance Monitoring**: Use Laravel Telescope to track performance
6. **Rollback Plan**: Each phase can be rolled back independently if issues arise

---

**Total Tasks**: 55
**Estimated Timeline**: 5 weeks (1 week per major phase)
**Parallel Opportunities**: 15 tasks can run in parallel (marked [P])
