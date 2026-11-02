<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Restore seller ownership for historical order lines when the product has an owner. */
    public function up(): void
    {
        DB::table('order_items')
            ->whereNull('seller_id')
            ->whereNotNull('product_id')
            ->orderBy('id')
            ->chunkById(500, function ($items): void {
                $owners = DB::table('products')
                    ->whereIn('id', $items->pluck('product_id')->unique())
                    ->whereNotNull('seller_id')
                    ->pluck('seller_id', 'id');

                foreach ($items as $item) {
                    $sellerId = $owners->get($item->product_id);
                    if ($sellerId === null) {
                        continue;
                    }

                    DB::table('order_items')->where('id', $item->id)->update([
                        'seller_id' => $sellerId,
                        'seller_status' => $item->seller_status ?? 'to_accept',
                    ]);
                }
            });
    }

    /** Keep reconstructed ownership data intact if this migration is rolled back. */
    public function down(): void
    {
        // Ownership may have been used by seller workflows after migration.
    }
};