# Research Document: Slack Integration for Joy

**Feature**: Slack Integration for Client Notifications
**Date**: 2025-10-10
**Status**: Complete

## Executive Summary

This research document analyzes the technical approach for integrating Slack notifications into the Joy content calendar system. The integration will enable real-time notifications to client-specific Slack channels for content review activities and Statusfaction report submissions/approvals.

## 1. Laravel Slack Integration Options

### Option 1: Laravel Notifications with Slack Channel (RECOMMENDED)

**Description**: Use Laravel's built-in notification system with the Slack notification channel.

**Pros**:
- Native Laravel integration - follows framework conventions
- Built-in queueing support for async notifications
- Consistent with existing notification patterns (User model already has Notifiable trait)
- Supports Slack's Block Kit for rich message formatting
- Easy to test and mock in unit tests
- Handles retries and failure logging automatically

**Cons**:
- Requires Slack webhook URLs per channel (not dynamic channel selection)
- Less flexible than direct API access for advanced features

**Implementation**:
```php
// Use Laravel Notifications
php artisan make:notification ClientCommentedNotification
php artisan make:notification StatusfactionSubmittedNotification
php artisan make:notification StatusfactionApprovedNotification

// In notification class:
public function via($notifiable): array
{
    return ['slack'];
}

public function toSlack($notifiable): SlackMessage
{
    return (new SlackMessage)
        ->from('Joy Bot')
        ->content('Client has commented on a post');
}
```

### Option 2: Slack Web API with HTTP Client (ALTERNATIVE)

**Description**: Use Laravel's HTTP client to directly interact with Slack Web API.

**Pros**:
- Full control over API interactions
- Can dynamically post to any channel using channel ID
- Access to all Slack API features (users, channels, workspace info)
- Can retrieve channel lists programmatically

**Cons**:
- More manual implementation required
- Need to handle rate limiting manually
- More complex error handling and retry logic
- OAuth flow implementation required

**Implementation**:
```php
// Using Laravel HTTP facade
use Illuminate\Support\Facades\Http;

$response = Http::withToken(config('services.slack.bot_token'))
    ->post('https://slack.api.com/api/chat.postMessage', [
        'channel' => $channelId,
        'text' => 'Message text',
        'blocks' => [...] // Rich formatting
    ]);
```

### Option 3: Slack Laravel Package (NOT RECOMMENDED)

**Packages**: `laravel/slack-notification-channel` (archived), third-party packages

**Analysis**:
- Official Laravel Slack channel is now integrated into core framework
- Third-party packages add unnecessary dependencies
- Not recommended for this use case

### Recommendation: Hybrid Approach

**Best Solution**: Combine both approaches:
1. Use **Slack Web API** (Option 2) for:
   - OAuth integration and workspace connection
   - Retrieving channel lists for admin UI
   - Posting messages to dynamic channels

2. Use **Laravel Notifications** (Option 1) for:
   - Queueing notification jobs
   - Retry logic and failure handling
   - Consistent notification patterns

3. Create a **SlackService** that wraps the Web API and is used by notification classes

## 2. Slack OAuth Flow & Bot Token Scopes

### Required OAuth Scopes

For the Joy integration, we need these bot token scopes:

```
channels:read       - List public channels
chat:write          - Post messages to channels
chat:write.public   - Post to channels without joining
users:read          - Read user information (for @mentions)
team:read           - Read workspace information
```

### OAuth Flow Implementation

**Approach**: Simplified workspace connection (not per-user OAuth)

1. Admin initiates connection from Joy admin panel
2. Redirect to Slack OAuth authorization URL
3. Slack redirects back with temporary code
4. Exchange code for bot token
5. Store encrypted bot token in `slack_workspaces` table
6. Test connection by fetching workspace info

**Alternative**: Use Slack App configuration with pre-generated bot token (simpler for single workspace)

### Security Considerations

- Bot token MUST be encrypted in database
- Use Laravel's `encrypted` cast for sensitive fields
- Store in dedicated `slack_workspaces` table, not in config
- Implement token refresh mechanism if using OAuth

## 3. Existing Joy Codebase Structure Analysis

### Current Architecture Patterns

**Models** (`/app/Models/`):
- Standard Eloquent models with relationships
- Use of `HasFactory` trait for testing
- Models: `Client`, `User`, `Team`, `ClientStatusUpdate`, `Comment`, `ContentItem`
- Example: `Client` has `team()`, `statusUpdates()`, `contentItems()` relationships

**Services** (`/app/Services/`):
- Service layer pattern for business logic
- Example: `TrelloService` shows integration pattern:
  - Constructor injection of dependencies
  - HTTP client for API calls
  - Error handling and logging
  - Methods for specific operations (testConnection, createCard, syncCommentToTrello)
- Services are NOT bound in service container (instantiated directly)

**Filament Resources** (`/app/Filament/Resources/`):
- Organized by entity: `Clients/`, `Users/`, `Roles/`
- Form schemas in `Schemas/ClientForm.php`
- Table configurations in separate classes
- Example: `ClientForm` uses Filament form components (TextInput, Textarea, Select)

