# Joy — Comprehensive BDD Test Scenarios
*Author: Fluffy 🚀 | Date: 2026-02-21 | Status: DRAFT*
*Covers all known functionality from FRD, user stories, routes, and codebase analysis*

---

## 1. Authentication & Authorization

```gherkin
Feature: User Authentication
  As a user, I need secure access to Joy based on my role

  Scenario: Admin logs in successfully
    Given I am an admin user with valid credentials
    When I submit the login form at /login
    Then I am redirected to /admin (Filament panel)

  Scenario: Agency team member logs in
    Given I am an agency user with valid credentials
    When I submit the login form
    Then I am redirected to /calendar/agency

  Scenario: Client user logs in
    Given I am a client user with valid credentials
    When I submit the login form
    Then I am redirected to /calendar/client

  Scenario: Invalid credentials rejected
    Given I provide incorrect email or password
    When I submit the login form
    Then I see an error message and remain on /login

  Scenario: Unauthenticated user redirected
    Given I am not logged in
    When I visit any protected route
    Then I am redirected to /login

  Scenario: Logout via POST
    Given I am logged in
    When I click the logout button (POST /logout)
    Then my session is destroyed and I am redirected to /login

  Scenario: Logout via GET (expired session fallback)
    Given my session has expired
    When I visit /logout (GET)
    Then I am redirected to /login without CSRF errors
```

## 2. Admin Panel (Filament)

```gherkin
Feature: Client Management (Admin)
  As an admin, I manage client records

  Scenario: List all clients
    Given I am logged in as admin
    When I visit the Clients resource in /admin
    Then I see a table of all clients

  Scenario: Create a new client
    Given I am on the Create Client page
    When I fill in client details and save
    Then a new Client record is created
    And an audit log entry is recorded

  Scenario: Edit an existing client
    Given a client "Acme Corp" exists
    When I edit the client name to "Acme Corporation"
    Then the client record is updated
    And an audit log entry is recorded

  Scenario: Delete a client
    Given a client exists with no active content
    When I delete the client
    Then the client record is removed
    And an audit log entry is recorded

Feature: User Management (Admin)
  As an admin, I manage team members and their roles

  Scenario: List all users
    Given I am logged in as admin
    When I visit the Users resource
    Then I see all users with their roles

  Scenario: Create a new user with role
    Given I am on the Create User page
    When I fill in user details and assign the "agency" role
    Then a new user is created with agency permissions

  Scenario: Edit user role
    Given a user exists with the "agency" role
    When I change their role to "admin"
    Then their permissions update accordingly

Feature: Role Management (Admin)
  As an admin, I manage roles and permissions

  Scenario: List all roles
    Given I am logged in as admin
    When I visit the Roles resource
    Then I see all defined roles with their permissions

  Scenario: Create a custom role
    Given I am on the Create Role page
    When I define a role with specific permissions
    Then the role is created and assignable to users
```

## 3. Content Management

```gherkin
Feature: Content Creation
  As an agency team member, I create and manage content concepts with variants

  Scenario: Create a new content concept
    Given I am logged in as agency
    And I am on the Add Content page (/content/add/agency)
    When I create a concept with title "February Campaign"
    Then a ContentItem is created with status "Draft"
    And an audit log entry is recorded

  Scenario: Add platform variants to a concept
    Given a concept "February Campaign" exists
    When I add variants for Facebook, Instagram, and LinkedIn
    Then each variant has platform-specific fields (copy, media, schedule)
    And platform icons are displayed correctly

  Scenario: Edit variant copy and schedule
    Given a variant exists for "February Campaign" on Facebook
    When I update the copy and scheduled date
    Then the variant reflects the changes
    And an audit log entry is recorded

  Scenario: Delete a variant
    Given a variant exists that hasn't been approved
    When I delete the variant
    Then it is removed from the concept
    And the concept status recalculates

  Scenario: Content status progression
    Given a content item is in "Draft" status
    When the agency moves it to "In Review"
    Then the status updates to "In Review"
    And an audit log is created
```

## 4. Calendar & Timeline Views

