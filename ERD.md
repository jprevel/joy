# Entity Relationship Diagram (ERD)

## Database Schema Overview

```
[ClientWorkspace] 1───* [Concept]
[ClientWorkspace] 1───* [MagicLink]
[ClientWorkspace] 1───* [AgencyUser]

[Concept] 1───* [Variant]

[Variant] *───1 [TrelloCard]
[Variant] 1───* [Comment]
[Variant] 1───* [AuditLog]

[MagicLink] *───* [AuditLog]
```

## Entity Definitions

### ClientWorkspace
- **id**: Primary key
- **name**: Client workspace name
- **logo**: Client logo URL/path
- **trelloBoardId**: Associated Trello board ID
- **trelloListId**: Associated Trello list ID

### AgencyUser
- **id**: Primary key
- **workspaceId**: Foreign key to ClientWorkspace
- **role**: User role (Admin, Agency Team)
- **name**: User full name
- **email**: User email address

### MagicLink
- **id**: Primary key
- **workspaceId**: Foreign key to ClientWorkspace
- **token**: Unique access token
- **scopes**: Permissions (view/comment/approve)
- **expiry**: Link expiration timestamp
- **pin**: Optional PIN for additional security

### Concept
- **id**: Primary key
- **workspaceId**: Foreign key to ClientWorkspace
- **title**: Concept title
- **notes**: Internal notes
- **ownerId**: Foreign key to AgencyUser
- **status**: Status (Draft/In Review/Approved/Scheduled)
- **dueDate**: Internal due date

### Variant
- **id**: Primary key
- **conceptId**: Foreign key to Concept
- **platform**: Platform type (FB/IG/LI/Blog)
- **copy**: Content copy/text
- **mediaUrl**: Media file URL
- **scheduledAt**: Scheduled publication date/time
- **status**: Variant status
- **trelloCardId**: Foreign key to TrelloCard

### Comment
- **id**: Primary key
- **variantId**: Foreign key to Variant
- **authorType**: Author type (client/agency)
- **body**: Comment content
- **createdAt**: Comment timestamp

### TrelloCard
- **id**: Primary key
- **variantId**: Foreign key to Variant
- **trelloId**: Trello card ID
- **url**: Trello card URL

### AuditLog
- **id**: Primary key
- **variantId**: Foreign key to Variant (nullable)
- **actor**: User who performed the action
- **action**: Action type
- **timestamp**: When the action occurred
- **details**: Additional action details (JSON)