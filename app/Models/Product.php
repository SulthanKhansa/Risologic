<?php

namespace App\Models;

use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'slug',
        'base_price',
        'current_stock',
        'batch_yield',
        'hpp',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'current_stock' => 'integer',
        'batch_yield' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function (Product $product) {
            if (empty($product->slug) && $product->name) {
                $product->slug = Str::slug($product->name) . '-' . uniqid();
            }
        });
    }

    /**
     * Get all sales for this product.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function productionEvents(): HasMany
    {
        return $this->hasMany(ProductionEvent::class);
    }

    /**
     * Calculate HPP (Total Material Cost) based on the current recipe.
     */
    public function calculateHppFromRecipe(): float
    {
        $total = 0;
        foreach ($this->recipeItems as $item) {
            $total += $item->quantity_required * $item->cost_per_unit;
        }
        return (float) $total;
    }

    /**
     * Compute and save the base_price natively to ensure DB integrity
     */
    public function updateBasePriceFromRecipe()
    {
        try {
            // Force reload the relationship to avoid Filament memory caching during Edit
            $this->load('recipeItems');
            
            $total = $this->calculateHppFromRecipe();
            $batchYield = max(1, (int) ($this->batch_yield ?? 1));
            $this->base_price = $total / $batchYield;
            $this->saveQuietly();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update base_price from recipe: ' . $e->getMessage(), [
                'product_id' => $this->id,
                'recipe_items' => $this->recipeItems->toArray()
            ]);
        }
    }
}
