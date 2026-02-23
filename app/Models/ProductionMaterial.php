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
    ];

    protected $casts = [
        'quantity_per_unit' => 'decimal:2',
        'quantity_used'     => 'decimal:2',
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
}
