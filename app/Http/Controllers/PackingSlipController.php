<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PackingSlipController extends Controller
{
    /**
     * Generate packing slip PDF for an order.
     */
    public function generate(Request $request, Order $order): Response
    {
        $user = $request->user();

        // Authorization: admins can view all, customers can only view their own
        if ($user->isCustomer() && $order->customer_id !== $user->customer->id) {
            abort(403, 'Unauthorized access to this order');
        }

        // Load order relationships
        $order->load(['customer.user', 'deliveryAddress', 'items.product']);

        // Prepare data for the PDF
        $data = [
            'order' => $order,
            'companyInfo' => [
                'name' => 'Ambachtelijke Slagerij T.F.M. Louman',
                'address' => 'Goudsbloemstraat 76',
                'postal_code' => '1015 JR',
                'city' => 'Amsterdam',
                'phone' => '020 6220771',
                'fax' => '020 6224001',
                'email' => 'Info@louman-jordaan.nl',
            ],
            'logoPath' => public_path('storage/img/Logo.png'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.packing-slip', $data);

        // Return PDF as download
        return $pdf->download('pakbon-' . $order->id . '.pdf');
    }

    /**
     * Generate invoice PDF for an order.
     */
    public function invoice(Request $request, Order $order): Response
    {
        $user = $request->user();

        // Authorization: admins can view all, customers can only view their own
        if ($user->isCustomer() && $order->customer_id !== $user->customer->id) {
            abort(403, 'Unauthorized access to this order');
        }

        // Only allow invoice download for completed orders
        if ($order->status !== 'completed') {
            abort(403, 'Factuur is alleen beschikbaar voor voltooide bestellingen.');
        }

        $order->load(['customer.user', 'deliveryAddress', 'items.product']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'invoiceDate' => $order->updated_at,
        ]);

        return $pdf->download('factuur-' . $order->id . '.pdf');
    }
}
