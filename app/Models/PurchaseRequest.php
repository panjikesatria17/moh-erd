<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Approval;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequestItem;
use App\Models\Sppg;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $number
 * @property int $sppg_id
 * @property int $requested_by
 * @property int $requester_id
 * @property \Illuminate\Support\Carbon $request_date
 * @property \Illuminate\Support\Carbon $needed_date
 * @property DocumentStatus $status
 * @property ?string $notes
 * @property bool $is_additional
 * @property ?int $additional_to_po_id
 * @property string $total_amount
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property-read Sppg $sppg
 * @property-read User $requester
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseRequestItem> $items
 */
class PurchaseRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'sppg_id',
        'requested_by',
        'request_date',
        'needed_date',
        'status',
        'notes',
        'is_additional',
        'additional_to_po_id',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'needed_date' => 'date',
            'status' => DocumentStatus::class,
            'is_additional' => 'boolean',
            'total_amount' => 'decimal:2',
        ];
    }

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function additionalToPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'additional_to_po_id');
    }

    public function recalculateTotal(): void
    {
        $total = $this->items()->sum('subtotal');
        $this->forceFill(['total_amount' => $total])->save();
    }
}
