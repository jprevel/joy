# Data Model: Clean Code Refactoring

## Overview
This document defines the data structures and service contracts for the refactored codebase. The refactoring focuses on extracting business logic into services while maintaining the existing database schema.

## Core Entities (Existing - No Schema Changes)

### 1. ContentItem
**Purpose**: Represents a piece of content in the calendar
**Relationships**:
- Belongs to Client
- Has many Comments
- May have TrelloCard

**Key Attributes**:
- `id`: Primary key
- `client_id`: Foreign key to clients
- `title`: Content title
- `description`: Content description
- `scheduled_date`: Publication date
- `status`: Enum (draft, pending_review, approved, published, rejected)
- `platform`: Platform identifier (facebook, instagram, linkedin, blog)

**Business Rules**:
- Status transitions must be validated (draft → pending_review → approved/rejected)
- Only agency/admin can change to approved/rejected
- Client can only comment, not approve

### 2. Client
**Purpose**: Represents a client account
**Relationships**:
- Has many ContentItems
- Has many Users (via client_id)
- Has many MagicLinks

**Key Attributes**:
- `id`: Primary key
- `name`: Client name
- `slug`: URL-friendly identifier
- `active`: Boolean status

### 3. User
**Purpose**: System user with role-based access
**Relationships**:
- May belong to Client (if role=client)
- Creates AuditLogs

**Key Attributes**:
- `id`: Primary key
- `client_id`: Nullable foreign key
- `name`: User name
- `email`: Unique email
- `role`: Enum (admin, agency, client)

### 4. Comment
**Purpose**: Feedback on content items
**Relationships**:
- Belongs to ContentItem
- Belongs to User

**Key Attributes**:
- `id`: Primary key
- `content_item_id`: Foreign key
- `user_id`: Foreign key
- `comment`: Text content
- `created_at`: Timestamp

### 5. MagicLink
**Purpose**: Temporary access links for clients
**Relationships**:
- Belongs to Client

**Key Attributes**:
- `id`: Primary key
- `client_id`: Foreign key
- `token`: Unique access token
- `expires_at`: Expiration timestamp

## Service Layer Design

### 1. CalendarService
**Purpose**: Business logic for calendar operations
**Responsibilities**:
- Build date ranges for month/week/day views
- Fetch content items for given date range
- Group content by date/week
- Filter content by client access

**Methods**:
```php
public function getMonthView(Client $client, Carbon $month): CalendarMonth
public function getRangeView(Client $client, Carbon $start, Carbon $end): CalendarRange
public function getDayView(Client $client, Carbon $day): CalendarDay
public function getWeekView(Client $client, Carbon $week): CalendarWeek
```

**Dependencies**:
- ContentItemRepository
- ClientAccessValidator

### 2. CalendarStatisticsService
**Purpose**: Calculate calendar statistics and metrics
**Responsibilities**:
- Calculate completion rates
- Identify busiest days/weeks
- Aggregate content by status
- Platform distribution analysis

**Methods**:
```php
public function getMonthStatistics(Client $client, Carbon $month): CalendarStatistics
public function getCompletionRate(Client $client, DateRange $range): float
public function getBusiestDay(Client $client, DateRange $range): Carbon
public function getPlatformDistribution(Client $client, DateRange $range): array
```

**Dependencies**:
- ContentItemRepository

### 3. ContentItemService (Enhanced)
**Purpose**: Business logic for content item operations
**Current State**: Partially implemented, needs completion
**Responsibilities**:
- CRUD operations for content items
- Status transition management
- Bulk operations
- Statistics aggregation

**Methods to Add**:
```php
public function create(Client $client, array $data): ContentItem
public function update(ContentItem $item, array $data): ContentItem
public function delete(ContentItem $item): bool
public function changeStatus(ContentItem $item, string $newStatus, User $user): ContentItem
public function bulkUpdateStatus(array $itemIds, string $status, User $user): Collection
public function getClientStatistics(Client $client): array
```

**Dependencies**:
- ContentItemRepository
- StatusTransitionValidator
- AuditService

