# Tasks: Update Joy Content Calendar System

**Input**: Design documents from `/specs/001-update-joy/`
**Prerequisites**: plan.md (required), research.md, data-model.md, contracts/

## Execution Flow (main)
```
1. Load plan.md from feature directory
   → Laravel 11 web application with Livewire 3, Filament admin panel
2. Load optional design documents:
   → data-model.md: 7 entities (User, Client, ContentItem, MagicLink, Comment, TrelloCard, AuditLog)
   → contracts/: 2 API specs (content-api.yaml, magic-link-api.yaml)
   → research.md: PHP 8.2+, Laravel 11, MySQL/PostgreSQL decisions
3. Generate tasks by category:
   → Setup: Laravel dependencies, PHPStan, testing setup
   → Tests: contract tests, integration tests for user scenarios
   → Core: Eloquent models, service classes, API controllers
   → Integration: migrations, Trello sync, audit logging
   → Polish: unit tests, performance optimization, documentation
4. Apply task rules:
   → Different files = mark [P] for parallel
   → Same file = sequential (no [P])
   → Tests before implementation (TDD)
5. Number tasks sequentially (T001, T002...)
```

## Format: `[ID] [P?] Description`
- **[P]**: Can run in parallel (different files, no dependencies)
- Include exact file paths in descriptions

## Path Conventions
- **Laravel app**: `joy-app/app/`, `joy-app/tests/`
- Following Laravel conventions and existing structure

## Phase 3.1: Setup
- [ ] T001 Update Laravel dependencies per research.md recommendations in composer.json
- [ ] T002 [P] Configure PHPStan level 5+ analysis in phpstan.neon
- [ ] T003 [P] Set up Laravel Telescope for debugging in config/telescope.php
- [ ] T004 [P] Configure additional test database in config/database.php

## Phase 3.2: Tests First (TDD) ⚠️ MUST COMPLETE BEFORE 3.3
**CRITICAL: These tests MUST be written and MUST FAIL before ANY implementation**

### Contract Tests (API Endpoints)
- [ ] T005 [P] Contract test POST /api/content in tests/Feature/Api/ContentCreateTest.php
- [ ] T006 [P] Contract test GET /api/content in tests/Feature/Api/ContentListTest.php
- [ ] T007 [P] Contract test PUT /api/content/{id} in tests/Feature/Api/ContentUpdateTest.php
- [ ] T008 [P] Contract test POST /api/content/{id}/comments in tests/Feature/Api/ContentCommentTest.php
- [ ] T009 [P] Contract test POST /api/magic-links in tests/Feature/Api/MagicLinkCreateTest.php
- [ ] T010 [P] Contract test GET /api/magic-links/{token} in tests/Feature/Api/MagicLinkAccessTest.php
- [ ] T011 [P] Contract test GET /magic/{token} in tests/Feature/MagicLinkClientAccessTest.php
- [ ] T012 [P] Contract test POST /magic/{token}/content/{id}/approve in tests/Feature/MagicLinkApprovalTest.php

### Integration Tests (User Scenarios)
- [ ] T013 [P] Integration test agency creates content for client review in tests/Feature/ContentCreationWorkflowTest.php
- [ ] T014 [P] Integration test client reviews and approves content in tests/Feature/ClientApprovalWorkflowTest.php
- [ ] T015 [P] Integration test calendar view with multi-platform content in tests/Feature/CalendarViewTest.php
- [ ] T016 [P] Integration test magic link security and expiration in tests/Feature/MagicLinkSecurityTest.php
- [ ] T017 [P] Integration test Trello comment synchronization in tests/Feature/TrelloIntegrationTest.php
- [ ] T018 [P] Integration test audit logging for all user actions in tests/Feature/AuditLoggingTest.php

## Phase 3.3: Database Schema (ONLY after tests are failing)
- [ ] T019 [P] Create users table migration in database/migrations/xxxx_create_users_table.php
- [ ] T020 [P] Create clients table migration in database/migrations/xxxx_create_clients_table.php
- [ ] T021 [P] Create content_items table migration in database/migrations/xxxx_create_content_items_table.php
- [ ] T022 [P] Create magic_links table migration in database/migrations/xxxx_create_magic_links_table.php
- [ ] T023 [P] Create comments table migration in database/migrations/xxxx_create_comments_table.php
- [ ] T024 [P] Create trello_cards table migration in database/migrations/xxxx_create_trello_cards_table.php
- [ ] T025 [P] Create audit_logs table migration in database/migrations/xxxx_create_audit_logs_table.php
- [ ] T026 [P] Create database indexes migration in database/migrations/xxxx_add_performance_indexes.php

