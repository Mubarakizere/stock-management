<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'type',
        'quantity',
        'unit_price',
        'subtotal',
        'cost_price',
        'profit',
    ];

    // 🔗 Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function returns()
{
    return $this->hasMany(\App\Models\SaleReturnItem::class);
}

    // 📈 Compute profit (helpful for reports)
    public function calculateProfit(): float
    {
        if (is_null($this->cost_price)) {
            return 0;
        }
        return round(($this->unit_price - $this->cost_price) * $this->quantity, 2);
    }
}
