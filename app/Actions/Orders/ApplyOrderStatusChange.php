<?php

namespace App\Actions\Orders;

use App\Mail\OrderCancelled;
use App\Mail\OrderShipped;
use App\Models\Order;
use App\Models\Setting;
use App\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Applies a status change to an order and sends the notifications that belong
 * to it. Shared by the admin screens and the integration API so a status set
 * from the external package behaves exactly like one set by hand.
 */
class ApplyOrderStatusChange
{
    /**
     * Persist the new status and send any notifications the transition
     * triggers. Returns the status the order had before the change, so the
     * caller can record it in its own audit trail.
     */
    public function handle(Order $order, string $newStatus): string
    {
        $previousStatus = $order->status;

        if ($previousStatus === $newStatus) {
            return $previousStatus;
        }

        $order->update(['status' => $newStatus]);

        if ($newStatus === 'completed') {
            $this->notifyCustomerOfShipment($order);
        }

        if ($newStatus === 'cancelled') {
            $this->notifyOfCancellation($order);
        }

        app(WebhookDispatcher::class)->orderStatusChanged($order, $previousStatus);

        return $previousStatus;
    }

    /**
     * Let the customer know their order is on its way.
     */
    private function notifyCustomerOfShipment(Order $order): void
    {
        $order->load(['customer.user', 'deliveryAddress', 'items.product']);

        $recipient = $order->customer->packing_slip_email ?: $order->customer->user?->email;

        if (! $recipient) {
            return;
        }

        try {
            Mail::to($recipient)->send(new OrderShipped($order));
        } catch (\Exception $e) {
            Log::error('Failed to send order shipped notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify the configured internal recipient that an order was cancelled.
     */
    private function notifyOfCancellation(Order $order): void
    {
        $recipient = Setting::get(Setting::MAIL_CANCELLATION_NOTIFICATION);

        if (! $recipient) {
            return;
        }

        $order->load(['customer.user', 'deliveryAddress', 'items.product']);

        try {
            Mail::to($recipient)->send(new OrderCancelled($order));
        } catch (\Exception $e) {
            Log::error('Failed to send order cancelled notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
