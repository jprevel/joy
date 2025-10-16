# Slack Integration Setup Guide

This guide provides step-by-step instructions for setting up Slack integration with the Joy application.

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Part 1: Create and Configure Slack App](#part-1-create-and-configure-slack-app)
- [Part 2: Configure Joy Application](#part-2-configure-joy-application)
- [Part 3: Database Setup](#part-3-database-setup)
- [Part 4: Testing the Integration](#part-4-testing-the-integration)
- [Troubleshooting](#troubleshooting)
- [Security Best Practices](#security-best-practices)

---

## Overview

The Slack integration enables Joy to send automatic notifications to client-specific Slack channels when:

- **Client Comments**: A client adds a comment to a content item
- **Content Approval**: A client approves a content item
- **Statusfaction Submission**: An account manager submits a weekly status report
- **Statusfaction Approval**: An admin approves a status report

### Architecture

- **SlackService**: Handles all Slack API communication
- **SlackNotificationService**: Orchestrates notification sending
- **SlackBlockFormatter**: Formats messages using Slack's Block Kit
- **Observers**: Automatically trigger notifications on model events
- **SlackNotification Model**: Audit trail of all sent notifications

---

## Prerequisites

Before starting, ensure you have:

- [ ] A Slack workspace where you have admin permissions
- [ ] Access to create Slack apps at [api.slack.com](https://api.slack.com/apps)
- [ ] Joy application installed and running locally
- [ ] Database migrations run successfully
- [ ] `.env` file created from `.env.example`

---

## Part 1: Create and Configure Slack App

### Step 1: Create a New Slack App

1. Go to [https://api.slack.com/apps](https://api.slack.com/apps)
2. Click **"Create New App"**
3. Select **"From scratch"**
4. Enter app details:
   - **App Name**: `Joy Notifications` (or your preferred name)
   - **Workspace**: Select your workspace
5. Click **"Create App"**

### Step 2: Configure OAuth & Permissions

1. In the left sidebar, click **"OAuth & Permissions"**
2. Scroll down to **"Scopes"** section
3. Under **"Bot Token Scopes"**, add the following scopes:

   | Scope | Purpose |
   |-------|---------|
   | `channels:read` | View basic info about public channels |
   | `groups:read` | View basic info about private channels |
   | `chat:write` | Send messages to channels |

4. Click **"Add an OAuth Scope"** for each scope above

### Step 3: Configure Redirect URLs (Optional - for OAuth flow)

> **⚠️ IMPORTANT**: Currently, Joy uses bot tokens directly. **OAuth flow is not implemented yet**, so you can **skip this step** for now.
>
> Slack requires HTTPS for redirect URLs, even in development. If you need to test OAuth in the future, you'll need to use a tunneling service like ngrok (see "HTTPS Development Setup" section below).

**If you want to set up redirect URLs anyway** (for future use):

1. Still in **"OAuth & Permissions"**
2. Scroll to **"Redirect URLs"**
3. Add your redirect URL:
   - **Development with ngrok**: `https://your-subdomain.ngrok.io/admin/slack/callback`
   - **Production**: `https://your-domain.com/admin/slack/callback`
4. Click **"Save URLs"**

**For now, you can skip this and proceed to Step 4.**

### Step 4: Install App to Workspace

> **💡 This is the key step** - you'll get your bot token here without needing OAuth callbacks.

1. Scroll to the top of **"OAuth & Permissions"** page
2. Click **"Install to Workspace"** button
3. Review the permissions requested
4. Click **"Allow"** to authorize the app
5. You'll see a **Bot User OAuth Token** (starts with `xoxb-`)
6. **Copy this token** - you'll need it for configuration

**This token is all you need** - no OAuth callback required! Paste it directly into your `.env` file.

### Step 5: Get Client ID and Secret

1. In the left sidebar, click **"Basic Information"**
2. Scroll to **"App Credentials"** section
3. Copy the following values:
   - **Client ID**
   - **Client Secret** (click "Show" to reveal)

---

## Part 2: Configure Joy Application

### Step 1: Update Environment Variables

1. Open your `.env` file in the Joy application root directory
2. Add or update the following Slack configuration:

```env
# Slack Integration
SLACK_CLIENT_ID=your_client_id_here
SLACK_CLIENT_SECRET=your_client_secret_here
SLACK_BOT_TOKEN=xoxb-your-bot-token-here
SLACK_REDIRECT_URI=http://localhost:8000/admin/slack/callback
```

**Important**: Replace the placeholder values with your actual credentials from the Slack app.

### Step 2: Verify Configuration

Run the following command to verify the configuration is loaded correctly:

```bash
php artisan tinker
```

Then test each configuration value:

```php
config('services.slack.client_id')
config('services.slack.client_secret')
config('services.slack.bot_token')
config('services.slack.redirect_uri')
```

All values should return your actual credentials (not `null`).

Type `exit` to leave tinker.

### Step 3: Clear Configuration Cache (if needed)

If you've previously cached your configuration, clear it:

```bash
php artisan config:clear
php artisan cache:clear
```

---

## Part 3: Database Setup

### Step 1: Run Migrations

Ensure all Slack-related tables are created:

```bash
php artisan migrate
```

This creates the following tables:
- `slack_workspaces` - Stores workspace connection info
- `slack_notifications` - Audit trail of sent notifications

### Step 2: Seed Slack Workspace

Create a default workspace record in the database:

```bash
php artisan tinker
```

```php
\App\Models\SlackWorkspace::create([
    'team_id' => 'T01234567',  // Your Slack workspace/team ID
    'team_name' => 'Your Workspace Name',
    'bot_token' => config('services.slack.bot_token'),
    'is_active' => true,
]);
```

**To find your Team ID**:
1. Open Slack in a web browser
2. The URL will be like: `https://app.slack.com/client/T01234567/...`
3. The `T01234567` part is your Team ID

Type `exit` to leave tinker.

### Step 3: Configure Client Slack Channels

For each client that should receive notifications:

1. Log in to Joy as an admin
2. Go to **Clients** in the Filament admin panel
3. Edit a client
4. Select a **Slack Channel** from the dropdown
   - The dropdown will show all channels the bot has access to
   - Channels are fetched live from Slack API
5. Save the client

**Note**: To make channels available in the dropdown, you must invite the bot to those channels first (see Part 4).

---

## Part 4: Testing the Integration

### Step 1: Invite Bot to a Test Channel

1. Open Slack workspace
2. Create a test channel (e.g., `#joy-test`)
3. In the channel, type: `/invite @Joy Notifications` (or your app name)
4. The bot should join the channel

### Step 2: Verify Bot Access

Test that the bot can see channels:

```bash
php artisan tinker
```

```php
$service = app(\App\Contracts\SlackServiceContract::class);
$result = $service->getChannels(includeArchived: false, includePrivate: true);

// Should return array with 'success' => true and 'channels' list
dd($result);
```

### Step 3: Test Notification Sending

Send a test notification to verify everything works:

```bash
php artisan tinker
```

```php
$service = app(\App\Contracts\SlackServiceContract::class);

// Replace with an actual channel ID from previous step
$channelId = 'C01234567';

$blocks = [
    [
        'type' => 'section',
        'text' => [
            'type' => 'mrkdwn',
            'text' => '🎉 *Test notification from Joy!*'
        ]
    ]
];

$result = $service->postMessage($channelId, $blocks, 'Test notification');

dd($result);
// Should return: ['success' => true, 'ts' => '...', 'channel' => '...']
```

### Step 4: Test Full Integration Flow

#### Test Client Comment Notification

1. Log in as a client user (or create a magic link)
2. Navigate to a content item
3. Add a comment
4. Check the configured Slack channel - you should see a notification

#### Test Content Approval Notification

1. Log in as a client user
2. Navigate to a content item in "Review" status
3. Approve the content
4. Check Slack for the approval notification

#### Test Statusfaction Notifications

1. Log in as an account manager (agency role)
2. Go to Statusfaction page
3. Submit a weekly status report
4. Check Slack for submission notification
5. Log in as admin
6. Approve the status report
7. Check Slack for approval notification

---

## Troubleshooting

### Problem: "Failed to fetch Slack channels for dropdown"

**Possible Causes**:
- Bot token is invalid or expired
- Bot token not set in `.env` file
- Configuration cache needs clearing

**Solutions**:
1. Verify bot token in `.env` matches Slack app
2. Clear config cache: `php artisan config:clear`
3. Check Laravel logs: `tail -f storage/logs/laravel.log`
4. Test API connection in tinker (see Step 2 of Part 4)

### Problem: "Channel not found" error

**Possible Causes**:
- Bot not invited to the channel
- Channel ID is incorrect
- Channel was archived or deleted

**Solutions**:
1. Invite bot to channel: `/invite @Joy Notifications`
2. Verify channel ID using `getChannels()` method
3. Check if channel is archived in Slack

### Problem: Notifications not being sent

**Possible Causes**:
- Queue worker not running
- Observer not registered
- Client doesn't have `slack_channel_id` set
- Bot lacks `chat:write` permission

**Solutions**:
1. Check queue worker is running: `php artisan queue:work`
2. Verify observer registration in `AppServiceProvider::boot()`
3. Check client has Slack channel configured
4. Review `slack_notifications` table for failed entries:
   ```sql
   SELECT * FROM slack_notifications WHERE status = 'failed' ORDER BY created_at DESC;
   ```
5. Check logs for error messages

### Problem: "Token expired" or "Invalid authentication"

**Possible Causes**:
- Bot was uninstalled from workspace
- Token was regenerated in Slack app settings

**Solutions**:
1. Go to Slack app **"OAuth & Permissions"**
2. Reinstall app to workspace
3. Copy new bot token
4. Update `SLACK_BOT_TOKEN` in `.env`
5. Update token in `slack_workspaces` table

### Problem: Dropdown shows no channels

**Possible Causes**:
- No channels exist in workspace
- Bot has no channel access (not invited to any channels)
- API permissions incorrect

**Solutions**:
1. Create a test channel in Slack
2. Invite bot: `/invite @Joy Notifications`
3. Verify bot scopes include `channels:read` and `groups:read`
4. Check browser console for JavaScript errors
5. Test API directly in tinker

---

## Security Best Practices

### 1. Token Security

- ✅ **DO**: Store tokens in `.env` file only
- ✅ **DO**: Add `.env` to `.gitignore`
- ✅ **DO**: Use environment variables in production
- ❌ **DON'T**: Commit tokens to version control
- ❌ **DON'T**: Share tokens in Slack, email, or documentation

### 2. Token Rotation

Rotate your bot token periodically:

1. Go to Slack app **"OAuth & Permissions"**
2. Click **"Revoke"** next to the bot token
3. Click **"Reinstall to Workspace"**
4. Update `.env` with new token
5. Update `slack_workspaces` table

### 3. Channel Access

- Only invite the bot to channels where notifications are needed
- Review bot's channel access regularly
- Remove bot from channels that no longer need notifications

### 4. Audit Trail

The `slack_notifications` table records all notification attempts:

```sql
-- View recent notifications
SELECT type, status, channel_name, sent_at, error_message
FROM slack_notifications
ORDER BY created_at DESC
LIMIT 50;

-- Check failure rate
SELECT
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 2) as percentage
FROM slack_notifications
GROUP BY status;
```

### 5. Rate Limiting

Slack has rate limits for API calls:
- **Tier 1 methods** (like `chat.postMessage`): ~1 request per second per workspace
- Joy implements automatic retry logic for rate limit errors
- Monitor `slack_notifications` table for frequent `failed` status

### 6. Error Monitoring

Set up monitoring for failed notifications:

```sql
-- Create a view for failed notifications
CREATE VIEW failed_slack_notifications AS
SELECT
    id,
    type,
    channel_name,
    error_message,
    created_at
FROM slack_notifications
WHERE status = 'failed'
ORDER BY created_at DESC;
```

---

## HTTPS Development Setup (For OAuth Testing)

> **Note**: This section is only needed if you want to implement or test OAuth flows. For current functionality, you can skip this entirely.

Slack requires HTTPS for OAuth callbacks, even in development. Here are your options:

### Option 1: ngrok (Recommended)

**Install ngrok**:
```bash
# macOS
brew install ngrok

# Or download from https://ngrok.com/download
```

**Usage**:
```bash
# Start Laravel
php artisan serve

# In another terminal, create HTTPS tunnel
ngrok http 8000
```

You'll get output like:
```
Forwarding  https://abc123.ngrok.io -> http://localhost:8000
```

**Update your configuration**:

1. Copy the HTTPS URL (e.g., `https://abc123.ngrok.io`)
2. Update `.env`:
   ```env
   SLACK_REDIRECT_URI=https://abc123.ngrok.io/admin/slack/callback
   APP_URL=https://abc123.ngrok.io
   ```
3. Update Slack app redirect URL to match
4. Clear config cache: `php artisan config:clear`

**Limitations**:
- Free ngrok URLs change every time you restart
- Consider paid ngrok account for persistent subdomain

### Option 2: Laravel Valet Share (macOS only)

If using Laravel Valet:

```bash
cd /path/to/joy-app
valet share
```

This creates a temporary HTTPS URL using ngrok under the hood.

### Option 3: expose.dev

```bash
# Install
composer global require beyondcode/expose

# Share
expose share http://127.0.0.1:8000 --subdomain=joy-dev
```

### Option 4: LocalTunnel

```bash
# Install
npm install -g localtunnel

# Share
lt --port 8000 --subdomain joy-dev
```

Gets you: `https://joy-dev.loca.lt`

---

## Advanced Configuration

### Multiple Workspaces (Future Enhancement)

Currently, Joy supports one Slack workspace. To add multi-workspace support:

1. Extend `SlackWorkspace` model to support multiple active workspaces
2. Add workspace selector to Client form
3. Update `SlackService` to accept workspace parameter
4. Modify observers to determine workspace per client

### Custom Message Formatting

To customize Slack message appearance, edit:
- `app/Services/SlackBlockFormatter.php`

Slack Block Kit resources:
- [Block Kit Builder](https://app.slack.com/block-kit-builder)
- [Block Kit Documentation](https://api.slack.com/block-kit)

### Notification Preferences

To add per-client notification preferences:

1. Add migration for notification settings:
   ```php
   Schema::table('clients', function (Blueprint $table) {
       $table->json('slack_notification_preferences')->nullable();
   });
   ```

2. Update observers to check preferences before sending

3. Add UI in Filament client form for preference management

---

## Reference

### Slack API Documentation

- [Slack API Home](https://api.slack.com/)
- [Bot Users](https://api.slack.com/bot-users)
- [OAuth Scopes](https://api.slack.com/scopes)
- [Block Kit](https://api.slack.com/block-kit)
- [Rate Limits](https://api.slack.com/docs/rate-limits)

### Joy Application Files

**Core Services**:
- `app/Services/SlackService.php` - API communication
- `app/Services/SlackNotificationService.php` - Notification orchestration
- `app/Services/SlackBlockFormatter.php` - Message formatting

**Contracts**:
- `app/Contracts/SlackServiceContract.php`
- `app/Contracts/SlackNotificationServiceContract.php`
- `app/Contracts/SlackBlockFormatterContract.php`

**Observers**:
- `app/Observers/CommentObserver.php`
- `app/Observers/ContentItemObserver.php`
- `app/Observers/ClientStatusUpdateObserver.php`

**Jobs**:
- `app/Jobs/SendClientCommentNotification.php`
- `app/Jobs/SendContentApprovedNotification.php`
- `app/Jobs/SendStatusfactionSubmittedNotification.php`
- `app/Jobs/SendStatusfactionApprovedNotification.php`

**Models**:
- `app/Models/SlackWorkspace.php`
- `app/Models/SlackNotification.php`

**Configuration**:
- `.env` - Environment variables
- `config/services.php` - Service configuration

---

## Support

For issues or questions:

1. Check [Troubleshooting](#troubleshooting) section
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check Slack API status: [status.slack.com](https://status.slack.com)
4. Review Joy documentation in `/specs` folder

---

**Last Updated**: 2025-10-11
**Version**: 1.0
