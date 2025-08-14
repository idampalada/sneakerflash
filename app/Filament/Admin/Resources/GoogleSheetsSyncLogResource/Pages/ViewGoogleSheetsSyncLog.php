<?php

namespace App\Filament\Admin\Resources\GoogleSheetsSyncLogResource\Pages;

use App\Filament\Admin\Resources\GoogleSheetsSyncLogResource;
use App\Services\GoogleSheetsSync;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ViewGoogleSheetsSyncLog extends ViewRecord
{
    protected static string $resource = GoogleSheetsSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(false), // Disable edit since logs should be read-only

            Actions\Action::make('retry_sync')
                ->label('🔄 Retry Sync')
                ->color('success')
                ->visible(function () {
                    return in_array($this->record->status, ['failed', 'completed']);
                })
                ->requiresConfirmation()
                ->modalHeading('Retry Google Sheets Sync')
                ->modalDescription('This will start a new sync with the same settings as this one.')
                ->action(function () {
                    try {
                        $syncService = new GoogleSheetsSync();
                        $result = $syncService->syncProducts([
                            'retry_of' => $this->record->sync_id,
                            'original_options' => $this->record->sync_options,
                            'user_id' => Auth::check() ? Auth::id() : null
                        ]);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('🎉 Retry Sync Started')
                                ->body("New Sync ID: {$result['sync_id']}")
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view_new_sync')
                                        ->label('View New Sync')
                                        ->url(static::getResource()::getUrl('view', ['record' => $result['sync_id'] ?? 'unknown']))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        } else {
                            Notification::make()
                                ->title('❌ Retry Failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Error')
                            ->body('Failed to retry sync: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('view_spreadsheet')
                ->label('📊 View Spreadsheet')
                ->color('info')
                ->url(fn () => "https://docs.google.com/spreadsheets/d/{$this->record->spreadsheet_id}/edit")
                ->openUrlInNewTab()
                ->icon('heroicon-m-document-text'),

            Actions\Action::make('download_error_log')
                ->label('📥 Download Error Log')
                ->color('warning')
                ->visible(fn () => $this->record->error_count > 0)
                ->action(function () {
                    $content = "Google Sheets Sync Error Log\n";
                    $content .= "=================================\n\n";
                    $content .= "Sync ID: {$this->record->sync_id}\n";
                    $content .= "Spreadsheet: {$this->record->spreadsheet_id}\n";
                    $content .= "Started: {$this->record->started_at}\n";
                    $content .= "Status: {$this->record->status}\n";
                    $content .= "Total Errors: {$this->record->error_count}\n\n";
                    
                    if ($this->record->error_message) {
                        $content .= "Main Error:\n{$this->record->error_message}\n\n";
                    }
                    
                    if ($this->record->error_details) {
                        $content .= "Detailed Errors:\n";
                        foreach ($this->record->error_details as $index => $error) {
                            $content .= ($index + 1) . ". ";
                            if (isset($error['row'])) {
                                $content .= "Row {$error['row']}: ";
                            }
                            $content .= $error['error'] . "\n";
                        }
                    }
                    
                    $filename = "sync_errors_{$this->record->sync_id}_" . now()->format('Y-m-d_H-i-s') . ".txt";
                    
                    return response()->streamDownload(function () use ($content) {
                        echo $content;
                    }, $filename, [
                        'Content-Type' => 'text/plain',
                    ]);
                }),

            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Sync Log')
                ->modalDescription('Are you sure you want to delete this sync log? This action cannot be undone.')
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return "Sync Log: {$this->record->sync_id}";
    }

    protected function getFooterWidgets(): array
    {
        return [
            // You can add widgets here for additional insights
        ];
    }
}