<?php

namespace App\Observers;

use App\Models\User;
use App\Models\AuditLog;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
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

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Only log if actual changes occurred
        if ($user->wasChanged()) {
            AuditLog::log([
                'event' => 'User Updated',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'user_id' => auth()->id(),
                'old_values' => $user->getOriginal(),
                'new_values' => $user->getChanges(),
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
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
                'role' => $user->roles->first()?->name,
            ],
        ]);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        AuditLog::log([
            'event' => 'User Restored',
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
}
