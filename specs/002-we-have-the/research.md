# Research: Enhanced Statusfaction for Account Managers

**Feature**: 002-we-have-the
**Date**: 2025-10-07
**Purpose**: Research current implementation and design decisions for evolving Statusfaction

## Current Implementation Analysis

### Existing System Overview

**Tech Stack**:
- Laravel 12.0 (PHP 8.2+)
- Livewire (full-page components with reactivity)
- Tailwind CSS (styling)
- Spatie Laravel Permission (role-based access control)
- MySQL/PostgreSQL (via Laravel migrations)

**Current Features**:
- Account Managers can submit status updates for clients
- Basic form with: status_notes (textarea), client_satisfaction (1-10 slider), team_health (1-10 slider)
- Simple client list view showing last update date
- No approval workflow
- No status states ('Needs Status', 'Pending Approval', 'Status Approved')
- No trend visualization/graphing
- No admin approval capability

### Database Schema (Current)

**Table**: `client_status_updates`
```
- id (bigint, PK)
- user_id (foreign key -> users)
- client_id (foreign key -> clients)
- status_notes (text)
- client_satisfaction (integer 1-10)
- team_health (integer 1-10)
- status_date (timestamp)
- created_at, updated_at
- indexes: (user_id, client_id), status_date
```

**Missing from current schema**:
- approval_status (enum or string)
- approved_by (foreign key to users)
- approved_at (timestamp)
- week_start_date (date for grouping by week)
- updated/editable tracking

### Role System (Spatie Permission)

**Current Roles**:
- Admin
- Account Manager (should have Agency-level access)
- Agency

**User->Client Relationship**:
- Users belong to Teams (many-to-many)
- Clients belong to a Team (one-to-many)
- Users access clients through their team memberships

## Design Decisions

### 1. Approval Workflow State Machine

**Decision**: Add approval_status enum column with three states
- 'needs_status' - Default for new week, no submission yet
- 'pending_approval' - Submitted but not approved
- 'approved' - Admin has approved

**Rationale**:
- Simple state machine, linear progression
- Matches spec requirements (FR-006)
- Easy to query and filter
- Supports editing restriction (can edit until approved)

**Alternatives Considered**:
- Separate approval table: Adds complexity for no benefit
- Boolean approved flag: Doesn't distinguish between "needs status" and "pending"

### 2. Week Period Tracking

**Decision**: Add week_start_date (date) column, calculated as most recent Sunday
- Automatically calculated on submit
- Index on (client_id, week_start_date) for uniqueness constraint
- Use Carbon's startOfWeek(Carbon::SUNDAY) for consistency

**Rationale**:
- Simple, deterministic calculation
- Supports "one status per week" rule (FR-015)
- Easy to query for 5-week trends
- Prevents duplicate submissions for same week

**Alternatives Considered**:
- ISO week number: Problematic for year boundaries
- Manual date entry: User error prone
- Week ranges (start/end): Redundant, adds complexity

### 3. Trend Graph Implementation

**Decision**: Use Chart.js library via CDN
- Line chart with two datasets (client_satisfaction, team_health)
- X-axis: Week labels (e.g., "Oct 1 - Oct 7")
- Y-axis: Rating (1-10)
- Show null/gaps for missing weeks
- Calculate 5 most recent calendar weeks (including current)

**Rationale**:
- Chart.js is lightweight, well-documented, widely used
- Handles null data points naturally (breaks in line)
- Easy Livewire integration (pass data as JSON)
- Responsive and accessible

**Alternatives Considered**:
- Alpine.js native charting: Too limited
- Server-side image generation: Poor UX, accessibility issues
- ApexCharts: More features than needed, larger bundle

### 4. UI/UX Approach

**Decision**: Evolve existing Statusfaction.php Livewire component
- Add detail view state (showDetail boolean)
- Client list shows status badges ('Needs Status', 'Pending', 'Approved')
- Clicking client opens detail view with form (if editable) or read-only + graph
- Admin sees "Approve" button on pending statuses
- Use Tailwind badge components for status states

