<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'product_id',
        'qty',
        'total_price',
        'net_income',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'channel' => 'string',
        'qty' => 'integer',
        'total_price' => 'decimal:2',
        'net_income' => 'decimal:2',
        'status' => 'string',
    ];

    /**
     * Get the product associated with this sale.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who recorded this sale.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (self $sale) {
            // Calculate net_income based on channel
            if ($sale->channel === 'gofood') {
                // Deduct 20% commission for gofood
                $sale->net_income = $sale->total_price * 0.80;
            } else {
                // For stand and po channels, net_income equals total_price
                $sale->net_income = $sale->total_price;
            }

            // Get the product and subtract qty from current_stock
            $product = Product::find($sale->product_id);
            if ($product) {
                $product->decrement('current_stock', $sale->qty);
            }
        });
    }
}
