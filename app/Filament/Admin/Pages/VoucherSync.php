<?php

namespace App\Filament\Admin\Pages;

use App\Models\VoucherSyncLog;
use App\Services\VoucherSyncService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Contracts\View\View;

class VoucherSync extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Shop';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.admin.pages.voucher-sync';

    public function getTitle(): string
    {
        return 'Voucher Sync';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_from_spreadsheet')
                ->label('Sync from Spreadsheet')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    try {
                        $syncService = new VoucherSyncService();
                        $result = $syncService->syncFromSpreadsheet();
                        
                        Notification::make()
                            ->title('Sync Completed Successfully!')
                            ->body("Processed: {$result['processed']} vouchers, Created: {$result['created']}, Updated: {$result['updated']}, Errors: {$result['errors']}")
                            ->success()
                            ->duration(8000)
                            ->send();
                            
                        $this->redirect(static::getUrl());
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sync Failed')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->duration(10000)
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Sync Vouchers from Google Spreadsheet')
                ->modalDescription('This will fetch the latest voucher data from Google Spreadsheet and update the database. Existing vouchers will be updated and new ones will be created.')
                ->modalSubmitActionLabel('Start Sync'),

            Action::make('sync_to_spreadsheet')
                ->label('Sync to Spreadsheet')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('secondary')
                ->action(function () {
                    try {
                        $syncService = new VoucherSyncService();
                        $result = $syncService->syncToSpreadsheet();
                        
                        Notification::make()
                            ->title('Sync to Spreadsheet Completed!')
                            ->body("Uploaded {$result['processed']} vouchers to spreadsheet")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sync to Spreadsheet Failed')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),

            Action::make('open_spreadsheet')
                ->label('Open Spreadsheet')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://docs.google.com/spreadsheets/d/' . config('google-sheets.voucher.spreadsheet_id'))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(VoucherSyncLog::query())
            ->columns([
                TextColumn::make('sync_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'primary' => 'spreadsheet_to_db',
                        'secondary' => 'db_to_spreadsheet',
                        'info' => 'manual',
                        'success' => 'auto',
                    ]),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'success',
                        'warning' => 'partial',
                        'danger' => 'error',
                    ]),

                TextColumn::make('records_processed')
                    ->label('Processed')
                    ->alignCenter(),

                TextColumn::make('errors_count')
                    ->label('Errors')
                    ->alignCenter()
                    ->color('danger'),

                TextColumn::make('execution_time_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn (?int $state): string => 
                        $state ? number_format($state / 1000, 2) . 's' : '-')
                    ->alignCenter(),

                TextColumn::make('synced_at')
                    ->label('Sync Time')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('synced_at', 'desc')
            ->paginated([10, 25, 50])
            ->poll('30s');
    }
}