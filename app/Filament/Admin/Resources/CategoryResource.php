<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CategoryResource\Pages;
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

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Shop';
    protected static ?int $navigationSort = 1;

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
                            ->unique(Category::class, 'slug', ignoreRecord: true),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Category description for SEO and display'),

                        Forms\Components\FileUpload::make('image')
                            ->label('Category Image')
                            ->image()
                            ->imageEditor()
                            ->directory('categories')
                            ->visibility('public')
                            ->imagePreviewHeight('200')
                            ->helperText('Category banner or icon image'),
                    ])->columns(2),

                Forms\Components\Section::make('Menu Classification')
                    ->description('Configure how this category appears in the new menu system')
                    ->schema([
                        Forms\Components\Select::make('menu_placement')
                            ->label('Primary Menu Placement')
                            ->options([
                                'mens' => 'MENS - Male targeted products',
                                'womens' => 'WOMENS - Female targeted products', 
                                'kids' => 'KIDS - Children targeted products',
                                'accessories' => 'ACCESSORIES - Bags, hats, socks, etc',
                                'general' => 'GENERAL - All menus (unisex)',
                            ])
                            ->placeholder('Select primary menu placement')
                            ->helperText('Where this category primarily appears in navigation')
                            ->native(true),

                        Forms\Components\CheckboxList::make('secondary_menus')
                            ->label('Also Show In')
                            ->options([
                                'mens' => 'MENS',
                                'womens' => 'WOMENS',
                                'kids' => 'KIDS',
                                'accessories' => 'ACCESSORIES',
                                'brand' => 'BRAND (if applicable)',
                            ])
                            ->helperText('Additional menus where this category can appear')
                            ->columns(2),

                        Forms\Components\TagsInput::make('category_keywords')
                            ->label('Search Keywords')
                            ->placeholder('Add keywords')
                            ->helperText('Keywords for search and filtering (e.g., sport, casual, formal)')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Category Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Category visible on website'),

                        Forms\Components\Toggle::make('show_in_menu')
                            ->label('Show in Navigation Menu')
                            ->default(true)
                            ->helperText('Display in website navigation'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Category')
                            ->default(false)
                            ->helperText('Highlight in homepage and featured sections'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])->columns(2),

                Forms\Components\Section::make('SEO & Display')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('SEO Title')
                            ->maxLength(60)
                            ->helperText('Page title for search engines (max 60 chars)'),

                        Forms\Components\Textarea::make('meta_description')
                            ->label('SEO Description')
                            ->maxLength(160)
                            ->rows(3)
                            ->helperText('Meta description for search engines (max 160 chars)'),

                        Forms\Components\TagsInput::make('meta_keywords')
                            ->label('SEO Keywords')
                            ->placeholder('Add keyword')
                            ->helperText('Keywords for search engine optimization'),

                        Forms\Components\ColorPicker::make('brand_color')
                            ->label('Brand Color')
                            ->helperText('Theme color for this category (optional)'),
                    ])->columns(2),

                Forms\Components\Section::make('Category Analytics')
                    ->schema([
                        Forms\Components\Placeholder::make('products_count')
                            ->label('Total Products')
                            ->content(function (?Category $record): string {
                                if (!$record) return '0';
                                return (string) $record->products()->count();
                            }),

                        Forms\Components\Placeholder::make('active_products_count')
                            ->label('Active Products')
                            ->content(function (?Category $record): string {
                                if (!$record) return '0';
                                return (string) $record->products()->where('is_active', true)->count();
                            }),

                        Forms\Components\Placeholder::make('featured_products_count')
                            ->label('Featured Products')
                            ->content(function (?Category $record): string {
                                if (!$record) return '0';
                                return (string) $record->products()->where('is_featured', true)->count();
                            }),

                        Forms\Components\Placeholder::make('sale_products_count')
                            ->label('Products on Sale')
                            ->content(function (?Category $record): string {
                                if (!$record) return '0';
                                return (string) $record->products()->whereNotNull('sale_price')->count();
                            }),
                    ])->columns(4)->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->size(50)
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('menu_placement')
                    ->label('Primary Menu')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mens' => 'blue',
                        'womens' => 'pink', 
                        'kids' => 'yellow',
                        'accessories' => 'green',
                        'general' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('secondary_menus')
                    ->label('Also In')
                    ->badge()
                    ->separator(',')
                    ->limit(20),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->getStateUsing(fn (Category $record): int => $record->products()->count())
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('active_products_count')
                    ->label('Active')
                    ->getStateUsing(fn (Category $record): int => $record->products()->where('is_active', true)->count())
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('show_in_menu')
                    ->label('In Menu')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('menu_placement')
                    ->label('Primary Menu')
                    ->options([
                        'mens' => 'MENS',
                        'womens' => 'WOMENS',
                        'kids' => 'KIDS',
                        'accessories' => 'ACCESSORIES',
                        'general' => 'GENERAL',
                    ]),

                Filter::make('active')
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),

                Filter::make('show_in_menu')
                    ->label('In Menu Only')
                    ->query(fn (Builder $query): Builder => $query->where('show_in_menu', true)),

                Filter::make('featured')
                    ->label('Featured Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),

                Filter::make('has_products')
                    ->label('Has Products')
                    ->query(fn (Builder $query): Builder => $query->has('products')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    
                    Tables\Actions\Action::make('view_products')
                        ->label('View Products')
                        ->icon('heroicon-o-cube')
                        ->url(fn (Category $record): string => "/admin/products?tableFilters[category_id][value]={$record->id}")
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('success'),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('danger'),

                    Tables\Actions\BulkAction::make('add_to_menu')
                        ->label('Add to Menu')
                        ->icon('heroicon-o-bars-3')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['show_in_menu' => true]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('info'),

                    Tables\Actions\BulkAction::make('feature')
                        ->label('Mark as Featured')
                        ->icon('heroicon-o-star')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_featured' => true]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('warning'),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['products']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'description'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Products' => $record->products()->count() . ' products',
            'Menu' => ucfirst($record->menu_placement ?? 'Not set'),
            'Status' => $record->is_active ? 'Active' : 'Inactive',
        ];
    }
}