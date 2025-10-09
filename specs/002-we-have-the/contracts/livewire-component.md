# Livewire Component Contract: Statusfaction

**Component**: `App\Livewire\Statusfaction`
**Purpose**: Enhanced status submission and approval workflow for Account Managers and Admins

## Public Properties

### State Management
```php
public ?Client $selectedClient = null;         // Currently selected client
public bool $showForm = false;                 // Show form view vs list view
public bool $showDetail = false;               // Show detail/graph view
public string $currentRole = 'admin';          // User's active role
public ?ClientStatusUpdate $selectedStatus = null; // Current status being viewed/edited
```

### Form Fields
```php
#[Rule('required|string|min:1')]
public string $status_notes = '';

#[Rule('required|integer|between:1,10')]
public int $client_satisfaction = 5;

#[Rule('required|integer|between:1,10')]
public int $team_health = 5;
```

## Public Methods

### Navigation Actions

#### `mount(?string $role = null): void`
**Purpose**: Initialize component with user's role
**Parameters**:
- `$role` (optional): Override role for testing

**Logic**:
```php
if (!$role) {
    if (auth()->user()->hasRole('Admin')) {
        $role = 'admin';
    } elseif (auth()->user()->hasRole('Account Manager')) {
        $role = 'account_manager';
    }
}
$this->currentRole = $role;
```

**Returns**: void

---

#### `selectClient(int $clientId): void`
**Purpose**: Navigate to client detail/form view
**Parameters**:
- `$clientId`: ID of client to view

**Logic**:
```php
$this->selectedClient = Client::findOrFail($clientId);
$this->authorize('view', $this->selectedClient); // Policy check

$currentWeek = now()->startOfWeek(Carbon::SUNDAY);
$this->selectedStatus = ClientStatusUpdate::where('client_id', $clientId)
    ->where('week_start_date', $currentWeek)
    ->first();

if ($this->selectedStatus && $this->canEdit($this->selectedStatus)) {
    $this->showForm = true;
    $this->loadFormData();
} else {
    $this->showDetail = true;
}
```

**Returns**: void
**Emits**: None
**Errors**: 404 if client not found, 403 if unauthorized

---

#### `backToList(): void`
**Purpose**: Return to client list view
**Logic**:
```php
$this->showForm = false;
$this->showDetail = false;
$this->selectedClient = null;
$this->selectedStatus = null;
$this->resetForm();
```

**Returns**: void

---

### Data Actions

#### `saveStatus(): void`
**Purpose**: Save or update status submission
**Authorization**: Account Manager or Admin
**Validation**: Uses property rules

**Logic**:
```php
$this->validate();

$weekStart = now()->startOfWeek(Carbon::SUNDAY);

// Check if can edit
if ($this->selectedStatus && !$this->canEdit($this->selectedStatus)) {
    session()->flash('error', 'Cannot edit approved status');
    return;
}

ClientStatusUpdate::updateOrCreate(
    [
        'client_id' => $this->selectedClient->id,
        'week_start_date' => $weekStart,
    ],
    [
        'user_id' => auth()->id(),
        'status_notes' => $this->status_notes,
        'client_satisfaction' => $this->client_satisfaction,
        'team_health' => $this->team_health,
        'status_date' => now(),
        'approval_status' => 'pending_approval',
    ]
);

session()->flash('success', 'Status update saved successfully!');
$this->backToList();
```

**Returns**: void
**Side Effects**: Creates/updates ClientStatusUpdate record, flashes session message

---

#### `approveStatus(int $statusId): void`
**Purpose**: Admin approves pending status
**Authorization**: Admin only

**Logic**:
```php
$this->authorize('approve', ClientStatusUpdate::class);

$status = ClientStatusUpdate::findOrFail($statusId);

if ($status->approval_status !== 'pending_approval') {
    session()->flash('error', 'Status is not pending approval');
    return;
}

$status->update([
    'approval_status' => 'approved',
    'approved_by' => auth()->id(),
    'approved_at' => now(),
]);

session()->flash('success', 'Status approved successfully!');
$this->dispatch('statusApproved'); // Refresh graph if on detail view
```

**Returns**: void
**Emits**: 'statusApproved'

---

### Helper Methods

#### `getClientsProperty(): Collection`
**Purpose**: Compute clients list with status states (Livewire computed property)
**Authorization**: Account Managers see assigned clients, Admins see all

**Logic**:
```php
$user = auth()->user();
$currentWeek = now()->startOfWeek(Carbon::SUNDAY);

$query = Client::query();

// Filter by role
if (!$user->hasRole('Admin')) {
    $query->whereIn('team_id', $user->teams->pluck('id'));
}

return $query->with(['statusUpdates' => function ($q) use ($currentWeek) {
        $q->where('week_start_date', $currentWeek);
    }])
    ->get()
    ->map(function ($client) use ($currentWeek) {
        $status = $client->statusUpdates->first();

        $client->status_state = match(true) {
            $status === null => 'Needs Status',
            $status->approval_status === 'pending_approval' => 'Pending Approval',
            $status->approval_status === 'approved' => 'Status Approved',
            default => 'Needs Status',
        };

        $client->status_badge_color = match($client->status_state) {
            'Needs Status' => 'red',
            'Pending Approval' => 'yellow',
            'Status Approved' => 'green',
        };

        return $client;
    });
```

**Returns**: Collection of Client objects with computed status_state and status_badge_color

---

#### `getGraphDataProperty(): array`
**Purpose**: Compute 5-week trend data for selected client (Livewire computed property)

