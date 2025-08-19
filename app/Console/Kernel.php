<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {

            $schedule->call(fn() => app(\App\Http\Controllers\GineeSyncController::class)->pullProducts())
             ->everyFifteenMinutes();

    $schedule->call(fn() => app(\App\Http\Controllers\GineeSyncController::class)->pushStock())
             ->everyFiveMinutes();

                 $schedule->call(function () {
        request()->merge(['warehouseId' => 'WW64BE1DB61890960001D39C7D']);
        app(\App\Http\Controllers\Frontend\InventoryPushController::class)->pushOnHand(request());
    })->everyFiveMinutes();
        // Existing product sync (if you have it)
        $schedule->command('sync:google-sheets')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();

        // NEW: Voucher sync every 30 minutes
        $schedule->command('vouchers:sync from_spreadsheet')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/voucher-sync.log'));

        // NEW: Daily backup sync to spreadsheet
        $schedule->command('vouchers:sync to_spreadsheet')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/voucher-backup.log'));

        // NEW: Clean old sync logs (keep last 30 days)
        $schedule->call(function () {
            \App\Models\VoucherSyncLog::where('synced_at', '<', now()->subDays(30))->delete();
        })->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
