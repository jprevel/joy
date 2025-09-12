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
                CheckboxList::make('permissions')
                    ->relationship('permissions', 'name')
                    ->columns(2)
                    ->searchable()
                    ->bulkToggleable()
                    ->gridDirection('row')
                    ->columnSpanFull()
                    ->disabled(fn ($record) => $record && $record->name === 'admin'),
            ]);
    }
}
