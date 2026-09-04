<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Information')
                    ->description('Manage the price and benefits shown in Bible Vision.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Plan name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Internal ID')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Example: ad_free, support, extra_sessions'),

                        TextInput::make('price')
                            ->label('Price')
                            ->prefix('$')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),

                        Select::make('billing_period')
                            ->label('Billing')
                            ->options([
                                'monthly' => 'Monthly',
                                'one_time' => 'One-time purchase',
                            ])
                            ->required(),

                        TextInput::make('support_sessions')
                            ->label('Support sessions included')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        TextInput::make('sort_order')
                            ->label('Display order')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Benefits & Availability')
                    ->schema([
                        Toggle::make('removes_ads')
                            ->label('Remove ads'),

                        Toggle::make('is_addon')
                            ->label('Extra-session add-on'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }
}
