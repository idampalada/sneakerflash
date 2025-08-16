<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUserSpendingCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'users:sync-spending 
                           {--user_id= : Sync specific user ID}
                           {--chunk=100 : Number of users to process per chunk}
                           {--force : Force sync even if recently updated}
                           {--bulk : Use bulk PostgreSQL update (faster)}';

    /**
     * The console command description.
     */
    protected $description = 'Sync user spending statistics from orders table (PostgreSQL optimized)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $userId = $this->option('user_id');
        $chunkSize = (int) $this->option('chunk');
        $force = $this->option('force');
        $bulk = $this->option('bulk');

        $this->info('🚀 Starting PostgreSQL user spending sync...');
        $this->newLine();

        if ($userId) {
            // Sync specific user
            $this->syncSpecificUser($userId);
        } elseif ($bulk) {
            // Bulk PostgreSQL update (super fast)
            $this->bulkSyncPostgreSQL($force);
        } else {
            // Sync all users with Eloquent (slower but safer)
            $this->syncAllUsers($chunkSize, $force);
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->newLine();
        $this->info("✅ Sync completed in {$duration} seconds");
    }

    /**
     * Bulk sync using raw PostgreSQL (super fast for large datasets)
     */
    private function bulkSyncPostgreSQL($force)
    {
        $this->info('💾 Using bulk PostgreSQL update (fastest method)...');
        
        $whereClause = '';
        if (!$force) {
            $whereClause = "WHERE u.spending_updated_at IS NULL 
                           OR u.spending_updated_at < NOW() - INTERVAL '1 day'
                           OR EXISTS (
                               SELECT 1 FROM orders o 
                               WHERE o.user_id = u.id 
                               AND o.updated_at > u.spending_updated_at
                           )";
        }
        
        // PostgreSQL bulk update query - UPDATED to include tier calculation
        $sql = "
            UPDATE users u SET 
                total_spent = COALESCE(order_stats.total_spent, 0),
                total_orders = COALESCE(order_stats.total_orders, 0),
                customer_tier = CASE 
                    WHEN COALESCE(order_stats.total_spent, 0) >= 10000000 THEN 'platinum'
                    WHEN COALESCE(order_stats.total_spent, 0) >= 5000000 THEN 'gold'
                    WHEN COALESCE(order_stats.total_spent, 0) >= 1000000 THEN 'silver'
                    WHEN COALESCE(order_stats.total_spent, 0) > 0 THEN 'basic'
                    ELSE 'new'
                END,
                spending_updated_at = NOW()
            FROM (
                SELECT 
                    user_id,
                    SUM(total_amount) as total_spent,
                    COUNT(*) as total_orders
                FROM orders 
                WHERE status IN ('paid', 'processing', 'shipped', 'delivered') 
                AND user_id IS NOT NULL
                GROUP BY user_id
            ) order_stats
            WHERE u.id = order_stats.user_id
            {$whereClause}
        ";
        
        $this->info('🔄 Executing bulk PostgreSQL update (including processing/shipped/delivered)...');
        $affectedRows = DB::update($sql);
        
        $this->info("✅ Updated {$affectedRows} users using bulk PostgreSQL update");
        
        // Show results
        $this->showTierDistribution();
    }

    /**
     * Sync specific user
     */
    private function syncSpecificUser($userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ User with ID {$userId} not found");
            return;
        }

        $this->info("🔄 Syncing user: {$user->name} (ID: {$userId})");
        
        $oldSpent = $user->total_spent ?? 0;
        $oldOrders = $user->total_orders ?? 0;
        
        // PostgreSQL specific query - UPDATED to include all paid statuses
        $stats = DB::select("
            SELECT 
                COALESCE(SUM(total_amount), 0) as total_spent,
                COALESCE(COUNT(*), 0) as total_orders
            FROM orders 
            WHERE user_id = ? AND status IN ('paid', 'processing', 'shipped', 'delivered')
        ", [$userId]);
        
        $newSpent = $stats[0]->total_spent ?? 0;
        $newOrders = $stats[0]->total_orders ?? 0;
        
        // Calculate tier
        $newTier = $this->calculateTierFromSpending($newSpent);
        
        $user->update([
            'total_spent' => $newSpent,
            'total_orders' => $newOrders,
            'customer_tier' => $newTier,
            'spending_updated_at' => now()
        ]);
        
        $this->table(
            ['Field', 'Old Value', 'New Value', 'Change'],
            [
                [
                    'Total Spent',
                    'Rp ' . number_format($oldSpent, 0, ',', '.'),
                    'Rp ' . number_format($newSpent, 0, ',', '.'),
                    'Rp ' . number_format($newSpent - $oldSpent, 0, ',', '.')
                ],
                [
                    'Total Orders',
                    $oldOrders,
                    $newOrders,
                    '+' . ($newOrders - $oldOrders)
                ],
                [
                    'Customer Tier',
                    $this->getTierFromSpending($oldSpent),
                    $this->getTierLabelFromTier($newTier),
                    $oldSpent != $newSpent ? '📈 Changed' : '✅ Same'
                ],
                [
                    'Stored in DB',
                    'Calculated only',
                    'customer_tier column',
                    '💾 Now Stored'
                ],
                [
                    'Counted Statuses',
                    'paid only',
                    'paid, processing, shipped, delivered',
                    '🔄 Updated Logic'
                ]
            ]
        );
        
        $this->info("✅ User {$userId} synced successfully");
    }

    /**
     * Sync all users with Eloquent
     */
    private function syncAllUsers($chunkSize, $force)
    {
        $query = User::query();
        
        if (!$force) {
            // PostgreSQL specific: Use interval for date comparison
            $query->where(function($q) {
                $q->whereNull('spending_updated_at')
                  ->orWhereRaw("spending_updated_at < NOW() - INTERVAL '1 day'")
                  ->orWhereExists(function($subQuery) {
                      $subQuery->select(DB::raw(1))
                               ->from('orders')
                               ->whereColumn('orders.user_id', 'users.id')
                               ->whereRaw('orders.updated_at > users.spending_updated_at');
                  });
            });
        }
        
        $totalUsers = $query->count();
        
        if ($totalUsers === 0) {
            $this->info('✅ All users are already up to date!');
            return;
        }
        
        $this->info("📊 Found {$totalUsers} users to sync");
        $this->newLine();
        
        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->setFormat('Processing: %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('Starting...');
        
        $processed = 0;
        $updated = 0;
        $errors = 0;
        
        $tierChanges = [
            'platinum' => 0,
            'gold' => 0,
            'silver' => 0,
            'basic' => 0,
            'new' => 0
        ];

        $query->chunk($chunkSize, function ($users) use ($progressBar, &$processed, &$updated, &$errors, &$tierChanges) {
            foreach ($users as $user) {
                try {
                    $oldTier = $this->getTierFromSpending($user->total_spent ?? 0);
                    $oldSpent = $user->total_spent ?? 0;
                    
                    // PostgreSQL specific: Use single query - UPDATED to include all paid statuses
                    $stats = DB::select("
                        SELECT 
                            COALESCE(SUM(total_amount), 0) as total_spent,
                            COALESCE(COUNT(*), 0) as total_orders
                        FROM orders 
                        WHERE user_id = ? AND status IN ('paid', 'processing', 'shipped', 'delivered')
                    ", [$user->id]);
                    
                    $newSpent = $stats[0]->total_spent ?? 0;
                    $newOrders = $stats[0]->total_orders ?? 0;
                    
                    // Calculate tier
                    $newTier = $this->calculateTierFromSpending($newSpent);
                    
                    $user->update([
                        'total_spent' => $newSpent,
                        'total_orders' => $newOrders,
                        'customer_tier' => $newTier,
                        'spending_updated_at' => now()
                    ]);
                    
                    $newTierName = $this->getTierFromSpending($newSpent);
                    
                    // Track tier changes
                    if ($oldTier !== $newTierName) {
                        $tierChanges[$newTierName]++;
                    }
                    
                    // Count as updated if spending changed
                    if ($oldSpent != $newSpent) {
                        $updated++;
                    }
                    
                    $processed++;
                    $progressBar->setMessage("User: {$user->name}");
                    $progressBar->advance();
                    
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("❌ Error syncing user {$user->id}: " . $e->getMessage());
                }
            }
        });
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Show results
        $this->info("📈 Sync Results:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $processed],
                ['Actually Updated', $updated],
                ['Errors', $errors],
                ['Success Rate', round(($processed - $errors) / $processed * 100, 1) . '%']
            ]
        );
        
        // Show tier changes
        $totalTierChanges = array_sum($tierChanges);
        if ($totalTierChanges > 0) {
            $this->newLine();
            $this->info("🏆 Tier Changes:");
            foreach ($tierChanges as $tier => $count) {
                if ($count > 0) {
                    $emoji = match($tier) {
                        'platinum' => '💎',
                        'gold' => '🥇',
                        'silver' => '🥈',
                        'basic' => '🥉',
                        'new' => '🆕'
                    };
                    $this->line("  {$emoji} " . ucfirst($tier) . ": {$count} users");
                }
            }
        }
        
        // Show current tier distribution
        $this->showTierDistribution();
    }

    /**
     * Calculate tier from spending amount
     */
    private function calculateTierFromSpending($spending)
    {
        if ($spending >= 10000000) return 'platinum';
        if ($spending >= 5000000) return 'gold';
        if ($spending >= 1000000) return 'silver';
        if ($spending > 0) return 'basic';
        return 'new';
    }

    /**
     * Get tier from spending amount (for display)
     */
    private function getTierFromSpending($spending)
    {
        return $this->calculateTierFromSpending($spending);
    }

    /**
     * Get tier label from tier code
     */
    private function getTierLabelFromTier($tier)
    {
        return match($tier) {
            'platinum' => 'Platinum Member',
            'gold' => 'Gold Member',
            'silver' => 'Silver Member',
            'basic' => 'basic Member',
            'new' => 'New Customer'
        };
    }

    /**
     * Show current tier distribution (PostgreSQL optimized)
     */
    private function showTierDistribution()
    {
        $this->newLine();
        $this->info("📊 Current Tier Distribution (PostgreSQL):");
        
        // PostgreSQL specific query with CASE WHEN
        $distribution = DB::select("
            SELECT 
                CASE 
                    WHEN total_spent >= 10000000 THEN 'platinum'
                    WHEN total_spent >= 5000000 THEN 'gold'
                    WHEN total_spent >= 1000000 THEN 'silver'
                    WHEN total_spent > 0 THEN 'bronze'
                    ELSE 'new'
                END as tier,
                COUNT(*) as count
            FROM users 
            GROUP BY 
                CASE 
                    WHEN total_spent >= 10000000 THEN 'platinum'
                    WHEN total_spent >= 5000000 THEN 'gold'
                    WHEN total_spent >= 1000000 THEN 'silver'
                    WHEN total_spent > 0 THEN 'bronze'
                    ELSE 'new'
                END
            ORDER BY 
                CASE 
                    WHEN CASE 
                        WHEN total_spent >= 10000000 THEN 'platinum'
                        WHEN total_spent >= 5000000 THEN 'gold'
                        WHEN total_spent >= 1000000 THEN 'silver'
                        WHEN total_spent > 0 THEN 'bronze'
                        ELSE 'new'
                    END = 'platinum' THEN 5
                    WHEN CASE 
                        WHEN total_spent >= 10000000 THEN 'platinum'
                        WHEN total_spent >= 5000000 THEN 'gold'
                        WHEN total_spent >= 1000000 THEN 'silver'
                        WHEN total_spent > 0 THEN 'bronze'
                        ELSE 'new'
                    END = 'gold' THEN 4
                    WHEN CASE 
                        WHEN total_spent >= 10000000 THEN 'platinum'
                        WHEN total_spent >= 5000000 THEN 'gold'
                        WHEN total_spent >= 1000000 THEN 'silver'
                        WHEN total_spent > 0 THEN 'bronze'
                        ELSE 'new'
                    END = 'silver' THEN 3
                    WHEN CASE 
                        WHEN total_spent >= 10000000 THEN 'platinum'
                        WHEN total_spent >= 5000000 THEN 'gold'
                        WHEN total_spent >= 1000000 THEN 'silver'
                        WHEN total_spent > 0 THEN 'bronze'
                        ELSE 'new'
                    END = 'bronze' THEN 2
                    ELSE 1
                END DESC
        ");
        
        $total = array_sum(array_column($distribution, 'count'));
        
        foreach ($distribution as $tier) {
            $percentage = $total > 0 ? round($tier->count / $total * 100, 1) : 0;
            $emoji = match($tier->tier) {
                'platinum' => '💎',
                'gold' => '🥇',
                'silver' => '🥈',
                'bronze' => '🥉',
                'new' => '🆕'
            };
            
            $this->line("  {$emoji} " . str_pad(ucfirst($tier->tier), 8) . ": {$tier->count} users ({$percentage}%)");
        }
        
        $this->newLine();
        $this->info("💰 PostgreSQL Spending Statistics:");
        
        // PostgreSQL aggregation functions
        $stats = DB::select("
            SELECT 
                COUNT(*) as total_customers,
                ROUND(AVG(total_spent), 2) as avg_spending,
                SUM(total_spent) as total_revenue,
                MAX(total_spent) as highest_spender,
                MIN(CASE WHEN total_spent > 0 THEN total_spent END) as lowest_spender,
                COUNT(CASE WHEN total_spent >= 5000000 THEN 1 END) as high_value_customers,
                COUNT(CASE WHEN total_orders >= 5 THEN 1 END) as frequent_buyers
            FROM users
        ")[0];
            
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Customers', number_format($stats->total_customers)],
                ['High Value Customers', number_format($stats->high_value_customers)],
                ['Frequent Buyers', number_format($stats->frequent_buyers)],
                ['Average Spending', 'Rp ' . number_format($stats->avg_spending ?? 0, 0, ',', '.')],
                ['Total Revenue', 'Rp ' . number_format($stats->total_revenue ?? 0, 0, ',', '.')],
                ['Highest Spender', 'Rp ' . number_format($stats->highest_spender ?? 0, 0, ',', '.')],
                ['Lowest Spender', 'Rp ' . number_format($stats->lowest_spender ?? 0, 0, ',', '.')]
            ]
        );
    }
}