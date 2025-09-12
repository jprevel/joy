# Functional Requirements Document (FRD)

## 1) Overview
A web app for MajorMajor Digital Marketing to manage client content calendars in a **monthly calendar view** and **timeline list view**. Agency creates/edits content; clients review, comment, and approve via magic-link access. Comments sync to Trello cards; Slack visibility happens via Trello's existing Slack integration. Supports per-platform variants (FB/IG/LI/Blog) with platform icons. Branded for MajorMajor.

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

## 9) Non-Functional Requirements
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