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

    public const TYPE_PRODUCT = 'product';
    public const TYPE_RAW_MATERIAL = 'raw_material';

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

    /** Production batches of this finished product */
    public function batches(): HasMany
    {
        return $this->hasMany(Production::class, 'product_id')->latest('produced_at');
    }

    /** Active non-expired production batches */
    public function activeBatches(): HasMany
    {
        return $this->hasMany(Production::class, 'product_id')
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->latest('produced_at');
    }

    /**
     * Estimated manufacturing cost from Recipe (BOM) based on current raw material costs.
     * Supports $basis: 'wac' (Weighted Average Cost) or 'latest_po' (Latest PO / Purchase Price).
     */
    public function estimatedRecipeCost(string $basis = 'wac'): ?float
    {
        $items = $this->recipeItems()->with('rawMaterial')->get();
        if ($items->isEmpty()) {
            return null;
        }

        $totalCost = 0.0;
        foreach ($items as $ri) {
            $rm = $ri->rawMaterial;
            if (!$rm) continue;

            if ($basis === 'latest_po') {
                $unitCost = (float)($rm->last_purchase_price ?: $rm->cost_price ?: $rm->price ?: 0);
            } else {
                // Default: WAC
                $wac = $rm->weightedAverageCost();
                $unitCost = $wac > 0 ? $wac : (float)($rm->cost_price ?: $rm->price ?: 0);
            }

            $neededBase = $ri->baseQuantityNeeded(1.0);
            $totalCost += ($neededBase * $unitCost);
        }

        return round($totalCost, 2);
    }

    /**
     * Dynamic BOM Cost with basis: 'wac' (Weighted Average Cost) or 'latest_po' (Latest Purchase Order Price).
     */
    public function bomCost(string $basis = 'wac'): float
    {
        return $this->estimatedRecipeCost($basis) ?? (float)($this->cost_price ?? $this->price ?? 0);
    }

    /**
     * Detailed ingredient-by-ingredient BOM Cost Breakdown.
     * Shows recipe qty, base units needed, unit cost, line cost, and % contribution to total batch cost.
     */
    public function bomCostBreakdown(string $basis = 'wac'): array
    {
        $items = $this->recipeItems()->with('rawMaterial')->get();
        if ($items->isEmpty()) {
            return [];
        }

        $totalBom = $this->bomCost($basis);
        $breakdown = [];

        foreach ($items as $ri) {
            $rm = $ri->rawMaterial;
            if (!$rm) continue;

            if ($basis === 'latest_po') {
                $unitCost = (float)($rm->last_purchase_price ?: $rm->cost_price ?: $rm->price ?: 0);
            } else {
                $wac = $rm->weightedAverageCost();
                $unitCost = $wac > 0 ? $wac : (float)($rm->cost_price ?: $rm->price ?: 0);
            }

            $neededBase = $ri->baseQuantityNeeded(1.0);
            $lineCost   = round($neededBase * $unitCost, 2);
            $pctShare   = ($totalBom > 0) ? round(($lineCost / $totalBom) * 100, 1) : 0.0;

            $breakdown[] = [
                'raw_material_id'   => $rm->id,
                'name'              => $rm->name,
                'recipe_quantity'   => (float)$ri->quantity,
                'recipe_unit'       => $ri->unit ?: ($rm->unit ?: 'pcs'),
                'base_quantity'     => $neededBase,
                'base_unit'         => $rm->unit ?: 'pcs',
                'unit_cost'         => $unitCost,
                'line_cost'         => $lineCost,
                'pct_share'         => $pctShare,
                'in_stock'          => $rm->currentStock(),
            ];
        }

        return $breakdown;
    }

    /**
     * Margin Guard Status:
     * Evaluates whether the product's current selling price meets or violates the target margin threshold.
     */
    public function marginGuardStatus(string $basis = 'wac'): array
    {
        $bomCost = $this->bomCost($basis);
        $sellingPrice = (float)($this->price ?? 0);
        $targetMargin = (float)($this->target_margin ?: 25.0);

        $grossProfit = $sellingPrice - $bomCost;
        $marginPct = ($sellingPrice > 0) ? round(($grossProfit / $sellingPrice) * 100, 1) : 0.0;

        // Suggested price to achieve target margin: Cost / (1 - Margin/100)
        $suggestedPrice = ($targetMargin < 100) ? round($bomCost / (1 - ($targetMargin / 100)), 2) : $bomCost;

        $deficitPct = round($targetMargin - $marginPct, 1);

        if ($marginPct < ($targetMargin / 2) || $grossProfit <= 0) {
            $status = 'critical';
            $badge = [
                'label' => "Critical Margin Alert: {$marginPct}% (Target: {$targetMargin}%)",
                'class' => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/50',
                'dot'   => 'bg-rose-500 animate-ping',
            ];
        } elseif ($marginPct < $targetMargin) {
            $status = 'warning';
            $badge = [
                'label' => "Margin Warning: {$marginPct}% < {$targetMargin}%",
                'class' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/50',
                'dot'   => 'bg-amber-500',
            ];
        } else {
            $status = 'healthy';
            $badge = [
                'label' => "Healthy Margin: {$marginPct}%",
                'class' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50',
                'dot'   => 'bg-emerald-500',
            ];
        }

        return [
            'bom_cost'        => $bomCost,
            'selling_price'   => $sellingPrice,
            'gross_profit'    => round($grossProfit, 2),
            'margin_pct'      => $marginPct,
            'target_margin'   => $targetMargin,
            'deficit_pct'     => $deficitPct,
            'suggested_price' => $suggestedPrice,
            'status'          => $status,
            'badge'           => $badge,
        ];
    }

    /**
     * Synchronize computed BOM cost to product cost_price.
     * Optionally adjust selling price to match target margin if $autoAdjustPrice is true.
     */
    public function syncBomCostToCostPrice(bool $autoAdjustPrice = false, string $basis = 'wac'): array
    {
        $newCost = $this->estimatedRecipeCost($basis);
        if ($newCost === null) {
            return ['synced' => false, 'message' => 'No recipe configured for this product.'];
        }

        $oldCost = (float)($this->cost_price ?? 0);
        $this->cost_price = $newCost;

        $oldPrice = (float)($this->price ?? 0);
        $priceChanged = false;

        if ($autoAdjustPrice) {
            $targetMargin = (float)($this->target_margin ?: 25.0);
            if ($targetMargin > 0 && $targetMargin < 100) {
                $this->price = round($newCost / (1 - ($targetMargin / 100)), 2);
                $priceChanged = true;
            }
        }

        $this->save();

        return [
            'synced'        => true,
            'old_cost'      => $oldCost,
            'new_cost'      => $newCost,
            'old_price'     => $oldPrice,
            'new_price'     => (float)$this->price,
            'price_changed' => $priceChanged,
        ];
    }

    /**
     * Synchronize all finished goods whose recipes consume a given raw material.
     */
    public static function syncAffectedFinishedGoods(int $rawMaterialId, bool $autoAdjustPrice = false): array
    {
        $recipes = ProductRecipe::where('raw_material_id', $rawMaterialId)->get();
        $affected = [];

        foreach ($recipes as $r) {
            $product = $r->product;
            if ($product) {
                $res = $product->syncBomCostToCostPrice($autoAdjustPrice);
                $affected[$product->id] = [
                    'product_name' => $product->name,
                    'result'       => $res,
                ];
            }
        }

        return $affected;
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
