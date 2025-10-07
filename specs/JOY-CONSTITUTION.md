# Joy Content Calendar Constitution

## Core Principles

### I. Test-First Development (NON-NEGOTIABLE)
**MANDATORY TDD**: All features must follow strict Test-Driven Development.

**RED-GREEN-Refactor Cycle Enforcement:**
- 🔴 **RED Phase**: Tests must be written FIRST and FAIL before any implementation
- 🟢 **GREEN Phase**: Write minimal code to make tests pass
- 🔵 **REFACTOR Phase**: Clean up code while keeping tests green

**Constitutional Order:** Contract → Integration → Unit tests must run in this order.

**Absolute Requirements:**
- No implementation code without failing tests first
- All git commits must show tests before implementation
- Build process MUST run tests first - failed tests fail the build
- No exceptions for "quick fixes" or "small changes"

### II. Test-Before-Build (NON-NEGOTIABLE)
**Build Pipeline Requirements:**
- Tests must run BEFORE any build process starts
- ANY test failure immediately fails the entire build
- No deployment/distribution without 100% test success
- No bypass mechanisms for failed tests

**Test Suite Execution Order:**
1. Contract tests (service interfaces, API contracts)
2. Integration tests (real dependencies, database, file system)
3. Unit tests (individual component logic)
4. End-to-End Role-Based tests (user workflow validation)

**Build Failure Policy:**
- Failed tests = failed build = no deployment
- No "todo" or "skip" tests in production branches
- All tests must be complete and passing

### III. Real Dependencies Preferred
**Integration Testing Requirements:**
- Use actual databases, not mocks (RefreshDatabase encouraged)
- Use real file systems for file operations
- Use actual service instances for component testing
- Mock only external APIs that are unavailable in test environment

**Mock Usage Limitations:**
- Maximum 10% of tests may use mocks
- Mocks only for external services (APIs, third-party integrations)
- Document justification for each mock usage

### IV. Service-Oriented Architecture
**Library-First Approach:**
- Every feature starts as a standalone service/library
- Services must be independently testable
- Clear interfaces and contracts required
- No direct app code - everything through services

**CLI Interface Requirements:**
- Every service exposes functionality via CLI when appropriate
- Support JSON and human-readable output formats
- Text I/O protocol: stdin/args → stdout, errors → stderr

### V. Observability & Audit
**Structured Logging Required:**
- All user actions must be logged with context
- Security events require comprehensive audit trails
- Frontend logs must stream to backend for unified monitoring
- Error context must be sufficient for debugging

**Audit Requirements:**
- Magic link access attempts logged
- Content creation/modification tracked
- Role changes and permission grants recorded
- Client data access monitored and logged

## Joy-Specific Requirements

### Content Management Standards
**Platform Support:**
- Facebook, Instagram, LinkedIn, Twitter, Blog
- Platform-specific validation and constraints
- Consistent scheduling and approval workflows

**Magic Link Security:**
- Secure token generation with expiration
- Access logging and monitoring
- Client workspace isolation enforcement

**Role-Based Access Control:**
- Admin: Full system access and user management
- Agency: Client content creation and management
- Client: View-only access via magic links

### Workflow Requirements
**Content Creation Flow:**
1. Agency creates content → Draft status
2. Content scheduled in calendar
3. Magic link generated for client review
4. Client approves/requests changes
5. Approved content moves to Scheduled status
6. Trello integration syncs cards

**Calendar Management:**
- Monthly grid view with content scheduling
- Role-based content visibility
- Platform filtering and status tracking
- Drag-and-drop rescheduling capabilities

### Role-Based End-to-End Testing (NON-NEGOTIABLE)
**Mandatory E2E Test Coverage:**
- ALL user roles must have comprehensive end-to-end test coverage
- Role-based access control must be verified through automated testing
- Security boundaries between roles must be tested on every deployment

**Required E2E Test Suites:**
1. **Admin Role Tests** (`role-admin-comprehensive.spec.ts`):
   - Full admin dashboard access and functionality
   - User and client management capabilities
   - Audit log access and management
   - System-wide content management permissions
   - Trello integration management
   - Access to all client calendar views

