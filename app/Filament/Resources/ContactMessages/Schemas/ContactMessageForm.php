<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('name')
                                ->label('Customer Name')
                                ->disabled(),

                            TextInput::make('phone')
                                ->label('Phone Number')
                                ->disabled(),

                            TextInput::make('email')
                                ->label('Email Address')
                                ->disabled(),
                        ]),
                    ]),

                Section::make('Customer Inquiry')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Subject / Topic')
                            ->disabled(),

                        Textarea::make('message')
                            ->label('Message Content')
                            ->rows(5)
                            ->disabled()
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            TextInput::make('ip_address')
                                ->label('IP Address')
                                ->disabled(),

                            DateTimePicker::make('created_at')
                                ->label('Received At')
                                ->disabled(),
                        ]),
                    ]),

                Section::make('Support Resolution & Follow-Up')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Inquiry Status')
                                ->options([
                                    'unread' => 'Unread',
                                    'read' => 'Read',
                                    'in_progress' => 'In Progress (Reaching Out)',
                                    'resolved' => 'Resolved',
                                    'spam' => 'Spam / Junk',
                                ])
                                ->required()
                                ->live(),

                            DateTimePicker::make('resolved_at')
                                ->label('Resolved Timestamp')
                                ->helperText('Automatically populated or set when resolved.'),
                        ]),

                        Textarea::make('admin_notes')
                            ->label('Admin Internal Notes / Resolution Summary')
                            ->placeholder('e.g., Called customer at 3:00 PM, explained delivery timeline for ELV-XXXX.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

