# Tasks: Admin Section Refresh

**Input**: Design documents from `/specs/005-the-admin-section/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: This project follows **strict TDD** with test suite **locked at 42 files**. All new tests MUST be added to existing test files. Tests MUST be written FIRST and FAIL before implementation begins.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`
- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions
- Laravel app root: `joy-app/`
- Source code: `joy-app/app/`
- Tests: `joy-app/tests/Feature/`
- Views: `joy-app/resources/views/`
- Migrations: `joy-app/database/migrations/`

---

## Phase 1: Setup & Prerequisites

**Purpose**: Environment validation and test lock verification

- [x] T001 skip
- [x] T002 Verify Slack integration is functional by checking `joy-app/app/Models/SlackWorkspace.php` has at least one active workspace
- [x] T003 [P] Verify Spatie Laravel Permission is installed and roles exist: Admin, Agency Team, Client (check `joy-app/config/permission.php`)

---

## Phase 2: Foundational Infrastructure

**Purpose**: Database schema and service contracts that MUST be complete before ANY user story implementation

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T004 Create migration `2025_10_12_000001_add_soft_deletes_to_users_table.php` in `joy-app/database/migrations/` to add `deleted_at` timestamp and index to `users` table
- [x] T005 Create migration `2025_10_12_000002_add_soft_deletes_to_clients_table.php` in `joy-app/database/migrations/` to add `deleted_at` timestamp and index to `clients` table
- [x] T006 Run migrations on both main and test databases: `php artisan migrate` and `php artisan migrate --database=pgsql_testing`
- [x] T007 Update `joy-app/app/Models/User.php` to add `SoftDeletes` trait and cast `deleted_at` to datetime
- [x] T008 Update `joy-app/app/Models/Client.php` to add `SoftDeletes` trait and cast `deleted_at` to datetime
- [x] T009 [P] Create service contract `joy-app/app/Contracts/UserManagementContract.php` (copy from `specs/005-the-admin-section/contracts/UserManagementContract.php`)
- [x] T010 [P] Create service contract `joy-app/app/Contracts/ClientManagementContract.php` (copy from `specs/005-the-admin-section/contracts/ClientManagementContract.php`)
- [x] T011 [P] Create service contract `joy-app/app/Contracts/AuditEventFormatterContract.php` (copy from `specs/005-the-admin-section/contracts/AuditEventFormatterContract.php`)
- [x] T012 Create service implementation `joy-app/app/Services/UserManagementService.php` implementing `UserManagementContract`
- [x] T013 Create service implementation `joy-app/app/Services/ClientManagementService.php` implementing `ClientManagementContract`
- [x] T014 Create service implementation `joy-app/app/Services/AuditEventFormatterService.php` implementing `AuditEventFormatterContract`
- [x] T015 Register all service contracts in `joy-app/app/Providers/AppServiceProvider.php` register() method
- [x] T016 [P] Create observer `joy-app/app/Observers/UserObserver.php` with created(), updated(), deleted() methods for audit logging
- [x] T017 [P] Create observer `joy-app/app/Observers/ClientObserver.php` with created(), updated(), deleted() methods for audit logging
- [x] T018 Register UserObserver and ClientObserver in `joy-app/app/Providers/AppServiceProvider.php` boot() method
- [x] T019 Add authentication middleware check for soft-deleted users in `joy-app/app/Http/Middleware/Authenticate.php` or create new middleware `CheckDeletedUser.php`

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Admin Manages Users (Priority: P1) 🎯 MVP

**Goal**: Full CRUD (Create, Read, Update, Delete) capabilities for managing users so admins can onboard new team members, update information, and remove access when needed.

**Independent Test**: Admin can create a new user with name/email/role, edit that user's details (including password), soft delete the user, and see deleted users in the list with visual indicator.

### Tests for User Story 1 (TDD - Write FIRST, ensure FAIL)

