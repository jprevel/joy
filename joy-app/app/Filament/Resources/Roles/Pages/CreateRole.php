<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove permission_ids from the data so it doesn't get saved to the role model
        $this->permissionIds = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Sync permissions after the role is created
        if (isset($this->permissionIds) && is_array($this->permissionIds)) {
            $this->record->permissions()->sync($this->permissionIds);
        }
    }

    private $permissionIds;
}
