# Slack Integration Service Contracts

This directory contains interface definitions for the Slack integration feature services.

## Contracts Overview

### 1. SlackServiceContract

**Purpose**: Low-level Slack API interaction
**Location**: `app/Contracts/SlackServiceContract.php`
**Implementation**: `app/Services/SlackService.php`

**Responsibilities**:
- Authenticate with Slack API
- Fetch channel lists
- Post messages to channels
- Validate channel existence
- Manage workspace connections

**Key Methods**:
- `testConnection()`: Verify Slack credentials are valid
- `getChannels()`: Retrieve list of workspace channels
- `postMessage()`: Send message to a specific channel
- `channelExists()`: Validate channel availability

---

### 2. SlackNotificationServiceContract

**Purpose**: Business logic for sending notifications
**Location**: `app/Contracts/SlackNotificationServiceContract.php`
**Implementation**: `app/Services/SlackNotificationService.php`

**Responsibilities**:
- Orchestrate notification sending for different event types
- Validate client has Slack integration enabled
- Create audit log entries (SlackNotification model)
- Handle notification failures gracefully

**Key Methods**:
- `sendClientCommentNotification(Comment)`: Notify when client comments
- `sendContentApprovedNotification(ContentItem)`: Notify on content approval
- `sendContentRejectedNotification(ContentItem)`: Notify on content rejection
- `sendStatusfactionSubmittedNotification(ClientStatusUpdate)`: Notify on status submission
- `sendStatusfactionApprovedNotification(ClientStatusUpdate)`: Notify on status approval

---

### 3. SlackBlockFormatterContract

**Purpose**: Format data into Slack Block Kit messages
**Location**: `app/Contracts/SlackBlockFormatterContract.php`
**Implementation**: `app/Services/SlackBlockFormatter.php`

**Responsibilities**:
- Transform Joy models into Slack Block Kit JSON
- Format timestamps for display
- Escape special characters for Slack markdown
- Create consistent, visually appealing messages

**Key Methods**:
- `formatClientComment(Comment)`: Create blocks for comment notification
- `formatContentApproved(ContentItem)`: Create blocks for approval notification
- `formatStatusfactionSubmitted(ClientStatusUpdate)`: Create blocks for status submission
- `escapeText(string)`: Sanitize user input for Slack
- `createLinkButton(url, text)`: Generate action button blocks

---

## Service Architecture

```
┌─────────────────────────────────────────────────────────┐
│                   Laravel Application                    │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌────────────────────────────────────────────────┐    │
│  │          Model Events / Observers              │    │
│  │  (Comment::created, ContentItem::updated)      │    │
│  └────────────┬───────────────────────────────────┘    │
│               │                                          │
│               ▼                                          │
│  ┌────────────────────────────────────────────────┐    │
│  │             Jobs (Queued)                      │    │
│  │  • SendClientCommentNotification               │    │
│  │  • SendContentApprovedNotification             │    │
│  │  • SendStatusfactionSubmittedNotification      │    │
│  └────────────┬───────────────────────────────────┘    │
│               │                                          │
│               ▼                                          │
│  ┌────────────────────────────────────────────────┐    │
│  │    SlackNotificationService                    │    │
│  │    (Business Logic Layer)                      │    │
│  │  • Validates client has Slack enabled          │    │
│  │  • Creates SlackNotification audit record      │    │
│  │  • Calls SlackService and SlackBlockFormatter  │    │
│  └──────┬──────────────────────────────┬──────────┘    │
│         │                               │               │
│         ▼                               ▼               │
│  ┌──────────────────┐        ┌──────────────────────┐  │
│  │  SlackService    │        │ SlackBlockFormatter  │  │
│  │  (API Layer)     │        │ (Presentation Layer) │  │
│  │  • HTTP calls    │        │ • Block Kit JSON     │  │
│  │  • Auth          │        │ • Text formatting    │  │
│  │  • Rate limits   │        │ • Escape chars       │  │
│  └────────┬─────────┘        └──────────────────────┘  │
│           │                                             │
│           ▼                                             │
│  ┌────────────────────────────────────────────────┐    │
│  │           Slack Web API                        │    │
│  │      (https://slack.com/api/*)                 │    │
│  └────────────────────────────────────────────────┘    │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

---

## Implementation Guidelines

### Dependency Injection

Services should be injected via constructor or method injection:

```php
// In a Job class
public function handle(SlackNotificationService $slackNotificationService): void
{
    $slackNotificationService->sendClientCommentNotification($this->comment);
}

// In a controller or Livewire component
public function __construct(private SlackService $slackService) {}
```

### Error Handling

All service methods return structured arrays with success status:

```php
[
    'success' => true,
    'notification_id' => 42,
    'data' => [...],
]

// OR on failure:
[
    'success' => false,
    'error' => 'Channel not found',
]
```

This allows callers to handle errors gracefully without exceptions.

### Testing

Contracts make testing easier via mocking:

```php
// In tests
$mockSlackService = Mockery::mock(SlackServiceContract::class);
$mockSlackService->shouldReceive('postMessage')
    ->once()
    ->andReturn(['success' => true, 'ts' => '123456']);

$this->app->instance(SlackServiceContract::class, $mockSlackService);
```

---

## Return Value Standards

### Success Response

```php
[
    'success' => true,
    'data' => [...],          // Optional: response data
    'notification_id' => 42,  // Optional: created notification ID
    'ts' => '123456.789',     // Optional: Slack message timestamp
]
```

### Error Response

```php
[
    'success' => false,
    'error' => 'Human-readable error message',
    'error_code' => 'CHANNEL_NOT_FOUND',  // Optional: machine-readable code
]
```

---

## Contract Compliance

Implementations MUST:

1. Accept parameters as defined in interface method signatures
2. Return data structures matching the documented return types
3. Handle all exceptions internally and return error arrays
4. Log errors appropriately before returning
5. Never throw exceptions from public methods (return error arrays instead)

Implementations SHOULD:

1. Use type hints for all parameters and return values
2. Document complex behaviors with inline comments
3. Write comprehensive unit tests for all public methods
4. Follow Laravel coding standards (PSR-12)

---

## Usage Examples

### Sending a Client Comment Notification

```php
// In app/Jobs/SendClientCommentNotification.php
public function handle(
    SlackNotificationServiceContract $notificationService
): void {
    $result = $notificationService->sendClientCommentNotification($this->comment);

    if (!$result['success']) {
        Log::warning('Slack notification failed', [
            'comment_id' => $this->comment->id,
            'error' => $result['error'],
        ]);
    }
}
```

### Fetching Channels for Admin UI

```php
// In app/Filament/Resources/Clients/Schemas/ClientForm.php
use App\Contracts\SlackServiceContract;

Select::make('slack_channel_id')
    ->label('Slack Channel')
    ->options(function (SlackServiceContract $slackService) {
        $result = $slackService->getChannels(includeArchived: false);

        if (!$result['success']) {
            return [];
        }

        return collect($result['channels'])
            ->pluck('name', 'id')
            ->toArray();
    })
    ->searchable();
```

### Formatting a Message

```php
// In app/Services/SlackNotificationService.php
public function sendClientCommentNotification(Comment $comment): array
{
    $blocks = $this->formatter->formatClientComment($comment);

    return $this->slackService->postMessage(
        $comment->contentItem->client->slack_channel_id,
        $blocks
    );
}
```

---

## Next Steps

1. Implement concrete classes for each contract
2. Register service bindings in `AppServiceProvider`
3. Write comprehensive tests for each implementation
4. Document service usage in `quickstart.md`
