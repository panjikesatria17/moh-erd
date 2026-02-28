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
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'needed_date' => 'date',
            'status' => DocumentStatus::class,
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

    public function recalculateTotal(): void
    {
        $total = $this->items()->sum('subtotal');
        $this->forceFill(['total_amount' => $total])->save();
    }
}
