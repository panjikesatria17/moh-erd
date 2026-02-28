<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Approval;
use App\Models\Delivery;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\Sppg;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'purchase_request_id',
        'sppg_id',
        'vendor_id',
        'ordered_by',
        'order_date',
        'expected_date',
        'status',
        'is_direct_purchase',
        'notes',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'status' => DocumentStatus::class,
            'is_direct_purchase' => 'boolean',
            'total_amount' => 'decimal:2',
        ];
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function recalculateTotal(): void
    {
        $total = $this->items()->sum('subtotal');
        $this->forceFill(['total_amount' => $total])->save();
    }
}