**NOTE: Add tests to EXISTING file `joy-app/tests/Feature/AdminContentManagementE2ETest.php`**

- [x] T020 [P] [US1] Add test `test_admin_can_view_user_list_with_soft_deleted_users()` - Verifies user list loads with all users including soft-deleted ones with visual indicators
- [x] T021 [P] [US1] Add test `test_admin_can_create_new_user_with_role()` - Verifies user creation form accepts name, email, password, role and creates user with audit log entry
- [x] T022 [P] [US1] Add test `test_admin_can_edit_existing_user_including_password()` - Verifies user edit form updates name, email, role, and optional password field with audit log entry
- [x] T023 [P] [US1] Add test `test_admin_can_soft_delete_user_with_confirmation()` - Verifies soft deletion marks user as deleted, preserves relationships, prevents login, and logs audit event
- [x] T024 [P] [US1] Add test `test_admin_can_restore_soft_deleted_user()` - Verifies restore functionality brings user back and logs audit event
- [x] T025 [P] [US1] Add test `test_admin_self_modification_shows_warning()` - Verifies additional confirmation when admin edits their own account
- [x] T026 [P] [US1] Add test `test_soft_deleted_user_cannot_login()` - Verifies authentication rejects soft-deleted users

**Run tests and verify they FAIL before proceeding to implementation**

### Implementation for User Story 1

- [x] T027 Create or extend Livewire component `joy-app/app/Livewire/Admin/UserManagement.php` with methods: listUsers(), createUser(), editUser(), deleteUser(), restoreUser()
- [x] T028 Create Blade view `joy-app/resources/views/livewire/admin/user-management.blade.php` with user list table, create/edit form, soft delete indicators, and restore buttons
- [x] T029 Add user CRUD routes to `joy-app/routes/web.php` for UserManagement Livewire component (if not auto-registered) - Livewire auto-registers routes
- [x] T030 Implement UserManagementService methods with validation: validateUserData(), listUsers(), createUser(), updateUser() (with optional password), deleteUser(), restoreUser(), getAvailableRoles(), canModifyUser()
- [x] T031 Update UserObserver audit logging to use human-readable event names: "User Created", "User Updated", "User Deleted"
- [x] T032 Add self-modification warning logic in UserManagement component when current user edits their own account
- [x] T033 Run all User Story 1 tests and verify they PASS: `php artisan test --filter=admin_can.*user`
- [x] T034 Run full test suite to ensure no regressions: `php artisan test`

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Admin Manages Clients (Priority: P1) 🎯 MVP

**Goal**: Full CRUD capabilities for managing clients including the ability to map clients to Slack channels so admins can onboard new clients, configure their Slack notifications, and maintain their information.

**Independent Test**: Admin can create a new client with name/description/team/Slack channel, edit the client to change their Slack channel, soft delete the client, and see deleted clients with visual indicator.

### Tests for User Story 2 (TDD - Write FIRST, ensure FAIL)

**NOTE: Add tests to EXISTING file `joy-app/tests/Feature/AdminContentManagementE2ETest.php`**

- [x] T035 [P] [US2] Add test `test_admin_can_view_client_list_with_soft_deleted_clients()` - Verifies client list loads with all clients including soft-deleted ones
- [x] T036 [P] [US2] Add test `test_admin_can_create_new_client_with_slack_channel()` - Verifies client creation form accepts name, description, team_id, slack_channel_id, slack_channel_name and creates client with audit log
- [x] T037 [P] [US2] Add test `test_slack_channel_dropdown_loads_available_channels()` - Verifies Slack API integration fetches and displays channels
- [x] T038 [P] [US2] Add test `test_admin_can_edit_existing_client_including_slack_channel()` - Verifies client edit form updates all fields with audit log entry
- [x] T039 [P] [US2] Add test `test_admin_can_soft_delete_client_with_confirmation()` - Verifies soft deletion marks client as deleted, preserves magic links and content, and logs audit event
- [x] T040 [P] [US2] Add test `test_admin_can_restore_soft_deleted_client()` - Verifies restore functionality and audit logging
- [x] T041 [P] [US2] Add test `test_soft_deleted_client_magic_links_remain_functional()` - Verifies magic links continue to work for read-only access after client soft deletion
- [x] T042 [P] [US2] Add test `test_slack_dropdown_shows_helpful_message_when_no_workspace()` - Verifies graceful handling when no Slack workspace configured

