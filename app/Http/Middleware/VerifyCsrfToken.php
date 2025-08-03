<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Midtrans webhook routes - exclude from CSRF protection
        'checkout/payment-notification',
        'midtrans/notification',
        'webhook/midtrans',
        'payment/webhook',
        
        // API routes that might need CSRF exclusion
        'api/payment/*',
        
        // Other webhook endpoints if needed
        // 'webhook/*',
    ];
}