# Implementation Plan: Clean Code Refactoring

<!-- VARIANT:sh - Run `joy-app/spec-kit/scripts/bash/update-agent-context.sh claude` for Claude Code -->

**Branch**: `001-clean-code-refactoring` | **Date**: 2025-10-02 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-clean-code-refactoring/spec.md`

## Execution Flow (/plan command scope)
```
1. Load feature spec from Input path
   → If not found: ERROR "No feature spec at {path}"
2. Fill Technical Context (scan for NEEDS CLARIFICATION)
   → Detect Project Type from context (web=frontend+backend, mobile=app+api)
   → Set Structure Decision based on project type
3. Evaluate Constitution Check section below
   → If violations exist: Document in Complexity Tracking
   → If no justification possible: ERROR "Simplify approach first"
   → Update Progress Tracking: Initial Constitution Check
4. Execute Phase 0 → research.md
   → If NEEDS CLARIFICATION remain: ERROR "Resolve unknowns"
5. Execute Phase 1 → contracts, data-model.md, quickstart.md, agent-specific template file (e.g., `CLAUDE.md` for Claude Code, `.github/copilot-instructions.md` for GitHub Copilot, or `GEMINI.md` for Gemini CLI).
6. Re-evaluate Constitution Check section
   → If new violations: Refactor design, return to Phase 1
   → Update Progress Tracking: Post-Design Constitution Check
