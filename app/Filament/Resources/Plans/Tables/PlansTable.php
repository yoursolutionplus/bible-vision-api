<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('billing_period')
                    ->label('Billing')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'one_time' => 'One-time',
                        default => 'Monthly',
                    })
                    ->badge(),

                TextColumn::make('support_sessions')
                    ->label('Sessions')
                    ->sortable(),

                IconColumn::make('removes_ads')
                    ->label('No Ads')
                    ->boolean(),

                IconColumn::make('is_addon')
                    ->label('Add-on')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
