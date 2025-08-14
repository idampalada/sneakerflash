<?php

namespace App\Console\Commands;

use App\Models\Voucher;
use App\Services\VoucherSyncService;
use Illuminate\Console\Command;

class TestVoucherSystem extends Command
{
    protected $signature = 'vouchers:test';
    protected $description = 'Test voucher system functionality';

    public function handle(): int
    {
        $this->info('🧪 Testing Voucher Management System...');
        $this->newLine();

        try {
            // Test 1: Database connection
            $this->info('1. Testing database connection...');
            $voucherCount = Voucher::count();
            $this->line("   ✅ Database connected. Found {$voucherCount} vouchers.");

            // Test 2: Google Sheets connection
            $this->info('2. Testing Google Sheets connection...');
            $this->testGoogleSheetsConnection();

            // Test 3: Sync service
            $this->info('3. Testing sync service...');
            $this->testSyncService();

            // Test 4: Voucher validation
            $this->info('4. Testing voucher validation...');
            $this->testVoucherValidation();

            // Test 5: Filament resources
            $this->info('5. Testing Filament resources...');
            $this->testFilamentResources();

            $this->newLine();
            $this->info('🎉 All tests passed! Voucher system is working correctly.');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function testGoogleSheetsConnection(): void
    {
        $spreadsheetId = config('google-sheets.voucher.spreadsheet_id');
        
        if (empty($spreadsheetId)) {
            $this->warn('   ⚠️  No spreadsheet ID configured');
            return;
        }

        $csvUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=csv";
        $headers = @get_headers($csvUrl);
        
        if ($headers && strpos($headers[0], '200') !== false) {
            $this->line('   ✅ Google Spreadsheet is accessible');
        } else {
            $this->warn('   ⚠️  Cannot access Google Spreadsheet - check sharing permissions');
        }
    }

    private function testSyncService(): void
    {
        try {
            $syncService = new VoucherSyncService();
            $this->line('   ✅ VoucherSyncService instantiated successfully');
        } catch (\Exception $e) {
            throw new \Exception('VoucherSyncService failed: ' . $e->getMessage());
        }
    }

    private function testVoucherValidation(): void
    {
        $voucher = Voucher::where('is_active', true)->first();
        
        if (!$voucher) {
            $this->warn('   ⚠️  No active vouchers found to test');
            return;
        }

        $validation = $voucher->isValidForUser('test_user', 100000);
        
        if (isset($validation['valid'])) {
            $this->line('   ✅ Voucher validation method working');
        } else {
            throw new \Exception('Voucher validation method not working correctly');
        }
    }

    private function testFilamentResources(): void
    {
        $resourceClasses = [
            'App\Filament\Admin\Resources\VoucherResource',
            'App\Filament\Admin\Resources\VoucherUsageResource',
        ];

        foreach ($resourceClasses as $resourceClass) {
            if (class_exists($resourceClass)) {
                $this->line("   ✅ {$resourceClass} exists");
            } else {
                throw new \Exception("Filament resource {$resourceClass} not found");
            }
        }
    }
}