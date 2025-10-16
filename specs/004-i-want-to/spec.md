# Feature Specification: Slack Integration for Client Notifications

**Feature Branch**: `004-i-want-to`
**Created**: 2025-10-10
**Status**: Draft
**Input**: User description: "I want to add a slack integration feature, and I have two use cases. First I want to connect the client review to its associated slack channel, so the team is informed when a client adds comments or approves a social media post.  The second use case is for statusfaction; When the account manager submits a statusfaction report for a client, I want it to be sent to slack as well with the client name, the team name, the account manager name, and the text of the client.  For the time being I want to leave the team health and client satisfaction out of the slack notification. When an admin approves the status, I want that to be posted to slack as well so the AM knows it's been approved.  In both cases, when a new client is added, we need to be able to select the channel from our slack group to connect the client in the joy app to the client slack channel."

## Clarifications

### Session 2025-10-10

- Q: When an account manager edits and resubmits a pending Statusfaction report, what should happen to Slack notifications? → A: Do not send any notification for edits, only for initial submission
- Q: When a Slack notification fails to send (API error, network issue, etc.), what should the retry behavior be? → A: No retries - log failure and move on
- Q: Should the Slack channel selector show both public and private channels, or only public channels? → A: Both public and private channels (requires `channels:read` + `groups:read` scopes)
- Q: When a Slack notification includes a link back to Joy, where should it direct the user? → A: Directly to the specific content item (requires login if not authenticated)
- Q: Should SlackChannelCache be implemented for caching channel lists? → A: No - not needed, fetch channels from Slack API when required

## User Scenarios & Testing *(mandatory)*

<!--
  IMPORTANT: User stories should be PRIORITIZED as user journeys ordered by importance.
  Each user story/journey must be INDEPENDENTLY TESTABLE - meaning if you implement just ONE of them,
  you should still have a viable MVP (Minimum Viable Product) that delivers value.

  Assign priorities (P1, P2, P3, etc.) to each story, where P1 is the most critical.
  Think of each story as a standalone slice of functionality that can be:
  - Developed independently
  - Tested independently
  - Deployed independently
  - Demonstrated to users independently
-->

### User Story 1 - Slack Channel Selection for Client (Priority: P1)

As an admin, when I create or edit a client in the system, I need to be able to select and connect a Slack channel from our workspace to that specific client, so that all client-related notifications can be routed to the appropriate team channel.

**Why this priority**: This is foundational infrastructure - without the ability to connect clients to Slack channels, no notifications can be sent. This represents the minimum viable integration that enables all other notification features.

**Independent Test**: Can be fully tested by creating a new client in the admin panel, selecting a Slack channel from a dropdown, saving the client configuration, and verifying the channel association is persisted in the database. Delivers immediate value by establishing the client-to-channel mapping.

**Acceptance Scenarios**:

1. **Given** I am an admin viewing the client creation form, **When** I access the Slack channel field, **Then** I see a dropdown list of available channels (both public and private) from our connected Slack workspace
2. **Given** I am creating a new client, **When** I select a Slack channel and save, **Then** the client is successfully created with the Slack channel association stored
3. **Given** I am editing an existing client, **When** I change the associated Slack channel and save, **Then** the new channel association replaces the old one
4. **Given** I am viewing a client's details, **When** I look at the client configuration, **Then** I can see which Slack channel is currently associated with that client
5. **Given** a client has no Slack channel configured, **When** notifications would be sent, **Then** the system handles this gracefully (skips notification or logs warning)

---

### User Story 2 - Content Review Slack Notifications (Priority: P2)

As a team member in the agency, when a client adds a comment or approves a social media post in the content calendar, I want to receive an immediate notification in our client's Slack channel, so that I can stay informed about client feedback and respond quickly.

**Why this priority**: This is the primary use case for content collaboration. It provides immediate business value by reducing communication lag and ensuring the agency team is aware of client actions in real-time.

**Independent Test**: Can be tested by having a client (using magic link) add a comment or approve a content variant, then verifying that a Slack message appears in the configured channel with relevant details. Delivers immediate value even without Statusfaction notifications.

**Acceptance Scenarios**:

