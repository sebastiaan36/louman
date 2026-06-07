<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
                'name' => 'Worstmakerij T.F.M. Louman',
                'address' => 'Kombuisweg 15',
                'postal_code' => '1041 AV',
                'city' => 'Amsterdam',
                'phone' => '020 4470930',
                'email' => 'info@louman-jordaan.nl',
            ],
            'logoPath' => public_path('storage/img/Logo.png'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.packing-slip', $data);

        // Stream the PDF inline so it opens in the browser tab.
        return $pdf->stream('pakbon-'.$order->id.'.pdf');
    }
}
