<?php

namespace App\Filament\Resources\ClientWorkspaces\Pages;

use App\Filament\Resources\ClientWorkspaces\ClientWorkspaceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientWorkspaces extends ListRecords
{
    protected static string $resource = ClientWorkspaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
