# TDD Migration Guide: Constitutional Compliance

This guide helps migrate the Joy app to full Constitutional TDD compliance.

## 🎯 Goal: RED-GREEN-Refactor Cycle Enforcement

### Current State → Target State

| Aspect | Before | After |
|--------|--------|-------|
| Test Order | Mixed execution | Contract→Integration→E2E→Unit |
| RED Phase | No enforcement | Tests must fail first |
| Dependencies | High mock usage (230) | Prefer real dependencies |
| Git Commits | Implementation first | Tests first, then implementation |

## 🚀 Quick Start

### 1. New Feature Development (Constitutional Way)

```bash
# Start a new feature with TDD
composer new-feature -- user-profile-management

# This creates:
# - TDD branch: tdd-user-profile-management
# - Guides you through test creation in correct order

# Create failing tests in Constitutional order:
# 1. tests/Contract/UserProfileContractTest.php
# 2. tests/Integration/UserProfileIntegrationTest.php
# 3. tests/Feature/UserProfileE2ETest.php
# 4. tests/Unit/UserProfileUnitTest.php

# Verify RED phase (tests must fail)
./scripts/verify-red-phase.sh user-profile-management

# Implement minimal code to pass tests

# Verify GREEN phase (all tests pass)
./scripts/verify-green-phase.sh user-profile-management
```

### 2. Daily Development Workflow

```bash
# Run full Constitutional test suite
composer test-tdd

# Quick RED phase check for current feature
composer test-red

# Verify GREEN phase
composer test-green
```

## 📋 Migration Checklist

### Phase 1: Test Structure ✅ COMPLETE
- [x] Created `tests/Contract/` directory
- [x] Created `tests/Integration/` directory
- [x] Updated `phpunit.xml` with Constitutional test order
- [x] Added test execution scripts

### Phase 2: Existing Tests Migration

**High Priority** - Migrate tests with high mock usage:

1. **ContentItemServiceTest.php** (12 mock instances)
   - Current: Heavy mocking of ImageUploadService
   - Target: Use real file system, actual image uploads
   - Location: Move some tests to `tests/Integration/`

2. **AuditLogAnalyzerTest.php** (complex service interactions)
   - Current: May have service mocking
   - Target: Real database interactions
   - Location: Move complex scenarios to `tests/Integration/`

3. **MagicLinkValidatorTest.php** (security-critical)
   - Current: Potentially mocked validation
   - Target: Real validation with actual database
   - Location: Move security tests to `tests/Integration/`

### Phase 3: Establish Contract Tests

**Create missing contract tests:**

1. **ContentItemContract**
   ```php
   // tests/Contract/ContentItemContractTest.php
   // - Verify ContentItemService interface
   // - Test platform validation contracts
   // - Image upload contract requirements
   ```

2. **MagicLinkContract**
   ```php
   // tests/Contract/MagicLinkContractTest.php
   // - Security validation interface
   // - Expiration handling contract
   // - Access control contracts
   ```

3. **API Contracts** (if REST endpoints exist)
   ```php
   // tests/Contract/ApiContractTest.php
   // - Request/response schemas
   // - Authentication contracts
   // - Error response formats
   ```

### Phase 4: Git Workflow Integration

```bash
# Add pre-commit hook to enforce RED-GREEN cycle
cp scripts/pre-commit-tdd-check.sh .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

## 🔧 Tools and Scripts

### Available Scripts
- `./scripts/test-tdd-cycle.sh` - Full Constitutional test run
- `./scripts/red-green-check.sh <feature>` - Start TDD cycle
- `./scripts/verify-red-phase.sh <feature>` - Ensure tests fail
- `./scripts/verify-green-phase.sh <feature>` - Ensure tests pass

### Composer Commands
- `composer test-tdd` - Run Constitutional TDD cycle
- `composer test-red` - Quick RED phase verification
- `composer test-green` - GREEN phase verification

## 📊 Progress Tracking

### Migration Metrics
- [ ] **25%** - Test structure setup (✅ Complete)
- [ ] **50%** - High-mock tests migrated to real dependencies
- [ ] **75%** - Contract tests created for all services
- [ ] **100%** - Full Constitutional compliance + git workflow

### Success Criteria
1. ✅ All tests run in Constitutional order
2. ⏳ <10% mock usage (currently ~80% mocked)
3. ⏳ Contract tests exist for all service interfaces
4. ⏳ RED-GREEN cycle enforced via git hooks
5. ⏳ All new features follow Constitutional TDD

## 🎓 Constitutional Principles Refresher

### Testing Order (NON-NEGOTIABLE)
1. **Contract** - Interface and API contracts
2. **Integration** - Components with real dependencies
3. **E2E** - Full user workflows
4. **Unit** - Individual component logic

### RED-GREEN-Refactor Cycle
1. **🔴 RED** - Write failing tests first
2. **🟢 GREEN** - Minimal implementation to pass
3. **🔵 REFACTOR** - Clean up while keeping tests green

### Real Dependencies Preference
- ✅ Actual databases (RefreshDatabase)
- ✅ Real file systems
- ✅ Actual service instances
- ❌ Excessive mocking (only for external APIs)

---
*Based on Constitution v2.1.1 - See `/memory/constitution.md`*