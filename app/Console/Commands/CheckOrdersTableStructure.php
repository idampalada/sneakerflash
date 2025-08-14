<?php

// Command untuk check table structure orders
// File: app/Console/Commands/CheckOrdersTableStructure.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CheckOrdersTableStructure extends Command
{
    protected $signature = 'check:orders-table';
    protected $description = 'Check the structure of orders table to see what columns exist';

    public function handle()
    {
        $this->info('🔍 Checking orders table structure...');
        
        try {
            // Check if table exists
            if (!Schema::hasTable('orders')) {
                $this->error('❌ Orders table does not exist!');
                return 1;
            }
            
            $this->info('✅ Orders table exists');
            
            // Get column listing
            $columns = Schema::getColumnListing('orders');
            
            $this->info('📋 Columns in orders table:');
            $this->table(['Column Name'], array_map(function($col) {
                return [$col];
            }, $columns));
            
            // Get detailed column information using DB
            $this->info('📊 Detailed column information:');
            
            if (config('database.default') === 'pgsql') {
                // PostgreSQL
                $columnDetails = DB::select("
                    SELECT 
                        column_name,
                        data_type,
                        is_nullable,
                        column_default
                    FROM information_schema.columns 
                    WHERE table_name = 'orders'
                    ORDER BY ordinal_position
                ");
            } else {
                // MySQL
                $columnDetails = DB::select("
                    SHOW COLUMNS FROM orders
                ");
            }
            
            $tableData = [];
            foreach ($columnDetails as $column) {
                if (config('database.default') === 'pgsql') {
                    $tableData[] = [
                        'Column' => $column->column_name,
                        'Type' => $column->data_type,
                        'Null' => $column->is_nullable,
                        'Default' => $column->column_default ?: 'NULL'
                    ];
                } else {
                    $tableData[] = [
                        'Column' => $column->Field,
                        'Type' => $column->Type,
                        'Null' => $column->Null,
                        'Default' => $column->Default ?: 'NULL'
                    ];
                }
            }
            
            $this->table(['Column', 'Type', 'Null', 'Default'], $tableData);
            
            // Check for problematic fields
            $this->info('🔍 Checking for potentially problematic fields...');
            
            $problematicFields = [
                'billing_address',
                'payment_status', 
                'order_status',
                'shipping_amount'
            ];
            
            $foundProblematic = [];
            foreach ($problematicFields as $field) {
                if (in_array($field, $columns)) {
                    $foundProblematic[] = $field;
                }
            }
            
            if (!empty($foundProblematic)) {
                $this->warn('⚠️ Found potentially problematic fields:');
                foreach ($foundProblematic as $field) {
                    $this->warn("   - {$field}");
                }
            } else {
                $this->info('✅ No problematic fields found');
            }
            
            // Check for required fields that might be missing
            $requiredFields = [
                'order_number',
                'customer_name', 
                'customer_email',
                'status',
                'total_amount',
                'payment_method'
            ];
            
            $this->info('🔍 Checking for required fields...');
            $missingRequired = [];
            foreach ($requiredFields as $field) {
                if (!in_array($field, $columns)) {
                    $missingRequired[] = $field;
                }
            }
            
            if (!empty($missingRequired)) {
                $this->error('❌ Missing required fields:');
                foreach ($missingRequired as $field) {
                    $this->error("   - {$field}");
                }
            } else {
                $this->info('✅ All required fields are present');
            }
            
            $this->info('');
            $this->info('💡 Summary:');
            $this->info("   Total columns: " . count($columns));
            $this->info("   Problematic fields: " . count($foundProblematic));
            $this->info("   Missing required: " . count($missingRequired));
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error checking table structure: ' . $e->getMessage());
            return 1;
        }
    }
}