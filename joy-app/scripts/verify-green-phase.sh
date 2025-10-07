#!/bin/bash

# Verify GREEN Phase - All tests must pass after implementation
# Constitutional requirement: RED-GREEN-Refactor cycle

set -e

FEATURE_NAME="$1"
if [ -z "$FEATURE_NAME" ]; then
    echo "Usage: $0 <feature-name>"
    exit 1
fi

echo "🟢 Verifying GREEN Phase for: $FEATURE_NAME"
echo "==========================================="

# Check if we're on a TDD branch
CURRENT_BRANCH=$(git branch --show-current)
if [[ ! "$CURRENT_BRANCH" =~ ^tdd- ]]; then
    echo "⚠️  Warning: Not on a TDD branch (current: $CURRENT_BRANCH)"
fi

# Run full TDD cycle with Constitutional ordering
echo "Running full TDD test suite in Constitutional order..."

if ! scripts/test-tdd-cycle.sh; then
    echo "❌ GREEN Phase failed - tests are not passing"
    echo ""
    echo "This violates Constitutional requirements!"
    echo "Fix implementation until all tests pass."
    exit 1
fi

echo ""
echo "🎉 GREEN Phase verified successfully!"
echo "   All tests passing in Constitutional order"
echo ""
echo "Next steps:"
echo "1. Commit your working implementation: git add . && git commit -m '🟢 GREEN: Implement $FEATURE_NAME - all tests pass'"
echo "2. Refactor if needed while keeping tests green"
echo "3. Final commit: git commit -m '🔵 REFACTOR: Clean up $FEATURE_NAME implementation'"
echo "4. Merge to main branch"