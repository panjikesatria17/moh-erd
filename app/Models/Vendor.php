<?php

namespace App\Models;

use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\ProductPriceHistory;
use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\VendorPerformance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'owner_name',
        'email',
        'phone',
        'address',
        'is_affiliate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_affiliate' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function defaultSppgs(): HasMany
    {
        return $this->hasMany(Sppg::class, 'default_vendor_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    public function performanceRecords(): HasMany
    {
        return $this->hasMany(VendorPerformance::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
