<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'unit',
        'price_per_unit',
        'pack_price',
        'pack_size',
        'current_stock',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'pack_price' => 'decimal:2',
        'pack_size' => 'decimal:2',
    ];

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class);
    }
}
