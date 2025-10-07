#!/bin/bash

# Test Suite Lock Script
# Ensures no new tests are written and all existing tests pass

set -e

EXPECTED_TEST_COUNT=42
CURRENT_TEST_COUNT=$(find tests -name "*Test.php" -type f | wc -l | tr -d ' ')

echo "🔒 Test Suite Lock Validation"
echo "=============================="
echo ""

# Check 1: Verify test count hasn't increased
echo "📊 Checking test file count..."
echo "Expected: $EXPECTED_TEST_COUNT test files"
echo "Current:  $CURRENT_TEST_COUNT test files"

if [ "$CURRENT_TEST_COUNT" -gt "$EXPECTED_TEST_COUNT" ]; then
    echo "❌ FAILED: New test files detected!"
    echo "   Test suite is locked. No new test files allowed."
    echo "   Remove new test files or update EXPECTED_TEST_COUNT in scripts/test-lock.sh"
    exit 1
elif [ "$CURRENT_TEST_COUNT" -lt "$EXPECTED_TEST_COUNT" ]; then
    echo "⚠️  WARNING: Test files were deleted"
    echo "   Expected $EXPECTED_TEST_COUNT but found $CURRENT_TEST_COUNT"
    echo "   This may indicate missing tests."
fi

echo "✅ Test count valid"
echo ""

# Check 2: Run all tests and ensure they pass
echo "🧪 Running all tests (excluding incomplete)..."
echo ""

./vendor/bin/phpunit --exclude-group incomplete --stop-on-failure

echo ""
echo "✅ All tests passed!"
echo ""
echo "🎉 Test Suite Lock validation complete"
