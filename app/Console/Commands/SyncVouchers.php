<?php

namespace App\Console\Commands;

use App\Services\VoucherSyncService;
use Illuminate\Console\Command;

class SyncVouchers extends Command
{
    protected $signature = 'vouchers:sync 
                           {direction=from_spreadsheet : Direction: from_spreadsheet, to_spreadsheet, both}
                           {--force : Force sync even if last sync was recent}';

    protected $description = 'Sync vouchers between database and Google Spreadsheet';

    protected $syncService;

    public function __construct(VoucherSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle()
    {
        $direction = $this->argument('direction');
        $force = $this->option('force');

        $this->info("🔄 Starting voucher sync: {$direction}");

        try {
            if ($direction === 'from_spreadsheet' || $direction === 'both') {
                $this->info("📥 Syncing from Google Spreadsheet to Database...");
                $result = $this->syncService->syncFromSpreadsheet();
                $this->displayResult('Spreadsheet → Database', $result);
            }

            if ($direction === 'to_spreadsheet' || $direction === 'both') {
                $this->info("📤 Syncing from Database to Google Spreadsheet...");
                $result = $this->syncService->syncToSpreadsheet();
                $this->displayResult('Database → Spreadsheet', $result);
            }

            $this->info("✅ Sync completed successfully!");
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Sync failed: " . $e->getMessage());
            return 1;
        }
    }

    private function displayResult($direction, $result)
    {
        $this->info("📊 {$direction} Results:");
        $this->line("  - Status: {$result['status']}");
        $this->line("  - Processed: {$result['processed']} records");
        $this->line("  - Errors: {$result['errors']} errors");
        
        if (!empty($result['error_details'])) {
            $this->warn("⚠️  Error Details:");
            foreach ($result['error_details'] as $error) {
                $this->line("    - {$error}");
            }
        }
        $this->newLine();
    }
}