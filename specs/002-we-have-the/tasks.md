# Tasks: Enhanced Statusfaction for Account Managers

**Input**: Design documents from `/specs/002-we-have-the/`
**Prerequisites**: plan.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅, quickstart.md ✅

## Execution Flow (main)
```
1. Load plan.md from feature directory ✅
   → Tech stack: Laravel 12, Livewire 3, Spatie Permission, Chart.js
   → Structure: Web app (Laravel monolith)
2. Load optional design documents ✅
   → data-model.md: ClientStatusUpdate extended
   → contracts/: Livewire component methods
   → research.md: Approval workflow, Chart.js decision
   → quickstart.md: 10 manual test scenarios
3. Generate tasks by category ✅
   → Setup: Migration, test lock verification
   → Tests: Integration tests (Livewire), E2E scenarios
   → Core: Model extensions, component methods, blade templates
   → Integration: Authorization, graph rendering
   → Polish: Manual testing, code review
4. Apply task rules ✅
   → Different files marked [P]
   → Sequential for same file modifications
   → Tests before implementation (TDD)
5. Number tasks sequentially ✅
6. Generate dependency graph ✅
7. Create parallel execution examples ✅
8. Validate task completeness ✅
9. Return: SUCCESS (44 tasks ready for execution)
```

## Format: `[ID] [P?] Description`
- **[P]**: Can run in parallel (different files, no dependencies)
- Include exact file paths in descriptions

## Path Conventions
- **Laravel monolith**: `joy-app/` at repository root
- Paths shown below are absolute from repository root

---

## Phase 3.1: Pre-Flight Checks

### T001: Verify Test Suite Lock
**File**: N/A (script execution)
**Command**: `./scripts/test-lock.sh`
**Success Criteria**:
- 42 test files exist
- All tests pass (excluding 23 marked incomplete)
- Exit code 0

**Description**: Run test lock verification script to ensure baseline stability before starting work. This is a CRITICAL constitutional requirement from CLAUDE.md.

---

### T002: Create Database Migration File
**File**: `joy-app/database/migrations/YYYY_MM_DD_HHMMSS_add_approval_workflow_to_client_status_updates.php`
**Success Criteria**: Migration file created with correct structure

**Description**: Create migration file to add approval workflow columns to client_status_updates table. Include:
- `week_start_date` (date, not null, indexed)
- `approval_status` (enum: needs_status, pending_approval, approved, default pending_approval)
- `approved_by` (foreignId nullable to users)
- `approved_at` (timestamp nullable)
- Unique constraint on (client_id, week_start_date)
- Indexes on approval_status, week_start_date
- Down method to rollback changes

See data-model.md for complete schema details.

---

## Phase 3.2: Tests First (TDD) ⚠️ MUST COMPLETE BEFORE 3.3
**CRITICAL: These tests MUST be written and MUST FAIL before ANY implementation**

### T003 [P]: Integration Test - Account Manager Submits New Status
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_account_manager_can_submit_status_for_assigned_client()`

**Description**: Write Livewire integration test for Account Manager submitting a new status. Test should:
- Create Account Manager user with assigned client
- Test Statusfaction component
- Assert client list shows "Needs Status"
- Call selectClient(), verify showForm = true
- Set form properties (status_notes, client_satisfaction, team_health)
- Call saveStatus()
- Assert database has new record with approval_status = 'pending_approval'
- Assert success flash message

Reference: quickstart.md Scenario 2, contracts/livewire-component.md saveStatus()

**Expected**: Test FAILS (method not implemented yet)

---

### T004 [P]: Integration Test - Account Manager Edits Pending Status
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_account_manager_can_edit_pending_status()`

**Description**: Write test for editing pending status. Test should:
- Create Account Manager with client and existing pending status
- Test Statusfaction component
- Call selectClient(), verify form pre-filled
- Change status_notes and client_satisfaction
- Call saveStatus()
- Assert database updated (not duplicated)
- Assert approval_status remains 'pending_approval'

Reference: quickstart.md Scenario 3

**Expected**: Test FAILS

---

