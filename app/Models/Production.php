<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Production extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'status',
        'notes',
        'user_id',
        'produced_at',
    ];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'produced_at' => 'datetime',
    ];

    /** The finished product that was produced */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** The raw materials consumed in this production run */
    public function materials(): HasMany
    {
        return $this->hasMany(ProductionMaterial::class);
    }

    /** The user who recorded this production */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /** Total raw material cost for this production run */
    public function totalMaterialCost(): float
    {
        return $this->materials->sum(function ($m) {
            return ($m->rawMaterial->price ?? 0) * $m->quantity_used;
        });
    }
}
