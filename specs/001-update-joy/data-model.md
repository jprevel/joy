# Data Model: Joy Content Calendar System

## Core Entities

### User
**Purpose**: System users (Admin, Agency staff) with authentication and role management
**Fields**:
- `id`: Primary key (UUID)
- `name`: Full name
- `email`: Unique email address
- `email_verified_at`: Email verification timestamp
- `password`: Hashed password
- `role`: Enum (admin, agency)
- `created_at`, `updated_at`: Timestamps

**Relationships**:
- `hasMany(ContentItem)` - owns content items
- `hasMany(Comment)` - can author comments
- `hasMany(AuditLog)` - actions logged

**Validation Rules**:
- Email must be unique and valid format
- Password minimum 8 characters
- Role must be valid enum value

### Client
**Purpose**: Client organizations with workspace isolation and magic link access
**Fields**:
- `id`: Primary key (UUID)
- `name`: Client organization name
- `logo`: Optional logo file path
- `trello_board_id`: Associated Trello board
- `trello_list_id`: Associated Trello list
- `created_at`, `updated_at`: Timestamps

**Relationships**:
- `hasMany(ContentItem)` - owns content items
- `hasMany(MagicLink)` - access tokens
- `hasMany(AuditLog)` - workspace actions logged

**Validation Rules**:
- Name required, maximum 255 characters
- Logo must be valid image file if provided

### ContentItem
**Purpose**: Core content entity with platform variants, scheduling, and workflow status
**Fields**:
- `id`: Primary key (UUID)
- `client_id`: Foreign key to Client
- `user_id`: Foreign key to User (creator)
- `title`: Content title
- `description`: Content description
- `platform`: Enum (facebook, instagram, linkedin, twitter, blog)
- `status`: Enum (draft, review, approved, scheduled)
- `scheduled_at`: Scheduled publication timestamp
- `media_path`: Optional media file path
- `created_at`, `updated_at`: Timestamps

**Relationships**:
- `belongsTo(Client)` - owned by client
- `belongsTo(User)` - created by user
- `hasMany(Comment)` - receives feedback
- `hasOne(TrelloCard)` - mapped to Trello
- `hasMany(AuditLog)` - actions logged

**State Transitions**:
- Draft → Review (agency action)
- Review → Approved (client action)
- Review → Draft (client rejection)
- Approved → Scheduled (system/agency action)

**Validation Rules**:
- Title required, maximum 255 characters
- Platform must be valid enum value
- Status must be valid enum value
- Scheduled date must be future date when status is scheduled

### MagicLink
**Purpose**: Secure access tokens with expiration, scopes, and optional PIN protection
**Fields**:
- `id`: Primary key (UUID)
- `client_id`: Foreign key to Client
- `token`: Unique secure token (UUID)
- `scopes`: JSON array of permissions (view, comment, approve)
- `expires_at`: Token expiration timestamp
- `pin`: Optional 4-digit PIN
- `accessed_at`: Last access timestamp
- `created_at`, `updated_at`: Timestamps

**Relationships**:
- `belongsTo(Client)` - grants access to client workspace
- `hasMany(AuditLog)` - access events logged

**Validation Rules**:
- Token must be unique UUID
- Scopes must be valid JSON array
- PIN must be 4 digits if provided
- Expiration must be future date

### Comment
**Purpose**: Client feedback linked to content items with Trello synchronization
**Fields**:
- `id`: Primary key (UUID)
- `content_item_id`: Foreign key to ContentItem
- `user_id`: Foreign key to User (nullable for client comments)
- `author_name`: Name of commenter (for client comments)
- `content`: Comment text
- `is_internal`: Boolean (agency-only comments)
- `created_at`, `updated_at`: Timestamps

**Relationships**:
- `belongsTo(ContentItem)` - comment on content
- `belongsTo(User)` - authored by user (nullable)
- `hasOne(TrelloCard)` - synced to Trello
- `hasMany(AuditLog)` - comment actions logged

**Validation Rules**:
- Content required, maximum 2000 characters
- Either user_id or author_name must be present
- Author_name required for client comments

### TrelloCard
**Purpose**: Integration reference for external task management and notifications
**Fields**:
- `id`: Primary key (UUID)
- `content_item_id`: Foreign key to ContentItem (nullable)
- `comment_id`: Foreign key to Comment (nullable)
- `trello_card_id`: External Trello card ID
- `trello_board_id`: External Trello board ID
- `trello_list_id`: External Trello list ID
- `sync_status`: Enum (pending, synced, failed)
- `last_synced_at`: Last successful sync timestamp
- `created_at`, `updated_at`: Timestamps

**Relationships**:
- `belongsTo(ContentItem)` - maps to content (nullable)
- `belongsTo(Comment)` - maps to comment (nullable)

**Validation Rules**:
- Either content_item_id or comment_id must be present
- Trello IDs must be valid external references
- Sync status must be valid enum value

### AuditLog
**Purpose**: Comprehensive activity tracking for all system actions and user interactions
**Fields**:
- `id`: Primary key (UUID)
- `user_id`: Foreign key to User (nullable for system events)
- `client_id`: Foreign key to Client (nullable)
- `auditable_type`: Polymorphic type
- `auditable_id`: Polymorphic ID
- `event`: Event type (created, updated, deleted, accessed, etc.)
- `old_values`: JSON of previous values
- `new_values`: JSON of new values
- `ip_address`: Request IP address
- `user_agent`: Request user agent
- `created_at`: Event timestamp

**Relationships**:
- `belongsTo(User)` - action performed by (nullable)
- `belongsTo(Client)` - action within workspace (nullable)
- `morphTo()` - auditable entity

**Validation Rules**:
- Event must be valid event type
- IP address must be valid format
- JSON fields must be valid JSON

## Entity Relationships Overview

```
User (1) → (∞) ContentItem
Client (1) → (∞) ContentItem
Client (1) → (∞) MagicLink
ContentItem (1) → (∞) Comment
ContentItem (1) → (1) TrelloCard
Comment (1) → (1) TrelloCard
All Entities (1) → (∞) AuditLog
```

## Database Indexes

**Performance Indexes**:
- `content_items(client_id, status)` - Client content filtering
- `content_items(scheduled_at)` - Calendar queries
- `magic_links(token)` - Token lookup
- `magic_links(expires_at)` - Cleanup queries
- `comments(content_item_id, created_at)` - Content comments
- `audit_logs(auditable_type, auditable_id)` - Entity audit trail
- `audit_logs(created_at)` - Chronological queries

**Unique Constraints**:
- `users(email)` - Unique email addresses
- `magic_links(token)` - Unique access tokens
- `trello_cards(trello_card_id)` - Prevent duplicate Trello mappings