1. **Given** a client has approved a social media post, **When** the approval is saved, **Then** a Slack notification is sent to the client's associated channel with the post title, platform, approval status, timestamp, and a direct link to the content item
2. **Given** a client has added a comment to a content variant, **When** the comment is submitted, **Then** a Slack notification is sent to the client's associated channel with the client name, comment text, content item title, and a direct link to the content item detail page in Joy
3. **Given** a client has rejected a social media post, **When** the rejection is saved, **Then** a Slack notification is sent to the client's associated channel indicating the rejection
4. **Given** a client interacts with content but has no Slack channel configured, **When** the notification would be sent, **Then** the system logs the event but does not fail
5. **Given** multiple comments are added in rapid succession, **When** notifications are generated, **Then** each notification is sent independently without duplication or loss

---

### User Story 3 - Statusfaction Submission Notification (Priority: P3)

As an account manager, when I submit a weekly Statusfaction report for a client, I want the status notes to be automatically posted to the client's Slack channel, so that the team is immediately aware of the client's current status and any important updates.

**Why this priority**: This provides valuable transparency for status reporting but is less time-critical than immediate client interaction notifications. It can be implemented after the core notification infrastructure is in place.

**Independent Test**: Can be tested by submitting a Statusfaction report through the Joy interface, then verifying a Slack message is posted with the client name, team name, account manager name, and status notes (excluding satisfaction scores). Delivers value independently by automating status communication.

**Acceptance Scenarios**:

1. **Given** I am an account manager submitting a Statusfaction report, **When** I save the report with status notes, **Then** a Slack notification is posted to the client's channel containing client name, team name, my name, and the status notes text
2. **Given** I submit a Statusfaction report, **When** the Slack notification is generated, **Then** the client satisfaction and team health scores are NOT included in the message
3. **Given** I edit and resubmit an existing pending Statusfaction report, **When** I save the changes, **Then** no Slack notification is sent (notifications only sent for initial submission)
4. **Given** a client has no Slack channel configured, **When** I submit a Statusfaction report, **Then** the report saves successfully but no Slack notification is sent
5. **Given** I submit a report with rich text formatting in status notes, **When** the Slack notification is generated, **Then** the formatting is preserved appropriately for Slack's markdown

---

### User Story 4 - Statusfaction Approval Notification (Priority: P4)

As an account manager, when an admin approves my Statusfaction report for a client, I want to receive a notification in the client's Slack channel, so that I know the report has been reviewed and is now visible to stakeholders.

**Why this priority**: This completes the Statusfaction workflow notification cycle but is least critical since approval is an internal process. It provides transparency and closure for the AM but doesn't directly impact client communication.

**Independent Test**: Can be tested by having an admin approve a pending Statusfaction report, then verifying a Slack notification is posted indicating approval, with the admin's name and timestamp. Delivers value independently by completing the status reporting feedback loop.

**Acceptance Scenarios**:

1. **Given** an admin approves a pending Statusfaction report, **When** the approval is saved, **Then** a Slack notification is posted to the client's channel indicating the report was approved, including the admin's name and timestamp
2. **Given** a report is approved, **When** the Slack notification is sent, **Then** it clearly indicates this is an approval notification (distinct from submission notification)
3. **Given** multiple reports are approved in a batch, **When** notifications are generated, **Then** each approval generates its own distinct notification
4. **Given** a client has no Slack channel configured, **When** an admin approves a report, **Then** the approval saves successfully but no Slack notification is sent

---

### Edge Cases

- What happens when the Slack API is temporarily unavailable? (System logs the failure without retrying; user actions in Joy continue unaffected)
- What happens when a client's Slack channel is deleted or archived in Slack after being configured in Joy? (System handles gracefully with error logging; no notification sent)
- What happens when a user tries to send a notification to a private Slack channel the bot doesn't have access to? (System detects permission errors, logs failure, and continues without blocking)
- What happens when status notes or comments contain special characters, mentions (@user), or Slack formatting? (System properly escapes or preserves formatting as appropriate for Slack markdown)
- What happens when notifications are triggered in rapid succession (e.g., multiple comments at once)? (System sends each notification independently; if rate limited, subsequent notifications fail and are logged)
- What happens when a client is deleted but notifications are pending? (Notifications process normally or fail gracefully if client lookup fails)
- What happens if the Slack workspace connection is revoked or expires? (System detects auth failures, logs them, and alerts admins via system notification or email)

## Requirements *(mandatory)*

### Functional Requirements

