<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Notifications\CustomerApproved;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerApprovalController extends Controller
{
    /**
     * Display a listing of pending customers.
     */
    public function index(): Response
    {
        $pendingCustomers = Customer::with('user')
            ->whereNull('approved_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'company_name' => $customer->company_name,
                'contact_person' => $customer->contact_person,
                'email' => $customer->user->email,
                'phone_number' => $customer->phone_number,
                'kvk_number' => $customer->kvk_number,
                'vat_number' => $customer->vat_number,
                'bank_account' => $customer->bank_account,
                'city' => $customer->city,
                'registered_at' => $customer->created_at->format('d-m-Y H:i'),
            ]);

        return Inertia::render('admin/PendingCustomers', [
            'customers' => $pendingCustomers,
        ]);
    }

    /**
     * Display a listing of all customers.
     */
    public function allCustomers(): Response
    {
        $customers = Customer::with('user')
            ->whereNotNull('approved_at')
            ->orderBy('company_name')
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'company_name' => $customer->company_name,
                'contact_person' => $customer->contact_person,
                'email' => $customer->user->email,
                'phone_number' => $customer->phone_number,
                'city' => $customer->city,
                'approved_at' => $customer->approved_at->format('d-m-Y'),
            ]);

        return Inertia::render('admin/Customers', [
            'customers' => $customers,
        ]);
    }

    /**
     * Display customer details with order history.
     */
    public function show(Customer $customer): Response
    {
        $customer->load(['user', 'deliveryAddresses', 'orders.items.product']);

        $customerData = [
            'id' => $customer->id,
            'company_name' => $customer->company_name,
            'contact_person' => $customer->contact_person,
            'email' => $customer->user->email,
            'phone_number' => $customer->phone_number,
            'kvk_number' => $customer->kvk_number,
            'vat_number' => $customer->vat_number,
            'bank_account' => $customer->bank_account,
            'street_name' => $customer->street_name,
            'house_number' => $customer->house_number,
            'postal_code' => $customer->postal_code,
            'city' => $customer->city,
            'packing_slip_email' => $customer->packing_slip_email,
            'customer_category' => $customer->customer_category,
            'customer_category_label' => $customer->getCategoryLabel(),
            'discount_percentage' => $customer->discount_percentage,
            'delivery_day' => $customer->delivery_day,
            'show_on_map' => $customer->show_on_map,
            'approved_at' => $customer->approved_at?->format('d-m-Y H:i'),
            'created_at' => $customer->created_at->format('d-m-Y H:i'),
        ];

        $deliveryAddresses = $customer->deliveryAddresses->map(fn ($address) => [
            'id' => $address->id,
            'name' => $address->name,
            'street_name' => $address->street_name,
            'house_number' => $address->house_number,
            'postal_code' => $address->postal_code,
            'city' => $address->city,
            'notes' => $address->notes,
            'is_default' => $address->is_default,
        ]);

        $orders = $customer->orders()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'created_at' => $order->created_at->format('d-m-Y H:i'),
                'total' => number_format((float) $order->total, 2, '.', ''),
                'status' => $order->status,
                'items_count' => $order->items->count(),
            ]);

        return Inertia::render('admin/CustomerDetail', [
            'customer' => $customerData,
            'deliveryAddresses' => $deliveryAddresses,
            'orders' => $orders,
        ]);
    }

    /**
     * Approve a customer.
     */
    public function approve(Request $request, Customer $customer): RedirectResponse
    {
        if ($customer->isApproved()) {
            return back()->with('error', 'Deze klant is al goedgekeurd.');
        }

        $validated = $request->validate([
            'customer_category' => ['required', 'in:groothandel,broodjeszaak,horeca'],
            'discount_percentage' => ['nullable', 'in:1,2,3,4,5'],
            'delivery_day' => ['required', 'in:maandag,dinsdag,woensdag,donderdag,vrijdag,zaterdag,zondag,ophalen'],
        ], [
            'customer_category.required' => 'Selecteer een klantcategorie.',
            'customer_category.in' => 'Ongeldige klantcategorie.',
            'discount_percentage.in' => 'Ongeldig kortingspercentage.',
            'delivery_day.required' => 'Selecteer een leverdag.',
            'delivery_day.in' => 'Ongeldige leverdag.',
        ]);

        $customer->update([
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'customer_category' => $validated['customer_category'],
            'discount_percentage' => $validated['discount_percentage'] ?? null,
            'delivery_day' => $validated['delivery_day'],
        ]);

        $customer->user->notify(new CustomerApproved);

        return back()->with('success', "Klant {$customer->company_name} is goedgekeurd.");
    }

    /**
     * Update customer category and discount.
     */
    public function updateCategoryAndDiscount(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'customer_category' => ['required', 'in:groothandel,broodjeszaak,horeca'],
            'discount_percentage' => ['nullable', 'in:1,2,3,4,5'],
            'delivery_day' => ['required', 'in:maandag,dinsdag,woensdag,donderdag,vrijdag,zaterdag,zondag,ophalen'],
            'show_on_map' => ['boolean'],
        ], [
            'customer_category.required' => 'Selecteer een klantcategorie.',
            'customer_category.in' => 'Ongeldige klantcategorie.',
            'discount_percentage.in' => 'Ongeldig kortingspercentage.',
            'delivery_day.required' => 'Selecteer een leverdag.',
            'delivery_day.in' => 'Ongeldige leverdag.',
        ]);

        $customer->update([
            'customer_category' => $validated['customer_category'],
            'discount_percentage' => $validated['discount_percentage'] ?? null,
            'delivery_day' => $validated['delivery_day'],
            'show_on_map' => $validated['show_on_map'],
        ]);

        return back()->with('success', "Klantgegevens bijgewerkt.");
    }

    /**
     * Update customer information.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'kvk_number' => ['required', 'string', 'max:8'],
            'vat_number' => ['required', 'string', 'max:14'],
            'bank_account' => ['required', 'string', 'max:34'],
            'street_name' => ['required', 'string', 'max:255'],
            'house_number' => ['required', 'string', 'max:10'],
            'postal_code' => ['required', 'string', 'max:7'],
            'city' => ['required', 'string', 'max:255'],
            'packing_slip_email' => ['nullable', 'email', 'max:255'],
        ], [
            'company_name.required' => 'Bedrijfsnaam is verplicht.',
            'contact_person.required' => 'Contactpersoon is verplicht.',
            'phone_number.required' => 'Telefoonnummer is verplicht.',
            'kvk_number.required' => 'KVK nummer is verplicht.',
            'vat_number.required' => 'BTW nummer is verplicht.',
            'bank_account.required' => 'IBAN is verplicht.',
            'street_name.required' => 'Straatnaam is verplicht.',
            'house_number.required' => 'Huisnummer is verplicht.',
            'postal_code.required' => 'Postcode is verplicht.',
            'city.required' => 'Plaats is verplicht.',
            'packing_slip_email.email' => 'Pakbon email moet een geldig email adres zijn.',
        ]);

        $customer->update($validated);

        return back()->with('success', "Klantgegevens bijgewerkt.");
    }

    /**
     * Store a new delivery address for a customer.
     */
    public function storeDeliveryAddress(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'street_name' => ['required', 'string', 'max:255'],
            'house_number' => ['required', 'string', 'max:10'],
            'postal_code' => ['required', 'string', 'max:7'],
            'city' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $customer->deliveryAddresses()->update(['is_default' => false]);
        }

        $customer->deliveryAddresses()->create($validated);

        return back()->with('success', 'Afleveradres toegevoegd.');
    }

    /**
     * Update a delivery address for a customer.
     */
    public function updateDeliveryAddress(Request $request, Customer $customer, DeliveryAddress $deliveryAddress): RedirectResponse
    {
        if ($deliveryAddress->customer_id !== $customer->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'street_name' => ['required', 'string', 'max:255'],
            'house_number' => ['required', 'string', 'max:10'],
            'postal_code' => ['required', 'string', 'max:7'],
            'city' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $customer->deliveryAddresses()->update(['is_default' => false]);
        }

        $deliveryAddress->update($validated);

        return back()->with('success', 'Afleveradres bijgewerkt.');
    }

    /**
     * Export all approved customers as a CSV file.
     */
    public function export(): StreamedResponse
    {
        $customers = Customer::with('user')
            ->whereNotNull('approved_at')
            ->orderBy('company_name')
            ->get();

        return response()->stream(function () use ($customers) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens it correctly
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'id', 'company_name', 'contact_person', 'email', 'phone_number',
                'street_name', 'house_number', 'postal_code', 'city',
                'kvk_number', 'bank_account', 'vat_number', 'packing_slip_email',
                'customer_category', 'discount_percentage', 'delivery_day',
                'route_order', 'show_on_map',
            ], ';');

            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer->id,
                    $customer->company_name,
                    $customer->contact_person,
                    $customer->user->email,
                    // Single-quote prefix tells Excel to treat the value as text,
                    // preserving leading zeros. The import strips this prefix automatically.
                    "'".$customer->phone_number,
                    $customer->street_name,
                    $customer->house_number,
                    $customer->postal_code,
                    $customer->city,
                    $customer->kvk_number,
                    $customer->bank_account,
                    $customer->vat_number,
                    $customer->packing_slip_email ?? '',
                    $customer->customer_category ?? '',
                    $customer->discount_percentage ?? '',
                    $customer->delivery_day ?? '',
                    $customer->route_order ?? '',
                    $customer->show_on_map ? 1 : 0,
                ], ';');
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="klanten-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Import customers from a CSV file (update-only, keyed by id).
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $request->file('csv_file')->getPathname();

        // Auto-detect delimiter
        $firstLine = fgets(fopen($path, 'r'));
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen($path, 'r');

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = null;
        $updated = 0;
        $skipped = [];
        $rowNumber = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if ($headers === null) {
                $headers = array_map('trim', $row);
                continue;
            }

            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }

            $data = array_combine($headers, array_map('trim', $row));

            if (empty($data['id'] ?? '')) {
                $skipped[] = "Rij {$rowNumber}: id is leeg";
                continue;
            }

            $customer = Customer::find((int) $data['id']);

            if (! $customer) {
                $skipped[] = "Rij {$rowNumber}: klant met id {$data['id']} niet gevonden";
                continue;
            }

            $updateData = $this->buildCustomerUpdateData($data, $headers, $rowNumber, $skipped);

            if (! empty($updateData)) {
                $customer->update($updateData);
                $updated++;
            }
        }

        fclose($handle);

        $summary = $updated > 0
            ? "{$updated} ".($updated === 1 ? 'klant' : 'klanten').' bijgewerkt'
            : 'Geen wijzigingen.';

        if (! empty($skipped)) {
            $summary .= '. '.count($skipped).' '.(count($skipped) === 1 ? 'rij' : 'rijen').' overgeslagen.';
        }

        return redirect()->route('admin.customers.index')
            ->with('success', $summary)
            ->with('import_results', ['updated' => $updated, 'skipped' => $skipped]);
    }

    /**
     * Build update data from a CSV row, validating fields.
     *
     * @param  array<string, string>  $data
     * @param  string[]  $headers
     * @param  string[]  $skipped
     */
    private function buildCustomerUpdateData(array $data, array $headers, int $rowNumber, array &$skipped): array
    {
        $validCategories = ['groothandel', 'broodjeszaak', 'horeca'];
        $validDiscounts = ['1', '2', '3', '4', '5'];
        $validDays = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag', 'ophalen'];

        // Map field name → CSV column(s) that drive it
        $fieldMap = [
            'company_name'       => ['company_name'],
            'contact_person'     => ['contact_person'],
            'phone_number'       => ['phone_number'],
            'street_name'        => ['street_name'],
            'house_number'       => ['house_number'],
            'postal_code'        => ['postal_code'],
            'city'               => ['city'],
            'packing_slip_email' => ['packing_slip_email'],
            'customer_category'  => ['customer_category'],
            'discount_percentage' => ['discount_percentage'],
            'delivery_day'       => ['delivery_day'],
            'route_order'        => ['route_order'],
            'show_on_map'        => ['show_on_map'],
        ];

        $updateData = [];

        foreach ($fieldMap as $field => $columns) {
            $col = $columns[0];
            if (! in_array($col, $headers)) {
                continue;
            }

            $value = $data[$col] ?? '';

            // Strip Excel text-marker prefix (single quote) added during export
            if (str_starts_with($value, "'")) {
                $value = substr($value, 1);
            }

            // Validate constrained fields
            if ($field === 'customer_category' && $value !== '' && ! in_array($value, $validCategories)) {
                $skipped[] = "Rij {$rowNumber}: ongeldige klantcategorie '{$value}' — rij overgeslagen";

                return [];
            }

            if ($field === 'discount_percentage' && $value !== '' && ! in_array($value, $validDiscounts)) {
                $skipped[] = "Rij {$rowNumber}: ongeldig kortingspercentage '{$value}' — rij overgeslagen";

                return [];
            }

            if ($field === 'delivery_day' && $value !== '' && ! in_array($value, $validDays)) {
                $skipped[] = "Rij {$rowNumber}: ongeldige leverdag '{$value}' — rij overgeslagen";

                return [];
            }

            // Parse values
            if ($field === 'show_on_map') {
                $updateData[$field] = $value !== '' ? (bool) (int) $value : true;
            } elseif ($field === 'route_order') {
                $updateData[$field] = $value !== '' ? (int) $value : null;
            } elseif (in_array($field, ['discount_percentage', 'packing_slip_email', 'customer_category', 'delivery_day'])) {
                $updateData[$field] = $value !== '' ? $value : null;
            } else {
                // String fields: only update if non-empty to avoid accidentally blanking them
                if ($value !== '') {
                    $updateData[$field] = $value;
                }
            }
        }

        return $updateData;
    }

    /**
     * Delete a delivery address for a customer.
     */
    public function destroyDeliveryAddress(Customer $customer, DeliveryAddress $deliveryAddress): RedirectResponse
    {
        if ($deliveryAddress->customer_id !== $customer->id) {
            abort(404);
        }

        $deliveryAddress->delete();

        return back()->with('success', 'Afleveradres verwijderd.');
    }
}
