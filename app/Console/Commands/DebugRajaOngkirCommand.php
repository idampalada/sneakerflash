<?php
// Debug Command untuk cek format response yang benar

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugRajaOngkirCommand extends Command
{
    protected $signature = 'rajaongkir:debug {--api-key=}';
    protected $description = 'Debug RajaOngkir V2 response format';

    private $apiKey;
    private $baseUrl = 'https://rajaongkir.komerce.id/api/v1';

    public function handle()
    {
        $this->apiKey = $this->option('api-key') ?: '8MZVaA6pc8c11707407345e5Ad0DK9eU';
        
        $this->info('🔍 Debug RajaOngkir V2 Response Format');
        $this->newLine();

        // Debug 1: Provinces format
        $this->debugProvinces();
        
        // Debug 2: Cities format
        $this->debugCities();
        
        // Debug 3: Search format
        $this->debugSearch();
        
        // Debug 4: Cost calculation format
        $this->debugCostCalculation();
    }

    private function debugProvinces()
    {
        $this->info('🌍 Debug Provinces Response Format:');
        
        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->apiKey
            ])->get($this->baseUrl . '/destination/province');

            $this->line('Status: ' . $response->status());
            
            if ($response->successful()) {
                $data = $response->json();
                
                $this->line('Raw Response Structure:');
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
                
                if (isset($data['data']) && !empty($data['data'])) {
                    $this->line('First Province Keys:');
                    $firstProvince = $data['data'][0];
                    $this->line('Keys: ' . implode(', ', array_keys($firstProvince)));
                    $this->line('Sample: ' . json_encode($firstProvince, JSON_PRETTY_PRINT));
                }
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function debugCities()
    {
        $this->info('🏙️ Debug Cities Response Format:');
        
        // Try different endpoints
        $endpoints = [
            '/destination/city?province=6',
            '/destination/cities?province=6',
            '/city?province=6',
            '/cities?province=6'
        ];
        
        foreach ($endpoints as $endpoint) {
            try {
                $this->line("Testing endpoint: {$endpoint}");
                
                $response = Http::timeout(10)->withHeaders([
                    'key' => $this->apiKey
                ])->get($this->baseUrl . $endpoint);

                $this->line('Status: ' . $response->status());
                
                if ($response->successful()) {
                    $data = $response->json();
                    $this->line('✅ SUCCESS! Structure:');
                    $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    break;
                } else {
                    $this->line('❌ Failed');
                }
            } catch (\Exception $e) {
                $this->line('❌ Exception: ' . $e->getMessage());
            }
        }
        
        $this->newLine();
    }

    private function debugSearch()
    {
        $this->info('🔍 Debug Search Response Format:');
        
        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->apiKey
            ])->get($this->baseUrl . '/destination/domestic-destination', [
                'search' => 'jakarta',
                'limit' => 1
            ]);

            $this->line('Status: ' . $response->status());
            
            if ($response->successful()) {
                $data = $response->json();
                
                $this->line('Search Response Structure:');
                $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                if (isset($data['data']) && !empty($data['data'])) {
                    $this->line('First Result Keys:');
                    $firstResult = $data['data'][0];
                    $this->line('Keys: ' . implode(', ', array_keys($firstResult)));
                }
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function debugCostCalculation()
    {
        $this->info('🚚 Debug Cost Calculation:');
        
        // Try different cost endpoints and parameters
        $testCases = [
            ['url' => '/cost', 'data' => ['origin' => 152, 'destination' => 22, 'weight' => 1000, 'courier' => 'jne']],
            ['url' => '/cost', 'data' => ['origin' => '152', 'destination' => '22', 'weight' => 1000, 'courier' => 'jne', 'origin_type' => 'district', 'destination_type' => 'district']],
            ['url' => '/shipping/cost', 'data' => ['origin' => 152, 'destination' => 22, 'weight' => 1000, 'courier' => 'jne']],
        ];
        
        foreach ($testCases as $case) {
            try {
                $this->line("Testing: {$case['url']} with data: " . json_encode($case['data']));
                
                $response = Http::timeout(15)->withHeaders([
                    'key' => $this->apiKey
                ])->post($this->baseUrl . $case['url'], $case['data']);

                $this->line('Status: ' . $response->status());
                
                if ($response->successful()) {
                    $data = $response->json();
                    $this->line('✅ SUCCESS! Response:');
                    $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    break;
                } else {
                    $this->line('❌ Failed. Response: ' . $response->body());
                }
            } catch (\Exception $e) {
                $this->line('❌ Exception: ' . $e->getMessage());
            }
        }
    }
}