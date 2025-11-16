<?php

namespace App\Http\Connector\Addon;

/**
 * Interface AddonInterface
 *
 * Contract for fetching Add-ons to show in the dashboard.
 */
interface AddonInterface
{
    /**
     * List Add-ons using `products` and `product_categories`.
     *
     * Expected output item fields: id, title, description, price, type.
     *
     * @param array $request {limit?:int, city?:string}
     * @param mixed $user    Authenticated user context (reserved for future personalization).
     * @return object        {success:bool, data:array, message:string}
     */
    public function listAddons(array $request, $user): object;
}