### T005 [P]: Integration Test - Cannot Edit Approved Status
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_account_manager_cannot_edit_approved_status()`

**Description**: Write test for approved status edit restriction. Test should:
- Create Account Manager with client and approved status
- Test Statusfaction component
- Call selectClient()
- Assert showForm = false (form not shown)
- Assert showDetail = true (detail view shown)

Reference: quickstart.md Scenario 5, contracts/livewire-component.md canEdit()

**Expected**: Test FAILS

---

### T006 [P]: Integration Test - Admin Approves Pending Status
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_admin_can_approve_pending_status()`

**Description**: Write test for admin approval flow. Test should:
- Create Admin user and pending status
- Test Statusfaction component as Admin
- Call approveStatus($statusId)
- Assert approval_status = 'approved'
- Assert approved_by = admin user ID
- Assert approved_at is set
- Assert success flash message

Reference: quickstart.md Scenario 8, contracts/livewire-component.md approveStatus()

**Expected**: Test FAILS

---

### T007 [P]: Integration Test - Account Manager Sees Only Assigned Clients
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_account_manager_sees_only_assigned_clients()`

**Description**: Write test for role-based client filtering. Test should:
- Create Account Manager with team A, clients 1-2 in team A
- Create clients 3-4 in team B (not assigned)
- Test Statusfaction component
- Assert clients property contains only clients 1-2
- Assert clients 3-4 not visible

Reference: quickstart.md Scenario 1, contracts/livewire-component.md getClientsProperty()

**Expected**: Test FAILS

---

### T008 [P]: Integration Test - Admin Sees All Clients
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_admin_sees_all_clients()`

**Description**: Write test for admin seeing all clients regardless of team. Test should:
- Create Admin user
- Create clients in multiple teams
- Test Statusfaction component as Admin
- Assert clients property contains all clients

Reference: quickstart.md Scenario 7

**Expected**: Test FAILS

---

### T009 [P]: Integration Test - Client Status States Calculated Correctly
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_client_status_states_calculated_correctly()`

**Description**: Write test for status state calculations. Test should:
- Create client with no submission this week → assert status_state = 'Needs Status'
- Create client with pending submission → assert status_state = 'Pending Approval'
- Create client with approved submission → assert status_state = 'Status Approved'

Reference: quickstart.md Scenario 1, contracts/livewire-component.md getClientsProperty()

**Expected**: Test FAILS

---

### T010 [P]: Integration Test - 5-Week Trend Graph Data
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_trend_graph_shows_five_weeks_with_gaps()`

**Description**: Write test for graph data generation. Test should:
- Create client with statuses for weeks -4, -2, 0 (current) - missing -3, -1
- Test Statusfaction component with selectedClient
- Assert graphData has 5 labels (week dates)
- Assert 2 datasets (client_satisfaction, team_health)
- Assert weeks -3 and -1 have null values (gaps)
- Assert weeks -4, -2, 0 have correct values

Reference: quickstart.md Scenario 10, contracts/livewire-component.md getGraphDataProperty()

**Expected**: Test FAILS

---

### T011 [P]: Integration Test - Empty Notes Validation
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_status_notes_required_validation()`

**Description**: Write test for notes field validation. Test should:
- Create Account Manager with client
- Test Statusfaction component
- Call selectClient()
- Set status_notes = '' (empty)
- Set client_satisfaction = 5, team_health = 5
- Call saveStatus()
- Assert validation error for status_notes
- Assert database unchanged (no record created)

Reference: quickstart.md Scenario 6

**Expected**: Test FAILS

---

### T012 [P]: Integration Test - Duplicate Week Constraint
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_unique_week_constraint_prevents_duplicates()`

**Description**: Write test for unique week constraint. Test should:
- Create status for current week
- Attempt to create second status for same client, same week_start_date
- Assert database constraint violation or application-level error
- Assert only 1 record exists for that week

Reference: data-model.md unique constraint

**Expected**: Test FAILS

---

### T013: Database Migration Test - Schema Changes
**File**: `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`
**Method**: `test_migration_adds_approval_workflow_columns()`

