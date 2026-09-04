<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription')
                    ->schema([
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('plan_id')
                            ->label('Plan')
                            ->relationship('plan', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'paused' => 'Paused',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ])
                            ->default('active')
                            ->required(),

                        TextInput::make('sessions_remaining')
                            ->label('Sessions remaining')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Dates')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Starts at'),

                        DateTimePicker::make('renews_at')
                            ->label('Renews at'),

                        DateTimePicker::make('ends_at')
                            ->label('Ends at'),
                    ])
                    ->columns(3),

                Section::make('Payment')
                    ->schema([
                        Select::make('payment_provider')
                            ->label('Payment provider')
                            ->options([
                                'apple' => 'Apple App Store',
                                'google' => 'Google Play',
                                'manual' => 'Manual',
                            ])
                            ->placeholder('Not connected yet'),

                        TextInput::make('provider_subscription_id')
                            ->label('Provider subscription ID')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}
