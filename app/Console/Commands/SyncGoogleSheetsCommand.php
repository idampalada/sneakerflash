<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetsSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleSheetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sync:google-sheets 
                            {--dry-run : Run sync in dry-run mode without actually creating/updating products}
                            {--force : Force sync even if there are validation errors}';

    /**
     * The console command description.
     */
    protected $description = 'Sync products from Google Sheets to database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Google Sheets sync...');
        $this->info('Spreadsheet: https://docs.google.com/spreadsheets/d/1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg/');
        $this->newLine();

        try {
            $syncService = new GoogleSheetsSync();
            
            // Show progress bar
            $progressBar = $this->output->createProgressBar();
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $progressBar->setMessage('Fetching data from Google Sheets...');
            $progressBar->start();

            if ($this->option('dry-run')) {
                $this->warn('🔍 Running in DRY-RUN mode - no changes will be made to database');
                $this->newLine();
            }

            // Perform sync
            $progressBar->setMessage('Processing data...');
            $progressBar->advance();

            $result = $syncService->syncProducts();

            $progressBar->setMessage('Sync completed!');
            $progressBar->finish();
            $this->newLine(2);

            // Display results
            $this->displayResults($result);

            return $result['success'] ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            Log::error('Google Sheets sync command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Display sync results in a formatted way
     */
    private function displayResults(array $result): void
    {
        if ($result['success']) {
            $this->info('✅ Sync completed successfully!');
            $this->newLine();

            // Display statistics
            $stats = $result['stats'];
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Products Created', $stats['created']],
                    ['Products Updated', $stats['updated']],
                    ['Rows Skipped', $stats['skipped']],
                    ['Errors', $stats['errors']],
                ]
            );

            // Display errors if any
            if (!empty($result['errors'])) {
                $this->newLine();
                $this->warn('⚠️  Errors encountered during sync:');
                
                foreach (array_slice($result['errors'], 0, 5) as $error) {
                    $this->line('• Row ' . ($error['row'] ?? 'Unknown') . ': ' . $error['error']);
                }

                if (count($result['errors']) > 5) {
                    $remaining = count($result['errors']) - 5;
                    $this->line("... and {$remaining} more errors");
                }

                $this->newLine();
                $this->comment('💡 Check logs for detailed error information');
            }

        } else {
            $this->error('❌ Sync failed: ' . $result['message']);
            
            if (!empty($result['errors'])) {
                $this->newLine();
                $this->warn('Errors:');
                foreach (array_slice($result['errors'], 0, 3) as $error) {
                    $this->line('• ' . $error['error']);
                }
            }
        }

        $this->newLine();
        $this->comment('📝 Full sync details have been logged');
    }
}