7. Plan Phase 2 → Describe task generation approach (DO NOT create tasks.md)
8. STOP - Ready for /tasks command
```

**IMPORTANT**: The /plan command STOPS at step 7. Phases 2-4 are executed by other commands:
- Phase 2: /tasks command creates tasks.md
- Phase 3-4: Implementation execution (manual or via tools)

## Summary

**Primary Requirement**: Refactor Joy application codebase to comply with Uncle Bob Martin's Clean Code principles, improving maintainability, readability, and testability.

**Technical Approach**: Incremental refactoring in 5 phases:
1. Extract duplicate authentication/authorization to middleware (170+ lines eliminated)
2. Refactor controllers to use service layer (reduce from 220→<100 lines avg)
3. Complete service layer implementation (consistent business logic)
4. Implement API Resources and Form Requests (consistent responses/validation)
5. Final cleanup (naming, static analysis level 8)

**Key Metrics**: Reduce avg function length 28→<10 lines, eliminate 90%+ code duplication, achieve 80%+ test coverage, maintain performance benchmarks.

## Technical Context
**Language/Version**: PHP 8.2+ (using modern PHP features)
**Primary Dependencies**: Laravel 12.x, Filament 4.x, Livewire 3.x, Spatie Laravel Permission, Larastan
**Storage**: MySQL/PostgreSQL (existing database schema, no changes required)
**Testing**: PHPUnit 11.5+, Playwright (E2E), PHPStan level 8 (static analysis)
**Target Platform**: Web application (TALL stack: Tailwind, Alpine.js, Laravel, Livewire)
**Project Type**: Web (single Laravel monolith with Livewire components)
**Performance Goals**: Maintain current response times (±5%), same or fewer DB queries
**Constraints**: No breaking API changes, preserve TALL stack architecture, backward compatible
**Scale/Scope**: 117 PHP files, ~20k LOC, 25+ refactoring tasks, 5-week timeline

## Constitution Check
*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Simplicity**:
- Projects: 1 (Laravel monolith - existing structure maintained) ✅
- Using framework directly? YES (Laravel patterns: Middleware, Resources, Form Requests) ✅
- Single data model? YES (existing models preserved, no DTOs - API Resources for serialization) ✅
- Avoiding patterns? Partially - using Repository pattern sparingly (only where justified: ContentItemRepository for complex queries) ⚠️

**Architecture**:
- EVERY feature as library? N/A (refactoring existing Laravel app, not building libraries)
- Libraries listed: N/A (this is code quality improvement, not new libraries)
- CLI per library: N/A
- Library docs: N/A

**Testing (NON-NEGOTIABLE)**:
- RED-GREEN-Refactor cycle enforced? YES (characterization tests first, then refactor, verify tests pass) ✅
- Git commits show tests before implementation? YES (Phase 1: tests, Phase 2: implementation) ✅
- Order: Contract→Integration→E2E→Unit strictly followed? YES (API contracts → feature tests → unit tests) ✅
- Real dependencies used? YES (actual database for integration tests) ✅
- Integration tests for: New middleware, refactored controllers, service layer ✅
- FORBIDDEN: Implementation before test, skipping RED phase ✅

**Observability**:
- Structured logging included? YES (Laravel Telescope already installed, AuditService exists) ✅
- Frontend logs → backend? YES (existing audit logging system) ✅
- Error context sufficient? YES (custom exceptions with detailed context planned) ✅

**Versioning**:
- Version number assigned? YES (API v2.0.0 for refactored endpoints) ✅
- BUILD increments on every change? YES (semantic versioning followed) ✅
- Breaking changes handled? YES (API versioning strategy, v1 maintained during transition) ✅

## Project Structure

### Documentation (this feature)
```
specs/[###-feature]/
├── plan.md              # This file (/plan command output)
├── research.md          # Phase 0 output (/plan command)
├── data-model.md        # Phase 1 output (/plan command)
├── quickstart.md        # Phase 1 output (/plan command)
├── contracts/           # Phase 1 output (/plan command)
└── tasks.md             # Phase 2 output (/tasks command - NOT created by /plan)
```

### Source Code (repository root)
```
joy-app/                      # Laravel application root
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Refactored controllers (<100 lines each)
│   │   ├── Middleware/       # NEW: EnsureAuthenticated, ResolveClientAccess
│   │   ├── Requests/         # NEW: Form Requests for validation
│   │   └── Resources/        # NEW: API Resources for responses
│   ├── Services/             # Enhanced service layer
│   │   ├── CalendarService.php
│   │   ├── CalendarStatisticsService.php
│   │   ├── ContentItemService.php (enhanced)
│   │   ├── ContentItemStatusManager.php (NEW)
│   │   ├── ClientAccessResolver.php (NEW)
│   │   ├── TrelloApiClient.php (extracted)
│   │   ├── TrelloCardService.php (extracted)
│   │   ├── TrelloWebhookService.php (extracted)
│   │   └── TrelloSyncService.php (extracted)
│   ├── Repositories/         # Data access layer
│   │   ├── ContentItemRepository.php
│   │   └── Contracts/
│   ├── Models/               # Eloquent models (existing)
│   └── Livewire/            # Livewire components (unchanged)
├── tests/
│   ├── Unit/                # Service layer unit tests
│   ├── Feature/             # HTTP integration tests
│   ├── Integration/         # Service + DB integration tests
│   └── e2e/                 # Playwright end-to-end tests
└── specs/
    └── 001-clean-code-refactoring/
        ├── spec.md
        ├── plan.md
        ├── research.md
        ├── data-model.md
        ├── quickstart.md
        ├── contracts/
        └── tasks.md (created by /tasks)
```

**Structure Decision**: Web application (Laravel monolith) - existing structure enhanced, not replaced

## Phase 0: Outline & Research ✅ COMPLETED

**Completed Activities**:
1. ✅ Analyzed entire codebase (117 PHP files) for Clean Code violations
2. ✅ Identified 20 critical refactoring candidates with severity ratings
3. ✅ Researched Clean Code principles application in Laravel context
4. ✅ Evaluated refactoring strategies and best practices
5. ✅ Assessed risks and created mitigation strategies
6. ✅ Defined success metrics and validation approach

**Key Findings** (detailed in research.md):
- Function Length: 25+ violations (avg 28 lines, max 73 lines)
- Code Duplication: 170+ occurrences (auth/authorization logic)
- SRP Violations: 6 major classes (CalendarController 486 lines, TrelloService 335 lines)
- Incomplete Service Layer: Controllers contain business logic
- Primitive Obsession: 40+ manual array response building

**Output**: ✅ research.md created with comprehensive analysis and refactoring strategy

## Phase 1: Design & Contracts ✅ COMPLETED

**Completed Activities**:
1. ✅ Created comprehensive data model design (data-model.md):
   - Documented all entities (ContentItem, Client, User, Comment, MagicLink)
   - Designed 9 new service classes with clear responsibilities
   - Defined value objects (DateRange, CalendarMonth, CalendarStatistics)
   - Created middleware specs (EnsureAuthenticated, ResolveClientAccess)
   - Designed API Resources and Form Requests
   - Documented state transitions and business rules

2. ✅ Generated API contracts (OpenAPI 3.0):
   - `/contracts/calendar-api.yml` - Calendar endpoints (month, range, stats)
   - `/contracts/content-item-api.yml` - Content CRUD + status management
   - Defined request/response schemas
   - Documented error responses

3. ✅ Created quickstart.md with implementation guide:
   - Phase-by-phase implementation steps
   - Code examples for each component
   - Testing strategy per phase
   - Validation checklist
   - Team training plan

4. ✅ Designed service architecture:
   - CalendarService, CalendarStatisticsService
   - ContentItemStatusManager, ClientAccessResolver
   - TrelloApiClient, TrelloCardService, TrelloWebhookService, TrelloSyncService
   - Repository pattern for complex queries

**Output**: ✅ data-model.md, contracts/*, quickstart.md all created

## Phase 2: Task Planning Approach
*This section describes what the /tasks command will do - DO NOT execute during /plan*

**Task Generation Strategy**:
The /tasks command will generate implementation tasks based on the 5-phase refactoring strategy:

**Phase 1: Middleware Extraction (Priority: CRITICAL)**
- Task: Create EnsureAuthenticated middleware with tests [P]
- Task: Create ResolveClientAccess middleware with tests [P]
- Task: Create ClientAccessResolver service with tests [P]
- Task: Register middleware in Kernel.php
- Task: Update routes to use middleware
- Task: Refactor 12+ controllers to remove duplicate auth code
- Task: Run test suite and verify all tests pass

**Phase 2: Controller Refactoring (Priority: HIGH)**
- Task: Create CalendarService with unit tests [P]
- Task: Create CalendarStatisticsService with unit tests [P]
- Task: Create ContentItemResource API resource [P]
- Task: Create CalendarResource API resource [P]
- Task: Create ClientResource API resource [P]
- Task: Refactor CalendarController to use services (486→60 lines)
- Task: Refactor ContentItemController to use services (308→80 lines)
- Task: Create integration tests for refactored controllers
- Task: Run full test suite

**Phase 3: Complete Service Layer (Priority: HIGH)**
- Task: Create StoreContentItemRequest Form Request [P]
- Task: Create UpdateContentItemRequest Form Request [P]
- Task: Create UpdateStatusRequest Form Request [P]
- Task: Create ContentItemStatusManager service with tests
- Task: Enhance ContentItemService with CRUD methods
- Task: Create ContentItemRepository with tests
- Task: Update controllers to use completed service layer

**Phase 4: Trello Service Split (Priority: MEDIUM)**
- Task: Create TrelloApiClient service [P]
- Task: Create TrelloCardService [P]
- Task: Create TrelloWebhookService [P]
- Task: Create TrelloSyncService [P]
- Task: Refactor TrelloService to use focused services
- Task: Update TrelloController
- Task: Create unit tests for all Trello services

**Phase 5: Final Cleanup (Priority: LOW)**
- Task: Rename generic variables ($data, $result, $info)
- Task: Replace magic numbers with Symfony Response constants
- Task: Fix misleading method names (getCurrentUserRole)
- Task: Achieve PHPStan level 8
- Task: Run Laravel Pint for code formatting
- Task: Generate final coverage report (target: 80%+)

**Ordering Strategy**:
1. **Test-First**: Characterization tests → refactor → verify tests pass
2. **Dependency Order**: Middleware → Services → Controllers → Resources
3. **Risk-Based**: Critical auth logic first, low-risk cleanup last
4. **Parallel Execution**: Mark [P] for independent tasks (services, resources, tests)

**Estimated Output**: 30-35 numbered, ordered, dependency-aware tasks in tasks.md

**Task Template Format**:
```
## Task N: [Task Name] [Priority] [P if parallel]

**Description**: Clear description of what to implement
**Files to Create/Modify**: List of file paths
**Dependencies**: Task numbers that must complete first
**Acceptance Criteria**:
- [ ] Specific, testable criteria
- [ ] Tests passing
**Estimated Effort**: S/M/L (Small/Medium/Large)
```

**IMPORTANT**: This phase is executed by the /tasks command, NOT by /plan

## Phase 3+: Future Implementation
*These phases are beyond the scope of the /plan command*

**Phase 3**: Task execution (/tasks command creates tasks.md)  
**Phase 4**: Implementation (execute tasks.md following constitutional principles)  
**Phase 5**: Validation (run tests, execute quickstart.md, performance validation)

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Repository pattern (ContentItemRepository) | Complex date range queries, statistics aggregation, multiple eager loading scenarios | Direct Eloquent queries in controllers violate SRP, make testing harder, scatter data access logic across 12+ controllers |


## Progress Tracking
*This checklist is updated during execution flow*

**Phase Status**:
- [x] Phase 0: Research complete (/plan command) ✅
- [x] Phase 1: Design complete (/plan command) ✅
- [x] Phase 2: Task planning complete (/plan command - describe approach only) ✅
- [x] Phase 3: Tasks generated (/tasks command) ✅ - 55 tasks created
- [ ] Phase 4: Implementation complete - Ready to execute tasks.md
- [ ] Phase 5: Validation passed

**Gate Status**:
- [x] Initial Constitution Check: PASS ✅
- [x] Post-Design Constitution Check: PASS ✅
- [x] All NEEDS CLARIFICATION resolved ✅ (No unknowns - existing codebase)
- [x] Complexity deviations documented ✅ (Repository pattern justified)

---
*Based on Constitution v2.1.1 - See `/memory/constitution.md`*
