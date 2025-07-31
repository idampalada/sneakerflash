<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Shop';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $schema = [
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $context, $state, callable $set) {
                            if ($context === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(Product::class, 'slug', ignoreRecord: true),

                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->options(function () {
                            return Category::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->required()
                        ->placeholder('Select a category')
                        ->native(true)
                        ->helperText('Select the main category for this product'),

                    Forms\Components\TextInput::make('brand')
                        ->maxLength(255)
                        ->placeholder('e.g., Nike, Adidas, Puma')
                        ->helperText('Brand name for filtering in BRAND menu'),

                    Forms\Components\TextInput::make('sku')
                        ->label('SKU')
                        ->maxLength(255)
                        ->unique(Product::class, 'sku', ignoreRecord: true)
                        ->placeholder('Auto-generated if empty'),
                ])->columns(2),

            Forms\Components\Section::make('Images')
                ->schema([
                    Forms\Components\FileUpload::make('images')
                        ->label('Product Images')
                        ->multiple()
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '1:1',
                            '4:3',
                            '16:9',
                        ])
                        ->directory('products')
                        ->visibility('public')
                        ->maxFiles(10)
                        ->reorderable()
                        ->appendFiles()
                        ->imagePreviewHeight('250')
                        ->uploadingMessage('Uploading images...')
                        ->helperText('Upload up to 10 images. First image will be featured image.')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Description')
                ->schema([
                    Forms\Components\Textarea::make('short_description')
                        ->label('Short Description')
                        ->maxLength(500)
                        ->rows(3)
                        ->helperText('Brief description for product listings'),

                    Forms\Components\RichEditor::make('description')
                        ->label('Full Description')
                        ->required()
                        ->columnSpanFull()
                        ->helperText('Detailed product description with formatting'),
                ]),

            Forms\Components\Section::make('Pricing')
                ->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('Regular Price')
                        ->required()
                        ->numeric()
                        ->prefix('Rp')
                        ->step(1000)
                        ->placeholder('1000000'),

                    Forms\Components\TextInput::make('sale_price')
                        ->label('Sale Price')
                        ->numeric()
                        ->prefix('Rp')
                        ->step(1000)
                        ->placeholder('900000')
                        ->helperText('Set this to show product in SALE menu'),
                ])->columns(2),

            Forms\Components\Section::make('Inventory')
                ->schema([
                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Stock Quantity')
                        ->required()
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Forms\Components\TextInput::make('weight')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->step(0.1)
                        ->placeholder('0.5'),
                ])->columns(2),

            Forms\Components\Section::make('Status & Visibility')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Product visible on website'),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured Product')
                        ->default(false)
                        ->helperText('Show in featured sections'),

                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Publish Date')
                        ->default(now())
                        ->helperText('When product becomes visible'),
                ])->columns(2),
        ];

        // Add conditional sections based on available columns
        if (Schema::hasColumn('products', 'gender_target') || Schema::hasColumn('products', 'product_type')) {
            $classificationFields = [];
            
            if (Schema::hasColumn('products', 'gender_target')) {
                $classificationFields[] = Forms\Components\Select::make('gender_target')
                    ->label('Target Gender')
                    ->options([
                        'mens' => 'Mens',
                        'womens' => 'Womens', 
                        'kids' => 'Kids',
                        'unisex' => 'Unisex',
                    ])
                    ->placeholder('Select target gender')
                    ->helperText('This helps categorize products in MENS/WOMENS/KIDS menus')
                    ->native(true);
            }

            if (Schema::hasColumn('products', 'product_type')) {
                $classificationFields[] = Forms\Components\Select::make('product_type')
                    ->label('Product Type')
                    ->options([
                        'sneakers' => 'Sneakers',
                        'running_shoes' => 'Running Shoes',
                        'basketball_shoes' => 'Basketball Shoes',
                        'casual_shoes' => 'Casual Shoes',
                        'boots' => 'Boots',
                        'sandals' => 'Sandals',
                        'backpack' => 'Backpack',
                        'bag' => 'Bag',
                        'hat' => 'Hat',
                        'cap' => 'Cap',
                        'socks' => 'Socks',
                        'laces' => 'Shoe Laces',
                        'care_products' => 'Care Products',
                        'accessories' => 'Other Accessories',
                    ])
                    ->placeholder('Select product type')
                    ->helperText('Helps filter in ACCESSORIES menu')
                    ->native(true);
            }

            if (Schema::hasColumn('products', 'search_keywords')) {
                $classificationFields[] = Forms\Components\TagsInput::make('search_keywords')
                    ->label('Search Keywords')
                    ->placeholder('Add keywords for better search')
                    ->helperText('Keywords to help customers find this product')
                    ->columnSpanFull();
            }

            $schema[] = Forms\Components\Section::make('Product Classification')
                ->description('Help classify your product for the new menu system')
                ->schema($classificationFields)
                ->columns(2);
        }

        // Add sale management section if columns exist
        if (Schema::hasColumn('products', 'sale_start_date') || Schema::hasColumn('products', 'is_featured_sale')) {
            $saleFields = [];

            if (Schema::hasColumn('products', 'sale_start_date')) {
                $saleFields[] = Forms\Components\DatePicker::make('sale_start_date')
                    ->label('Sale Start Date')
                    ->helperText('When the sale begins');
            }

            if (Schema::hasColumn('products', 'sale_end_date')) {
                $saleFields[] = Forms\Components\DatePicker::make('sale_end_date')
                    ->label('Sale End Date')
                    ->helperText('When the sale ends');
            }

            if (Schema::hasColumn('products', 'is_featured_sale')) {
                $saleFields[] = Forms\Components\Toggle::make('is_featured_sale')
                    ->label('Featured in Sale')
                    ->helperText('Show prominently in SALE menu')
                    ->default(false);
            }

            if (!empty($saleFields)) {
                $schema[] = Forms\Components\Section::make('Sale Management')
                    ->description('Configure sale settings for SALE menu')
                    ->schema($saleFields)
                    ->columns(2);
            }
        }

        // Add advanced features if columns exist
        if (Schema::hasColumn('products', 'available_sizes') || Schema::hasColumn('products', 'available_colors') || Schema::hasColumn('products', 'features')) {
            $advancedFields = [];

            if (Schema::hasColumn('products', 'available_sizes')) {
                $advancedFields[] = Forms\Components\TagsInput::make('available_sizes')
                    ->label('Available Sizes')
                    ->placeholder('Add size')
                    ->helperText('Available sizes (e.g., 40, 41, 42)')
                    ->columnSpanFull();
            }

            if (Schema::hasColumn('products', 'available_colors')) {
                $advancedFields[] = Forms\Components\TagsInput::make('available_colors')
                    ->label('Available Colors')
                    ->placeholder('Add color')
                    ->helperText('Available colors (e.g., Black, White, Red)')
                    ->columnSpanFull();
            }

            if (Schema::hasColumn('products', 'features')) {
                $advancedFields[] = Forms\Components\TagsInput::make('features')
                    ->label('Key Features')
                    ->placeholder('Add feature')
                    ->helperText('Key selling points and features')
                    ->columnSpanFull();
            }

            if (Schema::hasColumn('products', 'specifications')) {
                $advancedFields[] = Forms\Components\KeyValue::make('specifications')
                    ->label('Specifications')
                    ->keyLabel('Specification')
                    ->valueLabel('Value')
                    ->helperText('Technical specifications')
                    ->columnSpanFull();
            }

            if (!empty($advancedFields)) {
                $schema[] = Forms\Components\Section::make('Product Features')
                    ->schema($advancedFields);
            }
        }

        return $form->schema($schema);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            Tables\Columns\ImageColumn::make('featured_image')
                ->label('Image')
                ->getStateUsing(function (Product $record): ?string {
                    return $record->featured_image;
                })
                ->size(60),

            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->wrap()
                ->limit(30),

            Tables\Columns\TextColumn::make('brand')
                ->searchable()
                ->sortable()
                ->badge()
                ->color('info'),

            Tables\Columns\TextColumn::make('category.name')
                ->label('Category')
                ->sortable()
                ->badge()
                ->color('success'),

            Tables\Columns\TextColumn::make('price')
                ->money('IDR')
                ->sortable(),

            Tables\Columns\TextColumn::make('sale_price')
                ->money('IDR')
                ->sortable()
                ->color('danger'),

            Tables\Columns\TextColumn::make('stock_quantity')
                ->label('Stock')
                ->sortable()
                ->badge()
                ->color(function (int $state): string {
                    if ($state === 0) return 'danger';
                    if ($state <= 5) return 'warning';
                    return 'success';
                }),

            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean(),

            Tables\Columns\IconColumn::make('is_featured')
                ->label('Featured')
                ->boolean(),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        // Add conditional columns
        if (Schema::hasColumn('products', 'gender_target')) {
            array_splice($columns, 4, 0, [
                Tables\Columns\TextColumn::make('gender_target')
                    ->label('Gender')
                    ->badge()
                    ->color(function (?string $state): string {
                        return match ($state) {
                            'mens' => 'blue',
                            'womens' => 'pink',
                            'kids' => 'yellow',
                            'unisex' => 'gray',
                            default => 'gray',
                        };
                    })
            ]);
        }

        if (Schema::hasColumn('products', 'product_type')) {
            array_splice($columns, 5, 0, [
                Tables\Columns\TextColumn::make('product_type')
                    ->label('Type')
                    ->badge()
                    ->limit(15)
            ]);
        }

        $filters = [
            SelectFilter::make('category_id')
                ->label('Category')
                ->options(function () {
                    return Category::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                }),

            SelectFilter::make('brand')
                ->options(function () {
                    return Product::query()
                        ->whereNotNull('brand')
                        ->distinct()
                        ->orderBy('brand')
                        ->pluck('brand', 'brand')
                        ->toArray();
                }),

            Filter::make('on_sale')
                ->label('On Sale')
                ->query(function (Builder $query): Builder {
                    return $query->whereNotNull('sale_price');
                }),

            Filter::make('featured')
                ->label('Featured')
                ->query(function (Builder $query): Builder {
                    return $query->where('is_featured', true);
                }),

            Filter::make('low_stock')
                ->label('Low Stock')
                ->query(function (Builder $query): Builder {
                    return $query->where('stock_quantity', '<=', 5);
                }),

            Filter::make('out_of_stock')
                ->label('Out of Stock')
                ->query(function (Builder $query): Builder {
                    return $query->where('stock_quantity', 0);
                }),
        ];

        // Add conditional filters
        if (Schema::hasColumn('products', 'gender_target')) {
            $filters[] = SelectFilter::make('gender_target')
                ->label('Gender Target')
                ->options([
                    'mens' => 'Mens',
                    'womens' => 'Womens',
                    'kids' => 'Kids',
                    'unisex' => 'Unisex',
                ]);
        }

        if (Schema::hasColumn('products', 'product_type')) {
            $filters[] = SelectFilter::make('product_type')
                ->label('Product Type')
                ->options([
                    'sneakers' => 'Sneakers',
                    'running_shoes' => 'Running Shoes',
                    'basketball_shoes' => 'Basketball Shoes',
                    'casual_shoes' => 'Casual Shoes',
                    'boots' => 'Boots',
                    'sandals' => 'Sandals',
                    'backpack' => 'Backpack',
                    'bag' => 'Bag',
                    'hat' => 'Hat',
                    'cap' => 'Cap',
                    'socks' => 'Socks',
                    'laces' => 'Shoe Laces',
                    'care_products' => 'Care Products',
                    'accessories' => 'Other Accessories',
                ]);
        }

        return $table
            ->columns($columns)
            ->filters($filters)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                        })
                        ->requiresConfirmation()
                        ->color('success'),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                        })
                        ->requiresConfirmation()
                        ->color('danger'),

                    Tables\Actions\BulkAction::make('feature')
                        ->label('Mark as Featured')
                        ->icon('heroicon-o-star')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_featured' => true]);
                            }
                        })
                        ->requiresConfirmation()
                        ->color('warning'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}