**Jobs/Queues**:
- No existing Jobs directory found
- Queue configuration exists (`config/queue.php`)
- Will need to create `app/Jobs/` directory for async notifications

**Notifications**:
- `User` model has `Notifiable` trait
- No existing notification classes found
- Will need to create `app/Notifications/` directory

**Database Migrations**:
- Follows Laravel conventions
- Recent migration example: `add_approval_workflow_to_client_status_updates.php`
- Uses descriptive names with timestamp prefix

### Integration Points Identified

1. **Client Model** (`/app/Models/Client.php`):
   - Add `slack_channel_id` and `slack_channel_name` fields
   - Add `hasSlackIntegration()` helper method
   - Add `slackNotifications()` relationship

2. **ClientStatusUpdate Model** (`/app/Models/ClientStatusUpdate.php`):
   - Already has approval workflow fields (`approval_status`, `approved_by`, `approved_at`)
   - Trigger notification on create (submission)
   - Trigger notification on status change to 'approved'

3. **Comment Model** (`/app/Models/Comment.php`):
   - Trigger notification on create when `author_type === 'client'`
   - Include related `ContentItem` details in notification

4. **ContentItem Model** (`/app/Models/ContentItem.php`):
   - Has approval status tracking
   - Need to trigger notifications on status changes (approved/rejected)

5. **Filament ClientForm** (`/app/Filament/Resources/Clients/Schemas/ClientForm.php`):
   - Add `Select::make('slack_channel_id')` field
   - Populate options by fetching from SlackService
   - Display current channel name

## 4. Existing Notification Patterns

### TrelloService Pattern Analysis

The existing `TrelloService` provides a blueprint for our `SlackService`:

**Key Patterns**:
- Constructor injection of integration model
- HTTP facade for API calls
- Try-catch blocks with error logging
- Graceful failure handling (return null/false on error)
- Sync methods that can be called from model events or jobs

**Applicable to Slack**:
```php
class SlackService
{
    private SlackWorkspace $workspace;
    private string $baseUrl = 'https://slack.com/api';

    public function __construct(?SlackWorkspace $workspace = null)
    {
        $this->workspace = $workspace ?? SlackWorkspace::getDefault();
    }

    public function testConnection(): array { ... }
    public function getChannels(): array { ... }
    public function postMessage(string $channelId, array $blocks): bool { ... }
}
```

### Recommended Async Pattern

Since notifications should not block user actions:

1. **Model Events** trigger notification dispatch:
```php
// In Comment model
protected static function booted()
{
    static::created(function ($comment) {
        if ($comment->isFromClient()) {
            SendClientCommentNotification::dispatch($comment);
        }
    });
}
```

2. **Job Classes** handle the actual sending:
```php
class SendClientCommentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Comment $comment) {}

    public function handle(SlackService $slack): void
    {
        // Send notification
    }
}
```

3. **Audit Logging** tracks all attempts:
```php
SlackNotification::create([
    'type' => 'client_comment',
    'channel_id' => $channelId,
    'status' => 'success',
    'payload' => json_encode($blocks),
]);
```

## 5. Technical Approach Recommendations

### Database Design

**New Tables**:

1. `slack_workspaces` - Single workspace connection
   - `id`, `team_id`, `team_name`, `bot_token` (encrypted), `access_token` (encrypted)
   - `scopes`, `bot_user_id`, `is_active`, `last_sync_at`, `created_at`, `updated_at`

2. `slack_notifications` - Audit log
   - `id`, `type`, `notifiable_type`, `notifiable_id`, `channel_id`, `channel_name`
   - `status`, `payload`, `response`, `error_message`, `sent_at`, `created_at`

3. `slack_channel_cache` - Optional cache table
   - `id`, `workspace_id`, `channel_id`, `channel_name`, `is_private`, `is_archived`
   - `member_count`, `refreshed_at`, `created_at`, `updated_at`

**Modified Tables**:
- `clients`: Add `slack_channel_id` (string, nullable), `slack_channel_name` (string, nullable)

### Service Architecture

```
SlackService (core integration)
├── testConnection()
├── getChannels()
├── postMessage()
└── formatBlocks()

SlackNotificationService (business logic)
├── sendClientCommentNotification(Comment $comment)
├── sendContentApprovedNotification(ContentItem $item)
├── sendStatusfactionSubmittedNotification(ClientStatusUpdate $status)
└── sendStatusfactionApprovedNotification(ClientStatusUpdate $status)
```

### Notification Classes

```
app/Notifications/
├── ClientCommentedNotification.php
├── ContentApprovedNotification.php
├── ContentRejectedNotification.php
├── StatusfactionSubmittedNotification.php
└── StatusfactionApprovedNotification.php
```

Each notification:
- Implements `ShouldQueue` for async processing
- Has `via()` method returning `['slack']`
- Has `toSlack()` method returning formatted blocks
- Has `failed()` method for error handling

### Job Architecture

```
app/Jobs/
├── SendSlackNotification.php (generic wrapper)
└── SyncSlackChannels.php (refresh cache)
```