**Description**: Write test to verify migration schema changes. Test should:
- Run migration (if not already run)
- Assert client_status_updates table has columns: week_start_date, approval_status, approved_by, approved_at
- Assert unique index exists on (client_id, week_start_date)
- Assert indexes exist on approval_status, week_start_date
- Assert foreign key exists for approved_by → users

**Expected**: Test FAILS (migration not run yet)

---

## Phase 3.3: Core Implementation (ONLY after tests T003-T013 are failing)

### T014: Run Database Migration
**File**: `joy-app/database/migrations/*_add_approval_workflow_to_client_status_updates.php`
**Command**: `php artisan migrate`

**Description**: Execute the migration created in T002 to add approval workflow columns to client_status_updates table. Migration should:
- Add week_start_date, approval_status, approved_by, approved_at columns
- Create unique index on (client_id, week_start_date)
- Create indexes on approval_status, week_start_date
- Backfill existing records with week_start_date (calculated from status_date)
- Set existing records to approval_status = 'approved'

**Verify**: T013 test passes after migration

---

### T015: Extend ClientStatusUpdate Model - Fillable & Casts
**File**: `joy-app/app/Models/ClientStatusUpdate.php`

**Description**: Update ClientStatusUpdate model with new attributes. Add to $fillable:
- 'week_start_date'
- 'approval_status'
- 'approved_by'
- 'approved_at'

Add to $casts:
- 'week_start_date' => 'date'
- 'approved_at' => 'datetime'

Reference: data-model.md ClientStatusUpdate attributes

---

### T016 [P]: Add ClientStatusUpdate Model Scopes
**File**: `joy-app/app/Models/ClientStatusUpdate.php`

**Description**: Add query scopes to ClientStatusUpdate model:
- `scopePending($query)` - Filter to approval_status = 'pending_approval'
- `scopeApproved($query)` - Filter to approval_status = 'approved'
- `scopeForWeek($query, $date)` - Filter to specific week_start_date
- `scopeLastFiveWeeks($query, $clientId)` - Get 5 most recent weeks for a client

Reference: data-model.md Scopes section

**Verify**: Can be tested in isolation or via integration tests

---

### T017 [P]: Add ClientStatusUpdate Model Relationships
**File**: `joy-app/app/Models/ClientStatusUpdate.php`

**Description**: Add new relationship method for approver:
```php
public function approver(): BelongsTo
{
    return $this->belongsTo(User::class, 'approved_by');
}
```

Reference: data-model.md Relationships

---

### T018: Update Statusfaction Component - Add State Properties
**File**: `joy-app/app/Livewire/Statusfaction.php`

**Description**: Add new public properties to Statusfaction component:
```php
public bool $showDetail = false;
public ?ClientStatusUpdate $selectedStatus = null;
```

Update existing properties if needed. Do NOT implement methods yet.

---

### T019: Update Statusfaction Component - getClientsProperty() with Status States
**File**: `joy-app/app/Livewire/Statusfaction.php`

**Description**: Modify or create computed property `getClientsProperty()` to:
- Filter clients by role (Account Managers see assigned, Admins see all)
- Load current week's status for each client
- Calculate status_state ('Needs Status', 'Pending Approval', 'Status Approved')
- Calculate status_badge_color for UI (red/yellow/green)

Reference: contracts/livewire-component.md getClientsProperty()

**Verify**: T007, T008, T009 tests should start passing

---

### T020: Update Statusfaction Component - selectClient() Method
**File**: `joy-app/app/Livewire/Statusfaction.php`

**Description**: Modify selectClient() method to:
- Load selectedClient by ID
- Find or create selectedStatus for current week
- Determine if form should be shown (pending/needs) or detail view (approved)
- Set showForm or showDetail accordingly
- Load form data if editing

Reference: contracts/livewire-component.md selectClient()

**Verify**: T003, T005 tests should start passing

---

### T021: Update Statusfaction Component - saveStatus() Method
**File**: `joy-app/app/Livewire/Statusfaction.php`

