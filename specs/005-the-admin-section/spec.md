# Feature Specification: Admin Section Refresh

**Feature Branch**: `005-the-admin-section`
**Created**: 2025-10-11
**Status**: Draft
**Input**: User description: "the admin section needs a refresh in functionality and some minor visual tweaks. Admins need to be able to CRUD users and clients.  The client form needs to have a field to map the client to the correct slack channel; I think this functionality lives somewhere but I don't see it here. The audit section needs just one button 'view logs.' The filters in /audit/recent are big and clunky, I'd like to see a filter button reveal the filtering form, and I'd like it to be smaller and tidy. The audit logs themselves don't need an IP address displayed, but under details I want to know what the '+3 changes' actually mean. I don't want an audit log detail, but I want all the information relevant to the admin to be displayed. All I see for events is admin_access but I know as an admin I have done other things and I wonder if other events are captured.  The system status section is fake and should be removed."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Admin Manages Users (Priority: P1)

As an admin, I need full CRUD (Create, Read, Update, Delete) capabilities for managing users so that I can onboard new team members, update their information, and remove access when needed.

**Why this priority**: User management is the foundation of any admin panel. Without the ability to manage users directly from the admin interface, admins must rely on manual database operations or console commands, which is inefficient and error-prone.

**Independent Test**: Can be fully tested by logging in as an admin, creating a new user with name/email/role, editing that user's details, and deleting the user. Delivers immediate value by enabling user administration without technical intervention.

**Acceptance Scenarios**:

1. **Given** I am logged in as an admin, **When** I navigate to User Management and click "Create User", **Then** I should see a form to create a new user with fields for name, email, password, and role selection
2. **Given** I am viewing the user list, **When** I click "Edit" on a user, **Then** I should be able to update their name, email, and role
3. **Given** I am viewing the user list, **When** I click "Delete" on a user, **Then** the system should ask for confirmation and remove the user from the system
4. **Given** I am viewing the user list, **When** I view the list, **Then** I should see all users with their name, email, role, and account status clearly displayed

---

### User Story 2 - Admin Manages Clients (Priority: P1)

As an admin, I need full CRUD capabilities for managing clients including the ability to map clients to Slack channels so that I can onboard new clients, configure their Slack notifications, and maintain their information.

**Why this priority**: Client management is equally critical as user management. The Slack channel mapping functionality exists in Filament but needs to be accessible in the main admin interface for consistency and discoverability.

**Independent Test**: Can be fully tested by logging in as an admin, creating a new client with name/description/team/Slack channel, editing the client to change their Slack channel, and viewing the client list. Delivers immediate value by enabling client administration with proper Slack integration.

**Acceptance Scenarios**:

1. **Given** I am logged in as an admin, **When** I navigate to Client Management and click "Create Client", **Then** I should see a form with fields for client name, description, team assignment, and Slack channel selection
2. **Given** I am creating or editing a client, **When** I click on the Slack channel dropdown, **Then** I should see a searchable list of available Slack channels (both public and private) from the connected workspace
3. **Given** I am viewing the client list, **When** I click "Edit" on a client, **Then** I should be able to update all client fields including the Slack channel mapping
4. **Given** I am viewing the client list, **When** I click "Delete" on a client, **Then** the system should ask for confirmation and remove the client (with appropriate handling of related data)
5. **Given** I am viewing the client list, **When** I view the list, **Then** I should see client name, team, assigned Slack channel, and basic metadata

---

### User Story 3 - Improved Audit Access (Priority: P2)

As an admin, I want an audit section with a single "View Logs" button instead of multiple navigation options so that I can quickly access audit logs without cognitive overhead.

**Why this priority**: The current audit section has "Dashboard" and "Recent Logs" buttons which creates unnecessary navigation complexity. Simplifying to a single entry point improves UX and reduces confusion.

**Independent Test**: Can be fully tested by logging in as an admin, navigating to the Audit section, and clicking the single "View Logs" button which takes you directly to the audit logs with filtering capabilities. Delivers immediate value by reducing clicks and simplifying navigation.

