# Quickstart: Enhanced Statusfaction Testing

**Feature**: 002-we-have-the
**Purpose**: Manual testing guide and integration test validation scenarios

## Prerequisites

### Database Setup
```bash
cd joy-app
php artisan migrate
php artisan db:seed --class=RoleSeeder # If roles don't exist
```

### Test Data Setup
```php
// Run in php artisan tinker or create seeder
use App\Models\User;
use App\Models\Team;
use App\Models\Client;
use App\Models\ClientStatusUpdate;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

// Create roles if needed
Role::firstOrCreate(['name' => 'Admin']);
Role::firstOrCreate(['name' => 'Account Manager']);
Role::firstOrCreate(['name' => 'Agency']);

// Create users
$admin = User::factory()->create([
    'name' => 'Admin User',
    'email' => 'admin@test.com',
]);
$admin->assignRole('Admin');

$accountManager = User::factory()->create([
    'name' => 'Account Manager User',
    'email' => 'am@test.com',
]);
$accountManager->assignRole('Account Manager');

// Create team and assign account manager
$team = Team::factory()->create(['name' => 'Marketing Team']);
$accountManager->teams()->attach($team);

// Create clients
$client1 = Client::factory()->create([
    'name' => 'Acme Corp',
    'team_id' => $team->id,
]);

$client2 = Client::factory()->create([
    'name' => 'TechStart Inc',
    'team_id' => $team->id,
]);

// Create historical status data (5 weeks)
$weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

for ($i = 4; $i >= 1; $i--) {
    $week = $weekStart->copy()->subWeeks($i);

    ClientStatusUpdate::create([
        'user_id' => $accountManager->id,
        'client_id' => $client1->id,
        'status_notes' => "Status for week {$i} weeks ago",
        'client_satisfaction' => rand(6, 9),
        'team_health' => rand(5, 8),
        'status_date' => $week->copy()->addDays(3), // Thursday
        'week_start_date' => $week,
        'approval_status' => 'approved',
        'approved_by' => $admin->id,
        'approved_at' => $week->copy()->addDays(4), // Friday
    ]);
}

// Current week: pending approval
ClientStatusUpdate::create([
    'user_id' => $accountManager->id,
    'client_id' => $client1->id,
    'status_notes' => "Current week status - pending",
    'client_satisfaction' => 7,
    'team_health' => 8,
    'status_date' => $weekStart->copy()->addDays(3),
    'week_start_date' => $weekStart,
    'approval_status' => 'pending_approval',
]);

// Client 2: Needs status (no submission this week)
```

## Manual Testing Scenarios

### Scenario 1: Account Manager - View Client List

**As**: Account Manager User (am@test.com)

**Steps**:
1. Login to application
2. Navigate to `/statusfaction` or click "Statusfaction" in navigation
3. Observe client list

**Expected Results**:
- ✅ See "Acme Corp" with "Pending Approval" badge (yellow)
- ✅ See "TechStart Inc" with "Needs Status" badge (red)
- ✅ Only see clients from assigned teams
- ✅ Do NOT see clients from other teams

---

### Scenario 2: Account Manager - Submit New Status

**As**: Account Manager User

**Steps**:
1. In client list, click "TechStart Inc" (Needs Status)
2. Observe form appears with empty fields
3. Enter status notes: "Great progress this week!"
4. Set client satisfaction slider to 8
5. Set team health slider to 7
6. Click "Save Status Update"

**Expected Results**:
- ✅ Form validates (notes required)
- ✅ Status saved successfully message appears
- ✅ Returns to client list
- ✅ "TechStart Inc" now shows "Pending Approval" badge

**Verify in Database**:
```sql
SELECT * FROM client_status_updates
WHERE client_id = [TechStart Inc ID]
AND week_start_date = [Current Sunday]
ORDER BY created_at DESC LIMIT 1;
```
- ✅ `approval_status` = 'pending_approval'
- ✅ `status_notes` = "Great progress this week!"
- ✅ `client_satisfaction` = 8
- ✅ `team_health` = 7

---

### Scenario 3: Account Manager - Edit Pending Status

**As**: Account Manager User

**Steps**:
1. In client list, click "TechStart Inc" (now Pending Approval)
2. Observe form pre-filled with existing data
3. Change status notes to "Updated notes"
4. Change client satisfaction to 9
5. Click "Save Status Update"

**Expected Results**:
- ✅ Form shows existing values
- ✅ Updates saved successfully
- ✅ Returns to client list
- ✅ Status remains "Pending Approval"