## Phase 3.4: Eloquent Models (ONLY after migrations are complete)
- [ ] T027 [P] User model with relationships in app/Models/User.php
- [ ] T028 [P] Client model with relationships in app/Models/Client.php
- [ ] T029 [P] ContentItem model with relationships in app/Models/ContentItem.php
- [ ] T030 [P] MagicLink model with relationships in app/Models/MagicLink.php
- [ ] T031 [P] Comment model with relationships in app/Models/Comment.php
- [ ] T032 [P] TrelloCard model with relationships in app/Models/TrelloCard.php
- [ ] T033 [P] AuditLog model with relationships in app/Models/AuditLog.php

## Phase 3.5: Service Classes (ONLY after models are complete)
- [ ] T034 [P] ContentService for CRUD operations in app/Services/ContentService.php
- [ ] T035 [P] MagicLinkService for token management in app/Services/MagicLinkService.php
- [ ] T036 [P] CommentService for feedback handling in app/Services/CommentService.php
- [ ] T037 [P] TrelloSyncService for external integration in app/Services/TrelloSyncService.php
- [ ] T038 [P] AuditLogService for activity tracking in app/Services/AuditLogService.php
- [ ] T039 [P] CalendarViewService for content aggregation in app/Services/CalendarViewService.php

## Phase 3.6: API Controllers (ONLY after services are complete)
- [ ] T040 ContentController with create, list, update methods in app/Http/Controllers/Api/ContentController.php
- [ ] T041 CommentController with create method in app/Http/Controllers/Api/CommentController.php
- [ ] T042 MagicLinkController with create, access methods in app/Http/Controllers/Api/MagicLinkController.php
- [ ] T043 ClientAccessController for magic link frontend in app/Http/Controllers/ClientAccessController.php

## Phase 3.7: Livewire Components for UI
- [ ] T044 [P] CalendarComponent for monthly grid view in app/Livewire/CalendarComponent.php
- [ ] T045 [P] TimelineComponent for chronological view in app/Livewire/TimelineComponent.php
- [ ] T046 [P] ContentItemComponent for content display in app/Livewire/ContentItemComponent.php
- [ ] T047 [P] CommentComponent for client feedback in app/Livewire/CommentComponent.php
- [ ] T048 [P] ApprovalComponent for client actions in app/Livewire/ApprovalComponent.php

## Phase 3.8: Filament Admin Resources
- [ ] T049 [P] ContentItemResource for admin panel in app/Filament/Resources/ContentItemResource.php
- [ ] T050 [P] ClientResource for admin panel in app/Filament/Resources/ClientResource.php
- [ ] T051 [P] MagicLinkResource for admin panel in app/Filament/Resources/MagicLinkResource.php
- [ ] T052 [P] AuditLogResource for admin panel in app/Filament/Resources/AuditLogResource.php

## Phase 3.9: Integration Components
- [ ] T053 TrelloWebhookController for sync notifications in app/Http/Controllers/TrelloWebhookController.php
- [ ] T054 AuditLogObserver for automatic tracking in app/Observers/AuditLogObserver.php
- [ ] T055 MagicLinkMiddleware for client access in app/Http/Middleware/MagicLinkMiddleware.php
- [ ] T056 Route definitions for API and web routes in routes/api.php and routes/web.php

## Phase 3.10: Background Jobs and Queues
- [ ] T057 [P] SyncCommentToTrelloJob in app/Jobs/SyncCommentToTrelloJob.php
- [ ] T058 [P] CleanupExpiredMagicLinksJob in app/Jobs/CleanupExpiredMagicLinksJob.php
- [ ] T059 [P] SendMagicLinkEmailJob in app/Jobs/SendMagicLinkEmailJob.php

## Phase 3.11: Configuration and Middleware
- [ ] T060 [P] Platform configuration file in config/platforms.php
- [ ] T061 [P] Trello integration configuration in config/trello.php
- [ ] T062 CORS configuration for API access in config/cors.php

## Phase 3.12: Frontend Assets and Views
- [ ] T063 [P] Calendar view blade template in resources/views/calendar.blade.php
- [ ] T064 [P] Magic link access blade template in resources/views/magic-link.blade.php
- [ ] T065 [P] Tailwind CSS classes for platform colors in resources/css/app.css
- [ ] T066 JavaScript for drag-and-drop functionality in resources/js/calendar.js

## Phase 3.13: Polish and Optimization
- [ ] T067 [P] Unit tests for ContentService in tests/Unit/Services/ContentServiceTest.php
- [ ] T068 [P] Unit tests for MagicLinkService in tests/Unit/Services/MagicLinkServiceTest.php
- [ ] T069 [P] Unit tests for model validations in tests/Unit/Models/ContentItemTest.php
- [ ] T070 [P] Performance optimization for calendar queries in app/Services/CalendarViewService.php
- [ ] T071 [P] Database seeder for development data in database/seeders/DevelopmentSeeder.php
- [ ] T072 [P] Factory classes for testing in database/factories/
- [ ] T073 [P] Update API documentation in docs/api.md
- [ ] T074 Execute quickstart validation scenarios from quickstart.md
- [ ] T075 Run PHPStan analysis and fix any issues
- [ ] T076 Performance testing (sub-second response times)