**Acceptance Scenarios**:

1. **Given** I am logged in as an admin, **When** I view the admin dashboard, **Then** I should see an "Audit Logs" card with a single "View Logs" button
2. **Given** I am viewing the Audit Logs card, **When** I click "View Logs", **Then** I should be taken directly to the audit logs page with all logs displayed and filtering options available
3. **Given** I am on the audit logs page, **When** I view the page, **Then** I should NOT see separate "Dashboard" and "Recent Logs" navigation options

---

### User Story 4 - Collapsible Audit Filters (Priority: P2)

As an admin reviewing audit logs, I want compact, collapsible filters so that the filter form doesn't dominate the screen and I have more space to view the actual log entries.

**Why this priority**: The current 5-column filter form takes up significant vertical space. Making it collapsible with a "Filter" button improves the information density and puts focus on the logs themselves.

**Independent Test**: Can be fully tested by navigating to audit logs, clicking a "Filter" button to reveal/hide the filter form, applying filters, and seeing the compact interface. Delivers immediate value by improving screen real estate and reducing visual clutter.

**Acceptance Scenarios**:

1. **Given** I am viewing audit logs, **When** the page loads, **Then** the filter form should be hidden by default with a "Filter" button visible
2. **Given** the filter form is hidden, **When** I click the "Filter" button, **Then** the filter form should expand/reveal below the button
3. **Given** the filter form is visible, **When** I click the "Filter" button again, **Then** the filter form should collapse/hide
4. **Given** I am viewing the filter form, **When** the form is displayed, **Then** it should be more compact than the current 5-column layout (suggest 3 columns or stacked with better spacing)
5. **Given** I have applied filters, **When** the filter form is collapsed, **Then** I should see an indicator showing that filters are active (e.g., "3 filters active")

---

### User Story 5 - Enhanced Audit Log Details (Priority: P2)

As an admin reviewing audit logs, I want to see expanded change details inline (not in a separate detail page) and I don't need to see IP addresses in the main view, so that I can quickly understand what changed without extra navigation.

**Why this priority**: The "+3 changes" notation is cryptic and requires mental effort to understand. Expanding this inline to show actual field names and values provides immediate clarity. IP addresses are less relevant for most audit review and can be removed to reduce clutter.

**Independent Test**: Can be fully tested by viewing audit logs and seeing expanded change information (e.g., "name: 'Old' � 'New', email: added") directly in the table without clicking through to a detail page. Delivers immediate value by making audit logs more informative at a glance.

**Acceptance Scenarios**:

1. **Given** I am viewing audit logs, **When** I see a log entry with changes, **Then** I should see the actual field names and values that changed (e.g., "name: 'John' � 'Jane', email: 'old@example.com' � 'new@example.com'") instead of just "+3 changes"
2. **Given** I am viewing audit logs, **When** I see a log entry, **Then** the IP address column should NOT be displayed in the main table
3. **Given** I am viewing an audit log entry with multiple changes, **When** the changes are displayed, **Then** they should be formatted in a readable way (e.g., bulleted list or stacked format) without requiring a detail page
4. **Given** I am viewing an audit log entry, **When** there are many changes, **Then** the changes should be truncated with an expandable toggle (e.g., "Show all 12 changes") that expands inline
5. **Given** I am viewing audit logs, **When** I see the details column, **Then** all relevant information for admin review should be visible (model type, model ID, user, timestamp, specific changes)

---

### User Story 6 - Complete Audit Event Coverage (Priority: P3)

As an admin, I want to see ALL audit events captured in the system (not just "admin_access") so that I have a complete audit trail of user actions and system changes.

**Why this priority**: Currently only "admin_access" events appear, but the system likely captures more events. Ensuring all events are properly logged and displayed provides comprehensive audit coverage and accountability.

**Independent Test**: Can be fully tested by performing various actions as different user types (create content, approve content, add comments, create users, edit clients, etc.) and verifying that these events appear in the audit log with appropriate detail. Delivers immediate value by providing transparency into all system activity.

**Acceptance Scenarios**:

