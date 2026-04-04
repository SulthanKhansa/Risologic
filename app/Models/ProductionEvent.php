<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity_produced',
        'production_date',
        'status',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {
        static::created(function (ProductionEvent $event) {
            if ($event->status === 'completed') {
                $product = $event->product;
                $oldStock = $product->current_stock;
                $oldHpp = $product->hpp ?? 0;
                
                // 1. Hitung biaya produksi per 1 Unit
                $costPerUnitProduksi = 0;

                // 2. Potong stok bahan baku berdasarkan resep
                foreach ($product->recipeItems as $recipeItem) {
                    $rawMaterial = $recipeItem->rawMaterial;
                    $quantityToDeduct = $recipeItem->quantity_required * $event->quantity_produced;
                    $rawMaterial->current_stock -= $quantityToDeduct;
                    $rawMaterial->save();
                    
                    // Modal resep = Harga beli raw material * takaran resep
                    $costPerUnitProduksi += ($recipeItem->quantity_required * $rawMaterial->price_per_unit);
                }

                // 3. Hitung HPP dengan metode Rata-Rata Tertimbang (Moving Average)
                $newQuantity = $event->quantity_produced;
                $totalStock = $oldStock + $newQuantity;
                
                if ($totalStock > 0) {
                    $totalNilaiLama = $oldStock * $oldHpp;
                    $totalNilaiBaru = $newQuantity * $costPerUnitProduksi;
                    
                    $product->hpp = ($totalNilaiLama + $totalNilaiBaru) / $totalStock;
                } else {
                    $product->hpp = $costPerUnitProduksi;
                }

                // 4. Update stok akhir produk
                $product->current_stock = $totalStock;
                $product->save();
            }
        });
    }
}