**Description**: Modify saveStatus() method to:
- Calculate week_start_date (Sunday of current week)
- Check if status is editable (not approved)
- Use updateOrCreate with (client_id, week_start_date) to prevent duplicates
- Set approval_status = 'pending_approval'
- Set user_id, status_date, and form fields
- Flash success message and return to list

Reference: contracts/livewire-component.md saveStatus(), data-model.md Submit/Edit Status query

**Verify**: T003, T004 tests should pass

---

### T022: Update Statusfaction Component - approveStatus() Method
**File**: `joy-app/app/Livewire/Statusfaction.php`

**Description**: Add approveStatus($statusId) method to:
- Authorize user is Admin
- Load status by ID
- Verify approval_status = 'pending_approval'
- Update to approval_status = 'approved', approved_by = auth()->id(), approved_at = now()
- Flash success message
- Dispatch 'statusApproved' event

Reference: contracts/livewire-component.md approveStatus()

**Verify**: T006 test should pass

---

### T023 [P]: Update Statusfaction Component - Helper Methods
**File**: `joy-app/app/Livewire/Statusfaction.php`

**Description**: Add helper methods:
- `canEdit(ClientStatusUpdate $status): bool` - Check if user can edit status
- `canApprove(): bool` - Check if user is Admin
- `loadFormData(): void` - Load existing status into form properties
- Update `resetForm(): void` if needed

Reference: contracts/livewire-component.md Helper Methods section

---

### T024: Update Statusfaction Component - getGraphDataProperty()
**File**: `joy-app/app/Livewire/Statusfaction.php`

**Description**: Add computed property `getGraphDataProperty()` to:
- Calculate 5 most recent weeks (ending with current)
- Query statuses for selectedClient within date range
- Generate week labels (e.g., "Oct 1", "Oct 8")
- Map data with nulls for missing weeks
- Return Chart.js formatted array with labels and datasets (client_satisfaction, team_health)

Reference: contracts/livewire-component.md getGraphDataProperty(), data-model.md Query Patterns

**Verify**: T010 test should pass

---

### T025: Update Statusfaction Blade - Status Badges in Client List
**File**: `joy-app/resources/views/livewire/statusfaction.blade.php`

**Description**: Modify client list view (@if (!$showForm && !$showDetail) block) to:
- Display status badge next to client name
- Use computed status_state property
- Color badges: red (Needs Status), yellow (Pending Approval), green (Status Approved)
- Use Tailwind badge component styles

Reference: Existing blade template, plan.md UI/UX section

---

### T026: Update Statusfaction Blade - Conditional Form vs Detail View
**File**: `joy-app/resources/views/livewire/statusfaction.blade.php`

**Description**: Refactor existing @if ($showForm) block to support two modes:
- If $showForm = true: Show editable form (status_notes, sliders, Save button)
- If $showDetail = true: Show read-only detail view with:
  - Status notes (read-only text)
  - Ratings (read-only values, not sliders)
  - Graph placeholder (to be implemented in T027)
  - Admin "Approve" button (if pending and canApprove)

Reference: contracts/livewire-component.md, quickstart.md Scenarios 4, 5, 8

**Verify**: T005 test should pass (form not shown for approved)

---

### T027: Add Chart.js to Statusfaction Blade
**File**: `joy-app/resources/views/livewire/statusfaction.blade.php`

**Description**: In detail view block (@if $showDetail):
- Add Chart.js CDN script tag in <head> (use SRI hash for security)
- Add <canvas id="trendChart"></canvas> element
- Add Alpine.js or vanilla JS to render chart with $this->graphData
- Configure Chart.js:
  - Type: line
  - Data: from graphData computed property
  - Options: spanGaps = false (show breaks for nulls), Y-axis 1-10

Reference: research.md Chart.js decision, contracts/livewire-component.md getGraphDataProperty()

**Verify**: T010 test should pass (graph data correct), manual test shows visual graph

---

### T028: Add Admin Approve Button to Detail View
**File**: `joy-app/resources/views/livewire/statusfaction.blade.php`

