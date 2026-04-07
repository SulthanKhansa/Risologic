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
        'slug',
        'base_price',
        'current_stock',
        'hpp',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'current_stock' => 'integer',
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
            if ($item->rawMaterial) {
                $total += $item->quantity_required * $item->rawMaterial->price_per_unit;
            }
        }
        return (float) $total;
    }
}
