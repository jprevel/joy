# Quickstart Guide: Admin Section Refresh Development

**Feature**: 005-the-admin-section
**Branch**: `005-the-admin-section`
**Date**: 2025-10-11

## Prerequisites

Before starting development on the admin section refresh, ensure you have:

- [x] PHP 8.2+ installed
- [x] Composer installed
- [x] PostgreSQL running locally
- [x] Node.js and npm installed (for Vite/Tailwind)
- [x] Git repository cloned
- [x] Feature branch checked out: `005-the-admin-section`

## Environment Setup

### 1. Database Configuration

Ensure your `.env` file has the test database configured:

```env
# Main database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=joy
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Test database (locked test suite)
DB_TEST_CONNECTION=pgsql_testing
DB_TEST_HOST=127.0.0.1
DB_TEST_PORT=5432
DB_TEST_DATABASE=joy_testing
DB_TEST_USERNAME=your_username
DB_TEST_PASSWORD=your_password
```

### 2. Install Dependencies

```bash
cd joy-app
composer install
npm install
```

### 3. Run Existing Migrations

```bash
php artisan migrate
php artisan migrate --database=pgsql_testing  # Test database
```

### 4. Seed Development Data

```bash
php artisan db:seed
```

## Development Workflow

### Phase Execution Order

Follow this order to implement the admin section refresh:

**Phase 0**: ✅ Research (Complete) - `research.md` created
**Phase 1**: ✅ Design (Complete) - Schema, contracts, this guide created
**Phase 2**: 🔄 Implementation (Follow tasks.md when generated)

### Test-First Development (TDD)

**CRITICAL**: This project follows strict TDD. Always write tests FIRST.

#### Test Lock Constraint

- ❌ **NO NEW TEST FILES**: Test suite is locked at 42 files
- ✅ **EXTEND EXISTING FILES**: Add test methods to existing test classes
- ✅ **ALL TESTS MUST PASS**: Zero tolerance for failing tests

#### Test File Locations

Add tests to these existing files:

```bash
# User CRUD tests
tests/Feature/AdminContentManagementE2ETest.php

# Client CRUD tests
tests/Feature/AdminContentManagementE2ETest.php

# Audit UI tests
tests/Feature/AuditLogViewerTest.php

# Audit event tests
tests/Feature/StatusfactionReportingE2ETest.php
```

#### TDD Cycle

For each task, follow Red-Green-Refactor:

```bash
# 1. Write failing test
vim tests/Feature/AdminContentManagementE2ETest.php

# 2. Run test (should fail)
php artisan test --filter=test_admin_can_create_user

# 3. Implement minimum code to pass
vim app/Livewire/Admin/UserManagement.php

# 4. Run test again (should pass)
php artisan test --filter=test_admin_can_create_user

# 5. Run full test suite (must all pass)
php artisan test

# 6. Refactor if needed, keep tests passing
```

## Running the Test Suite

### Full Test Suite

```bash
php artisan test
```

**Expected Result**: All 42 test files pass, X tests total (varies as tests are added)

### Specific Test File

```bash
php artisan test tests/Feature/AdminContentManagementE2ETest.php
```

### Specific Test Method

```bash
php artisan test --filter=test_admin_can_create_user
```

### Test Lock Verification Script

Before committing, always run:

```bash
./scripts/test-lock.sh
```

This script:
- Counts test files (must be exactly 42)
- Runs full test suite
- Exits with error if either check fails

## Database Migrations for This Feature

### Create Migration Files

```bash
# User soft deletes migration
php artisan make:migration add_soft_deletes_to_users_table --table=users

# Client soft deletes migration
php artisan make:migration add_soft_deletes_to_clients_table --table=clients
```

### Run Feature Migrations

```bash
# Run both migrations
php artisan migrate

# Test database
php artisan migrate --database=pgsql_testing
```

### Rollback if Needed

```bash
php artisan migrate:rollback --step=2
```

## Livewire Component Development

### Create New Livewire Component

```bash
# User management component (if doesn't exist)
php artisan make:livewire Admin/UserManagement
```

This generates:
- `app/Livewire/Admin/UserManagement.php` (component class)
- `resources/views/livewire/admin/user-management.blade.php` (view)

