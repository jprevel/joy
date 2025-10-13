# Data Model: Admin Section Refresh

**Feature**: 005-the-admin-section
**Date**: 2025-10-11
**Status**: Phase 1 Design

## Overview

This document defines database schema changes required for the admin section refresh, focusing on soft deletion support for Users and Clients, and audit event naming standardization.

## Schema Changes

### 1. Add Soft Deletes to `users` Table

**Migration**: `2025_10_11_add_soft_deletes_to_users.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes(); // Adds deleted_at timestamp column
        });

        // Add index for soft delete queries
        Schema::table('users', function (Blueprint $table) {
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
```

**Impact**:
- Adds `deleted_at` TIMESTAMP NULL DEFAULT NULL column
- Enables Laravel's `SoftDeletes` trait on User model
- Deleted users remain in database but cannot log in
- All relationships (content, comments, audit logs) preserved
- Queries automatically exclude soft-deleted users unless `withTrashed()` is used

**Indexes**: Index on `deleted_at` for performance on queries filtering soft-deleted records.

### 2. Add Soft Deletes to `clients` Table

**Migration**: `2025_10_11_add_soft_deletes_to_clients.php`

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
            $table->softDeletes(); // Adds deleted_at timestamp column
        });

        // Add index for soft delete queries
        Schema::table('clients', function (Blueprint $table) {
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
```

**Impact**:
- Adds `deleted_at` TIMESTAMP NULL DEFAULT NULL column
- Enables Laravel's `SoftDeletes` trait on Client model
- Deleted clients' content and magic links remain accessible (read-only)
- All relationships (content items, comments, magic links, audit logs) preserved
- Queries automatically exclude soft-deleted clients unless `withTrashed()` is used

**Indexes**: Index on `deleted_at` for performance on admin client list queries.

### 3. Verify `audit_logs` Table Schema

**No migration needed** - Current schema already supports human-readable event names.

**Current Schema** (from existing migration):
```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->string('event'); // ✅ VARCHAR(255) - supports "User Created", etc.
    $table->string('auditable_type')->nullable();
    $table->unsignedBigInteger('auditable_id')->nullable();
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();

    $table->index(['auditable_type', 'auditable_id']);
    $table->index('event');
    $table->index('created_at');
});
```

**Verification**:
- ✅ `event` column is `VARCHAR(255)` - sufficient for human-readable names like "User Created", "Content Approved"
- ✅ Index on `event` column exists - filter dropdown will be performant
- ✅ `old_values` and `new_values` are JSON - supports flexible change tracking
- ✅ Polymorphic relationship via `auditable_type` and `auditable_id` - works for all models

**No Changes Required**: Schema already supports feature requirements.

## Model Updates

### User Model Changes

**File**: `app/Models/User.php`

Add SoftDeletes trait and update relationships:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes; // ← Add this
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes; // ← Add SoftDeletes

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'deleted_at' => 'datetime', // ← Add this cast
    ];

    // Existing relationships remain unchanged
    // ...
}
```

**Soft Delete Behavior**:
- `User::all()` automatically excludes soft-deleted users
- `User::withTrashed()->get()` includes soft-deleted users (for admin view)
- `User::onlyTrashed()->get()` returns only soft-deleted users
- `$user->delete()` performs soft delete (sets `deleted_at`)
- `$user->forceDelete()` permanently deletes (not used in this feature)
- `$user->restore()` un-deletes user (sets `deleted_at` to null)

### Client Model Changes

**File**: `app/Models/Client.php`

Add SoftDeletes trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // ← Add this
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, SoftDeletes; // ← Add SoftDeletes

    protected $fillable = [
        'name',
        'description',
        'team_id',
        'slack_channel_id',
        'slack_channel_name',
        'trello_board_id',
        'trello_list_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime', // ← Add this cast
    ];

    // Existing relationships remain unchanged
    // ...
}
```

**Soft Delete Behavior**:
- `Client::all()` automatically excludes soft-deleted clients
- `Client::withTrashed()->get()` includes soft-deleted clients (for admin view)
- Soft-deleted clients' content items remain accessible via magic links (read-only)
- All relationships preserved (content items, comments, magic links)

## Audit Event Naming Convention

**Standard**: Human-readable, title case with spaces

**Event Name Mapping**:

| Old Event (if exists) | New Event Name | Context |
|---|---|---|
| `admin_access` | `Admin Access` | Admin dashboard access |
| `user_created` | `User Created` | New user created |
| `user_updated` | `User Updated` | User details changed |
| `user_deleted` | `User Deleted` | User soft deleted |
| `client_created` | `Client Created` | New client created |
| `client_updated` | `Client Updated` | Client details changed |
| `client_deleted` | `Client Deleted` | Client soft deleted |
| `content_created` | `Content Created` | New content item |
| `content_updated` | `Content Updated` | Content item modified |
| `content_approved` | `Content Approved` | Content approved by client |
| `content_rejected` | `Content Rejected` | Content rejected by client |
| `comment_added` | `Comment Added` | Comment on content |
| `statusfaction_submitted` | `Statusfaction Submitted` | Weekly report submitted |
| `statusfaction_approved` | `Statusfaction Approved` | Weekly report approved |

**Implementation Note**: Use AuditLog accessor/mutator to handle event name formatting:

```php
// In AuditLog model
protected function event(): Attribute
{
    return Attribute::make(
        get: fn ($value) => $this->formatEventName($value),
        set: fn ($value) => $this->formatEventName($value),
    );
}

