<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetsSync;
use Illuminate\Console\Command;

class TestNewSyncLogic extends Command
{
    protected $signature = 'test:new-sync {--preview : Only preview data without syncing}';
    protected $description = 'Test new sync logic where each SKU becomes separate product';

    public function handle()
    {
        $this->info('🧪 Testing New Sync Logic - Each SKU = Separate Product');
        $this->newLine();

        try {
            $syncService = new GoogleSheetsSync();
            
            if ($this->option('preview')) {
                $this->info('📋 Previewing data...');
                $result = $syncService->previewData(10);
                
                if ($result['success']) {
                    $this->info("✅ Found {$result['total_rows']} total rows");
                    $this->info("📊 Preview of {$result['preview_count']} products:");
                    $this->newLine();
                    
                    $this->table(
                        ['Name', 'Brand', 'SKU Parent', 'SKU', 'Size', 'Price', 'Stock'],
                        array_map(function($item) {
                            return [
                                substr($item['name'], 0, 30),
                                $item['brand'],
                                $item['sku_parent'],
                                $item['sku'],
                                $item['size'],
                                'Rp ' . number_format($item['price']),
                                $item['stock']
                            ];
                        }, $result['data'])
                    );
                    
                    $this->newLine();
                    $this->info("🔍 Analysis:");
                    $uniqueSkuParents = array_unique(array_column($result['data'], 'sku_parent'));
                    $uniqueSkus = array_unique(array_column($result['data'], 'sku'));
                    
                    $this->line("• Unique SKU Parents: " . count($uniqueSkuParents));
                    $this->line("• Unique SKUs: " . count($uniqueSkus));
                    $this->line("• Total Products (Old Logic): " . count($uniqueSkuParents));
                    $this->line("• Total Products (New Logic): " . count($uniqueSkus));
                    
                    // Check current database state
                    $currentProducts = \App\Models\Product::count();
                    $this->line("• Current Products in DB: " . $currentProducts);
                    
                    if ($currentProducts > 0) {
                        $existingSkus = \App\Models\Product::pluck('sku')->toArray();
                        $toDelete = array_diff($existingSkus, $uniqueSkus);
                        $this->line("• Products to be DELETED: " . count($toDelete));
                        $this->line("• Final Product Count: " . count($uniqueSkus));
                    }
                    
                } else {
                    $this->error("❌ Preview failed: " . $result['message']);
                    return 1;
                }
                
            } else {
                $this->warn('⚠️  This will perform actual sync. Continue?');
                if (!$this->confirm('Proceed with sync?')) {
                    $this->info('Sync cancelled.');
                    return 0;
                }
                
                $this->info('🔄 Starting sync...');
                $result = $syncService->syncProducts(['clean_old_data' => true]);
                
                if ($result['success']) {
                    $stats = $result['stats'];
                    $this->info('✅ Sync completed successfully!');
                    $this->newLine();
                    
                    $this->table(
                        ['Metric', 'Count'],
                        [
                            ['Created Products', $stats['created']],
                            ['Updated Products', $stats['updated']],
                            ['Skipped Rows', $stats['skipped']],
                            ['Errors', $stats['errors']],
                        ]
                    );
                    
                    if (!empty($result['errors'])) {
                        $this->newLine();
                        $this->warn('⚠️ Errors encountered:');
                        foreach (array_slice($result['errors'], 0, 5) as $error) {
                            $this->line('• Row ' . ($error['row'] ?? 'Unknown') . ': ' . $error['error']);
                        }
                    }
                    
                } else {
                    $this->error('❌ Sync failed: ' . $result['message']);
                    return 1;
                }
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('💥 Command failed: ' . $e->getMessage());
            return 1;
        }
    }
}