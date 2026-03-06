<?php

namespace App\Models;

use App\Enums\FundingRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseFundingRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'purchase_order_id',
        'title',
        'vendor_id',
        'sppg_id',
        'fund_source',
        'requested_amount',
        'reviewed_amount',
        'approved_amount',
        'disbursed_amount',
        'spent_amount',
        'status',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'disbursed_by',
        'disbursed_at',
        'settled_by',
        'settled_at',
        'notes',
        'finance_notes',
        'owner_notes',
        'settlement_proof_path',
        'settlement_proof_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'reviewed_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'disbursed_amount' => 'decimal:2',
            'spent_amount' => 'decimal:2',
            'status' => FundingRequestStatus::class,
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'settled_at' => 'datetime',
            'settlement_proof_uploaded_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function disburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function settler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function getRemainingAmountAttribute(): float
    {
        $disbursed = (float) ($this->disbursed_amount ?? 0);
        $spent = (float) ($this->spent_amount ?? 0);

        return max($disbursed - $spent, 0);
    }
}
