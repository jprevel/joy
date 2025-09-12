<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use App\Models\Team;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->columnSpanFull(),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->placeholder('Select a role'),
                Select::make('teams')
                    ->relationship('teams', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                DateTimePicker::make('email_verified_at')
                    ->columnSpanFull(),
            ]);
    }
}
