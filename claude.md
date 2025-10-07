# Joy - MajorMajor suite of applications

This directory contains the project documentation for Joy, a content calendar management system for MajorMajor Digital Marketing.

## Documentation Structure

- [Functional Requirements (FRD)](./FRD.md) - Detailed functional requirements and system specifications
- [BDD Test Scenarios](./BDD.md) - Behavior-driven development scenarios in Gherkin format
- [User Stories](./user-stories.md) - User-centered requirements and acceptance criteria
- [Entity Relationship Diagram (ERD)](./ERD.md) - Database schema and entity relationships

## Project Overview

Joy is a web application built with the TALL stack (Tailwind, Alpine.js, Laravel, Livewire) + Filament for managing client content calendars. The system provides:

- Monthly calendar and timeline views for content management
- Magic link sharing for client access without login
- Client review, commenting, and approval workflows
- Integration with Trello for comment synchronization
- Multi-platform content variants (Facebook, Instagram, LinkedIn, Blog)

## Key Features

- **Role-based access**: Admin, Agency Team, and Client roles with appropriate permissions
- **Workspace isolation**: Each client gets their own workspace
- **Magic link authentication**: Secure, time-limited access for clients
- **Real-time synchronization**: Comments sync to Trello cards automatically
- **Audit trail**: Complete activity logging for compliance and tracking
- **Responsive design**: Works across desktop and mobile devices

## Technical Stack

- **Frontend**: Tailwind CSS, Alpine.js, Livewire
- **Backend**: Laravel with Filament admin panel
- **Database**: MySQL/PostgreSQL (Laravel compatible)
- **Integrations**: Trello API, Slack (via Trello)
- **Deployment**: Web-based responsive application

## Development Constitution

### Test Suite Lock 🔒

**CRITICAL:** The test suite is **LOCKED** as of 2025-10-06. This is a hard requirement for application stability.

#### Rules:

1. **NO NEW TEST FILES** - The test suite is frozen at **42 test files**
   - Do not create new `*Test.php` files
   - Do not add new test classes
   - Exception: Only with explicit user approval

2. **ALL TESTS MUST PASS** - Zero tolerance for failing tests
   - Run `./scripts/test-lock.sh` before any code changes
   - All existing tests must pass (excluding incomplete tests)
   - Fix broken tests immediately - do not commit failing tests

3. **Pre-Development Check** - Before making ANY code changes:
   ```bash
   ./scripts/test-lock.sh
   ```
   - This validates test count and runs the full test suite
   - If this fails, STOP and fix tests before proceeding

4. **Incomplete Tests** - 23 incomplete tests are marked and allowed
   - These are for future implementation
   - Do not remove incomplete test markers
   - Do not implement these without user approval

#### Enforcement:

- The test lock script (`scripts/test-lock.sh`) enforces these rules
- Run this script before starting work on the codebase
- Treat test failures as blocking issues - nothing ships with failing tests

#### Why This Matters:

- Ensures code stability and prevents regressions
- Provides confidence when refactoring
- Acts as living documentation of expected behavior
- Prevents test suite bloat and maintenance burden