## Dependencies
### Critical Dependencies
- Tests (T005-T018) MUST COMPLETE and FAIL before implementation (T019+)
- Migrations (T019-T026) before Models (T027-T033)
- Models (T027-T033) before Services (T034-T039)
- Services (T034-T039) before Controllers (T040-T043)
- Controllers before UI Components (T044-T048)

### Service Dependencies
- T034 (ContentService) blocks T040 (ContentController)
- T035 (MagicLinkService) blocks T042 (MagicLinkController), T055 (MagicLinkMiddleware)
- T037 (TrelloSyncService) blocks T053 (TrelloWebhookController), T057 (SyncCommentToTrelloJob)
- T038 (AuditLogService) blocks T054 (AuditLogObserver)

### UI Dependencies
- T040-T043 (Controllers) before T044-T048 (Livewire Components)
- T029 (ContentItem model) before T049 (ContentItemResource)
- T056 (Routes) blocks all HTTP functionality

## Parallel Execution Examples

### Phase 3.2: All Contract Tests (Run Together)
```bash
# Launch T005-T012 together:
Task: "Contract test POST /api/content in tests/Feature/Api/ContentCreateTest.php"
Task: "Contract test GET /api/content in tests/Feature/Api/ContentListTest.php"
Task: "Contract test PUT /api/content/{id} in tests/Feature/Api/ContentUpdateTest.php"
Task: "Contract test POST /api/content/{id}/comments in tests/Feature/Api/ContentCommentTest.php"
Task: "Contract test POST /api/magic-links in tests/Feature/Api/MagicLinkCreateTest.php"
Task: "Contract test GET /api/magic-links/{token} in tests/Feature/Api/MagicLinkAccessTest.php"
Task: "Contract test GET /magic/{token} in tests/Feature/MagicLinkClientAccessTest.php"
Task: "Contract test POST /magic/{token}/content/{id}/approve in tests/Feature/MagicLinkApprovalTest.php"
```

### Phase 3.3: All Migrations (Run Together)
```bash
# Launch T019-T026 together:
Task: "Create users table migration in database/migrations/xxxx_create_users_table.php"
Task: "Create clients table migration in database/migrations/xxxx_create_clients_table.php"
Task: "Create content_items table migration in database/migrations/xxxx_create_content_items_table.php"
Task: "Create magic_links table migration in database/migrations/xxxx_create_magic_links_table.php"
Task: "Create comments table migration in database/migrations/xxxx_create_comments_table.php"
Task: "Create trello_cards table migration in database/migrations/xxxx_create_trello_cards_table.php"
Task: "Create audit_logs table migration in database/migrations/xxxx_create_audit_logs_table.php"
Task: "Create database indexes migration in database/migrations/xxxx_add_performance_indexes.php"
```

### Phase 3.4: All Models (Run Together)
```bash
# Launch T027-T033 together:
Task: "User model with relationships in app/Models/User.php"
Task: "Client model with relationships in app/Models/Client.php"
Task: "ContentItem model with relationships in app/Models/ContentItem.php"
Task: "MagicLink model with relationships in app/Models/MagicLink.php"
Task: "Comment model with relationships in app/Models/Comment.php"
Task: "TrelloCard model with relationships in app/Models/TrelloCard.php"
Task: "AuditLog model with relationships in app/Models/AuditLog.php"
```

## Notes
- [P] tasks = different files, no dependencies between them
- Verify ALL tests fail before implementing ANY functionality (TDD requirement)
- Run `php artisan test` after each phase to ensure tests pass
- Commit after completing each phase
- Use Laravel conventions and existing Joy app structure
- Follow PHPStan level 5+ standards throughout implementation

## Task Generation Rules Applied
1. **From Contracts**: 8 contract tests generated from content-api.yaml and magic-link-api.yaml
2. **From Data Model**: 7 entity models + migrations for each entity
3. **From User Stories**: 6 integration tests covering all quickstart scenarios
4. **Laravel Structure**: Services, Controllers, Livewire components, Filament resources
5. **TDD Ordering**: All tests before any implementation
6. **Parallel Marking**: Independent files marked [P], dependent files sequential

## Validation Checklist ✅
- [x] All contracts have corresponding tests (T005-T012)
- [x] All entities have model tasks (T027-T033)
- [x] All tests come before implementation (T005-T018 before T019+)
- [x] Parallel tasks truly independent (different files, no shared dependencies)
- [x] Each task specifies exact file path
- [x] No task modifies same file as another [P] task
- [x] Laravel conventions followed throughout
- [x] Existing Joy app structure respected