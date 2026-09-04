<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Information')
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Full name'),

                        TextEntry::make('email'),

                        TextEntry::make('phone')
                            ->placeholder('No phone number'),

                        TextEntry::make('language')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'fr' => 'Français',
                                'ht' => 'Kreyòl Ayisyen',
                                default => 'English',
                            }),
                    ])
                    ->columns(2),

                Section::make('Session Information')
                    ->schema([
                        TextEntry::make('session_date')
                            ->label('Session date')
                            ->date('M d, Y'),

                        TextEntry::make('session_time')
                            ->label('Session time'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'confirmed' => 'success',
                                'completed' => 'info',
                                'cancelled' => 'danger',
                                default => 'warning',
                            }),

                        TextEntry::make('zoom_link')
                            ->label('Zoom link')
                            ->url(fn ($record) => $record->zoom_link)
                            ->openUrlInNewTab()
                            ->placeholder('No Zoom link yet'),

                        TextEntry::make('note')
                            ->label('Client note')
                            ->placeholder('No note')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
