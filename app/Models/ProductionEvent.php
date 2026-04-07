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
                
                // Cek ketersediaan stok bahan baku / produk setengah jadi sebelum memproses
                foreach ($product->recipeItems as $recipeItem) {
                    $ingredient = $recipeItem->ingredient_product_id ? $recipeItem->ingredientProduct : $recipeItem->rawMaterial;
                    if (!$ingredient) continue;

                    $quantityNeeded = $recipeItem->quantity_required * $event->quantity_produced;
                    
                    if ($ingredient->current_stock < $quantityNeeded) {
                        throw new \Exception("Stok {$recipeItem->ingredient_name} tidak mencukupi untuk produksi ini (Butuh: {$quantityNeeded}, Tersedia: {$ingredient->current_stock})");
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
        
        // 1. Balikkan stok bahan baku/produk pengganti
        foreach ($product->recipeItems as $recipeItem) {
            $ingredient = $recipeItem->ingredient_product_id ? $recipeItem->ingredientProduct : $recipeItem->rawMaterial;
            if (!$ingredient) continue;

            $quantityToRestore = $recipeItem->quantity_required * $this->quantity_produced;
            $ingredient->increment('current_stock', $quantityToRestore);
        }

        // 2. Kurangi stok produk jadi (batches × yield)
        $batchYield = max(1, $product->batch_yield ?? 1);
        $product->decrement('current_stock', $this->quantity_produced * $batchYield);
        $product->save();
    }

    public function processProduction()
    {
        $product = $this->product;
        $batchYield = max(1, $product->batch_yield ?? 1);
        $oldStock = $product->current_stock;
        $oldHpp = $product->hpp ?? $product->base_price ?? 0;
        
        $costPerBatch = 0;

        // Potong stok bahan baku & hitung modal real-time
        // quantity_produced = jumlah batch yang diproduksi
        foreach ($product->recipeItems as $recipeItem) {
            $ingredient = $recipeItem->ingredient_product_id ? $recipeItem->ingredientProduct : $recipeItem->rawMaterial;
            if (!$ingredient) continue;

            $quantityToDeduct = $recipeItem->quantity_required * $this->quantity_produced;
            
            $ingredient->decrement('current_stock', $quantityToDeduct);
            
            $costPerBatch += ($recipeItem->quantity_required * $recipeItem->cost_per_unit);
        }

        // HPP per pcs = biaya per batch / yield
        $costPerPcs = $costPerBatch / $batchYield;

        // Hitung HPP dengan metode Rata-Rata Tertimbang
        $newPcsProduced = $this->quantity_produced * $batchYield;
        $totalStock = $oldStock + $newPcsProduced;
        
        if ($totalStock > 0) {
            $totalNilaiLama = $oldStock * $oldHpp;
            $totalNilaiBaru = $newPcsProduced * $costPerPcs;
            $product->hpp = ($totalNilaiLama + $totalNilaiBaru) / $totalStock;
            $product->base_price = $product->hpp;
        } else {
            $product->hpp = $costPerPcs;
            $product->base_price = $costPerPcs;
        }

        $product->current_stock = $totalStock;
        $product->save();
    }
}
