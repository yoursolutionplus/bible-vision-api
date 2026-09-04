<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Information')
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Full name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),

                        Select::make('language')
                            ->options([
                                'en' => 'English',
                                'fr' => 'Français',
                                'ht' => 'Kreyòl Ayisyen',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Session')
                    ->schema([
                        DatePicker::make('session_date')
                            ->label('Session date')
                            ->required(),

                        TimePicker::make('session_time')
                            ->label('Session time')
                            ->seconds(false)
                            ->required(),

                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),

                        TextInput::make('zoom_link')
                            ->label('Zoom link')
                            ->url()
                            ->placeholder('https://zoom.us/j/...'),

                        Textarea::make('note')
                            ->label('Client note')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