1. **Given** I am an admin reviewing audit logs, **When** I view the event filter dropdown, **Then** I should see all available event types in the system (Admin Access, Content Created, Content Approved, Content Rejected, Comment Added, User Created, User Updated, User Deleted, Client Created, Client Updated, Client Deleted, Statusfaction Submitted, Statusfaction Approved, etc.)
2. **Given** I have performed various admin actions, **When** I view the audit logs, **Then** I should see entries for Client Created, Client Updated, User Created, User Updated, and User Deleted
3. **Given** content has been created or updated, **When** I view audit logs, **Then** I should see events for Content Created, Content Approved, Content Rejected
4. **Given** comments have been added, **When** I view audit logs, **Then** I should see Comment Added events with details about the commenter and content item
5. **Given** audit logging is implemented across the system, **When** any significant data change occurs, **Then** it should be captured in the audit log with appropriate context (user, timestamp, changes)

---

### User Story 7 - Remove Fake System Status (Priority: P3)

As an admin, I don't want to see the fake "System Status" card that always shows "System Healthy" because it provides no real value and creates confusion.

**Why this priority**: The fake system status is misleading and takes up space. Removing it cleans up the dashboard and prevents admins from relying on inaccurate information. This is lower priority than CRUD and audit improvements but should be included in the refresh.

**Independent Test**: Can be fully tested by logging in as admin and verifying the System Status card (with red icon and "System Healthy" badge) is no longer visible on the admin dashboard. Delivers immediate value by removing misleading UI elements.

**Acceptance Scenarios**:

1. **Given** I am logged in as an admin, **When** I view the admin dashboard, **Then** I should NOT see a "System Status" card
2. **Given** the System Status card has been removed, **When** I view the dashboard, **Then** the remaining cards should be reorganized to fill the space appropriately

---

### Edge Cases

- **Deleting a user with related data**: When an admin deletes a user who has created content, comments, or audit log entries, the system performs a soft deletion (marks user as deleted with deleted_at timestamp). All related data (content, comments, audit logs) remains intact and visible. The deleted user cannot log in and appears in gray color to indicated deleted state in the user list.

- **Deleting a client with active content**: When an admin deletes a client that has content items, comments, or active magic links, the system performs a soft deletion (marks client as deleted with deleted_at timestamp). All related data (content, comments, magic links, audit logs) remains intact. Magic links continue to function for read-only access to content. The deleted client appears with a "Deleted" indicator in the client list.

- **Slack channel becomes unavailable**: What happens when a client is mapped to a Slack channel that is later archived or the bot is removed from? System should handle gracefully by logging errors but not breaking the admin interface.

- **Audit logs with very large change sets**: What happens when an audit log entry has 50+ field changes? System should truncate or paginate the display while still allowing full access to the data.

- **No Slack workspace configured**: What happens when admin tries to select Slack channel but no workspace is connected? Field should be disabled with a helpful message like "No Slack workspace connected - configure Slack integration first".

- **Concurrent admin edits**: What happens when two admins edit the same user or client simultaneously? System should handle optimistic locking or last-write-wins with appropriate feedback.

- **User editing their own account**: When an admin edits their own user account (name, email, role) or deletes themselves, the system allows the operation but displays an additional confirmation warning: "You are modifying your own account. Are you sure?" This prevents accidental self-modification while maintaining full admin control.

## Requirements *(mandatory)*

### Functional Requirements

#### User Management (P1)
- **FR-001**: System MUST provide a user management interface accessible from the admin dashboard
- **FR-002**: Admins MUST be able to create new users with name, email, password, and role assignment (available roles: Admin, Agency, Account Manager, Client)
- **FR-003**: Admins MUST be able to view a list of all users with their name, email, role, and status (including deleted status)
- **FR-004**: Admins MUST be able to edit existing users' name, email, and role
- **FR-005**: Admins MUST be able to delete users with confirmation prompt
- **FR-006**: System MUST validate user email addresses are unique during creation and editing
- **FR-007**: System MUST hash passwords securely when creating or updating users
- **FR-008**: System MUST allow admins to edit their own account (name, email, role) and delete themselves with confirmation prompt
- **FR-008a**: System MUST display an additional warning when an admin attempts to change their own role or delete themselves (e.g., "You are modifying your own account. Are you sure?")
- **FR-009**: System MUST perform soft deletion when users are deleted - marking them as inactive/deleted while preserving all data and relationships (content, comments, audit logs remain intact)
- **FR-009a**: System MUST prevent soft-deleted users from logging in
- **FR-009b**: System MUST display deleted users with clear visual indicator in user list (e.g., "Deleted" badge, greyed out)

