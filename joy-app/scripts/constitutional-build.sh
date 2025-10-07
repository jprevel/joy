#!/bin/bash

# Constitutional Build Script
# Enforces Joy Constitution requirements: Tests-First, No Failed Tests

set -e

echo "🏛️  JOY CONSTITUTIONAL BUILD PROCESS"
echo "====================================="
echo "Enforcing Constitutional requirements:"
echo "  ✓ Test-First Development (TDD)"
echo "  ✓ No failed tests = no build"
echo "  ✓ Contract → Integration → Unit test order"
echo ""

# Track start time
start_time=$(date +%s)

# Constitution Article I & II: Test-First Development & Test-Before-Build
echo "📋 CONSTITUTIONAL REQUIREMENT: Tests Before Build"
echo "=================================================="

# Run Constitutional TDD cycle
echo "Running Constitutional test suite..."
if ! ./scripts/test-tdd-cycle.sh; then
    echo ""
    echo "❌ CONSTITUTIONAL VIOLATION: Tests failed"
    echo "🏛️  Article I & II violated: Test-First Development & Test-Before-Build"
    echo ""
    echo "Per Joy Constitution:"
    echo "  - Tests must pass BEFORE any build process"
    echo "  - No exceptions for failed tests"
    echo "  - No bypass mechanisms allowed"
    echo ""
    echo "Fix all failing tests before attempting build."
    exit 1
fi

echo ""
echo "✅ Constitutional Test Requirements Satisfied"
echo "  ✓ Contract tests passed"
echo "  ✓ Integration tests passed"
echo "  ✓ Unit tests passed"

# Constitution Article III: Build Process
echo ""
echo "🔨 CONSTITUTIONAL BUILD: Frontend Assets"
echo "========================================"

# Build frontend assets
if ! npm run build; then
    echo ""
    echo "❌ BUILD FAILED: Frontend asset compilation"
    echo ""
    echo "Fix build errors and try again."
    exit 1
fi

echo "✅ Frontend Assets Built Successfully"

# Constitution Article III: Final Validation
echo ""
echo "🔍 CONSTITUTIONAL REQUIREMENT: Post-Build Validation"
echo "===================================================="

# Verify critical files exist
CRITICAL_FILES=(
    "public/build/manifest.json"
    "public/css/filament/filament/app.css"
    "public/js/filament/filament/app.js"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo "❌ CRITICAL FILE MISSING: $file"
        echo "Build appears incomplete."
        exit 1
    fi
done

# Calculate total build time
end_time=$(date +%s)
duration=$((end_time - start_time))

echo ""
echo "🎉 CONSTITUTIONAL BUILD COMPLETE!"
echo "================================="
echo "✅ All Constitutional requirements satisfied:"
echo "  ✓ Test-First Development enforced"
echo "  ✓ All tests passed before build"
echo "  ✓ Constitutional test order followed"
echo "  ✓ Frontend assets compiled"
echo "  ✓ Build integrity verified"
echo ""
echo "Total build time: ${duration}s"
echo ""
echo "🏛️  Joy Constitution v1.0.0 compliance: ✅ VERIFIED"