<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Sync Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Google Spreadsheet</h3>
                        <p class="text-sm text-gray-500">Source of truth for voucher data</p>
                        <a href="https://docs.google.com/spreadsheets/d/{{ config('google-sheets.voucher.spreadsheet_id') }}" 
                           target="_blank" 
                           class="text-sm text-blue-600 hover:text-blue-800">
                            Open Spreadsheet →
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7M4 7l2-3h12l2 3M4 7h16"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Database</h3>
                        <p class="text-sm text-gray-500">{{ \App\Models\Voucher::count() }} vouchers stored</p>
                        <p class="text-sm text-gray-500">{{ \App\Models\Voucher::where('is_active', true)->count() }} active vouchers</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Last Sync</h3>
                        @php
                            $lastSync = \App\Models\VoucherSyncLog::latest('synced_at')->first();
                        @endphp
                        @if($lastSync)
                            <p class="text-sm text-gray-500">{{ $lastSync->synced_at->diffForHumans() }}</p>
                            <p class="text-sm text-{{ $lastSync->status === 'success' ? 'green' : 'red' }}-600">
                                Status: {{ ucfirst($lastSync->status) }}
                            </p>
                        @else
                            <p class="text-sm text-gray-500">No sync performed yet</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-medium text-blue-900 mb-4">How Voucher Sync Works</h3>
            <div class="space-y-3 text-sm text-blue-800">
                <div class="flex items-start">
                    <span class="font-semibold mr-2">1.</span>
                    <span>Update voucher data in your Google Spreadsheet with the correct column format</span>
                </div>
                <div class="flex items-start">
                    <span class="font-semibold mr-2">2.</span>
                    <span>Click "Sync from Spreadsheet" to pull the latest data into your database</span>
                </div>
                <div class="flex items-start">
                    <span class="font-semibold mr-2">3.</span>
                    <span>New vouchers will be created and existing ones will be updated automatically</span>
                </div>
                <div class="flex items-start">
                    <span class="font-semibold mr-2">4.</span>
                    <span>Monitor sync logs below to track success and troubleshoot any issues</span>
                </div>
            </div>
        </div>

        <!-- Sync Logs Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Sync History</h3>
                <p class="text-sm text-gray-500">Track all synchronization activities</p>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
