#!/bin/bash

# Verify RED Phase - Tests must fail before implementation
# Constitutional requirement: RED-GREEN-Refactor cycle

set -e

FEATURE_NAME="$1"
if [ -z "$FEATURE_NAME" ]; then
    echo "Usage: $0 <feature-name>"
    exit 1
fi

echo "🔴 Verifying RED Phase for: $FEATURE_NAME"
echo "========================================="

# Check if we're on a TDD branch
CURRENT_BRANCH=$(git branch --show-current)
if [[ ! "$CURRENT_BRANCH" =~ ^tdd- ]]; then
    echo "⚠️  Warning: Not on a TDD branch (current: $CURRENT_BRANCH)"
    echo "   Consider using: scripts/red-green-check.sh $FEATURE_NAME"
fi

# Run tests and expect them to fail
echo "Running tests expecting failures..."

FAILED_TESTS=0

# Check Contract tests
if ls tests/Contract/*${FEATURE_NAME}* 1> /dev/null 2>&1; then
    echo "Testing Contract tests (should fail)..."
    if vendor/bin/phpunit tests/Contract/ --filter="$FEATURE_NAME" > /dev/null 2>&1; then
        echo "❌ Contract tests are passing - this violates RED phase!"
        exit 1
    else
        echo "✅ Contract tests failing as expected"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
fi

# Check Integration tests
if ls tests/Integration/*${FEATURE_NAME}* 1> /dev/null 2>&1; then
    echo "Testing Integration tests (should fail)..."
    if vendor/bin/phpunit tests/Integration/ --filter="$FEATURE_NAME" > /dev/null 2>&1; then
        echo "❌ Integration tests are passing - this violates RED phase!"
        exit 1
    else
        echo "✅ Integration tests failing as expected"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
fi

# Check E2E tests
if ls tests/Feature/*${FEATURE_NAME}* 1> /dev/null 2>&1; then
    echo "Testing E2E tests (should fail)..."
    if vendor/bin/phpunit tests/Feature/ --filter="$FEATURE_NAME" > /dev/null 2>&1; then
        echo "❌ E2E tests are passing - this violates RED phase!"
        exit 1
    else
        echo "✅ E2E tests failing as expected"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
fi

# Check Unit tests
if ls tests/Unit/*${FEATURE_NAME}* 1> /dev/null 2>&1; then
    echo "Testing Unit tests (should fail)..."
    if vendor/bin/phpunit tests/Unit/ --filter="$FEATURE_NAME" > /dev/null 2>&1; then
        echo "❌ Unit tests are passing - this violates RED phase!"
        exit 1
    else
        echo "✅ Unit tests failing as expected"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
fi

if [ $FAILED_TESTS -eq 0 ]; then
    echo "❌ No tests found for feature: $FEATURE_NAME"
    echo "Create tests first following Constitutional order!"
    exit 1
fi

echo ""
echo "🎉 RED Phase verified successfully!"
echo "   Found $FAILED_TESTS failing test suites"
echo ""
echo "Next steps:"
echo "1. Commit your failing tests: git add . && git commit -m '🔴 RED: Add failing tests for $FEATURE_NAME'"
echo "2. Implement minimal code to make tests pass"
echo "3. Run: scripts/verify-green-phase.sh $FEATURE_NAME"