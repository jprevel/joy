# Tasks: Slack Integration for Client Notifications

**Feature Branch**: `004-i-want-to`
**Created**: 2025-10-10
**Status**: Ready for Implementation

---

## Test Suite Lock Enforcement

**CRITICAL**: This feature must comply with the test suite lock (42 test files maximum, currently at 42).

**Rules**:
- ❌ NO new test files without explicit user approval
- ✅ Add test methods to existing test files
- ✅ Create test traits (not counted as test files)
- ✅ Run `./scripts/test-lock.sh` before and after each phase
- ❌ Zero tolerance for failing tests

**Before starting ANY task**: Run `./scripts/test-lock.sh` to ensure all tests pass.

---

## Table of Contents

1. [Setup Tasks](#setup-tasks-t001-t005) (T001-T005)
2. [Database Tasks](#database-tasks-t006-t009-p) (T006-T009) [P]
3. [Model Tasks](#model-tasks-t010-t012-p) (T010-T012) [P]
4. [Factory Tasks](#factory-tasks-t013-p) (T013) [P]
5. [Service Implementation Tasks](#service-implementation-tasks-t014-t016) (T014-T016)
6. [Job Tasks](#job-tasks-t017-t021-p) (T017-T021) [P]
7. [Observer Tasks](#observer-tasks-t022-t024-p) (T022-T024) [P]
8. [Integration Tasks](#integration-tasks-t025-t027) (T025-T027)
9. [Test Tasks](#test-tasks-t028-t030) (T028-T030)
10. [Polish Tasks](#polish-tasks-t031-t033) (T031-T033)

**Legend**: [P] = Can be executed in parallel with other [P] tasks in same section

---

## Setup Tasks (T001-T005)

### T001: Create Directory Structure [P]

**Depends on**: None
**Estimated time**: 5 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Contracts/`
- `/Users/jprevel/Documents/joy/joy-app/app/Jobs/`
- `/Users/jprevel/Documents/joy/joy-app/app/Observers/`
- `/Users/jprevel/Documents/joy/joy-app/tests/Traits/`

**Commands**:
```bash
mkdir -p /Users/jprevel/Documents/joy/joy-app/app/Contracts
mkdir -p /Users/jprevel/Documents/joy/joy-app/app/Jobs
mkdir -p /Users/jprevel/Documents/joy/joy-app/app/Observers
mkdir -p /Users/jprevel/Documents/joy/joy-app/tests/Traits
```

**Verification**:
```bash
ls -la /Users/jprevel/Documents/joy/joy-app/app/ | grep -E "Contracts|Jobs|Observers"
ls -la /Users/jprevel/Documents/joy/joy-app/tests/ | grep "Traits"
```

**Details**:
Create the new directory structure required for Slack integration. These directories will house service contracts, background jobs, model observers, and test helpers.

---

### T002: Copy SlackServiceContract [P]

**Depends on**: T001
**Estimated time**: 2 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Contracts/SlackServiceContract.php`

**Commands**:
```bash
cp /Users/jprevel/Documents/joy/specs/004-i-want-to/contracts/SlackServiceContract.php /Users/jprevel/Documents/joy/joy-app/app/Contracts/SlackServiceContract.php
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Contracts/SlackServiceContract.php | head -20
```

**Details**:
Copy the SlackServiceContract interface from the specs directory to the app. This contract defines methods for low-level Slack API interaction (testConnection, getChannels, postMessage).

---

### T003: Copy SlackNotificationServiceContract [P]

**Depends on**: T001
**Estimated time**: 2 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Contracts/SlackNotificationServiceContract.php`

**Commands**:
```bash
cp /Users/jprevel/Documents/joy/specs/004-i-want-to/contracts/SlackNotificationServiceContract.php /Users/jprevel/Documents/joy/joy-app/app/Contracts/SlackNotificationServiceContract.php
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Contracts/SlackNotificationServiceContract.php | head -20
```

**Details**:
Copy the SlackNotificationServiceContract interface. This contract defines business logic methods for sending different types of notifications (client comments, content approvals, statusfaction updates).

---

### T004: Copy SlackBlockFormatterContract [P]

**Depends on**: T001
**Estimated time**: 2 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Contracts/SlackBlockFormatterContract.php`

**Commands**:
```bash
cp /Users/jprevel/Documents/joy/specs/004-i-want-to/contracts/SlackBlockFormatterContract.php /Users/jprevel/Documents/joy/joy-app/app/Contracts/SlackBlockFormatterContract.php
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Contracts/SlackBlockFormatterContract.php | head -20
```

**Details**:
Copy the SlackBlockFormatterContract interface. This contract defines methods for formatting Joy models into Slack Block Kit JSON messages.

---

### T005: Configure Environment Variables

**Depends on**: None
**Estimated time**: 10 minutes
**Files modified**:
- `/Users/jprevel/Documents/joy/joy-app/.env.example`
- `/Users/jprevel/Documents/joy/joy-app/config/services.php`

**Commands**:
```bash
# Update .env.example (manual edit required)
# Update config/services.php (manual edit required)
```

**Details**:

1. **Update `.env.example`**: Add Slack configuration variables:
```env
# Slack Integration
SLACK_CLIENT_ID=
SLACK_CLIENT_SECRET=
SLACK_BOT_TOKEN=
SLACK_REDIRECT_URI=http://localhost:8000/admin/slack/callback
```

2. **Update `config/services.php`**: Add Slack service configuration:
```php
'slack' => [
    'client_id' => env('SLACK_CLIENT_ID'),
    'client_secret' => env('SLACK_CLIENT_SECRET'),
    'bot_token' => env('SLACK_BOT_TOKEN'),
    'redirect_uri' => env('SLACK_REDIRECT_URI'),
],
```

3. **Update local `.env`**: Copy variables from `.env.example` and add actual bot token (obtain from Slack app dashboard).

**Verification**:
```bash
grep -A 4 "Slack Integration" /Users/jprevel/Documents/joy/joy-app/.env.example
grep -A 6 "'slack'" /Users/jprevel/Documents/joy/joy-app/config/services.php
```

---

## Database Tasks (T006-T009) [P]

### T006: Create Migration - slack_workspaces Table [P]

**Depends on**: None
**Estimated time**: 10 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/database/migrations/2025_10_10_000001_create_slack_workspaces_table.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:migration create_slack_workspaces_table
```

**Migration code**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slack_workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('team_id')->unique()->comment('Slack team/workspace ID');
            $table->string('team_name')->comment('Slack workspace name');
            $table->text('bot_token')->comment('Encrypted bot OAuth token');
            $table->text('access_token')->nullable()->comment('Encrypted user access token');
            $table->text('scopes')->nullable()->comment('JSON array of granted OAuth scopes');
            $table->string('bot_user_id')->nullable()->comment('Slack bot user ID');
            $table->boolean('is_active')->default(true)->comment('Is this workspace connection active');
            $table->timestamp('last_sync_at')->nullable()->comment('Last time channels were synced');
            $table->text('last_error')->nullable()->comment('Last connection error message');
            $table->json('metadata')->nullable()->comment('Additional workspace metadata');
            $table->timestamps();

            $table->index('team_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slack_workspaces');
    }
};
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/database/migrations/*_create_slack_workspaces_table.php
```

**Details**:
Create migration for the `slack_workspaces` table. This table stores Slack workspace connection configuration, including encrypted bot token and metadata. Only one active workspace is supported.

---

### T007: Create Migration - slack_notifications Table [P]

**Depends on**: None
**Estimated time**: 15 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/database/migrations/2025_10_10_000002_create_slack_notifications_table.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:migration create_slack_notifications_table
```

**Migration code**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slack_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('slack_workspaces')->onDelete('cascade');
            $table->enum('type', [
                'client_comment',
                'content_approved',
                'content_rejected',
                'statusfaction_submitted',
                'statusfaction_approved'
            ])->comment('Type of notification');
            $table->string('notifiable_type')->comment('Polymorphic type');
            $table->unsignedBigInteger('notifiable_id')->comment('Polymorphic ID');
            $table->string('channel_id')->comment('Slack channel ID');
            $table->string('channel_name')->nullable()->comment('Slack channel name (cached)');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->json('payload')->nullable()->comment('Slack message payload');
            $table->json('response')->nullable()->comment('Slack API response');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('status');
            $table->index('type');
            $table->index('channel_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slack_notifications');
    }
};
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/database/migrations/*_create_slack_notifications_table.php
```

**Details**:
Create migration for the `slack_notifications` table. This is an audit log of all Slack notification attempts (success and failure). Uses polymorphic relationships to track source entities (Comment, ContentItem, ClientStatusUpdate).

**CRITICAL CLARIFICATION**: NO retries on Slack notification failures (clarification #2 from spec.md). Single attempt only, then log and move on.

---

### T008: Create Migration - add_slack_fields_to_clients_table [P]

**Depends on**: None
**Estimated time**: 10 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/database/migrations/2025_10_10_000003_add_slack_fields_to_clients_table.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:migration add_slack_fields_to_clients_table
```

**Migration code**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Add after existing columns (adjust 'after' if needed based on actual schema)
            $table->string('slack_channel_id')->nullable()->comment('Associated Slack channel ID');
            $table->string('slack_channel_name')->nullable()->comment('Associated Slack channel name (cached)');

            $table->index('slack_channel_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['slack_channel_id']);
            $table->dropColumn(['slack_channel_id', 'slack_channel_name']);
        });
    }
};
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/database/migrations/*_add_slack_fields_to_clients_table.php
```

**Details**:
Add Slack channel association fields to the `clients` table. Stores both channel ID (for API calls) and channel name (for display in admin UI).

---

### T009: Run Migrations and Verify Schema

**Depends on**: T006, T007, T008
**Estimated time**: 5 minutes
**Files modified**: Database schema

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan migrate
```

**Verification**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan tinker
>>> Schema::hasTable('slack_workspaces')
>>> Schema::hasTable('slack_notifications')
>>> Schema::hasColumn('clients', 'slack_channel_id')
>>> Schema::hasColumn('clients', 'slack_channel_name')
>>> exit
```

**Details**:
Run all migrations in order. Verify that the three new tables exist and the clients table has been updated with Slack fields.

**IMPORTANT**: If migrations fail, DO NOT proceed. Fix issues before continuing.

---

## Model Tasks (T010-T012) [P]

### T010: Create SlackWorkspace Model [P]

**Depends on**: T009
**Estimated time**: 15 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Models/SlackWorkspace.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:model SlackWorkspace
```

**Model code** (replace generated file):
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlackWorkspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'team_name',
        'bot_token',
        'access_token',
        'scopes',
        'bot_user_id',
        'is_active',
        'last_sync_at',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'bot_token' => 'encrypted',
        'access_token' => 'encrypted',
        'scopes' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get all notifications for this workspace
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(SlackNotification::class, 'workspace_id');
    }

    /**
     * Check if workspace connection is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the default (active) workspace
     */
    public static function getDefault(): ?self
    {
        return self::where('is_active', true)->first();
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Models/SlackWorkspace.php
```

**Details**:
Create the SlackWorkspace model with encrypted casts for sensitive tokens. This model represents the connected Slack workspace configuration.

---

### T011: Create SlackNotification Model [P]

**Depends on**: T009
**Estimated time**: 20 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Models/SlackNotification.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:model SlackNotification
```

**Model code** (replace generated file):
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SlackNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'channel_id',
        'channel_name',
        'status',
        'payload',
        'response',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the workspace that owns this notification
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(SlackWorkspace::class, 'workspace_id');
    }

    /**
     * Get the notifiable entity (Comment, ContentItem, or ClientStatusUpdate)
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Get only failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Get only sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope: Get notifications for a specific channel
     */
    public function scopeForChannel($query, string $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Mark notification as successfully sent
     */
    public function markAsSent(array $response): void
    {
        $this->update([
            'status' => 'sent',
            'response' => $response,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Models/SlackNotification.php
```

**Details**:
Create the SlackNotification model with polymorphic relationship support. This model serves as an audit log for all Slack notification attempts.

---

### T012: Update Client Model

**Depends on**: T009
**Estimated time**: 10 minutes
**Files modified**:
- `/Users/jprevel/Documents/joy/joy-app/app/Models/Client.php`

**Details**:
Update the Client model to include Slack integration fields and helper methods.

**Changes to make**:

1. Add to `$fillable` array:
```php
'slack_channel_id',
'slack_channel_name',
```

2. Add helper method to check if client has Slack integration:
```php
/**
 * Check if client has Slack integration enabled
 */
public function hasSlackIntegration(): bool
{
    return !empty($this->slack_channel_id);
}
```

**Verification**:
```bash
grep -A 2 "hasSlackIntegration" /Users/jprevel/Documents/joy/joy-app/app/Models/Client.php
grep "slack_channel_id" /Users/jprevel/Documents/joy/joy-app/app/Models/Client.php
```

---

## Factory Tasks (T013) [P]

### T013: Create SlackWorkspaceFactory [P]

**Depends on**: T010
**Estimated time**: 10 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/database/factories/SlackWorkspaceFactory.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:factory SlackWorkspaceFactory
```

**Factory code** (replace generated file):
```php
<?php

namespace Database\Factories;

use App\Models\SlackWorkspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlackWorkspace>
 */
class SlackWorkspaceFactory extends Factory
{
    protected $model = SlackWorkspace::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => 'T' . $this->faker->numerify('##########'),
            'team_name' => $this->faker->company,
            'bot_token' => 'xoxb-test-token-' . $this->faker->uuid,
            'scopes' => ['channels:read', 'groups:read', 'chat:write', 'chat:write.public'],
            'bot_user_id' => 'U' . $this->faker->numerify('##########'),
            'is_active' => true,
            'last_sync_at' => now(),
        ];
    }

    /**
     * Indicate that the workspace is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/database/factories/SlackWorkspaceFactory.php
cd /Users/jprevel/Documents/joy/joy-app && php artisan tinker
>>> \App\Models\SlackWorkspace::factory()->make()
>>> exit
```

**Details**:
Create factory for SlackWorkspace model to support testing. Generates realistic test data for Slack workspace connections.

---

## Service Implementation Tasks (T014-T016)

### T014: Implement SlackService

**Depends on**: T002, T010
**Estimated time**: 45 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Services/SlackService.php`

**Commands**:
```bash
touch /Users/jprevel/Documents/joy/joy-app/app/Services/SlackService.php
```

**Service code**:
```php
<?php

namespace App\Services;

use App\Contracts\SlackServiceContract;
use App\Models\SlackWorkspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService implements SlackServiceContract
{
    protected ?SlackWorkspace $workspace = null;
    protected string $baseUrl = 'https://slack.com/api/';

    /**
     * Set the workspace to use for API calls
     */
    public function setWorkspace(SlackWorkspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    /**
     * Get the current workspace or default active workspace
     */
    protected function getWorkspace(): ?SlackWorkspace
    {
        return $this->workspace ?? SlackWorkspace::getDefault();
    }

    /**
     * Test connection to Slack API
     */
    public function testConnection(): array
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
            ])->post($this->baseUrl . 'auth.test');

            $data = $response->json();

            if (!$data['ok'] ?? false) {
                return ['success' => false, 'error' => $data['error'] ?? 'Unknown error'];
            }

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Slack connection test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get list of channels from Slack
     *
     * CRITICAL: Fetches both public AND private channels (clarification #4 from spec.md)
     * NO caching (clarification #5 from spec.md) - fetches live from API when needed
     */
    public function getChannels(bool $includeArchived = false, bool $includePrivate = true): array
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        try {
            $channels = [];

            // Fetch public channels
            $publicResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
            ])->get($this->baseUrl . 'conversations.list', [
                'types' => 'public_channel',
                'exclude_archived' => !$includeArchived,
                'limit' => 200,
            ]);

            $publicData = $publicResponse->json();

            if ($publicData['ok'] ?? false) {
                $channels = array_merge($channels, $publicData['channels'] ?? []);
            }

            // Fetch private channels (requires groups:read scope)
            if ($includePrivate) {
                $privateResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $workspace->bot_token,
                ])->get($this->baseUrl . 'conversations.list', [
                    'types' => 'private_channel',
                    'exclude_archived' => !$includeArchived,
                    'limit' => 200,
                ]);

                $privateData = $privateResponse->json();

                if ($privateData['ok'] ?? false) {
                    $channels = array_merge($channels, $privateData['channels'] ?? []);
                }
            }

            return [
                'success' => true,
                'channels' => $channels,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Slack channels', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Post message to Slack channel
     */
    public function postMessage(string $channelId, array $blocks, ?string $text = null): array
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'chat.postMessage', [
                'channel' => $channelId,
                'blocks' => $blocks,
                'text' => $text ?? 'New notification from Joy',
            ]);

            $data = $response->json();

            if (!$data['ok'] ?? false) {
                Log::warning('Slack message failed', [
                    'channel' => $channelId,
                    'error' => $data['error'] ?? 'Unknown error',
                ]);
                return ['success' => false, 'error' => $data['error'] ?? 'Unknown error'];
            }

            return [
                'success' => true,
                'ts' => $data['ts'] ?? null,
                'channel' => $data['channel'] ?? $channelId,
            ];
        } catch (\Exception $e) {
            Log::error('Slack postMessage exception', [
                'channel' => $channelId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if a channel exists
     */
    public function channelExists(string $channelId): bool
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
            ])->get($this->baseUrl . 'conversations.info', [
                'channel' => $channelId,
            ]);

            $data = $response->json();

            return $data['ok'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get workspace info
     */
    public function getWorkspaceInfo(): array
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
            ])->get($this->baseUrl . 'team.info');

            $data = $response->json();

            if (!$data['ok'] ?? false) {
                return ['success' => false, 'error' => $data['error'] ?? 'Unknown error'];
            }

            return [
                'success' => true,
                'team' => $data['team'] ?? [],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Services/SlackService.php | head -50
```

**Details**:
Implement the SlackService as a wrapper around the Slack Web API. Handles authentication, channel fetching (both public AND private per clarification #4), and message posting. NO channel caching per clarification #5 - always fetches live from API.

**CRITICAL**: Links in notifications direct to specific content item (clarification #4 from spec.md), NOT to calendar page.

---

### T015: Implement SlackBlockFormatter

**Depends on**: T004
**Estimated time**: 60 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Services/SlackBlockFormatter.php`

**Commands**:
```bash
touch /Users/jprevel/Documents/joy/joy-app/app/Services/SlackBlockFormatter.php
```

**Service code**:
```php
<?php

namespace App\Services;

use App\Contracts\SlackBlockFormatterContract;
use App\Models\ClientStatusUpdate;
use App\Models\Comment;
use App\Models\ContentItem;

class SlackBlockFormatter implements SlackBlockFormatterContract
{
    /**
     * Format client comment notification
     *
     * CRITICAL: Link directs to specific content item (clarification #4 from spec.md)
     */
    public function formatClientComment(Comment $comment): array
    {
        $contentItem = $comment->contentItem;
        $client = $contentItem->client;

        // Generate link to specific content item detail page
        $contentLink = url("/content-items/{$contentItem->id}");

        return [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '💬 New Comment from Client',
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Client:*\n{$this->escapeText($client->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Author:*\n{$this->escapeText($comment->author_name)}",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Content:*\n{$this->escapeText($contentItem->title)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Platform:*\n{$contentItem->platform ?? 'N/A'}",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Comment:*\n{$this->escapeText($comment->body)}",
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "<{$contentLink}|View Content Item in Joy>",
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "Commented {$this->formatTimestamp($comment->created_at)}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Format content approved notification
     */
    public function formatContentApproved(ContentItem $contentItem): array
    {
        $client = $contentItem->client;
        $contentLink = url("/content-items/{$contentItem->id}");

        return [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '✅ Content Approved',
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Client:*\n{$this->escapeText($client->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Status:*\nApproved",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Content:*\n{$this->escapeText($contentItem->title)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Platform:*\n{$contentItem->platform ?? 'N/A'}",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "<{$contentLink}|View Content Item in Joy>",
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "Approved {$this->formatTimestamp($contentItem->updated_at)}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Format content rejected notification
     */
    public function formatContentRejected(ContentItem $contentItem): array
    {
        $client = $contentItem->client;
        $contentLink = url("/content-items/{$contentItem->id}");

        return [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '❌ Content Rejected',
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Client:*\n{$this->escapeText($client->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Status:*\nRejected",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Content:*\n{$this->escapeText($contentItem->title)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Platform:*\n{$contentItem->platform ?? 'N/A'}",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "<{$contentLink}|View Content Item in Joy>",
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "Rejected {$this->formatTimestamp($contentItem->updated_at)}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Format statusfaction submitted notification
     *
     * CRITICAL: DOES NOT include client satisfaction or team health scores (FR-019, FR-020 from spec.md)
     */
    public function formatStatusfactionSubmitted(ClientStatusUpdate $statusUpdate): array
    {
        $client = $statusUpdate->client;
        $user = $statusUpdate->user;
        $team = $client->team;

        return [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '📊 Statusfaction Report Submitted',
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Client:*\n{$this->escapeText($client->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Team:*\n{$this->escapeText($team->name ?? 'N/A')}",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Account Manager:*\n{$this->escapeText($user->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Week:*\n{$statusUpdate->week_start_date->format('M d, Y')}",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Status Notes:*\n{$this->escapeText($statusUpdate->status_notes)}",
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "Submitted {$this->formatTimestamp($statusUpdate->created_at)}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Format statusfaction approved notification
     */
    public function formatStatusfactionApproved(ClientStatusUpdate $statusUpdate): array
    {
        $client = $statusUpdate->client;
        $approver = $statusUpdate->approver;

        return [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '✅ Statusfaction Report Approved',
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Client:*\n{$this->escapeText($client->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Approved by:*\n{$this->escapeText($approver->name ?? 'Admin')}",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Week:*\n{$statusUpdate->week_start_date->format('M d, Y')}",
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "Approved {$this->formatTimestamp($statusUpdate->approved_at)}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Escape text for Slack markdown
     */
    public function escapeText(string $text): string
    {
        // Escape special Slack markdown characters
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text);
    }

    /**
     * Format timestamp for human-readable display
     */
    public function formatTimestamp(\DateTimeInterface $timestamp): string
    {
        return $timestamp->diffForHumans();
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Services/SlackBlockFormatter.php | head -100
```

**Details**:
Implement the SlackBlockFormatter to create rich Slack Block Kit messages. CRITICAL: Statusfaction notifications DO NOT include satisfaction or team health scores (FR-019, FR-020 from spec.md).

---

### T016: Implement SlackNotificationService

**Depends on**: T003, T011, T014, T015
**Estimated time**: 60 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Services/SlackNotificationService.php`

**Commands**:
```bash
touch /Users/jprevel/Documents/joy/joy-app/app/Services/SlackNotificationService.php
```

**Service code**:
```php
<?php

namespace App\Services;

use App\Contracts\SlackNotificationServiceContract;
use App\Contracts\SlackServiceContract;
use App\Contracts\SlackBlockFormatterContract;
use App\Models\ClientStatusUpdate;
use App\Models\Comment;
use App\Models\ContentItem;
use App\Models\SlackNotification;
use App\Models\SlackWorkspace;
use Illuminate\Support\Facades\Log;

class SlackNotificationService implements SlackNotificationServiceContract
{
    public function __construct(
        protected SlackServiceContract $slackService,
        protected SlackBlockFormatterContract $formatter
    ) {}

    /**
     * Send client comment notification
     */
    public function sendClientCommentNotification(Comment $comment): array
    {
        $client = $comment->contentItem->client;

        if (!$client->hasSlackIntegration()) {
            Log::info('Client does not have Slack integration', ['client_id' => $client->id]);
            return ['success' => false, 'error' => 'Client does not have Slack integration'];
        }

        $workspace = SlackWorkspace::getDefault();

        if (!$workspace) {
            Log::warning('No Slack workspace configured');
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        // Create audit record (pending)
        $notification = SlackNotification::create([
            'workspace_id' => $workspace->id,
            'type' => 'client_comment',
            'notifiable_type' => Comment::class,
            'notifiable_id' => $comment->id,
            'channel_id' => $client->slack_channel_id,
            'channel_name' => $client->slack_channel_name,
            'status' => 'pending',
        ]);

        try {
            // Format message
            $blocks = $this->formatter->formatClientComment($comment);

            // Send to Slack
            $result = $this->slackService->postMessage(
                $client->slack_channel_id,
                $blocks,
                "New comment from {$comment->author_name}"
            );

            // Update notification record
            $notification->update(['payload' => $blocks]);

            if ($result['success']) {
                $notification->markAsSent($result);
                Log::info('Slack notification sent successfully', [
                    'notification_id' => $notification->id,
                    'type' => 'client_comment',
                ]);
            } else {
                // NO RETRY per clarification #2 from spec.md - just log and mark as failed
                $notification->markAsFailed($result['error']);
                Log::warning('Slack notification failed', [
                    'notification_id' => $notification->id,
                    'error' => $result['error'],
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            Log::error('Slack notification exception', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send content approved notification
     */
    public function sendContentApprovedNotification(ContentItem $contentItem): array
    {
        $client = $contentItem->client;

        if (!$client->hasSlackIntegration()) {
            return ['success' => false, 'error' => 'Client does not have Slack integration'];
        }

        $workspace = SlackWorkspace::getDefault();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        $notification = SlackNotification::create([
            'workspace_id' => $workspace->id,
            'type' => 'content_approved',
            'notifiable_type' => ContentItem::class,
            'notifiable_id' => $contentItem->id,
            'channel_id' => $client->slack_channel_id,
            'channel_name' => $client->slack_channel_name,
            'status' => 'pending',
        ]);

        try {
            $blocks = $this->formatter->formatContentApproved($contentItem);
            $result = $this->slackService->postMessage(
                $client->slack_channel_id,
                $blocks,
                "Content approved: {$contentItem->title}"
            );

            $notification->update(['payload' => $blocks]);

            if ($result['success']) {
                $notification->markAsSent($result);
            } else {
                $notification->markAsFailed($result['error']);
            }

            return $result;
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send content rejected notification
     */
    public function sendContentRejectedNotification(ContentItem $contentItem): array
    {
        $client = $contentItem->client;

        if (!$client->hasSlackIntegration()) {
            return ['success' => false, 'error' => 'Client does not have Slack integration'];
        }

        $workspace = SlackWorkspace::getDefault();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        $notification = SlackNotification::create([
            'workspace_id' => $workspace->id,
            'type' => 'content_rejected',
            'notifiable_type' => ContentItem::class,
            'notifiable_id' => $contentItem->id,
            'channel_id' => $client->slack_channel_id,
            'channel_name' => $client->slack_channel_name,
            'status' => 'pending',
        ]);

        try {
            $blocks = $this->formatter->formatContentRejected($contentItem);
            $result = $this->slackService->postMessage(
                $client->slack_channel_id,
                $blocks,
                "Content rejected: {$contentItem->title}"
            );

            $notification->update(['payload' => $blocks]);

            if ($result['success']) {
                $notification->markAsSent($result);
            } else {
                $notification->markAsFailed($result['error']);
            }

            return $result;
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send statusfaction submitted notification
     *
     * CRITICAL: NO notifications when editing pending reports (clarification #1 from spec.md)
     * Only sent on initial submission
     */
    public function sendStatusfactionSubmittedNotification(ClientStatusUpdate $statusUpdate): array
    {
        $client = $statusUpdate->client;

        if (!$client->hasSlackIntegration()) {
            return ['success' => false, 'error' => 'Client does not have Slack integration'];
        }

        $workspace = SlackWorkspace::getDefault();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        $notification = SlackNotification::create([
            'workspace_id' => $workspace->id,
            'type' => 'statusfaction_submitted',
            'notifiable_type' => ClientStatusUpdate::class,
            'notifiable_id' => $statusUpdate->id,
            'channel_id' => $client->slack_channel_id,
            'channel_name' => $client->slack_channel_name,
            'status' => 'pending',
        ]);

        try {
            $blocks = $this->formatter->formatStatusfactionSubmitted($statusUpdate);
            $result = $this->slackService->postMessage(
                $client->slack_channel_id,
                $blocks,
                "New statusfaction report for {$client->name}"
            );

            $notification->update(['payload' => $blocks]);

            if ($result['success']) {
                $notification->markAsSent($result);
            } else {
                $notification->markAsFailed($result['error']);
            }

            return $result;
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send statusfaction approved notification
     */
    public function sendStatusfactionApprovedNotification(ClientStatusUpdate $statusUpdate): array
    {
        $client = $statusUpdate->client;

        if (!$client->hasSlackIntegration()) {
            return ['success' => false, 'error' => 'Client does not have Slack integration'];
        }

        $workspace = SlackWorkspace::getDefault();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        $notification = SlackNotification::create([
            'workspace_id' => $workspace->id,
            'type' => 'statusfaction_approved',
            'notifiable_type' => ClientStatusUpdate::class,
            'notifiable_id' => $statusUpdate->id,
            'channel_id' => $client->slack_channel_id,
            'channel_name' => $client->slack_channel_name,
            'status' => 'pending',
        ]);

        try {
            $blocks = $this->formatter->formatStatusfactionApproved($statusUpdate);
            $result = $this->slackService->postMessage(
                $client->slack_channel_id,
                $blocks,
                "Statusfaction report approved for {$client->name}"
            );

            $notification->update(['payload' => $blocks]);

            if ($result['success']) {
                $notification->markAsSent($result);
            } else {
                $notification->markAsFailed($result['error']);
            }

            return $result;
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if Slack is enabled for a client
     */
    public function isEnabledForClient(int $clientId): bool
    {
        $client = \App\Models\Client::find($clientId);
        return $client?->hasSlackIntegration() ?? false;
    }

    /**
     * Get Slack channel ID for a client
     */
    public function getClientChannelId(int $clientId): ?string
    {
        $client = \App\Models\Client::find($clientId);
        return $client?->slack_channel_id;
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Services/SlackNotificationService.php | head -100
```

**Details**:
Implement the SlackNotificationService as the orchestration layer. Validates client has Slack integration, creates audit records, calls formatter and API service. CRITICAL: NO retries on failures (clarification #2 from spec.md) - single attempt only.

---

## Job Tasks (T017-T021) [P]

### T017: Create SendClientCommentNotification Job [P]

**Depends on**: T001, T016
**Estimated time**: 15 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Jobs/SendClientCommentNotification.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:job SendClientCommentNotification
```

**Job code** (replace generated file):
```php
<?php

namespace App\Jobs;

use App\Contracts\SlackNotificationServiceContract;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendClientCommentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Comment $comment
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SlackNotificationServiceContract $slackNotificationService): void
    {
        $slackNotificationService->sendClientCommentNotification($this->comment);
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Jobs/SendClientCommentNotification.php
```

**Details**:
Create queued job to send Slack notification when client adds a comment. Job is dispatched by CommentObserver.

---

### T018: Create SendContentApprovedNotification Job [P]

**Depends on**: T001, T016
**Estimated time**: 15 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Jobs/SendContentApprovedNotification.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:job SendContentApprovedNotification
```

**Job code** (replace generated file):
```php
<?php

namespace App\Jobs;

use App\Contracts\SlackNotificationServiceContract;
use App\Models\ContentItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendContentApprovedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ContentItem $contentItem
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SlackNotificationServiceContract $slackNotificationService): void
    {
        $slackNotificationService->sendContentApprovedNotification($this->contentItem);
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Jobs/SendContentApprovedNotification.php
```

**Details**:
Create queued job to send Slack notification when content is approved. Job is dispatched by ContentItemObserver.

---

### T019: Create SendContentRejectedNotification Job [P]

**Depends on**: T001, T016
**Estimated time**: 15 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Jobs/SendContentRejectedNotification.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:job SendContentRejectedNotification
```

**Job code** (replace generated file):
```php
<?php

namespace App\Jobs;

use App\Contracts\SlackNotificationServiceContract;
use App\Models\ContentItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendContentRejectedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ContentItem $contentItem
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SlackNotificationServiceContract $slackNotificationService): void
    {
        $slackNotificationService->sendContentRejectedNotification($this->contentItem);
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Jobs/SendContentRejectedNotification.php
```

**Details**:
Create queued job to send Slack notification when content is rejected. Job is dispatched by ContentItemObserver.

---

### T020: Create SendStatusfactionSubmittedNotification Job [P]

**Depends on**: T001, T016
**Estimated time**: 15 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Jobs/SendStatusfactionSubmittedNotification.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:job SendStatusfactionSubmittedNotification
```

**Job code** (replace generated file):
```php
<?php

namespace App\Jobs;

use App\Contracts\SlackNotificationServiceContract;
use App\Models\ClientStatusUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendStatusfactionSubmittedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ClientStatusUpdate $statusUpdate
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SlackNotificationServiceContract $slackNotificationService): void
    {
        $slackNotificationService->sendStatusfactionSubmittedNotification($this->statusUpdate);
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Jobs/SendStatusfactionSubmittedNotification.php
```

**Details**:
Create queued job to send Slack notification when statusfaction report is submitted. Job is dispatched by ClientStatusUpdateObserver on creation. CRITICAL: NO notifications on edits (clarification #1 from spec.md).

---

### T021: Create SendStatusfactionApprovedNotification Job [P]

**Depends on**: T001, T016
**Estimated time**: 15 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Jobs/SendStatusfactionApprovedNotification.php`

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan make:job SendStatusfactionApprovedNotification
```

**Job code** (replace generated file):
```php
<?php

namespace App\Jobs;

use App\Contracts\SlackNotificationServiceContract;
use App\Models\ClientStatusUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendStatusfactionApprovedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ClientStatusUpdate $statusUpdate
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SlackNotificationServiceContract $slackNotificationService): void
    {
        $slackNotificationService->sendStatusfactionApprovedNotification($this->statusUpdate);
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Jobs/SendStatusfactionApprovedNotification.php
```

**Details**:
Create queued job to send Slack notification when statusfaction report is approved by admin. Job is dispatched by ClientStatusUpdateObserver on approval status change.

---

## Observer Tasks (T022-T024) [P]

### T022: Create CommentObserver [P]

**Depends on**: T001, T017
**Estimated time**: 20 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Observers/CommentObserver.php`

**Commands**:
```bash
touch /Users/jprevel/Documents/joy/joy-app/app/Observers/CommentObserver.php
```

**Observer code**:
```php
<?php

namespace App\Observers;

use App\Jobs\SendClientCommentNotification;
use App\Models\Comment;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     *
     * Only dispatch notification if comment is from client (not internal)
     */
    public function created(Comment $comment): void
    {
        // Check if comment is from client
        if (!$this->isFromClient($comment)) {
            return;
        }

        // Check if client has Slack integration
        if (!$comment->contentItem?->client?->hasSlackIntegration()) {
            return;
        }

        // Dispatch notification job
        SendClientCommentNotification::dispatch($comment);
    }

    /**
     * Check if comment is from a client (vs internal team member)
     */
    protected function isFromClient(Comment $comment): bool
    {
        // Assuming comments have an author_type field or similar
        // Adjust logic based on actual Comment model structure
        return $comment->author_type === 'client';
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Observers/CommentObserver.php
```

**Details**:
Create observer to watch for new comments. Only dispatches notification job if comment is from client AND client has Slack integration enabled. Internal comments are ignored.

**NOTE**: Adjust `isFromClient()` logic based on actual Comment model structure in the codebase.

---

### T023: Create ContentItemObserver [P]

**Depends on**: T001, T018, T019
**Estimated time**: 25 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Observers/ContentItemObserver.php`

**Commands**:
```bash
touch /Users/jprevel/Documents/joy/joy-app/app/Observers/ContentItemObserver.php
```

**Observer code**:
```php
<?php

namespace App\Observers;

use App\Jobs\SendContentApprovedNotification;
use App\Jobs\SendContentRejectedNotification;
use App\Models\ContentItem;

class ContentItemObserver
{
    /**
     * Handle the ContentItem "updated" event.
     *
     * Detect status changes to 'approved' or 'rejected' and dispatch notifications
     */
    public function updated(ContentItem $contentItem): void
    {
        // Check if client has Slack integration
        if (!$contentItem->client?->hasSlackIntegration()) {
            return;
        }

        // Check if status changed
        if (!$contentItem->isDirty('status')) {
            return;
        }

        // Get new status
        $newStatus = $contentItem->status;

        // Dispatch appropriate notification based on status
        if ($newStatus === 'approved') {
            SendContentApprovedNotification::dispatch($contentItem);
        } elseif ($newStatus === 'rejected') {
            SendContentRejectedNotification::dispatch($contentItem);
        }
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Observers/ContentItemObserver.php
```

**Details**:
Create observer to watch for content item status changes. Dispatches notification job when status changes to 'approved' or 'rejected'. Only fires if client has Slack integration.

**NOTE**: Adjust status field names ('approved', 'rejected') based on actual ContentItem model enum/field values.

---

### T024: Create ClientStatusUpdateObserver [P]

**Depends on**: T001, T020, T021
**Estimated time**: 30 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/app/Observers/ClientStatusUpdateObserver.php`

**IMPORTANT TERMINOLOGY NOTE**:
While the model is currently named `ClientStatusUpdate` in the codebase, this observer specifically handles **Statusfaction reports** (weekly status reports with satisfaction tracking), NOT general client comments. Statusfaction is distinct from the client comment notification system. The model will be renamed to `ClientStatusfactionUpdate` for clarity during implementation.

**Commands**:
```bash
touch /Users/jprevel/Documents/joy/joy-app/app/Observers/ClientStatusUpdateObserver.php
```

**Observer code**:
```php
<?php

namespace App\Observers;

use App\Jobs\SendStatusfactionApprovedNotification;
use App\Jobs\SendStatusfactionSubmittedNotification;
use App\Models\ClientStatusUpdate;

class ClientStatusUpdateObserver
{
    /**
     * Handle the ClientStatusUpdate "created" event.
     *
     * CRITICAL: Only send notification on INITIAL submission (clarification #1 from spec.md)
     * Do NOT send notifications on edits to pending reports
     */
    public function created(ClientStatusUpdate $statusUpdate): void
    {
        // Check if client has Slack integration
        if (!$statusUpdate->client?->hasSlackIntegration()) {
            return;
        }

        // Dispatch submission notification
        SendStatusfactionSubmittedNotification::dispatch($statusUpdate);
    }

    /**
     * Handle the ClientStatusUpdate "updated" event.
     *
     * Only dispatch approval notification when approval_status changes to 'approved'
     *
     * CRITICAL: Do NOT send submission notifications on edits (clarification #1 from spec.md)
     */
    public function updated(ClientStatusUpdate $statusUpdate): void
    {
        // Check if client has Slack integration
        if (!$statusUpdate->client?->hasSlackIntegration()) {
            return;
        }

        // Check if approval_status changed to 'approved'
        if ($statusUpdate->isDirty('approval_status') && $statusUpdate->approval_status === 'approved') {
            SendStatusfactionApprovedNotification::dispatch($statusUpdate);
        }

        // NOTE: Do NOT dispatch submission notification on edits
        // This prevents duplicate notifications when AM edits pending report
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/app/Observers/ClientStatusUpdateObserver.php
```

**Details**:
Create observer to watch for Statusfaction report lifecycle events (weekly status reports with satisfaction tracking). CRITICAL: Only sends submission notification on creation (first submission), NOT on edits to pending reports (clarification #1 from spec.md). Sends approval notification when approval_status changes to 'approved'.

**NOTE**: This is for Statusfaction reports (weekly status updates), NOT client comments on content items - those are handled separately by CommentObserver.

---

## Integration Tasks (T025-T027)

### T025: Update AppServiceProvider - Register Services and Observers

**Depends on**: T014, T015, T016, T022, T023, T024
**Estimated time**: 15 minutes
**Files modified**:
- `/Users/jprevel/Documents/joy/joy-app/app/Providers/AppServiceProvider.php`

**Details**:

Add the following to `AppServiceProvider.php`:

1. **In the `register()` method** - Add service bindings:
```php
use App\Contracts\SlackServiceContract;
use App\Contracts\SlackNotificationServiceContract;
use App\Contracts\SlackBlockFormatterContract;
use App\Services\SlackService;
use App\Services\SlackNotificationService;
use App\Services\SlackBlockFormatter;

public function register(): void
{
    // Existing bindings...

    // Slack Integration Service Bindings
    $this->app->bind(SlackServiceContract::class, SlackService::class);
    $this->app->bind(SlackNotificationServiceContract::class, SlackNotificationService::class);
    $this->app->bind(SlackBlockFormatterContract::class, SlackBlockFormatter::class);
}
```

2. **In the `boot()` method** - Register observers:
```php
use App\Models\Comment;
use App\Models\ContentItem;
use App\Models\ClientStatusUpdate;
use App\Observers\CommentObserver;
use App\Observers\ContentItemObserver;
use App\Observers\ClientStatusUpdateObserver;

public function boot(): void
{
    // Existing boot logic...

    // Register Slack Integration Observers
    Comment::observe(CommentObserver::class);
    ContentItem::observe(ContentItemObserver::class);
    ClientStatusUpdate::observe(ClientStatusUpdateObserver::class);
}
```

**Verification**:
```bash
grep -A 3 "SlackServiceContract" /Users/jprevel/Documents/joy/joy-app/app/Providers/AppServiceProvider.php
grep -A 3 "CommentObserver" /Users/jprevel/Documents/joy/joy-app/app/Providers/AppServiceProvider.php
```

---

### T026: Update Filament ClientForm - Add Slack Channel Selector

**Depends on**: T014
**Estimated time**: 30 minutes
**Files modified**:
- `/Users/jprevel/Documents/joy/joy-app/app/Filament/Resources/Clients/Schemas/ClientForm.php`

**Details**:

Add Slack channel selector to the Client form. CRITICAL: NO cache (clarification #5 from spec.md) - fetch channels from API live when form loads.

**Add to form schema**:
```php
use App\Contracts\SlackServiceContract;
use Filament\Forms\Components\Select;

// Add after existing fields (adjust placement as needed)
Select::make('slack_channel_id')
    ->label('Slack Channel')
    ->helperText('Select the Slack channel for client notifications')
    ->searchable()
    ->options(function (SlackServiceContract $slackService) {
        // Fetch channels live from API (no cache per clarification #5)
        $result = $slackService->getChannels(includeArchived: false, includePrivate: true);

        if (!$result['success']) {
            // Log error and return empty array
            \Illuminate\Support\Facades\Log::warning('Failed to fetch Slack channels for dropdown', [
                'error' => $result['error'] ?? 'Unknown error',
            ]);
            return [];
        }

        // Map channels to [id => name] array
        return collect($result['channels'] ?? [])
            ->mapWithKeys(fn($channel) => [$channel['id'] => '#' . $channel['name']])
            ->toArray();
    })
    ->afterStateUpdated(function ($state, callable $set) {
        // Update channel_name when channel_id is selected
        if ($state) {
            $slackService = app(SlackServiceContract::class);
            $result = $slackService->getChannels(includeArchived: false, includePrivate: true);

            if ($result['success']) {
                $channel = collect($result['channels'] ?? [])
                    ->firstWhere('id', $state);

                if ($channel) {
                    $set('slack_channel_name', '#' . $channel['name']);
                }
            }
        }
    })
    ->nullable(),

// Hidden field to store channel name
\Filament\Forms\Components\Hidden::make('slack_channel_name'),
```

**Verification**:
```bash
grep -A 10 "slack_channel_id" /Users/jprevel/Documents/joy/joy-app/app/Filament/Resources/Clients/Schemas/ClientForm.php
```

**NOTE**: Adjust field placement and form structure based on actual ClientForm.php structure.

---

### T027: Verify Configuration Files

**Depends on**: T005
**Estimated time**: 5 minutes
**Files verified**:
- `/Users/jprevel/Documents/joy/joy-app/.env.example`
- `/Users/jprevel/Documents/joy/joy-app/config/services.php`
- `/Users/jprevel/Documents/joy/joy-app/.env`

**Commands**:
```bash
# Verify .env.example has Slack variables
grep "SLACK_" /Users/jprevel/Documents/joy/joy-app/.env.example

# Verify config/services.php has Slack configuration
grep -A 6 "'slack'" /Users/jprevel/Documents/joy/joy-app/config/services.php

# Verify local .env has Slack token
grep "SLACK_BOT_TOKEN" /Users/jprevel/Documents/joy/joy-app/.env
```

**Details**:
Verify that all configuration files are properly set up for Slack integration. Ensure actual bot token is present in local `.env` file (not in `.env.example` or version control).

---

## Test Tasks (T028-T030)

### T028: Create SlackNotificationAssertions Trait

**Depends on**: T001
**Estimated time**: 30 minutes
**Files created**:
- `/Users/jprevel/Documents/joy/joy-app/tests/Traits/SlackNotificationAssertions.php`

**Commands**:
```bash
touch /Users/jprevel/Documents/joy/joy-app/tests/Traits/SlackNotificationAssertions.php
```

**Trait code**:
```php
<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * Test helper trait for Slack notification assertions
 *
 * This is a trait, NOT a test file, so it doesn't count toward test lock
 */
trait SlackNotificationAssertions
{
    /**
     * Mock Slack API with successful responses
     */
    protected function mockSlackApiSuccess(): void
    {
        Http::fake([
            'slack.com/api/auth.test' => Http::response([
                'ok' => true,
                'user' => 'test-bot',
            ], 200),
            'slack.com/api/conversations.list' => Http::response([
                'ok' => true,
                'channels' => [
                    ['id' => 'C123456', 'name' => 'test-channel', 'is_archived' => false],
                ],
            ], 200),
            'slack.com/api/chat.postMessage' => Http::response([
                'ok' => true,
                'ts' => '1234567890.123456',
                'channel' => 'C123456',
            ], 200),
        ]);
    }

    /**
     * Mock Slack API with failure responses
     */
    protected function mockSlackApiFailure(): void
    {
        Http::fake([
            'slack.com/api/*' => Http::response([
                'ok' => false,
                'error' => 'channel_not_found',
            ], 404),
        ]);
    }

    /**
     * Fake the queue for testing job dispatching
     */
    protected function fakeQueue(): void
    {
        Queue::fake();
    }

    /**
     * Assert Slack API was called with specific URL
     */
    protected function assertSlackApiCalled(string $endpoint): void
    {
        Http::assertSent(fn($request) =>
            str_contains($request->url(), $endpoint)
        );
    }

    /**
     * Assert Slack notification job was dispatched
     */
    protected function assertSlackJobDispatched(string $jobClass): void
    {
        Queue::assertPushed($jobClass);
    }

    /**
     * Assert Slack notification was NOT dispatched
     */
    protected function assertSlackJobNotDispatched(string $jobClass): void
    {
        Queue::assertNotPushed($jobClass);
    }
}
```

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/joy-app/tests/Traits/SlackNotificationAssertions.php
```

**Details**:
Create reusable test trait for Slack notification mocking and assertions. This is NOT counted as a test file (it's a trait), so it complies with test lock.

---

### T029: Add Slack Notification Tests to StatusfactionReportingE2ETest.php

**Depends on**: T028, T020, T021, T024
**Estimated time**: 45 minutes
**Files modified**:
- `/Users/jprevel/Documents/joy/joy-app/tests/Feature/StatusfactionReportingE2ETest.php`

**Details**:

Add the following test methods to the existing `StatusfactionReportingE2ETest.php` file:

1. **Import trait at top of class**:
```php
use Tests\Traits\SlackNotificationAssertions;

class StatusfactionReportingE2ETest extends TestCase
{
    use RefreshDatabase;
    use SlackNotificationAssertions; // ADD THIS

    // Existing test methods...
}
```

2. **Add test methods**:

```php
/** @test */
public function slack_notification_sent_when_statusfaction_submitted()
{
    $this->mockSlackApiSuccess();
    $this->fakeQueue();

    Role::firstOrCreate(['name' => 'agency']);

    $accountManager = User::factory()->create();
    $accountManager->assignRole('agency');

    $team = Team::factory()->create();
    $accountManager->teams()->attach($team);

    // Create client with Slack integration
    $client = Client::factory()->create([
        'team_id' => $team->id,
        'slack_channel_id' => 'C123456',
        'slack_channel_name' => '#test-channel',
    ]);

    // Create workspace
    \App\Models\SlackWorkspace::factory()->create([
        'is_active' => true,
        'bot_token' => 'xoxb-test-token',
    ]);

    // Submit statusfaction report
    ClientStatusUpdate::factory()->create([
        'client_id' => $client->id,
        'user_id' => $accountManager->id,
        'status_notes' => 'Test status',
        'approval_status' => 'pending_approval',
    ]);

    // Assert job was dispatched
    $this->assertSlackJobDispatched(\App\Jobs\SendStatusfactionSubmittedNotification::class);
}

/** @test */
public function slack_notification_sent_when_statusfaction_approved()
{
    $this->mockSlackApiSuccess();
    $this->fakeQueue();

    Role::firstOrCreate(['name' => 'admin']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create client with Slack integration
    $client = Client::factory()->create([
        'slack_channel_id' => 'C123456',
        'slack_channel_name' => '#test-channel',
    ]);

    // Create workspace
    \App\Models\SlackWorkspace::factory()->create([
        'is_active' => true,
        'bot_token' => 'xoxb-test-token',
    ]);

    // Create pending status
    $status = ClientStatusUpdate::factory()->create([
        'client_id' => $client->id,
        'approval_status' => 'pending_approval',
    ]);

    // Approve status
    $status->update([
        'approval_status' => 'approved',
        'approved_by' => $admin->id,
        'approved_at' => now(),
    ]);

    // Assert job was dispatched
    $this->assertSlackJobDispatched(\App\Jobs\SendStatusfactionApprovedNotification::class);
}

/** @test */
public function slack_notification_not_sent_when_editing_pending_statusfaction()
{
    // CRITICAL TEST: Verify clarification #1 from spec.md - NO notifications on edits
    $this->mockSlackApiSuccess();
    $this->fakeQueue();

    Role::firstOrCreate(['name' => 'agency']);

    $accountManager = User::factory()->create();
    $accountManager->assignRole('agency');

    // Create client with Slack integration
    $client = Client::factory()->create([
        'slack_channel_id' => 'C123456',
        'slack_channel_name' => '#test-channel',
    ]);

    // Create workspace
    \App\Models\SlackWorkspace::factory()->create([
        'is_active' => true,
        'bot_token' => 'xoxb-test-token',
    ]);

    // Create initial status (this WILL dispatch notification)
    $status = ClientStatusUpdate::factory()->create([
        'client_id' => $client->id,
        'user_id' => $accountManager->id,
        'status_notes' => 'Original notes',
        'approval_status' => 'pending_approval',
    ]);

    // Clear queue to reset assertions
    Queue::fake();

    // Edit the pending status
    $status->update([
        'status_notes' => 'Updated notes',
        'client_satisfaction' => 9,
    ]);

    // Assert submission notification was NOT dispatched on edit
    $this->assertSlackJobNotDispatched(\App\Jobs\SendStatusfactionSubmittedNotification::class);
}

/** @test */
public function slack_notification_not_sent_when_client_has_no_slack_integration()
{
    $this->fakeQueue();

    Role::firstOrCreate(['name' => 'agency']);

    $accountManager = User::factory()->create();
    $accountManager->assignRole('agency');

    // Create client WITHOUT Slack integration
    $client = Client::factory()->create([
        'slack_channel_id' => null,
        'slack_channel_name' => null,
    ]);

    // Submit statusfaction report
    ClientStatusUpdate::factory()->create([
        'client_id' => $client->id,
        'user_id' => $accountManager->id,
        'approval_status' => 'pending_approval',
    ]);

    // Assert job was NOT dispatched (no Slack integration)
    $this->assertSlackJobNotDispatched(\App\Jobs\SendStatusfactionSubmittedNotification::class);
}
```

**Verification**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && ./scripts/test-lock.sh
```

**Details**:
Add comprehensive Slack notification tests to existing test file. CRITICAL: Test that NO notifications are sent when editing pending reports (clarification #1 from spec.md). This approach complies with test lock by extending existing test file rather than creating new one.

---

### T030: Manual Testing Checklist

**Depends on**: All previous tasks
**Estimated time**: 60 minutes
**Files**: N/A (manual testing)

**Manual Testing Steps**:

1. **Setup Slack Workspace**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && php artisan tinker
>>> $workspace = \App\Models\SlackWorkspace::factory()->create([
...   'bot_token' => env('SLACK_BOT_TOKEN'),
...   'is_active' => true
... ])
>>> $service = app(\App\Contracts\SlackServiceContract::class)
>>> $service->testConnection()
# Should return ['success' => true]
```

2. **Test Channel Fetching**:
```bash
>>> $service->getChannels()
# Should return list of channels (both public and private)
```

3. **Test Client Comment Notification**:
```bash
# In Filament admin, create client with Slack channel
# As client user (magic link), add comment to content item
# Verify notification appears in Slack channel
```

4. **Test Content Approval Notification**:
```bash
# Approve a content item for client with Slack integration
# Verify approval notification appears in Slack
```

5. **Test Statusfaction Submission**:
```bash
# As account manager, submit new statusfaction report
# Verify submission notification appears in Slack
# Verify satisfaction/health scores are NOT in message (per FR-019, FR-020)
```

6. **Test Statusfaction Edit (NO notification)**:
```bash
# As account manager, edit pending statusfaction report
# Verify NO new notification is sent (per clarification #1)
```

7. **Test Statusfaction Approval**:
```bash
# As admin, approve pending statusfaction report
# Verify approval notification appears in Slack
```

8. **Test Error Handling**:
```bash
# Set invalid Slack channel ID on client
# Trigger notification
# Verify graceful failure (no user-facing error)
# Check slack_notifications table for failed record
```

9. **Test Queue Processing**:
```bash
php artisan queue:work
# Trigger notifications
# Verify jobs process successfully
```

**Verification**:
Create a checklist document or spreadsheet to track manual testing results.

---

## Polish Tasks (T031-T033)

### T031: Run Test Lock Script and Fix Failures

**Depends on**: T029
**Estimated time**: 30 minutes
**Files**: Potentially any test files with failures

**Commands**:
```bash
cd /Users/jprevel/Documents/joy/joy-app && ./scripts/test-lock.sh
```

**Details**:
Run the test lock script to ensure:
1. Test count is still 42 (no new test files created)
2. All tests pass (zero tolerance for failures)

If tests fail:
- Identify root cause
- Fix issues in implementation or tests
- Re-run test lock script
- Repeat until all tests pass

**CRITICAL**: Do NOT proceed to next task until test lock script passes completely.

---

### T032: Verify Error Handling for All Edge Cases

**Depends on**: T030
**Estimated time**: 45 minutes
**Files**: Service implementations

**Edge Cases to Verify** (from spec.md):

1. **Slack API temporarily unavailable**:
   - System logs failure
   - User actions in Joy continue unaffected
   - No retry (per clarification #2)

2. **Slack channel deleted/archived**:
   - System handles gracefully with error logging
   - No notification sent
   - User can still use Joy

3. **Permission errors (private channel)**:
   - System detects permission errors
   - Logs failure
   - Continues without blocking

4. **Special characters in status notes/comments**:
   - System properly escapes for Slack markdown
   - No rendering issues in Slack

5. **Rapid successive notifications**:
   - System sends each independently
   - If rate limited, log failures (no retry)

6. **Client deleted with pending notifications**:
   - Notifications process normally or fail gracefully

7. **Slack token expired/revoked**:
   - System detects auth failures
   - Logs them for admin review

**Verification Steps**:
- Manually test each edge case
- Review logs for appropriate error messages
- Verify graceful degradation (no user-facing errors)
- Document any issues found

---

### T033: Final Documentation Updates

**Depends on**: T031, T032
**Estimated time**: 30 minutes
**Files modified**:
- `/Users/jprevel/Documents/joy/specs/004-i-want-to/IMPLEMENTATION_NOTES.md` (create if needed)

**Details**:

Create implementation notes document summarizing:

1. **What was implemented**:
   - All features from spec.md
   - Any deviations or clarifications applied

2. **Known limitations**:
   - Single workspace support only
   - No retry on failures (by design)
   - No channel caching (by design)

3. **Configuration requirements**:
   - Slack app setup steps
   - Required scopes: `channels:read`, `groups:read`, `chat:write`, `chat:write.public`
   - Environment variables needed

4. **Testing results**:
   - Test lock compliance (42 files maintained)
   - All tests passing
   - Manual testing completed

5. **Deployment checklist**:
   - Migration steps
   - Queue worker configuration
   - Environment variable setup
   - Initial Slack workspace connection

**Verification**:
```bash
cat /Users/jprevel/Documents/joy/specs/004-i-want-to/IMPLEMENTATION_NOTES.md
```

---

## Dependency Graph

```
Setup Tasks (T001-T005)
  └─> T001 (directories) ─> T002, T003, T004 (contracts) [P]
  └─> T005 (env config)

Database Tasks (T006-T009) [P]
  └─> T006, T007, T008 (migrations) [P] ─> T009 (run migrations)

Model Tasks (T010-T013) [P]
  └─> T009 (migrations) ─> T010, T011, T012 (models) [P]
  └─> T010 (SlackWorkspace) ─> T013 (factory) [P]

Service Implementation (T014-T016)
  └─> T002 + T010 ─> T014 (SlackService)
  └─> T004 ─> T015 (SlackBlockFormatter)
  └─> T003 + T011 + T014 + T015 ─> T016 (SlackNotificationService)

Job Tasks (T017-T021) [P]
  └─> T001 + T016 ─> T017, T018, T019, T020, T021 (jobs) [P]

Observer Tasks (T022-T024) [P]
  └─> T001 + T017 ─> T022 (CommentObserver) [P]
  └─> T001 + T018 + T019 ─> T023 (ContentItemObserver) [P]
  └─> T001 + T020 + T021 ─> T024 (ClientStatusUpdateObserver) [P]

Integration Tasks (T025-T027)
  └─> T014 + T015 + T016 + T022 + T023 + T024 ─> T025 (AppServiceProvider)
  └─> T014 ─> T026 (ClientForm)
  └─> T005 ─> T027 (verify config)

Test Tasks (T028-T030)
  └─> T001 ─> T028 (test trait)
  └─> T028 + T020 + T021 + T024 ─> T029 (add tests)
  └─> All tasks ─> T030 (manual testing)

Polish Tasks (T031-T033)
  └─> T029 ─> T031 (test lock)
  └─> T030 ─> T032 (error handling)
  └─> T031 + T032 ─> T033 (documentation)
```

---

## Execution Strategy

### Recommended Execution Order

**Day 1 - Foundation**:
- T001-T005 (Setup)
- T006-T009 (Database)
- T010-T013 (Models)
- T014-T016 (Services)
- Checkpoint: Run test lock script

**Day 2 - Jobs & Observers**:
- T017-T021 (Jobs)
- T022-T024 (Observers)
- T025 (AppServiceProvider)
- Checkpoint: Run test lock script

**Day 3 - Integration & Testing**:
- T026 (ClientForm)
- T027 (Config verification)
- T028 (Test trait)
- T029 (Add tests)
- T031 (Test lock verification)
- Checkpoint: All tests passing

**Day 4 - Polish & Deploy**:
- T030 (Manual testing)
- T032 (Error handling verification)
- T033 (Documentation)
- Final checkpoint: Ready for deployment

---

## Success Criteria

**Feature is complete when**:
- ✅ All 33 tasks completed
- ✅ Test lock maintained (42 test files)
- ✅ All tests passing (100%)
- ✅ Manual testing checklist completed
- ✅ All edge cases verified
- ✅ Documentation complete
- ✅ No user-facing errors
- ✅ All 5 notification types working
- ✅ Graceful error handling confirmed

---

## Notes

- **Test Lock Compliance**: This implementation adds 0 new test files (uses trait + existing test file)
- **No Retries**: Clarification #2 enforced - single attempt only, then log and move on
- **No Cache**: Clarification #5 enforced - channels fetched live from API, no SlackChannelCache table created
- **No Edit Notifications**: Clarification #1 enforced - only initial statusfaction submission triggers notification
- **Public + Private Channels**: Clarification #4 enforced - both channel types supported
- **Direct Links**: Clarification #4 enforced - links go to specific content item, not calendar page

---

**Last Updated**: 2025-10-10
**Total Tasks**: 33
**Estimated Total Time**: 12-16 hours (spread over 3-4 days)
