<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Models\Booking;
use App\Models\SessionCreditTransaction;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('language')
                    ->label('Language')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'fr' => 'French',
                        'ht' => 'Haitian Creole',
                        default => 'English',
                    })
                    ->badge(),

                TextColumn::make('session_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('session_time')
                    ->label('Time'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),

                TextColumn::make('zoom_link')
                    ->label('Zoom')
                    ->formatStateUsing(
                        fn (?string $state): string => $state ? 'Open Zoom' : 'Not added'
                    )
                    ->url(fn (Booking $record): ?string => $record->zoom_link)
                    ->openUrlInNewTab(),

                TextColumn::make('session_credit_used')
                    ->label('Credit used')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('language')
                    ->options([
                        'en' => 'English',
                        'fr' => 'French',
                        'ht' => 'Haitian Creole',
                    ]),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Booking $record): bool =>
                        $record->status === 'pending'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Confirm this booking?')
                    ->modalDescription(
                        'Make sure the date and time are correct. You can add the Zoom link before or after confirming.'
                    )
                    ->action(function (Booking $record): void {
                        $record->update([
                            'status' => 'confirmed',
                        ]);

                        Notification::make()
                            ->title('Booking confirmed')
                            ->success()
                            ->send();
                    }),

                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (Booking $record): bool =>
                        $record->status === 'confirmed'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark this session as completed?')
                    ->modalDescription(
                        'This will mark the session as completed and deduct exactly 1 support-session credit from the customer.'
                    )
                    ->action(function (Booking $record): void {
                        $result = DB::transaction(function () use ($record): array {
                            /** @var Booking $booking */
                            $booking = Booking::query()
                                ->whereKey($record->getKey())
                                ->lockForUpdate()
                                ->firstOrFail();

                            if ($booking->session_credit_used || $booking->status === 'completed') {
                                return [
                                    'ok' => false,
                                    'message' => 'This booking has already used its session credit.',
                                ];
                            }

                            if ($booking->status !== 'confirmed') {
                                return [
                                    'ok' => false,
                                    'message' => 'Only a confirmed booking can be completed.',
                                ];
                            }

                            if (! $booking->user_id) {
                                return [
                                    'ok' => false,
                                    'message' => 'This booking is not linked to a customer account yet.',
                                ];
                            }

                            /** @var Subscription|null $subscription */
                            $subscription = Subscription::query()
                                ->where('user_id', $booking->user_id)
                                ->where('status', 'active')
                                ->where('sessions_remaining', '>', 0)
                                ->latest('id')
                                ->lockForUpdate()
                                ->first();

                            if (! $subscription) {
                                return [
                                    'ok' => false,
                                    'message' => 'The customer has no active support-session credit available.',
                                ];
                            }

                            $newBalance = $subscription->sessions_remaining - 1;

                            $subscription->update([
                                'sessions_remaining' => $newBalance,
                            ]);

                            $booking->update([
                                'status' => 'completed',
                                'session_credit_used' => true,
                            ]);

                            SessionCreditTransaction::create([
                                'user_id' => $booking->user_id,
                                'subscription_id' => $subscription->id,
                                'booking_id' => $booking->id,
                                'plan_id' => $subscription->plan_id,
                                'amount' => -1,
                                'type' => 'booking_completed',
                                'description' => 'Support session completed',
                                'balance_after' => $newBalance,
                            ]);

                            return [
                                'ok' => true,
                                'balance' => $newBalance,
                            ];
                        });

                        if (! $result['ok']) {
                            Notification::make()
                                ->title('Session not completed')
                                ->body($result['message'])
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Session completed')
                            ->body("1 credit deducted. {$result['balance']} session(s) remaining.")
                            ->success()
                            ->send();
                    }),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Booking $record): bool =>
                        ! in_array($record->status, ['completed', 'cancelled'])
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Cancel this booking?')
                    ->action(function (Booking $record): void {
                        $record->update([
                            'status' => 'cancelled',
                        ]);

                        Notification::make()
                            ->title('Booking cancelled')
                            ->warning()
                            ->send();
                    }),

                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->defaultSort('session_date', 'asc');
    }
}
