<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Customers move in both directions: they sign up in the portal, and the
 * external package writes back the customer number, the external id and the
 * commercial fields once the account exists on its side.
 */
class CustomerController extends ApiController
{
    /**
     * List customers, oldest change first so a poller can walk forward.
     *
     * Filters: updated_since, customer_number, external_id, unlinked (customers
     * that do not have a customer number yet), approved, active.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Customer::query()->with(['user', 'deliveryAddresses']);

        $this->applyUpdatedSince($query, $request);

        if ($request->filled('customer_number')) {
            $query->where('customer_number', $request->query('customer_number'));
        }

        if ($request->filled('external_id')) {
            $query->where('external_id', $request->query('external_id'));
        }

        if ($request->boolean('unlinked')) {
            $query->whereNull('customer_number');
        }

        if ($request->has('approved')) {
            $request->boolean('approved')
                ? $query->whereNotNull('approved_at')
                : $query->whereNull('approved_at');
        }

        if ($request->has('active')) {
            $request->boolean('active')
                ? $query->whereNull('deactivated_at')
                : $query->whereNotNull('deactivated_at');
        }

        $customers = $query->orderBy('updated_at')->orderBy('id')
            ->paginate($this->perPage($request));

        return CustomerResource::collection($customers);
    }

    /**
     * Show a single customer, including their price agreements.
     */
    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource(
            $customer->load(['user', 'deliveryAddresses', 'customProductPrices.product']),
        );
    }

    /**
     * Update the fields the external package owns. Only the fields present in
     * the payload are written; everything else is left untouched.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $data = $request->validated();

        $customer->fill($data);
        $changed = array_keys($customer->getDirty());
        $customer->synced_at = now();
        $customer->save();

        AuditLog::recordForApiClient(
            $this->client($request),
            'api.customer.updated',
            'Klantgegevens bijgewerkt via koppeling: '.($customer->company_name ?? "klant #{$customer->id}"),
            $customer,
            ['fields' => $changed],
        );

        return new CustomerResource(
            $customer->load(['user', 'deliveryAddresses', 'customProductPrices.product']),
        );
    }
}