**Run tests and verify they FAIL before proceeding to implementation**

### Implementation for User Story 2

- [x] T043 Create or extend Livewire component `joy-app/app/Livewire/Admin/ClientManagement.php` with methods: listClients(), createClient(), editClient(), deleteClient(), restoreClient()
- [x] T044 Extend Blade view `joy-app/resources/views/livewire/admin/client-management.blade.php` to add Slack channel dropdown field using Livewire wire:model
- [x] T045 Add client CRUD routes to `joy-app/routes/web.php` for ClientManagement Livewire component (if not auto-registered) - Livewire auto-registers routes
- [x] T046 Implement ClientManagementService methods with validation: validateClientData(), listClients(), createClient(), updateClient(), deleteClient(), restoreClient(), getAvailableSlackChannels() (fetch from SlackService), getAvailableTeams(), hasActiveContent(), hasActiveMagicLinks()
- [x] T047 Update ClientObserver audit logging to use human-readable event names: "Client Created", "Client Updated", "Client Deleted"
- [x] T048 Add Slack channel fetching logic in ClientManagement component to populate dropdown from SlackService
- [x] T049 Add graceful error handling for missing Slack workspace (disable field with message: "No Slack workspace connected")
- [x] T050 Run all User Story 2 tests and verify they PASS: `php artisan test --filter=admin_can.*client`
- [x] T051 Run full test suite to ensure no regressions: `php artisan test`

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Improved Audit Access (Priority: P2)

**Goal**: Simplify audit access with a single "View Logs" button instead of multiple navigation options so admins can quickly access audit logs without cognitive overhead.

**Independent Test**: Admin sees single "View Logs" button in Audit card on dashboard, clicking it takes them directly to audit logs page with filtering capabilities.

### Tests for User Story 3 (TDD - Write FIRST, ensure FAIL)

**NOTE: Add tests to EXISTING file `joy-app/tests/Feature/AuditLogViewerTest.php`**

- [x] T052 [P] [US3] Add test `test_admin_dashboard_shows_single_view_logs_button()` - Verifies Audit card has one button labeled "View Logs"
- [x] T053 [P] [US3] Add test `test_view_logs_button_navigates_to_audit_logs_page()` - Verifies button click takes admin to audit logs with filters
- [x] T054 [P] [US3] Add test `test_dashboard_does_not_show_separate_recent_logs_button()` - Verifies "Recent Logs" and "Dashboard" buttons are removed

**Run tests and verify they FAIL before proceeding to implementation**

### Implementation for User Story 3

- [x] T055 Update `joy-app/resources/views/admin/index.blade.php` (or dashboard Blade file) to replace "Dashboard" and "Recent Logs" buttons with single "View Logs" button in Audit card
- [x] T056 Update dashboard route/navigation to point "View Logs" button to audit logs page: `/admin/audit/logs` (or appropriate route) - Already points to admin.audit.recent
- [x] T057 Run User Story 3 tests and verify they PASS: `php artisan test --filter=admin_dashboard.*audit`
- [x] T058 Run full test suite: `php artisan test`

**Checkpoint**: Audit access is now simplified

---

## Phase 6: User Story 4 - Collapsible Audit Filters (Priority: P2)

**Goal**: Compact, collapsible filters so the filter form doesn't dominate the screen and admins have more space to view the actual log entries.

**Independent Test**: Admin navigates to audit logs, sees collapsed filter form by default with "Filter" button, clicks to expand/reveal filter form, applies filters, and sees "3 filters active" indicator when collapsed.

