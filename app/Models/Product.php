<?php

namespace App\Models;

use App\Models\ProductCategory;
use App\Models\ProductPriceHistory;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequestItem;
use App\Models\RejectedItem;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Product extends Model
{
    use HasFactory, SoftDeletes;

    // Nilai Inventory (otomatis)
    public function getNilaiInventoryAttribute()
    {
        // Pastikan purchase_price dan total_inventory tidak null
        return ($this->total_inventory ?? 0) * ($this->purchase_price ?? 0);
    }

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'product_category_id',
        'vendor_id',
        'unit',
        'pcs_per_box',
        'pcs_per_pack',
        'purchase_price',
        'selling_price',
        'government_price_cap',
        'price_variance_percent',
        'price_variance_amount',
        'minimum_stock_level',
        'reorder_stock_level',
        'total_inventory', // qty
        'is_active',
        'is_ad_hoc',
    ];

    protected function casts(): array
    {
        return [
            'government_price_cap' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'price_variance_percent' => 'decimal:2',
            'price_variance_amount' => 'decimal:2',
            'pcs_per_box' => 'decimal:4',
            'pcs_per_pack' => 'decimal:4',
            'minimum_stock_level' => 'decimal:2',
            'reorder_stock_level' => 'decimal:2',
            'is_active' => 'boolean',
            'is_ad_hoc' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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

    public function rejectedItems(): HasMany
    {
        return $this->hasMany(RejectedItem::class);
    }
}
