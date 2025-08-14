<?php

// =====================================
// 8. CONTROLLER - VOUCHER API
// File: app/Http/Controllers/Api/VoucherController.php
// =====================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\VoucherSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VoucherController extends Controller
{
    protected $syncService;

    public function __construct(VoucherSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Get vouchers with filtering
     */
    public function index(Request $request)
    {
        try {
            $query = Voucher::with(['usages']);

            // Apply filters
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'active':
                        $query->valid();
                        break;
                    case 'expired':
                        $query->where(function ($q) {
                            $q->where('end_date', '<', now())
                              ->orWhere('is_active', false);
                        });
                        break;
                    case 'pending':
                        $query->where('start_date', '>', now());
                        break;
                }
            }

            if ($request->has('category')) {
                $query->forCategory($request->category);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('voucher_code', 'ILIKE', "%$search%")
                      ->orWhere('name_voucher', 'ILIKE', "%$search%");
                });
            }

            $vouchers = $query->orderBy('created_at', 'desc')
                             ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $vouchers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vouchers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate voucher for usage
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'voucher_code' => 'required|string',
            'customer_id' => 'nullable|string',
            'order_total' => 'numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $voucher = Voucher::where('voucher_code', $request->voucher_code)->first();

            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher code not found'
                ], 404);
            }

            $validation = $voucher->isValidForUser(
                $request->customer_id,
                $request->order_total ?? 0
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'voucher_id' => $voucher->id,
                    'voucher_code' => $voucher->voucher_code,
                    'name' => $voucher->name_voucher,
                    'valid' => $validation['valid'],
                    'message' => $validation['message'] ?? 'Voucher is valid',
                    'discount_amount' => $validation['discount'] ?? 0,
                    'voucher_type' => $voucher->voucher_type,
                    'value' => $voucher->value
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply/use voucher
     */
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'voucher_code' => 'required|string',
            'customer_id' => 'required|string',
            'customer_email' => 'required|email',
            'order_id' => 'required|string',
            'order_total' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $voucher = Voucher::where('voucher_code', $request->voucher_code)
                             ->lockForUpdate()
                             ->first();

            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher code not found'
                ], 404);
            }

            // Validate voucher
            $validation = $voucher->isValidForUser($request->customer_id, $request->order_total);
            
            if (!$validation['valid']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $validation['message']
                ], 400);
            }

            // Check if already used for this order
            $existingUsage = VoucherUsage::where('order_id', $request->order_id)->first();
            if ($existingUsage) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher already applied to this order'
                ], 400);
            }

            // Create usage record
            $usage = VoucherUsage::create([
                'voucher_id' => $voucher->id,
                'customer_id' => $request->customer_id,
                'customer_email' => $request->customer_email,
                'order_id' => $request->order_id,
                'discount_amount' => $validation['discount'],
                'order_total' => $request->order_total,
                'used_at' => now()
            ]);

            // Update voucher usage count
            $voucher->increment('total_used');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Voucher applied successfully',
                'data' => [
                    'usage_id' => $usage->id,
                    'discount_amount' => $validation['discount'],
                    'voucher_name' => $voucher->name_voucher
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get voucher statistics
     */
    public function stats()
    {
        try {
            $stats = [
                'total_vouchers' => Voucher::count(),
                'active_vouchers' => Voucher::valid()->count(),
                'expired_vouchers' => Voucher::where('end_date', '<', now())->count(),
                'total_usage' => VoucherUsage::count(),
                'total_discount_given' => VoucherUsage::sum('discount_amount'),
                'unique_customers' => VoucherUsage::distinct('customer_id')->count(),
                'usage_last_7_days' => VoucherUsage::where('used_at', '>=', now()->subDays(7))->count(),
                'usage_last_30_days' => VoucherUsage::where('used_at', '>=', now()->subDays(30))->count(),
            ];

            // Top vouchers
            $topVouchers = Voucher::withCount('usages')
                                 ->having('usages_count', '>', 0)
                                 ->orderByDesc('usages_count')
                                 ->limit(10)
                                 ->get();

            // Recent activity
            $recentActivity = VoucherUsage::with('voucher')
                                        ->orderByDesc('used_at')
                                        ->limit(20)
                                        ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => $stats,
                    'top_vouchers' => $topVouchers,
                    'recent_activity' => $recentActivity
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger manual sync
     */
    public function sync(Request $request)
    {
        $direction = $request->get('direction', 'from_spreadsheet'); // from_spreadsheet, to_spreadsheet

        try {
            if ($direction === 'from_spreadsheet') {
                $result = $this->syncService->syncFromSpreadsheet();
            } else {
                $result = $this->syncService->syncToSpreadsheet();
            }

            return response()->json([
                'success' => true,
                'message' => 'Sync completed',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}