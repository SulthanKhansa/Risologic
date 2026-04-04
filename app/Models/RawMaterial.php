<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'current_stock',
        'price_per_unit',
    ];

    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }
}
