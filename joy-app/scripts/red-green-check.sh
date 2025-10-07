#!/bin/bash

# RED-GREEN-Refactor Cycle Enforcement
# Ensures tests fail first (RED phase) before implementation

set -e

FEATURE_NAME="$1"
if [ -z "$FEATURE_NAME" ]; then
    echo "Usage: $0 <feature-name>"
    echo "Example: $0 user-authentication"
    exit 1
fi

BRANCH_NAME="tdd-$FEATURE_NAME"

echo "🔴 Starting RED-GREEN-Refactor cycle for: $FEATURE_NAME"

# 1. Create feature branch
echo "Creating TDD branch: $BRANCH_NAME"
git checkout -b "$BRANCH_NAME" 2>/dev/null || git checkout "$BRANCH_NAME"

# 2. RED Phase - ensure tests fail
echo ""
echo "🔴 RED PHASE: Write failing tests first"
echo "======================================="
echo "1. Create your test files in the correct order:"
echo "   - tests/Contract/${FEATURE_NAME}ContractTest.php"
echo "   - tests/Integration/${FEATURE_NAME}IntegrationTest.php"
echo "   - tests/Feature/${FEATURE_NAME}E2ETest.php"
echo "   - tests/Unit/${FEATURE_NAME}UnitTest.php"
echo ""
echo "2. Run this command when ready to verify RED phase:"
echo "   scripts/verify-red-phase.sh $FEATURE_NAME"
echo ""
echo "Remember: Tests MUST fail first to satisfy Constitutional requirements!"