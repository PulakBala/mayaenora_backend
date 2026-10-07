<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'unread' => 'Unread',
                        'read' => 'Read',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'spam' => 'Spam',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'unread' => 'danger',
                        'in_progress' => 'warning',
                        'read' => 'info',
                        'resolved' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope')
                    ->placeholder('None'),

                TextColumn::make('subject')
                    ->label('Subject')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('message')
                    ->label('Message Preview')
                    ->limit(50)
                    ->tooltip(fn (ContactMessage $record): string => $record->message),

                TextColumn::make('created_at')
                    ->label('Received At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'unread' => 'Unread',
                        'read' => 'Read',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'spam' => 'Spam',
                    ]),

                SelectFilter::make('subject')
                    ->label('Filter by Subject')
                    ->options([
                        'Order Inquiry' => 'Order Inquiry',
                        'Product Availability' => 'Product Availability',
                        'Delivery & Shipping' => 'Delivery & Shipping',
                        'Returns & Refunds' => 'Returns & Refunds',
                        'Bulk / Wholesale' => 'Bulk / Wholesale',
                        'General Inquiry' => 'General Inquiry',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('View / Manage'),

                Action::make('mark_as_read')
                    ->label('Mark Read')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->visible(fn (ContactMessage $record) => $record->status === 'unread')
                    ->action(function (ContactMessage $record) {
                        $record->update(['status' => 'read']);
                        Notification::make()
                            ->title('Inquiry Marked as Read')
                            ->success()
                            ->send();
                    }),

                Action::make('mark_as_resolved')
                    ->label('Resolve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (ContactMessage $record) => $record->status !== 'resolved')
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Resolution Notes (Optional)')
                            ->placeholder('Describe how this inquiry was addressed.')
                            ->default(fn (ContactMessage $record) => $record->admin_notes),
                    ])
                    ->action(function (ContactMessage $record, array $data) {
                        $record->update([
                            'status' => 'resolved',
                            'admin_notes' => $data['admin_notes'] ?? $record->admin_notes,
                            'resolved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Inquiry Marked as Resolved')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

