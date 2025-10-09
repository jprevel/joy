# Feature Specification: Enhanced Statusfaction for Account Managers

**Feature Branch**: `002-we-have-the`
**Created**: 2025-10-07
**Status**: Ready for Planning
**Input**: User description: "we have the beginnings of 'statusfaction' for account managers. Account Managers have the same access as Agency but they should also have access to the Statusfaction link.  The form fields are correct, but the list of clients to give status needs to be update to show the client name, the status ('Needs Status','Pending Approval','Status Approved'). Admins have the ability to approve a status. Admins need to be able to read the most recent status and approve it. When Account Managers and Admins click into a status, they should see a graph that shows the last 5 weeks ratings for client satisfaction and team health."

## Execution Flow (main)
```
1. Parse user description from Input
   → Feature enhances existing Statusfaction system
2. Extract key concepts from description
   → Actors: Account Managers, Admins
   → Actions: Submit status, approve status, view trends
   → Data: Client satisfaction ratings, team health ratings
   → Constraints: 5-week historical view
3. Clarifications resolved:
   → Data older than 5 weeks is stored but not displayed
   → Account Managers can edit statuses until approved
   → Status triggers weekly on Sundays, typically submitted Thursdays
4. User Scenarios & Testing section complete
   → Clear user flows identified for both roles
5. Functional Requirements complete
   → All requirements are testable
6. Key Entities identified
   → Statusfaction submission, approval workflow
7. Review Checklist passed
   → All clarifications resolved
8. Return: SUCCESS (spec ready for planning)
```

---

## ⚡ Quick Guidelines
- ✅ Focus on WHAT users need and WHY
- ❌ Avoid HOW to implement (no tech stack, APIs, code structure)
- 👥 Written for business stakeholders, not developers

---

## Clarifications

### Session 2025-10-07

- Q: When an Account Manager views client data outside of Statusfaction, what access should they have? → A: Full read/write access to all client data (calendar, content, etc.) like Agency users
- Q: When displaying the "last 5 weeks of ratings" in the trend graph, which 5 weeks should be shown? → A: The 5 most recent weeks ending with the current week, showing gaps for missing submissions
- Q: Besides client satisfaction (1-10) and team health (1-10) ratings, what other fields should be included in the status submission form? → A: Ratings plus required text notes field
- Q: When an Admin clicks into a client that has "Needs Status" (no submission yet for current week), what should they see? → A: The 5-week trend graph with only previous weeks' data (current week empty)
- Q: What validation constraints should apply to the required notes field in status submissions? → A: No minimum length; any non-empty text accepted

---

## User Scenarios & Testing *(mandatory)*

### Primary User Story

**As an** Account Manager
**I want to** submit weekly status reports for my assigned clients and view historical trends
**So that** I can track client satisfaction and team health over time and communicate status to leadership

**As an** Admin
**I want to** review and approve status submissions from Account Managers
**So that** I can ensure quality and accuracy before status is finalized

### Acceptance Scenarios

**Account Manager Scenarios:**

1. **Given** I am an Account Manager logged into the system
   **When** I navigate to the Statusfaction section
   **Then** I should see a list of my assigned clients with their current status state ('Needs Status', 'Pending Approval', 'Status Approved')

2. **Given** I have submitted a status for a client
   **When** I click into that status
   **Then** I should see a line graph showing the last 5 weeks of client satisfaction and team health ratings on the same graph

3. **Given** I am viewing a client that 'Needs Status'
   **When** I submit the status form with client satisfaction (1-10) and team health (1-10) ratings plus required notes
   **Then** the client status should change to 'Pending Approval'

4. **Given** I have a status in 'Pending Approval' state
   **When** I edit the ratings or notes
   **Then** the system should save my changes and maintain 'Pending Approval' status

5. **Given** I am submitting a status form
   **When** I attempt to submit without entering notes
   **Then** the system should prevent submission and require notes to be entered

6. **Given** my status has been approved
   **When** I attempt to edit it
   **Then** the system should prevent editing of approved statuses

**Admin Scenarios:**

1. **Given** I am an Admin logged into the system
   **When** I navigate to the Statusfaction section
   **Then** I should see all clients across all Account Managers with their status states

2. **Given** a status is in 'Pending Approval' state
   **When** I click to view the status details
   **Then** I should see the submitted ratings, notes, and have the ability to approve

3. **Given** I am viewing a status in 'Pending Approval'
   **When** I approve the status
   **Then** the status should change to 'Status Approved'

4. **Given** I click into any client's status (regardless of state)
   **When** I view the status details
   **Then** I should see the same 5-week trend line graph showing both client satisfaction and team health ratings

5. **Given** I click into a client with 'Needs Status' state
   **When** I view the details
   **Then** I should see the 5-week trend graph showing only previous weeks' data (current week shows as empty/gap)

### Edge Cases

- **No Clients Assigned**: When an Account Manager has no clients assigned, no status reports are needed (empty state shown)
- **Pending Approval**: If a status is never approved, it remains in 'Pending Approval' state indefinitely
- **Admin/Account Manager Overlap**: Admins can approve statuses (no Account Managers hold Admin role)
- **Limited Historical Data**: When less than 5 weeks of data exists, show only the available weeks with gaps for missing submissions
- **Missing Submissions**: Trend graph displays gaps (null data points) for weeks where no status was submitted within the 5-week window
- **Needs Status Detail View**: Clicking into a client with 'Needs Status' state shows the trend graph with historical data; current week appears as empty/gap
- **One Status Per Week**: Only one status can be submitted per client per week
- **Client Reassignment**: Client reassignment mid-week does not affect existing status workflow
- **Weekly Cycle**: New status period triggers on Sundays; statuses typically submitted on Thursdays

