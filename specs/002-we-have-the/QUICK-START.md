# Enhanced Statusfaction - Quick Start Guide

**TL;DR:** Account Managers submit weekly status updates → Admins approve them → Everyone sees 5-week trends

---

## 🚀 Quick Access

**URL:** `/statusfaction`

**Who Can Access:**
- ✅ Admins (see all clients)
- ✅ Account Managers (see assigned teams only)
- ❌ Other roles

---

## 📝 Submit Status (Account Manager)

1. Click **Statusfaction** in sidebar
2. Find client with **red "Needs Status"** badge
3. Click the client
4. Fill out form:
   - **Status Notes:** What happened this week?
   - **Client Satisfaction:** 1-10 slider
   - **Team Health:** 1-10 slider
5. Click **Save Status**
6. Status → **yellow "Pending Approval"**

---

## ✅ Approve Status (Admin)

1. Click **Statusfaction** in sidebar
2. Find client with **yellow "Pending Approval"** badge
3. Click the client
4. Review the submission
5. Click **Approve Status**
6. Status → **green "Status Approved"**

---

## 📊 View Trends

Scroll down after selecting any client to see:
- **Blue line:** Client Satisfaction (last 5 weeks)
- **Green line:** Team Health (last 5 weeks)
- **Gaps:** Weeks with no submission

---

## 🎨 Status Badges

| Badge | Meaning | Action Needed |
|-------|---------|---------------|
| 🔴 **Needs Status** | No submission this week | Account Manager: Submit |
| 🟡 **Pending Approval** | Awaiting review | Admin: Approve |
| 🟢 **Status Approved** | Completed | None - locked ✅ |

---

## 🔒 Editing Rules

| Status | Account Manager | Admin |
|--------|----------------|-------|
| **Before approval** | Can edit own | Can edit any |
| **After approval** | Cannot edit | Cannot edit |

**Note:** Once approved, statuses are locked to maintain data integrity.

---

## 🗓️ Weekly Cycle

```
Sunday = Week Start
├─ Monday-Friday: Account Managers submit
├─ By Friday: Admins approve
└─ Next Sunday: New week begins
```

---

## ⚡ Pro Tips

1. **Submit early in the week** - Don't wait until Friday!
2. **Be specific in notes** - Future you will thank you
3. **Watch the trends** - Declining scores = action needed
4. **Can't find a client?** - Check if you're assigned to their team
5. **Made a mistake?** - Edit before approval or ask admin

---

## 🛠️ Developer Quick Reference

### Component Path
```
app/Livewire/Statusfaction.php
```

### Blade Template
```
resources/views/livewire/statusfaction.blade.php
```

### Model
```
app/Models/ClientStatusUpdate.php
```

### Migration
```
database/migrations/2025_10_08_000712_add_approval_workflow_to_client_status_updates.php
```

### Key Routes
```php
GET /statusfaction          // Default role
GET /statusfaction/{role}   // Specific role (admin|account_manager)
```

### Permission Gate
```php
Gate::define('access statusfaction', fn($user) =>
    $user->hasRole(['Admin', 'Account Manager'])
);
```

### Run Tests
```bash
./vendor/bin/phpunit --filter StatusfactionReportingE2ETest
./scripts/test-lock.sh
```

### Database Queries

**Get pending submissions:**
```php
ClientStatusUpdate::pending()->get();
```

**Get this week's status for a client:**
```php
ClientStatusUpdate::where('client_id', $clientId)
    ->forWeek(Carbon::now()->startOfWeek())
    ->first();
```

**Get last 5 weeks:**
```php
ClientStatusUpdate::lastFiveWeeks($clientId)->get();
```

---

## 🔧 Common Tasks

### Create Status Programmatically
```php
ClientStatusUpdate::create([
    'user_id' => auth()->id(),
    'client_id' => $client->id,
    'status_notes' => 'Great progress this week',
    'client_satisfaction' => 9,
    'team_health' => 8,
    'status_date' => now(),
    'week_start_date' => Carbon::now()->startOfWeek(Carbon::SUNDAY),
    'approval_status' => 'pending_approval',
]);
```

### Approve Status Programmatically
```php
$status->update([
    'approval_status' => 'approved',
    'approved_by' => auth()->id(),
    'approved_at' => now(),
]);
```

### Check User Permissions
```php
// Can access statusfaction?
Gate::allows('access statusfaction')

// Can approve?
auth()->user()->hasRole('Admin')

// Can edit this status?
$status->approval_status === 'pending_approval'
    && (auth()->user()->hasRole('Admin') || $status->user_id === auth()->id())
```

---

## 📱 API Reference (Component Methods)

### User Actions
```php
selectClient($clientId)    // Navigate to client
saveStatus()               // Submit/update status
approveStatus($statusId)   // Approve (admin only)
backToList()              // Return to client list
```

### Data Access
```php
$this->clients          // Computed: Filtered client list
$this->graphData        // Computed: Chart.js data structure
```

### State Properties
```php
$selectedClient         // Current Client model
$selectedStatus         // Current ClientStatusUpdate model
$showForm              // Boolean: Show edit form
$showDetail            // Boolean: Show read-only view
$status_notes          // Form field: Text
$client_satisfaction   // Form field: 1-10
$team_health          // Form field: 1-10
```

---

## 📋 Checklist for Manual Testing

**As Account Manager:**
- [ ] Navigate to /statusfaction
- [ ] See only clients from my teams
- [ ] Click client with "Needs Status"
- [ ] Submit status with all fields
- [ ] Status becomes "Pending Approval"
- [ ] Can edit my pending status
- [ ] Cannot edit approved status
- [ ] See 5-week trend graph

**As Admin:**
- [ ] Navigate to /statusfaction
- [ ] See ALL clients (not just my teams)
- [ ] Click client with "Pending Approval"
- [ ] See submitted data
- [ ] Click "Approve Status"
- [ ] Status becomes "Status Approved"
- [ ] Cannot edit approved status
- [ ] Can still edit other pending statuses

---

## 🐛 Quick Troubleshooting

**Problem:** Can't see Statusfaction in sidebar
- **Fix:** Check role = Admin or Account Manager

**Problem:** Can't see any clients
- **Fix (AM):** Check team assignments
- **Fix (Admin):** Check if any clients exist in database

**Problem:** Can't edit status
- **Fix:** Check if status is already approved (shows green badge)

**Problem:** Form won't submit
- **Fix:** Check all fields are filled (notes required, sliders 1-10)

**Problem:** Graph shows no data
- **Fix:** No historical data exists - submit status over several weeks

**Problem:** Duplicate submission error
- **Fix:** One status per client per week - edit existing instead

---

## 📚 Full Documentation

For complete technical details, see:
- **Full Implementation Docs:** `IMPLEMENTATION.md`
- **Feature Spec:** `spec.md`
- **Implementation Plan:** `plan.md`
- **Task List:** `tasks.md`

---

**Version:** 1.0.0
**Last Updated:** 2025-10-07
