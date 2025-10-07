# Joy Application Scripts

This directory contains helper scripts for the Joy application.

## Test Suite Lock 🔒

### `test-lock.sh`

**Purpose:** Enforces test suite stability by preventing new tests and ensuring all existing tests pass.

**Usage:**

```bash
./scripts/test-lock.sh
```

**What it does:**

1. **Validates Test Count** - Ensures exactly 42 test files exist
   - If more: Fails and reports new test files detected
   - If fewer: Warns that tests were deleted

2. **Runs All Tests** - Executes the full test suite (excluding incomplete tests)
   - Must pass with zero failures
   - Stops on first failure for fast feedback

**When to run:**

- ✅ Before starting any development work
- ✅ After making code changes
- ✅ Before committing code
- ✅ In CI/CD pipelines

**Exit codes:**

- `0` - Success (all tests pass, count correct)
- `1` - Failure (new tests detected or tests failing)

**Example output:**

```
🔒 Test Suite Lock Validation
==============================

📊 Checking test file count...
Expected: 42 test files
Current:  42 test files
✅ Test count valid

🧪 Running all tests (excluding incomplete)...

OK, but there were issues!
Tests: 213, Assertions: 393, PHPUnit Deprecations: 213, Incomplete: 86.

✅ All tests passed!

🎉 Test Suite Lock validation complete
```

## Test Suite Constitution

See [CLAUDE.md](../CLAUDE.md) for the complete test suite lock policy and development constitution.

**Key Rules:**

1. **NO NEW TEST FILES** - Test suite frozen at 42 files
2. **ALL TESTS MUST PASS** - Zero tolerance for failing tests
3. **RUN BEFORE CHANGES** - Always run `test-lock.sh` before coding
4. **INCOMPLETE TESTS OK** - 86 incomplete tests are allowed (for future work)

## Modifying the Lock

If you need to add new tests (with user approval):

1. Get explicit user approval
2. Add the new test file
3. Update `EXPECTED_TEST_COUNT` in `scripts/test-lock.sh`
4. Update the count in `CLAUDE.md`
5. Run `./scripts/test-lock.sh` to verify
6. Commit the changes together

## Troubleshooting

**"New test files detected!"**
- Check for accidentally created test files
- Remove them or get approval to increase the lock count

**"Tests failing!"**
- Fix the broken tests immediately
- Do not commit failing tests
- Run PHPUnit directly to debug: `./vendor/bin/phpunit --stop-on-failure`

**"Test files were deleted"**
- Verify no tests were accidentally removed
- Check git status to see what changed
- Restore deleted tests or update the lock count if intentional
