<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->disabled(fn ($record) => $record && $record->name === 'admin'),
                Hidden::make('guard_name')
                    ->default('web'),
                CheckboxList::make('permission_ids')
                    ->label('Permissions')
                    ->options(\Spatie\Permission\Models\Permission::pluck('name', 'id'))
                    ->columns(2)
                    ->searchable()
                    ->bulkToggleable()
                    ->gridDirection('row')
                    ->columnSpanFull()
                    ->disabled(fn ($record) => $record && $record->name === 'admin')
                    ->default(function ($record) {
                        return $record ? $record->permissions->pluck('id')->toArray() : [];
                    }),
            ]);
    }
}
