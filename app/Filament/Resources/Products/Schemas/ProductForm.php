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
                                        ->placeholder('e.g. Mini Electric Food Chopper 250ml')
                                        ->required()
                                        ->maxLength(200)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                    TextInput::make('slug')
                                        ->label('URL Slug')
                                        ->placeholder('e.g. mini-electric-food-chopper-250ml')
                                        ->helperText('Unique URL slug (auto-generated from title)')
                                        ->required()
                                        ->maxLength(200)
                                        ->unique(ignoreRecord: true),
                                ]),

                                Grid::make(3)->schema([
                                    TextInput::make('base_sku')
                                        ->label('Base SKU Code')
                                        ->placeholder('e.g. ELV-CHOP-01')
                                        ->helperText('Master SKU code for this product')
                                        ->required()
                                        ->maxLength(60)
                                        ->unique(ignoreRecord: true),

                                    Select::make('category_id')
                                        ->label('Category')
                                        ->placeholder('Select product category')
                                        ->relationship('category', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Select::make('brand_id')
                                        ->label('Brand')
                                        ->placeholder('Select brand (optional)')
                                        ->relationship('brand', 'name')
                                        ->searchable()
                                        ->preload(),
                                ]),

                                Textarea::make('short_description')
                                    ->label('Short Description')
                                    ->placeholder('Brief 1-2 sentence summary of the product (e.g. Wireless rechargeable portable food processor with 3 stainless steel blades).')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                RichEditor::make('description')
                                    ->label('Full Specifications & Description')
                                    ->placeholder('Write complete product description, technical specifications, and key features here...')
                                    ->columnSpanFull(),

                                Grid::make(3)->schema([
                                    Toggle::make('is_published')
                                        ->label('Published in Store')
                                        ->helperText('Visible to customers on the website')
                                        ->default(true),

                                    Toggle::make('is_featured')
                                        ->label('Featured Badge')
                                        ->helperText('Showcase in Featured collections on the homepage')
                                        ->default(false),

                                    Toggle::make('allow_reviews')
                                        ->label('Allow Customer Reviews')
                                        ->helperText('Allow verified buyers to leave reviews')
                                        ->default(true),
                                ]),
                            ]),

                        Tab::make('Pricing & Promotion')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('base_cost_price')
                                        ->label('Cost Price')
                                        ->placeholder('e.g. 320.00')
                                        ->helperText('Internal wholesale / manufacturing cost price')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0.00),

                                    TextInput::make('base_selling_price')
                                        ->label('Regular Selling Price')
                                        ->placeholder('e.g. 729.00')
                                        ->helperText('Standard retail price displayed to customers')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->required(),
                                ]),

                                Grid::make(4)->schema([
                                    TextInput::make('discount_price')
                                        ->label('Discount Price')
                                        ->placeholder('e.g. 499.00')
                                        ->helperText('Promotional discounted sale price (optional)')
                                        ->numeric()
                                        ->prefix('৳'),

                                    Select::make('discount_type')
                                        ->options([
                                            'fixed' => 'Fixed Amount (৳)',
                                            'percent' => 'Percentage (%)',
                                        ])
                                        ->default('fixed'),

                                    DateTimePicker::make('discount_start')
                                        ->label('Discount Start Date')
                                        ->placeholder('Select promo start date'),

                                    DateTimePicker::make('discount_end')
                                        ->label('Discount End Date')
                                        ->placeholder('Select promo end date'),
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
                                            ->placeholder('e.g. Front view of product')
                                            ->maxLength(150),

                                        Toggle::make('is_primary')
                                            ->label('Primary Cover')
                                            ->helperText('Set as main cover thumbnail')
                                            ->default(false),

                                        TextInput::make('sort_order')
                                            ->label('Display Order')
                                            ->placeholder('e.g. 0')
                                            ->numeric()
                                            ->default(0),
                                    ])
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
                                                ->placeholder('e.g. ELV-CHOP-WHT-250')
                                                ->required(),

                                            TextInput::make('size')
                                                ->label('Size / Capacity')
                                                ->placeholder('e.g. 250ml, 350ml, M, L, XL'),

                                            TextInput::make('color')
                                                ->label('Color')
                                                ->placeholder('e.g. White, Green, Black'),
                                        ]),

                                        Grid::make(4)->schema([
                                            TextInput::make('cost_price')
                                                ->label('Unit Cost')
                                                ->placeholder('e.g. 320.00')
                                                ->numeric()
                                                ->prefix('৳'),

                                            TextInput::make('regular_price')
                                                ->label('Regular Price')
                                                ->placeholder('e.g. 729.00')
                                                ->numeric()
                                                ->prefix('৳')
                                                ->required(),

                                            TextInput::make('sale_price')
                                                ->label('Sale Price')
                                                ->placeholder('e.g. 499.00')
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
                                                ->placeholder('e.g. 100')
                                                ->numeric()
                                                ->required()
                                                ->default(0),

                                            TextInput::make('low_stock_threshold')
                                                ->label('Low Stock Alert Threshold')
                                                ->placeholder('e.g. 5')
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
                                    ->label('SEO Meta Title')
                                    ->placeholder('e.g. Mini Electric Food Chopper 250ml | Maya Enora Store'),

                                Textarea::make('meta_description')
                                    ->label('SEO Meta Description')
                                    ->placeholder('e.g. Buy high quality rechargeable mini electric food chopper online at best price in Bangladesh.')
                                    ->rows(2),

                                KeyValue::make('custom_fields')
                                    ->label('Custom Specifications & Properties')
                                    ->keyLabel('Specification Name (e.g. Battery Capacity)')
                                    ->valueLabel('Specification Value (e.g. 1200mAh)'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
