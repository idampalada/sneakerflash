<?php

namespace App\Filament\Admin\Resources\GoogleSheetsSyncLogResource\Pages;

use App\Filament\Admin\Resources\GoogleSheetsSyncLogResource;
use App\Services\GoogleSheetsSync;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ListGoogleSheetsSyncLogs extends ListRecords
{
    protected static string $resource = GoogleSheetsSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('test_connection')
                ->label('🔗 Test Connection')
                ->color('info')
                ->action(function () {
                    $syncService = new GoogleSheetsSync();
                    $result = $syncService->testConnection();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('✅ Connection Successful')
                            ->body('Successfully connected to Google Sheets')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('❌ Connection Failed')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('preview_data')
                ->label('👁️ Preview Data')
                ->color('warning')
                ->action(function () {
                    $syncService = new GoogleSheetsSync();
                    $result = $syncService->previewData(5);
                    
                    if ($result['success']) {
                        $message = "Found {$result['total_rows']} total rows. Preview of first {$result['preview_count']} rows:\n\n";
                        foreach ($result['data'] as $index => $row) {
                            $message .= ($index + 1) . ". {$row['name']} ({$row['brand']}) - Rp " . number_format($row['price']) . "\n";
                        }
                        
                        Notification::make()
                            ->title('📊 Data Preview')
                            ->body($message)
                            ->info()
                            ->duration(15000)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('❌ Preview Failed')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('trigger_sync')
                ->label('🔄 Trigger New Sync')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Trigger Google Sheets Sync')
                ->modalDescription('This will start a new sync process from Google Sheets.')
                ->action(function () {
                    try {
                        $syncService = new GoogleSheetsSync();
                        $result = $syncService->syncProducts([
                            'triggered_from' => 'sync_logs_page',
                            'user_id' => Auth::check() ? Auth::id() : null
                        ]);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('🎉 Sync Started Successfully')
                                ->body("Sync ID: {$result['sync_id']}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('❌ Sync Failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Error')
                            ->body('Failed to start sync: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('cleanup_old_logs')
                ->label('🧹 Cleanup Old Logs')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Cleanup Old Sync Logs')
                ->modalDescription('This will delete sync logs older than 30 days. This action cannot be undone.')
                ->action(function () {
                    $deleted = \App\Models\GoogleSheetsSyncLog::cleanup(30);
                    
                    Notification::make()
                        ->title('🧹 Cleanup Completed')
                        ->body("Deleted {$deleted} old sync logs")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Google Sheets Sync Logs';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add widgets here if needed
        ];
    }
}