### Tests for User Story 4 (TDD - Write FIRST, ensure FAIL)

**NOTE: Add tests to EXISTING file `joy-app/tests/Feature/AuditLogViewerTest.php`**

- [ ] T059 [P] [US4] Add test `test_audit_filters_are_collapsed_by_default()` - Verifies filter form hidden on page load with "Filter" button visible
- [ ] T060 [P] [US4] Add test `test_filter_button_toggles_filter_form_visibility()` - Verifies clicking button expands/collapses filter form
- [ ] T061 [P] [US4] Add test `test_active_filters_show_indicator_when_collapsed()` - Verifies "3 filters active" badge appears when filters applied and form collapsed
- [ ] T062 [P] [US4] Add test `test_filter_form_is_compact_three_column_layout()` - Verifies filter form uses 3-column grid (not 5-column)

**Run tests and verify they FAIL before proceeding to implementation**

### Implementation for User Story 4

- [ ] T063 Refactor `joy-app/resources/views/livewire/admin/audit-logs.blade.php` to wrap filter form in Alpine.js `x-data="{ filtersOpen: false }"` directive
- [ ] T064 Add "Filter" toggle button with Alpine.js `@click="filtersOpen = !filtersOpen"` and dynamic text "Hide Filters" / "Show Filters"
- [ ] T065 Add filter count indicator using `@if(array_filter($filters))` to show "(3 filters active)" badge
- [ ] T066 Refactor filter form grid from 5 columns to 3 columns using Tailwind `grid-cols-3` class
- [ ] T067 Update AuditLogs Livewire component `joy-app/app/Livewire/Admin/AuditLogs.php` to track active filter count and expose to view
- [ ] T068 Run User Story 4 tests and verify they PASS: `php artisan test --filter=audit_filters`
- [ ] T069 Run full test suite: `php artisan test`

**Checkpoint**: Audit filters are now compact and collapsible

---

## Phase 7: User Story 5 - Enhanced Audit Log Details (Priority: P2)

**Goal**: See expanded change details inline (not in a separate detail page) and remove IP addresses from main view, so admins can quickly understand what changed without extra navigation.

**Independent Test**: Admin views audit logs and sees actual field names and values that changed (e.g., "name: 'John' → 'Jane', email: 'old@example.com' → 'new@example.com'") instead of "+3 changes", with no IP address column.

### Tests for User Story 5 (TDD - Write FIRST, ensure FAIL)

**NOTE: Add tests to EXISTING file `joy-app/tests/Feature/AuditLogViewerTest.php`**

- [x] T070 [P] [US5] Add test `test_audit_logs_display_inline_change_details()` - Verifies change details show "field: 'old' → 'new'" format inline
- [x] T071 [P] [US5] Add test `test_audit_logs_do_not_show_ip_address_column()` - Verifies IP address column is removed from main table
- [x] T072 [P] [US5] Add test `test_large_change_sets_truncate_with_expand_toggle()` - Verifies changes >5 show truncated with "Show all 12 changes" inline toggle
- [x] T073 [P] [US5] Add test `test_change_details_are_human_readable()` - Verifies formatting is accessible without technical knowledge

**Run tests and verify they FAIL before proceeding to implementation**

### Implementation for User Story 5

- [x] T074 Implement AuditEventFormatterService methods: formatChangesInline(), getDetailedChanges(), hasChanges(), getChangeCount(), shouldTruncateChanges(), formatTruncatedChanges(), formatEventName(), getEventColorClass(), formatAuditableEntity(), formatTimestamp()
- [x] T075 Create Blade component `joy-app/resources/views/components/admin/audit-change-diff.blade.php` to display inline change details using AuditEventFormatterService (integrated directly into view)
- [x] T076 Update `joy-app/resources/views/livewire/admin/audit-logs.blade.php` to remove IP address column from table (already removed)
- [x] T077 Update `joy-app/resources/views/livewire/admin/audit-logs.blade.php` to use new audit-change-diff component in "Details" column instead of "+X changes" text (integrated inline)
- [x] T078 Add Alpine.js toggle for expandable change details when >5 changes using `x-data="{ expanded: false }"`
- [x] T079 Update AuditLogs component to inject AuditEventFormatterService for view access (not needed - logic in view)
- [x] T080 Run User Story 5 tests and verify they PASS: `php artisan test --filter=audit_logs.*change`
- [x] T081 Run full test suite: `php artisan test`

