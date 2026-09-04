<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Models\Plan;
use App\Models\SessionCreditTransaction;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'paused' => 'warning',
                        'cancelled' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('sessions_remaining')
                    ->label('Sessions left')
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Started')
                    ->dateTime('M d, Y')
                    ->sortable(),

                TextColumn::make('renews_at')
                    ->label('Renews')
                    ->dateTime('M d, Y')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('payment_provider')
                    ->label('Provider')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'apple' => 'Apple',
                        'google' => 'Google',
                        'manual' => 'Manual',
                        default => 'Not connected',
                    })
                    ->badge(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),

                SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name'),
            ])
            ->recordActions([
                Action::make('add_extra_sessions')
                    ->label('Record Extra Sessions Purchase')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(function (Subscription $record): bool {
                        return $record->status === 'active'
                            && $record->sessions_remaining === 0
                            && $record->plan?->slug === 'support';
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Record extra-session purchase?')
                    ->modalDescription(function (): string {
                        $addon = Plan::query()
                            ->where('slug', 'extra_sessions')
                            ->where('is_active', true)
                            ->first();

                        if (! $addon) {
                            return 'The Extra Sessions add-on is not active.';
                        }

                        return sprintf(
                            'Confirm that the customer has paid $%s. This will add %d support sessions.',
                            number_format((float) $addon->price, 2),
                            (int) $addon->support_sessions,
                        );
                    })
                    ->action(function (Subscription $record): void {
                        $result = DB::transaction(function () use ($record): array {
                            /** @var Subscription $subscription */
                            $subscription = Subscription::query()
                                ->whereKey($record->getKey())
                                ->lockForUpdate()
                                ->firstOrFail();

                            if ($subscription->status !== 'active') {
                                return [
                                    'ok' => false,
                                    'message' => 'This subscription is not active.',
                                ];
                            }

                            if ($subscription->plan?->slug !== 'support') {
                                return [
                                    'ok' => false,
                                    'message' => 'Extra support sessions are only available for the Support plan.',
                                ];
                            }

                            if ($subscription->sessions_remaining > 0) {
                                return [
                                    'ok' => false,
                                    'message' => 'The customer still has support sessions remaining.',
                                ];
                            }

                            /** @var Plan|null $addon */
                            $addon = Plan::query()
                                ->where('slug', 'extra_sessions')
                                ->where('is_active', true)
                                ->first();

                            if (! $addon) {
                                return [
                                    'ok' => false,
                                    'message' => 'The Extra Sessions add-on is not active.',
                                ];
                            }

                            $creditsToAdd = (int) $addon->support_sessions;

                            if ($creditsToAdd <= 0) {
                                return [
                                    'ok' => false,
                                    'message' => 'The Extra Sessions add-on has no session credits configured.',
                                ];
                            }

                            $newBalance = $subscription->sessions_remaining + $creditsToAdd;

                            $subscription->update([
                                'sessions_remaining' => $newBalance,
                            ]);

                            SessionCreditTransaction::create([
                                'user_id' => $subscription->user_id,
                                'subscription_id' => $subscription->id,
                                'booking_id' => null,
                                'plan_id' => $addon->id,
                                'amount' => $creditsToAdd,
                                'type' => 'addon_purchase',
                                'description' => sprintf(
                                    'Extra Sessions add-on purchased for $%s',
                                    number_format((float) $addon->price, 2),
                                ),
                                'balance_after' => $newBalance,
                            ]);

                            return [
                                'ok' => true,
                                'credits' => $creditsToAdd,
                                'balance' => $newBalance,
                            ];
                        });

                        if (! $result['ok']) {
                            Notification::make()
                                ->title('Extra sessions not added')
                                ->body($result['message'])
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Extra sessions added')
                            ->body(
                                "{$result['credits']} session(s) added. {$result['balance']} session(s) available."
                            )
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