### Hot Reload During Development

```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Vite dev server (for Tailwind/Alpine.js)
npm run dev
```

Access at: http://127.0.0.1:8000

### Livewire Testing Pattern

```php
use Livewire\Livewire;
use App\Livewire\Admin\UserManagement;

public function test_admin_can_create_user()
{
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->set('form.name', 'New User')
        ->set('form.email', 'newuser@example.com')
        ->set('form.password', 'password123')
        ->set('form.role', 'Agency Team')
        ->call('createUser')
        ->assertHasNoErrors()
        ->assertDispatched('user-created');

    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'User Created',
        'auditable_type' => User::class,
    ]);
}
```

## Model Changes

### Adding SoftDeletes Trait

```php
// app/Models/User.php
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'deleted_at' => 'datetime', // Add this
    ];
}
```

### Testing Soft Deletes

```php
public function test_user_soft_delete_preserves_content()
{
    $user = User::factory()->create();
    $content = ContentItem::factory()->for($user)->create();

    $user->delete(); // Soft delete

    $this->assertSoftDeleted('users', ['id' => $user->id]);
    $this->assertDatabaseHas('content_items', ['user_id' => $user->id]);

    // Verify user cannot log in
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password'
    ])->assertRedirect('/login');
}
```

## Observer Implementation

### Create Observer

```bash
php artisan make:observer UserObserver --model=User
```

### Register Observer

```php
// app/Providers/AppServiceProvider.php
use App\Models\User;
use App\Observers\UserObserver;

public function boot(): void
{
    User::observe(UserObserver::class);
}
```

### Observer Pattern for Audit Logging

```php
// app/Observers/UserObserver.php
namespace App\Observers;

use App\Models\User;
use App\Models\AuditLog;

class UserObserver
{
    public function created(User $user): void
    {
        AuditLog::log([
            'event' => 'User Created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => auth()->id(),
            'new_values' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
            ],
        ]);
    }

    public function updated(User $user): void
    {
        AuditLog::log([
            'event' => 'User Updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => auth()->id(),
            'old_values' => $user->getOriginal(),
            'new_values' => $user->getChanges(),
        ]);
    }

    public function deleted(User $user): void
    {
        AuditLog::log([
            'event' => 'User Deleted',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => auth()->id(),
            'old_values' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
```

## Service Implementation

### Create Service Class

```bash
mkdir -p app/Services
touch app/Services/UserManagementService.php
```

### Implement Contract

```php
// app/Services/UserManagementService.php
namespace App\Services;

use App\Contracts\UserManagementContract;
use App\Models\User;

class UserManagementService implements UserManagementContract
{
    public function listUsers(int $perPage = 15, bool $includeTrashed = true)
    {
        $query = $includeTrashed ? User::withTrashed() : User::query();
        return $query->with('roles')->paginate($perPage);
    }

    public function createUser(array $data): User
    {
        $validated = $this->validateUserData($data);
        $user = User::create($validated);
        $user->assignRole($validated['role']);
        return $user;
    }

    public function updateUser(int $userId, array $data): User
    {
        $validated = $this->validateUserData($data, $userId);
        $user = $this->findUser($userId);

        // Only update password if provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return $user;
    }

    // ... implement remaining methods
}
```

### Register in AppServiceProvider

```php
// app/Providers/AppServiceProvider.php
use App\Contracts\UserManagementContract;
use App\Services\UserManagementService;

public function register(): void
{
    $this->app->bind(
        UserManagementContract::class,
        UserManagementService::class
    );
}
```

## Frontend Development

### Blade Template Structure

