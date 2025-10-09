# Enhanced Statusfaction - Implementation Documentation

**Feature ID:** 002-we-have-the
**Status:** ✅ Completed
**Implementation Date:** 2025-10-07
**Laravel Version:** 12.x
**Livewire Version:** 3.x

---

## Table of Contents

1. [Overview](#overview)
2. [User Guide](#user-guide)
3. [Technical Architecture](#technical-architecture)
4. [Database Schema](#database-schema)
5. [Component API](#component-api)
6. [Authorization & Security](#authorization--security)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

---

## Overview

The Enhanced Statusfaction feature enables Account Managers to submit weekly status updates for their assigned clients, with Admin approval workflow and historical trend visualization.

### Key Features

✅ **Weekly Status Submission**
- Account Managers submit status for assigned clients
- Required fields: Status notes, Client satisfaction (1-10), Team health (1-10)
- One submission per client per week (Sunday-Saturday)

✅ **Approval Workflow**
- Submissions start as "Pending Approval"
- Admins review and approve submissions
- Approved statuses are locked from editing

✅ **Visual Trend Analysis**
- 5-week rolling trend graph (Chart.js)
- Client Satisfaction and Team Health metrics
- Automatic gap handling for missing weeks

✅ **Role-Based Access**
- Account Managers: See clients in assigned teams only
- Admins: See all clients across all teams
- Automatic permission filtering

---

## User Guide

### For Account Managers

#### Accessing Statusfaction

1. Log in with your Account Manager credentials
2. Click **"Statusfaction"** in the sidebar navigation
3. You'll see a list of clients from your assigned teams

#### Submitting Weekly Status

1. **Find Your Client**
   - Clients with red "Needs Status" badge require submission
   - Yellow "Pending Approval" badge = awaiting admin approval
   - Green "Status Approved" badge = approved for current week

2. **Click on a Client**
   - If no status exists: Form appears for new submission
   - If pending status exists: Form shows your data (editable)
   - If approved status exists: Read-only detail view

3. **Fill Out the Form**
   ```
   Status Notes: [Required - describe the week's activity]
   Client Satisfaction: [1-10 slider - how satisfied is the client?]
   Team Health: [1-10 slider - how is the team performing?]
   ```

4. **Submit**
   - Click "Save Status"
   - Status becomes "Pending Approval"
   - Admin will receive it for review

5. **View Trends**
   - Scroll down to see the 5-week trend graph
   - Blue line = Client Satisfaction
   - Green line = Team Health
   - Gaps indicate weeks with no submissions

#### Editing Your Submission

- **Before Approval:** You can edit your pending submission by clicking the client again
- **After Approval:** Status is locked - contact your admin if changes are needed

---

### For Admins

#### Reviewing Submissions

1. Navigate to **Statusfaction** from sidebar
2. Look for clients with yellow "Pending Approval" badge
3. Click the client to see submission details

#### Approving Status

1. Review the submitted information:
   - Status notes
   - Client satisfaction rating
   - Team health rating
   - Submitted by (Account Manager name)
   - Submission date

2. Click **"Approve Status"** button
3. Status badge changes to green "Status Approved"
4. The submission is now locked from editing

#### Viewing All Clients

- As Admin, you see **all clients** across all teams
- Filter by badge color to prioritize:
  - Red = Needs attention (no submission yet)
  - Yellow = Needs approval
  - Green = Completed for the week

---

## Technical Architecture

### System Components

```
┌─────────────────────────────────────────────────┐
│            User Interface Layer                 │
│  resources/views/livewire/statusfaction.blade.php │
│  - Client List View                             │
│  - Status Submission Form                       │
│  - Detail/Read-Only View                        │
│  - Chart.js Trend Visualization                 │
└───────────────┬─────────────────────────────────┘
                │
┌───────────────▼─────────────────────────────────┐
│         Livewire Component Layer                │
│      app/Livewire/Statusfaction.php             │
│  - State Management (showForm, showDetail)      │
│  - User Actions (selectClient, saveStatus)      │
│  - Computed Properties (clients, graphData)     │
│  - Authorization (canEdit, canApprove)          │
└───────────────┬─────────────────────────────────┘
                │
┌───────────────▼─────────────────────────────────┐
│             Model Layer                         │
│   app/Models/ClientStatusUpdate.php             │
│  - Eloquent Relationships                       │
│  - Query Scopes (pending, approved, forWeek)    │
│  - Attribute Casting                            │
└───────────────┬─────────────────────────────────┘
                │
┌───────────────▼─────────────────────────────────┐
│           Database Layer                        │
│  client_status_updates table                    │
│  - Approval workflow columns                    │
│  - Indexed for performance                      │
│  - Unique constraint per client/week            │
└─────────────────────────────────────────────────┘
```

### State Management

The component uses three view states:

1. **List View** (`!showForm && !showDetail`)
   - Shows all clients with status badges
   - Click to select a client

2. **Form View** (`showForm = true`)
   - Triggered when: No status OR user can edit pending status
   - Allows data entry/editing
   - Saves to database

3. **Detail View** (`showDetail = true`)
   - Triggered when: Status exists but user cannot edit (approved)
   - Read-only display
   - Shows approval metadata

### Workflow State Machine

```
┌─────────────┐
│ Needs Status│ (no record for current week)
└──────┬──────┘
       │ Account Manager submits
       ▼
┌──────────────────┐
│ Pending Approval │ (approval_status = 'pending_approval')
└──────┬───────────┘
       │ Admin approves
       ▼
┌─────────────────┐
│ Status Approved │ (approval_status = 'approved')
└─────────────────┘
       │
       │ (locked - no further edits)
```

---

## Database Schema

### Migration: `add_approval_workflow_to_client_status_updates`

**File:** `database/migrations/2025_10_08_000712_add_approval_workflow_to_client_status_updates.php`

#### New Columns

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `week_start_date` | date | No | - | Sunday of the submission week |
| `approval_status` | enum | No | 'pending_approval' | Workflow state |
| `approved_by` | foreignId | Yes | NULL | User ID of approver |
| `approved_at` | timestamp | Yes | NULL | Approval timestamp |

#### Enum Values

```php
approval_status: ['needs_status', 'pending_approval', 'approved']
```

#### Indexes

```sql
-- Prevent duplicate submissions per client per week
UNIQUE INDEX client_week_unique (client_id, week_start_date)

-- Fast filtering by approval status
INDEX idx_approval_status (approval_status)

-- Efficient date range queries
INDEX idx_week_start_date (week_start_date)
```

#### Foreign Keys

```sql
approved_by -> users.id (ON DELETE SET NULL)
```

### Model Relationships

**ClientStatusUpdate:**
```php
// Original submitter
user() -> User

// Associated client
client() -> Client

// Approver (if approved)
approver() -> User
```

### Query Scopes

```php
// Get pending submissions
ClientStatusUpdate::pending()->get();

// Get approved submissions
ClientStatusUpdate::approved()->get();

// Get submissions for specific week
ClientStatusUpdate::forWeek('2025-10-07')->get();

// Get last 5 weeks for a client
ClientStatusUpdate::lastFiveWeeks($clientId)->get();

// Get submissions for user's teams (Account Manager)
ClientStatusUpdate::forUser($user)->get();
```

---

## Component API

### Public Properties

```php
// State management
public $selectedClient = null;      // Currently selected Client model
public $showForm = false;            // Show submission form?
public $showDetail = false;          // Show read-only detail?
public $currentRole = 'admin';       // Current user role context
public $selectedStatus = null;       // Current ClientStatusUpdate

// Form fields (validated)
#[Rule('required|string|min:1')]
public $status_notes = '';

#[Rule('required|integer|min:1|max:10')]
public $client_satisfaction = 5;

#[Rule('required|integer|min:1|max:10')]
public $team_health = 5;
```

### Public Methods

#### `mount(?string $role = null)`
Initialize component with role detection.

**Parameters:**
- `$role` (optional): Override role ('admin', 'account_manager')

**Behavior:**
- Auto-detects role from authenticated user if not provided
- Falls back to 'admin' for unauthenticated tests

---

#### `selectClient(int $clientId)`
Handle client selection from list.

**Parameters:**
- `$clientId`: ID of selected client

**Behavior:**
1. Load client and current week's status
2. If status exists and editable → show form with data
3. If status exists but not editable → show detail view
4. If no status → show empty form

**Side Effects:**
- Sets `$selectedClient`
- Sets `$selectedStatus`
- Updates `$showForm` / `$showDetail`
- May call `loadFormData()` or `resetForm()`

---

#### `saveStatus()`
Save or update status submission.

**Validation:**
- Required: `status_notes` (min 1 char)
- Required: `client_satisfaction` (1-10)
- Required: `team_health` (1-10)

**Authorization:**
- Cannot save if status is approved
- Sets flash error if unauthorized

**Database:**
```php
updateOrCreate([
    'client_id' => $this->selectedClient->id,
    'week_start_date' => Carbon::now()->startOfWeek(Carbon::SUNDAY),
], [
    'user_id' => auth()->id(),
    'status_notes' => $this->status_notes,
    'client_satisfaction' => $this->client_satisfaction,
    'team_health' => $this->team_health,
    'status_date' => now(),
    'approval_status' => 'pending_approval',
]);
```

**Side Effects:**
- Flash success message
- Calls `backToList()`

---

#### `approveStatus(int $statusId)`
Approve a pending status (Admins only).

**Parameters:**
- `$statusId`: ID of ClientStatusUpdate to approve

**Authorization:**
- Only users with 'Admin' role
- Only statuses with 'pending_approval' status

**Database:**
```php
$status->update([
    'approval_status' => 'approved',
    'approved_by' => auth()->id(),
    'approved_at' => now(),
]);
```

**Events:**
- Dispatches `statusApproved` Livewire event

---

#### `backToList()`
Return to client list view.

**Side Effects:**
- Resets all state properties
- Clears form fields
- Hides form/detail views

---

### Computed Properties

#### `#[Computed] clients()`
Get filtered client list with status badges.

**Returns:** `Collection<Client>`

**Filtering:**
- Admins: All clients
- Account Managers: Clients in user's teams only

**Eager Loading:**
- `statusUpdates` for current week only

**Computed Attributes:**
- `status_state`: 'Needs Status' | 'Pending Approval' | 'Status Approved'
- `status_badge_color`: 'red' | 'yellow' | 'green'

**Performance:**
- Single query with whereIn for team filtering
- Eager loads status updates (prevents N+1)
- Uses computed property caching

---

#### `#[Computed] graphData()`
Generate Chart.js data for 5-week trend.

**Returns:** `array`

**Structure:**
```php
[
    'labels' => ['Sep 7', 'Sep 14', 'Sep 21', 'Sep 28', 'Oct 5'],
    'datasets' => [
        [
            'label' => 'Client Satisfaction',
            'data' => [8, null, 7, null, 9], // null = gap
            'borderColor' => 'rgb(59, 130, 246)',
            'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
            'spanGaps' => false,
        ],
        [
            'label' => 'Team Health',
            'data' => [7, null, 8, null, 8],
            'borderColor' => 'rgb(16, 185, 129)',
            'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
            'spanGaps' => false,
        ],
    ],
]
```

**Date Range:**
- Current week (Sunday) back 4 weeks = 5 total weeks
- Week labels formatted as 'M j' (e.g., 'Oct 5')

**Gap Handling:**
- Missing weeks return `null` values
- `spanGaps: false` shows discontinuous lines

---

### Private Methods

#### `canEdit(ClientStatusUpdate $status): bool`
Check if current user can edit a status.

**Rules:**
- Cannot edit approved statuses (anyone)
- Admins can edit any pending status
- Account Managers can only edit their own pending statuses

---

#### `canApprove(): bool`
Check if current user can approve statuses.

**Rules:**
- Only users with 'Admin' role

---

#### `resetForm(): void`
Clear form fields to defaults.

---

#### `loadFormData(): void`
Populate form fields from `$selectedStatus`.

---

## Authorization & Security

### Gate Definition

**File:** `app/Providers/AppServiceProvider.php`

```php
Gate::define('access statusfaction', function ($user) {
    return $user->hasRole(['Admin', 'Account Manager']);
});
```

### Route Protection

**File:** `routes/web.php`

```php
Route::middleware('auth')->group(function () {
    Route::get('/statusfaction', \App\Livewire\Statusfaction::class)
        ->name('statusfaction')
        ->middleware('can:access statusfaction');

    Route::get('/statusfaction/{role}', \App\Livewire\Statusfaction::class)
        ->name('statusfaction.role')
        ->where('role', 'client|agency|admin')
        ->middleware('can:access statusfaction');
});
```

### Permission Matrix

| Action | Account Manager | Admin |
|--------|----------------|-------|
| View statusfaction page | ✅ (own teams) | ✅ (all) |
| See client list | ✅ (filtered) | ✅ (all) |
| Submit new status | ✅ | ✅ |
| Edit own pending status | ✅ | ✅ |
| Edit others' pending status | ❌ | ✅ |
| Edit approved status | ❌ | ❌ |
| Approve pending status | ❌ | ✅ |
| View trend graph | ✅ | ✅ |

### Data Filtering

**Team-Based Access:**
```php
// Account Managers see only clients in their teams
if ($user && !$user->hasRole('Admin')) {
    $query->whereIn('team_id', $user->teams->pluck('id'));
}
```

**Status Edit Control:**
```php
// In selectClient() method
if ($this->selectedStatus && $this->canEdit($this->selectedStatus)) {
    $this->showForm = true;  // Allow editing
} elseif ($this->selectedStatus) {
    $this->showDetail = true;  // Read-only view
}
```

---

## Testing

### Integration Tests

**File:** `tests/Feature/StatusfactionReportingE2ETest.php`

#### Test Coverage

✅ **T003:** Account Manager can submit status for assigned client
✅ **T004:** Account Manager can edit pending status
✅ **T005:** Account Manager cannot edit approved status
✅ **T006:** Admin can approve pending status
✅ **T007:** Account Manager sees only assigned clients
✅ **T008:** Admin sees all clients
✅ **T009:** Client status states calculated correctly
✅ **T010:** Trend graph shows five weeks with gaps
✅ **T011:** Status notes required validation
✅ **T012:** Unique week constraint prevents duplicates
✅ **T013:** Migration adds approval workflow columns

#### Running Tests

```bash
# Run all Statusfaction tests
./vendor/bin/phpunit --filter StatusfactionReportingE2ETest

# Run specific test
./vendor/bin/phpunit --filter account_manager_can_submit_status

# Run with test lock validation
./scripts/test-lock.sh
```

#### Test Data Cleanup

All tests use `RefreshDatabase` trait for automatic cleanup:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatusfactionReportingE2ETest extends TestCase
{
    use RefreshDatabase;

    // Database automatically rolled back after each test
}
```

### Factories

#### ClientStatusUpdateFactory

**File:** `database/factories/ClientStatusUpdateFactory.php`

```php
ClientStatusUpdate::factory()->create([
    'client_id' => $client->id,
    'week_start_date' => Carbon::now()->startOfWeek(Carbon::SUNDAY),
    'client_satisfaction' => 8,
    'team_health' => 7,
    'approval_status' => 'pending_approval',
]);
```

#### TeamFactory

**File:** `database/factories/TeamFactory.php`

```php
Team::factory()->create([
    'name' => 'Development Team',
    'description' => 'Main development team',
]);
```

---

## Troubleshooting

### Common Issues

#### 1. "Session is missing expected key [success]"

**Cause:** Flash messages in Livewire tests aren't aged like HTTP requests

**Solution:** Remove `assertSessionHas()` from Livewire tests:
```php
// Don't do this in Livewire tests:
->assertSessionHas('success');

// Instead, assert database state or component properties
->assertSet('showForm', false);
$this->assertDatabaseHas('client_status_updates', [...]);
```

---

#### 2. "Call to undefined method Team::factory()"

**Cause:** Missing `HasFactory` trait on model

**Solution:**
```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;
    // ...
}
```

---

#### 3. "Attempt to read property 'name' on null"

**Cause:** Blade template tries to access properties on null user/client

**Solution:** Add null safety:
```php
// In mount() method
if (!$role && auth()->check()) {
    // Only access auth()->user() if authenticated
}

// In clients() computed property
if ($user && !$user->hasRole('Admin')) {
    // Check $user exists before calling methods
}
```

---

#### 4. Graph shows all null values

**Cause:** Date comparison mismatch (Carbon object vs string)

**Solution:**
```php
// Don't use firstWhere() with Carbon dates
$status = $statusData->firstWhere('week_start_date', $week['date']);

// Use first() with callback instead
$status = $statusData->first(function ($s) use ($week) {
    return $s->week_start_date->format('Y-m-d') === $week['date'];
});
```

---

#### 5. Migration fails with "WEEKDAY function doesn't exist"

**Cause:** Using MySQL-specific function on PostgreSQL database

**Solution:** Use database-agnostic date arithmetic:
```sql
-- MySQL (wrong):
DATE_SUB(status_date, INTERVAL (WEEKDAY(status_date) + 1) DAY)

-- PostgreSQL (correct):
(status_date::date - EXTRACT(DOW FROM status_date)::int * INTERVAL '1 day')::date
```

---

#### 6. "Unique constraint violation"

**Cause:** Attempting to create multiple statuses for same client/week

**Expected Behavior:** This is by design. Use `updateOrCreate()`:
```php
ClientStatusUpdate::updateOrCreate(
    [
        'client_id' => $clientId,
        'week_start_date' => $weekStart,
    ],
    [
        'status_notes' => $notes,
        // ... other fields
    ]
);
```

---

## Performance Considerations

### Database Indexes

✅ Composite unique index prevents duplicate submissions
✅ Status index speeds up filtering (pending/approved)
✅ Date index optimizes weekly queries
✅ Foreign key indexes automatic on relationships

### Query Optimization

✅ Eager loading prevents N+1 queries:
```php
->with(['statusUpdates' => function ($q) use ($currentWeek) {
    $q->where('week_start_date', $currentWeek);
}])
```

✅ Computed properties cache results per request

✅ Single query for team filtering:
```php
->whereIn('team_id', $user->teams->pluck('id'))
```

### Caching Strategy

**Current:** Livewire computed properties cache within single request

**Future Enhancement:** Consider Redis caching for:
- Client lists (cache per user/role)
- Graph data (cache per client/week range)
- TTL: 5 minutes or invalidate on status update

---

## Future Enhancements

### Potential Features

1. **Email Notifications**
   - Notify admins when status submitted
   - Notify Account Managers when status approved
   - Weekly reminder for missing submissions

2. **Bulk Operations**
   - Approve multiple statuses at once
   - Export weekly reports

3. **Advanced Analytics**
   - Compare clients across teams
   - Identify trends (improving/declining)
   - Alert on low satisfaction scores

4. **Comments/Discussion**
   - Add comments to status updates
   - Thread discussions between AM and Admin

5. **Historical Reporting**
   - Generate PDF reports
   - Export to CSV/Excel
   - Custom date range selection

---

## Changelog

### v1.0.0 (2025-10-07)

**Added:**
- Weekly status submission form
- Admin approval workflow
- 5-week trend visualization with Chart.js
- Role-based access control
- Team-filtered client lists
- Status badge system (red/yellow/green)
- Database migration with approval columns
- 11 integration tests
- Factories for testing

**Technical:**
- PostgreSQL-compatible date calculations
- Indexed database columns for performance
- Eager loading to prevent N+1 queries
- Livewire 3 computed properties
- Spatie Permission integration
- Gate-based route protection

---

## Support

### Resources

- **Feature Spec:** `specs/002-we-have-the/spec.md`
- **Implementation Plan:** `specs/002-we-have-the/plan.md`
- **Task List:** `specs/002-we-have-the/tasks.md`
- **This Documentation:** `specs/002-we-have-the/IMPLEMENTATION.md`

### Getting Help

1. Check [Troubleshooting](#troubleshooting) section
2. Review test cases for usage examples
3. Consult Laravel Livewire docs: https://livewire.laravel.com
4. Check Chart.js docs: https://www.chartjs.org/docs/latest/

---

**Documentation Version:** 1.0.0
**Last Updated:** 2025-10-07
**Maintained By:** Joy Development Team
