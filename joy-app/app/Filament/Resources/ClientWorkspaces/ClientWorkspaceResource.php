<?php

namespace App\Filament\Resources\ClientWorkspaces;

use App\Filament\Resources\ClientWorkspaces\Pages\CreateClientWorkspace;
use App\Filament\Resources\ClientWorkspaces\Pages\EditClientWorkspace;
use App\Filament\Resources\ClientWorkspaces\Pages\ListClientWorkspaces;
use App\Filament\Resources\ClientWorkspaces\Schemas\ClientWorkspaceForm;
use App\Filament\Resources\ClientWorkspaces\Tables\ClientWorkspacesTable;
use App\Models\ClientWorkspace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClientWorkspaceResource extends Resource
{
    protected static ?string $model = ClientWorkspace::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ClientWorkspaceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientWorkspacesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientWorkspaces::route('/'),
            'create' => CreateClientWorkspace::route('/create'),
            'edit' => EditClientWorkspace::route('/{record}/edit'),
        ];
    }
}
