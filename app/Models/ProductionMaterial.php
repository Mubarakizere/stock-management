<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMaterial extends Model
{
    protected $fillable = [
        'production_id',
        'raw_material_id',
        'quantity_per_unit',
        'quantity_used',
        'unit',
        'base_quantity_used',
    ];

    protected $casts = [
        'quantity_per_unit'  => 'decimal:2',
        'quantity_used'      => 'decimal:2',
        'base_quantity_used' => 'decimal:4',
    ];

    /** The production run this belongs to */
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    /** The raw material that was consumed */
    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }

    /**
     * Display quantity consumed with unit.
     */
    public function formattedQuantityUsed(): string
    {
        $qty = (float) $this->quantity_used;
        $formatted = number_format($qty, 2);
        if (str_contains($formatted, '.')) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }
        $unit = $this->unit ?: ($this->rawMaterial->unit ?? 'pcs');
        return "{$formatted} {$unit}";
    }
}