**Verify in Database**:
```sql
SELECT * FROM client_status_updates
WHERE client_id = [TechStart Inc ID]
AND week_start_date = [Current Sunday];
```
- ✅ Only 1 record for this week (updated, not duplicated)
- ✅ `status_notes` = "Updated notes"
- ✅ `client_satisfaction` = 9

---

### Scenario 4: Account Manager - View Trend Graph

**As**: Account Manager User

**Steps**:
1. In client list, click "Acme Corp" (Status Approved - has history)
2. Observe detail view with graph (not form, because approved)

**Expected Results**:
- ✅ Cannot edit form (status is approved)
- ✅ See line graph with 5 weeks on X-axis
- ✅ Two lines: Blue (Client Satisfaction), Green (Team Health)
- ✅ Current week shows data point for approved status
- ✅ Previous 4 weeks show data points
- ✅ Y-axis ranges from 1-10

**Verify Graph Data**:
- ✅ X-axis labels show week dates (e.g., "Sep 24", "Oct 1", ...)
- ✅ Hover over data points shows exact values
- ✅ Lines connect consecutive weeks
- ✅ No line segment if week is missing (gap)

---

### Scenario 5: Account Manager - Cannot Edit Approved Status

**As**: Account Manager User

**Steps**:
1. In client list, click "Acme Corp" (from previous weeks, approved)
2. Observe detail view (graph only)

**Expected Results**:
- ✅ Form is NOT shown (or disabled)
- ✅ Only graph and read-only status details visible
- ✅ Status notes displayed as read-only text
- ✅ Ratings displayed as read-only values

---

### Scenario 6: Account Manager - Empty Notes Validation

**As**: Account Manager User

**Steps**:
1. Click client with "Needs Status"
2. Leave notes field empty
3. Set satisfaction = 5, team health = 5
4. Click "Save Status Update"

**Expected Results**:
- ✅ Validation error: "The status notes field is required"
- ✅ Form does NOT submit
- ✅ Remains on form view
- ✅ Satisfaction and team health values retained

---

### Scenario 7: Admin - View All Clients

**As**: Admin User (admin@test.com)

**Steps**:
1. Login as admin
2. Navigate to `/statusfaction`
3. Observe client list

**Expected Results**:
- ✅ See ALL clients (not just assigned teams)
- ✅ See "Acme Corp" with "Pending Approval" badge
- ✅ See "TechStart Inc" with appropriate status
- ✅ See clients from ALL teams

---

### Scenario 8: Admin - Approve Pending Status

**As**: Admin User

**Steps**:
1. In client list, click "Acme Corp" (Pending Approval)
2. Observe detail view with "Approve" button
3. Click "Approve Status" button

**Expected Results**:
- ✅ "Approve" button is visible (Admin only)
- ✅ Success message: "Status approved successfully!"
- ✅ Button disappears (no longer pending)
- ✅ Status badge changes to "Status Approved" (green)

**Verify in Database**:
```sql
SELECT * FROM client_status_updates
WHERE client_id = [Acme Corp ID]
AND week_start_date = [Current Sunday];
```
- ✅ `approval_status` = 'approved'
- ✅ `approved_by` = [Admin user ID]
- ✅ `approved_at` IS NOT NULL

---

### Scenario 9: Admin - View Trend Graph (Needs Status)

**As**: Admin User

**Steps**:
1. In client list, click client with "Needs Status"
2. Observe detail view

**Expected Results**:
- ✅ Graph shows last 5 weeks
- ✅ Historical data (previous weeks) displayed
- ✅ Current week shows gap/no data point
- ✅ Lines end at last submitted week

---

### Scenario 10: Edge Case - Duplicate Week Submission

**As**: Account Manager User

**Steps**:
1. Manually attempt to create duplicate status via tinker:
```php
ClientStatusUpdate::create([
    'user_id' => [Account Manager ID],
    'client_id' => [Client ID],
    'status_notes' => 'Duplicate',
    'client_satisfaction' => 5,
    'team_health' => 5,
    'status_date' => now(),
    'week_start_date' => now()->startOfWeek(Carbon::SUNDAY),
    'approval_status' => 'pending_approval',
]);
```

**Expected Results**:
- ✅ Database constraint violation error
- ✅ Unique index prevents duplicate: `client_week_unique`

---

## Integration Test Validation

