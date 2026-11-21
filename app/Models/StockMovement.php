<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'batch_number',
        'expiry_date',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'source_type',
        'source_id',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function isFlowIn(): bool
    {
        return strtolower($this->type ?? '') === 'in';
    }

    public function isFlowOut(): bool
    {
        return strtolower($this->type ?? '') === 'out';
    }

    /**
     * Resolve human-friendly reference or note from polymorphic source or movement record.
     */
    public function referenceText(): ?string
    {
        $src = $this->source;
        if ($src) {
            if ($this->source_type === Production::class) {
                return $src->batch_number ?: ($src->notes ?: "Production Run #{$this->source_id}");
            }
            if ($this->source_type === Sale::class) {
                return $src->reference_number ?: ($src->invoice_number ?: ($src->notes ?: "Sale #{$this->source_id}"));
            }
            if ($this->source_type === Purchase::class) {
                return $src->reference_number ?: ($src->invoice_number ?: ($src->notes ?: "PO #{$this->source_id}"));
            }
            if ($this->source_type === StockAdjustment::class) {
                return $src->notes ?: "Stock Adjustment #{$this->source_id}";
            }
            if ($this->source_type === PurchaseReturn::class) {
                return $src->return_reason ?: ($src->remarks ?: "Supplier Return #{$this->source_id}");
            }
            if ($this->source_type === SaleReturn::class) {
                return $src->reason ?: ($src->remarks ?: "Customer Return #{$this->source_id}");
            }
            if ($this->source_type === ItemLoan::class) {
                return $src->borrower_name ?: ($src->notes ?: "Item Loan #{$this->source_id}");
            }
            if ($this->source_type === ItemLoanReturn::class) {
                return $src->remarks ?: "Loan Return #{$this->source_id}";
            }
            if ($this->source_type === User::class) {
                return "Manual Audit by " . ($src->name ?? 'User');
            }
        }

        return $this->reference
            ?? $this->note
            ?? data_get($src, 'reference')
            ?? data_get($src, 'note')
            ?? data_get($src, 'remarks')
            ?? null;
    }

    /**
     * Resolve structured origin details (label, deep-link, visual tag) for the movement source.
     */
    public function sourceDetails(): array
    {
        $type = $this->source_type;
        $id = $this->source_id;
        $src = $this->source;

        if ($type === Purchase::class) {
            return [
                'origin' => 'Purchase (IN)',
                'label' => "Purchase #{$id}",
                'link' => $id ? route('purchases.show', $id) : null,
                'type' => 'purchase',
            ];
        }

        if ($type === Sale::class) {
            return [
                'origin' => 'Sale (OUT)',
                'label' => "Sale #{$id}",
                'link' => $id ? route('sales.show', $id) : null,
                'type' => 'sale',
            ];
        }

        if ($type === Production::class) {
            return [
                'origin' => $this->isFlowIn() ? 'Production Output (IN)' : 'Material Consumption (OUT)',
                'label' => "Production #{$id}",
                'link' => $id ? route('productions.show', $id) : null,
                'type' => 'production',
            ];
        }

        if ($type === StockAdjustment::class || $type === User::class) {
            $prodLink = $this->product
                ? ($this->product->type === 'raw_material' ? route('raw-materials.show', $this->product) : route('products.show', $this->product))
                : null;

            return [
                'origin' => 'Stock Audit / Adjustment',
                'label' => $id ? "Adjustment #{$id}" : "Manual Adjustment",
                'link' => $prodLink,
                'type' => 'adjustment',
            ];
        }

        if ($type === PurchaseReturn::class) {
            $purchaseId = data_get($src, 'purchase_id');
            return [
                'origin' => 'Return to Supplier (OUT)',
                'label' => "Supplier Return #{$id}",
                'link' => $purchaseId ? route('purchases.show', $purchaseId) : null,
                'type' => 'purchase_return',
            ];
        }

        if ($type === SaleReturn::class) {
            $saleId = data_get($src, 'sale_id');
            return [
                'origin' => 'Customer Return (IN)',
                'label' => "Customer Return #{$id}",
                'link' => $saleId ? route('sales.show', $saleId) : null,
                'type' => 'sale_return',
            ];
        }

        if ($type === ItemLoan::class) {
            return [
                'origin' => 'Item Loan (OUT)',
                'label' => "Loan #{$id}",
                'link' => $id ? route('item-loans.show', $id) : null,
                'type' => 'loan',
            ];
        }

        if ($type === ItemLoanReturn::class) {
            $loanId = data_get($src, 'item_loan_id');
            return [
                'origin' => 'Loan Return (IN)',
                'label' => "Loan Return #{$id}",
                'link' => $loanId ? route('item-loans.show', $loanId) : null,
                'type' => 'loan_return',
            ];
        }

        return [
            'origin' => 'Direct Transaction',
            'label' => $id ? "Ref #{$id}" : 'System Audit',
            'link' => null,
            'type' => 'system',
        ];
    }
}
