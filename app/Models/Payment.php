<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $number
 * @property int $invoice_id
 * @property \Illuminate\Support\Carbon $payment_date
 * @property string $amount
 * @property PaymentStatus $status
 * @property ?string $payment_method
 * @property ?string $reference_no
 * @property ?string $proof_image_path
 * @property ?int $proof_uploaded_by
 * @property ?string $proof_uploaded_at
 * @property ?int $approved_by
 * @property ?string $approved_at
 * @property ?string $paid_by
 * @property ?string $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property-read Invoice $invoice
 */
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'invoice_id',
        'payment_date',
        'amount',
        'status',
        'payment_method',
        'reference_no',
        'proof_image_path',
        'proof_uploaded_by',
        'proof_uploaded_at',
        'approved_by',
        'approved_at',
        'paid_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'proof_uploaded_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function proofUploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proof_uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
