<?php

namespace App\Services;

use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Jobs\DeliverWebhook;
use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\Order;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Support\WebhookEvent;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Queues an event for every endpoint subscribed to it.
 *
 * Delivery itself happens in a queued job, so a slow or unreachable receiver
 * never delays the request that triggered the event.
 */
class WebhookDispatcher
{
    /**
     * Queue an event for delivery.
     *
     * @param  array<string, mixed>  $data
     * @return list<\App\Models\WebhookDelivery>
     */
    public function dispatch(string $event, array $data): array
    {
        $originClientId = $this->originApiClientId();

        $endpoints = WebhookEndpoint::query()
            ->active()
            ->subscribedTo($event)
            ->when(
                $originClientId !== null,
                // Never echo a change back to the integration that caused it.
                fn ($query) => $query->where('api_client_id', '!=', $originClientId),
            )
            ->get();

        $deliveries = [];

        foreach ($endpoints as $endpoint) {
            $delivery = WebhookDelivery::create([
                'webhook_endpoint_id' => $endpoint->id,
                'uuid' => (string) Str::uuid(),
                'event' => $event,
                'payload' => $this->envelope($event, $data),
            ]);

            DeliverWebhook::dispatch($delivery);

            $deliveries[] = $delivery;
        }

        return $deliveries;
    }

    /**
     * A new order was placed in the portal.
     */
    public function orderCreated(Order $order): void
    {
        $this->dispatch(WebhookEvent::OrderCreated, $this->orderPayload($order));
    }

    /**
     * An order changed status in the portal.
     */
    public function orderStatusChanged(Order $order, string $previousStatus): void
    {
        $this->dispatch(WebhookEvent::OrderStatusChanged, array_merge(
            $this->orderPayload($order),
            ['previous_status' => $previousStatus],
        ));
    }

    /**
     * A visitor signed up and is waiting to be approved.
     */
    public function customerRegistered(Customer $customer): void
    {
        $this->dispatch(WebhookEvent::CustomerRegistered, $this->customerPayload($customer));
    }

    /**
     * An administrator approved a customer, who can now place orders.
     */
    public function customerApproved(Customer $customer): void
    {
        $this->dispatch(WebhookEvent::CustomerApproved, $this->customerPayload($customer));
    }

    /**
     * Build the same order representation the GET endpoints return.
     *
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        $order->load(['customer', 'deliveryAddress', 'items.product']);

        return $this->resolve(new OrderResource($order));
    }

    /**
     * Build the same customer representation the GET endpoints return.
     *
     * @return array<string, mixed>
     */
    private function customerPayload(Customer $customer): array
    {
        $customer->load(['user', 'deliveryAddresses']);

        return $this->resolve(new CustomerResource($customer));
    }

    /**
     * Render a resource exactly as the matching GET endpoint would, so a
     * webhook payload and a polled response are never subtly different.
     *
     * @return array<string, mixed>
     */
    private function resolve(JsonResource $resource): array
    {
        return $resource->response()->getData(true)['data'];
    }

    /**
     * Wrap the event data in the envelope the receiver sees.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function envelope(string $event, array $data): array
    {
        return [
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
            'data' => $data,
        ];
    }

    /**
     * The integration behind the current request, if any.
     */
    private function originApiClientId(): ?int
    {
        $client = Auth::guard('sanctum')->user();

        return $client instanceof ApiClient ? $client->id : null;
    }
}
