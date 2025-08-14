<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GoogleSheetsSyncLogResource\Pages;
use App\Models\GoogleSheetsSyncLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class GoogleSheetsSyncLogResource extends Resource
{
    protected static ?string $model = GoogleSheetsSyncLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Shop';
    protected static ?string $navigationLabel = 'Sync Logs';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sync Information')
                    ->schema([
                        Forms\Components\TextInput::make('sync_id')
                            ->label('Sync ID')
                            ->disabled(),

                        Forms\Components\TextInput::make('spreadsheet_id')
                            ->label('Spreadsheet ID')
                            ->disabled(),

                        Forms\Components\TextInput::make('sheet_name')
                            ->label('Sheet Name')
                            ->disabled(),

                        Forms\Components\TextInput::make('initiated_by')
                            ->label('Initiated By')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Started At')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completed At')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('total_rows')
                            ->label('Total Rows')
                            ->disabled()
                            ->numeric(),

                        Forms\Components\TextInput::make('processed_rows')
                            ->label('Processed Rows')
                            ->disabled()
                            ->numeric(),

                        Forms\Components\TextInput::make('created_products')
                            ->label('Created Products')
                            ->disabled()
                            ->numeric(),

                        Forms\Components\TextInput::make('updated_products')
                            ->label('Updated Products')
                            ->disabled()
                            ->numeric(),

                        Forms\Components\TextInput::make('skipped_rows')
                            ->label('Skipped Rows')
                            ->disabled()
                            ->numeric(),

                        Forms\Components\TextInput::make('error_count')
                            ->label('Error Count')
                            ->disabled()
                            ->numeric(),
                    ])->columns(3),

                Forms\Components\Section::make('Results & Errors')
                    ->schema([
                        Forms\Components\Textarea::make('summary')
                            ->label('Summary')
                            ->disabled()
                            ->rows(3),

                        Forms\Components\Textarea::make('error_message')
                            ->label('Error Message')
                            ->disabled()
                            ->rows(3)
                            ->visible(fn (GoogleSheetsSyncLog $record) => !empty($record->error_message)),

                        Forms\Components\KeyValue::make('sync_results')
                            ->label('Sync Results')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('error_details')
                            ->label('Error Details')
                            ->disabled()
                            ->columnSpanFull()
                            ->visible(fn (GoogleSheetsSyncLog $record) => !empty($record->error_details)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sync_id')
                    ->label('Sync ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->limit(20)
                    ->tooltip(function (GoogleSheetsSyncLog $record): string {
                        return $record->sync_id;
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => '⏳ Pending',
                        'running' => '🔄 Running',
                        'completed' => '✅ Completed',
                        'failed' => '❌ Failed',
                        default => '❓ Unknown'
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'running' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->tooltip(function (GoogleSheetsSyncLog $record): string {
                        return $record->started_at->format('l, F j, Y \a\t g:i A');
                    }),

                Tables\Columns\TextColumn::make('duration_formatted')
                    ->label('Duration')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_rows')
                    ->label('Total Rows')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_products')
                    ->label('Created')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('updated_products')
                    ->label('Updated')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->color('info')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('error_count')
                    ->label('Errors')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->weight(fn ($state) => $state > 0 ? 'bold' : 'normal'),

                Tables\Columns\TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->alignEnd()
                    ->color(fn ($state) => match(true) {
                        $state >= 90 => 'success',
                        $state >= 70 => 'warning',
                        default => 'danger'
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('initiated_by')
                    ->label('By')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'System';
                        $user = \App\Models\User::find($state);
                        return $user ? $user->name : 'Unknown';
                    })
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'running' => 'Running',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ])
                    ->multiple(),

                Filter::make('with_errors')
                    ->label('With Errors')
                    ->query(fn (Builder $query): Builder => $query->where('error_count', '>', 0)),

                Filter::make('successful')
                    ->label('Successful (No Errors)')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'completed')->where('error_count', 0)),

                Filter::make('recent')
                    ->label('Last 24 Hours')
                    ->query(fn (Builder $query): Builder => $query->where('started_at', '>=', now()->subDay())),

                Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->where('started_at', '>=', now()->startOfWeek())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-m-information-circle')
                    ->color('info')
                    ->modalHeading(fn (GoogleSheetsSyncLog $record) => 'Sync Details: ' . $record->sync_id)
                    ->modalContent(fn (GoogleSheetsSyncLog $record) => view('filament.components.sync-log-details', compact('record')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\Action::make('view_errors')
                    ->label('View Errors')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (GoogleSheetsSyncLog $record) => $record->error_count > 0)
                    ->modalHeading(fn (GoogleSheetsSyncLog $record) => 'Errors: ' . $record->sync_id)
                    ->modalContent(fn (GoogleSheetsSyncLog $record) => view('filament.components.sync-errors', compact('record')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected Logs')
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('cleanup_old')
                        ->label('Cleanup Old Logs')
                        ->icon('heroicon-m-trash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Cleanup Old Sync Logs')
                        ->modalDescription('This will delete sync logs older than 30 days.')
                        ->action(function () {
                            $deleted = GoogleSheetsSyncLog::cleanup(30);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Cleanup Completed')
                                ->body("Deleted {$deleted} old sync logs")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('started_at', 'desc')
            ->poll('30s') // Auto refresh every 30 seconds for running syncs
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoogleSheetsSyncLogs::route('/'),
            'view' => Pages\ViewGoogleSheetsSyncLog::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $runningCount = GoogleSheetsSyncLog::whereIn('status', ['pending', 'running'])->count();
        return $runningCount > 0 ? (string) $runningCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $runningCount = GoogleSheetsSyncLog::whereIn('status', ['pending', 'running'])->count();
        return $runningCount > 0 ? 'warning' : null;
    }

    public static function getWidgets(): array
    {
        return [
            // SyncStatsWidget::class, // Uncomment when widget is created
        ];
    }
}