### 4. ContentItemStatusManager
**Purpose**: Manage status transitions and validation
**Responsibilities**:
- Validate status transitions based on user role
- Enforce business rules for status changes
- Record status history

**Methods**:
```php
public function canTransition(ContentItem $item, string $newStatus, User $user): bool
public function validateTransition(ContentItem $item, string $newStatus): void
public function executeTransition(ContentItem $item, string $newStatus, User $user): ContentItem
public function getAvailableStatuses(ContentItem $item, User $user): array
```

**Business Rules**:
```
draft → pending_review (any role)
pending_review → approved (admin/agency only)
pending_review → rejected (admin/agency only)
pending_review → draft (any role)
approved → published (admin/agency only)
rejected → draft (any role)
```

### 5. TrelloCardService
**Purpose**: Trello card management
**Responsibilities**:
- Create cards for content items
- Update card information
- Sync card status

**Methods**:
```php
public function createCard(ContentItem $item): TrelloCard
public function updateCard(TrelloCard $card, array $data): TrelloCard
public function syncStatus(TrelloCard $card, string $status): void
public function deleteCard(TrelloCard $card): bool
```

**Dependencies**:
- TrelloApiClient

### 6. TrelloWebhookService
**Purpose**: Webhook registration and management
**Responsibilities**:
- Register webhooks with Trello
- Validate webhook payloads
- Process webhook events

**Methods**:
```php
public function registerWebhook(string $callbackUrl): string
public function validatePayload(Request $request): bool
public function processWebhook(array $payload): void
public function deleteWebhook(string $webhookId): bool
```

**Dependencies**:
- TrelloApiClient
- CommentService

### 7. TrelloSyncService
**Purpose**: Synchronize comments between Joy and Trello
**Responsibilities**:
- Sync comments from Joy to Trello
- Process incoming Trello comments
- Handle sync failures

**Methods**:
```php
public function syncCommentToTrello(Comment $comment): void
public function syncCommentFromTrello(array $trelloComment): Comment
public function retryFailedSync(Comment $comment): void
```

**Dependencies**:
- TrelloCardService
- CommentService
- TrelloApiClient

### 8. ClientAccessResolver
**Purpose**: Resolve and validate client access
**Responsibilities**:
- Determine which client the user is accessing
- Validate user has permission to access client
- Handle both authenticated users and magic links

**Methods**:
```php
public function resolveClient(?int $clientId, User $user): Client
public function validateAccess(User $user, Client $client): void
public function resolveFromRequest(Request $request): Client
```

**Dependencies**:
- RoleDetectionService

### 9. CommentService
**Purpose**: Comment management and notification
**Responsibilities**:
- Create comments
- Notify relevant users
- Sync to Trello

**Methods**:
```php
public function create(ContentItem $item, User $user, string $text): Comment
public function update(Comment $comment, string $text): Comment
public function delete(Comment $comment): bool
public function notifyStakeholders(Comment $comment): void
```

**Dependencies**:
- TrelloSyncService
- NotificationService

## Value Objects

### 1. DateRange
**Purpose**: Encapsulate date range logic
```php
class DateRange {
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end
    ) {}

    public function contains(Carbon $date): bool
    public function overlap(DateRange $other): bool
    public function days(): int
}
```

### 2. CalendarMonth
**Purpose**: Represent a month of content
```php
class CalendarMonth {
    public function __construct(
        public readonly Carbon $month,
        public readonly Collection $contentItems,
        public readonly array $groupedByDate
    ) {}
}
```

### 3. CalendarStatistics
**Purpose**: Encapsulate statistics data
```php
class CalendarStatistics {
    public function __construct(
        public readonly float $completionRate,
        public readonly Carbon $busiestDay,
        public readonly array $platformDistribution,
        public readonly array $statusBreakdown
    ) {}
}
```

### 4. StatusTransition
**Purpose**: Represent a status change
```php
class StatusTransition {
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly User $actor,
        public readonly Carbon $occurredAt
    ) {}
}
```

## Middleware

