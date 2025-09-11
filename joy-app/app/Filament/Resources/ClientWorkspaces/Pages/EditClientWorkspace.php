<?php

namespace App\Filament\Resources\ClientWorkspaces\Pages;

use App\Filament\Resources\ClientWorkspaces\ClientWorkspaceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClientWorkspace extends EditRecord
{
    protected static string $resource = ClientWorkspaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