**Description**: In detail view block, add conditional button:
```blade
@if ($selectedStatus && $selectedStatus->approval_status === 'pending_approval' && $this->canApprove())
    <button wire:click="approveStatus({{ $selectedStatus->id }})" ...>
        Approve Status
    </button>
@endif
```

Style with Tailwind green button. Show success message after approval.

Reference: quickstart.md Scenario 8

**Verify**: T006 test should pass

---

## Phase 3.4: Integration & Polish

### T029 [P]: Add Validation Rules to Statusfaction Component
**File**: `joy-app/app/Livewire/Statusfaction.php`

**Description**: Ensure validation rules are defined for form properties:
```php
#[Rule('required|string|min:1')]
public string $status_notes = '';
```

Verify rules match spec (notes required, satisfaction/health 1-10).

Reference: data-model.md Validation Rules

**Verify**: T011 test should pass

---

### T030 [P]: Create ClientStatusUpdatePolicy for Authorization
**File**: `joy-app/app/Policies/ClientStatusUpdatePolicy.php` (new file)

**Description**: OPTIONAL - If not using inline auth checks, create Policy with methods:
- `approve(User $user)` - Only Admins can approve
- `update(User $user, ClientStatusUpdate $status)` - Can edit if pending and own status (or Admin)

Register policy in AuthServiceProvider if created.

Reference: contracts/livewire-component.md Authorization Rules

**Note**: This is optional; authorization can be handled inline in component methods.

---

### T031: Add Empty State to Client List
**File**: `joy-app/resources/views/livewire/statusfaction.blade.php`

**Description**: In client list block, add @if ($clients->count() === 0) block with empty state message:
- Icon or illustration
- Message: "No clients assigned" (for Account Managers) or "No clients in system" (for Admins)

Reference: quickstart.md Edge case

---

### T032: Update Statusfaction Navigation Link
**File**: `joy-app/resources/views/livewire/content-calendar.blade.php` or navigation partial

**Description**: Ensure "Statusfaction" link is visible in navigation for:
- Account Managers (hasRole('Account Manager'))
- Admins (hasRole('Admin'))

Link to: `/statusfaction` route

Reference: spec.md FR-001

---

### T033: Verify Route Exists for Statusfaction
**File**: `joy-app/routes/web.php`

**Description**: Ensure route exists for Statusfaction component:
```php
Route::get('/statusfaction', Statusfaction::class)
    ->middleware(['auth'])
    ->name('statusfaction');
```

Adjust as needed based on existing routing patterns.

---

### T034: Run Full Test Suite
**Command**: `cd joy-app && ./vendor/bin/phpunit`

**Description**: Run all tests to verify:
- All new tests (T003-T013) pass
- No existing tests broken
- Test count still 42 files (no new test files created)

**Success Criteria**: All tests pass, 0 failures

---

### T035: Manual Testing - Account Manager Submit Flow
**Reference**: `quickstart.md` Scenarios 1, 2, 3

**Description**: Manually test as Account Manager:
1. Login as Account Manager
2. Navigate to /statusfaction
3. Verify client list shows assigned clients with "Needs Status"
4. Click client, submit status with notes, ratings
5. Verify status changes to "Pending Approval"
6. Click same client again, edit status
7. Verify changes saved

**Success Criteria**: All scenarios in quickstart.md Scenarios 1-6 pass

---

### T036: Manual Testing - Admin Approval Flow
**Reference**: `quickstart.md` Scenarios 7, 8

**Description**: Manually test as Admin:
1. Login as Admin
2. Navigate to /statusfaction
3. Verify seeing all clients (not just assigned)
4. Click client with "Pending Approval"
5. Verify "Approve" button visible
6. Click Approve, verify status changes to "Status Approved"

**Success Criteria**: Scenarios 7-8 in quickstart.md pass

---

### T037: Manual Testing - Trend Graph Visualization
**Reference**: `quickstart.md` Scenarios 4, 9

**Description**: Manually test graph:
1. Create test data with 5 weeks of historical statuses (use quickstart.md setup script)
2. Click client with history
3. Verify graph displays with 5 weeks on X-axis
4. Verify two lines (Client Satisfaction blue, Team Health green)
5. Verify gaps shown for missing weeks
6. Hover over data points to verify values

