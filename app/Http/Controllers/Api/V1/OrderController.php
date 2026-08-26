<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\ApplyOrderStatusChange;
use App\Http\Requests\Api\V1\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\AuditLog;
use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Orders are created in the portal and read by the external package, which
 * writes the status back once the order has been handled there.
 */
class OrderController extends ApiController
{
    /**
     * List orders, oldest change first so a poller can walk forward without
     * skipping records.
     *
     * Filters: updated_since, status, customer_number.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::query()->with(['customer', 'deliveryAddress', 'items.product']);

        $this->applyUpdatedSince($query, $request);

        if ($request->filled('status')) {
            $status = (string) $request->query('status');

            if (! in_array($status, OrderStatus::STATUSES, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Ongeldige status. Toegestaan: '.implode(', ', OrderStatus::STATUSES).'.',
                ]);
            }

            $query->where('status', $status);
        }

        if ($request->filled('customer_number')) {
            $query->whereHas(
                'customer',
                fn ($customer) => $customer->where('customer_number', $request->query('customer_number')),
            );
        }

        $orders = $query->orderBy('updated_at')->orderBy('id')
            ->paginate($this->perPage($request));

        return OrderResource::collection($orders);
    }

    /**
     * Show a single order with its lines.
     */
    public function show(Order $order): OrderResource
    {
        return new OrderResource(
            $order->load(['customer', 'deliveryAddress', 'items.product']),
        );
    }

    /**
     * Write the status back. Setting an order to completed sends the customer
     * the same shipping notification as a status change made in the portal.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        $newStatus = $request->validated('status');

        $previousStatus = app(ApplyOrderStatusChange::class)->handle($order, $newStatus);

        AuditLog::recordForApiClient(
            $this->client($request),
            'api.order.status_updated',
            "Bestelstatus gewijzigd van {$previousStatus} naar {$newStatus} via koppeling",
            $order,
            ['previous_status' => $previousStatus, 'new_status' => $newStatus],
        );

        return new OrderResource(
            $order->load(['customer', 'deliveryAddress', 'items.product']),
        );
    }
}
