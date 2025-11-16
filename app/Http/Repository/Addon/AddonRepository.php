<?php

namespace App\Http\Repository\Addon;

use App\Http\Connector\Addon\AddonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class AddonRepository
 *
 * Reads add-on items from:
 *  - products (id, title, description, price, location, category_id, ...)
 *  - product_categories (id, name)
 *
 * Business rule:
 *  - Treat items from categories named one of
 *    ['Add-ons','Addons','Add Ons','Services','Coaching'] as "Add-ons".
 *  - If none of those categories exist, gracefully return the latest products.
 */
class AddonRepository implements AddonInterface
{
    /**
     * {@inheritdoc}
     */
    public function listAddons(array $request, $user): object
    {
        $ret = (object)['success' => false, 'data' => [], 'message' => ''];

        try {
            $limit = (int)($request['limit'] ?? 5);
            if ($limit <= 0 || $limit > 20) $limit = 5;

            // Resolve "Add-on" category ids by common names present in DB
            $catNames = ['Add-ons', 'Addons', 'Add Ons', 'Services', 'Coaching'];
            $addonCatIds = DB::table('product_categories')
                ->whereIn('name', $catNames)
                ->pluck('id')
                ->all();

            $q = DB::table('products as p')
                ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id');

            if (!empty($addonCatIds)) {
                $q->whereIn('p.category_id', $addonCatIds);
            }

            if (!empty($request['city'])) {
                $q->where('p.location', $request['city']);
            }

            $rows = $q->orderBy('p.created_at', 'desc')
                ->limit($limit)
                ->get([
                    'p.id',
                    'p.title',
                    'p.description',
                    'p.price',
                    'c.name as type',
                ]);

            // Fallback: no configured categories found — show latest few products
            if ($rows->isEmpty() && empty($addonCatIds)) {
                $rows = DB::table('products as p')
                    ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id')
                    ->orderBy('p.created_at', 'desc')
                    ->limit($limit)
                    ->get(['p.id','p.title','p.description','p.price','c.name as type']);
            }

            $data = [];
            foreach ($rows as $r) {
                $data[] = [
                    'id'          => (int)$r->id,
                    'title'       => (string)$r->title,
                    'description' => (string)($r->description ?? ''),
                    'price'       => (float)$r->price,
                    'type'        => (string)($r->type ?? 'Add-on'),
                ];
            }

            $ret->success = true;
            $ret->data = $data;
        } catch (\Throwable $e) {
            $ret->message = $e->getMessage();
        }

        return $ret;
    }
}
