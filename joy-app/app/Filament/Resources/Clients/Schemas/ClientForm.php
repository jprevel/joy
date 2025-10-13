<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Contracts\SlackServiceContract;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('team_id')
                    ->relationship('team', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                // Slack Integration
                Select::make('slack_channel_id')
                    ->label('Slack Channel')
                    ->helperText('Select the Slack channel for client notifications')
                    ->searchable()
                    ->options(function (SlackServiceContract $slackService) {
                        // Fetch channels live from API (no cache per clarification #5)
                        $result = $slackService->getChannels(includeArchived: false, includePrivate: true);

                        if (!$result['success']) {
                            // Log error and return empty array
                            Log::warning('Failed to fetch Slack channels for dropdown', [
                                'error' => $result['error'] ?? 'Unknown error',
                            ]);
                            return [];
                        }

                        // Map channels to [id => name] array
                        return collect($result['channels'] ?? [])
                            ->mapWithKeys(fn($channel) => [$channel['id'] => '#' . $channel['name']])
                            ->toArray();
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Update channel_name when channel_id is selected
                        if ($state) {
                            $slackService = app(SlackServiceContract::class);
                            $result = $slackService->getChannels(includeArchived: false, includePrivate: true);

                            if ($result['success']) {
                                $channel = collect($result['channels'] ?? [])
                                    ->firstWhere('id', $state);

                                if ($channel) {
                                    $set('slack_channel_name', '#' . $channel['name']);
                                }
                            }
                        }
                    })
                    ->nullable()
                    ->columnSpanFull(),

                // Hidden field to store channel name
                Hidden::make('slack_channel_name'),
            ]);
    }
}
