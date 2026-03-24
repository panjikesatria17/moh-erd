<?php

namespace App\Models;

use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\ProductPriceHistory;
use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\VendorMarginPayment;
use App\Models\VendorPerformance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property ?string $owner_name
 * @property ?string $email
 * @property ?string $phone
 * @property ?string $address
 * @property bool $is_affiliate
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $invoices
 */
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

    public function marginPayments(): HasMany
    {
        return $this->hasMany(VendorMarginPayment::class);
    }
}
