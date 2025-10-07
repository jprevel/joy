#!/bin/bash

# Joy Content Calendar - Role-Based End-to-End Test Runner
# This script runs all mandatory role-based e2e tests as required by the Constitution

set -e  # Exit on any error

echo "🔥 Joy Constitution - Role-Based E2E Test Suite"
echo "==============================================="
echo "Running mandatory role-based end-to-end tests..."
echo ""

# Check if Playwright is installed
if ! command -v npx &> /dev/null; then
    echo "❌ ERROR: npx not found. Please install Node.js and npm."
    exit 1
fi

# Check if we're in the correct directory
if [ ! -f "package.json" ]; then
    echo "❌ ERROR: package.json not found. Please run this script from the project root."
    exit 1
fi

# Check if application is running
echo "🔍 Checking if application is running..."
if curl -s http://localhost:8000 >/dev/null; then
    APP_URL="http://localhost:8000"
    echo "✅ Application found on localhost:8000"
elif curl -s http://localhost:8001 >/dev/null; then
    APP_URL="http://localhost:8001"
    echo "✅ Application found on localhost:8001"
else
    echo "❌ ERROR: Application not running on localhost:8000 or localhost:8001"
    echo "   Please start the application with: php artisan serve"
    exit 1
fi

echo "✅ Application is running"
echo ""

# Set test environment
export NODE_ENV=test

echo "🧪 Running Role-Based E2E Tests (Constitutional Requirements)"
echo "============================================================="

# Track overall success
OVERALL_SUCCESS=true

# Test 1: Admin Role Comprehensive Tests
echo ""
echo "1️⃣  Admin Role Tests (role-admin-comprehensive.spec.ts)"
echo "------------------------------------------------------"
if npx playwright test tests/e2e/role-admin-comprehensive.spec.ts; then
    echo "✅ Admin role tests PASSED"
else
    echo "❌ Admin role tests FAILED"
    OVERALL_SUCCESS=false
fi

# Test 2: Agency Role Comprehensive Tests
echo ""
echo "2️⃣  Agency Role Tests (role-agency-comprehensive.spec.ts)"
echo "--------------------------------------------------------"
if npx playwright test tests/e2e/role-agency-comprehensive.spec.ts; then
    echo "✅ Agency role tests PASSED"
else
    echo "❌ Agency role tests FAILED"
    OVERALL_SUCCESS=false
fi

# Test 3: Client Role Comprehensive Tests
echo ""
echo "3️⃣  Client Role Tests (role-client-comprehensive.spec.ts)"
echo "--------------------------------------------------------"
if npx playwright test tests/e2e/role-client-comprehensive.spec.ts; then
    echo "✅ Client role tests PASSED"
else
    echo "❌ Client role tests FAILED"
    OVERALL_SUCCESS=false
fi

# Test 4: Security Boundary Tests (CRITICAL)
echo ""
echo "4️⃣  Security Boundary Tests (role-security-comprehensive.spec.ts) - CRITICAL"
echo "--------------------------------------------------------------------------"
if npx playwright test tests/e2e/role-security-comprehensive.spec.ts; then
    echo "✅ Security boundary tests PASSED"
else
    echo "❌ Security boundary tests FAILED - CRITICAL FAILURE"
    OVERALL_SUCCESS=false
fi

# Summary
echo ""
echo "📊 TEST SUITE SUMMARY"
echo "===================="

if $OVERALL_SUCCESS; then
    echo "🎉 ALL ROLE-BASED E2E TESTS PASSED"
    echo "✅ Constitutional requirements satisfied"
    echo "✅ Application ready for deployment"
    echo ""
    echo "Tests completed successfully at $(date)"
    exit 0
else
    echo "💥 ROLE-BASED E2E TESTS FAILED"
    echo "❌ Constitutional requirements NOT satisfied"
    echo "❌ Deployment BLOCKED by failed tests"
    echo ""
    echo "🚨 CRITICAL: Fix all failing tests before proceeding"
    echo "   Role-based security is NON-NEGOTIABLE per Constitution"
    echo ""
    echo "Tests failed at $(date)"
    exit 1
fi