<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\SyncCustomerPricesRequest;
use App\Http\Resources\Api\V1\CustomerPriceResource;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Price agreements per customer. They are made in the external package and
 * pushed here as a complete set.
 */
class CustomerPriceController extends ApiController
{
    /**
     * List the deviating prices for one customer.
     */
    public function index(Customer $customer): AnonymousResourceCollection
    {
        return CustomerPriceResource::collection(
            $customer->customProductPrices()->with('product')->get(),
        );
    }

    /**
     * Replace all price agreements for this customer with the ones sent.
     * Agreements missing from the payload are removed.
     */
    public function sync(SyncCustomerPricesRequest $request, Customer $customer): AnonymousResourceCollection
    {
        /** @var array<int, array{article_number: string, custom_price?: string|null, custom_price_per_kg?: string|null}> $rows */
        $rows = $request->validated('prices');

        $productIds = Product::query()
            ->whereIn('article_number', array_column($rows, 'article_number'))
            ->pluck('id', 'article_number');

        DB::transaction(function () use ($customer, $rows, $productIds): void {
            $keptProductIds = [];

            foreach ($rows as $row) {
                $productId = $productIds[$row['article_number']];
                $keptProductIds[] = $productId;

                CustomerProductPrice::updateOrCreate(
                    ['customer_id' => $customer->id, 'product_id' => $productId],
                    [
                        'custom_price' => $row['custom_price'] ?? null,
                        'custom_price_per_kg' => $row['custom_price_per_kg'] ?? null,
                    ],
                );
            }

            $customer->customProductPrices()
                ->whereNotIn('product_id', $keptProductIds)
                ->delete();
        });

        $customer->forceFill(['synced_at' => now()])->save();

        AuditLog::recordForApiClient(
            $this->client($request),
            'api.customer.prices_synced',
            'Prijsafspraken bijgewerkt via koppeling: '.($customer->company_name ?? "klant #{$customer->id}"),
            $customer,
            ['count' => count($rows)],
        );

        return CustomerPriceResource::collection(
            $customer->customProductPrices()->with('product')->get(),
        );
    }
}