#### Client Management (P1)
- **FR-010**: System MUST provide a client management interface accessible from the admin dashboard
- **FR-011**: Admins MUST be able to create new clients with name, description, team assignment, and Slack channel selection
- **FR-012**: System MUST populate the Slack channel dropdown with live data from the connected Slack workspace (including both public and private channels)
- **FR-013**: System MUST store both slack_channel_id and slack_channel_name when a channel is selected
- **FR-014**: Admins MUST be able to view a list of all clients with their name, team, Slack channel, metadata, and status (including deleted status)
- **FR-015**: Admins MUST be able to edit existing clients including updating their Slack channel mapping
- **FR-016**: Admins MUST be able to delete clients with confirmation prompt
- **FR-017**: System MUST perform soft deletion when clients are deleted - marking them as inactive/deleted while preserving all data and relationships (content, comments, magic links, audit logs remain intact)
- **FR-017a**: System MUST allow soft-deleted clients' magic links to remain functional for read-only access to content
- **FR-017b**: System MUST display deleted clients with clear visual indicator in client list (e.g., "Deleted" badge, greyed out)
- **FR-018**: System MUST gracefully handle cases where no Slack workspace is configured (disable field with helpful message)
- **FR-019**: System MUST display appropriate error message if Slack API call fails when fetching channels

#### Audit Section Simplification (P2)
- **FR-020**: Admin dashboard MUST display a single "View Logs" button in the Audit Logs card
- **FR-021**: System MUST remove the separate "Dashboard" and "Recent Logs" navigation options
- **FR-022**: Clicking "View Logs" MUST navigate directly to the audit logs page with all logs and filtering capabilities

#### Audit Filter UI (P2)
- **FR-023**: Audit logs page MUST display filters in a collapsed state by default
- **FR-024**: System MUST provide a "Filter" button to expand/collapse the filter form
- **FR-025**: When filters are applied and the form is collapsed, system MUST display an indicator (e.g., "3 filters active")
- **FR-026**: Filter form MUST be more compact than current 5-column layout (suggest 3 columns or stacked layout)
- **FR-027**: System MUST preserve filter state when toggling the collapsed/expanded view

#### Audit Log Display Enhancement (P2)
- **FR-028**: Audit logs table MUST NOT display IP address column in the main view
- **FR-029**: Audit logs MUST display expanded change details inline instead of "+X changes" notation
- **FR-030**: Change details MUST show field names and both old/new values (e.g., "name: 'John' � 'Jane'")
- **FR-031**: When changes exceed a reasonable number (suggest >5), system MUST truncate with inline expandable toggle
- **FR-032**: Details column MUST display all relevant information for admin review: model type, model ID, user, timestamp, and specific field changes
- **FR-033**: System MUST NOT require navigation to a separate detail page to view full audit information

#### Complete Audit Event Coverage (P3)
- **FR-034**: System MUST capture audit events for: Admin Access, Content Created, Content Updated, Content Approved, Content Rejected, Comment Added, User Created, User Updated, User Deleted, Client Created, Client Updated, Client Deleted, Statusfaction Submitted, Statusfaction Approved
- **FR-035**: Event filter dropdown MUST include all captured event types
- **FR-036**: All audit log entries MUST include: user_id, event type, auditable_type, auditable_id, old_values, new_values, and timestamp
- **FR-037**: System MUST use human-readable event naming convention (title case with spaces, e.g., "User Created", "Content Approved") across all audit log entries for display-friendly filtering and viewing

