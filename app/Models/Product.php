<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    /**
     * Mass-assignable fields.
     */
    protected $fillable = [
        'name',
        'category_id',
        'type',
        'price',
        'cost_price',
        'last_purchase_price',
        'target_margin',
        'stock',
        'unit',
    ];

    /** Cast numeric money fields for consistency */
    protected $casts = [
        'price'               => 'decimal:2',
        'cost_price'          => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'target_margin'       => 'decimal:2',
        'stock'               => 'decimal:2',
    ];

    /** Optionally expose a formatted stock string in JSON if needed */
    protected $appends = ['formatted_stock'];

    /* =========================================================================
     | Relationships
     |======================================================================== */

    public function category(): BelongsTo
    {
        // Category supports SoftDeletes; show even if soft-deleted
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** Recipe: raw materials needed to produce this product */
    public function recipeItems(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'product_id');
    }

    /** Reverse: recipes where this item is used as a raw material */
    public function usedInRecipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'raw_material_id');
    }

    /* =========================================================================
     | Query scopes (handy for controllers)
     |======================================================================== */

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!($term = trim((string) $term))) return $q;

        return $q->where(function ($w) use ($term) {
            $w->where('name', 'like', "%{$term}%");
        });
    }

    public function scopeCategoryId(Builder $q, $categoryId): Builder
    {
        return $categoryId ? $q->where('category_id', $categoryId) : $q;
    }

    /** Only finished products */
    public function scopeProducts(Builder $q): Builder
    {
        return $q->where('type', 'product');
    }

    /** Only raw materials */
    public function scopeRawMaterials(Builder $q): Builder
    {
        return $q->where('type', 'raw_material');
    }

    /* =========================================================================
     | Computed / Helpers
     |======================================================================== */

    /** Current stock = IN - OUT (from movements) */
    public function currentStock(): float
    {
        $in  = (float) $this->stockMovements()->where('type', 'in')->sum('quantity');
        $out = (float) $this->stockMovements()->where('type', 'out')->sum('quantity');
        return round($in - $out, 2);
    }

    /** Weighted Average Cost computed from IN movements */
    public function weightedAverageCost(): float
    {
        $row = $this->stockMovements()
            ->where('type', 'in')
            ->selectRaw('COALESCE(SUM(quantity),0)       as qty')
            ->selectRaw('COALESCE(SUM(quantity*unit_cost),0) as cost')
            ->first();

        $qty  = (float)($row->qty  ?? 0);
        $cost = (float)($row->cost ?? 0);

        return $qty > 0
            ? round($cost / $qty, 2)
            : round((float)($this->cost_price ?? 0), 2);
    }

    /** Monetary value of current stock (units * WAC) */
    public function stockValue(): float
    {
        return round($this->currentStock() * $this->weightedAverageCost(), 2);
    }

    /** Human-friendly stock display */
    public function getFormattedStockAttribute(): string
    {
        return number_format($this->currentStock(), 2);
    }

    /** Stock display with unit symbol */
    public function getFormattedStockWithUnitAttribute(): string
    {
        $unit = $this->unit ?? 'pcs';
        return number_format($this->currentStock(), 2) . ' ' . $unit;
    }

    /** Low-stock flag, configurable via config('inventory.low_stock', 5) */
    public function isLowStock(?int $threshold = null): bool
    {
        $threshold = $threshold ?? (int) config('inventory.low_stock', 5);
        return $this->currentStock() <= $threshold;
    }

    /**
     * Estimated manufacturing cost from Recipe (BOM) based on current raw material costs.
     */
    public function estimatedRecipeCost(): ?float
    {
        // Must have loaded or present recipe items
        $items = $this->recipeItems()->with('rawMaterial')->get();
        if ($items->isEmpty()) {
            return null;
        }

        $totalCost = 0.0;
        foreach ($items as $ri) {
            $rm = $ri->rawMaterial;
            if (!$rm) continue;

            $unitCost = (float)($rm->cost_price ?: $rm->price ?: 0);
            $neededBase = $ri->baseQuantityNeeded(1.0);
            $totalCost += ($neededBase * $unitCost);
        }

        return round($totalCost, 2);
    }

    /**
     * Effective cost price: uses cost_price if set, or falls back to recipe cost or price.
     */
    public function effectiveCostPrice(): float
    {
        if ((float)($this->cost_price ?? 0) > 0) {
            return (float)$this->cost_price;
        }

        $recipeCost = $this->estimatedRecipeCost();
        if ($recipeCost !== null && $recipeCost > 0) {
            return $recipeCost;
        }

        return (float)($this->price ?? 0);
    }

    /**
     * Suggested selling price based on cost and target profit margin %.
     */
    public function suggestedSellingPrice(?float $customCost = null): float
    {
        $cost = $customCost !== null ? $customCost : $this->effectiveCostPrice();
        $margin = (float)($this->target_margin ?? 0);

        if ($margin > 0 && $margin < 100) {
            // Margin formula: Price = Cost / (1 - Margin/100) or Cost * (1 + Markup/100)
            // Using standard markup calculation: Cost * (1 + Margin/100)
            return round($cost * (1 + ($margin / 100)), 2);
        }

        return round($cost, 2);
    }

    /**
     * Current profit margin percentage: ((Price - Cost) / Price) * 100
     */
    public function profitMarginPercentage(): ?float
    {
        $selling = (float)($this->price ?? 0);
        if ($selling <= 0) return null;

        $cost = $this->effectiveCostPrice();
        return round((($selling - $cost) / $selling) * 100, 1);
    }
}