#### Slack Workspace Integration
- **FR-001**: System MUST integrate with Slack using the official Slack API (Web API and/or Webhooks)
- **FR-002**: System MUST authenticate with Slack using OAuth 2.0 with appropriate bot token scopes (`channels:read`, `groups:read`, `chat:write`, `chat:write.public`)
- **FR-003**: System MUST be able to retrieve a list of both public and private channels from the connected Slack workspace
- **FR-004**: System MUST persist Slack workspace connection credentials securely (encrypted bot token)
- **FR-005**: System MUST validate Slack API connectivity on initial setup

#### Client-Channel Association
- **FR-006**: Admin users MUST be able to view available Slack channels when creating or editing a client
- **FR-007**: System MUST store the association between a Joy client and a Slack channel ID
- **FR-008**: System MUST allow one Slack channel to be associated with multiple Joy clients (if needed)
- **FR-009**: System MUST allow changing a client's associated Slack channel at any time
- **FR-010**: System MUST display the current Slack channel name in the client details view

#### Content Review Notifications
- **FR-011**: System MUST send a Slack notification when a client approves a content variant
- **FR-012**: System MUST send a Slack notification when a client rejects a content variant
- **FR-013**: System MUST send a Slack notification when a client adds a comment to a content item
- **FR-014**: Content review notifications MUST include: client name, action type (approve/reject/comment), content item title, platform, and timestamp
- **FR-015**: Comment notifications MUST include the full comment text
- **FR-016**: Notifications MUST include a clickable link that directs users to the specific content item detail page in Joy (users must authenticate if not logged in)

#### Statusfaction Notifications
- **FR-017**: System MUST send a Slack notification when an account manager submits a Statusfaction report for the first time
- **FR-017a**: System MUST NOT send Slack notifications when editing an existing pending Statusfaction report (only initial submission triggers notification)
- **FR-018**: Statusfaction submission notifications MUST include: client name, team name, account manager name, and status notes text
- **FR-019**: Statusfaction submission notifications MUST NOT include client satisfaction scores
- **FR-020**: Statusfaction submission notifications MUST NOT include team health scores
- **FR-021**: System MUST send a Slack notification when an admin approves a Statusfaction report
- **FR-022**: Statusfaction approval notifications MUST include: approver name, approval timestamp, and clear indication it's an approval

#### Error Handling & Reliability
- **FR-023**: System MUST handle Slack API failures gracefully without blocking user actions in Joy
- **FR-023a**: System MUST NOT retry failed Slack notifications (single attempt only, then log failure)
- **FR-024**: System MUST log all notification attempts (success and failure) for troubleshooting
- **FR-025**: System MUST validate Slack channel existence before attempting to send notifications
- **FR-026**: System MUST handle cases where a client has no configured Slack channel without throwing errors
- **FR-027**: System MUST respect Slack API rate limits and implement appropriate throttling

#### Notification Formatting
- **FR-028**: Slack notifications MUST be formatted using Slack's Block Kit for rich, structured messages
- **FR-029**: Notifications MUST use appropriate emoji/icons to distinguish notification types
- **FR-030**: Notifications MUST format timestamps in user-friendly format (e.g., "2 minutes ago")
- **FR-031**: Notifications MUST properly escape special characters in user-generated content

### Key Entities

- **SlackWorkspace**: Represents the connected Slack workspace; stores workspace ID, team name, bot token (encrypted), installation timestamp, and last sync status
- **Client**: Extended to include optional `slack_channel_id` (string) and `slack_channel_name` (string) attributes for the associated Slack channel
- **SlackNotification**: Audit log of notifications sent; stores notification type, target channel, payload, timestamp, delivery status, error message (if failed), and reference to the source entity (ClientStatusUpdate or Comment)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Admin can successfully connect Joy to Slack workspace and retrieve channel list within 60 seconds
- **SC-002**: Admin can select and save a Slack channel association for a client in under 30 seconds
- **SC-003**: 95% of content review actions (comment/approve/reject) result in successful Slack notifications within 5 seconds
- **SC-004**: 95% of Statusfaction submissions and approvals result in successful Slack notifications within 10 seconds
- **SC-005**: System handles Slack API failures gracefully with zero impact on core Joy functionality (users can still comment/approve even if Slack is down)
- **SC-006**: All Slack notification attempts are logged with sufficient detail for troubleshooting within 1 day of any reported issue
- **SC-007**: Team members report 90% satisfaction with Slack notification clarity and usefulness (via survey after 2 weeks of use)
- **SC-008**: Reduce average response time to client comments by 50% compared to pre-Slack integration baseline
