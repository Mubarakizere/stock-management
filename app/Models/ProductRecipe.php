<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipe extends Model
{
    protected $fillable = [
        'product_id',
        'raw_material_id',
        'quantity',
        'unit',
        'conversion_factor',
    ];

    protected $casts = [
        'quantity'          => 'decimal:2',
        'conversion_factor' => 'decimal:6',
    ];

    /** The finished product this recipe belongs to */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** The raw material ingredient */
    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }

    /**
     * Compute how much raw material in its BASE unit is required for a given production quantity.
     * Formula: quantity * conversion_factor * $productionQty
     */
    public function baseQuantityNeeded(float $productionQty = 1.0): float
    {
        $factor = (float) ($this->conversion_factor ?: 1.0);
        return round((float) $this->quantity * $factor * $productionQty, 4);
    }

    /**
     * Human-friendly formatted recipe quantity with unit (e.g. "250 g", "1 bottle").
     */
    public function formattedQuantity(): string
    {
        $qty = (float) $this->quantity;
        $formatted = number_format($qty, 2);
        if (str_contains($formatted, '.')) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }
        $unit = $this->unit ?: 'pcs';
        return "{$formatted} {$unit}";
    }
}
