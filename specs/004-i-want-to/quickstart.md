# Quickstart Guide: Slack Integration

**Feature**: Slack Integration for Client Notifications
**Date**: 2025-10-10
**Audience**: Developers implementing this feature

## Overview

This guide provides step-by-step instructions for setting up, developing, testing, and deploying the Slack integration feature for Joy.

---

## Prerequisites

### Development Environment

- PHP 8.2+
- Laravel 12
- Composer
- Node.js & NPM
- MySQL or PostgreSQL
- Redis (for queue workers)
- Slack workspace with admin access

### Required Knowledge

- Laravel Eloquent models and migrations
- Laravel queues and jobs
- Filament admin panel basics
- Slack Web API fundamentals
- Slack Block Kit message formatting

---

## Part 1: Slack App Setup

### Step 1: Create Slack App

1. Go to [https://api.slack.com/apps](https://api.slack.com/apps)
2. Click **"Create New App"**
3. Select **"From scratch"**
4. Enter app name: **"Joy Content Calendar"**
5. Select your workspace
6. Click **"Create App"**

### Step 2: Configure OAuth Scopes

1. Navigate to **"OAuth & Permissions"** in sidebar
2. Scroll to **"Scopes"** section
3. Under **"Bot Token Scopes"**, add:
   - `channels:read` - View basic info about public channels
   - `chat:write` - Post messages
   - `chat:write.public` - Post to channels without joining
   - `users:read` - View users (for mentions)
   - `team:read` - View workspace info

### Step 3: Install App to Workspace

1. Scroll to **"OAuth Tokens for Your Workspace"**
2. Click **"Install to Workspace"**
3. Review permissions and click **"Allow"**
4. Copy the **Bot User OAuth Token** (starts with `xoxb-`)
5. Save this token securely - you'll add it to `.env`

### Step 4: (Optional) Enable Socket Mode for Testing

1. Navigate to **"Socket Mode"** in sidebar
2. Enable Socket Mode
3. This allows local development without exposing a public URL

---

## Part 2: Development Setup

### Step 1: Environment Configuration

Add to `.env`:

```env
# Slack Integration
SLACK_CLIENT_ID=your_client_id_here
SLACK_CLIENT_SECRET=your_client_secret_here
SLACK_BOT_TOKEN=xoxb-your-bot-token-here
SLACK_REDIRECT_URI=http://localhost:8000/admin/slack/callback

# Queue Configuration (if not already set)
QUEUE_CONNECTION=redis
```

### Step 2: Install Dependencies

No additional Composer packages required - we use Laravel's built-in HTTP client.

```bash
cd /Users/jprevel/Documents/joy/joy-app
composer install
```

### Step 3: Database Setup

Run migrations in order:

```bash
php artisan migrate --path=database/migrations/2025_10_10_000001_create_slack_workspaces_table.php
php artisan migrate --path=database/migrations/2025_10_10_000002_create_slack_channel_cache_table.php
php artisan migrate --path=database/migrations/2025_10_10_000003_create_slack_notifications_table.php
php artisan migrate --path=database/migrations/2025_10_10_000004_add_slack_fields_to_clients_table.php
```

Or run all pending migrations:

```bash
php artisan migrate
```

### Step 4: Create Models

Models to create:
- `app/Models/SlackWorkspace.php`
- `app/Models/SlackNotification.php`
- `app/Models/SlackChannelCache.php`

See `data-model.md` for complete model code.

### Step 5: Create Service Classes

Create services in `app/Services/`:

1. **SlackService.php**
   - Implements `SlackServiceContract`
   - Handles Slack Web API calls

2. **SlackNotificationService.php**
   - Implements `SlackNotificationServiceContract`
   - Orchestrates notification sending

3. **SlackBlockFormatter.php**
   - Implements `SlackBlockFormatterContract`
   - Formats messages for Slack

Copy contracts from `specs/004-i-want-to/contracts/` to `app/Contracts/`.

### Step 6: Register Service Bindings

In `app/Providers/AppServiceProvider.php`:

```php
use App\Contracts\SlackServiceContract;
use App\Contracts\SlackNotificationServiceContract;
use App\Contracts\SlackBlockFormatterContract;
use App\Services\SlackService;
use App\Services\SlackNotificationService;
use App\Services\SlackBlockFormatter;

public function register(): void
{
    $this->app->bind(SlackServiceContract::class, SlackService::class);
    $this->app->bind(SlackNotificationServiceContract::class, SlackNotificationService::class);
    $this->app->bind(SlackBlockFormatterContract::class, SlackBlockFormatter::class);
}
```

### Step 7: Create Jobs

Create job classes in `app/Jobs/`:

```bash
php artisan make:job SendClientCommentNotification
php artisan make:job SendContentApprovedNotification
php artisan make:job SendContentRejectedNotification
php artisan make:job SendStatusfactionSubmittedNotification
php artisan make:job SendStatusfactionApprovedNotification
php artisan make:job SyncSlackChannels
```

Each job should:
- Implement `ShouldQueue`
- Accept relevant model in constructor
- Call `SlackNotificationService` in `handle()` method

### Step 8: Create Model Observers

Create observers to trigger notifications:

```bash
php artisan make:observer CommentObserver --model=Comment
php artisan make:observer ContentItemObserver --model=ContentItem
php artisan make:observer ClientStatusUpdateObserver --model=ClientStatusUpdate
```

Register observers in `AppServiceProvider::boot()`:

```php
use App\Models\Comment;
use App\Models\ContentItem;
use App\Models\ClientStatusUpdate;
use App\Observers\CommentObserver;
use App\Observers\ContentItemObserver;
use App\Observers\ClientStatusUpdateObserver;

public function boot(): void
{
    Comment::observe(CommentObserver::class);
    ContentItem::observe(ContentItemObserver::class);
    ClientStatusUpdate::observe(ClientStatusUpdateObserver::class);
}
```

### Step 9: Update Filament ClientForm

Edit `app/Filament/Resources/Clients/Schemas/ClientForm.php`:

```php
use App\Contracts\SlackServiceContract;
use Filament\Forms\Components\Select;

// Add to components array:
Select::make('slack_channel_id')
    ->label('Slack Channel')
    ->placeholder('Select a Slack channel')
    ->options(function () {
        $slackService = app(SlackServiceContract::class);
        $result = $slackService->getChannels(includeArchived: false);

        if (!$result['success']) {
            return [];
        }

        return collect($result['channels'] ?? [])
            ->pluck('name', 'id')
            ->toArray();
    })
    ->searchable()
    ->helperText('Select the Slack channel where notifications will be sent')
    ->columnSpanFull()
    ->reactive()
    ->afterStateUpdated(function ($state, callable $set) {
        // Auto-populate channel name when channel is selected
        if ($state) {
            $slackService = app(SlackServiceContract::class);
            $result = $slackService->getChannelInfo($state);
            if ($result['success']) {
                $set('slack_channel_name', $result['channel']['name'] ?? null);
            }
        }
    }),

// Add hidden field for channel name
TextInput::make('slack_channel_name')
    ->hidden()
    ->dehydrated(),
```

---

## Part 3: Testing

### Local Testing Strategy

Due to the **42-test-file lock**, we CANNOT create new test files. Instead:

1. **Extend existing test files** with new methods
2. **Use test traits** for shared Slack test logic
3. **Request approval** if a dedicated test file is absolutely necessary

### Option 1: Extend Existing Tests

Add methods to `/Users/jprevel/Documents/joy/joy-app/tests/Feature/StatusfactionReportingE2ETest.php`:

```php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendStatusfactionSubmittedNotification;

public function test_slack_notification_sent_when_statusfaction_submitted(): void
{
    // Mock Slack API
    Http::fake([
        'slack.com/api/*' => Http::response(['ok' => true, 'ts' => '123456'], 200)
    ]);

    // Setup client with Slack channel
    $client = Client::factory()->create([
        'slack_channel_id' => 'C0123456789',
        'slack_channel_name' => '#test-channel',
    ]);

    // Create Slack workspace
    SlackWorkspace::factory()->create();

    // Create status update
    Queue::fake();
    $status = ClientStatusUpdate::factory()->create([
        'client_id' => $client->id,
        'approval_status' => 'pending_approval',
    ]);

    // Assert job was dispatched
    Queue::assertPushed(SendStatusfactionSubmittedNotification::class);
}

public function test_slack_notification_not_sent_when_client_has_no_channel(): void
{
    Queue::fake();

    $client = Client::factory()->create([
        'slack_channel_id' => null, // No Slack channel
    ]);

    $status = ClientStatusUpdate::factory()->create([
        'client_id' => $client->id,
    ]);

    // Should not dispatch notification
    Queue::assertNotPushed(SendStatusfactionSubmittedNotification::class);
}
```

### Option 2: Create Test Trait (NOT a test file)

Create `/Users/jprevel/Documents/joy/joy-app/tests/Traits/SlackNotificationAssertions.php`:

```php
<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Http;

trait SlackNotificationAssertions
{
    protected function mockSlackApi(): void
    {
        Http::fake([
            'slack.com/api/auth.test' => Http::response([
                'ok' => true,
                'user' => 'test_user',
            ], 200),
            'slack.com/api/conversations.list' => Http::response([
                'ok' => true,
                'channels' => [
                    ['id' => 'C123', 'name' => 'test-channel'],
                ],
            ], 200),
            'slack.com/api/chat.postMessage' => Http::response([
                'ok' => true,
                'ts' => '123456.789',
            ], 200),
        ]);
    }

    protected function assertSlackMessageSent(string $channelId): void
    {
        Http::assertSent(function ($request) use ($channelId) {
            return $request->url() === 'https://slack.com/api/chat.postMessage'
                && $request['channel'] === $channelId;
        });
    }
}
```

Then use in existing test files:

```php
class StatusfactionReportingE2ETest extends TestCase
{
    use SlackNotificationAssertions;

    public function test_example()
    {
        $this->mockSlackApi();
        // ... test code ...
        $this->assertSlackMessageSent('C123');
    }
}
```

### Manual Testing Checklist

1. **Workspace Connection**
   - [ ] Navigate to Joy admin panel
   - [ ] Access Slack settings (to be created in admin)
   - [ ] Enter bot token and test connection
   - [ ] Verify workspace name displays correctly

2. **Channel Selection**
   - [ ] Create or edit a client
   - [ ] Verify Slack channel dropdown populates
   - [ ] Select a channel and save
   - [ ] Verify `slack_channel_id` and `slack_channel_name` are saved

3. **Client Comment Notification**
   - [ ] Log in as client via magic link
   - [ ] Add comment to a content item
   - [ ] Check Slack channel - message should appear within 5 seconds
   - [ ] Verify message contains: client name, comment text, content title, link to Joy

4. **Content Approval Notification**
   - [ ] Log in as client
   - [ ] Approve a content item
   - [ ] Check Slack channel for approval message
   - [ ] Verify message contains: client name, content title, platform, approval timestamp

5. **Statusfaction Submission Notification**
   - [ ] Log in as account manager
   - [ ] Submit Statusfaction report with status notes
   - [ ] Check Slack channel for submission message
   - [ ] Verify message contains: client name, team name, AM name, status notes
   - [ ] Verify satisfaction scores are NOT included

6. **Statusfaction Approval Notification**
   - [ ] Log in as admin
   - [ ] Approve pending Statusfaction report
   - [ ] Check Slack channel for approval message
   - [ ] Verify message contains: admin name, approval timestamp

7. **Error Handling**
   - [ ] Set invalid Slack channel ID on client
   - [ ] Trigger notification
   - [ ] Verify notification fails gracefully (no user-facing error)
   - [ ] Check `slack_notifications` table for error log

### Queue Worker Testing

Start queue worker in separate terminal:

```bash
cd /Users/jprevel/Documents/joy/joy-app
php artisan queue:work --tries=3 --timeout=60
```

Monitor queue:

```bash
php artisan queue:listen
```

Check failed jobs:

```bash
php artisan queue:failed
```

Retry failed jobs:

```bash
php artisan queue:retry all
```

---

## Part 4: Running the Test Suite (Constitutional Compliance)

**CRITICAL**: Before making ANY code changes, verify the test suite passes.

### Pre-Development Check

```bash
cd /Users/jprevel/Documents/joy/joy-app
./scripts/test-lock.sh
```

This script:
1. Counts test files (must be exactly 42)
2. Runs full test suite
3. Fails if any tests fail (excluding marked incomplete)

### During Development

Run tests frequently:

```bash
composer test
```

Or run specific test file:

```bash
php artisan test --filter=StatusfactionReportingE2ETest
```

### Before Committing

**Always** run test lock script:

```bash
./scripts/test-lock.sh
```

All tests must pass before committing code.

---

## Part 5: Deployment

### Pre-Deployment Checklist

- [ ] All tests pass (`./scripts/test-lock.sh`)
- [ ] Environment variables documented in `.env.example`
- [ ] Database migrations tested in staging
- [ ] Queue worker configured for production
- [ ] Slack app installed to production workspace
- [ ] Production Slack bot token obtained and encrypted

### Production Environment Setup

1. **Add to production `.env`**:
   ```env
   SLACK_BOT_TOKEN=xoxb-production-token-here
   QUEUE_CONNECTION=redis
   ```

2. **Run migrations**:
   ```bash
   php artisan migrate --force
   ```

3. **Configure queue worker** (Supervisor):
   ```ini
   [program:joy-queue-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/joy-app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/path/to/joy-app/storage/logs/worker.log
   stopwaitsecs=3600
   ```

4. **Start queue worker**:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start joy-queue-worker:*
   ```

5. **Test connection**:
   ```bash
   php artisan tinker
   >>> app(\App\Contracts\SlackServiceContract::class)->testConnection()
   ```

### Monitoring

1. **Queue Monitoring**:
   ```bash
   php artisan queue:monitor redis:default,redis:notifications --max=100
   ```

2. **Failed Jobs**:
   ```bash
   php artisan queue:failed-table
   php artisan migrate
   ```

3. **Slack Notification Logs**:
   ```sql
   SELECT * FROM slack_notifications
   WHERE status = 'failed'
   ORDER BY created_at DESC
   LIMIT 20;
   ```

---

## Part 6: Troubleshooting

### Issue: Channels not loading in dropdown

**Symptoms**: Slack channel dropdown is empty in client form

**Diagnosis**:
1. Check if `SlackWorkspace` record exists:
   ```bash
   php artisan tinker
   >>> \App\Models\SlackWorkspace::first()
   ```

2. Test Slack connection:
   ```bash
   >>> app(\App\Contracts\SlackServiceContract::class)->testConnection()
   ```

3. Check `storage/logs/laravel.log` for API errors

**Solutions**:
- Verify `SLACK_BOT_TOKEN` in `.env`
- Verify bot has `channels:read` scope
- Check if workspace connection is marked as `is_active`

---

### Issue: Notifications not sending

**Symptoms**: No messages appear in Slack channel

**Diagnosis**:
1. Check if queue worker is running:
   ```bash
   ps aux | grep "queue:work"
   ```

2. Check failed jobs:
   ```bash
   php artisan queue:failed
   ```

3. Check `slack_notifications` table:
   ```sql
   SELECT status, error_message FROM slack_notifications ORDER BY created_at DESC LIMIT 10;
   ```

4. Check if client has `slack_channel_id`:
   ```bash
   php artisan tinker
   >>> \App\Models\Client::find(1)->slack_channel_id
   ```

**Solutions**:
- Start queue worker: `php artisan queue:work`
- Verify client has Slack channel configured
- Check bot has `chat:write` permission
- Verify channel ID is correct (starts with 'C')

---

### Issue: "channel_not_found" error

**Symptoms**: Notifications fail with channel_not_found error

**Diagnosis**:
1. Verify channel exists in Slack workspace
2. Check if channel is archived
3. Verify bot has access to channel

**Solutions**:
- Invite bot to channel: `/invite @Joy Content Calendar`
- Update client with correct channel ID
- Refresh channel cache: `php artisan slack:sync-channels` (if command created)

---

### Issue: Messages appear but formatting is broken

**Symptoms**: Slack messages lack formatting or look incorrect

**Diagnosis**:
1. Check `payload` in `slack_notifications` table
2. Validate Block Kit JSON: [https://app.slack.com/block-kit-builder](https://app.slack.com/block-kit-builder)

**Solutions**:
- Review `SlackBlockFormatter` implementation
- Test blocks in Block Kit Builder
- Check for special character escaping issues

---

## Part 7: Development Workflow

### Typical Development Session

```bash
# 1. Start development server
cd /Users/jprevel/Documents/joy/joy-app
php artisan serve

# 2. Start queue worker (separate terminal)
php artisan queue:work

# 3. Watch logs (separate terminal)
tail -f storage/logs/laravel.log

# 4. Make code changes...

# 5. Run tests after changes
composer test

# 6. Before committing
./scripts/test-lock.sh
```

### Git Workflow

```bash
# Create feature branch (if not already on 004-i-want-to)
git checkout -b 004-i-want-to

# Make changes...

# Run tests
./scripts/test-lock.sh

# Commit changes
git add .
git commit -m "Add Slack integration for client notifications"

# Push to remote
git push origin 004-i-want-to
```

---

## Part 8: Performance Optimization

### Caching Strategy

1. **Channel Cache**: Refresh every 24 hours
   ```php
   // In SlackService
   $cacheKey = "slack_channels_{$this->workspace->id}";
   $channels = Cache::remember($cacheKey, 86400, function() {
       return $this->fetchChannelsFromApi();
   });
   ```

2. **Workspace Info**: Cache workspace details
   ```php
   Cache::remember('slack_workspace_info', 3600, function() {
       return $this->getWorkspaceInfo();
   });
   ```

### Queue Optimization

1. Use dedicated queue for Slack notifications:
   ```php
   // In Job class
   public $queue = 'notifications';
   ```

2. Run separate worker for notifications:
   ```bash
   php artisan queue:work redis --queue=notifications,default
   ```

### Rate Limit Handling

Slack has rate limits (Tier 3: ~50 requests/minute). Implement throttling:

```php
// In SlackService
use Illuminate\Support\Facades\RateLimiter;

public function postMessage(string $channelId, array $blocks, ?string $text = null): array
{
    $key = "slack_api_post_message";

    if (RateLimiter::tooManyAttempts($key, 50)) {
        $seconds = RateLimiter::availableIn($key);
        return [
            'success' => false,
            'error' => "Rate limit exceeded. Retry in {$seconds} seconds.",
        ];
    }

    RateLimiter::hit($key, 60); // 1 minute window

    // Make API call...
}
```

---

## Part 9: Security Considerations

### Token Storage

- **Bot token MUST be encrypted** in database using Laravel's `encrypted` cast
- **Never commit tokens** to version control
- **Rotate tokens** periodically
- **Use environment variables** for configuration

### Input Validation

- **Escape user input** before sending to Slack (prevent injection)
- **Validate channel IDs** before sending messages
- **Sanitize markdown** in comment text

### Access Control

- **Only admins** can configure Slack workspace connection
- **Only admins** can select Slack channels for clients
- **Audit all configuration changes** via Filament's built-in logging

---

## Part 10: Resources

### Documentation

- [Slack Web API Documentation](https://api.slack.com/web)
- [Slack Block Kit Builder](https://app.slack.com/block-kit-builder)
- [Laravel Queue Documentation](https://laravel.com/docs/12.x/queues)
- [Laravel HTTP Client](https://laravel.com/docs/12.x/http-client)

### Internal Documentation

- Feature Spec: `/Users/jprevel/Documents/joy/specs/004-i-want-to/spec.md`
- Data Model: `/Users/jprevel/Documents/joy/specs/004-i-want-to/data-model.md`
- Service Contracts: `/Users/jprevel/Documents/joy/specs/004-i-want-to/contracts/`
- Implementation Plan: `/Users/jprevel/Documents/joy/specs/004-i-want-to/plan.md`

### Support

- Slack API Community: [https://api.slack.com/community](https://api.slack.com/community)
- Laravel Slack Channel: `#laravel` on Slack
- Internal: Check project README and CLAUDE.md for team conventions

---

## Quick Reference Commands

```bash
# Testing
./scripts/test-lock.sh                    # Run full test suite with lock check
composer test                             # Run tests without lock check
php artisan test --filter=FeatureTest     # Run specific test

# Queue
php artisan queue:work                    # Start queue worker
php artisan queue:listen                  # Watch queue in real-time
php artisan queue:failed                  # List failed jobs
php artisan queue:retry all               # Retry all failed jobs

# Database
php artisan migrate                       # Run pending migrations
php artisan migrate:rollback              # Rollback last migration batch
php artisan db:seed                       # Run seeders

# Tinker (testing)
php artisan tinker                        # Interactive shell
>>> app(\App\Contracts\SlackServiceContract::class)->testConnection()

# Logs
tail -f storage/logs/laravel.log          # Watch Laravel logs
tail -f storage/logs/worker.log           # Watch queue worker logs

# Cache
php artisan cache:clear                   # Clear application cache
php artisan config:clear                  # Clear config cache
php artisan route:clear                   # Clear route cache
```

---

## Next Steps

1. Review this quickstart with the team
2. Set up Slack app and obtain bot token
3. Begin implementation following this guide
4. Test each component as you build
5. Run `./scripts/test-lock.sh` before each commit
6. Document any deviations or additional findings

---

**Questions or issues?** Refer to `plan.md` for detailed implementation steps, or review service contracts in `/specs/004-i-want-to/contracts/`.
