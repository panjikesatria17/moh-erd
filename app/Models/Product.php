<?php

namespace App\Models;

use App\Models\ProductCategory;
use App\Models\ProductPriceHistory;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequestItem;
use App\Models\StockAlert;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'product_category_id',
        'unit',
        'government_price_cap',
        'minimum_stock_level',
        'reorder_stock_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'government_price_cap' => 'decimal:2',
            'minimum_stock_level' => 'decimal:2',
            'reorder_stock_level' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function purchaseRequestItems(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }
}