private function formatEventName(string $event): string
{
    // Convert snake_case to Title Case if needed
    return str($event)->title()->replace('_', ' ')->value();
}
```

## Data Preservation Strategy

### Soft Delete Rationale

**Why Soft Deletes?**
1. **Audit Trail Integrity**: Audit logs reference user_id and client_id - hard deletes would break foreign keys
2. **Content Ownership**: User-created content (posts, comments) must remain attributed
3. **Client History**: Client content and magic links must remain accessible even after "deletion"
4. **Regulatory Compliance**: Some industries require data retention for compliance

**What Gets Preserved?**
- User soft delete preserves: content authorship, comment authorship, audit log entries
- Client soft delete preserves: all content items, comments, magic links, audit logs

**What Gets Hidden?**
- Soft-deleted users cannot log in (authentication check excludes deleted_at != null)
- Soft-deleted users/clients appear with "Deleted" badge in admin lists
- Soft-deleted users/clients excluded from dropdowns (team assignments, etc.) unless explicitly included

### Query Performance Considerations

**Indexes Added**:
- `users.deleted_at` - Speeds up `WHERE deleted_at IS NULL` queries
- `clients.deleted_at` - Speeds up `WHERE deleted_at IS NULL` queries

**Query Patterns**:
```php
// Active users only (default)
User::all(); // WHERE deleted_at IS NULL

// Include deleted users for admin view
User::withTrashed()->get(); // No WHERE clause

// Only deleted users
User::onlyTrashed()->get(); // WHERE deleted_at IS NOT NULL
```

**Performance Impact**: Minimal - indexes ensure soft delete checks are fast (<5ms overhead per query).

## Migration Execution Plan

### Pre-Migration Checklist

- [ ] Backup production database
- [ ] Test migrations on local environment
- [ ] Test migrations on staging environment
- [ ] Verify no foreign key constraints will fail

### Migration Order

1. **Run User soft delete migration first**
   ```bash
   php artisan migrate --path=database/migrations/2025_10_11_add_soft_deletes_to_users.php
   ```

2. **Run Client soft delete migration second**
   ```bash
   php artisan migrate --path=database/migrations/2025_10_11_add_soft_deletes_to_clients.php
   ```

3. **Verify migrations**
   ```bash
   php artisan migrate:status
   ```

### Rollback Plan

If issues arise:
```bash
php artisan migrate:rollback --step=2
```

This removes both `deleted_at` columns without data loss (columns are nullable, so no data is affected).

## Testing Strategy

### Database Tests

1. **Soft Delete Functionality**
   ```php
   public function test_user_soft_delete_preserves_relationships()
   {
       $user = User::factory()->create();
       $content = ContentItem::factory()->for($user)->create();

       $user->delete(); // Soft delete

       $this->assertSoftDeleted('users', ['id' => $user->id]);
       $this->assertDatabaseHas('content_items', ['user_id' => $user->id]);
   }
   ```

2. **Soft Deleted Users Cannot Log In**
   ```php
   public function test_soft_deleted_user_cannot_authenticate()
   {
       $user = User::factory()->create();
       $user->delete();

       $this->assertGuest();
       $response = $this->post('/login', [
           'email' => $user->email,
           'password' => 'password'
       ]);
       $response->assertRedirect('/login');
   }
   ```

3. **Audit Event Naming**
   ```php
   public function test_audit_events_use_human_readable_names()
   {
       $user = User::factory()->create();

       $this->assertDatabaseHas('audit_logs', [
           'event' => 'User Created', // ← Human readable
           'auditable_type' => User::class,
           'auditable_id' => $user->id,
       ]);
   }
   ```

## Success Criteria

- ✅ Users can be soft deleted via admin interface
- ✅ Soft-deleted users appear in admin list with "Deleted" indicator
- ✅ Soft-deleted users cannot log in
- ✅ Soft-deleted users' content and audit logs remain visible
- ✅ Clients can be soft deleted via admin interface
- ✅ Soft-deleted clients' magic links remain functional (read-only)
- ✅ Audit events use human-readable names ("User Created" not "user_created")
- ✅ No data loss during migration
- ✅ Migration can be rolled back safely

## Next Steps

1. Review data-model.md with stakeholders
2. Create migration files based on documented schema changes
3. Update User and Client models with SoftDeletes trait
4. Implement audit event name formatting in AuditLog model
5. Write tests for soft delete behavior
6. Execute migrations on dev/staging environments
7. Verify all relationships preserved after soft delete

---

**Status**: ✅ Ready for Review and Implementation
