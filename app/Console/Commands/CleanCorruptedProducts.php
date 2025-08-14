<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CleanCorruptedProducts extends Command
{
    protected $signature = 'clean:corrupted-products {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Clean products with corrupted data from sync issues';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('🧹 ' . ($dryRun ? 'Analyzing' : 'Cleaning') . ' corrupted products...');
        $this->newLine();

        // Find corrupted products
        $corruptedProducts = Product::where(function($query) {
            $query->where('name', 'like', 'ya%')  // Names starting with "ya"
                  ->orWhere('name', '=', 'ya"')    // Exact match "ya""
                  ->orWhere('brand', 'like', '%pastikan%')  // Brand containing description text
                  ->orWhere('brand', 'like', '%size dan produk%')  // More description text
                  ->orWhereRaw('CAST(sku AS TEXT) ~ \'^[0-9.]+$\'') // SKU that's just numbers
                  ->orWhere('stock_quantity', 0)  // Zero stock (optional)
                  ->orWhereRaw('LENGTH(name) < 3'); // Very short names
        })->get();

        if ($corruptedProducts->isEmpty()) {
            $this->info('✅ No corrupted products found!');
            return 0;
        }

        $this->warn("Found {$corruptedProducts->count()} corrupted products:");
        $this->newLine();

        // Show corrupted products
        $this->table(
            ['ID', 'Name', 'SKU', 'Brand', 'Price', 'Created'],
            $corruptedProducts->map(function($product) {
                return [
                    $product->id,
                    Str::limit($product->name ?? 'No name', 20),
                    $product->sku ?? 'No SKU',
                    Str::limit($product->brand ?? 'No brand', 30),
                    'Rp ' . number_format($product->price ?? 0),
                    $product->created_at->format('Y-m-d H:i')
                ];
            })->toArray()
        );

        $this->newLine();

        if ($dryRun) {
            $this->info('🔍 This was a dry run. Use without --dry-run to actually delete these products.');
            return 0;
        }

        if (!$this->confirm('Are you sure you want to delete these corrupted products?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Delete corrupted products
        $deletedCount = 0;
        foreach ($corruptedProducts as $product) {
            try {
                $product->delete();
                $deletedCount++;
                $this->line("Deleted: {$product->name} (ID: {$product->id})");
            } catch (\Exception $e) {
                $this->error("Failed to delete product {$product->id}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("✅ Successfully deleted {$deletedCount} corrupted products.");
        
        // Show remaining products count
        $remainingCount = Product::count();
        $this->info("📊 Remaining products in database: {$remainingCount}");

        return 0;
    }
}