```gherkin
Feature: Calendar View
  As a user, I see content organized in a monthly calendar grid

  Scenario: View monthly calendar
    Given I am logged in
    And content is scheduled for March 2026
    When I navigate to the calendar view for March 2026
    Then I see a monthly grid with content items on their scheduled dates

  Scenario: Calendar shows platform colors
    Given variants exist for Facebook and Instagram on the same date
    When I view the calendar
    Then each variant shows its platform-specific icon/color

  Scenario: Calendar shows approval status
    Given a variant is approved and another is pending
    When I view the calendar
    Then approved items show approval indicator
    And pending items show pending indicator

  Scenario: Calendar shows comment count
    Given a variant has 3 comments
    When I view the calendar
    Then the comment count badge shows "3"

  Scenario: Click item opens detail drawer
    Given I see a content item on the calendar
    When I click on it
    Then a detail drawer opens showing full variant details

  Scenario: Navigate between months
    Given I am viewing March 2026
    When I click the next month arrow
    Then I see April 2026 calendar

Feature: Timeline View
  As a user, I see content in a chronological list

  Scenario: Timeline default ascending order
    Given content exists across multiple dates
    When I open the timeline view
    Then items are listed in ascending chronological order

  Scenario: Timeline descending sort
    Given I am viewing the timeline in ascending order
    When I toggle to descending sort
    Then items are listed newest first

  Scenario: Timeline shows same detail as calendar
    Given a variant exists with platform, copy, and status
    When I view it in the timeline
    Then I see the platform icon, copy preview, status, and comment count
```

## 5. Client-Facing Views (Calendar Role Routing)

```gherkin
Feature: Role-Based Calendar Views
  As different user types, I see appropriate content for my role

  Scenario: Agency sees all clients
    Given I am logged in as agency
    When I visit /calendar/agency
    Then I can see content for all clients
    And I can switch between clients

  Scenario: Client sees only their content
    Given I am logged in as a client user for "Acme Corp"
    When I visit /calendar/client
    Then I see only Acme Corp's content

  Scenario: Client-specific calendar via URL
    Given I am logged in as agency
    When I visit /calendar/agency/client/5
    Then I see only client #5's content calendar
```

## 6. Magic Link Access

```gherkin
Feature: Magic Link Generation and Access
  As an agency member, I share content with clients via magic links

  Scenario: Generate a magic link with full permissions
    Given I am an agency user working on Acme Corp content
    When I generate a magic link with view, comment, and approve scopes
    Then a unique token URL is created (/client/{token})
    And the link details are logged in the audit trail

  Scenario: Generate a magic link with view-only scope
    Given I am generating a share link
    When I select only "view" scope
    Then the client can view but cannot comment or approve

  Scenario: Magic link with expiry
    Given I generate a link with 7-day expiry
    When the client opens it on day 6
    Then access is granted
    When the client opens it on day 8
    Then an expiry message is shown

  Scenario: Magic link with PIN
    Given I generate a link with PIN "1234"
    When the client opens the link
    Then they must enter the PIN before accessing content

  Scenario: Client accesses via magic link
    Given a valid magic link exists with comment and approve scopes
    When the client opens /client/{token}
    Then they see the calendar and timeline views
    And they can comment and approve items

  Scenario: Expired magic link denied
    Given a magic link expired yesterday
    When the client opens it
    Then an expiry/invalid message is shown
    And no content is accessible

  Scenario: Revoked magic link denied
    Given an agency member revoked a magic link
    When the client opens the previously valid link
    Then access is denied

  Scenario: Magic link calendar navigation
    Given a client accessed via magic link
    When they navigate to /client/{token}/calendar
    Then they see the calendar view for their client's content
```

## 7. Comments & Trello Integration

```gherkin
Feature: Client Comments
  As a client, I provide feedback on content items

  Scenario: Client adds a comment via magic link
    Given I accessed content via magic link with comment scope
    And I opened a variant in the detail drawer
    When I type a comment and submit
    Then the comment is saved and visible on the variant
    And the comment count increments

  Scenario: Agency sees client comments
    Given a client commented on a variant
    When an agency member opens the variant detail
    Then they see the client's comment with timestamp

  Scenario: Comment without permission denied
    Given I have a view-only magic link
    When I try to add a comment
    Then the comment form is not available

Feature: Trello Comment Sync
  As an agency member, I want comments to sync to Trello automatically

  Scenario: Comment syncs to existing Trello card
    Given a variant is mapped to Trello card "abc123"
    When a client adds a comment
    Then the comment appears on Trello card "abc123"
    And the comment includes a backlink to the Joy item

  Scenario: Comment creates new Trello card if unmapped
    Given a variant has no Trello card mapping
    When a client adds a comment
    Then a new Trello card is created
    And the variant is mapped to the new card
    And the comment is posted to the new card

  Scenario: Trello sync failure is queued for retry
    Given the Trello API is unavailable
    When a client adds a comment
    Then the comment is saved locally
    And a background job is queued for retry
    And no error is shown to the client

  Scenario: Trello sync retry succeeds
    Given a Trello sync job failed and was queued
    When the job retries and Trello is available
    Then the comment syncs successfully
    And the job is marked complete
```