```blade
{{-- resources/views/livewire/admin/user-management.blade.php --}}
<div>
    <!-- User List -->
    <div class="mb-6">
        <button wire:click="$set('showCreateForm', true)"
                class="px-4 py-2 bg-blue-600 text-white rounded-md">
            Create User
        </button>
    </div>

    <!-- Create/Edit Form -->
    @if($showCreateForm || $showEditForm)
        <form wire:submit="saveUser">
            <input wire:model="form.name" type="text" />
            <input wire:model="form.email" type="email" />
            {{-- Password field: required for create, optional for edit --}}
            <input wire:model="form.password" type="password"
                   placeholder="{{ $showEditForm ? 'Leave blank to keep current password' : 'Password' }}" />
            <select wire:model="form.role">
                <option value="Admin">Admin</option>
                <option value="Agency Team">Agency Team</option>
                <option value="Client">Client</option>
            </select>
            <button type="submit">Save</button>
        </form>
    @endif

    <!-- User Table -->
    <table class="min-w-full">
        @foreach($users as $user)
            <tr class="{{ $user->trashed() ? 'bg-gray-100' : '' }}">
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    @if($user->trashed())
                        <span class="text-red-600">Deleted</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
</div>
```

### Alpine.js for Collapsible Filters

```blade
{{-- Collapsible filter form --}}
<div x-data="{ filtersOpen: false }">
    <button @click="filtersOpen = !filtersOpen"
            class="px-4 py-2 bg-gray-200 rounded-md">
        <span x-text="filtersOpen ? 'Hide Filters' : 'Show Filters'"></span>
        @if(array_filter($filters))
            <span class="ml-2 text-blue-600">({{ count(array_filter($filters)) }} active)</span>
        @endif
    </button>

    <div x-show="filtersOpen"
         x-transition
         class="mt-4 grid grid-cols-3 gap-4">
        <select wire:model.live="filters.event">
            <option value="">All Events</option>
            @foreach($eventTypes as $event)
                <option value="{{ $event }}">{{ $event }}</option>
            @endforeach
        </select>
        <!-- More filters -->
    </div>
</div>
```

## Debugging Tips

### Livewire Debugging

```php
// In Livewire component
dd($this->form); // Dump form data
logger('User created', ['user_id' => $user->id]); // Log to laravel.log
```

### Database Query Logging

```php
// Enable query logging
DB::enableQueryLog();

// Your code that queries database

// Dump queries
dd(DB::getQueryLog());
```

### Test Debugging

```bash
# Run tests with verbose output
php artisan test --verbose

# Run specific test with stack trace
php artisan test --filter=test_name --testdox
```

## Common Issues & Solutions

### Issue: Test Lock Script Fails

**Problem**: `./scripts/test-lock.sh` reports wrong test file count

**Solution**:
```bash
# Count test files manually
find tests -name "*Test.php" | wc -l

# Should be exactly 42
# If not, check for accidentally created test files
```

### Issue: Soft Deleted Users Can Still Log In

**Problem**: Authentication doesn't check `deleted_at`

**Solution**: Add middleware to auth routes:
```php
// In auth middleware
if ($user->trashed()) {
    Auth::logout();
    return redirect('/login')->with('error', 'Account deleted');
}
```

### Issue: Audit Events Not Logging

**Problem**: Observer not firing

**Solution**: Verify observer registration:
```bash
php artisan tinker
>>> User::getObservableEvents()
# Should include 'created', 'updated', 'deleted'
```

### Issue: Slack Channels Not Loading

**Problem**: SlackService not configured

**Solution**: Check Slack workspace exists:
```bash
php artisan tinker
>>> App\Models\SlackWorkspace::first()
# Should return workspace instance
```

## Next Steps After Quickstart

1. ✅ Review `research.md` to understand existing patterns
2. ✅ Review `data-model.md` to understand schema changes
3. ✅ Review `contracts/` to understand service interfaces
4. ⏳ Wait for `/tasks` command to generate `tasks.md`
5. 🔄 Implement tasks following TDD workflow (Red-Green-Refactor)
6. ✅ Keep test lock at 42 files
7. ✅ Ensure all tests pass before committing

## Useful Commands Reference

```bash
# Start dev environment
php artisan serve
npm run dev

# Run tests
php artisan test
./scripts/test-lock.sh

# Database
php artisan migrate
php artisan migrate:fresh --seed  # Reset database

# Create components
php artisan make:livewire Admin/ComponentName
php artisan make:observer UserObserver --model=User
php artisan make:migration add_column_to_table

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

**Status**: ✅ Ready for Task Implementation
**Next Command**: `/tasks` (generates tasks.md from this plan)
