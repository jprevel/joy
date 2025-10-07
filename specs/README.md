# Joy Content Calendar Specifications

This directory contains all specification and governance documents for the Joy Content Calendar project.

## 📁 Directory Structure

```
specs/
├── README.md                    # This file
├── JOY-CONSTITUTION.md         # 🏛️  Constitutional requirements (TDD, testing, build)
├── spec.md                     # Core technical specification
├── BDD.md                      # Behavior-Driven Development specs
├── ERD.md                      # Entity Relationship Diagram
├── FRD.md                      # Functional Requirements Document
├── user-stories.md             # User stories and acceptance criteria
├── 001-update-joy/             # Feature specifications
├── docs/                       # Documentation
│   ├── TDD-MIGRATION-GUIDE.md  # TDD implementation guide
│   └── spec-kit-README.md      # Spec kit documentation
├── memory/                     # Governance and process memory
│   ├── constitution.md         # Template constitution
│   └── constitution_update_checklist.md
└── templates/                  # Document templates
    ├── plan-template.md
    ├── spec-template.md
    ├── tasks-template.md
    └── commands/
```

## 🏛️ Constitutional Governance

The Joy project is governed by the **[Joy Constitution](JOY-CONSTITUTION.md)** which establishes:

### Non-Negotiable Requirements
- **Test-First Development (TDD)** - All features must follow RED-GREEN-Refactor cycle
- **Test-Before-Build** - Tests must pass before any build process
- **Constitutional Test Order** - Contract → Integration → E2E → Unit

### Development Workflow
```bash
# Start new feature (Constitutional way)
composer new-feature -- feature-name

# Verify TDD compliance
scripts/verify-red-phase.sh feature-name    # Tests must fail first
scripts/verify-green-phase.sh feature-name  # Tests must pass

# Build (Constitutional process)
composer build                              # Enforces test-first
composer build-force                        # ⚠️ Constitutional violation
```

## 📋 Key Documents

### 🏛️ [Joy Constitution](JOY-CONSTITUTION.md)
**Supreme governance document** - All development must comply with Constitutional requirements.

### 📘 [Core Specification](spec.md)
Technical specification including architecture, data models, and API contracts.

### 🧪 [TDD Migration Guide](docs/TDD-MIGRATION-GUIDE.md)
Complete guide for implementing Constitutional TDD requirements.

### 📊 [BDD Specification](BDD.md)
Behavior-driven development scenarios and acceptance criteria.

### 🗃️ [Entity Relationship Diagram](ERD.md)
Database schema and relationships.

### ⚙️ [Functional Requirements](FRD.md)
Detailed functional requirements and business logic.

### 👤 [User Stories](user-stories.md)
User personas, stories, and acceptance criteria.

## 🔄 Development Process

### Feature Development
1. **Constitutional TDD**: Follow strict Test-First Development
2. **Specification-Driven**: Create/update specs before implementation
3. **Documentation**: Update relevant docs during development
4. **Review**: Ensure Constitutional compliance in all PRs

### Document Updates
- All specs must be updated when features change
- Constitutional amendments require formal process
- Templates should be kept current with requirements

## 🎯 Quality Standards

### Testing Requirements
- **90% minimum code coverage**
- **100% coverage for security components**
- **Real dependencies preferred over mocks**
- **Constitutional test order enforced**

### Documentation Standards
- All specs must be current and accurate
- Changes require corresponding spec updates
- Cross-references must be maintained
- Examples must be working and tested

## 🚀 Quick Start

### New Feature Development
```bash
# 1. Review Constitutional requirements
cat specs/JOY-CONSTITUTION.md

# 2. Start TDD process
cd joy-app
composer new-feature -- your-feature-name

# 3. Follow TDD cycle
scripts/verify-red-phase.sh your-feature-name
# ... implement feature ...
scripts/verify-green-phase.sh your-feature-name

# 4. Build with Constitutional compliance
composer build
```

### Updating Specifications
1. Update relevant spec documents
2. Ensure consistency across all related docs
3. Update user stories and acceptance criteria
4. Verify Constitutional compliance
5. Update templates if process changes

---

📍 **Location**: `/joy/specs/`
🏛️ **Governed by**: [Joy Constitution v1.0.0](JOY-CONSTITUTION.md)
📅 **Last Updated**: 2025-09-29