<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\PlaceOrderRequest;
use App\Mail\OrderConfirmation;
use App\Mail\OrderPlacedNotification;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display the customer's order history.
     */
    public function index(Request $request): Response
    {
        $customer = $request->user()->customer;
        $search = $request->input('search');

        $query = $customer->orders()
            ->with('deliveryAddress')
            ->orderBy('created_at', 'desc');

        // Search by order number
        if ($search) {
            // Remove # prefix if present
            $searchTerm = ltrim($search, '#');

            if (is_numeric($searchTerm)) {
                $query->where('orders.id', $searchTerm);
            }
        }

        $orders = $query->get()
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'order_number' => '#'.$order->id,
                    'created_at' => $order->created_at->format('d-m-Y H:i'),
                    'total' => $order->total,
                    'status' => $order->status,
                    'status_label' => $this->getStatusLabel($order->status),
                    'item_count' => $order->items()->count(),
                ];
            });

        return Inertia::render('customer/Orders', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, Order $order): Response
    {
        // Ensure order belongs to the authenticated customer
        if ($order->customer_id !== $request->user()->customer->id) {
            abort(403);
        }

        $order->load(['items.product', 'deliveryAddress', 'customer']);

        // Use delivery address if exists, otherwise use customer's main address
        if ($order->deliveryAddress) {
            $deliveryAddress = [
                'name' => $order->deliveryAddress->name,
                'street_name' => $order->deliveryAddress->street_name,
                'house_number' => $order->deliveryAddress->house_number,
                'postal_code' => $order->deliveryAddress->postal_code,
                'city' => $order->deliveryAddress->city,
            ];
        } else {
            $deliveryAddress = [
                'name' => 'Hoofdadres',
                'street_name' => $order->customer->street_name,
                'house_number' => $order->customer->house_number,
                'postal_code' => $order->customer->postal_code,
                'city' => $order->customer->city,
            ];
        }

        return Inertia::render('customer/OrderDetail', [
            'order' => [
                'id' => $order->id,
                'order_number' => '#'.$order->id,
                'created_at' => $order->created_at->format('d-m-Y H:i'),
                'total' => $order->total,
                'status' => $order->status,
                'status_label' => $this->getStatusLabel($order->status),
                'notes' => $order->notes,
                'delivery_address' => $deliveryAddress,
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_title' => $item->product->title,
                        'product_thumbnail' => $item->product->thumbnail_url,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => (float) $item->price * $item->quantity,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Place a new order.
     */
    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        $customer = $request->user()->customer;

        // Check if cart is not empty
        $cartItems = $customer->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return back()->with('error', 'Je winkelwagen is leeg.');
        }

        // Check if all products are available and in stock
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;

            if (! $product->is_active) {
                return back()->with('error', "Product '{$product->title}' is niet meer beschikbaar.");
            }

            if (! $product->isInStock()) {
                return back()->with('error', "Product '{$product->title}' is niet op voorraad.");
            }
        }

        // Verify delivery address belongs to customer if provided
        $deliveryAddressId = $request->input('delivery_address_id');

        if ($deliveryAddressId) {
            $deliveryAddress = $customer->deliveryAddresses()->find($deliveryAddressId);

            if (! $deliveryAddress) {
                return back()->with('error', 'Ongeldig afleveradres.');
            }
        }

        DB::beginTransaction();

        try {
            // Calculate total
            $total = $cartItems->sum(function ($cartItem) use ($customer) {
                return (float) $cartItem->product->getPriceForCustomer($customer) * $cartItem->quantity;
            });

            // Create order
            $order = $customer->orders()->create([
                'delivery_address_id' => $deliveryAddressId,
                'total' => $total,
                'status' => 'pending',
                'notes' => $request->input('notes'),
            ]);

            // Create order items
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $cartItem->quantity,
                    'price' => $product->getPriceForCustomer($customer),
                ]);

                // Optionally: Deduct stock
                // $product->decrement('stock_quantity', $cartItem->quantity);
            }

            // Clear cart
            $customer->cartItems()->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Order creation failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Er ging iets mis bij het plaatsen van de bestelling. Probeer het opnieuw.');
        }

        // Send mails after transaction — mail errors should not block order creation
        try {
            Mail::to('info@louman-joraan.nl')
                ->send(new OrderPlacedNotification($order));
        } catch (\Exception $e) {
            \Log::error('Failed to send admin order notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $confirmationEmail = $customer->packing_slip_email ?? $customer->user->email;
            Mail::to($confirmationEmail)
                ->send(new OrderConfirmation($order));
        } catch (\Exception $e) {
            \Log::error('Failed to send order confirmation to customer', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return to_route('customer.orders.show', $order)
            ->with('success', 'Bestelling succesvol geplaatst!');
    }

    /**
     * Get human-readable status label.
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'In behandeling',
            'confirmed' => 'Bevestigd',
            'completed' => 'Voltooid',
            'cancelled' => 'Geannuleerd',
            default => $status,
        };
    }
}
