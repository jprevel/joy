# Data Model: Enhanced Statusfaction

**Feature**: 002-we-have-the
**Date**: 2025-10-07

## Entity Definitions

### ClientStatusUpdate (Extended)

**Purpose**: Weekly status report with approval workflow and trend tracking

**Attributes**:
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, auto-increment | Primary key |
| user_id | bigint | FK(users), not null, cascade delete | Account Manager who submitted |
| client_id | bigint | FK(clients), not null, cascade delete | Client this status is for |
| status_notes | text | not null | Required notes (any non-empty text) |
| client_satisfaction | integer | not null, 1-10 | Client satisfaction rating |
| team_health | integer | not null, 1-10 | Team health rating |
| status_date | timestamp | not null | Timestamp of submission |
| week_start_date | date | not null, indexed | Sunday of the week (calculated) |
| approval_status | enum | not null, default 'needs_status' | 'needs_status', 'pending_approval', 'approved' |
| approved_by | bigint | FK(users), nullable | Admin who approved (null if not approved) |
| approved_at | timestamp | nullable | Timestamp of approval |
| created_at | timestamp | auto | Record creation |
| updated_at | timestamp | auto | Last update |

**Indexes**:
- Primary: (id)
- Unique: (client_id, week_start_date) - one status per client per week
- Index: (user_id, client_id) - efficient user+client queries
- Index: (status_date) - chronological sorting
- Index: (approval_status) - filtering by status
- Index: (week_start_date) - trend queries

**Relationships**:
- `belongsTo` User (submitter via user_id)
- `belongsTo` User (approver via approved_by)
- `belongsTo` Client

**Validation Rules**:
```php
'status_notes' => 'required|string|min:1',
'client_satisfaction' => 'required|integer|between:1,10',
'team_health' => 'required|integer|between:1,10',
'status_date' => 'required|date',
'week_start_date' => 'required|date',
'approval_status' => 'required|in:needs_status,pending_approval,approved',
```

**State Transitions**:
```
needs_status → pending_approval (on submit)
pending_approval → approved (admin approves)
pending_approval → pending_approval (edit while pending)
approved → [locked, no transitions]
```

**Business Logic**:
- `week_start_date` calculated: `Carbon::parse($status_date)->startOfWeek(Carbon::SUNDAY)`
- `approval_status` starts as 'pending_approval' on first submit
- Cannot edit if `approval_status === 'approved'`
- Cannot delete after submission
- Unique constraint prevents duplicate week submissions

**Scopes**:
```php
scopeForUser($user) - Filter to user's accessible clients
scopeForWeek($date) - Filter to specific week
scopePending() - approval_status = 'pending_approval'
scopeApproved() - approval_status = 'approved'
scopeNeedsStatus($client, $weekStart) - Check if client needs status for week
scopeLastFiveWeeks($client) - Get 5 most recent weeks for trend graph
```

### User (No Changes to Schema)

**Relevant Attributes**:
- Uses Spatie\Permission\Traits\HasRoles
- Roles: 'Admin', 'Account Manager', 'Agency'

**New Methods** (application logic, not schema):
```php
hasAccountManagerAccess(): bool - Check if user has Account Manager or Agency role
canApproveStatus(): bool - Check if user is Admin
assignedClients(): Collection - Get clients user can submit status for
```

### Client (No Changes)

**Existing Relationship**:
- `hasMany` ClientStatusUpdate (status updates)
- `belongsTo` Team

**New Methods** (application logic, not schema):
```php
currentWeekStatus(): ?ClientStatusUpdate - Get status for current week
statusForWeek(Carbon $weekStart): ?ClientStatusUpdate - Get status for specific week
lastFiveWeeksData(): Collection - Get 5 weeks of data for graph
needsStatus(): bool - Check if current week has no submission
```

## Migration Plan

### Migration: Add Approval Workflow Columns

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_approval_workflow_to_client_status_updates.php`

**Up**:
```php
Schema::table('client_status_updates', function (Blueprint $table) {
    $table->date('week_start_date')->after('status_date');
    $table->enum('approval_status', ['needs_status', 'pending_approval', 'approved'])
          ->default('pending_approval')
          ->after('week_start_date');
    $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable()->after('approved_by');

    // Indexes
    $table->unique(['client_id', 'week_start_date'], 'client_week_unique');
    $table->index('approval_status');
    $table->index('week_start_date');
});
```

**Down**:
```php
Schema::table('client_status_updates', function (Blueprint $table) {
    $table->dropForeign(['approved_by']);
    $table->dropUnique('client_week_unique');
    $table->dropIndex(['approval_status']);
    $table->dropIndex(['week_start_date']);
    $table->dropColumn(['week_start_date', 'approval_status', 'approved_by', 'approved_at']);
});
```

**Data Migration** (for existing records):
```php
// In migration up() after schema changes:
DB::table('client_status_updates')->update([
    'week_start_date' => DB::raw("DATE_SUB(status_date, INTERVAL WEEKDAY(status_date) + 1 DAY)"),
    'approval_status' => 'approved', // Assume existing are approved
]);
```

## Query Patterns

### Get Clients with Status States (for list view)

```php
// Account Manager view
$clients = Client::whereIn('team_id', auth()->user()->teams->pluck('id'))
    ->with(['latestStatusForCurrentWeek'])
    ->get()
    ->map(function ($client) {
        $currentWeekStart = now()->startOfWeek(Carbon::SUNDAY);
        $status = $client->statusUpdates()
            ->where('week_start_date', $currentWeekStart)
            ->first();

        $client->status_state = match(true) {
            $status === null => 'Needs Status',
            $status->approval_status === 'pending_approval' => 'Pending Approval',
            $status->approval_status === 'approved' => 'Status Approved',
            default => 'Needs Status',
        };

        return $client;
    });
