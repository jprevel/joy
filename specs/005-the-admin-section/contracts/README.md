# Service Contracts: Admin Section Refresh

**Feature**: 005-the-admin-section
**Phase**: 1 (Design)
**Date**: 2025-10-11

## Overview

This directory contains service contract interfaces that define the behavior of admin section services. These contracts ensure consistent API design and enable dependency injection for testability.

## Contracts

### 1. UserManagementContract.php

**Purpose**: Defines CRUD operations for User management with soft delete support.

**Key Methods**:
- `listUsers()`: Paginated user listing with trashed inclusion
- `createUser()`: User creation with role assignment
- `updateUser()`: User modification with validation
- `deleteUser()`: Soft deletion preserving relationships
- `restoreUser()`: Restore soft-deleted user
- `getAvailableRoles()`: Returns ['Admin', 'Agency Team', 'Client']
- `canModifyUser()`: Authorization check for self-modification

**Implementation Class**: `App\Services\UserManagementService`

**Usage Example**:
```php
$userService = app(UserManagementContract::class);
$users = $userService->listUsers(perPage: 20, includeTrashed: true);
$newUser = $userService->createUser([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secure-password',
    'role' => 'Admin'
]);
```

---

### 2. ClientManagementContract.php

**Purpose**: Defines CRUD operations for Client management with Slack integration.

**Key Methods**:
- `listClients()`: Paginated client listing with trashed inclusion
- `createClient()`: Client creation with team and Slack channel assignment
- `updateClient()`: Client modification including Slack channel updates
- `deleteClient()`: Soft deletion preserving content and magic links
- `restoreClient()`: Restore soft-deleted client
- `getAvailableSlackChannels()`: Fetch channels from Slack API
- `getAvailableTeams()`: Return available teams for assignment
- `hasActiveContent()`: Check if client has content items
- `hasActiveMagicLinks()`: Check if client has unexpired magic links

**Implementation Class**: `App\Services\ClientManagementService`

**Usage Example**:
```php
$clientService = app(ClientManagementContract::class);
$slackChannels = $clientService->getAvailableSlackChannels();
$newClient = $clientService->createClient([
    'name' => 'Acme Corp',
    'description' => 'Digital marketing client',
    'team_id' => 1,
    'slack_channel_id' => 'C12345678',
    'slack_channel_name' => '#acme-notifications'
]);
```

---

### 3. AuditEventFormatterContract.php

**Purpose**: Defines methods for formatting audit log changes into human-readable format.

**Key Methods**:
- `formatChangesInline()`: Convert JSON changes to readable string
- `getDetailedChanges()`: Return array of individual field changes
- `hasChanges()`: Check if audit log has old/new values
- `getChangeCount()`: Count number of changed fields
- `shouldTruncateChanges()`: Determine if >5 fields changed
- `formatTruncatedChanges()`: Show first N changes with expand link
- `formatEventName()`: Convert snake_case to "Title Case"
- `getEventColorClass()`: Return Tailwind CSS class for event badge
- `formatAuditableEntity()`: Format entity name for display
- `formatTimestamp()`: Format date to relative/absolute format

**Implementation Class**: `App\Services\AuditEventFormatterService`

**Usage Example**:
```php
$formatter = app(AuditEventFormatterContract::class);
$changesSummary = $formatter->formatChangesInline($auditLog);
// Returns: "name: 'John' → 'Jane', email: 'old@example.com' → 'new@example.com'"

if ($formatter->shouldTruncateChanges($auditLog)) {
    $truncated = $formatter->formatTruncatedChanges($auditLog, limit: 5);
    // Returns: "name: 'John' → 'Jane', email: ... (Show all 12 changes)"
}
```

---

## Implementation Guidelines

### Dependency Injection

Bind contracts to implementations in `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \App\Contracts\UserManagementContract::class,
        \App\Services\UserManagementService::class
    );

    $this->app->bind(
        \App\Contracts\ClientManagementContract::class,
        \App\Services\ClientManagementService::class
    );

    $this->app->bind(
        \App\Contracts\AuditEventFormatterContract::class,
        \App\Services\AuditEventFormatterService::class
    );
}
```

### Testing with Contracts

Use contracts in tests for easier mocking:

```php
public function test_user_creation()
{
    $userService = Mockery::mock(UserManagementContract::class);
    $userService->shouldReceive('createUser')
        ->once()
        ->with(['name' => 'Test User', ...])
        ->andReturn(User::factory()->make());

    $this->app->instance(UserManagementContract::class, $userService);

    // Test code that uses UserManagementContract
}
```

### Livewire Component Usage

Inject contracts into Livewire components via constructor:

```php
class UserManagement extends Component
{
    public function __construct(
        private UserManagementContract $userService
    ) {}

    public function createUser()
    {
        $this->userService->createUser($this->form);
    }
}
```

## Contract Validation

### Required Properties

All contracts must:
- [ ] Define clear method signatures with type hints
- [ ] Include PHPDoc blocks with parameter descriptions
- [ ] Specify return types
- [ ] Document exceptions that can be thrown
- [ ] Include usage examples

### Testing Requirements

Each contract implementation must have:
- [ ] Unit tests for all methods
- [ ] Integration tests for database interactions
- [ ] Validation tests for input data
- [ ] Error handling tests for exceptions

## Next Steps

1. **Implement Service Classes**: Create concrete implementations in `app/Services/`
2. **Register in AppServiceProvider**: Bind contracts to implementations
3. **Write Unit Tests**: Test each service method in isolation
4. **Integrate in Livewire**: Use contracts in admin Livewire components
5. **Document Service Behavior**: Add inline comments explaining business logic

---

**Status**: ✅ Contracts Defined - Ready for Implementation
