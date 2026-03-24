<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vendor_id
 * @property \Illuminate\Support\Carbon $payment_date
 * @property string $amount
 * @property ?string $reference_no
 * @property ?string $notes
 * @property string $status
 * @property ?string $proof_image_path
 * @property ?\Illuminate\Support\Carbon $proof_uploaded_at
 * @property ?int $approved_by
 * @property ?\Illuminate\Support\Carbon $approved_at
 * @property ?string $rejection_note
 * @property ?int $created_by
 */
class VendorMarginPayment extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'vendor_id',
        'payment_date',
        'amount',
        'reference_no',
        'notes',
        'status',
        'proof_image_path',
        'proof_uploaded_at',
        'approved_by',
        'approved_at',
        'rejection_note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'proof_uploaded_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
