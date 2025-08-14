<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherSyncLog;
use App\Services\VoucherSyncService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    protected $syncService;

    public function __construct(VoucherSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function index(Request $request)
    {
        $query = Voucher::with(['usages']);

        // Apply filters
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->valid();
                    break;
                case 'expired':
                    $query->where('end_date', '<', now());
                    break;
                case 'pending':
                    $query->where('start_date', '>', now());
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('voucher_code', 'ILIKE', "%$search%")
                  ->orWhere('name_voucher', 'ILIKE', "%$search%");
            });
        }

        $vouchers = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function show(Voucher $voucher)
    {
        $voucher->load(['usages.voucher']);
        $usageStats = [
            'total_usage' => $voucher->usages->count(),
            'unique_customers' => $voucher->usages->unique('customer_id')->count(),
            'total_discount' => $voucher->usages->sum('discount_amount'),
            'avg_order_value' => $voucher->usages->avg('order_total'),
        ];

        return view('admin.vouchers.show', compact('voucher', 'usageStats'));
    }

    public function sync(Request $request)
    {
        try {
            $direction = $request->get('direction', 'from_spreadsheet');
            
            if ($direction === 'from_spreadsheet') {
                $result = $this->syncService->syncFromSpreadsheet();
                $message = 'Vouchers synced from spreadsheet successfully';
            } else {
                $result = $this->syncService->syncToSpreadsheet();
                $message = 'Vouchers synced to spreadsheet successfully';
            }

            return back()->with('success', $message . ". Processed: {$result['processed']}, Errors: {$result['errors']}");

        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function syncLogs()
    {
        $logs = VoucherSyncLog::orderBy('synced_at', 'desc')->paginate(20);
        return view('admin.vouchers.sync-logs', compact('logs'));
    }

    public function toggle(Voucher $voucher)
    {
        $voucher->update(['is_active' => !$voucher->is_active]);
        
        $status = $voucher->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Voucher {$status} successfully");
    }
}