## 8. Approval Workflow

```gherkin
Feature: Content Approval
  As a client, I approve content items for publishing

  Scenario: Approve a single variant
    Given I have approve permissions via magic link
    And I opened a variant in the detail drawer
    When I click "Approve"
    Then the variant status changes to "Approved"
    And an audit log entry is created with approver and timestamp

  Scenario: Reject a variant
    Given I have approve permissions
    When I click "Reject" on a variant
    Then the variant status changes to "Rejected"
    And the agency is notified

  Scenario: Concept auto-approves when all variants approved
    Given a concept has 3 variants
    And 2 are already approved
    When I approve the 3rd variant
    Then the concept status becomes "Approved"

  Scenario: Concept shows partial approval
    Given a concept has 3 variants
    And 1 is approved, 2 are pending
    Then the concept status shows "Partially Approved" or similar indicator

  Scenario: Cannot approve without permission
    Given I have a view-only magic link
    When I view a variant
    Then no "Approve" button is visible
```

## 9. Statusfaction (Weekly Client Health)

```gherkin
Feature: Statusfaction - Weekly Status Reports
  As an account manager, I track weekly client satisfaction

  Scenario: Access Statusfaction
    Given I am logged in with "access statusfaction" permission
    When I visit /statusfaction
    Then I see the Statusfaction dashboard

  Scenario: Submit weekly status update
    Given I am on the Statusfaction page for client "Acme Corp"
    And the current week has no status submitted
    When I enter satisfaction score (7) and team health score (8)
    And I submit the status
    Then a ClientStatusfactionUpdate record is created for this week

  Scenario: One status per client per week
    Given a status already exists for Acme Corp for this week
    When I try to submit another status for the same week
    Then the existing status is updated (not duplicated)

  Scenario: Week boundaries (Sunday to Saturday)
    Given today is Wednesday Feb 19
    When I submit a status update
    Then it is associated with the week of Feb 16-22

  Scenario: View 5-week trend graph
    Given status updates exist for the past 5 weeks
    When I view the Statusfaction dashboard for Acme Corp
    Then I see a Chart.js trend graph showing satisfaction over 5 weeks

  Scenario: Admin approval workflow
    Given an agency member submitted a status update
    When an admin reviews and approves it
    Then the status becomes visible to the client

  Scenario: Client cannot see unapproved status
    Given a status update is submitted but not yet approved
    When the client views their Statusfaction page
    Then the unapproved status is not visible

  Scenario: Role-based Statusfaction access
    Given I visit /statusfaction/agency
    Then I see the agency view of status reports
    Given I visit /statusfaction/admin
    Then I see the admin view with approval controls
```

## 10. Slack Integration

```gherkin
Feature: Slack Notifications
  As an agency, we receive Slack notifications for key events

  Scenario: Slack workspace is configured
    Given an admin has connected a Slack workspace
    Then SlackWorkspace and SlackNotification models are populated

  Scenario: Notification on client approval
    Given Slack is configured for Acme Corp
    When a client approves a variant
    Then a Slack notification is sent to the configured channel

  Scenario: Notification via Trello-Slack bridge
    Given Trello is connected to Slack
    When a comment syncs to Trello
    Then Slack receives the notification via Trello's integration
```

## 11. Audit Trail

```gherkin
Feature: Audit Trail
  As an admin, I track all system activity for compliance

  Scenario: Content creation logged
    Given an agency member creates a new concept
    Then an AuditLog entry records the action, actor, and timestamp

  Scenario: Approval logged
    Given a client approves a variant
    Then an AuditLog entry records the approver, item, and timestamp

  Scenario: Magic link events logged
    Given a magic link is generated
    Then an AuditLog entry records the creation
    When the link is accessed
    Then an AuditLog entry records the access event

  Scenario: Comment logged
    Given a client posts a comment
    Then an AuditLog entry records the comment event

  Scenario: Trello sync logged
    Given a comment syncs to Trello
    Then an AuditLog entry records the sync status

  Scenario: Admin views audit trail
    Given audit entries exist
    When I visit the audit trail page as admin
    Then I see a chronological list of all events with actor, action, and timestamp

  Scenario: Agency views audit trail
    Given audit entries exist
    When I visit the audit trail page as agency
    Then I see audit entries relevant to my clients
```