**Success Criteria**: Graph renders correctly, shows gaps, accurate data

---

### T038: Manual Testing - Edge Cases
**Reference**: `quickstart.md` Edge Cases

**Description**: Test edge cases:
- Account Manager with no clients assigned → empty state shown
- Client with <5 weeks data → graph shows available weeks only
- Attempt to edit approved status → form not shown
- Empty notes submission → validation error

**Success Criteria**: All edge cases handled gracefully

---

### T039: Code Review - Constitutional Compliance
**File**: All modified files

**Description**: Review code changes against Joy Development Constitution (CLAUDE.md):
- ✅ Test suite still 42 files (no new test files)
- ✅ All tests pass (run ./scripts/test-lock.sh)
- ✅ TDD followed (tests written before implementation)
- ✅ No new complexity violations
- ✅ Follows existing Laravel/Livewire patterns

**Success Criteria**: All constitutional requirements met

---

### T040: Performance Validation
**Description**: Validate performance targets from plan.md:
- Page load time <200ms (measure /statusfaction initial load)
- Livewire action response <100ms (measure saveStatus, approveStatus)
- Graph rendering <1s for 5 weeks of data

**Tools**: Browser DevTools Network/Performance tabs

**Success Criteria**: All targets met

---

### T041 [P]: Update CLAUDE.md with New Patterns (if applicable)
**File**: `joy-app/CLAUDE.md` or `CLAUDE.md` at repo root

**Description**: OPTIONAL - If this feature introduces new patterns worth documenting:
- Livewire component with approval workflow
- Chart.js integration pattern
- Role-based computed properties

Add concise notes to CLAUDE.md under relevant section.

**Note**: Only update if pattern is reusable. This feature follows existing patterns.

---

### T042: Clean Up Test Data
**Description**: Remove any test data created during manual testing:
- Delete test users (admin@test.com, am@test.com)
- Delete test status submissions
- Reset database to clean state

**Command**: See quickstart.md Cleanup section for scripts

---

### T043: Final Test Suite Lock Verification
**Command**: `./scripts/test-lock.sh`

**Description**: Final verification before marking feature complete:
- Exactly 42 test files
- All tests pass
- Exit code 0

**Success Criteria**: Test lock passes

---

### T044: Mark Feature Complete
**Description**: Update progress tracking in plan.md:
- [x] Phase 4: Implementation complete
- [x] Phase 5: Validation passed

Commit final changes with message:
```
Complete Enhanced Statusfaction for Account Managers (#002)

- Add approval workflow (needs_status → pending_approval → approved)
- Add admin approval capability
- Add 5-week trend visualization with Chart.js
- Extend ClientStatusUpdate model and migration
- Update Statusfaction Livewire component and blade template
- All tests passing, constitutional compliance verified

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

---

## Dependencies

**Critical Path**:
1. T001 (test lock) blocks all other tasks
2. T002 (migration file) → T013 (migration test) → T014 (run migration)
3. T003-T013 (all tests) block T015-T028 (implementation)
4. T014 (migration) blocks T015-T017 (model changes)
5. T015-T017 (model) block T019-T024 (component logic)
6. T019-T024 (component) block T025-T028 (blade templates)
7. T025-T028 (UI) block T035-T038 (manual testing)
8. T034 (test suite) blocks T039 (code review)
9. T035-T040 (validation) block T044 (feature complete)

**Parallel Opportunities**:
- T003-T012 can be written in parallel (different test methods)
- T016, T017 can be done in parallel with T015 (different sections of same file, low conflict)
- T025-T028 blade updates can be partially parallel (different view sections)
- T029, T030, T031 can be done in parallel (different files)
- T035-T038 manual tests can be executed concurrently (different user flows)
- T040, T041 can be done in parallel (independent activities)

---

## Parallel Execution Examples

### Example 1: Write All Integration Tests in Parallel
```bash
# Launch T003-T012 together (10 test methods in same file - use caution, may conflict):
# Better approach: Write in sequence but quickly, or split across 2-3 sessions

