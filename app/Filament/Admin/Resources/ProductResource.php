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
            // Basic Information Section
            Forms\Components\Section::make('Basic Information')
                ->description('Essential product details that will appear on your website')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Product Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $context, $state, callable $set) {
                            if ($context === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->placeholder('e.g., Nike Air Jordan 1 Retro High')
                        ->helperText('This will be the main product title shown to customers'),

                    Forms\Components\TextInput::make('slug')
                        ->label('URL Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(Product::class, 'slug', ignoreRecord: true)
                        ->helperText('Auto-generated from product name. This creates the product URL: /products/your-slug'),

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
                        ->helperText('Main category for product organization and filtering'),

                    Forms\Components\TextInput::make('brand')
                        ->label('Brand')
                        ->maxLength(255)
                        ->placeholder('e.g., Nike, Adidas, Puma')
                        ->helperText('Brand name for filtering in BRAND menu')
                        ->datalist([
                            'Nike',
                            'Adidas', 
                            'Puma',
                            'Converse',
                            'Vans',
                            'New Balance',
                            'Jordan',
                            'Reebok',
                            'ASICS',
                            'Under Armour',
                            'Skechers',
                            'Fila',
                            'DC Shoes',
                            'Timberland',
                        ]),

                    Forms\Components\TextInput::make('sku')
                        ->label('SKU (Stock Keeping Unit)')
                        ->maxLength(255)
                        ->unique(Product::class, 'sku', ignoreRecord: true)
                        ->placeholder('Auto-generated if empty')
                        ->helperText('Unique identifier for inventory management'),
                ])->columns(2),

            // Product Images Section
            Forms\Components\Section::make('Product Images')
                ->description('Upload high-quality product images. First image becomes the featured image.')
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
                        ->helperText('Upload up to 10 images. First image will be the main featured image on your product pages.')
                        ->columnSpanFull(),
                ]),

            // Product Description Section
            Forms\Components\Section::make('Product Description')
                ->description('Detailed information about your product')
                ->schema([
                    Forms\Components\Textarea::make('short_description')
                        ->label('Short Description')
                        ->maxLength(500)
                        ->rows(3)
                        ->placeholder('Brief product summary...')
                        ->helperText('Brief description for product cards and listings (max 500 characters)'),

                    Forms\Components\RichEditor::make('description')
                        ->label('Full Product Description')
                        ->required()
                        ->columnSpanFull()
                        ->placeholder('Detailed product information, features, materials, etc...')
                        ->helperText('Detailed product description with formatting. This appears on the product detail page.')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'link',
                            'blockquote',
                        ]),
                ]),

            // Pricing Section - Disesuaikan dengan frontend
            Forms\Components\Section::make('Pricing & Sales')
                ->description('Set product pricing. Sale price enables SALE menu display.')
                ->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('Regular Price')
                        ->required()
                        ->numeric()
                        ->prefix('Rp')
                        ->step(1000)
                        ->placeholder('1000000')
                        ->helperText('Base price of the product'),

                    Forms\Components\TextInput::make('sale_price')
                        ->label('Sale Price (Optional)')
                        ->numeric()
                        ->prefix('Rp')
                        ->step(1000)
                        ->placeholder('800000')
                        ->helperText('Set this to show product in SALE menu and display discount badge')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            // Auto calculate discount percentage
                            $regularPrice = $get('price');
                            if ($regularPrice && $state && $state < $regularPrice) {
                                $discount = round((($regularPrice - $state) / $regularPrice) * 100);
                                // You could set a discount_percentage field here if you have one
                            }
                        }),
                ])->columns(2),

            // Inventory Section
            Forms\Components\Section::make('Inventory Management')
                ->description('Stock levels and product specifications')
                ->schema([
                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Stock Quantity')
                        ->required()
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->helperText('Current stock level. Shows "In stock" or "Out of stock" on frontend'),

                    Forms\Components\TextInput::make('weight')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->step(0.1)
                        ->placeholder('0.5')
                        ->helperText('Product weight for shipping calculations'),
                ])->columns(2),

            // Status & Visibility Section
            Forms\Components\Section::make('Product Status & Visibility')
                ->description('Control how this product appears on your website')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active Product')
                        ->default(true)
                        ->helperText('When enabled, product is visible on website. Disabled products are hidden from customers.'),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured Product')
                        ->default(false)
                        ->helperText('Featured products appear in "Featured Products" section on homepage and get special badges.'),

                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Publish Date')
                        ->default(now())
                        ->helperText('When this product becomes available. Future dates will hide product until then.'),
                ])->columns(3),
        ];

            // Add Product Classification section if columns exist
        if (Schema::hasColumn('products', 'gender_target') || Schema::hasColumn('products', 'product_type')) {
            $classificationFields = [];

            if (Schema::hasColumn('products', 'gender_target')) {
                $classificationFields[] = Forms\Components\CheckboxList::make('gender_target')
                    ->label('Target Gender')
                    ->options([
                        'mens' => "👨 Men's",
                        'womens' => "👩 Women's", 
                        'kids' => '👶 Kids',
                    ])
                    ->columns(3)
                    ->helperText('Select all genders where this product should appear. Can select multiple (e.g., both Men\'s and Women\'s)')
                    ->required();
            }

            if (Schema::hasColumn('products', 'product_type')) {
                $classificationFields[] = Forms\Components\Select::make('product_type')
                    ->label('Product Type')
                    ->options([
                        'sneakers' => '👟 Sneakers',
                        'running' => '🏃 Running Shoes',
                        'basketball' => '🏀 Basketball Shoes',
                        'lifestyle_casual' => '🚶 Lifestyle/Casual',
                        'training' => '💪 Training Shoes',
                        'formal' => '👔 Formal Shoes',
                        'sandals' => '🩴 Sandals',
                        'boots' => '🥾 Boots',
                        'accessories' => '🎒 Accessories',
                        'apparel' => '👕 Apparel',
                        'backpack' => '🎒 Backpack',
                        'bag' => '👜 Bag',
                        'hat' => '🧢 Hat/Cap',
                        'socks' => '🧦 Socks',
                        'laces' => '🔗 Shoe Laces',
                        'care_products' => '🧴 Shoe Care',
                    ])
                    ->placeholder('Select product type')
                    ->helperText('Specific product category for better organization and filtering');
            }

            if (Schema::hasColumn('products', 'search_keywords')) {
                $classificationFields[] = Forms\Components\TagsInput::make('search_keywords')
                    ->label('Search Keywords')
                    ->placeholder('Add keyword')
                    ->helperText('Keywords to help customers find this product (e.g., sport, casual, premium)')
                    ->columnSpanFull();
            }

            $schema[] = Forms\Components\Section::make('Product Classification')
                ->description('Classify your product for proper menu navigation (MEN\'S, WOMEN\'S, KIDS, etc.)')
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
                    ->helperText('Available sizes (e.g., 40, 41, 42, 43)')
                    ->columnSpanFull();
            }

            if (Schema::hasColumn('products', 'available_colors')) {
                $advancedFields[] = Forms\Components\TagsInput::make('available_colors')
                    ->label('Available Colors')
                    ->placeholder('Add color')
                    ->helperText('Available colors (e.g., Black, White, Red, Blue)')
                    ->suggestions([
                        'Black',
                        'White', 
                        'Red',
                        'Blue',
                        'Navy',
                        'Grey',
                        'Green',
                        'Yellow',
                        'Pink',
                        'Brown',
                        'Orange',
                        'Purple',
                        'Silver',
                        'Gold',
                        'Beige',
                        'Maroon',
                    ])
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
                    ->description('Additional product attributes and specifications')
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
                ->size(60)
                ->circular(),

            Tables\Columns\TextColumn::make('name')
                ->label('Product Name')
                ->searchable()
                ->sortable()
                ->wrap()
                ->limit(25)
                ->tooltip(function (Product $record): ?string {
                    return $record->name;
                }),

            Tables\Columns\TextColumn::make('brand')
                ->searchable()
                ->sortable()
                ->badge()
                ->color('info')
                ->formatStateUsing(fn ($state) => $state ?? 'No Brand'),

            Tables\Columns\TextColumn::make('category.name')
                ->label('Category')
                ->sortable()
                ->badge()
                ->color('success'),

            Tables\Columns\TextColumn::make('gender_target')
                ->label('Gender')
                ->badge()
                ->formatStateUsing(function ($state) {
                    if (!$state) return 'No Gender';
                    
                    if (is_array($state)) {
                        $genders = [];
                        foreach ($state as $gender) {
                            $genders[] = match($gender) {
                                'mens' => '👨 Men\'s',
                                'womens' => '👩 Women\'s',
                                'kids' => '👶 Kids',
                                default => $gender
                            };
                        }
                        return implode(', ', $genders);
                    }
                    
                    return match($state) {
                        'mens' => '👨 Men\'s',
                        'womens' => '👩 Women\'s',
                        'kids' => '👶 Kids',
                        default => $state
                    };
                })
                ->color(fn ($state) => is_array($state) && count($state) > 1 ? 'warning' : 'info')
                ->searchable(),

            Tables\Columns\TextColumn::make('available_colors')
                ->label('Colors')
                ->badge()
                ->formatStateUsing(function ($state) {
                    if (!$state) return 'No Colors';
                    
                    if (is_array($state)) {
                        $count = count($state);
                        if ($count <= 3) {
                            return implode(', ', array_map('ucfirst', $state));
                        }
                        return $count . ' colors';
                    }
                    
                    return $state;
                })
                ->color('secondary')
                ->limit(20),

            Tables\Columns\TextColumn::make('price')
                ->label('Price')
                ->money('IDR')
                ->sortable()
                ->alignEnd(),

            Tables\Columns\TextColumn::make('sale_price')
                ->label('Sale Price')
                ->money('IDR')
                ->sortable()
                ->alignEnd()
                ->color('danger')
                ->weight('bold')
                ->formatStateUsing(function ($state, Product $record) {
                    if (!$state) return '-';
                    $discount = round((($record->price - $state) / $record->price) * 100);
                    return "Rp " . number_format($state, 0, ',', '.') . " (-{$discount}%)";
                }),

            Tables\Columns\TextColumn::make('stock_quantity')
                ->label('Stock')
                ->sortable()
                ->alignEnd()
                ->color(fn ($state) => match(true) {
                    $state == 0 => 'danger',
                    $state < 10 => 'warning', 
                    default => 'success'
                })
                ->formatStateUsing(fn ($state) => $state == 0 ? 'Out of Stock' : $state),

            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean()
                ->sortable(),

            Tables\Columns\IconColumn::make('is_featured')
                ->label('Featured')
                ->boolean()
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Created')
                ->dateTime('d M Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        return $table
            ->columns($columns)
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),

                SelectFilter::make('brand')
                    ->options(function () {
                        return Product::query()
                            ->whereNotNull('brand')
                            ->distinct()
                            ->pluck('brand', 'brand')
                            ->toArray();
                    })
                    ->multiple(),

                Filter::make('is_featured')
                    ->label('Featured Products')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),

                Filter::make('on_sale')
                    ->label('On Sale')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('sale_price')),

                Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock_quantity', 0)),

                Filter::make('low_stock')
                    ->label('Low Stock (< 10)')
                    ->query(fn (Builder $query): Builder => $query->where('stock_quantity', '<', 10)->where('stock_quantity', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    // Bulk actions for common operations
                    Tables\Actions\BulkAction::make('toggle_featured')
                        ->label('Toggle Featured')
                        ->icon('heroicon-m-star')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_featured' => !$record->is_featured]);
                            }
                        }),

                    Tables\Actions\BulkAction::make('toggle_active')
                        ->label('Toggle Active')
                        ->icon('heroicon-m-eye')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => !$record->is_active]);
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s'); // Auto refresh every 60 seconds
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

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'success' : 'warning';
    }
}