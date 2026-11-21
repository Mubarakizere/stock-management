<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Production extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'mfg_date',
        'expiry_date',
        'quantity',
        'expected_quantity',
        'waste_quantity',
        'waste_reason',
        'yield_percentage',
        'unit_cost',
        'total_cost',
        'status',
        'notes',
        'user_id',
        'produced_at',
    ];

    protected $casts = [
        'quantity'          => 'decimal:2',
        'expected_quantity' => 'decimal:2',
        'waste_quantity'    => 'decimal:2',
        'yield_percentage'  => 'decimal:2',
        'unit_cost'         => 'decimal:2',
        'total_cost'        => 'decimal:2',
        'mfg_date'          => 'date',
        'expiry_date'       => 'date',
        'produced_at'       => 'datetime',
    ];

    /* =========================================================================
     | Relationships
     |======================================================================== */

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

    /** Stock movements associated with this production */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'source_id')
            ->where('source_type', self::class);
    }

    /** Sales that dispensed items from this batch */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'batch_number', 'batch_number');
    }

    /* =========================================================================
     | Scopes
     |======================================================================== */

    /** Scope for active non-expired batches */
    public function scopeActiveBatches(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>=', now()->toDateString());
        });
    }

    /** Scope for batches expiring soon (default 30 days) */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        $today = now()->toDateString();
        $target = now()->addDays($days)->toDateString();

        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $target]);
    }

    /** Scope for expired batches */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString());
    }

    /* =========================================================================
     | Batch & Yield Calculations
     |======================================================================== */

    /**
     * Auto-generate a clean, unique enterprise batch lot number.
     * Format: BATCH-YYYYMMDD-XXXX (e.g. BATCH-20261121-8402)
     */
    public static function generateBatchNumber(?int $productId = null): string
    {
        $dateStr = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        $prefix = $productId ? "BATCH-P{$productId}-{$dateStr}-{$random}" : "BATCH-{$dateStr}-{$random}";

        // Ensure uniqueness
        while (self::where('batch_number', $prefix)->exists()) {
            $random = strtoupper(substr(uniqid(), -4));
            $prefix = $productId ? "BATCH-P{$productId}-{$dateStr}-{$random}" : "BATCH-{$dateStr}-{$random}";
        }

        return $prefix;
    }

    /** Scrap / Shrinkage Percentage */
    public function scrapPercentage(): float
    {
        $expected = (float) ($this->expected_quantity ?: ($this->quantity + $this->waste_quantity));
        if ($expected <= 0) return 0.0;

        return round(((float) $this->waste_quantity / $expected) * 100, 2);
    }

    /** Total Monetary Loss due to Scrap/Waste */
    public function totalScrapCost(): float
    {
        $costPerUnit = (float) ($this->unit_cost ?: ($this->product->cost_price ?? 0));
        return round((float) $this->waste_quantity * $costPerUnit, 2);
    }

    /** Total raw material cost calculated directly from consumed materials */
    public function totalMaterialCost(): float
    {
        if ($this->total_cost !== null && (float) $this->total_cost > 0) {
            return (float) $this->total_cost;
        }

        return $this->materials->sum(function ($m) {
            $rawMat = $m->rawMaterial;
            $unitCost = (float) ($rawMat->cost_price ?: $rawMat->price ?: 0);
            $qty = (float) ($m->base_quantity_used ?: $m->quantity_used);
            return $unitCost * $qty;
        });
    }

    /** Expiration status checks */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && !$this->isExpired() && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function daysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) return null;
        return (int) now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Expiration badge styling and label for UI.
     */
    public function expiryStatusBadge(): array
    {
        if (!$this->expiry_date) {
            return [
                'status' => 'no_expiry',
                'label'  => 'No Expiry',
                'class'  => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                'dot'    => 'bg-slate-400',
            ];
        }

        $days = $this->daysUntilExpiry();

        if ($days < 0) {
            return [
                'status' => 'expired',
                'label'  => 'Expired (' . abs($days) . 'd ago)',
                'class'  => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/50',
                'dot'    => 'bg-rose-500',
            ];
        }

        if ($days <= 30) {
            return [
                'status' => 'expiring_soon',
                'label'  => "Expiring in {$days}d",
                'class'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/50',
                'dot'    => 'bg-amber-500 animate-pulse',
            ];
        }

        return [
            'status' => 'fresh',
            'label'  => "Fresh ({$days}d left)",
            'class'  => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50',
            'dot'    => 'bg-emerald-500',
        ];
    }

    /**
     * Yield & Scrap efficiency status badge.
     */
    public function yieldStatusBadge(): array
    {
        $yield = (float) ($this->yield_percentage ?? 100);

        if ($yield >= 95.0) {
            return [
                'status' => 'optimal',
                'label'  => number_format($yield, 1) . '% Yield (Optimal)',
                'class'  => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50',
                'badge'  => 'Optimal',
            ];
        }

        if ($yield >= 85.0) {
            return [
                'status' => 'moderate_scrap',
                'label'  => number_format($yield, 1) . '% Yield (' . number_format($this->waste_quantity, 1) . ' units scrap)',
                'class'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/50',
                'badge'  => 'Moderate Scrap',
            ];
        }

        return [
            'status' => 'high_waste',
            'label'  => number_format($yield, 1) . '% Yield (High Waste: ' . number_format($this->waste_quantity, 1) . ')',
            'class'  => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/50',
            'badge'  => 'High Scrap Alert',
        ];
    }
}
