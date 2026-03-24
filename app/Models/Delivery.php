<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\RejectedItem;
use App\Models\Sppg;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $number
 * @property int $purchase_order_id
 * @property ?int $goods_receipt_id
 * @property int $sppg_id
 * @property int $vendor_id
 * @property int $delivered_by
 * @property \Illuminate\Support\Carbon $delivery_date
 * @property ?string $delivery_proof_image_path
 * @property ?string $signed_delivery_note_path
 * @property ?string $proof_uploaded_at
 * @property ?string $delivered_at
 * @property DocumentStatus $status
 * @property string $total_amount
 * @property string $invoiced_po_amount
 * @property ?string $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read Sppg $sppg
 * @property-read Vendor $vendor
 * @property-read User $deliveredBy
 */
class Delivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'purchase_order_id',
        'goods_receipt_id',
        'sppg_id',
        'vendor_id',
        'delivered_by',
        'delivery_date',
        'delivery_proof_image_path',
        'signed_delivery_note_path',
        'proof_uploaded_at',
        'delivered_at',
        'status',
        'total_amount',
        'invoiced_po_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'proof_uploaded_at' => 'datetime',
            'delivered_at' => 'datetime',
            'status' => DocumentStatus::class,
            'total_amount' => 'decimal:2',
            'invoiced_po_amount' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function rejectedItems(): HasMany
    {
        return $this->hasMany(RejectedItem::class);
    }
}
