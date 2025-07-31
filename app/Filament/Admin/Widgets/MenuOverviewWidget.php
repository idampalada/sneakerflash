<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use App\Models\Category;
use App\Models\MenuNavigation;
use Filament\Widgets\ChartWidget;

class MenuOverviewWidget extends ChartWidget
{
    protected static ?string $heading = 'Products Distribution by Menu';
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $data = [
            'MENS' => $this->getMensCount(),
            'WOMENS' => $this->getWomensCount(),
            'KIDS' => $this->getKidsCount(),
            'ACCESSORIES' => $this->getAccessoriesCount(),
            'SALE' => $this->getSaleCount(),
            'BRAND' => $this->getBrandCount(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Products Count',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#3B82F6', // blue for mens
                        '#EC4899', // pink for womens
                        '#F59E0B', // yellow for kids
                        '#10B981', // green for accessories
                        '#EF4444', // red for sale
                        '#6366F1', // indigo for brand
                    ],
                    'borderColor' => [
                        '#1D4ED8',
                        '#BE185D',
                        '#D97706',
                        '#047857',
                        '#DC2626',
                        '#4338CA',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
    }

    // Helper methods to avoid scope issues
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