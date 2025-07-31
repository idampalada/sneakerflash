<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use App\Models\MenuNavigation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MenuStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            Stat::make('MENS Products', $this->getMensCount())
                ->description('Active mens products')
                ->descriptionIcon('heroicon-m-user')
                ->color('blue'),

            Stat::make('WOMENS Products', $this->getWomensCount())
                ->description('Active womens products')
                ->descriptionIcon('heroicon-m-user')
                ->color('pink'),

            Stat::make('KIDS Products', $this->getKidsCount())
                ->description('Active kids products')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('yellow'),

            Stat::make('ACCESSORIES', $this->getAccessoriesCount())
                ->description('Active accessories')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('green'),

            Stat::make('SALE Products', $this->getSaleCount())
                ->description('Products on sale')
                ->descriptionIcon('heroicon-m-tag')
                ->color('red'),

            Stat::make('BRAND Products', $this->getBrandCount())
                ->description('Products with brands')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('indigo'),
        ];
    }

    // Helper methods
    private function getMensCount(): int
    {
        return Product::where('is_active', true)->where(function ($q) {
            $q->where('gender_target', 'mens')->orWhere('gender_target', 'unisex');
        })->count();
    }

    private function getWomensCount(): int
    {
        return Product::where('is_active', true)->where(function ($q) {
            $q->where('gender_target', 'womens')->orWhere('gender_target', 'unisex');
        })->count();
    }

    private function getKidsCount(): int
    {
        return Product::where('is_active', true)->where('gender_target', 'kids')->count();
    }

    private function getAccessoriesCount(): int
    {
        return Product::where('is_active', true)->whereIn('product_type', [
            'backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories'
        ])->count();
    }

    private function getSaleCount(): int
    {
        return Product::where('is_active', true)
            ->whereNotNull('sale_price')
            ->whereRaw('sale_price < price')
            ->count();
    }

    private function getBrandCount(): int
    {
        return Product::where('is_active', true)->whereNotNull('brand')->count();
    }
}