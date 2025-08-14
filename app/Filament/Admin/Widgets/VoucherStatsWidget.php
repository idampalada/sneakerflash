<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Voucher;
use App\Models\VoucherUsage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VoucherStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalVouchers = Voucher::count();
        $activeVouchers = Voucher::where('is_active', true)
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now())
                                ->whereRaw('quota > total_used')
                                ->count();
        
        $totalUsage = VoucherUsage::count();
        $totalDiscount = VoucherUsage::sum('discount_amount');
        $usageToday = VoucherUsage::whereDate('used_at', today())->count();
        $usageThisMonth = VoucherUsage::whereMonth('used_at', now()->month)
                                    ->whereYear('used_at', now()->year)
                                    ->count();

        return [
            Stat::make('Total Vouchers', $totalVouchers)
                ->description('All vouchers in system')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary'),

            Stat::make('Active Vouchers', $activeVouchers)
                ->description('Currently usable')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Usage', $totalUsage)
                ->description($usageToday . ' used today')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Total Discount Given', 'Rp ' . number_format($totalDiscount, 0, ',', '.'))
                ->description($usageThisMonth . ' uses this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}
