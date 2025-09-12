# User Stories

## 1. Admin manages clients and team
**As an Admin**, I want to add/edit/remove clients and team members so that I can maintain proper workspace access control.
- Can add/edit/remove clients, team members.
- Changes logged in audit trail.

## 2. Agency creates concepts + variants
**As an Agency Team member**, I want to create content concepts with platform-specific variants so that I can plan multi-platform content campaigns.
- Add concept and variants with platform, copy, media, schedule.
- Platform icons display.

## 3. Agency views items in calendar/timeline
**As an Agency Team member**, I want to view content in both calendar and timeline formats so that I can manage schedules effectively.
- Calendar = monthly grid; timeline = chronological list.
- Click item → detail drawer.

## 4. Agency shares via magic link
**As an Agency Team member**, I want to generate secure magic links for clients so that they can review content without needing accounts.
- Create link with scopes, expiry, optional PIN.
- Revoke links.
- Events logged.

## 5. Client reviews and comments
**As a Client**, I want to review content and add comments so that I can provide feedback on proposed content.
- Open link, see calendar and timeline.
- Add comments → Trello sync.
- Queued if Trello down.

## 6. Client approves items
**As a Client**, I want to approve content items so that the agency knows which content is ready to publish.
- Approve button in detail drawer.
- Status updates and audit trail entry created.

## 7. Agency maps to Trello
**As an Agency Team member**, I want comments to automatically sync to Trello so that our existing workflow tools stay updated.
- On comment, create card if none exists.
- Mapping persists.

## 8. Agency/Admin views audit trail
**As an Admin or Agency Team member**, I want to view a complete audit trail so that I can track all activity for compliance and troubleshooting.
- Shows events by type, timestamp, and actor.

## 9. Agency branded views
**As a Client**, I want to see MajorMajor branding on client-facing pages so that I know I'm working with the right agency.
- Client-facing pages show MajorMajor branding only.

## 10. Resilience
**As an Agency Team member**, I want the system to handle Trello integration failures gracefully so that client work isn't disrupted by third-party issues.
- Background jobs retry Trello sync.
- Status indicators show sync success/failure.