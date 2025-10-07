#!/bin/bash

# TDD Cycle Test Execution Script
# Enforces Constitutional test order: Contract→Integration→Unit

set -e

echo "🔴 RED PHASE: Running TDD Cycle..."
echo "Testing in Constitutional order: Contract→Integration→Unit"

# Track start time
start_time=$(date +%s)

# Phase 1: Contract Tests (MUST run first)
echo ""
echo "📋 Phase 1: Contract Tests"
echo "============================"
if ! vendor/bin/phpunit --testsuite=Contract --stop-on-failure; then
    echo "❌ Contract tests failed - stopping TDD cycle"
    exit 1
fi

# Phase 2: Integration Tests
echo ""
echo "🔗 Phase 2: Integration Tests"
echo "=============================="
if ! vendor/bin/phpunit --testsuite=Integration --stop-on-failure; then
    echo "❌ Integration tests failed - stopping TDD cycle"
    exit 1
fi

# Phase 3: Unit Tests (run last)
echo ""
echo "🧪 Phase 3: Unit Tests"
echo "======================"
if ! vendor/bin/phpunit --testsuite=Unit --stop-on-failure; then
    echo "❌ Unit tests failed - stopping TDD cycle"
    exit 1
fi

# Calculate duration
end_time=$(date +%s)
duration=$((end_time - start_time))

echo ""
echo "✅ TDD Cycle Complete!"
echo "Total duration: ${duration}s"
echo "All Constitutional requirements satisfied:"
echo "  ✓ Contract tests passed first"
echo "  ✓ Integration tests passed"
echo "  ✓ Unit tests passed last"