### Error Handling Strategy

1. **Graceful Degradation**: Never block user actions if Slack fails
2. **Retry Logic**: Use Laravel queue retry mechanism (3 attempts)
3. **Logging**: Log all failures to `slack_notifications` table with error details
4. **Admin Alerts**: Consider email notification to admin on repeated failures
5. **Rate Limiting**: Implement exponential backoff if hitting Slack rate limits

### Configuration

Add to `config/services.php`:
```php
'slack' => [
    'client_id' => env('SLACK_CLIENT_ID'),
    'client_secret' => env('SLACK_CLIENT_SECRET'),
    'redirect_uri' => env('SLACK_REDIRECT_URI'),
    'bot_token' => env('SLACK_BOT_TOKEN'), // For manual setup
],
```

## 6. Implementation Risks & Mitigations

### Risk 1: Slack API Rate Limits
**Impact**: Notifications could be dropped during high activity
**Mitigation**:
- Use queueing to spread out requests
- Implement rate limit detection and backoff
- Cache channel lists to reduce API calls

### Risk 2: Channel Deletion/Archival
**Impact**: Notifications fail for archived channels
**Mitigation**:
- Validate channel existence before posting
- Gracefully handle API errors (404 for missing channel)
- Log failures and notify admin to update client configuration

### Risk 3: Token Expiration
**Impact**: All notifications fail if token expires
**Mitigation**:
- Implement health check job (daily ping to Slack API)
- Alert admin if connection fails
- Provide re-authorization flow in admin panel

### Risk 4: Test Suite Lock Constraint
**Impact**: Cannot add new test files due to 42-file limit
**Mitigation**:
- Add test methods to existing test files
- Use data providers for multiple scenarios
- Request user approval for new test file if absolutely necessary

### Risk 5: Queue Failure
**Impact**: Notifications lost if queue worker not running
**Mitigation**:
- Document queue worker requirement in quickstart
- Implement queue monitoring
- Consider fallback to sync dispatch in local environment

## 7. Testing Strategy (Within Test Lock Constraints)

### Existing Test Files to Extend

Based on the 42-file test lock, we must add to existing files:

1. **Feature Tests**: Add to existing feature test files
   - `/tests/Feature/StatusfactionReportingE2ETest.php` - Add Slack notification assertions
   - Create methods like `test_slack_notification_sent_when_statusfaction_submitted()`

2. **Unit Tests**: Add to existing or create ONE new test file with approval
   - Could add `SlackServiceTest.php` if approved

### Test Approach Without New Files

**Option 1**: Extend existing test files with new test methods
```php
// In StatusfactionReportingE2ETest.php
public function test_slack_notification_sent_when_am_submits_status()
{
    Notification::fake();
    // Test notification dispatch
}
```

**Option 2**: Use test traits to organize Slack-related tests
```php
// Create tests/Traits/SlackNotificationTests.php (not a test file, so not counted)
trait SlackNotificationTests
{
    public function test_client_comment_sends_slack_notification() { ... }
}

// Use in existing test files
class ContentReviewTest extends TestCase
{
    use SlackNotificationTests;
}
```

### Mocking Strategy

```php
// Mock Slack HTTP calls
Http::fake([
    'slack.com/api/*' => Http::response(['ok' => true], 200)
]);

// Mock notifications
Notification::fake();

// Assert notification sent
Notification::assertSentTo(
    $client,
    ClientCommentedNotification::class
);
```

## 8. Recommendations Summary

### Phase 1: Foundation (P1 - Slack Channel Selection)
1. Create `SlackWorkspace` model and migration
2. Create `SlackService` with OAuth and channel fetching
3. Add Slack fields to `clients` table migration
4. Update `ClientForm` to include Slack channel dropdown
5. Implement workspace connection flow in admin panel

### Phase 2: Content Notifications (P2)
1. Create notification classes for comments and approvals
2. Create `SendSlackNotification` job
3. Add model observers to trigger notifications
4. Implement Slack Block Kit formatting
5. Create `slack_notifications` audit table

### Phase 3: Statusfaction Notifications (P3-P4)
1. Add notifications for Statusfaction submission
2. Add notifications for Statusfaction approval
3. Ensure proper filtering (exclude satisfaction scores)

### Phase 4: Monitoring & Refinement
1. Create admin dashboard for Slack notification status
2. Implement health check job
3. Add retry and error recovery mechanisms
4. Performance optimization and caching

## 9. Open Questions for User

1. **OAuth vs Manual Token**: Should admins go through OAuth flow, or manually paste a bot token?
2. **Single vs Multi-Workspace**: Do we need to support multiple Slack workspaces, or just one?
3. **Channel Privacy**: Should we support private channels, or only public ones?
4. **Notification Preferences**: Should admins be able to toggle notification types per client?
5. **Test File Approval**: Can we create ONE new test file (`SlackIntegrationTest.php`) for Slack tests?

## 10. Next Steps

1. Review this research with stakeholders
2. Get approval for technical approach
3. Proceed to data model design
4. Define service contracts
5. Create implementation plan in `plan.md`
