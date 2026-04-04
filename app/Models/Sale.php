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
        'admin_fee',
        'net_income',
        'total_cost',
        'gross_profit',
        'margin_percentage',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'channel' => 'string',
        'qty' => 'integer',
        'total_price' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'net_income' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'margin_percentage' => 'decimal:2',
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
            // Get the product to fetch base_price (HPP)
            $product = Product::find($sale->product_id);

            if ($product) {
                // 1. Calculate total_cost = HPP * qty
                $sale->total_cost = $product->base_price * $sale->qty;

                // 2. Default admin_fee if online and not set
                if ($sale->channel === 'online' && ($sale->admin_fee === null || $sale->admin_fee == 0)) {
                    $sale->admin_fee = $sale->total_price * 0.20;
                }

                // 3. Calculate net_income = total_price - admin_fee
                $sale->net_income = $sale->total_price - ($sale->admin_fee ?? 0);

                // 4. Calculate gross_profit = net_income - total_cost
                $sale->gross_profit = $sale->net_income - $sale->total_cost;

                // 5. Calculate margin_percentage = (gross_profit / net_income) * 100
                if ($sale->net_income > 0) {
                    $sale->margin_percentage = ($sale->gross_profit / $sale->net_income) * 100;
                } else {
                    $sale->margin_percentage = 0;
                }

                // 6. Subtract qty from product current_stock
                $product->decrement('current_stock', $sale->qty);
            }
        });
    }
}
