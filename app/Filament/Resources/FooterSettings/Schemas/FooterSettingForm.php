<?php

namespace App\Filament\Resources\FooterSettings\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FooterSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand & Copyright Information')
                    ->description('Set your store bio, copyright statement, and footer slogan.')
                    ->schema([
                        Textarea::make('brand_bio')
                            ->label('Brand Bio / About')
                            ->rows(3)
                            ->placeholder('e.g. Your trusted online store for everyday essentials, trending products and more.')
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            TextInput::make('copyright_text')
                                ->label('Copyright Text')
                                ->placeholder('e.g. © 2026 ELVOA. All rights reserved.')
                                ->required(),

                            TextInput::make('bottom_slogan')
                                ->label('Bottom Slogan / Tagline')
                                ->placeholder('e.g. Shop Better. Live Smarter.'),
                        ]),
                    ]),

                Section::make('Social Media Channels')
                    ->description('Add and manage social media links displayed in the footer.')
                    ->schema([
                        Repeater::make('social_links')
                            ->label('Social Profiles')
                            ->schema([
                                Select::make('platform')
                                    ->label('Platform')
                                    ->options([
                                        'facebook' => 'Facebook',
                                        'instagram' => 'Instagram',
                                        'youtube' => 'YouTube',
                                        'tiktok' => 'TikTok',
                                        'twitter' => 'X / Twitter',
                                        'whatsapp' => 'WhatsApp',
                                        'linkedin' => 'LinkedIn',
                                        'pinterest' => 'Pinterest',
                                    ])
                                    ->required(),

                                TextInput::make('title')
                                    ->label('Display Title')
                                    ->placeholder('e.g. Facebook'),

                                TextInput::make('url')
                                    ->label('Profile Link URL')
                                    ->url()
                                    ->required()
                                    ->placeholder('https://...'),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['title'] ?? $state['platform'] ?? null)),
                    ]),

                Section::make('Newsletter Subscription Box')
                    ->description('Control the newsletter banner shown in the footer.')
                    ->schema([
                        Toggle::make('newsletter_enabled')
                            ->label('Enable Newsletter Form')
                            ->default(true),

                        Grid::make(2)->schema([
                            TextInput::make('newsletter_title')
                                ->label('Heading')
                                ->placeholder('e.g. Subscribe to our newsletter'),

                            TextInput::make('newsletter_subtitle')
                                ->label('Subtitle')
                                ->placeholder('e.g. Get the latest deals and updates.'),
                        ]),
                    ]),

                Section::make('Contact & Support Info')
                    ->description('Optional contact information displayed in footer or customer support blocks.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('contact_phone')
                                ->label('Customer Support Phone')
                                ->placeholder('+880 9610-000000'),

                            TextInput::make('contact_email')
                                ->label('Support Email')
                                ->email()
                                ->placeholder('support@elvoa.com'),

                            TextInput::make('contact_address')
                                ->label('Office Address')
                                ->placeholder('House #12, Road #4, Dhaka'),
                        ]),
                    ]),
            ]);
    }
}
