<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Voucher;

class ValidateVoucherUsage
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('voucher_code') && !empty($request->voucher_code)) {
            $voucher = Voucher::where('voucher_code', $request->voucher_code)->first();
            
            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid voucher code'
                ], 400);
            }

            $customerId = $request->customer_id ?? auth()->id();
            $orderTotal = $request->order_total ?? 0;

            $validation = $voucher->isValidForUser($customerId, $orderTotal);
            
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message']
                ], 400);
            }

            // Add voucher info to request
            $request->merge([
                'voucher_id' => $voucher->id,
                'voucher_discount' => $validation['discount']
            ]);
        }

        return $next($request);
    }
}