## Requirements *(mandatory)*

### Functional Requirements

**Access Control:**
- **FR-001**: Account Managers MUST have access to the existing Statusfaction link in navigation
- **FR-002**: Account Managers MUST have full read/write access to all client data (calendar, content, etc.) identical to Agency role users, plus Statusfaction submission capabilities
- **FR-003**: Admins MUST be able to view all Statusfaction submissions across all Account Managers

**Status Display:**
- **FR-004**: System MUST display a list of clients with their current status state
- **FR-005**: System MUST show client name alongside status for each entry in the list
- **FR-006**: System MUST support three status states: 'Needs Status', 'Pending Approval', 'Status Approved'
- **FR-007**: Account Managers MUST only see clients assigned to them in the Statusfaction list
- **FR-008**: Admins MUST see all clients in the Statusfaction list regardless of assignment

**Status Submission:**
- **FR-009**: Account Managers MUST be able to submit status using the existing form fields
- **FR-010**: Status submission MUST include client satisfaction rating (1-10 scale)
- **FR-011**: Status submission MUST include team health rating (1-10 scale)
- **FR-012**: Status submission MUST include required text notes field
- **FR-013**: System MUST prevent submission if notes field is empty (any non-empty text accepted, no minimum length)
- **FR-014**: Submitting a status MUST change the client status from 'Needs Status' to 'Pending Approval'
- **FR-015**: System MUST allow only one status submission per client per week
- **FR-016**: New status period MUST trigger on Sundays

**Status Editing:**
- **FR-017**: Account Managers MUST be able to edit submitted statuses while in 'Pending Approval' state
- **FR-018**: Account Managers MUST NOT be able to edit statuses after approval
- **FR-019**: System MUST prevent deletion of submitted statuses

**Status Approval:**
- **FR-020**: Admins MUST be able to view the most recent status submission for any client
- **FR-021**: Admins MUST be able to approve pending statuses
- **FR-022**: Approving a status MUST change the status state from 'Pending Approval' to 'Status Approved'

**Trend Visualization:**
- **FR-023**: Account Managers MUST be able to click into a client to view detailed information (regardless of status state)
- **FR-024**: Admins MUST be able to click into a client to view detailed information (regardless of status state)
- **FR-025**: Status detail view MUST display a line graph showing the 5 most recent calendar weeks ending with the current week
- **FR-026**: Graph MUST show both client satisfaction and team health ratings on the same graph
- **FR-027**: Graph MUST display client satisfaction ratings (1-10) over the 5-week period
- **FR-028**: Graph MUST display team health ratings (1-10) over the 5-week period
- **FR-029**: Graph MUST show gaps (null data points) for weeks where no status submission exists, including current week if in 'Needs Status' state
- **FR-030**: Graph MUST display only the 5 most recent calendar weeks; older data is hidden from view
- **FR-031**: Clients in 'Needs Status' state MUST still be clickable and show trend graph with historical data (current week empty)

**Data Management:**
- **FR-032**: System MUST retain all historical status data indefinitely
- **FR-033**: System MUST display only the most recent 5 weeks of data in trend graphs
- **FR-034**: System MUST store submission date, notes, and Account Manager information with each status

### Key Entities *(include if feature involves data)*

- **Statusfaction Submission**: A weekly status report containing client satisfaction rating (1-10), team health rating (1-10), required text notes, submission date, week start date (Sunday), Account Manager who submitted, and approval status. Related to a specific Client and can have multiple historical entries per client.

- **Status State**: An enumeration representing the current state of a statusfaction submission:
  - 'Needs Status' - No submission for current week period
  - 'Pending Approval' - Submitted but not yet approved by Admin
  - 'Status Approved' - Reviewed and approved by Admin

- **Client Satisfaction Rating**: A numeric rating (1-10 scale) indicating how satisfied the client is with services provided during the week

- **Team Health Rating**: A numeric rating (1-10 scale) indicating the health/morale of the team working on the client account during the week

- **Account Manager**: A user role with identical full read/write access to all client data as Agency users (calendar, content, etc.), plus Statusfaction submission capabilities for their assigned clients

- **Week Period**: Calendar week starting on Sunday, used to determine when new status submissions are required

---

## Review & Acceptance Checklist
*GATE: Automated checks run during main() execution*

### Content Quality
- [x] No implementation details (languages, frameworks, APIs)
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

### Requirement Completeness
- [x] No [NEEDS CLARIFICATION] markers remain
- [x] Requirements are testable and unambiguous
- [x] Success criteria are measurable (1-10 rating scale defined)
- [x] Scope is clearly bounded
- [x] Dependencies and assumptions identified

---

## Execution Status
*Updated by main() during processing*

- [x] User description parsed
- [x] Key concepts extracted
- [x] Ambiguities marked and resolved
- [x] User scenarios defined
- [x] Requirements generated
- [x] Entities identified
- [x] Review checklist passed

---

## Notes for Planning Phase

**Key Clarifications Applied:**
1. ✅ Rating scales: Both client satisfaction and team health use 1-10 scale
2. ✅ Graph format: Line chart with both metrics on same graph
3. ✅ Edit workflow: Editable until approved, locked after approval
4. ✅ Data retention: All data retained indefinitely, only last 5 weeks displayed
5. ✅ Weekly period: Starts Sunday, typically submitted Thursday
6. ✅ One status per week per client
7. ✅ No notification system required for approval
8. ✅ No Admin/Account Manager role overlap exists

**Dependencies:**
- Existing Statusfaction form fields are correct and functional
- Client assignment system exists for Account Managers
- User role system supports Account Manager role distinction from Agency role
- Week period tracking system (Sunday start)

**Ready for /plan command** ✅
