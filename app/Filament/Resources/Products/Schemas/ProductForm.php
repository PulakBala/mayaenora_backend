<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Product Details')
                    ->tabs([
                        Tab::make('General Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('title')
                                        ->label('Product Title')
                                        ->required()
                                        ->maxLength(200)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                    TextInput::make('slug')
                                        ->label('URL Slug')
                                        ->required()
                                        ->maxLength(200)
                                        ->unique(ignoreRecord: true),
                                ]),

                                Grid::make(3)->schema([
                                    TextInput::make('base_sku')
                                        ->label('Base SKU Code')
                                        ->required()
                                        ->maxLength(60)
                                        ->unique(ignoreRecord: true),

                                    Select::make('category_id')
                                        ->label('Category')
                                        ->relationship('category', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Select::make('brand_id')
                                        ->label('Brand')
                                        ->relationship('brand', 'name')
                                        ->searchable()
                                        ->preload(),
                                ]),

                                Textarea::make('short_description')
                                    ->label('Short Description')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                RichEditor::make('description')
                                    ->label('Full Specifications & Description')
                                    ->columnSpanFull(),

                                Grid::make(3)->schema([
                                    Toggle::make('is_published')
                                        ->label('Published in Store')
                                        ->default(true),

                                    Toggle::make('is_featured')
                                        ->label('Featured Badge')
                                        ->default(false),

                                    Toggle::make('allow_reviews')
                                        ->label('Allow Customer Reviews')
                                        ->default(true),
                                ]),
                            ]),

                        Tab::make('Pricing & Promotion')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('base_cost_price')
                                        ->label('Cost Price')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0.00),

                                    TextInput::make('base_selling_price')
                                        ->label('Regular Selling Price')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->required(),
                                ]),

                                Grid::make(4)->schema([
                                    TextInput::make('discount_price')
                                        ->label('Discount Price')
                                        ->numeric()
                                        ->prefix('৳'),

                                    Select::make('discount_type')
                                        ->options([
                                            'fixed' => 'Fixed Amount',
                                            'percent' => 'Percentage',
                                        ])
                                        ->default('fixed'),

                                    DateTimePicker::make('discount_start')
                                        ->label('Discount Start Date'),

                                    DateTimePicker::make('discount_end')
                                        ->label('Discount End Date'),
                                ]),
                            ]),

                        Tab::make('Image Gallery')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Repeater::make('images')
                                    ->relationship('images')
                                    ->schema([
                                        FileUpload::make('image_url')
                                            ->label('Product Image')
                                            ->image()
                                            ->disk('public')
                                            ->directory('products/gallery')
                                            ->required(),

                                        TextInput::make('alt_text')
                                            ->label('Alt / Caption')
                                            ->maxLength(150),

                                        Toggle::make('is_primary')
                                            ->label('Primary Cover')
                                            ->default(false),

                                        TextInput::make('sort_order')
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->columns(3)
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Dynamic Variant Matrix')
                            ->icon('heroicon-o-squares-2x2')
                            ->schema([
                                Repeater::make('variants')
                                    ->relationship('variants')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('variant_sku')
                                                ->label('Variant SKU')
                                                ->required(),

                                            TextInput::make('size')
                                                ->label('Size / Capacity (e.g. 250ml, L, XL)'),

                                            TextInput::make('color')
                                                ->label('Color (e.g. White, Black)'),
                                        ]),

                                        Grid::make(4)->schema([
                                            TextInput::make('cost_price')
                                                ->label('Unit Cost')
                                                ->numeric()
                                                ->prefix('৳'),

                                            TextInput::make('regular_price')
                                                ->label('Regular Price')
                                                ->numeric()
                                                ->prefix('৳')
                                                ->required(),

                                            TextInput::make('sale_price')
                                                ->label('Sale Price')
                                                ->numeric()
                                                ->prefix('৳'),

                                            Select::make('status')
                                                ->options([
                                                    'active' => 'Active',
                                                    'inactive' => 'Inactive',
                                                ])
                                                ->default('active'),
                                        ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('stock_quantity')
                                                ->label('Current Stock')
                                                ->numeric()
                                                ->required()
                                                ->default(0),

                                            TextInput::make('low_stock_threshold')
                                                ->label('Low Stock Alert Threshold')
                                                ->numeric()
                                                ->default(5),
                                        ]),
                                    ])
                                    ->columnSpanFull()
                                    ->collapsed(),
                            ]),

                        Tab::make('SEO & Metadata')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                TextInput::make('meta_title')
                                    ->label('SEO Meta Title'),

                                Textarea::make('meta_description')
                                    ->label('SEO Meta Description')
                                    ->rows(2),

                                KeyValue::make('custom_fields')
                                    ->label('Custom Specifications & Properties'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