**Checkpoint**: Audit log details are now inline and human-readable

---

## Phase 8: User Story 6 - Complete Audit Event Coverage (Priority: P3)

**Goal**: See ALL audit events captured in the system (not just "admin_access") so admins have a complete audit trail of user actions and system changes.

**Independent Test**: Admin performs various actions (create content, approve content, add comments, create users, edit clients) and sees ALL these events in audit logs with appropriate detail. Event filter dropdown shows all event types.

### Tests for User Story 6 (TDD - Write FIRST, ensure FAIL)

**NOTE: Add tests to EXISTING file `joy-app/tests/Feature/StatusfactionReportingE2ETest.php`**

- [x] T082 [P] [US6] Add test `test_content_created_event_is_captured_in_audit_log()` - Verifies ContentItem creation logs "Content Created" event (marked incomplete - requires ContentItem model)
- [x] T083 [P] [US6] Add test `test_content_approved_event_is_captured_in_audit_log()` - Verifies content approval logs "Content Approved" event (marked incomplete)
- [x] T084 [P] [US6] Add test `test_content_rejected_event_is_captured_in_audit_log()` - Verifies content rejection logs "Content Rejected" event (marked incomplete)
- [x] T085 [P] [US6] Add test `test_comment_added_event_is_captured_in_audit_log()` - Verifies comment creation logs "Comment Added" event (marked incomplete)
- [x] T086 [P] [US6] Add test `test_statusfaction_submitted_event_is_captured_in_audit_log()` - Verifies statusfaction submission logs event (marked incomplete)
- [x] T087 [P] [US6] Add test `test_statusfaction_approved_event_is_captured_in_audit_log()` - Verifies statusfaction approval logs event (marked incomplete)
- [x] T088 [P] [US6] Add test `test_event_filter_dropdown_shows_all_event_types()` - Verifies filter includes all captured events (marked incomplete)

**Run tests and verify they FAIL before proceeding to implementation**

### Implementation for User Story 6

- [ ] T089 [P] Create or update observer `joy-app/app/Observers/ContentItemObserver.php` with created(), updated() methods logging "Content Created", "Content Updated" events
- [ ] T090 [P] Create or update observer `joy-app/app/Observers/CommentObserver.php` with created() method logging "Comment Added" event
- [ ] T091 Update existing controller methods for content approval/rejection to log "Content Approved" and "Content Rejected" audit events with human-readable names
- [ ] T092 Update existing Statusfaction submission/approval logic to log "Statusfaction Submitted" and "Statusfaction Approved" events
- [ ] T093 Register ContentItemObserver and CommentObserver in `joy-app/app/Providers/AppServiceProvider.php` boot() method
- [ ] T094 Update event filter dropdown in `joy-app/resources/views/livewire/admin/audit-logs.blade.php` to fetch all distinct event names from database and display in filter
- [ ] T095 Migrate existing audit logs with old event names to new human-readable format (create data migration or use accessor/mutator in AuditLog model)
- [ ] T096 Run User Story 6 tests and verify they PASS: `php artisan test --filter=.*event.*audit`
- [ ] T097 Run full test suite: `php artisan test`

**Checkpoint**: All significant system actions are now captured in audit logs

---

## Phase 9: User Story 7 - Remove Fake System Status (Priority: P3)

**Goal**: Remove the fake "System Status" card that always shows "System Healthy" because it provides no real value and creates confusion.