**Rationale**:
- Maintains existing Livewire architecture
- No page reload, smooth UX
- Consistent with current Joy app patterns
- Minimal new dependencies

**Alternatives Considered**:
- Separate pages: More navigation complexity
- Modal for detail: Cramped for graph + form
- Full Filament resource: Overhead for simple workflow

### 5. Access Control Strategy

**Decision**: Extend existing role checks in Livewire component
- Account Managers filter to assigned clients only
- Admins see all clients
- Permission checks for approval action
- Use Spatie's hasRole() method

**Rationale**:
- Leverages existing Spatie Permission setup
- Simple, declarative permission checks
- No new middleware or gates needed

**Alternatives Considered**:
- Laravel Policies: Overkill for simple role checks
- Custom middleware: Redundant with Spatie

### 6. Data Validation

**Decision**: Laravel validation rules + constraints
- notes: required, string, min:1 (any non-empty text)
- client_satisfaction: required, integer, between:1,10
- team_health: required, integer, between:1,10
- Unique constraint: (client_id, week_start_date)

**Rationale**:
- Matches spec requirement (FR-013: no minimum length)
- Database constraint prevents race conditions
- Client-side validation for UX (HTML5 required)

**Alternatives Considered**:
- Form Request classes: Overkill for simple form
- Minimum text length: Spec explicitly says no minimum

## Technology Choices

### Frontend Libraries
- **Livewire 3**: Reactive components (already in use)
- **Alpine.js**: Minimal client-side interactions (already in use)
- **Tailwind CSS 3**: Utility-first styling (already in use)
- **Chart.js 4**: Trend visualization (new, via CDN)

### Backend
- **Laravel 12**: Framework (current)
- **Spatie Permission 6**: RBAC (current)
- **Carbon**: Date manipulation (Laravel built-in)

### Testing
- **PHPUnit 11**: Unit/integration tests (current)
- **Pest**: BDD-style syntax (optional, available)
- **Laravel Dusk**: E2E browser tests (not needed for this feature)

## Performance Considerations

**Query Optimization**:
- Index on (client_id, week_start_date) for uniqueness + fast lookups
- Index on approval_status for filtering
- Eager load relationships (with(['user', 'client']))
- Limit graph queries to 5 weeks (WHERE week_start_date >= DATE_SUB(NOW(), INTERVAL 5 WEEK))

**Expected Scale**:
- ~50-100 clients per account manager
- 1 status per client per week = ~52 records/client/year
- 10 account managers = ~26,000 records/year
- Well within Laravel/MySQL performance envelope

**Caching Strategy**:
- No caching needed

## Integration Points

### Existing Dependencies
- Client model (already exists)
- User model with Spatie roles (already exists)
- Team model and relationships (already exists)
- Livewire components (existing pattern)

### New Dependencies
- Chart.js (CDN)
- Migration to add columns to client_status_updates table

## Risk Assessment

**Low Risk**:
- Additive changes to existing model
- UI evolution, not replacement
- No breaking changes to current functionality

**Medium Risk**:
- Migration affects production data (needs careful testing)
- Chart.js CDN dependency (mitigate with SRI hash)

**Mitigation**:
- Feature flag for rollout
- Database backup before migration
- Comprehensive test coverage (unit + integration)

## Open Questions (Resolved)
All questions were resolved in the clarification phase. See spec.md Clarifications section.

## References

**Laravel Documentation**:
- Livewire Components: https://livewire.laravel.com/docs/components
- Eloquent Relationships: https://laravel.com/docs/eloquent-relationships
- Validation: https://laravel.com/docs/validation

**Third-Party Libraries**:
- Spatie Permission: https://spatie.be/docs/laravel-permission
- Chart.js: https://www.chartjs.org/docs/latest/
- Tailwind CSS: https://tailwindcss.com/docs

**Joy Codebase Patterns**:
- Livewire component: `app/Livewire/Statusfaction.php`
- View template: `resources/views/livewire/statusfaction.blade.php`
- Migration pattern: `database/migrations/*_create_client_status_updates_table.php`
