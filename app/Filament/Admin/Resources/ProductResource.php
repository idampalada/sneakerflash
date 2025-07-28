<?php
// File: app/Filament/Admin/Resources/ProductResource.php
// Updated version with image upload functionality

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Shop';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, callable $set) => 
                                $context === 'create' ? $set('slug', Str::slug($state)) : null),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Product::class, 'slug', ignoreRecord: true),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(function () {
                                return Category::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->placeholder('Select a category')
                            ->native(true),

                        Forms\Components\TextInput::make('brand')
                            ->maxLength(255),

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
                            ->helperText('Upload up to 10 images. First image will be used as featured image.')
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

                Forms\Components\Section::make('Pricing & Inventory')
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
                            ->helperText('Leave empty if no discount'),

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
                            ->suffix('kg')
                            ->placeholder('0.5'),
                    ])->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Product will be visible to customers'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false)
                            ->helperText('Show in featured products section'),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->default(now())
                            ->helperText('When product becomes available'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Image')
                    ->getStateUsing(function (Product $record): ?string {
                        if ($record->images && count($record->images) > 0) {
                            return $record->images[0];
                        }
                        return null;
                    })
                    ->size(50)
                    ->square(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('brand')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->money('IDR')
                    ->placeholder('No sale')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn (string $state): string => match (true) {
                        $state == 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('brand')
                    ->options(function () {
                        return Product::whereNotNull('brand')
                            ->distinct()
                            ->pluck('brand', 'brand')
                            ->toArray();
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active products')
                    ->falseLabel('Inactive products')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueLabel('Featured products')
                    ->falseLabel('Non-featured products')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}