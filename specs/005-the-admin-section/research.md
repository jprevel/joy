# Phase 0 Research: Admin Section Patterns in Joy

**Date**: 2025-10-11
**Purpose**: Document existing patterns in the Joy codebase to inform Phase 0 implementation of admin section refresh

## Table of Contents
1. [Admin Component Structure](#admin-component-structure)
2. [Audit Logging Mechanism](#audit-logging-mechanism)
3. [Soft Delete Implementations](#soft-delete-implementations)
4. [Livewire Form Validation Patterns](#livewire-form-validation-patterns)
5. [Alpine.js Usage Patterns](#alpinejs-usage-patterns)
6. [Test Patterns](#test-patterns)
7. [Recommendations](#recommendations)

---

## 1. Admin Component Structure

### Current State

The Joy app has a well-established admin component structure in `/Users/jprevel/Documents/joy/joy-app/app/Livewire/Admin/`:

**Existing Admin Components:**
- `UserManagement.php` - List view with search, role filter, sorting, pagination
- `EditUser.php` - Edit view (currently minimal, needs CRUD functionality)
- `ClientManagement.php` - List view (currently minimal, needs CRUD functionality)
- `EditClient.php` - Edit view (currently minimal, needs CRUD functionality)
- `CreateClient.php` - Create view
- `AuditLogs.php` - Audit log viewer with comprehensive filtering
- `Dashboard.php` - Admin dashboard
- `UserDetail.php` - User detail view
- `ClientDetail.php` - Client detail view
- `TrelloManager.php`, `TrelloSyncStatus.php`, `IntegrationManager.php`, `SystemHealth.php`, `ClientTrelloSetup.php`

**Pattern: Authorization Check**
All admin components follow this pattern:
```php
use App\Services\RoleDetectionService;

public function __construct(
    private RoleDetectionService $roleDetectionService
) {
    parent::__construct();
}

public function mount()
{
    $this->currentUser = $this->roleDetectionService->getCurrentUser();

    if (!$this->roleDetectionService->isAdmin($this->currentUser)) {
        abort(403, 'Admin access required');
    }
}
```

**Note**: The RoleDetectionService exists but uses Spatie Laravel Permission under the hood:
```php
// From RoleDetectionService.php
public function isAdmin(User $user): bool
{
    return $user->hasRole('admin');
}
```

**Pattern: Layout**
All admin views use the admin layout:
```php
return view('livewire.admin.component-name')
    ->layout('components.layouts.admin');
```

**Pattern: Search & Filter**
`UserManagement.php` demonstrates the search/filter pattern:
```php
public string $search = '';
public string $roleFilter = '';
public string $sortBy = 'name';
public string $sortDirection = 'asc';

public function updatedSearch()
{
    $this->resetPage(); // Reset pagination on search
}

public function sortBy(string $field)
{
    if ($this->sortBy === $field) {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        $this->sortBy = $field;
        $this->sortDirection = 'asc';
    }
}
```

**Pattern: Pagination**
```php
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public function render()
    {
        $users = $query->paginate(15);
        return view('livewire.admin.user-management', ['users' => $users]);
    }
}
```

### What Currently Exists
✅ Admin component structure with proper authorization
✅ RoleDetectionService for role-based access control
✅ Search, filter, sort, pagination patterns
✅ Admin layout (`components.layouts.admin`)
✅ User list view with filtering (UserManagement)
✅ Audit log viewer with comprehensive filtering (AuditLogs)

### Gaps to Fill
❌ **User CRUD operations** - EditUser, CreateUser, DeleteUser methods
❌ **Client CRUD operations** - EditClient needs full implementation, DeleteClient
❌ **Slack channel selector** - Need to integrate Slack API to fetch channels
❌ **Soft delete UI** - Need to display deleted users/clients with indicators
❌ **Form validation patterns** - Need consistent validation across CRUD forms

---

## 2. Audit Logging Mechanism

### Current State

**Models & Structure:**
- **Location**: `/Users/jprevel/Documents/joy/joy-app/app/Models/AuditLog.php`
- **Services**:
  - `/Users/jprevel/Documents/joy/joy-app/app/Services/Audit/AuditLogger.php`
  - `/Users/jprevel/Documents/joy/joy-app/app/Services/AuditLogFormatter.php`
  - `/Users/jprevel/Documents/joy/joy-app/app/Services/AuditLogAnalyzer.php`

**Database Schema:**
```php
protected $fillable = [
    'client_id',
    'user_id',
    'event',
    'auditable_type',
    'auditable_id',
    'old_values',
    'new_values',
    'ip_address',
    'user_agent',
];

protected $casts = [
    'old_values' => 'array',
    'new_values' => 'array',
];
```

**Relationships:**
```php
public function client(): BelongsTo
{
    return $this->belongsTo(Client::class, 'client_id');
}

public function user(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}

public function auditable()
{
    return $this->morphTo();
}
```

**Scopes:**
```php
public function scopeForClient(Builder $query, int $clientId): Builder
public function scopeForUser(Builder $query, int $userId): Builder
public function scopeForEvent(Builder $query, string $event): Builder
public function scopeForModel(Builder $query, string $modelType, ?int $modelId = null): Builder
public function scopeRecent(Builder $query, int $days = 30): Builder
```

**AuditLogger Service Pattern:**
```php
// From app/Services/Audit/AuditLogger.php
public function log(AuditLogData $data): AuditLog
{
    $this->enrichDataWithContext($data);
    $this->enrichDataWithRequest($data);

    return AuditLog::create(array_filter($data->toArray()));
}

private function enrichDataWithContext(AuditLogData $data): void
{
    $this->detectWorkspace($data);
    $this->detectUser($data);
}

private function enrichDataWithRequest(AuditLogData $data): void
{
    $request = request();
    $data->requestData = [
        'url' => $request->fullUrl(),
        'method' => $request->method(),
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ];
}
```

**AuditLogFormatter Service:**
```php
// Display helpers
public function getUserDisplayName(AuditLog $auditLog): string
public function getActionDisplayName(AuditLog $auditLog): string
public function getSeverityColor(AuditLog $auditLog): string
public function getModelDisplayName(AuditLog $auditLog): string
public function getSummary(AuditLog $auditLog): string

// Example formatting
public function getActionDisplayName(AuditLog $auditLog): string
{
    return match($auditLog->action) {
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'viewed' => 'Viewed',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'commented' => 'Added Comment',
        'login' => 'Logged In',
        'logout' => 'Logged Out',
        default => ucfirst($auditLog->action)
    };
}
```

**Observer Pattern:**
The codebase uses Eloquent Observers for automatic logging:
```php
// From app/Observers/ContentItemObserver.php
public function updated(ContentItem $contentItem): void
{
    if (!$contentItem->client?->hasSlackIntegration()) {
        return;
    }

    if (!$contentItem->isDirty('status')) {
        return;
    }

    if ($contentItem->status === 'approved') {
        SendContentApprovedNotification::dispatch($contentItem);
    }
}
```

**Observers:**
- `ContentItemObserver.php` - Watches content approvals
- `CommentObserver.php` - Watches client comments
- `ClientStatusUpdateObserver.php` - Watches statusfaction updates

### What Currently Exists
✅ Comprehensive audit logging system with services
✅ AuditLog model with polymorphic relationships
✅ AuditLogFormatter for display formatting
✅ Observer pattern for automatic event capture
✅ Query scopes for filtering audit logs
✅ Helper methods delegated to services

### Gaps to Fill
❌ **Model observers for User/Client CRUD** - Need UserObserver, ClientObserver
❌ **Audit event constants** - Need standardized event names (see FR-037)
❌ **Change detail display** - Need to expand "+3 changes" inline
❌ **Event coverage** - Need to capture all user/client CRUD operations

### Patterns to Follow
1. **Use Observer pattern** for automatic audit logging on model events
2. **Delegate formatting to services** - Keep models lean, use AuditLogFormatter
3. **Use scopes for filtering** - Follow existing scope patterns
4. **Store changes in JSON** - old_values and new_values as arrays

**Example Implementation for User CRUD:**
```php
// app/Observers/UserObserver.php
class UserObserver
{
    public function created(User $user): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'User Created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'new_values' => $user->only(['name', 'email', 'role']),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function updated(User $user): void
    {
        if (!$user->wasChanged()) {
            return;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'User Updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => $user->getOriginal(),
            'new_values' => $user->getChanges(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function deleted(User $user): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'User Deleted',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => $user->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

---

## 3. Soft Delete Implementations

### Current State

**Finding**: ❌ **NO SOFT DELETES CURRENTLY IMPLEMENTED**

Search Results:
```bash
$ grep -r "use Illuminate\\Database\\Eloquent\\SoftDeletes" app/Models
# No results found
```

This is a **gap** that needs to be addressed for Phase 0.

### What Needs to be Implemented

**Pattern to Follow (Laravel Standard):**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $dates = ['deleted_at'];
}
```

**Migration Pattern:**
```php
Schema::table('users', function (Blueprint $table) {
    $table->softDeletes();
});
```

**Query Patterns:**
```php
// Include soft-deleted records
User::withTrashed()->get();

// Only soft-deleted records
User::onlyTrashed()->get();

// Restore soft-deleted record
$user->restore();

// Force delete (permanent)
$user->forceDelete();
```

**UI Display Pattern:**
```php
// In Livewire component
public function render()
{
    $users = User::withTrashed()->paginate(15);

    return view('livewire.admin.user-management', [
        'users' => $users,
    ]);
}
```

**Blade Display Pattern:**
```blade
@if($user->trashed())
    <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
        Deleted
    </span>
@endif
```

### Requirements from Spec

From FR-009, FR-017:
- Users and Clients MUST use soft deletion
- Soft-deleted users cannot log in (FR-009a)
- Soft-deleted users display "Deleted" indicator (FR-009b)
- Soft-deleted clients retain functional magic links (FR-017a)
- Soft-deleted clients display "Deleted" indicator (FR-017b)
- All relationships remain intact (content, comments, audit logs)

### Implementation Strategy

1. **Add SoftDeletes trait to User and Client models**
2. **Create migration to add deleted_at column**
3. **Update admin list views to show withTrashed()**
4. **Add visual indicators for deleted records**
5. **Prevent soft-deleted users from logging in** (middleware/auth check)
6. **Add restore functionality to admin UI** (optional, but useful)

---

## 4. Livewire Form Validation Patterns

### Current State

The codebase uses Laravel's Livewire validation attributes:

**Pattern: Validation Attributes (Modern Livewire v3)**
```php
use Livewire\Attributes\Rule;

class Statusfaction extends Component
{
    #[Rule('required|string|min:1')]
    public $status_notes = '';

    #[Rule('required|integer|min:1|max:10')]
    public $client_satisfaction = 5;

    #[Rule('required|integer|min:1|max:10')]
    public $team_health = 5;

    public function saveStatus()
    {
        $this->validate();

        // ... save logic
    }
}
```

**Pattern: Display Validation Errors**
```blade
<textarea wire:model="status_notes" required></textarea>
@error('status_notes')
    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
@enderror
```

**Pattern: Real-time Validation**
```php
// Use wire:model.live for real-time updates
<input type="text" wire:model.live="email" />

// Property updated hook
public function updatedEmail($value)
{
    $this->validateOnly('email');
}
```

**Pattern: Custom Validation Messages**
```php
protected function messages()
{
    return [
        'email.required' => 'Email address is required',
        'email.email' => 'Please enter a valid email address',
        'email.unique' => 'This email is already registered',
    ];
}
```

### What Currently Exists
✅ Validation attribute pattern (#[Rule('...')]) in Statusfaction component
✅ Blade error display pattern (@error directive)
✅ Real-time validation with wire:model.live

### Patterns to Follow for CRUD Forms

**User Create/Edit Form:**
```php
class CreateUser extends Component
{
    #[Rule('required|string|max:255')]
    public $name = '';

    #[Rule('required|email|unique:users,email')]
    public $email = '';

    #[Rule('required|string|min:8')]
    public $password = '';

    #[Rule('required|in:admin,agency,client')]
    public $role = '';

    public function save()
    {
        $this->validate();

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ])->assignRole($this->role);

        session()->flash('success', 'User created successfully');
        return redirect()->route('admin.users.index');
    }
}
```

**Client Create/Edit Form:**
```php
class CreateClient extends Component
{
    #[Rule('required|string|max:255')]
    public $name = '';

    #[Rule('nullable|string|max:1000')]
    public $description = '';

    #[Rule('required|exists:teams,id')]
    public $team_id = '';

    #[Rule('nullable|string')]
    public $slack_channel_id = '';

    public function save()
    {
        $this->validate();

        Client::create([
            'name' => $this->name,
            'description' => $this->description,
            'team_id' => $this->team_id,
            'slack_channel_id' => $this->slack_channel_id,
        ]);

        session()->flash('success', 'Client created successfully');
        return redirect()->route('admin.clients.index');
    }
}
```

---

## 5. Alpine.js Usage Patterns

### Current State

Alpine.js is used extensively in the Statusfaction view for interactive UI:

**Pattern: Component State Management**
```blade
<div x-data="{ sidebarOpen: true }" class="flex h-screen">
    <div :class="sidebarOpen ? 'w-64' : 'w-16'">
        <button @click="sidebarOpen = !sidebarOpen">
            Toggle
        </button>
    </div>
</div>
```

**Pattern: Conditional Display**
```blade
<div x-show="sidebarOpen" x-transition>
    Sidebar content
</div>
```

**Pattern: Click Handlers**
```blade
<button @click="sidebarOpen = !sidebarOpen">
    <svg class="w-5 h-5">...</svg>
</button>
```

**Pattern: Chart.js Integration**
```blade
<div wire:ignore>
    <canvas id="trendChart"></canvas>
</div>

<script>
document.addEventListener('livewire:init', () => {
    @if($showDetail && $selectedClient)
        const graphData = @js($this->graphData);

        setTimeout(() => {
            const canvas = document.getElementById('trendChart');
            const ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: { labels: graphData.labels, datasets: graphData.datasets }
            });
        }, 300);
    @endif
});
</script>
```

### What Currently Exists
✅ Alpine.js for collapsible sidebar
✅ Alpine.js for conditional rendering (x-show, x-transition)
✅ Alpine.js for click handlers (@click)
✅ Chart.js integration with Livewire data

### Patterns to Use for Collapsible Filters

**Pattern: Collapsible Filter Form**
```blade
<div x-data="{ filtersOpen: false }" class="mb-6">
    <button
        @click="filtersOpen = !filtersOpen"
        class="px-4 py-2 bg-white rounded-lg shadow">
        <span x-text="filtersOpen ? 'Hide Filters' : 'Show Filters'"></span>
        @if($activeFiltersCount > 0)
            <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                {{ $activeFiltersCount }} active
            </span>
        @endif
    </button>

    <div x-show="filtersOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="mt-4 bg-white rounded-lg shadow p-6">
        <!-- Filter form fields -->
    </div>
</div>
```

**Pattern: Delete Confirmation Modal**
```blade
<div x-data="{ showModal: false }">
    <button @click="showModal = true">Delete User</button>

    <div x-show="showModal"
         @click.away="showModal = false"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this user?</p>
            <div class="mt-4 flex gap-2">
                <button @click="showModal = false">Cancel</button>
                <button wire:click="deleteUser" @click="showModal = false">Delete</button>
            </div>
        </div>
    </div>
</div>
```

---

## 6. Test Patterns

### Current State

**Test Location**: `/Users/jprevel/Documents/joy/joy-app/tests/Feature/`

**Existing Admin/Audit Tests:**
- `AdminContentManagementE2ETest.php`
- `AuditLogViewerTest.php`

**Test Pattern from StatusfactionReportingE2ETest.php:**

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StatusfactionReportingE2ETest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function account_manager_can_submit_status_for_assigned_client()
    {
        // Create roles
        Role::firstOrCreate(['name' => 'agency']);

        // Create Account Manager with assigned client
        $accountManager = User::factory()->create();
        $accountManager->assignRole('agency');

        $team = Team::factory()->create();
        $accountManager->teams()->attach($team);

        $client = Client::factory()->create(['team_id' => $team->id]);

        // Test Statusfaction component
        $component = Livewire::actingAs($accountManager)
            ->test(Statusfaction::class)
            ->assertSee($client->name)
            ->assertSee('Needs Status')
            ->call('selectClient', $client->id)
            ->assertSet('showForm', true)
            ->set('status_notes', 'Test status notes')
            ->set('client_satisfaction', 8)
            ->set('team_health', 7)
            ->call('saveStatus');

        $component->assertHasNoErrors()
            ->assertSet('showForm', false);

        // Assert database has new record
        $this->assertDatabaseHas('client_status_updates', [
            'client_id' => $client->id,
            'user_id' => $accountManager->id,
            'status_notes' => 'Test status notes',
            'client_satisfaction' => 8,
            'team_health' => 7,
            'approval_status' => 'pending_approval',
        ]);
    }
}
```

**Key Testing Patterns:**
1. **Use RefreshDatabase** to reset DB state between tests
2. **Create roles first** using `Role::firstOrCreate(['name' => 'role_name'])`
3. **Assign roles** using `$user->assignRole('role_name')`
4. **Use Factories** for model creation (`User::factory()->create()`)
5. **Test Livewire components** using `Livewire::actingAs($user)->test(Component::class)`
6. **Assert component state** using `->assertSet('property', value)`
7. **Assert database state** using `$this->assertDatabaseHas('table', ['column' => 'value'])`
8. **Call component methods** using `->call('methodName', $param)`

### Test Suite Lock Constraints

From CLAUDE.md:
> **CRITICAL:** The test suite is **LOCKED** as of 2025-10-06. This is a hard requirement for application stability.
>
> 1. **NO NEW TEST FILES** - The test suite is frozen at **42 test files**

**Current count**: 6 test files in `tests/Feature/`

This suggests the 42 file limit is project-wide, not just Feature tests.

**Implication**: We CANNOT create new test files for UserManagementTest.php or ClientManagementTest.php.

**Strategy**: Add test methods to existing admin test files:
- Add user CRUD tests to `AdminContentManagementE2ETest.php`
- Add client CRUD tests to `AdminContentManagementE2ETest.php`
- Add audit UI tests to `AuditLogViewerTest.php`

### Test Patterns to Follow for CRUD

**User CRUD Test Pattern:**
```php
/** @test */
public function admin_can_create_user()
{
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(CreateUser::class)
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('password', 'password123')
        ->set('role', 'agency')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
}

/** @test */
public function admin_can_delete_user_with_soft_delete()
{
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $userToDelete = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('deleteUser', $userToDelete->id);

    $this->assertSoftDeleted('users', [
        'id' => $userToDelete->id,
    ]);
}
```

---

## 7. Recommendations

### Immediate Actions for Phase 0

#### 1. User CRUD Implementation
**Priority**: P1
**Files to Create/Modify**:
- ✅ `app/Livewire/Admin/CreateUser.php` - New file
- ✅ `app/Livewire/Admin/EditUser.php` - Enhance existing
- ✅ `app/Models/User.php` - Add SoftDeletes trait
- ✅ `database/migrations/YYYY_MM_DD_add_soft_deletes_to_users.php` - New migration
- ✅ `app/Observers/UserObserver.php` - New file for audit logging
- ✅ `resources/views/livewire/admin/create-user.blade.php` - New view
- ✅ `resources/views/livewire/admin/edit-user.blade.php` - Enhance existing

**Pattern to Follow**:
- Use `#[Rule()]` attributes for validation (follow Statusfaction pattern)
- Use RoleDetectionService for authorization (follow UserManagement pattern)
- Use Laravel's Hash::make() for passwords
- Use Spatie's assignRole() for role assignment
- Implement soft delete with SoftDeletes trait
- Create UserObserver for automatic audit logging

#### 2. Client CRUD Implementation
**Priority**: P1
**Files to Create/Modify**:
- ✅ `app/Livewire/Admin/EditClient.php` - Enhance existing
- ✅ `app/Models/Client.php` - Add SoftDeletes trait
- ✅ `database/migrations/YYYY_MM_DD_add_soft_deletes_to_clients.php` - New migration
- ✅ `app/Observers/ClientObserver.php` - New file for audit logging
- ✅ `resources/views/livewire/admin/create-client.blade.php` - Enhance if exists
- ✅ `resources/views/livewire/admin/edit-client.blade.php` - Enhance existing
- ✅ `app/Services/SlackChannelService.php` - New service for fetching Slack channels

**Pattern to Follow**:
- Use existing SlackService patterns for API calls
- Store slack_channel_id AND slack_channel_name (both required)
- Handle API errors gracefully with user-friendly messages
- Show "No Slack workspace connected" if not configured
- Implement soft delete with SoftDeletes trait
- Create ClientObserver for automatic audit logging

#### 3. Audit UI Enhancements
**Priority**: P2
**Files to Modify**:
- ✅ `app/Livewire/Admin/AuditLogs.php` - Add collapsible filter state
- ✅ `resources/views/livewire/admin/audit-logs.blade.php` - Add Alpine.js collapsible filters
- ✅ `app/Services/AuditLogFormatter.php` - Enhance change detail formatting

**Pattern to Follow**:
- Use Alpine.js x-data for filter collapse state (follow sidebar pattern)
- Add activeFiltersCount computed property in Livewire
- Remove IP address column from main view
- Expand change details inline using AuditLogFormatter
- Add expandable toggle for >5 changes using Alpine.js

#### 4. System Status Removal
**Priority**: P3
**Files to Modify**:
- ✅ `app/Livewire/Admin/Dashboard.php` - Remove SystemHealth reference
- ✅ `resources/views/livewire/admin/dashboard.blade.php` - Remove card, reorganize layout

### Code Examples to Reference

**Authorization Check** (all admin components):
```php
if (!$this->roleDetectionService->isAdmin($this->currentUser)) {
    abort(403, 'Admin access required');
}
```

**Livewire Validation** (Statusfaction):
```php
#[Rule('required|string|min:1')]
public $field = '';
```

**Pagination** (UserManagement):
```php
use Livewire\WithPagination;
$users = $query->paginate(15);
```

**Search Filter** (UserManagement):
```php
public function updatedSearch() {
    $this->resetPage();
}
```

**Alpine.js Collapsible** (Statusfaction sidebar):
```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-transition>Content</div>
</div>
```

**Audit Logging** (ContentItemObserver):
```php
public function updated(ContentItem $item): void {
    if (!$item->isDirty('status')) return;
    // Log the change
}
```

### Existing Services to Leverage

1. **RoleDetectionService** - For authorization checks
2. **AuditLogFormatter** - For formatting audit display
3. **AuditLogger** - For creating audit logs
4. **SlackService** - For Slack API integration (use as reference for SlackChannelService)

### Migration Strategy

**Soft Deletes Migration:**
```php
Schema::table('users', function (Blueprint $table) {
    $table->softDeletes();
});

Schema::table('clients', function (Blueprint $table) {
    $table->softDeletes();
});
```

### Test Strategy

**DO NOT CREATE NEW TEST FILES** - Add to existing:
- Add user CRUD tests to `tests/Feature/AdminContentManagementE2ETest.php`
- Add client CRUD tests to `tests/Feature/AdminContentManagementE2ETest.php`
- Add audit UI tests to `tests/Feature/AuditLogViewerTest.php`

Follow patterns from StatusfactionReportingE2ETest.php:
- Use RefreshDatabase
- Create roles first
- Use factories
- Test Livewire components with actingAs()
- Assert component state and database state

---

## Summary

### Strong Patterns to Follow
1. ✅ **Authorization via RoleDetectionService** - Consistent admin access checks
2. ✅ **Validation attributes** - Modern Livewire v3 #[Rule()] pattern
3. ✅ **Service delegation** - Keep components lean, delegate to services
4. ✅ **Observer pattern** - Automatic audit logging on model events
5. ✅ **Alpine.js for interactivity** - Collapsible filters, modals, toggles
6. ✅ **Pagination with search** - WithPagination + updatedSearch() pattern

### Critical Gaps to Address
1. ❌ **Soft deletes** - Not implemented anywhere, need to add to User and Client
2. ❌ **User CRUD operations** - EditUser/CreateUser need full implementation
3. ❌ **Client CRUD operations** - EditClient needs enhancement, CreateClient exists
4. ❌ **Slack channel selector** - Need new SlackChannelService
5. ❌ **Audit observers** - Need UserObserver and ClientObserver
6. ❌ **Change detail display** - Need to expand "+3 changes" inline

### Files Requiring Creation
- `app/Livewire/Admin/CreateUser.php`
- `app/Observers/UserObserver.php`
- `app/Observers/ClientObserver.php`
- `app/Services/SlackChannelService.php`
- `database/migrations/YYYY_MM_DD_add_soft_deletes_to_users.php`
- `database/migrations/YYYY_MM_DD_add_soft_deletes_to_clients.php`
- `resources/views/livewire/admin/create-user.blade.php`

### Files Requiring Enhancement
- `app/Livewire/Admin/EditUser.php` - Add CRUD methods
- `app/Livewire/Admin/EditClient.php` - Add CRUD methods
- `app/Livewire/Admin/UserManagement.php` - Add delete functionality
- `app/Livewire/Admin/ClientManagement.php` - Add CRUD functionality
- `app/Livewire/Admin/AuditLogs.php` - Add collapsible filter state
- `app/Models/User.php` - Add SoftDeletes trait
- `app/Models/Client.php` - Add SoftDeletes trait
- `app/Services/AuditLogFormatter.php` - Enhance change detail formatting
- `resources/views/livewire/admin/audit-logs.blade.php` - Add Alpine.js filters
- `resources/views/livewire/admin/edit-user.blade.php` - Add form fields
- `resources/views/livewire/admin/edit-client.blade.php` - Add Slack channel selector

### Success Metrics
- [ ] Admin can create users without console commands
- [ ] Admin can create clients with Slack channel mapping
- [ ] Audit filters collapse by default
- [ ] Change details expand inline without detail page
- [ ] Deleted users/clients show with indicators
- [ ] All CRUD operations logged in audit trail
- [ ] System Status card removed from dashboard

---

**End of Research Document**
