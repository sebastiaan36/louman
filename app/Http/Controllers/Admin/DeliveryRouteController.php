<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryRouteController extends Controller
{
    const DAYS = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag', 'ophalen'];

    public function index(Request $request): Response
    {
        $day = in_array($request->get('day'), self::DAYS)
            ? $request->get('day')
            : 'maandag';

        $customers = Customer::approved()
            ->where('delivery_day', $day)
            ->orderByRaw('CASE WHEN route_order IS NULL THEN 1 ELSE 0 END, route_order ASC, company_name ASC')
            ->get(['id', 'company_name', 'street_name', 'house_number', 'city', 'route_order']);

        return Inertia::render('admin/DeliveryRoute', [
            'customers' => $customers,
            'selectedDay' => $day,
            'days' => self::DAYS,
        ]);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $request->validate([
            'day' => ['required', 'string', 'in:'.implode(',', self::DAYS)],
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:customers,id'],
        ]);

        foreach ($request->order as $position => $customerId) {
            Customer::where('id', $customerId)
                ->where('delivery_day', $request->day)
                ->update(['route_order' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }
}
