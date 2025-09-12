# Functional Requirements (FRD)

## 1) Overview
A web app for MajorMajor Digital Marketing to manage client content calendars in a **monthly calendar view** and **timeline list view**. Agency creates/edits content; clients review, comment, and approve via magic-link access. Comments sync to Trello cards; Slack visibility happens via Trello’s existing Slack integration. Supports per-platform variants (FB/IG/LI/Blog) with platform icons. Branded for MajorMajor.

## 2) Roles & Permissions
- **Admin (MajorMajor):** manage clients & team (CRUD), global settings, workspace ownership, view audit trail.
- **Agency Team (MajorMajor):** CRUD content items & variants, move status, generate client share links, view comments, view audit trail.
- **Client (per workspace):** open magic link, view content, comment, approve items.

## 3) Sharing & Access
- Workspace per client.
- Magic links with scopes (view/comment/approve), optional expiry and PIN.
- No client login required.

## 4) Content Model
- **Concept** with **Variants** per platform.
  - Concept fields: Title, Notes, Owner, Status (Draft / In Review / Approved / Scheduled), Internal due date, Audit trail.
  - Variant fields: Platform, Scheduled date/time, Copy, Media URL, Thumbnail, Status, Client comments, Trello Card ID.
- Platform icons (FB, IG, LI, Blog).

## 5) Views & UX
- **Calendar view**: monthly only; shows variants by date/time, colored by platform, approval status, and comment count.
- **Timeline view**: chronological list, sorted ascending/descending by date.
- **Detail drawer**: opens on click; agency can edit, client can comment/approve.
- **No filters** in v1.

## 6) Workflow
1. Agency creates Concept + Variants.
2. Share via magic link.
3. Clients review items, comment, or approve.
4. Approvals roll up from variant → concept (full vs. partial approval).

## 7) Comments & Integrations
- Client comments sync to Trello (create card if missing, append otherwise).
- Trello comments include backlink to the item.
- Slack notifications handled by Trello → Slack integration.

## 8) Audit Trail
- Logs: item creation/edit/deletion, approvals, link events, comment posts, Trello sync.
- Admin & Agency can view.

## 9) Non-Functional
- Built with **TALL stack + Filament**.
- Responsive web app.
- Background jobs handle Trello sync with retries.

## 10) Out of Scope (v1)
- Direct publishing to platforms.
- Imports/exports.
- Advanced tagging/UTM.
- Multi-step approvals.

## 11) Assumptions
- Trello service account available.
- Slack visibility fully via Trello integration.
- Media hosted externally.

## 12) Dependencies
- Trello API.
- Email service (optional for sending magic links).

## 13) Success Metrics
- Faster client approvals.
- High adoption of magic links.
- Reliable Trello sync (>99%).

---

# BDD Scenarios (Gherkin)

```gherkin
Feature: Magic link access
  Scenario: Client opens valid link
    Given a client has a magic link with comment and approve permissions
    When they open it
    Then they see the monthly calendar and timeline list
    And they can comment and approve items

  Scenario: Expired link
    Given a magic link expired yesterday
    When opened
    Then an expiry message is shown

Feature: Calendar + timeline views
  Scenario: Calendar monthly view
    Given multiple variants scheduled in September
    When the calendar is viewed
    Then items are shown in a grid for each day

  Scenario: Timeline chronological list
    Given multiple variants with dates
    When the timeline view is opened
    Then items are listed in ascending order by default

Feature: Comments → Trello
  Scenario: Comment on mapped item
    Given a variant mapped to Trello card X
    When client adds a comment
    Then the same comment appears on card X

  Scenario: Comment on unmapped item
    Given a variant without a Trello card
    When client comments
    Then a new Trello card is created and the comment posted

Feature: Approvals
  Scenario: Approve a variant
    Given client has approve permissions
    When they click Approve
    Then the variant status becomes Approved

  Scenario: Concept full approval
    Given all variants in a concept are approved
    Then the concept status is Approved

Feature: Audit trail
  Scenario: Log approval
    Given a client approves a variant
    Then the audit trail records approver and timestamp
```

---

# User Stories

### 1. Admin manages clients and team
- Can add/edit/remove clients, team members.
- Changes logged in audit trail.

### 2. Agency creates concepts + variants
- Add concept and variants with platform, copy, media, schedule.
- Platform icons display.

### 3. Agency views items in calendar/timeline
- Calendar = monthly grid; timeline = chronological list.
- Click item → detail drawer.

### 4. Agency shares via magic link
- Create link with scopes, expiry, optional PIN.
- Revoke links.
- Events logged.

### 5. Client reviews and comments
- Open link, see calendar and timeline.
- Add comments → Trello sync.
- Queued if Trello down.

### 6. Client approves items
- Approve button in detail drawer.
- Status updates and audit trail entry created.

### 7. Agency maps to Trello
- On comment, create card if none exists.
- Mapping persists.

### 8. Agency/Admin views audit trail
- Shows events by type, timestamp, and actor.

### 9. Agency branded views
- Client-facing pages show MajorMajor branding only.

### 10. Resilience
- Background jobs retry Trello sync.
- Status indicators show sync success/failure.

---

# ERD (Entity Relationship Diagram)

```
[ClientWorkspace] 1───* [Concept]
[ClientWorkspace] 1───* [MagicLink]
[ClientWorkspace] 1───* [AgencyUser]

[Concept] 1───* [Variant]

[Variant] *───1 [TrelloCard]
[Variant] 1───* [Comment]
[Variant] 1───* [AuditLog]

[MagicLink] *───* [AuditLog]

Entities:
- ClientWorkspace(id, name, logo, trelloBoardId, trelloListId)
- AgencyUser(id, workspaceId, role, name, email)
- MagicLink(id, workspaceId, token, scopes, expiry, pin)
- Concept(id, workspaceId, title, notes, ownerId, status, dueDate)
- Variant(id, conceptId, platform, copy, mediaUrl, scheduledAt, status, trelloCardId)
- Comment(id, variantId, authorType[client/agency], body, createdAt)
- TrelloCard(id, variantId, trelloId, url)
- AuditLog(id, variantId, actor, action, timestamp, details)
```

---

