<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Support\DeliveryDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryRouteController extends Controller
{
    const DAYS = DeliveryDay::ALL;

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

    public function updateOrder(Request $request): RedirectResponse
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

        return back();
    }

    /**
     * Export the delivery route as a CSV file, for one day or all days.
     */
    public function export(Request $request): StreamedResponse
    {
        $day = $request->get('day');
        $isAllDays = ! in_array($day, self::DAYS, true);

        $query = Customer::approved()->whereNotNull('delivery_day');
        if (! $isAllDays) {
            $query->where('delivery_day', $day);
        }

        // Order by weekday, then by route position (unset positions last), then name.
        $dayCase = 'CASE delivery_day';
        foreach (self::DAYS as $index => $weekday) {
            $dayCase .= " WHEN '{$weekday}' THEN {$index}";
        }
        $dayCase .= ' ELSE 99 END';

        $customers = $query
            ->orderByRaw($dayCase)
            ->orderByRaw('CASE WHEN route_order IS NULL THEN 1 ELSE 0 END, route_order ASC, company_name ASC')
            ->get(['company_name', 'phone_number', 'delivery_day', 'route_order']);

        $scope = $isAllDays ? 'alle dagen' : $day;
        AuditLog::record('delivery_route.export', "Rijroute-export ({$scope}) van {$customers->count()} klanten");

        $fileSuffix = $isAllDays ? 'alle-dagen' : $day;

        return response()->stream(function () use ($customers) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens it correctly
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['klantnaam', 'telefoonnummer', 'leverdag', 'rijroute'], ';');

            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $this->csvValue($customer->company_name),
                    $this->csvValue($customer->phone_number),
                    $customer->delivery_day,
                    $customer->route_order ?? '',
                ], ';');
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rijroute-'.$fileSuffix.'-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Prepare a value for the CSV.
     *
     * A spreadsheet treats a field that opens with =, +, - or @ as a formula.
     * The phone numbers used to be exported with a "- " prefix, which Excel
     * read as a subtraction: "- 020-6249065" arrived as -6249085. Only those
     * values get an apostrophe, which marks them as literal text; a normal
     * phone number like 020-6249065 is written exactly as it is stored.
     */
    private function csvValue(?string $value): string
    {
        $value = trim((string) $value);

        if ($value !== '' && str_contains('=+-@', $value[0])) {
            return "'".$value;
        }

        return $value;
    }
}