```

### Get 5-Week Trend Data

```php
// For graph on detail view
$weekStart = now()->startOfWeek(Carbon::SUNDAY);
$fiveWeeksAgo = $weekStart->copy()->subWeeks(4);

$statusData = ClientStatusUpdate::where('client_id', $clientId)
    ->whereBetween('week_start_date', [$fiveWeeksAgo, $weekStart])
    ->orderBy('week_start_date')
    ->get(['week_start_date', 'client_satisfaction', 'team_health']);

// Generate 5-week array with nulls for missing weeks
$weeks = collect(range(0, 4))->map(function ($offset) use ($weekStart) {
    return $weekStart->copy()->subWeeks(4 - $offset)->format('Y-m-d');
});

$graphData = $weeks->map(function ($week) use ($statusData) {
    $status = $statusData->firstWhere('week_start_date', $week);
    return [
        'week' => Carbon::parse($week)->format('M j'),
        'client_satisfaction' => $status?->client_satisfaction,
        'team_health' => $status?->team_health,
    ];
});
```

### Approve Status

```php
// Admin action
$status = ClientStatusUpdate::findOrFail($id);

// Authorization check (done in Livewire)
if (!auth()->user()->hasRole('Admin')) {
    abort(403);
}

// Can only approve pending statuses
if ($status->approval_status !== 'pending_approval') {
    abort(422, 'Status is not pending approval');
}

$status->update([
    'approval_status' => 'approved',
    'approved_by' => auth()->id(),
    'approved_at' => now(),
]);
```

### Submit/Edit Status

```php
// Calculate week start
$weekStart = now()->startOfWeek(Carbon::SUNDAY);

// Check for existing status this week
$existing = ClientStatusUpdate::where('client_id', $clientId)
    ->where('week_start_date', $weekStart)
    ->first();

if ($existing && $existing->approval_status === 'approved') {
    abort(422, 'Cannot edit approved status');
}

// Create or update
ClientStatusUpdate::updateOrCreate(
    [
        'client_id' => $clientId,
        'week_start_date' => $weekStart,
    ],
    [
        'user_id' => auth()->id(),
        'status_notes' => $validated['status_notes'],
        'client_satisfaction' => $validated['client_satisfaction'],
        'team_health' => $validated['team_health'],
        'status_date' => now(),
        'approval_status' => 'pending_approval',
    ]
);
```

## Data Integrity Constraints

**Database Level**:
- Foreign key constraints with cascade delete
- Unique constraint on (client_id, week_start_date)
- Enum constraint on approval_status
- NOT NULL constraints on required fields
- CHECK constraints on rating ranges (1-10)

**Application Level**:
- Validation rules (Laravel Request validation)
- Authorization checks (Spatie Permission)
- State transition guards (cannot approve non-pending, cannot edit approved)
- Unique week submission enforcement

## Performance Optimization

**Indexes** (defined above):
- Unique (client_id, week_start_date) - 1-week uniqueness + fast lookups
- Index approval_status - filtering by state
- Index week_start_date - chronological queries
- Existing: (user_id, client_id), status_date

**Query Optimization**:
- Eager loading: `with(['user', 'client', 'approver'])`
- Limit trend queries to 5 weeks: `whereBetween('week_start_date', ...)`
- Use indexes for sorting: `orderBy('week_start_date', 'desc')`

**Caching** (if needed):
- No caching required initially
- Optional: Cache 5-week graph data (key: `client:{id}:graph:5weeks`, TTL: 1 hour)

## Testing Data Requirements

**Test Fixtures**:
- User with 'Admin' role
- User with 'Account Manager' role
- User with 'Agency' role
- Client with assigned team
- ClientStatusUpdate in each state (needs_status, pending_approval, approved)
- Multiple weeks of historical data for trend graph testing
- Edge case: Client with <5 weeks of data
- Edge case: Client with gaps in submissions

**Test Scenarios** (see spec.md for BDD scenarios):
- Account Manager submits new status
- Account Manager edits pending status
- Account Manager cannot edit approved status
- Admin approves pending status
- Trend graph shows 5 weeks with gaps
- Unique week constraint prevents duplicate submissions
