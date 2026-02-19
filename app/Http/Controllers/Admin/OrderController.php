<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderShipped;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of all orders.
     */
    public function index(Request $request): Response
    {
        $status = $request->input('status');
        $search = $request->input('search');

        $query = Order::with(['customer', 'deliveryAddress'])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($status && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
            $query->where('status', $status);
        }

        // Search by order number or customer name
        if ($search) {
            $query->where(function ($q) use ($search) {
                // Search by order ID (without the # prefix)
                if (is_numeric($search)) {
                    $q->orWhere('orders.id', $search);
                }
                // Search by order ID with # prefix
                if (str_starts_with($search, '#')) {
                    $searchId = ltrim($search, '#');
                    if (is_numeric($searchId)) {
                        $q->orWhere('orders.id', $searchId);
                    }
                }
                // Search by customer company name
                $q->orWhereHas('customer', function ($customerQuery) use ($search) {
                    $customerQuery->where('company_name', 'like', '%'.$search.'%');
                });
            });
        }

        $orders = $query->paginate(50)->through(function (Order $order) {
            return [
                'id' => $order->id,
                'order_number' => '#'.$order->id,
                'customer_name' => $order->customer->company_name,
                'created_at' => $order->created_at->format('d-m-Y H:i'),
                'total' => $order->total,
                'status' => $order->status,
                'status_label' => $this->getStatusLabel($order->status),
                'item_count' => $order->items()->count(),
            ];
        });

        return Inertia::render('admin/Orders', [
            'orders' => $orders,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): Response
    {
        $order->load(['customer', 'items.product', 'deliveryAddress']);

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

        // Get all active products for adding to order
        $products = \App\Models\Product::where('is_active', true)
            ->orderBy('title')
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
            ]);

        return Inertia::render('admin/OrderDetail', [
            'order' => [
                'id' => $order->id,
                'order_number' => '#'.$order->id,
                'created_at' => $order->created_at->format('d-m-Y H:i'),
                'total' => $order->total,
                'status' => $order->status,
                'status_label' => $this->getStatusLabel($order->status),
                'notes' => $order->notes,
                'customer' => [
                    'company_name' => $order->customer->company_name,
                    'contact_person' => $order->customer->contact_person,
                    'phone_number' => $order->customer->phone_number,
                    'email' => $order->customer->user->email ?? null,
                ],
                'delivery_address' => $deliveryAddress,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_title' => $item->product->title,
                        'product_thumbnail' => $item->product->thumbnail_url,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => (float) $item->price * $item->quantity,
                    ];
                }),
            ],
            'availableProducts' => $products,
        ]);
    }

    /**
     * Update the order status.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $previousStatus = $order->status;
        $newStatus = $request->input('status');

        $order->update([
            'status' => $newStatus,
        ]);

        // Send shipped notification to customer when order is completed
        if ($newStatus === 'completed' && $previousStatus !== 'completed') {
            $order->load(['customer.user', 'deliveryAddress', 'items.product']);
            $shippedEmail = $order->customer->packing_slip_email ?? $order->customer->user->email;
            Mail::to($shippedEmail)->send(new OrderShipped($order));
        }

        return back()->with('success', 'Bestelstatus bijgewerkt.');
    }

    /**
     * Update order items and notes.
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        // Only allow editing if order is not completed or cancelled
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Deze bestelling kan niet meer worden aangepast.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'exists:order_items,id'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $existingItemIds = [];

        // Process items: update existing or create new
        foreach ($validated['items'] as $itemData) {
            if (isset($itemData['id'])) {
                // Update existing item
                $orderItem = $order->items()->find($itemData['id']);
                if ($orderItem) {
                    $orderItem->update([
                        'quantity' => $itemData['quantity'],
                    ]);
                    $existingItemIds[] = $itemData['id'];
                }
            } else {
                // Create new item
                $product = \App\Models\Product::find($itemData['product_id']);
                if ($product) {
                    $newItem = $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $itemData['quantity'],
                        'price' => $product->price,
                    ]);
                    $existingItemIds[] = $newItem->id;
                }
            }
        }

        // Delete items that are no longer in the order
        $order->items()->whereNotIn('id', $existingItemIds)->delete();

        // Recalculate total
        $total = $order->items()->get()->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $order->update([
            'total' => $total,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Bestelling bijgewerkt.');
    }

    /**
     * Update status for multiple orders.
     */
    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'exists:orders,id'],
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $orderIds = $request->input('order_ids');
        $status = $request->input('status');

        Order::whereIn('id', $orderIds)->update([
            'status' => $status,
        ]);

        $count = count($orderIds);
        $statusLabel = $this->getStatusLabel($status);

        return back()->with('success', "{$count} ".($count === 1 ? 'bestelling' : 'bestellingen')." bijgewerkt naar '{$statusLabel}'.");
    }

    /**
     * Download packing slips for multiple orders as ZIP.
     */
    public function bulkDownloadPackingSlips(Request $request)
    {
        $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'exists:orders,id'],
        ]);

        $orderIds = $request->input('order_ids');
        $orders = Order::with(['customer.user', 'deliveryAddress', 'items.product'])
            ->whereIn('id', $orderIds)
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'Geen bestellingen gevonden.');
        }

        // Create temporary directory for PDFs
        $tempDir = storage_path('app/temp/packing-slips-'.uniqid());
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Company info
        $companyInfo = [
            'name' => 'Ambachtelijke Slagerij T.F.M. Louman',
            'address' => 'Goudsbloemstraat 76',
            'postal_code' => '2565 CK',
            'city' => 'Den Haag',
            'phone' => '070-3605916',
            'fax' => '070-3683175',
            'email' => 'info@louman.nl',
        ];

        $logoPath = public_path('storage/img/Logo.png');

        // Generate PDFs
        foreach ($orders as $order) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.packing-slip', [
                'order' => $order,
                'companyInfo' => $companyInfo,
                'logoPath' => $logoPath,
            ]);

            $fileName = "pakbon-{$order->id}.pdf";
            $pdf->save("{$tempDir}/{$fileName}");
        }

        // Create ZIP file
        $zipFileName = 'pakbonnen-'.date('Y-m-d-His').'.zip';
        $zipPath = storage_path('app/temp/'.$zipFileName);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Kon ZIP bestand niet aanmaken.');
        }

        // Add PDFs to ZIP
        foreach (glob("{$tempDir}/*.pdf") as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();

        // Clean up temp PDFs
        array_map('unlink', glob("{$tempDir}/*.pdf"));
        rmdir($tempDir);

        // Download ZIP and schedule cleanup
        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
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
