<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SetupVoucherSystem extends Command
{
    protected $signature = 'vouchers:setup {--fresh : Fresh installation, will reset all data}';
    protected $description = 'Setup complete voucher management system with Filament integration';

    public function handle(): int
    {
        $this->info('🎫 Setting up Voucher Management System...');
        $this->newLine();

        $fresh = $this->option('fresh');

        try {
            // Step 1: Check dependencies
            $this->info('1. Checking dependencies...');
            $this->checkDependencies();

            // Step 2: Run migrations
            $this->info('2. Running voucher migrations...');
            if ($fresh) {
                Artisan::call('migrate:fresh', ['--seed' => true]);
                $this->line('   ✅ Fresh migration completed');
            } else {
                Artisan::call('migrate');
                $this->line('   ✅ Migrations completed');
            }

            // Step 3: Check Google Sheets config
            $this->info('3. Checking Google Sheets configuration...');
            $this->checkGoogleSheetsConfig();

            // Step 4: Test sync
            $this->info('4. Testing voucher sync...');
            $this->testVoucherSync();

            // Step 5: Publish Filament assets (if needed)
            $this->info('5. Publishing Filament assets...');
            Artisan::call('filament:optimize');
            $this->line('   ✅ Filament assets optimized');

            // Success message
            $this->newLine();
            $this->info('🎉 Voucher Management System setup completed successfully!');
            $this->newLine();
            
            $this->displayNextSteps();
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function checkDependencies(): void
    {
        $dependencies = [
            'revolution/laravel-google-sheets' => 'Google Sheets integration',
            'google/apiclient' => 'Google API client',
            'filament/filament' => 'Filament admin panel',
        ];

        foreach ($dependencies as $package => $description) {
            if (!$this->isPackageInstalled($package)) {
                throw new \Exception("Missing dependency: {$package} ({$description})");
            }
            $this->line("   ✅ {$description}");
        }
    }

    private function isPackageInstalled(string $package): bool
    {
        $composerPath = base_path('composer.json');
        if (!File::exists($composerPath)) {
            return false;
        }

        $composer = json_decode(File::get($composerPath), true);
        return isset($composer['require'][$package]) || isset($composer['require-dev'][$package]);
    }

    private function checkGoogleSheetsConfig(): void
    {
        $requiredConfigs = [
            'GOOGLE_VOUCHER_SPREADSHEET_ID' => env('GOOGLE_VOUCHER_SPREADSHEET_ID'),
            'GOOGLE_VOUCHER_SHEET_NAME' => env('GOOGLE_VOUCHER_SHEET_NAME'),
        ];

        foreach ($requiredConfigs as $key => $value) {
            if (empty($value)) {
                $this->warn("   ⚠️  {$key} not set in .env file");
            } else {
                $this->line("   ✅ {$key} configured");
            }
        }

        // Check if Google Sheets config exists
        $configPath = config_path('google-sheets.php');
        if (!File::exists($configPath)) {
            $this->warn('   ⚠️  Google Sheets config file not found');
        } else {
            $this->line('   ✅ Google Sheets config file exists');
        }
    }

    private function testVoucherSync(): void
    {
        try {
            if (empty(env('GOOGLE_VOUCHER_SPREADSHEET_ID'))) {
                $this->warn('   ⚠️  Skipping sync test - no spreadsheet ID configured');
                return;
            }

            // Test if we can reach the spreadsheet
            $spreadsheetId = env('GOOGLE_VOUCHER_SPREADSHEET_ID');
            $csvUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=csv";
            
            $headers = get_headers($csvUrl);
            if (strpos($headers[0], '200') !== false) {
                $this->line('   ✅ Google Spreadsheet is accessible');
            } else {
                $this->warn('   ⚠️  Google Spreadsheet may not be accessible');
            }

        } catch (\Exception $e) {
            $this->warn('   ⚠️  Could not test spreadsheet access: ' . $e->getMessage());
        }
    }

    private function displayNextSteps(): void
    {
        $this->info('📋 Next Steps:');
        $this->line('');
        
        $this->line('1. 🔧 Configure your .env file with voucher spreadsheet settings:');
        $this->line('   GOOGLE_VOUCHER_SPREADSHEET_ID=1eZmdrZZnmWbSVA8iuaGKGwTHFlyIbnr5k9627LZGDpU');
        $this->line('   GOOGLE_VOUCHER_SHEET_NAME=Sheet1');
        $this->line('');
        
        $this->line('2. 📊 Setup your Google Spreadsheet with these columns:');
        $this->line('   A: code_Product | B: voucher_code | C: name_voucher | D: start | E: end');
        $this->line('   F: min_purchase | G: quota | H: claim_per_customer | I: voucher_type');
        $this->line('   J: value | K: discount_max | L: category_customer');
        $this->line('');
        
        $this->line('3. 🚀 Access your admin panel:');
        $this->line('   - Vouchers: ' . url('/admin/vouchers'));
        $this->line('   - Voucher Sync: ' . url('/admin/voucher-sync'));
        $this->line('   - Voucher Usage: ' . url('/admin/voucher-usage'));
        $this->line('');
        
        $this->line('4. 🔄 Run your first sync:');
        $this->line('   php artisan vouchers:sync from_spreadsheet');
        $this->line('');
        
        $this->line('5. ⚡ Setup automatic sync (optional):');
        $this->line('   Add to your cron job: * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1');
    }
}