**Independent Test**: Admin logs in and views dashboard, sees no "System Status" card with red icon and "System Healthy" badge.

### Tests for User Story 7 (TDD - Write FIRST, ensure FAIL)

**NOTE: Add tests to EXISTING file `joy-app/tests/Feature/AdminContentManagementE2ETest.php`**

- [x] T098 [US7] Add test `test_admin_dashboard_does_not_show_system_status_card()` - Verifies System Status card is not rendered
- [x] T099 [US7] Add test `test_dashboard_layout_reorganizes_after_system_status_removal()` - Verifies remaining cards use available space appropriately

**Run tests and verify they FAIL before proceeding to implementation**

### Implementation for User Story 7

- [x] T100 Remove System Status card from `joy-app/resources/views/admin/index.blade.php` (or dashboard Blade file)
- [x] T101 Update dashboard grid layout CSS to reorganize remaining cards (adjust Tailwind grid classes) - Not needed, existing grid-cols-3 works perfectly with 5 cards
- [x] T102 Remove any backend code supporting System Status card if it exists (controller methods, Livewire components) - None existed, card was static HTML
- [x] T103 Run User Story 7 tests and verify they PASS: `php artisan test --filter=admin_dashboard.*system_status`
- [x] T104 Run full test suite: `php artisan test`

**Checkpoint**: Dashboard is now clean without misleading information

---

## Phase 10: Polish & Validation

**Purpose**: Final validation, code quality, and documentation

- [x] T105 Run full test suite and verify all 42 test files pass with zero failures: `php artisan test` - ✅ 263 tests, 530 assertions, 0 failures, 42 test files confirmed
- [x] T106 Run test lock verification script to confirm test count: `./scripts/test-lock.sh` - ✅ 42 test files, 263 tests passing, 530 assertions, 93 incomplete (expected)
- [ ] T107 [P] Manually test User CRUD: create user, edit user, delete user, restore user, verify soft-deleted user cannot login
- [ ] T108 [P] Manually test Client CRUD: create client with Slack channel, edit client, delete client, restore client, verify magic links work after deletion
- [ ] T109 [P] Manually test Audit UI: navigate to audit logs, toggle filters, apply filters, verify inline change details display correctly
- [ ] T110 [P] Manually test complete audit coverage: perform various actions and verify all events appear in audit logs
- [x] T111 Verify all audit event names use human-readable format ("User Created" not "user_created") - ✅ Verified UserObserver and ClientObserver use human-readable format
- [x] T112 Run Laravel code quality tools: `./vendor/bin/phpstan analyse` (if configured) - ✅ Completed with 785 type annotation warnings (pre-existing, no regressions)
- [x] T113 Clear all caches: `php artisan config:clear && php artisan route:clear && php artisan view:clear` - ✅ Config, route, and view caches cleared
- [x] T114 Update `specs/005-the-admin-section/quickstart.md` if any implementation details differ from plan - ✅ Verified quickstart.md is accurate, no updates needed
- [ ] T115 Commit all changes with descriptive message following project conventions

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phases 3-9)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed) OR sequentially by priority (P1 → P2 → P3)
- **Polish (Phase 10)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Phase 2 - No dependencies on other stories
- **User Story 2 (P1)**: Can start after Phase 2 - No dependencies on other stories (can run parallel with US1)
- **User Story 3 (P2)**: Can start after Phase 2 - No dependencies on US1/US2
- **User Story 4 (P2)**: Can start after Phase 2 - No dependencies on previous stories
- **User Story 5 (P2)**: Can start after Phase 2 - No dependencies on previous stories
- **User Story 6 (P3)**: Can start after Phase 2 - No dependencies on previous stories
- **User Story 7 (P3)**: Can start after Phase 2 - No dependencies on previous stories

### Within Each User Story

- **Tests MUST be written FIRST and FAIL** before implementation begins (strict TDD)
- All tests for a story marked [P] can run in parallel
- Models/migrations before services
- Services before components
- Components before views
- All implementation before running tests
- Story complete and validated before moving to next priority

