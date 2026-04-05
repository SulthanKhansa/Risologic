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

        static::updated(function (self $sale) {
            // Handle stock adjustment if qty or product_id changed
            if ($sale->isDirty(['qty', 'product_id'])) {
                $oldProductId = $sale->getOriginal('product_id');
                $oldQty = $sale->getOriginal('qty');

                // If product changed
                if ($sale->isDirty('product_id')) {
                    // Restore old product stock
                    $oldProduct = Product::find($oldProductId);
                    if ($oldProduct) {
                        $oldProduct->increment('current_stock', $oldQty);
                    }
                    // Deduct from new product stock
                    $sale->product->decrement('current_stock', $sale->qty);
                } else {
                    // Same product, adjust by difference
                    $diffQty = $sale->qty - $oldQty;
                    $sale->product->decrement('current_stock', $diffQty);
                }
            }
        });

        static::deleted(function (self $sale) {
            // Restore stock on delete
            if ($sale->product) {
                $sale->product->increment('current_stock', $sale->qty);
            }
        });
    }
}