## 12. Trello Integration Setup

```gherkin
Feature: Trello Integration Configuration
  As an admin, I configure the Trello integration

  Scenario: Connect Trello account
    Given I am an admin
    When I configure the Trello integration with API key and token
    Then the TrelloIntegration is saved
    And I can map boards/lists to clients

  Scenario: Map client to Trello board
    Given Trello is connected
    When I assign a Trello board to client "Acme Corp"
    Then content items for Acme Corp can sync to that board
```

## 13. Edge Cases & Error Handling

```gherkin
Feature: Edge Cases
  Various edge cases and error handling

  Scenario: Content with no variants
    Given a concept exists with no variants
    When I view it on the calendar
    Then it does not appear (only variants with dates show)

  Scenario: Multiple variants on same date
    Given 3 variants are scheduled for March 15
    When I view the calendar
    Then all 3 appear on March 15 (stacked or expandable)

  Scenario: Very long comment text
    Given a client is commenting on a variant
    When they submit a comment with 5000 characters
    Then the comment is saved and displayed correctly

  Scenario: Concurrent approvals
    Given two clients open the same variant simultaneously
    When both click approve at the same time
    Then no duplicate audit entries are created
    And the variant status is "Approved"

  Scenario: Media URL is broken
    Given a variant has a media URL that returns 404
    When viewing the variant detail
    Then a placeholder image is shown (not a broken image icon)
```

## 14. NPS Surveys (NEW — Future Feature)

```gherkin
Feature: Client NPS Surveys
  As an admin, I measure client satisfaction through NPS surveys

  Scenario: Create NPS survey campaign
    Given I am an admin
    When I create an NPS survey for client "Acme Corp"
    Then the survey is saved with send date and status "draft"

  Scenario: Send NPS survey email
    Given an NPS survey is ready to send
    When I trigger the send
    Then an HTML email is sent with clickable buttons 1-10
    And an optional comment text box
    And a SlackNotification is optionally triggered

  Scenario: Client responds to NPS survey
    Given a client received an NPS email
    When they click score "9"
    Then their response is recorded
    And they see a thank-you page with optional comment box

  Scenario: Client provides additional feedback
    Given a client clicked score "7"
    When they type additional feedback and submit
    Then both the score and comment are saved

  Scenario: View NPS activity dashboard
    Given multiple NPS surveys have been sent
    When I visit the NPS dashboard
    Then I see: surveys sent, response rate, score distribution, average NPS
    And I can see who responded vs who ignored

  Scenario: NPS score classification
    Given responses exist
    Then scores 9-10 are classified as "Promoters"
    And scores 7-8 are classified as "Passives"
    And scores 1-6 are classified as "Detractors"
    And NPS = %Promoters - %Detractors

  Scenario: Toggle survey sending per client
    Given I am an admin viewing Acme Corp's NPS settings
    When I toggle "Send surveys" off
    Then no automated NPS emails are sent to Acme Corp

  Scenario: NPS feeds into Statusfaction
    Given a client responded with NPS score 8
    When I view their Statusfaction dashboard
    Then the NPS score is displayed alongside weekly status updates
```

---

## Coverage Matrix

| Feature Area | Scenarios | Models Involved |
|-------------|-----------|-----------------|
| Authentication | 7 | User |
| Admin - Clients | 4 | Client, AuditLog |
| Admin - Users | 3 | User, Role |
| Admin - Roles | 2 | Role |
| Content Mgmt | 5 | ContentItem, AuditLog |
| Calendar View | 6 | ContentItem |
| Timeline View | 3 | ContentItem |
| Role Routing | 3 | User, Client |
| Magic Links | 8 | MagicLink, AuditLog |
| Comments | 4 | Comment |
| Trello Sync | 4 | TrelloCard, TrelloIntegration |
| Approvals | 5 | ContentItem, AuditLog |
| Statusfaction | 8 | ClientStatusfactionUpdate, Status |
| Slack | 3 | SlackWorkspace, SlackNotification |
| Audit Trail | 7 | AuditLog |
| Trello Setup | 2 | TrelloIntegration |
| Edge Cases | 5 | Various |
| NPS (Future) | 8 | New models TBD |
| **TOTAL** | **87** | |

## Next Steps
1. JP to review and confirm which scenarios match actual current behavior
2. Run regression: manually test each scenario against deployed Joy
3. Mark each as PASS / FAIL / NOT IMPLEMENTED
4. Convert failures into GitHub Issues
5. Convert "not implemented" into backlog items
