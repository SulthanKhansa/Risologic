<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_date',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    // Note: supplier() is now on PurchaseItem to allow multi-vendor per purchase record.

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    protected static function booted()
    {
        static::updated(function (Purchase $purchase) {
            $oldStatus = $purchase->getOriginal('status');

            // 1. Jika status berubah MENJADI completed
            if ($oldStatus !== 'completed' && $purchase->status === 'completed') {
                $purchase->increaseStock();
            }

            // 2. Jika status berubah DARI completed menjadi lainnya (Realisasi Pembatalan)
            if ($oldStatus === 'completed' && $purchase->status !== 'completed') {
                $purchase->decreaseStock();
            }
        });

        static::deleted(function (Purchase $purchase) {
            // Restore stock if a completed purchase is deleted
            if ($purchase->status === 'completed') {
                $purchase->decreaseStock();
            }
        });
    }

    public function increaseStock()
    {
        DB::transaction(function () {
            foreach ($this->items as $item) {
                $rawMaterial = $item->rawMaterial;
                $oldStock = $rawMaterial->current_stock;
                $oldPrice = $rawMaterial->price_per_unit;
                
                $newStock = $item->qty;
                $newPrice = $item->unit_price;
                $totalStock = $oldStock + $newStock;
                
                if ($totalStock > 0) {
                    $rawMaterial->price_per_unit = (($oldStock * $oldPrice) + ($newStock * $newPrice)) / $totalStock;
                } else {
                    $rawMaterial->price_per_unit = $newPrice;
                }
                
                $rawMaterial->current_stock = $totalStock;
                $rawMaterial->save();
            }
        });
    }

    public function decreaseStock()
    {
        DB::transaction(function () {
            foreach ($this->items as $item) {
                $rawMaterial = $item->rawMaterial;
                $rawMaterial->decrement('current_stock', $item->qty);
            }
        });
    }
}