2. **Agency Role Tests** (`role-agency-comprehensive.spec.ts`):
   - Agency calendar access with team-specific client filtering
   - Content creation and management for assigned clients
   - Statusfaction access for weekly updates
   - Restricted access verification (no admin privileges)
   - Team workspace isolation enforcement

3. **Client Role Tests** (`role-client-comprehensive.spec.ts`):
   - Magic link access pattern validation
   - Client portal functionality via secure tokens
   - Content review and approval workflows
   - Workspace isolation per client token
   - Access restriction validation (no admin/agency access)

4. **Security Boundary Tests** (`role-security-comprehensive.spec.ts`):
   - Cross-role access control verification
   - Session isolation between different roles
   - CSRF protection validation
   - Input sanitization and XSS prevention
   - Magic link security and token validation

**E2E Test Execution Requirements:**
- Tests must run against live application with real database
- All role-based tests must pass before any deployment
- Security boundary tests are especially critical - NO EXCEPTIONS
- Failed role tests = immediate build failure

## Development Workflow

### Feature Development Process
1. **TDD Initiation**: `composer new-feature -- feature-name`
2. **RED Phase**: Create failing tests in Constitutional order
3. **Verification**: `scripts/verify-red-phase.sh feature-name`
4. **Implementation**: Write minimal code to pass tests
5. **GREEN Phase**: `scripts/verify-green-phase.sh feature-name`
6. **Refactor**: Clean code while maintaining green tests

### Git Workflow Requirements
**Commit Message Standards:**
- 🔴 RED: Add failing tests for [feature]
- 🟢 GREEN: Implement [feature] - all tests pass
- 🔵 REFACTOR: Clean up [feature] implementation

**Branch Protection:**
- All PRs require passing tests
- No direct commits to main branch
- Feature branches must follow `tdd-feature-name` pattern

### Build and Deployment
**Pre-Build Requirements:**
1. Run Contract and Integration tests in Constitutional order
2. All Contract and Integration tests must pass (no exceptions)
3. Unit tests must pass
4. All Role-Based End-to-End tests must pass (especially security boundary tests)
5. Magic link and authentication workflow tests must validate successfully

**Build Commands:**
```bash
# Constitutional TDD test run (required before build)
composer test-tdd

# Individual test phases
composer test-red     # Verify RED phase
composer test-green   # Verify GREEN phase

# Role-based E2E tests (MANDATORY before deployment)
npx playwright test role-admin-comprehensive
npx playwright test role-agency-comprehensive
npx playwright test role-client-comprehensive
npx playwright test role-security-comprehensive

# Full E2E test suite
npx playwright test tests/e2e/role-*.spec.ts

# OR use the constitutional E2E test runner
./scripts/run-role-e2e-tests.sh

# Build only runs after ALL tests pass
npm run build
```

## Quality Gates

### Test Coverage Requirements
- Contract tests for all service interfaces
- Integration tests for all database operations
- Unit tests for individual component logic
- End-to-End Role-Based tests for user workflows and security

### Performance Standards
- Calendar load time < 2 seconds
- Image upload processing < 10 seconds
- Magic link validation < 500ms
- Trello sync operations < 30 seconds

### Security Requirements
- All user inputs validated and sanitized
- Magic link tokens cryptographically secure
- Audit logs tamper-evident
- Client data isolation strictly enforced

## Governance

### Constitutional Supremacy
This Constitution supersedes all other development practices, coding standards, and project guidelines. No exceptions without formal amendment process.

### Amendment Process
1. Proposed changes must be documented
2. Impact assessment on existing codebase
3. Migration plan for compliance
4. Team approval required
5. Update all dependent documentation

### Compliance Monitoring
- All PRs must verify Constitutional compliance
- Regular audits of test coverage and TDD adherence
- Build pipeline enforces all requirements automatically
- No bypassing of Constitutional requirements

### Violation Consequences
- Failed builds for test violations
- PR rejection for TDD non-compliance
- Mandatory refactoring for architectural violations
- Code review escalation for security issues

---

**Version**: 1.1.0 | **Ratified**: 2025-09-29 | **Last Amended**: 2025-10-01

*This Constitution ensures the Joy Content Calendar maintains high quality, security, and maintainability through strict adherence to Test-Driven Development and proven software engineering principles.*