**Logic**:
```php
if (!$this->selectedClient) {
    return [];
}

$weekStart = now()->startOfWeek(Carbon::SUNDAY);
$fiveWeeksAgo = $weekStart->copy()->subWeeks(4);

$statusData = ClientStatusUpdate::where('client_id', $this->selectedClient->id)
    ->whereBetween('week_start_date', [$fiveWeeksAgo, $weekStart])
    ->orderBy('week_start_date')
    ->get(['week_start_date', 'client_satisfaction', 'team_health']);

// Generate 5-week labels
$weeks = collect(range(0, 4))->map(function ($offset) use ($weekStart) {
    $date = $weekStart->copy()->subWeeks(4 - $offset);
    return [
        'date' => $date->format('Y-m-d'),
        'label' => $date->format('M j'),
    ];
});

// Map data to weeks (with nulls for gaps)
$graphData = $weeks->map(function ($week) use ($statusData) {
    $status = $statusData->firstWhere('week_start_date', $week['date']);
    return [
        'week' => $week['label'],
        'client_satisfaction' => $status?->client_satisfaction,
        'team_health' => $status?->team_health,
    ];
});

return [
    'labels' => $graphData->pluck('week')->toArray(),
    'datasets' => [
        [
            'label' => 'Client Satisfaction',
            'data' => $graphData->pluck('client_satisfaction')->toArray(),
            'borderColor' => 'rgb(59, 130, 246)',
            'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
            'spanGaps' => false, // Show gaps as breaks in line
        ],
        [
            'label' => 'Team Health',
            'data' => $graphData->pluck('team_health')->toArray(),
            'borderColor' => 'rgb(16, 185, 129)',
            'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
            'spanGaps' => false,
        ],
    ],
];
```

**Returns**: Array formatted for Chart.js

---

#### `canEdit(ClientStatusUpdate $status): bool`
**Purpose**: Check if current user can edit this status

**Logic**:
```php
// Cannot edit approved statuses
if ($status->approval_status === 'approved') {
    return false;
}

// Admins can edit any pending status
if (auth()->user()->hasRole('Admin')) {
    return true;
}

// Account Managers can only edit their own pending statuses
return $status->user_id === auth()->id()
    && $status->approval_status === 'pending_approval';
```

**Returns**: bool

---

#### `canApprove(): bool`
**Purpose**: Check if current user can approve statuses

**Logic**:
```php
return auth()->user()->hasRole('Admin');
```

**Returns**: bool

---

## Private Methods

#### `resetForm(): void`
**Purpose**: Reset form fields to defaults
**Logic**:
```php
$this->status_notes = '';
$this->client_satisfaction = 5;
$this->team_health = 5;
```

---

#### `loadFormData(): void`
**Purpose**: Load existing status into form for editing
**Logic**:
```php
if ($this->selectedStatus) {
    $this->status_notes = $this->selectedStatus->status_notes;
    $this->client_satisfaction = $this->selectedStatus->client_satisfaction;
    $this->team_health = $this->selectedStatus->team_health;
}
```

---

## Render Method

```php
public function render()
{
    return view('livewire.statusfaction', [
        'clients' => $this->clients,
        'graphData' => $this->graphData,
        'canApprove' => $this->canApprove(),
    ]);
}
```

## Authorization Rules

**Policies** (if using Laravel Policy):
```php
// ClientPolicy
public function view(User $user, Client $client): bool
{
    // Admins can view all
    if ($user->hasRole('Admin')) {
        return true;
    }

    // Account Managers can view assigned clients
    return $client->team_id && $user->teams->pluck('id')->contains($client->team_id);
}

// ClientStatusUpdatePolicy
public function approve(User $user, ?ClientStatusUpdate $status): bool
{
    return $user->hasRole('Admin');
}

public function update(User $user, ClientStatusUpdate $status): bool
{
    // Cannot edit approved
    if ($status->approval_status === 'approved') {
        return false;
    }

    // Admins can edit any
    if ($user->hasRole('Admin')) {
        return true;
    }

    // Account Managers can edit their own pending
    return $status->user_id === $user->id
        && $status->approval_status === 'pending_approval';
}
```

## Events

**Dispatched**:
- `statusApproved`: After admin approves a status (used to refresh graph)

**Listened**:
- None

## Testing Contract

### Test Cases Required

1. **Mount**:
   - Admin role detection
   - Account Manager role detection
   - Role override parameter

2. **Client List**:
   - Account Manager sees only assigned clients
   - Admin sees all clients
   - Status states calculated correctly ('Needs Status', 'Pending Approval', 'Status Approved')

3. **Navigation**:
   - Select client opens form if status is editable
   - Select client opens detail view if status is read-only
   - Back to list resets state

4. **Save Status**:
   - Creates new status with 'pending_approval'
   - Updates existing pending status
   - Cannot update approved status
   - Validates required fields
   - Prevents duplicate week submissions

5. **Approve Status**:
   - Admin can approve pending status
   - Non-admin cannot approve
   - Cannot approve non-pending status
   - Sets approved_by and approved_at

6. **Graph Data**:
   - Returns 5 weeks of labels
   - Maps data correctly
   - Shows nulls for missing weeks
   - Handles clients with <5 weeks data

7. **Authorization**:
   - Account Manager cannot view unassigned clients
   - Account Manager cannot edit others' statuses
   - Account Manager cannot approve
   - Admin can approve
   - Cannot edit approved status

## Error Handling

**Validation Errors**:
- Flash validation errors for form fields
- Display inline in Blade template

**Authorization Errors**:
- 403 Forbidden for unauthorized actions
- Flash error message for soft failures (e.g., edit approved status)

**Not Found Errors**:
- 404 for invalid client/status IDs
- Graceful fallback to list view

**Constraint Violations**:
- Unique week constraint → flash error message
- Database errors → log and show generic error to user
