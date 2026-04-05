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
        static::saving(function (ProductionEvent $event) {
            if ($event->status === 'completed' && $event->getOriginal('status') !== 'completed') {
                $product = $event->product;
                
                // Cek ketersediaan stok bahan baku sebelum memproses
                foreach ($product->recipeItems as $recipeItem) {
                    $rawMaterial = $recipeItem->rawMaterial;
                    $quantityNeeded = $recipeItem->quantity_required * $event->quantity_produced;
                    
                    if ($rawMaterial->current_stock < $quantityNeeded) {
                        throw new \Exception("Stok {$rawMaterial->name} tidak mencukupi untuk produksi ini (Butuh: {$quantityNeeded}, Tersedia: {$rawMaterial->current_stock})");
                    }
                }
            }
        });

        static::updated(function (ProductionEvent $event) {
            $oldStatus = $event->getOriginal('status');

            // 1. DARI pending/lainnya KE completed
            if ($oldStatus !== 'completed' && $event->status === 'completed') {
                $event->processProduction();
            }

            // 2. DARI completed KE lainnya (Pembatalan)
            if ($oldStatus === 'completed' && $event->status !== 'completed') {
                $event->reverseProduction();
            }
        });

        static::created(function (ProductionEvent $event) {
            if ($event->status === 'completed') {
                $event->processProduction();
            }
        });

        static::deleted(function (ProductionEvent $event) {
            if ($event->status === 'completed') {
                $event->reverseProduction();
            }
        });
    }

    public function reverseProduction()
    {
        $product = $this->product;
        
        // 1. Balikkan stok bahan baku
        foreach ($product->recipeItems as $recipeItem) {
            $rawMaterial = $recipeItem->rawMaterial;
            $quantityToRestore = $recipeItem->quantity_required * $this->quantity_produced;
            $rawMaterial->increment('current_stock', $quantityToRestore);
        }

        // 2. Kurangi stok produk jadi
        $product->decrement('current_stock', $this->quantity_produced);
        $product->save();
    }

    public function processProduction()
    {
        $product = $this->product;
        $oldStock = $product->current_stock;
        $oldHpp = $product->hpp ?? $product->base_price ?? 0;
        
        $costPerUnitProduksi = 0;

        // Potong stok bahan baku & hitung modal real-time
        foreach ($product->recipeItems as $recipeItem) {
            $rawMaterial = $recipeItem->rawMaterial;
            $quantityToDeduct = $recipeItem->quantity_required * $this->quantity_produced;
            
            $rawMaterial->decrement('current_stock', $quantityToDeduct);
            
            $costPerUnitProduksi += ($recipeItem->quantity_required * $rawMaterial->price_per_unit);
        }

        // Hitung HPP dengan metode Rata-Rata Tertimbang
        $newQuantity = $this->quantity_produced;
        $totalStock = $oldStock + $newQuantity;
        
        if ($totalStock > 0) {
            $totalNilaiLama = $oldStock * $oldHpp;
            $totalNilaiBaru = $newQuantity * $costPerUnitProduksi;
            $product->hpp = ($totalNilaiLama + $totalNilaiBaru) / $totalStock;
            $product->base_price = $product->hpp; // Sinkronkan ke base_price agar BoM akurat
        } else {
            $product->hpp = $costPerUnitProduksi;
            $product->base_price = $costPerUnitProduksi;
        }

        $product->current_stock = $totalStock;
        $product->save();
    }
}
