<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'raw_material_id',
        'ingredient_product_id',
        'quantity_required',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function ingredientProduct()
    {
        return $this->belongsTo(Product::class, 'ingredient_product_id');
    }

    public function getIngredientNameAttribute()
    {
        return $this->ingredient_product_id ? $this->ingredientProduct->name : $this->rawMaterial->name;
    }

    public function getCostPerUnitAttribute()
    {
        return $this->ingredient_product_id ? $this->ingredientProduct->base_price : $this->rawMaterial->price_per_unit;
    }
}