### 1. EnsureAuthenticated
**Purpose**: Validate user is authenticated
**Responsibility**: Replace duplicate auth checks in controllers
**Logic**:
```php
public function handle(Request $request, Closure $next) {
    $user = $this->roleDetection->getCurrentUser();
    if (!$user) {
        throw new UnauthenticatedException();
    }
    $request->merge(['authenticated_user' => $user]);
    return $next($request);
}
```

### 2. ResolveClientAccess
**Purpose**: Resolve and validate client access
**Responsibility**: Replace duplicate client access logic
**Logic**:
```php
public function handle(Request $request, Closure $next) {
    $user = $request->get('authenticated_user');
    $clientId = $request->input('client_id') ?? $request->route('client');

    $client = $this->clientResolver->resolveClient($clientId, $user);
    $this->clientResolver->validateAccess($user, $client);

    $request->merge(['resolved_client' => $client]);
    return $next($request);
}
```

## API Resources (Response Formatting)

### 1. ContentItemResource
**Purpose**: Consistent API response for content items
```php
public function toArray($request) {
    return [
        'id' => $this->id,
        'title' => $this->title,
        'description' => $this->description,
        'scheduled_date' => $this->scheduled_date->toIso8601String(),
        'status' => $this->status,
        'platform' => $this->platform,
        'client' => new ClientResource($this->whenLoaded('client')),
        'comments_count' => $this->comments_count ?? $this->comments->count(),
        'created_at' => $this->created_at->toIso8601String(),
    ];
}
```

### 2. CalendarResource
**Purpose**: Format calendar data consistently
```php
public function toArray($request) {
    return [
        'month' => $this->month->format('Y-m'),
        'content_items' => ContentItemResource::collection($this->contentItems),
        'grouped_by_date' => $this->groupedByDate,
        'statistics' => new CalendarStatisticsResource($this->statistics),
    ];
}
```

### 3. ClientResource
**Purpose**: Client data formatting
```php
public function toArray($request) {
    return [
        'id' => $this->id,
        'name' => $this->name,
        'slug' => $this->slug,
        'active' => $this->active,
        'content_items_count' => $this->when($request->user()->isAdmin(),
            $this->content_items_count
        ),
    ];
}
```

## Form Requests (Validation)

### 1. StoreContentItemRequest
**Purpose**: Validate content item creation
```php
public function rules() {
    return [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'scheduled_date' => 'required|date',
        'platform' => 'required|in:facebook,instagram,linkedin,blog',
        'client_id' => 'required|exists:clients,id',
    ];
}

public function authorize() {
    return $this->user()->can('create', ContentItem::class);
}
```

### 2. UpdateContentItemRequest
**Purpose**: Validate content item updates
```php
public function rules() {
    return [
        'title' => 'sometimes|string|max:255',
        'description' => 'nullable|string',
        'scheduled_date' => 'sometimes|date',
        'platform' => 'sometimes|in:facebook,instagram,linkedin,blog',
    ];
}
```

### 3. UpdateStatusRequest
**Purpose**: Validate status changes
```php
public function rules() {
    return [
        'status' => [
            'required',
            'string',
            Rule::in(['draft', 'pending_review', 'approved', 'rejected', 'published'])
        ],
        'reason' => 'required_if:status,rejected|string|max:500',
    ];
}

public function withValidator($validator) {
    $validator->after(function ($validator) {
        $item = $this->route('content_item');
        if (!app(ContentItemStatusManager::class)->canTransition($item, $this->status, $this->user())) {
            $validator->errors()->add('status', 'Invalid status transition');
        }
    });
}
```

## Repositories (Data Access Layer)

### 1. ContentItemRepository
**Purpose**: Encapsulate database queries for content items
```php
interface ContentItemRepositoryInterface {
    public function findById(int $id): ?ContentItem;
    public function findByClient(Client $client): Collection;
    public function findByDateRange(Client $client, DateRange $range): Collection;
    public function create(array $data): ContentItem;
    public function update(ContentItem $item, array $data): ContentItem;
    public function delete(ContentItem $item): bool;
    public function getStatistics(Client $client, DateRange $range): array;
}
```

