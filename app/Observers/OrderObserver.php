<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * Valid statuses that should be counted as "paid/completed" for revenue
     */
    private array $paidStatuses = ['paid', 'processing', 'shipped', 'delivered'];

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Update spending if order is created with a paid status
        if (in_array($order->status, $this->paidStatuses) && $order->user_id) {
            $this->updateUserSpending($order->user_id, 'order_created');
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if status changed
        if ($order->isDirty('status') && $order->user_id) {
            $oldStatus = $order->getOriginal('status');
            $newStatus = $order->status;
            
            Log::info('Order status changed, checking spending update', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'total_amount' => $order->total_amount,
                'old_is_paid' => in_array($oldStatus, $this->paidStatuses),
                'new_is_paid' => in_array($newStatus, $this->paidStatuses)
            ]);
            
            // Update spending if status changes between paid/unpaid states
            $oldIsPaid = in_array($oldStatus, $this->paidStatuses);
            $newIsPaid = in_array($newStatus, $this->paidStatuses);
            
            if ($oldIsPaid !== $newIsPaid) {
                // Status changed from paid to unpaid OR unpaid to paid
                $this->updateUserSpending($order->user_id, 'status_change', [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'old_is_paid' => $oldIsPaid,
                    'new_is_paid' => $newIsPaid,
                    'order_number' => $order->order_number
                ]);
            }
        }
        
        // Check if total_amount changed for orders with paid status
        if ($order->isDirty('total_amount') && in_array($order->status, $this->paidStatuses) && $order->user_id) {
            $this->updateUserSpending($order->user_id, 'amount_change', [
                'old_amount' => $order->getOriginal('total_amount'),
                'new_amount' => $order->total_amount
            ]);
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        // Update user spending when order is deleted
        if ($order->user_id) {
            $this->updateUserSpending($order->user_id, 'order_deleted', [
                'deleted_order_number' => $order->order_number,
                'deleted_amount' => $order->total_amount,
                'deleted_status' => $order->status
            ]);
        }
    }

    /**
     * Update user spending statistics
     */
    private function updateUserSpending($userId, $trigger = 'unknown', $context = [])
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                Log::warning('User not found for spending update', [
                    'user_id' => $userId,
                    'trigger' => $trigger
                ]);
                return;
            }

            // Get old values for logging
            $oldSpent = $user->total_spent ?? 0;
            $oldOrders = $user->total_orders ?? 0;
            $oldTier = $this->getTierFromSpending($oldSpent);
            
            // Update spending stats
            $user->updateSpendingStats();
            $user->refresh();
            
            // Get new values
            $newSpent = $user->total_spent;
            $newOrders = $user->total_orders;
            $newTier = $user->getCustomerTier();
            
            // Log the update
            Log::info('User spending updated via OrderObserver', array_merge([
                'user_id' => $userId,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'trigger' => $trigger,
                'old_spent' => $oldSpent,
                'new_spent' => $newSpent,
                'spent_change' => $newSpent - $oldSpent,
                'old_orders' => $oldOrders,
                'new_orders' => $newOrders,
                'orders_change' => $newOrders - $oldOrders,
                'old_tier' => $oldTier,
                'new_tier' => $newTier,
                'tier_changed' => $oldTier !== $newTier,
                'paid_statuses' => $this->paidStatuses
            ], $context));
            
            // Log tier change separately if it occurred
            if ($oldTier !== $newTier) {
                Log::info('Customer tier changed', [
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'old_tier' => $oldTier,
                    'new_tier' => $newTier,
                    'new_tier_label' => $user->getCustomerTierLabel(),
                    'trigger' => $trigger
                ]);
                
                // Here you could dispatch events for tier changes
                // event(new CustomerTierChanged($user, $oldTier, $newTier));
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update user spending via OrderObserver', [
                'user_id' => $userId,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'context' => $context
            ]);
        }
    }

    /**
     * Get tier from spending amount
     */
    private function getTierFromSpending($spending)
    {
        if ($spending >= 10000000) return 'platinum';
        if ($spending >= 5000000) return 'gold';
        if ($spending >= 1000000) return 'silver';
        if ($spending > 0) return 'bronze';
        return 'new';
    }
}