#### System Status Removal (P3)
- **FR-038**: Admin dashboard MUST NOT display the "System Status" card with fake "System Healthy" indicator
- **FR-039**: Dashboard layout MUST reorganize remaining cards to fill space after removal

### Key Entities

- **User**: Represents system users who can log in. Attributes include name, email, password (hashed), roles/permissions, deleted_at (for soft deletion), created_at, updated_at. Related to audit logs as the actor, and potentially to clients as account managers. Soft-deleted users (deleted_at not null) cannot log in but their data relationships remain intact.

- **Client**: Represents client organizations managed in Joy. Attributes include name, description, team_id, slack_channel_id, slack_channel_name, deleted_at (for soft deletion), created_at, updated_at. Related to content items, comments, statusfactions, magic links, and audit logs. Soft-deleted clients (deleted_at not null) retain functional magic links for read-only access but their data relationships remain intact.

- **AuditLog**: Represents a record of system activity. Attributes include user_id, event (string), auditable_type, auditable_id, old_values (JSON), new_values (JSON), ip_address, user_agent, created_at. Related to user (actor) and polymorphically to the auditable entity.

- **SlackWorkspace**: Represents the connected Slack workspace. Attributes include team_id, team_name, bot_token (encrypted), is_active, last_sync_at. Related to clients through the slack_channel_id mapping.

- **Team**: Represents internal agency teams. Attributes include name, description. Related to clients (one team has many clients).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Admins can create, view, edit, and delete users without leaving the web interface (no console commands required)
- **SC-002**: Admins can create, view, edit, and delete clients with Slack channel mapping in under 60 seconds per operation
- **SC-003**: Admins can access audit logs with a single click from the admin dashboard
- **SC-004**: Audit filter form occupies less than 25% of vertical screen space when collapsed
- **SC-005**: Admins can understand what changed in an audit log entry without expanding to a detail page (85% of use cases)
- **SC-006**: All significant system actions (content changes, user management, client management) are captured in audit logs with 100% coverage
- **SC-007**: Admin dashboard loads without fake/misleading system status information
- **SC-008**: User and client management interfaces maintain consistent UX patterns with existing Joy interfaces (forms, validation, error handling)
- **SC-009**: Slack channel selection dropdown loads and displays all available channels within 3 seconds
- **SC-010**: Audit log change details are human-readable without requiring technical knowledge (e.g., "Name changed from 'ABC Corp' to 'ABC Corporation'" instead of raw JSON)

## Clarifications

### Session 2025-10-11

- Q: When an admin attempts to delete a user who has created content, comments, or audit logs, what should happen? → A: Soft delete - Mark user as inactive/deleted but preserve all data and relationships. User cannot log in.
- Q: When an admin attempts to delete a client that has content items, comments, or active magic links, what should happen? → A: Soft delete (like users) - Mark client as inactive/deleted but preserve all data. Client portal remains read-only via magic links.
- Q: Should admins be allowed to edit their own user account (name, email, role) or delete themselves? → A: Allow all - Admins can edit their own name, email, role, and even delete themselves (with confirmation).
- Q: What naming convention should be used for audit event names? → A: Human readable - Examples: "User Created", "Content Approved", "Client Deleted" (display-friendly format).
- Q: What user roles should be available when creating/editing users in the admin interface? → A: All three roles - Admin can create users with Admin, Agency Team, or Client roles.

## Clarifications Needed

1. **Audit log retention**: Is there a retention policy for audit logs? Should old logs be archived or deleted after a certain period? Answer: no

2. **User password management**: When editing users, should admins be able to reset passwords? Or should password resets be handled through a separate "Send Reset Link" mechanism? Answer: admins can edit user passwords.

3. **Filter persistence**: Should audit log filters persist across sessions (stored in user preferences) or reset on page reload? Answer: no.

4. **Client-Team relationship**: Can a client belong to multiple teams, or is it one-to-many? Does this affect the client management CRUD interface? Answer: one-to-many.

5. **Slack channel validation**: Should the system validate that the selected Slack channel still exists and the bot has access before saving? Or handle stale mappings gracefully at notification time? Answer: no.
