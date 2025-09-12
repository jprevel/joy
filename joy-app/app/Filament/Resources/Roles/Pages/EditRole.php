<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Add current permissions to the form data
        $data['permission_ids'] = $this->record->permissions->pluck('id')->toArray();
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove permission_ids from the data so it doesn't get saved to the role model
        $this->permissionIds = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Sync permissions after the role is saved
        if (isset($this->permissionIds) && is_array($this->permissionIds)) {
            $this->record->permissions()->sync($this->permissionIds);
        }
    }

    private $permissionIds;
}