### 2. ClientRepository (if needed)
**Purpose**: Client-specific queries
```php
interface ClientRepositoryInterface {
    public function findById(int $id): ?Client;
    public function findBySlug(string $slug): ?Client;
    public function findAll(): Collection;
    public function findActive(): Collection;
}
```

## State Transitions

### Content Item Status Flow
```
[draft]
  ↓ (any role)
[pending_review]
  ↓ (admin/agency only)        ↓ (admin/agency only)
[approved]                    [rejected]
  ↓ (admin/agency only)        ↓ (any role)
[published]                   [draft]
```

**Validation Rules**:
- Client role can only move draft → pending_review
- Admin/Agency can perform all transitions
- Rejected items can only go back to draft
- Published items cannot change status (terminal state)

## Error Handling

### Custom Exceptions
```php
// Domain Exceptions
class ContentItemException extends DomainException {}
class InvalidStatusTransitionException extends ContentItemException {}
class UnauthorizedStatusChangeException extends ContentItemException {}

// Infrastructure Exceptions
class TrelloApiException extends RuntimeException {}
class TrelloSyncFailedException extends TrelloApiException {}

// Access Exceptions
class ClientAccessException extends AccessDeniedException {}
class UnauthenticatedException extends AuthenticationException {}
```

### Exception Handling Strategy
- Domain exceptions: Return 422 with validation error details
- Access exceptions: Return 403 with minimal information
- Infrastructure exceptions: Return 500, log full details, show generic message
- Authentication exceptions: Return 401

## Dependency Injection

### Service Provider Registration
```php
// App\Providers\RepositoryServiceProvider
$this->app->bind(ContentItemRepositoryInterface::class, ContentItemRepository::class);

// App\Providers\AppServiceProvider
$this->app->singleton(CalendarService::class);
$this->app->singleton(CalendarStatisticsService::class);
$this->app->singleton(ContentItemStatusManager::class);
$this->app->singleton(ClientAccessResolver::class);
$this->app->singleton(TrelloApiClient::class);
```

### Controller Dependencies
```php
class CalendarController extends Controller {
    public function __construct(
        private CalendarService $calendarService,
        private CalendarStatisticsService $statsService
    ) {}
}
```

## Data Flow Examples

### Example 1: Get Month Calendar
```
1. Request → Route → Middleware (EnsureAuthenticated)
2. Middleware (ResolveClientAccess) → resolve client
3. Controller → CalendarService::getMonthView($client, $month)
4. CalendarService → ContentItemRepository::findByDateRange()
5. CalendarService → group items, build CalendarMonth object
6. Controller → CalendarResource::make($calendarMonth)
7. Response ← JSON
```

### Example 2: Update Content Status
```
1. Request → Route → Middleware (EnsureAuthenticated)
2. Middleware (ResolveClientAccess)
3. FormRequest (UpdateStatusRequest) → validate
4. Controller → ContentItemService::changeStatus($item, $status, $user)
5. ContentItemService → StatusManager::validateTransition()
6. ContentItemService → StatusManager::executeTransition()
7. ContentItemService → AuditService::log()
8. Controller → ContentItemResource::make($item)
9. Response ← JSON
```

### Example 3: Create Comment with Trello Sync
```
1. Request → Controller → CommentService::create($item, $user, $text)
2. CommentService → create Comment record
3. CommentService → TrelloSyncService::syncCommentToTrello($comment)
4. TrelloSyncService → TrelloCardService::getOrCreateCard($item)
5. TrelloSyncService → TrelloApiClient::addComment($card, $text)
6. CommentService → notifyStakeholders($comment)
7. Response ← CommentResource
```

## Testing Considerations

### Unit Tests (Service Layer)
- Test business logic in isolation
- Mock repositories and external dependencies
- Verify state transitions, calculations, validations

### Integration Tests (Service + Repository)
- Test with real database
- Verify data persistence
- Test complex queries

### Feature Tests (Full Request/Response)
- Test HTTP endpoints
- Verify middleware execution
- Test authentication/authorization flows

### Contract Tests (API Responses)
- Verify API Resource output structure
- Ensure backward compatibility
- Validate against OpenAPI schema