### Test 1: Account Manager Submission Flow
```php
/** @test */
public function account_manager_can_submit_status_for_assigned_client()
{
    $accountManager = User::factory()->create();
    $accountManager->assignRole('Account Manager');

    $team = Team::factory()->create();
    $accountManager->teams()->attach($team);

    $client = Client::factory()->create(['team_id' => $team->id]);

    Livewire::actingAs($accountManager)
        ->test(Statusfaction::class)
        ->assertSee($client->name)
        ->assertSee('Needs Status')
        ->call('selectClient', $client->id)
        ->assertSet('showForm', true)
        ->set('status_notes', 'Test notes')
        ->set('client_satisfaction', 8)
        ->set('team_health', 7)
        ->call('saveStatus')
        ->assertHasNoErrors()
        ->assertSet('showForm', false)
        ->assertSessionHas('success');

    $this->assertDatabaseHas('client_status_updates', [
        'client_id' => $client->id,
        'user_id' => $accountManager->id,
        'status_notes' => 'Test notes',
        'client_satisfaction' => 8,
        'team_health' => 7,
        'approval_status' => 'pending_approval',
    ]);
}
```

### Test 2: Admin Approval Flow
```php
/** @test */
public function admin_can_approve_pending_status()
{
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $status = ClientStatusUpdate::factory()->create([
        'approval_status' => 'pending_approval',
    ]);

    Livewire::actingAs($admin)
        ->test(Statusfaction::class)
        ->call('approveStatus', $status->id)
        ->assertSessionHas('success');

    $status->refresh();

    $this->assertEquals('approved', $status->approval_status);
    $this->assertEquals($admin->id, $status->approved_by);
    $this->assertNotNull($status->approved_at);
}
```

### Test 3: Cannot Edit Approved Status
```php
/** @test */
public function account_manager_cannot_edit_approved_status()
{
    $accountManager = User::factory()->create();
    $accountManager->assignRole('Account Manager');

    $status = ClientStatusUpdate::factory()->create([
        'user_id' => $accountManager->id,
        'approval_status' => 'approved',
    ]);

    Livewire::actingAs($accountManager)
        ->test(Statusfaction::class)
        ->call('selectClient', $status->client_id)
        ->assertSet('showForm', false) // Form not shown for approved
        ->assertSet('showDetail', true); // Detail/graph shown instead
}
```

### Test 4: 5-Week Graph Data
```php
/** @test */
public function trend_graph_shows_five_weeks_with_gaps()
{
    $client = Client::factory()->create();
    $weekStart = now()->startOfWeek(Carbon::SUNDAY);

    // Create data for weeks -4, -2, 0 (current) - missing weeks -3 and -1
    foreach ([4, 2, 0] as $weeksAgo) {
        ClientStatusUpdate::factory()->create([
            'client_id' => $client->id,
            'week_start_date' => $weekStart->copy()->subWeeks($weeksAgo),
            'client_satisfaction' => 8,
            'team_health' => 7,
        ]);
    }

    $component = Livewire::test(Statusfaction::class)
        ->set('selectedClient', $client);

    $graphData = $component->graphData;

    $this->assertCount(5, $graphData['labels']); // 5 week labels
    $this->assertCount(2, $graphData['datasets']); // 2 lines

    // Weeks -3 and -1 should have null values
    $satisfactionData = $graphData['datasets'][0]['data'];
    $this->assertNotNull($satisfactionData[0]); // Week -4
    $this->assertNull($satisfactionData[1]); // Week -3 (gap)
    $this->assertNotNull($satisfactionData[2]); // Week -2
    $this->assertNull($satisfactionData[3]); // Week -1 (gap)
    $this->assertNotNull($satisfactionData[4]); // Week 0 (current)
}
```

## Cleanup

After testing:
```bash
# Reset database
php artisan migrate:fresh

# Or remove test data
php artisan tinker
> ClientStatusUpdate::where('status_notes', 'LIKE', '%test%')->delete();
> User::whereIn('email', ['admin@test.com', 'am@test.com'])->delete();
```

## Success Criteria

All scenarios pass when:
- ✅ Account Managers can submit and edit pending statuses
- ✅ Account Managers cannot edit approved statuses
- ✅ Admins can approve pending statuses
- ✅ Admins see all clients, Account Managers see assigned only
- ✅ Trend graph shows 5 weeks with gaps for missing data
- ✅ Validation prevents empty notes
- ✅ Database constraints prevent duplicate week submissions
- ✅ Role-based access control enforced