# Safe parallel: T003-T005 (Account Manager tests)
Task: "Integration test - Account Manager submits new status in tests/Feature/StatusfactionReportingE2ETest.php"
Task: "Integration test - Account Manager edits pending status in tests/Feature/StatusfactionReportingE2ETest.php"
Task: "Integration test - Cannot edit approved status in tests/Feature/StatusfactionReportingE2ETest.php"

# Then: T006-T008 (Admin tests)
Task: "Integration test - Admin approves pending status in tests/Feature/StatusfactionReportingE2ETest.php"
Task: "Integration test - Account Manager sees only assigned clients in tests/Feature/StatusfactionReportingE2ETest.php"
Task: "Integration test - Admin sees all clients in tests/Feature/StatusfactionReportingE2ETest.php"
```

### Example 2: Model Extensions in Parallel
```bash
# After migration runs (T014), these can run concurrently:
Task: "Add scopes to ClientStatusUpdate model in app/Models/ClientStatusUpdate.php (scopePending, scopeApproved, etc.)"
Task: "Add approver relationship to ClientStatusUpdate model in app/Models/ClientStatusUpdate.php"

# Note: Both modify same file but different sections - low conflict risk
```

### Example 3: Blade Template Sections
```bash
# These modify different sections of the blade template:
Task: "Add status badges to client list in resources/views/livewire/statusfaction.blade.php"
Task: "Add detail view with graph placeholder in resources/views/livewire/statusfaction.blade.php"

# Moderate conflict risk - coordinate carefully
```

### Example 4: Final Polish Tasks
```bash
# These are independent files/activities:
Task: "Add empty state to client list in statusfaction.blade.php"
Task: "Create ClientStatusUpdatePolicy in app/Policies/ClientStatusUpdatePolicy.php"
Task: "Update CLAUDE.md with new patterns (if applicable)"
```

---

## Notes

- **[P] tasks**: Different files or low-conflict sections, can run in parallel
- **Sequential tasks**: Same file, high-conflict sections, must run in order
- **TDD Critical**: T003-T013 MUST fail before starting T014-T028
- **Test Lock**: Run T001 first, T034 mid-way, T043 at end
- **Commit Strategy**: Commit after each logical grouping (e.g., after all tests written, after migration, after component complete)
- **Constitutional Compliance**: NO NEW TEST FILES - extend StatusfactionReportingE2ETest.php only

---

## Task Generation Rules Applied

1. **From Contracts** (livewire-component.md):
   - Each method → implementation task (T019-T024)
   - Each method → integration test (T003-T012)

2. **From Data Model** (data-model.md):
   - Entity extension → migration task (T002, T013, T014)
   - Entity extension → model tasks (T015, T016, T017)
   - Query patterns → component logic (T019, T024)

3. **From User Stories** (spec.md, quickstart.md):
   - Each scenario → integration test (T003-T012)
   - Each scenario → manual test (T035-T038)

4. **Ordering**:
   - Setup (T001-T002) → Tests (T003-T013) → Migration (T014) → Models (T015-T017) → Component (T018-T024) → Blade (T025-T028) → Polish (T029-T044)

---

## Validation Checklist

*GATE: Checked before task execution*

- [x] All contracts have corresponding tests (getClientsProperty, saveStatus, approveStatus, etc.)
- [x] All entities have model tasks (ClientStatusUpdate extended)
- [x] All tests come before implementation (T003-T013 before T014-T028)
- [x] Parallel tasks truly independent (marked [P] appropriately)
- [x] Each task specifies exact file path
- [x] No task modifies same file as another [P] task (blade tasks have moderate conflict, marked sequential)
- [x] Constitutional compliance (no new test files, TDD enforced, test lock verified)

---

**Total Tasks**: 44
**Estimated Completion Time**: 12-16 hours (for experienced Laravel/Livewire developer)
**Risk Level**: Low (extends existing system, well-tested, constitutional compliance enforced)

**Ready for Execution** ✅