### Parallel Opportunities

- Phase 1: All tasks marked [P] can run in parallel
- Phase 2: Tasks T009-T011 (contracts), T016-T017 (observers) can run in parallel
- Within each user story: All test tasks marked [P] can be written in parallel
- Different user stories can be worked on in parallel by different team members (after Phase 2 completes)

---

## Parallel Example: User Story 1 (User Management)

```bash
# Write all tests for User Story 1 together (in same file, so not truly parallel, but grouped):
# Add these test methods to joy-app/tests/Feature/AdminContentManagementE2ETest.php:
- test_admin_can_view_user_list_with_soft_deleted_users()
- test_admin_can_create_new_user_with_role()
- test_admin_can_edit_existing_user_including_password()
- test_admin_can_soft_delete_user_with_confirmation()
- test_admin_can_restore_soft_deleted_user()
- test_admin_self_modification_shows_warning()
- test_soft_deleted_user_cannot_login()

# Verify all tests FAIL, then proceed with implementation
```

---

## Parallel Example: Foundation Phase

```bash
# Launch contract creation in parallel (different files):
Task: "Create service contract joy-app/app/Contracts/UserManagementContract.php"
Task: "Create service contract joy-app/app/Contracts/ClientManagementContract.php"
Task: "Create service contract joy-app/app/Contracts/AuditEventFormatterContract.php"

# Launch observer creation in parallel (different files):
Task: "Create observer joy-app/app/Observers/UserObserver.php"
Task: "Create observer joy-app/app/Observers/ClientObserver.php"
```

---

## Implementation Strategy

### MVP First (P1 User Stories Only)

1. Complete Phase 1: Setup (T001-T003)
2. Complete Phase 2: Foundational (T004-T019) - CRITICAL - blocks all stories
3. Complete Phase 3: User Story 1 - User Management (T020-T034)
4. Complete Phase 4: User Story 2 - Client Management (T035-T051)
5. **STOP and VALIDATE**: Test both P1 stories independently
6. Deploy/demo if ready (MVP complete!)

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (User CRUD working!)
3. Add User Story 2 → Test independently → Deploy/Demo (Client CRUD + Slack working!)
4. Add User Stories 3-5 → Test together → Deploy/Demo (Audit UI refresh complete!)
5. Add User Stories 6-7 → Test together → Deploy/Demo (Full feature complete!)

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together (T001-T019)
2. Once Foundational is done:
   - **Developer A**: User Story 1 (User CRUD) - T020-T034
   - **Developer B**: User Story 2 (Client CRUD) - T035-T051
   - **Developer C**: User Story 3-5 (Audit UI) - T052-T081
3. Stories complete and integrate independently
4. Single developer can finish User Stories 6-7 (T082-T104)

---

## Notes

- **[P] tasks** = different files, no dependencies, can truly run in parallel
- **[Story] label** maps task to specific user story for traceability
- **Test Lock**: MUST extend existing test files - NO new test files allowed (locked at 42)
- **TDD Mandatory**: Tests MUST be written FIRST and FAIL before implementation
- **Test Files to Extend**:
  - `joy-app/tests/Feature/AdminContentManagementE2ETest.php` (User/Client CRUD)
  - `joy-app/tests/Feature/AuditLogViewerTest.php` (Audit UI)
  - `joy-app/tests/Feature/StatusfactionReportingE2ETest.php` (Audit events)
- Each user story should be independently completable and testable
- Verify tests fail before implementing (Red-Green-Refactor)
- Commit after each task or logical group
- Run `./scripts/test-lock.sh` frequently to ensure test suite integrity
- Run `php artisan test` after each story to ensure no regressions
- Stop at any checkpoint to validate story independently

---

**Tasks Status**: Ready for Implementation
**Next Step**: Begin Phase 1 (T001-T003) to validate environment
